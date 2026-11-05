<?php
/**
 * Detail Tiket + panel aksi state-machine berbasis role & status.
 */
require __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();

// ---------- Data ----------
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$ticket = $id ? vulns()->find($id) : null;
if ($ticket === null) {
    abort(404, 'Tiket yang Anda cari tidak ditemukan.');
}
// Cegah IDOR: developer tidak boleh membuka tiket yang bukan miliknya
if (!vulns()->canView($ticket, $user)) {
    abort(403, 'Tiket ini tidak ditugaskan kepada Anda.');
}

[$old, $errors] = pull_old_input();
$asset      = assets()->find((int) $ticket['asset_id']);
$reporter   = Auth::findUser((int) $ticket['reporter_id']);
$assignee   = Auth::findUser((int) $ticket['assignee_id']);
$notes      = vulns()->notesFor($id);
$logs       = vulns()->logsFor($id);
$sla        = Sla::status($ticket);
$metrics    = Cvss::parseVector($ticket['cvss_vector']);
$actions    = Workflow::available($ticket, $user);
$can        = fn (string $a) => in_array($a, $actions, true);
$developers = Auth::usersByRole('developer');
$stepIndex  = array_search($ticket['status'], STATUSES, true);

// ---------- Tampilan ----------
$pageTitle = ticket_code($id);
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb small mb-2">
        <li class="breadcrumb-item"><a href="vulnerabilities.php">Kerentanan</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e(ticket_code($id)) ?></li>
    </ol>
</nav>

