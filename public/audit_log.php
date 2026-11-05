<?php
/**
 * Audit Log (append-only / immutable). Developer hanya melihat log tiket miliknya.
 */
require __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();

// ---------- Data ----------
$logs = vulns()->logsVisibleTo($user);
$titles = array_column(vulns()->all(), 'title', 'id');

// ---------- Tampilan ----------
$pageTitle = 'Audit Log';
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <div class="vt-eyebrow text-muted small fw-semibold text-uppercase">Compliance</div>
        <h1 class="h3 fw-bold mb-0">Audit Log</h1>
        <p class="text-muted small mb-0"><i class="bi bi-lock-fill me-1"></i>Catatan bersifat immutable — tidak dapat diubah atau dihapus.</p>
    </div>
</div>

<div class="card vt-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle js-datatable vt-table w-100" data-order-col="0" data-order-dir="desc">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Waktu</th>
                    <th>Tiket</th>
                    <th>Pengguna</th>
                    <th>Aksi</th>
                    <th>Perubahan Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $l): $actor = Auth::findUser((int) $l['user_id']); ?>
                    <tr>
                        <td class="vt-mono text-muted"><?= (int) $l['id'] ?></td>
                        <td class="small text-nowrap" data-order="<?= e($l['timestamp']) ?>"><?= e(fmt_date($l['timestamp'], 'd M Y, H:i:s')) ?></td>
                        <td>
                            <a href="detail_ticket.php?id=<?= (int) $l['vulnerability_id'] ?>" class="vt-mono fw-semibold text-decoration-none"><?= e(ticket_code((int) $l['vulnerability_id'])) ?></a>
                            <div class="small text-muted text-truncate" style="max-width: 260px"><?= e($titles[$l['vulnerability_id']] ?? '') ?></div>
                        </td>
                        <td class="small">
                            <?= e($actor['name'] ?? '-') ?><br><?= $actor ? role_badge($actor['role']) : '' ?>
                        </td>
                        <td class="small"><?= e($l['action']) ?></td>
                        <td class="text-nowrap">
                            <?php if ($l['old_status'] !== null): ?>
                                <?= status_badge($l['old_status']) ?> <i class="bi bi-arrow-right mx-1 text-muted"></i>
                            <?php endif; ?>
                            <?= status_badge((string) $l['new_status']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require VIEW_PATH . '/layout/footer.php'; ?>

