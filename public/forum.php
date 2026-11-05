<?php
require_once __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    try {
        forum()->create($_POST, $user);
        flash('success', 'Diskusi baru berhasil dipublikasikan.');
    } catch (ValidationException $ex) {
        with_old_input($_POST, $ex->errors);
        flash('danger', 'Periksa kembali subjek dan isi pesan diskusi.');
    }
    redirect('forum.php');
}

[$old, $errors] = pull_old_input();
$posts = forum()->all();

$pageTitle = 'Forum Diskusi';
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<div class="vt-page-header">
    <h1 class="fw-bold mb-0">Forum Diskusi</h1>
    <span class="badge st-open"><?= count($posts) ?> topik</span>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card vt-card vt-sticky">
            <div class="card-header vt-card-header">
                <h2 class="vt-card-title">Buat Diskusi Baru</h2>
            </div>
            <div class="card-body">
                <form method="post" action="forum.php" novalidate>
                    <?= Csrf::field() ?>
                    <div class="mb-3">
                        <label for="subject" class="form-label small fw-semibold">Subjek <span class="text-danger">*</span></label>
                        <input type="text"
                               id="subject"
                               name="subject"
                               maxlength="150"
                               class="form-control<?= invalid($errors, 'subject') ?>"
                               value="<?= e($old['subject'] ?? '') ?>"
                               placeholder="Contoh: Update re-test patch VT-0002"
                               required>
                        <?= field_error($errors, 'subject') ?>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label small fw-semibold">Isi Pesan <span class="text-danger">*</span></label>
                        <textarea id="message"
                                  name="message"
                                  rows="4"
                                  maxlength="2000"
                                  class="form-control<?= invalid($errors, 'message') ?>"
                                  placeholder="Bagikan progres perbaikan, kendala teknis, atau jadwal verifikasi..."
                                  required><?= e($old['message'] ?? '') ?></textarea>
                        <?= field_error($errors, 'message') ?>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-send" aria-hidden="true"></i>
                        <span>Kirim Diskusi</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <section aria-labelledby="forumFeedHeading">
            <h2 class="h6 fw-semibold text-secondary mb-3" id="forumFeedHeading">Update Tim Terbaru</h2>

            <?php if (empty($posts)): ?>
                <div class="card vt-card">
                    <div class="card-body vt-empty-state">
                        <div class="vt-empty-icon"><i class="bi bi-chat-left-text" aria-hidden="true"></i></div>
                        <div class="fw-semibold text-body mb-1">Belum ada diskusi tim</div>
                        <p class="small mb-0">Gunakan formulir Buat Diskusi Baru untuk memulai koordinasi antar peran.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="vt-forum-list">
                    <?php foreach ($posts as $post):
                        $author = Auth::findUser((int) $post['user_id']);
                        $authorName = $author['name'] ?? 'Pengguna Internal';
                        $authorEmail = $author['email'] ?? '-';
                        $authorRole = $author['role'] ?? 'developer';
                    ?>
                        <article class="vt-forum-item">
                            <div class="vt-forum-header">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="vt-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($authorName, 0, 1))) ?></span>
                                    <div>
                                        <h3 class="vt-forum-subject"><?= e($post['subject']) ?></h3>
                                        <div class="vt-forum-author">
                                            <span class="vt-forum-author-name"><?= e($authorName) ?></span>
                                            <?= role_badge($authorRole) ?>
                                            <span class="vt-forum-email"><?= e($authorEmail) ?></span>
                                        </div>
                                    </div>
                                </div>
                                <time class="vt-forum-time vt-mono" datetime="<?= e($post['created_at']) ?>">
                                    <?= e(fmt_forum_time($post['created_at'])) ?>
                                </time>
                            </div>
                            <div class="vt-forum-body"><?= nl2br(e($post['message'])) ?></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require VIEW_PATH . '/layout/footer.php'; ?>

