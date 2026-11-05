<?php
/**
 * Buat Tiket Kerentanan + Kalkulator CVSS v3.1 interaktif.
 * Hak akses: Security Analyst & Admin.
 */
require __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireRole('admin', 'analyst');

// ---------- Pemrosesan form (POST → Redirect → GET) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    try {
        $id = vulns()->create($_POST, $_FILES['poc_file'] ?? null, $user, assets());
        $ticket = vulns()->find($id);
        flash('success', sprintf(
            'Tiket %s berhasil dibuat (CVSS %.1f – %s). SLA: %s.',
            ticket_code($id),
            $ticket['cvss_score'],
            $ticket['severity'],
            fmt_date($ticket['sla_deadline'])
        ));
        redirect('dashboard.php');
    } catch (ValidationException $ex) {
        with_old_input($_POST, $ex->errors);
        flash('danger', 'Periksa kembali isian formulir.');
        redirect('create_ticket.php');
    }
}

// ---------- Data tampilan ----------
[$old, $errors] = pull_old_input();
$assetList  = assets()->all();
$developers = Auth::usersByRole('developer');
$defaults   = ['AV' => 'N', 'AC' => 'L', 'PR' => 'N', 'UI' => 'N', 'S' => 'U', 'C' => 'N', 'I' => 'N', 'A' => 'N'];
$cvssOld    = array_merge($defaults, array_intersect_key((array) ($old['cvss'] ?? []), $defaults));

$pageTitle = 'Laporkan Celah Baru';
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb small mb-2">
        <li class="breadcrumb-item"><a href="vulnerabilities.php">Kerentanan</a></li>
        <li class="breadcrumb-item active" aria-current="page">Tiket Baru</li>
    </ol>
</nav>
<div class="mb-4">
    <h1 class="h3 fw-bold mb-0">Laporkan Celah Keamanan</h1>
    <p class="text-muted small mb-0">Lengkapi detail temuan dan skor keparahan menggunakan kalkulator CVSS v3.1.</p>
</div>

