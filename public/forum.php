<?php
require_once __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();
$categories = forum()->categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    $action = (string) ($_POST['action'] ?? 'create_thread');

    if ($action === 'toggle_subscribe') {
        $tid = (int) ($_POST['thread_id'] ?? 0);
        if (forum()->findThread($tid) === null) {
            abort(404, 'Topik diskusi tidak ditemukan.');
        }
        $nowSubscribed = forum()->toggleSubscribe($tid, (int) $user['id']);
        flash(
            'success',
            $nowSubscribed
                ? 'Anda mengikuti topik ini. Indikator pembaruan diaktifkan.'
                : 'Anda berhenti mengikuti pembaruan topik ini.'
        );
        redirect('forum.php?thread=' . $tid);
    }

    if ($action === 'reply_thread') {
        $tid = (int) ($_POST['thread_id'] ?? 0);
        if (forum()->findThread($tid) === null) {
            abort(404, 'Topik diskusi tidak ditemukan.');
        }
        try {
            forum()->addReply($tid, $_POST, $user);
            flash('success', 'Balasan diskusi berhasil dikirim.');
        } catch (ValidationException $ex) {
            with_old_input(array_merge($_POST, ['_form' => 'reply_thread']), $ex->errors);
            flash('danger', 'Periksa kembali isi pesan balasan Anda.');
        }
        redirect('forum.php?thread=' . $tid);
    }

    try {
        $newThreadId = forum()->createThread($_POST, $user);
        flash('success', 'Topik diskusi baru berhasil dipublikasikan.');
        redirect('forum.php?thread=' . $newThreadId);
    } catch (ValidationException $ex) {
        with_old_input(array_merge($_POST, ['_form' => 'create_thread']), $ex->errors);
        flash('danger', 'Periksa kembali kategori, subjek, dan pesan pembuka topik.');
        $returnCat = trim((string) ($_POST['return_category'] ?? ''));
        $target = $returnCat !== '' && forum()->findCategory($returnCat) !== null
            ? 'forum.php?category=' . urlencode($returnCat)
            : 'forum.php';
        redirect($target);
    }
}

[$old, $errors] = pull_old_input();
$formOrigin = (string) ($old['_form'] ?? '');
$threadErrors = $formOrigin === 'create_thread' ? $errors : [];
$replyErrors  = $formOrigin === 'reply_thread' ? $errors : [];
$threadOld    = $formOrigin === 'create_thread' ? $old : [];
$replyOld     = $formOrigin === 'reply_thread' ? $old : [];

$counts = forum()->categoryCounts();
$activeThread = null;
$replies = [];
$isSubscribed = false;

if (isset($_GET['thread']) && $_GET['thread'] !== '') {
    $rawTid = (string) $_GET['thread'];
    if (!ctype_digit($rawTid)) {
        abort(404, 'Topik diskusi tidak ditemukan.');
    }
    $activeThread = forum()->findThread((int) $rawTid);
    if ($activeThread === null) {
        abort(404, 'Topik diskusi tidak ditemukan.');
    }
    $activeCategorySlug = (string) $activeThread['category'];
    $activeCategory = forum()->findCategory($activeCategorySlug);
    $replies = forum()->repliesForThread((int) $activeThread['id']);
    $isSubscribed = forum()->isSubscribed($activeThread, (int) $user['id']);
    $threads = [];
} else {
    $rawCategory = trim((string) ($_GET['category'] ?? ''));
    $activeCategory = $rawCategory !== '' ? forum()->findCategory($rawCategory) : null;
    $activeCategorySlug = $activeCategory ? $activeCategory['slug'] : '';
    $threads = forum()->threads($activeCategorySlug !== '' ? $activeCategorySlug : null);
}

$selectedModalCategory = (string) ($threadOld['category'] ?? ($activeCategorySlug !== '' ? $activeCategorySlug : 'general-updates'));
$subscribeChecked = $formOrigin === 'create_thread' ? !empty($threadOld['subscribe']) : true;

$pageTitle = $activeThread ? $activeThread['subject'] . ' - Forum Diskusi' : 'Forum Diskusi';
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<div class="vt-page-header">
    <div>
        <h1 class="fw-bold mb-0">Forum Diskusi</h1>
        <p class="vt-page-subtitle">Ruang koordinasi remediasi, verifikasi patch, dan eskalasi SLA antar peran.</p>
    </div>
    <button type="button"
            class="btn btn-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#createThreadModal">
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
        <span>Buat Topik Baru</span>
    </button>
</div>

