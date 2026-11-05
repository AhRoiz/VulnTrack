<?php
require_once __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$ticket = $id ? vulns()->find($id) : null;
if ($ticket === null) {
    abort(404, 'Tiket yang Anda cari tidak ditemukan.');
}

// Tolak akses langsung developer ke tiket yang tidak ditugaskan kepadanya (mitigasi IDOR).
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

$pageTitle = ticket_code($id);
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<nav aria-label="Breadcrumb">
    <ol class="breadcrumb small mb-2">
        <li class="breadcrumb-item"><a href="vulnerabilities.php">Kerentanan</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e(ticket_code($id)) ?></li>
    </ol>
</nav>

<div class="card vt-card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-3">
            <div>
                <div class="vt-mono text-secondary small mb-1"><?= e(ticket_code($id)) ?> | Dilaporkan <?= e(fmt_date($ticket['created_at'])) ?></div>
                <h1 class="h4 fw-bold mb-2"><?= e($ticket['title']) ?></h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <?= severity_badge($ticket['severity']) ?>
                    <?= status_badge($ticket['status']) ?>
                    <?= sla_badge($ticket) ?>
                    <span class="small text-secondary"><?= e($sla['remaining']) ?></span>
                </div>
            </div>
            <div class="text-end">
                <div class="vt-score-circle vt-score-lg <?= e(severity_class($ticket['severity'])) ?>">
                    <span><?= e(number_format((float) $ticket['cvss_score'], 1)) ?></span>
                </div>
                <div class="small text-secondary mt-1">CVSS v3.1</div>
            </div>
        </div>

        <div class="vt-stepper mt-4" aria-label="Tahap workflow tiket">
            <?php foreach (STATUSES as $i => $s): ?>
                <div class="vt-step <?= $i < $stepIndex ? 'done' : ($i === $stepIndex ? 'current' : '') ?>">
                    <div class="vt-step-dot" aria-hidden="true"><?= $i < $stepIndex ? '<i class="bi bi-check-lg"></i>' : $i + 1 ?></div>
                    <div class="vt-step-label"><?= e($s) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <?php if ($actions): ?>
            <div class="card vt-card mb-4">
                <div class="card-header vt-card-header vt-action-header">
                    <h2 class="vt-card-title text-reset">Tindakan Workflow</h2>
                </div>
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
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-person-check" aria-hidden="true"></i>
                                    <span>Triage &amp; Tugaskan</span>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <?php if ($can('start_remediation')): ?>
                        <form method="post" action="ticket_action.php" class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $id ?>">
                            <input type="hidden" name="action" value="start_remediation">
                            <div class="small text-secondary">Tandai bahwa Anda mulai mengerjakan perbaikan celah ini.</div>
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-play-fill" aria-hidden="true"></i>
                                <span>Mulai Perbaikan</span>
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($can('submit_patch')): ?>
                        <h3 class="h6 fw-bold mb-3">Catatan Perbaikan &amp; Pengajuan Patch</h3>
                        <form method="post" action="ticket_action.php" novalidate>
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $id ?>">
                            <input type="hidden" name="action" value="submit_patch">
                            <div class="mb-3">
                                <label for="patch_summary" class="form-label small fw-semibold">Ringkasan Patch <span class="text-danger">*</span></label>
                                <textarea name="patch_summary"
                                          id="patch_summary"
                                          rows="4"
                                          maxlength="3000"
                                          class="form-control<?= invalid($errors, 'patch_summary') ?>"
                                          placeholder="Jelaskan akar masalah dan perubahan kode yang diterapkan..."><?= e($old['patch_summary'] ?? '') ?></textarea>
                                <?= field_error($errors, 'patch_summary') ?>
                            </div>
                            <div class="mb-3">
                                <label for="commit_url" class="form-label small fw-semibold">Tautan Commit / Pull Request <span class="text-danger">*</span></label>
                                <input type="url"
                                       name="commit_url"
                                       id="commit_url"
                                       class="form-control<?= invalid($errors, 'commit_url') ?>"
                                       value="<?= e($old['commit_url'] ?? '') ?>"
                                       placeholder="https://github.com/org/repo/pull/123">
                                <?= field_error($errors, 'commit_url') ?>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-upload" aria-hidden="true"></i>
                                <span>Kirim Patch untuk Re-test</span>
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($can('verify_pass') || $can('verify_fail')): ?>
                        <p class="small text-secondary mb-3">
                            Developer telah mengirimkan patch. Lakukan pengujian ulang (re-test) lalu tentukan status verifikasi.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($can('verify_pass')): ?>
                                <form method="post" action="ticket_action.php" class="js-confirm" data-confirm="Tutup tiket ini? Tindakan akan tercatat permanen di audit log.">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $id ?>">
                                    <input type="hidden" name="action" value="verify_pass">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                                        <span>Verifikasi Lulus &amp; Tutup</span>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($can('verify_fail')): ?>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#failModal">
                                    <i class="bi bi-shield-x" aria-hidden="true"></i>
                                    <span>Verifikasi Gagal</span>
                                </button>
                            <?php endif; ?>
                        </div>
                        <?= field_error($errors, 'reason') ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($ticket['status'] !== 'Closed'): ?>
            <div class="alert alert-light border d-flex gap-2 align-items-center small mb-4" role="status">
                <i class="bi bi-hourglass-split text-secondary" aria-hidden="true"></i>
                <span>
                    <?= match ($ticket['status']) {
                        'Open'           => 'Menunggu triage oleh Security Analyst.',
                        'Triaged'        => 'Menunggu developer memulai perbaikan.',
                        'In Remediation' => 'Developer sedang melakukan perbaikan. Menunggu patch dikirim.',
                        'Re-test'        => 'Patch telah dikirim. Menunggu verifikasi ulang oleh Security Analyst.',
                        default          => '',
                    } ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="card vt-card mb-4">
            <div class="card-header vt-card-header">
                <h2 class="vt-card-title">Deskripsi &amp; Langkah Reproduksi</h2>
            </div>
            <div class="card-body">
                <div class="vt-description"><?= nl2br(e($ticket['description'])) ?></div>
                <?php if ($ticket['poc_file']): ?>
                    <hr>
                    <a href="download.php?id=<?= (int) $id ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-paperclip" aria-hidden="true"></i>
                        <span>Unduh PoC: <?= e($ticket['poc_original']) ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card vt-card mb-4">
            <div class="card-header vt-card-header d-flex align-items-center justify-content-between">
                <h2 class="vt-card-title">Catatan Remediasi</h2>
                <span class="badge st-open"><?= count($notes) ?> catatan</span>
            </div>
            <div class="card-body">
                <?php if (!$notes): ?>
                    <p class="text-secondary small mb-0">Belum ada catatan patch yang dikirim developer.</p>
                <?php endif; ?>
                <?php foreach ($notes as $n): $author = Auth::findUser((int) $n['user_id']); ?>
                    <div class="vt-note mb-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <strong><?= e($author['name'] ?? 'Tidak diketahui') ?></strong>
                            <span class="text-secondary"><?= e(fmt_date($n['created_at'])) ?></span>
                        </div>
                        <div class="mb-2"><?= nl2br(e($n['patch_summary'])) ?></div>
                        <a href="<?= e($n['commit_url']) ?>" target="_blank" rel="noopener noreferrer" class="small vt-mono text-break">
                            <i class="bi bi-git me-1" aria-hidden="true"></i><?= e($n['commit_url']) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card vt-card mb-4">
            <div class="card-header vt-card-header">
                <h2 class="vt-card-title">Informasi Tiket</h2>
            </div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">Aset</span>
                    <span class="text-end"><?= e($asset['asset_name'] ?? '-') ?><br><?= $asset ? env_badge($asset['environment']) : '' ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">Pelapor</span>
                    <span><?= e($reporter['name'] ?? '-') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">Assignee</span>
                    <span><?= e($assignee['name'] ?? '-') ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">Tenggat SLA</span>
                    <span class="fw-semibold"><?= e(fmt_date($ticket['sla_deadline'])) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">Dibuat</span>
                    <span><?= e(fmt_date($ticket['created_at'])) ?></span>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span class="text-secondary">Diperbarui</span>
                    <span><?= e(fmt_date($ticket['updated_at'])) ?></span>
                </li>
                <?php if ($ticket['closed_at']): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="text-secondary">Ditutup</span>
                        <span><?= e(fmt_date($ticket['closed_at'])) ?></span>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card vt-card mb-4">
            <div class="card-header vt-card-header">
                <h2 class="vt-card-title">Rincian Metrik CVSS</h2>
            </div>
            <div class="card-body">
                <div class="vt-mono small text-break mb-3 p-2 bg-light border rounded"><?= e($ticket['cvss_vector']) ?></div>
                <table class="table table-sm small mb-0">
                    <tbody>
                    <?php foreach (Cvss::METRICS as $k => [$label, $options]): ?>
                        <tr>
                            <td class="text-secondary"><?= e($label) ?></td>
                            <td class="text-end fw-medium"><?= e($options[$metrics[$k] ?? ''] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card vt-card">
            <div class="card-header vt-card-header">
                <h2 class="vt-card-title">Riwayat Audit</h2>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <p class="text-secondary small mb-0">Belum ada riwayat perubahan status.</p>
                <?php else: ?>
                    <ul class="vt-timeline list-unstyled mb-0">
                        <?php foreach ($logs as $l): $actor = Auth::findUser((int) $l['user_id']); ?>
                            <li>
                                <div class="small fw-semibold"><?= e($l['action']) ?></div>
                                <div class="small text-secondary">
                                    <?= e($actor['name'] ?? '-') ?> | <?= e(fmt_date($l['timestamp'])) ?>
                                </div>
                                <?php if ($l['old_status'] !== null): ?>
                                    <div class="small mt-1">
                                        <?= status_badge($l['old_status']) ?>
                                        <i class="bi bi-arrow-right mx-1" aria-hidden="true"></i>
                                        <?= status_badge($l['new_status']) ?>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($can('verify_fail')): ?>
    <div class="modal fade" id="failModal" tabindex="-1" aria-labelledby="failModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" action="ticket_action.php" class="modal-content">
                <?= Csrf::field() ?>
                <input type="hidden" name="id" value="<?= (int) $id ?>">
                <input type="hidden" name="action" value="verify_fail">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="failModalLabel">Kembalikan ke Tahap Perbaikan</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup dialog"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-secondary">Tiket akan dikembalikan ke status <strong>In Remediation</strong>. Jelaskan hasil pengujian ulang agar developer dapat menindaklanjutinya.</p>
                    <label for="reason" class="form-label small fw-semibold">Temuan Re-test <span class="text-danger">*</span></label>
                    <textarea name="reason" id="reason" rows="4" class="form-control" maxlength="1000" required minlength="5"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Kembalikan ke Developer</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php require VIEW_PATH . '/layout/footer.php'; ?>
