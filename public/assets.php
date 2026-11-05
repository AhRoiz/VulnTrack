<?php
/**
 * Manajemen Aset (khusus Admin).
 */
require __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireRole('admin');

// ---------- Pemrosesan form ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();
    try {
        assets()->create($_POST);
        flash('success', 'Aset berhasil ditambahkan.');
    } catch (ValidationException $ex) {
        with_old_input($_POST, $ex->errors);
        flash('danger', 'Periksa kembali isian aset.');
    }
    redirect('assets.php');
}

// ---------- Data ----------
[$old, $errors] = pull_old_input();
$assetList = assets()->all();
$countByAsset = array_count_values(array_map(fn ($v) => (int) $v['asset_id'], vulns()->all()));
$openByAsset = array_count_values(array_map(
    fn ($v) => (int) $v['asset_id'],
    array_filter(vulns()->all(), fn ($v) => $v['status'] !== 'Closed')
));

// ---------- Tampilan ----------
$pageTitle = 'Aset';
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <div class="vt-eyebrow text-muted small fw-semibold text-uppercase">Asset Inventory</div>
        <h1 class="h3 fw-bold mb-0">Aset</h1>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assetModal"><i class="bi bi-plus-lg me-1"></i>Tambah Aset</button>
</div>

<div class="card vt-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle js-datatable vt-table w-100" data-order-col="0" data-order-dir="asc">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Aset</th>
                    <th>Environment</th>
                    <th>Deskripsi</th>
                    <th class="text-center">Temuan Aktif</th>
                    <th class="text-center">Total Temuan</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($assetList as $a): ?>
                    <tr>
                        <td class="vt-mono"><?= (int) $a['id'] ?></td>
                        <td class="fw-semibold"><?= e($a['asset_name']) ?></td>
                        <td><?= env_badge($a['environment']) ?></td>
                        <td class="small text-muted"><?= e($a['description']) ?></td>
                        <td class="text-center"><span class="badge <?= ($openByAsset[$a['id']] ?? 0) > 0 ? 'text-bg-danger' : 'text-bg-light' ?>"><?= (int) ($openByAsset[$a['id']] ?? 0) ?></span></td>
                        <td class="text-center"><?= (int) ($countByAsset[$a['id']] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal tambah aset -->
<div class="modal fade" id="assetModal" tabindex="-1" aria-labelledby="assetModalLabel" aria-hidden="true" data-open-on-load="<?= $errors ? '1' : '0' ?>">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="assets.php" class="modal-content" novalidate>
            <?= Csrf::field() ?>
            <div class="modal-header">
                <h2 class="modal-title h5" id="assetModalLabel"><i class="bi bi-hdd-network me-2"></i>Tambah Aset</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="asset_name" class="form-label small fw-semibold">Nama Aset <span class="text-danger">*</span></label>
                    <input type="text" name="asset_name" id="asset_name" maxlength="100" class="form-control<?= invalid($errors, 'asset_name') ?>" value="<?= e($old['asset_name'] ?? '') ?>">
                    <?= field_error($errors, 'asset_name') ?>
                </div>
                <div class="mb-3">
                    <label for="environment" class="form-label small fw-semibold">Environment <span class="text-danger">*</span></label>
                    <select name="environment" id="environment" class="form-select<?= invalid($errors, 'environment') ?>">
                        <?php foreach (AssetRepository::ENVIRONMENTS as $env): ?>
                            <option value="<?= e($env) ?>" <?= ($old['environment'] ?? '') === $env ? 'selected' : '' ?>><?= e(ucfirst($env)) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'environment') ?>
                </div>
                <div>
                    <label for="asset_desc" class="form-label small fw-semibold">Deskripsi</label>
                    <textarea name="description" id="asset_desc" rows="3" maxlength="500" class="form-control<?= invalid($errors, 'description') ?>"><?= e($old['description'] ?? '') ?></textarea>
                    <?= field_error($errors, 'description') ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Aset</button>
            </div>
        </form>
    </div>
</div>
<?php require VIEW_PATH . '/layout/footer.php'; ?>