<div class="row g-4">
    <div class="col-lg-4 col-xl-3">
        <div class="card vt-card vt-sticky">
            <div class="card-header vt-card-header">
                <h2 class="vt-card-title">Kategori Subforum</h2>
            </div>
            <nav aria-label="Kategori subforum" class="card-body p-2 vt-subforum-nav">
                <?php $isAllActive = ($activeCategorySlug === '' && $activeThread === null); ?>
                <a href="forum.php"
                   class="vt-subforum-link <?= $isAllActive ? 'active' : '' ?>"
                   <?= $isAllActive ? 'aria-current="page"' : '' ?>>
                    <div class="d-flex align-items-start gap-2 min-w-0">
                        <i class="bi bi-collection vt-subforum-icon" aria-hidden="true"></i>
                        <div class="min-w-0">
                            <span class="vt-subforum-name">Semua Kategori</span>
                            <span class="vt-subforum-desc">Ringkasan seluruh topik lintas subforum.</span>
                        </div>
                    </div>
                    <span class="vt-subforum-count"><?= (int) $counts['all'] ?></span>
                </a>

                <?php foreach ($categories as $slug => $cat):
                    $isCatActive = ($activeCategorySlug === $slug); ?>
                    <a href="forum.php?category=<?= e($slug) ?>"
                       class="vt-subforum-link <?= $isCatActive ? 'active' : '' ?>"
                       <?= ($isCatActive && $activeThread === null) ? 'aria-current="page"' : '' ?>>
                        <div class="d-flex align-items-start gap-2 min-w-0">
                            <i class="bi <?= e($cat['icon']) ?> vt-subforum-icon" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <span class="vt-subforum-name"><?= e($cat['name']) ?></span>
                                <span class="vt-subforum-desc"><?= e($cat['description']) ?></span>
                            </div>
                        </div>
                        <span class="vt-subforum-count"><?= (int) ($counts[$slug] ?? 0) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>

    <div class="col-lg-8 col-xl-9">
        <?php if ($activeThread !== null):
            $starter = Auth::findUser((int) $activeThread['user_id']);
            $starterName  = $starter['name'] ?? 'Pengguna Internal';
            $starterEmail = $starter['email'] ?? '-';
            $starterRole  = $starter['role'] ?? 'developer';
            $catName      = $activeCategory['name'] ?? 'General Updates';
        ?>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <nav aria-label="Jejak navigasi forum">
                    <ol class="vt-breadcrumb">
                        <li><a href="forum.php">Forum Diskusi</a></li>
                        <li aria-hidden="true">/</li>
                        <li><a href="forum.php?category=<?= e($activeCategorySlug) ?>"><?= e($catName) ?></a></li>
                        <li aria-hidden="true">/</li>
                        <li aria-current="page">Topik #<?= (int) $activeThread['id'] ?></li>
                    </ol>
                </nav>
                <a href="forum.php?category=<?= e($activeCategorySlug) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    <span>Kembali ke Subforum</span>
                </a>
            </div>

            <article class="card vt-card mb-4">
                <div class="card-header vt-card-header">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                        <div>
                            <div class="mb-2">
                                <?= forum_category_badge($activeCategorySlug, $catName) ?>
                            </div>
                            <h2 class="vt-forum-subject"><?= e($activeThread['subject']) ?></h2>
                        </div>

                        <form method="post" action="forum.php?thread=<?= (int) $activeThread['id'] ?>" class="m-0">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="toggle_subscribe">
                            <input type="hidden" name="thread_id" value="<?= (int) $activeThread['id'] ?>">
                            <button type="submit"
                                    class="btn btn-sm vt-subscribe-btn <?= $isSubscribed ? 'is-subscribed' : '' ?>"
                                    aria-pressed="<?= $isSubscribed ? 'true' : 'false' ?>">
                                <i class="bi <?= $isSubscribed ? 'bi-bell-fill' : 'bi-bell' ?>" aria-hidden="true"></i>
                                <span><?= $isSubscribed ? 'Mengikuti Topik' : 'Ikuti Topik' ?></span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card-body">
                    <?php if ($isSubscribed): ?>
                        <div class="vt-subscribe-banner is-active mb-3" role="status">
                            <i class="bi bi-bell-fill" aria-hidden="true"></i>
                            <span><strong>Notifikasi topik aktif:</strong> Anda mengikuti diskusi ini dan akan menerima penanda pembaruan saat ada balasan baru.</span>
                        </div>
                    <?php else: ?>
                        <div class="vt-subscribe-banner mb-3" role="status">
                            <i class="bi bi-bell" aria-hidden="true"></i>
                            <span>Klik <strong>Ikuti Topik</strong> untuk mengaktifkan indikator pembaruan pada diskusi ini.</span>
                        </div>
                    <?php endif; ?>

                    <div class="vt-forum-header">
                        <div class="d-flex align-items-start gap-3">
                            <span class="vt-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($starterName, 0, 1))) ?></span>
                            <div>
                                <div class="vt-forum-author">
                                    <span class="vt-forum-author-name"><?= e($starterName) ?></span>
                                    <?= role_badge($starterRole) ?>
                                    <span class="vt-forum-email"><?= e($starterEmail) ?></span>
                                </div>
                                <div class="small text-secondary mt-1">Pembuka topik</div>
                            </div>
                        </div>
                        <time class="vt-forum-time vt-mono" datetime="<?= e($activeThread['created_at']) ?>">
                            <?= e(fmt_forum_time($activeThread['created_at'])) ?>
                        </time>
                    </div>

                    <div class="vt-forum-body"><?= nl2br(e($activeThread['message'])) ?></div>
                </div>
            </article>

            <section aria-labelledby="repliesHeading" class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h3 class="h6 fw-bold mb-0" id="repliesHeading">Daftar Balasan (<?= count($replies) ?>)</h3>
                </div>

                <?php if (empty($replies)): ?>
                    <div class="card vt-card">
                        <div class="card-body vt-empty-state">
                            <div class="vt-empty-icon"><i class="bi bi-chat-square-dots" aria-hidden="true"></i></div>
                            <div class="fw-semibold text-body mb-1">Belum ada balasan pada topik ini</div>
                            <p class="small mb-0">Tulis tanggapan pertama melalui formulir balasan di bawah.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="vt-reply-list">
                        <?php foreach ($replies as $reply):
                            $rAuthor = Auth::findUser((int) $reply['user_id']);
                            $rName   = $rAuthor['name'] ?? 'Pengguna Internal';
                            $rEmail  = $rAuthor['email'] ?? '-';
                            $rRole   = $rAuthor['role'] ?? 'developer';
                        ?>
                            <article class="vt-reply-card">
                                <div class="vt-forum-header">
                                    <div class="d-flex align-items-start gap-3">
                                        <span class="vt-avatar vt-avatar-sm" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($rName, 0, 1))) ?></span>
                                        <div class="vt-forum-author">
                                            <span class="vt-forum-author-name"><?= e($rName) ?></span>
                                            <?= role_badge($rRole) ?>
                                            <span class="vt-forum-email"><?= e($rEmail) ?></span>
                                        </div>
                                    </div>
                                    <time class="vt-forum-time vt-mono" datetime="<?= e($reply['created_at']) ?>">
                                        <?= e(fmt_forum_time($reply['created_at'])) ?>
                                    </time>
                                </div>
                                <div class="vt-forum-body"><?= nl2br(e($reply['message'])) ?></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section aria-labelledby="replyFormHeading">
                <div class="card vt-card">
                    <div class="card-header vt-card-header">
                        <h3 class="vt-card-title" id="replyFormHeading">Kirim Balasan</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="forum.php?thread=<?= (int) $activeThread['id'] ?>" novalidate>
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="reply_thread">
                            <input type="hidden" name="thread_id" value="<?= (int) $activeThread['id'] ?>">

                            <div class="mb-3">
                                <label for="replyMessage" class="form-label small fw-semibold">Isi Pesan Balasan <span class="text-danger">*</span></label>
                                <textarea id="replyMessage"
                                          name="message"
                                          rows="4"
                                          maxlength="3000"
                                          class="form-control<?= invalid($replyErrors, 'message') ?>"
                                          placeholder="Tulis pembaruan hasil verifikasi, kendala perbaikan, atau tindak lanjut..."
                                          required><?= e($replyOld['message'] ?? '') ?></textarea>
                                <?= field_error($replyErrors, 'message') ?>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-reply" aria-hidden="true"></i>
                                    <span>Kirim Balasan</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <div class="card vt-card mb-3">
                <div class="card-header vt-card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h2 class="vt-card-title"><?= e($activeCategory ? $activeCategory['name'] : 'Semua Topik Diskusi') ?></h2>
                        <p class="vt-card-subtitle"><?= e($activeCategory ? $activeCategory['description'] : 'Menampilkan topik terbaru dari seluruh kategori subforum.') ?></p>
                    </div>
                    <span class="badge st-open"><?= count($threads) ?> topik</span>
                </div>
            </div>

            <?php if (empty($threads)): ?>
                <div class="card vt-card">
                    <div class="card-body vt-empty-state">
                        <div class="vt-empty-icon"><i class="bi bi-chat-left-text" aria-hidden="true"></i></div>
                        <h3 class="h6 fw-semibold text-body mb-1">Belum ada topik pada kategori ini</h3>
                        <p class="small mb-3">Mulai diskusi baru untuk mengoordinasikan perbaikan atau eskalasi tiket.</p>
                        <button type="button"
                                class="btn btn-sm btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#createThreadModal">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            <span>Buat Topik Pertama</span>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="vt-thread-list">
                    <?php foreach ($threads as $t):
                        $author = Auth::findUser((int) $t['user_id']);
                        $authorName  = $author['name'] ?? 'Pengguna Internal';
                        $authorEmail = $author['email'] ?? '-';
                        $authorRole  = $author['role'] ?? 'developer';
                        $catSlug     = (string) ($t['category'] ?? 'general-updates');
                        $catInfo     = $categories[$catSlug] ?? $categories['general-updates'];
                        $userFollows = forum()->isSubscribed($t, (int) $user['id']);
                        $excerpt     = mb_strimwidth((string) $t['message'], 0, 175, '...');
                    ?>
                        <article class="vt-thread-card">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <?= forum_category_badge($catSlug, $catInfo['name']) ?>
                                    <?php if ($userFollows): ?>
                                        <span class="vt-sub-pill" title="Anda mengikuti pembaruan topik ini">
                                            <i class="bi bi-bell-fill" aria-hidden="true"></i>
                                            <span>Mengikuti</span>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <time class="vt-forum-time vt-mono" datetime="<?= e($t['updated_at']) ?>">
                                    Aktif <?= e(fmt_forum_time($t['updated_at'])) ?>
                                </time>
                            </div>

                            <h3 class="vt-thread-title">
                                <a href="forum.php?thread=<?= (int) $t['id'] ?>"><?= e($t['subject']) ?></a>
                            </h3>

                            <p class="vt-thread-excerpt"><?= e($excerpt) ?></p>

                            <div class="vt-thread-meta">
                                <div class="vt-forum-author">
                                    <span class="vt-avatar vt-avatar-sm" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($authorName, 0, 1))) ?></span>
                                    <span class="vt-forum-author-name"><?= e($authorName) ?></span>
                                    <?= role_badge($authorRole) ?>
                                    <span class="vt-forum-email"><?= e($authorEmail) ?></span>
                                </div>
                                <a href="forum.php?thread=<?= (int) $t['id'] ?>" class="vt-thread-stat text-decoration-none">
                                    <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                                    <span><?= (int) $t['reply_count'] ?> balasan</span>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade"
     id="createThreadModal"
     tabindex="-1"
     aria-labelledby="createThreadModalLabel"
     aria-hidden="true"
     data-open-on-load="<?= !empty($threadErrors) ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="post" action="forum.php" novalidate>
                <?= Csrf::field() ?>
                <input type="hidden" name="action" value="create_thread">
                <input type="hidden" name="return_category" value="<?= e($activeCategorySlug) ?>">

                <div class="modal-header">
                    <h2 class="modal-title h6 fw-bold" id="createThreadModalLabel">Buat Topik Diskusi Baru</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup formulir topik baru"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="threadCategory" class="form-label small fw-semibold">Kategori Subforum <span class="text-danger">*</span></label>
                        <select id="threadCategory"
                                name="category"
                                class="form-select<?= invalid($threadErrors, 'category') ?>"
                                required>
                            <?php foreach ($categories as $slug => $cat): ?>
                                <option value="<?= e($slug) ?>" <?= $selectedModalCategory === $slug ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($threadErrors, 'category') ?>
                    </div>

                    <div class="mb-3">
                        <label for="threadSubject" class="form-label small fw-semibold">Subjek Topik <span class="text-danger">*</span></label>
                        <input type="text"
                               id="threadSubject"
                               name="subject"
                               maxlength="150"
                               class="form-control<?= invalid($threadErrors, 'subject') ?>"
                               value="<?= e($threadOld['subject'] ?? '') ?>"
                               placeholder="Contoh: Koordinasi verifikasi patch VT-0002 di staging"
                               required>
                        <?= field_error($threadErrors, 'subject') ?>
                    </div>

                    <div class="mb-3">
                        <label for="threadMessage" class="form-label small fw-semibold">Pesan Pembuka <span class="text-danger">*</span></label>
                        <textarea id="threadMessage"
                                  name="message"
                                  rows="4"
                                  maxlength="3000"
                                  class="form-control<?= invalid($threadErrors, 'message') ?>"
                                  placeholder="Jelaskan konteks teknis, tautan PR terkait, atau keputusan yang dibutuhkan..."
                                  required><?= e($threadOld['message'] ?? '') ?></textarea>
                        <?= field_error($threadErrors, 'message') ?>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input"
                               type="checkbox"
                               id="threadSubscribe"
                               name="subscribe"
                               value="1"
                               <?= $subscribeChecked ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="threadSubscribe">
                            Ikuti topik ini (aktifkan indikator lonceng saat ada balasan baru)
                        </label>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-send" aria-hidden="true"></i>
                        <span>Publikasikan Topik</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require VIEW_PATH . '/layout/footer.php'; ?>