<form method="post" action="create_ticket.php" enctype="multipart/form-data" id="ticketForm" novalidate>
    <?= Csrf::field() ?>
    <div class="row g-4">
        <!-- Kolom kiri: detail temuan -->
        <div class="col-lg-7">
            <div class="card vt-card mb-4">
                <div class="card-header vt-card-header"><i class="bi bi-file-earmark-text me-2"></i>Detail Temuan</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold small">Judul Kerentanan <span class="text-danger">*</span></label>
                        <input type="text" id="title" name="title" maxlength="150" class="form-control<?= invalid($errors, 'title') ?>"
                               value="<?= e($old['title'] ?? '') ?>" placeholder="mis. IDOR pada endpoint /api/orders/{id}" required>
                        <?= field_error($errors, 'title') ?>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="asset_id" class="form-label fw-semibold small">Aset Terdampak <span class="text-danger">*</span></label>
                            <select id="asset_id" name="asset_id" class="form-select<?= invalid($errors, 'asset_id') ?>" required>
                                <option value="">— Pilih aset —</option>
                                <?php foreach ($assetList as $a): ?>
                                    <option value="<?= (int) $a['id'] ?>" <?= (int) ($old['asset_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>>
                                        <?= e($a['asset_name']) ?> (<?= e($a['environment']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= field_error($errors, 'asset_id') ?>
                        </div>
                        <div class="col-md-6">
                            <label for="assignee_id" class="form-label fw-semibold small">Assign ke Developer <span class="text-danger">*</span></label>
                            <select id="assignee_id" name="assignee_id" class="form-select<?= invalid($errors, 'assignee_id') ?>" required>
                                <option value="">— Pilih developer —</option>
                                <?php foreach ($developers as $d): ?>
                                    <option value="<?= (int) $d['id'] ?>" <?= (int) ($old['assignee_id'] ?? 0) === (int) $d['id'] ? 'selected' : '' ?>>
                                        <?= e($d['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= field_error($errors, 'assignee_id') ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold small">Deskripsi &amp; Langkah Reproduksi <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" rows="8" maxlength="5000" class="form-control<?= invalid($errors, 'description') ?>"
                                  placeholder="Jelaskan dampak, endpoint terdampak, dan langkah reproduksi..." required><?= e($old['description'] ?? '') ?></textarea>
                        <?= field_error($errors, 'description') ?>
                    </div>

                    <div>
                        <label for="poc_file" class="form-label fw-semibold small">Proof of Concept (opsional)</label>
                        <input type="file" id="poc_file" name="poc_file" class="form-control<?= invalid($errors, 'poc_file') ?>" accept=".png,.jpg,.jpeg,.pdf,.txt">
                        <div class="form-text">PNG, JPG, PDF, atau TXT. Maksimal 2 MB.</div>
                        <?= field_error($errors, 'poc_file') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom kanan: kalkulator CVSS -->
        <div class="col-lg-5">
            <div class="card vt-card vt-sticky">
                <div class="card-header vt-card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-calculator me-2"></i>Kalkulator CVSS v3.1</span>
                    <a href="https://www.first.org/cvss/v3.1/specification-document" target="_blank" rel="noopener noreferrer" class="small text-decoration-none">Spesifikasi <i class="bi bi-box-arrow-up-right"></i></a>
                </div>
                <div class="card-body">
                    <!-- Hasil skor real-time -->
                    <div class="vt-score-panel mb-3" id="scorePanel">
                        <div class="d-flex align-items-center gap-3">
                            <div class="vt-score-circle" id="scoreCircle"><span id="cvssScore">0.0</span></div>
                            <div class="flex-grow-1">
                                <div class="small text-muted">Severity</div>
                                <div><span class="badge fs-6" id="cvssSeverity">None</span></div>
                                <div class="small mt-1"><i class="bi bi-stopwatch me-1"></i>SLA: <strong id="cvssSla">—</strong></div>
                            </div>
                        </div>
                        <div class="vt-mono small mt-3 text-break" id="cvssVector">CVSS:3.1/…</div>
                    </div>

                    <?php if (isset($errors['cvss'])): ?>
                        <div class="alert alert-danger small py-2"><?= e($errors['cvss']) ?></div>
                    <?php endif; ?>

                    <?php foreach (Cvss::METRICS as $key => [$label, $options]): ?>
                        <div class="mb-2">
                            <div class="small fw-semibold mb-1"><?= e($label) ?> <span class="text-muted vt-mono">(<?= e($key) ?>)</span></div>
                            <div class="btn-group btn-group-sm w-100 vt-metric" role="group" aria-label="<?= e($label) ?>">
                                <?php foreach ($options as $val => $optLabel):
                                    $inputId = 'cvss_' . $key . '_' . $val; ?>
                                    <input type="radio" class="btn-check js-cvss" name="cvss[<?= e($key) ?>]" id="<?= e($inputId) ?>" value="<?= e($val) ?>"
                                           data-metric="<?= e($key) ?>" <?= $cvssOld[$key] === $val ? 'checked' : '' ?> autocomplete="off">
                                    <label class="btn btn-outline-secondary" for="<?= e($inputId) ?>"><?= e($optLabel) ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="small text-muted mt-3">
                        <i class="bi bi-info-circle me-1"></i>Skor final dihitung ulang di server. SLA:
                        Critical 24 jam · High 72 jam · Medium 7 hari · Low 14 hari.
                    </div>
                </div>
                <div class="card-footer bg-white border-0 pb-3">
                    <button type="submit" class="btn btn-primary w-100 fw-semibold" id="submitBtn">
                        <i class="bi bi-send me-1"></i>Submit Laporan
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script type="application/json" id="sla-config"><?= json_encode(SLA_HOURS, JSON_HEX_TAG) ?></script>
<?php
$scripts = ['assets/js/cvss.js'];
require VIEW_PATH . '/layout/footer.php';