<!-- Header tiket -->
<div class="card vt-card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
                <div class="vt-mono text-muted small mb-1"><?= e(ticket_code($id)) ?> · dilaporkan <?= e(fmt_date($ticket['created_at'])) ?></div>
                <h1 class="h4 fw-bold mb-2"><?= e($ticket['title']) ?></h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <?= severity_badge($ticket['severity']) ?>
                    <?= status_badge($ticket['status']) ?>
                    <?= sla_badge($ticket) ?>
                    <span class="small text-muted"><?= e($sla['remaining']) ?></span>
                </div>
            </div>
            <div class="text-end">
                <div class="vt-score-circle vt-score-lg <?= e(severity_class($ticket['severity'])) ?>"><span><?= e(number_format((float) $ticket['cvss_score'], 1)) ?></span></div>
                <div class="small text-muted mt-1">CVSS v3.1</div>
            </div>
        </div>

        <!-- Stepper workflow -->
        <div class="vt-stepper mt-4">
            <?php foreach (STATUSES as $i => $s): ?>
                <div class="vt-step <?= $i < $stepIndex ? 'done' : ($i === $stepIndex ? 'current' : '') ?>">
                    <div class="vt-step-dot"><?= $i < $stepIndex ? '<i class="bi bi-check-lg"></i>' : $i + 1 ?></div>
                    <div class="vt-step-label"><?= e($s) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Kolom kiri -->
    <div class="col-lg-8">

        <!-- PANEL AKSI (render berdasarkan role & status) -->
        <?php if ($actions): ?>
            <div class="card vt-card vt-action-card mb-4">
                <div class="card-header vt-card-header"><i class="bi bi-lightning-charge me-2"></i>Tindakan yang Diperlukan</div>
                <div class="card-body">

                    <?php if ($can('triage')): ?>
                        <form method="post" action="ticket_action.php" class="row g-2 align-items-end">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $id ?>">
                            <input type="hidden" name="action" value="triage">
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold" for="triage_assignee">Konfirmasi Developer Penanggung Jawab</label>
                                <select name="assignee_id" id="triage_assignee" class="form-select<?= invalid($errors, 'assignee_id') ?>">
                                    <?php foreach ($developers as $d): ?>
                                        <option value="<?= (int) $d['id'] ?>" <?= (int) $d['id'] === (int) $ticket['assignee_id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= field_error($errors, 'assignee_id') ?>
                            </div>
                            <div class="col-md-5">
                                <button class="btn btn-primary w-100"><i class="bi bi-person-check me-1"></i>Triage &amp; Tugaskan</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if ($can('start_remediation')): ?>
                        <form method="post" action="ticket_action.php" class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $id ?>">
                            <input type="hidden" name="action" value="start_remediation">
                            <div class="small text-muted">Tandai bahwa Anda sedang mengerjakan perbaikan celah ini.</div>
                            <button class="btn btn-outline-primary btn-sm"><i class="bi bi-play-fill me-1"></i>Mulai Perbaikan</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($can('submit_patch')): ?>
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-git me-1"></i>Catatan Perbaikan &amp; Submit Patch</h2>
                        <form method="post" action="ticket_action.php" novalidate>
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $id ?>">
                            <input type="hidden" name="action" value="submit_patch">
                            <div class="mb-3">
                                <label for="patch_summary" class="form-label small fw-semibold">Ringkasan Patch <span class="text-danger">*</span></label>
                                <textarea name="patch_summary" id="patch_summary" rows="4" maxlength="3000" class="form-control<?= invalid($errors, 'patch_summary') ?>"
                                          placeholder="Jelaskan akar masalah dan perubahan yang dilakukan..."><?= e($old['patch_summary'] ?? '') ?></textarea>
                                <?= field_error($errors, 'patch_summary') ?>
                            </div>
                            <div class="mb-3">
                                <label for="commit_url" class="form-label small fw-semibold">Link Commit / Pull Request <span class="text-danger">*</span></label>
                                <input type="url" name="commit_url" id="commit_url" class="form-control<?= invalid($errors, 'commit_url') ?>"
                                       value="<?= e($old['commit_url'] ?? '') ?>" placeholder="https://github.com/org/repo/pull/123">
                                <?= field_error($errors, 'commit_url') ?>
                            </div>
                            <button class="btn btn-success"><i class="bi bi-upload me-1"></i>Submit Patch untuk Re-test</button>
                        </form>
                    <?php endif; ?>

                    <?php if ($can('verify_pass') || $can('verify_fail')): ?>
                        <p class="small text-muted mb-3">
                            Developer telah mengirimkan patch. Lakukan pengujian ulang (re-test) lalu tentukan hasil verifikasi.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($can('verify_pass')): ?>
                                <form method="post" action="ticket_action.php" class="js-confirm" data-confirm="Tutup tiket ini? Tindakan tercatat permanen di audit log.">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $id ?>">
                                    <input type="hidden" name="action" value="verify_pass">
                                    <button class="btn btn-success"><i class="bi bi-shield-check me-1"></i>Verifikasi Lulus &amp; Tutup</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($can('verify_fail')): ?>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#failModal">
                                    <i class="bi bi-shield-x me-1"></i>Verifikasi Gagal
                                </button>
                            <?php endif; ?>
                        </div>
                        <?= field_error($errors, 'reason') ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($ticket['status'] !== 'Closed'): ?>
            <div class="alert alert-light border d-flex gap-2 align-items-center small mb-4">
                <i class="bi bi-hourglass-split text-muted"></i>
                <?php
                echo match ($ticket['status']) {
                    'Open'           => 'Menunggu triage oleh Security Analyst.',
                    'Triaged'        => 'Menunggu developer memulai perbaikan.',
                    'In Remediation' => 'Developer sedang melakukan perbaikan. Menunggu patch disubmit.',
                    'Re-test'        => 'Patch telah disubmit. Menunggu verifikasi ulang oleh Security Analyst.',
                    default          => '',
                };
                ?>
            </div>
        <?php endif; ?>

        <!-- Deskripsi -->
        <div class="card vt-card mb-4">
            <div class="card-header vt-card-header"><i class="bi bi-file-text me-2"></i>Deskripsi</div>
            <div class="card-body">
                <div class="vt-description"><?= nl2br(e($ticket['description'])) ?></div>
                <?php if ($ticket['poc_file']): ?>
                    <hr>
                    <a href="download.php?id=<?= (int) $id ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-paperclip me-1"></i>Unduh PoC: <?= e($ticket['poc_original']) ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Catatan Remediasi -->
        <div class="card vt-card mb-4">
            <div class="card-header vt-card-header"><i class="bi bi-wrench-adjustable me-2"></i>Catatan Remediasi <span class="badge text-bg-light ms-1"><?= count($notes) ?></span></div>
            <div class="card-body">
                <?php if (!$notes): ?>
                    <p class="text-muted small mb-0">Belum ada patch yang disubmit.</p>
                <?php endif; ?>
                <?php foreach ($notes as $n): $author = Auth::findUser((int) $n['user_id']); ?>
                    <div class="vt-note mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <strong><?= e($author['name'] ?? 'Unknown') ?></strong>
                            <span class="text-muted"><?= e(fmt_date($n['created_at'])) ?></span>
                        </div>
                        <div class="mb-2"><?= nl2br(e($n['patch_summary'])) ?></div>
                        <a href="<?= e($n['commit_url']) ?>" target="_blank" rel="noopener noreferrer" class="small vt-mono text-break">
                            <i class="bi bi-github me-1"></i><?= e($n['commit_url']) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Kolom kanan -->
    <div class="col-lg-4">
        <div class="card vt-card mb-4">
            <div class="card-header vt-card-header"><i class="bi bi-info-square me-2"></i>Informasi</div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Aset</span>
                    <span class="text-end"><?= e($asset['asset_name'] ?? '-') ?><br><?= $asset ? env_badge($asset['environment']) : '' ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Reporter</span><span><?= e($reporter['name'] ?? '-') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Assignee</span><span><?= e($assignee['name'] ?? '-') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">SLA Deadline</span><span class="fw-semibold"><?= e(fmt_date($ticket['sla_deadline'])) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Dibuat</span><span><?= e(fmt_date($ticket['created_at'])) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Diperbarui</span><span><?= e(fmt_date($ticket['updated_at'])) ?></span></li>
                <?php if ($ticket['closed_at']): ?>
                    <li class="list-group-item d-flex justify-content-between"><span class="text-muted">Ditutup</span><span><?= e(fmt_date($ticket['closed_at'])) ?></span></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card vt-card mb-4">
            <div class="card-header vt-card-header"><i class="bi bi-calculator me-2"></i>Metrik CVSS</div>
            <div class="card-body">
                <div class="vt-mono small text-break mb-3 p-2 bg-light rounded"><?= e($ticket['cvss_vector']) ?></div>
                <table class="table table-sm small mb-0">
                    <?php foreach (Cvss::METRICS as $k => [$label, $options]): ?>
                        <tr>
                            <td class="text-muted"><?= e($label) ?></td>
                            <td class="text-end fw-medium"><?= e($options[$metrics[$k] ?? ''] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="card vt-card">
            <div class="card-header vt-card-header"><i class="bi bi-clock-history me-2"></i>Riwayat Audit</div>
            <div class="card-body">
                <ul class="vt-timeline list-unstyled mb-0">
                    <?php foreach ($logs as $l): $actor = Auth::findUser((int) $l['user_id']); ?>
                        <li>
                            <div class="small fw-semibold"><?= e($l['action']) ?></div>
                            <div class="small text-muted">
                                <?= e($actor['name'] ?? '-') ?> · <?= e(fmt_date($l['timestamp'])) ?>
                            </div>
                            <?php if ($l['old_status'] !== null): ?>
                                <div class="small mt-1"><?= status_badge($l['old_status']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= status_badge($l['new_status']) ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if ($can('verify_fail')): ?>
    <!-- Modal verifikasi gagal -->
    <div class="modal fade" id="failModal" tabindex="-1" aria-labelledby="failModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" action="ticket_action.php" class="modal-content">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $id ?>">
                <input type="hidden" name="action" value="verify_fail">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="failModalLabel"><i class="bi bi-shield-x text-danger me-2"></i>Verifikasi Gagal</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Tiket akan dikembalikan ke status <strong>In Remediation</strong>. Jelaskan temuan re-test agar developer dapat memperbaikinya.</p>
                    <label for="reason" class="form-label small fw-semibold">Alasan / Temuan Re-test <span class="text-danger">*</span></label>
                    <textarea name="reason" id="reason" rows="4" class="form-control" maxlength="1000" required minlength="5"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Kembalikan ke Developer</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php require VIEW_PATH . '/layout/footer.php'; ?>

