<?php
require_once __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();

$logs = vulns()->logsVisibleTo($user);
$titles = array_column(vulns()->all(), 'title', 'id');

$pageTitle = 'Audit Log';
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<div class="vt-page-header">
    <h1 class="fw-bold mb-0">Audit Log</h1>
    <span class="badge st-open"><i class="bi bi-lock-fill me-1" aria-hidden="true"></i>Append-only</span>
</div>

<div class="card vt-card">
    <div class="card-body">
        <?php if (empty($logs)): ?>
            <div class="vt-empty-state">
                <div class="vt-empty-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></div>
                <h2 class="h6 fw-semibold mb-1">Belum ada aktivitas audit</h2>
                <p class="small mb-0">Perubahan status tiket akan tercatat otomatis di halaman ini.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle js-datatable vt-table w-100" data-order-col="0" data-order-dir="desc">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Waktu</th>
                        <th>Tiket</th>
                        <th>Pelaksana</th>
                        <th>Tindakan</th>
                        <th>Transisi Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $l): $actor = Auth::findUser((int) $l['user_id']); ?>
                        <tr>
                            <td class="vt-mono text-secondary"><?= (int) $l['id'] ?></td>
                            <td class="small text-nowrap" data-order="<?= e($l['timestamp']) ?>"><?= e(fmt_date($l['timestamp'], 'd M Y, H:i:s')) ?></td>
                            <td>
                                <a href="detail_ticket.php?id=<?= (int) $l['vulnerability_id'] ?>" class="vt-mono fw-semibold text-decoration-none">
                                    <?= e(ticket_code((int) $l['vulnerability_id'])) ?>
                                </a>
                                <div class="small text-secondary text-truncate" style="max-width: 260px"><?= e($titles[$l['vulnerability_id']] ?? '') ?></div>
                            </td>
                            <td class="small">
                                <div class="fw-medium"><?= e($actor['name'] ?? '-') ?></div>
                                <div class="mt-1"><?= $actor ? role_badge($actor['role']) : '' ?></div>
                            </td>
                            <td class="small"><?= e($l['action']) ?></td>
                            <td class="text-nowrap">
                                <?php if ($l['old_status'] !== null): ?>
                                    <?= status_badge($l['old_status']) ?>
                                    <i class="bi bi-arrow-right mx-1 text-secondary" aria-hidden="true"></i>
                                <?php endif; ?>
                                <?= status_badge((string) $l['new_status']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require VIEW_PATH . '/layout/footer.php'; ?>
