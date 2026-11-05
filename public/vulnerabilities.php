<?php
/**
 * Daftar Kerentanan (menu "Kerentanan") dengan filter status cepat.
 */
require __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();

// ---------- Data ----------
$all      = vulns()->visibleTo($user);
$filter   = (string) ($_GET['status'] ?? '');
$tickets  = in_array($filter, STATUSES, true) ? array_values(array_filter($all, fn ($t) => $t['status'] === $filter)) : $all;
$filter   = in_array($filter, STATUSES, true) ? $filter : '';
$counts   = vulns()->stats($all)['by_status'];
$assetMap = assets()->map();

// ---------- Tampilan ----------
$pageTitle = 'Kerentanan';
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <div class="vt-eyebrow text-muted small fw-semibold text-uppercase">Vulnerability Register</div>
        <h1 class="h3 fw-bold mb-0">Kerentanan</h1>
    </div>
    <?php if (Auth::hasRole('admin', 'analyst')): ?>
        <a href="create_ticket.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Laporkan Celah Baru</a>
    <?php endif; ?>
</div>

<ul class="nav nav-pills vt-pills mb-3 flex-wrap gap-1">
    <li class="nav-item">
        <a class="nav-link <?= $filter === '' ? 'active' : '' ?>" href="vulnerabilities.php">Semua <span class="badge rounded-pill text-bg-light ms-1"><?= count($all) ?></span></a>
    </li>
    <?php foreach (STATUSES as $s): ?>
        <li class="nav-item">
            <a class="nav-link <?= $filter === $s ? 'active' : '' ?>" href="vulnerabilities.php?status=<?= urlencode($s) ?>">
                <?= e($s) ?> <span class="badge rounded-pill text-bg-light ms-1"><?= (int) $counts[$s] ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="card vt-card">
    <div class="card-body">
        <?php
        $tableId = 'vulnTable';
        $compact = false;
        require VIEW_PATH . '/partials/ticket_table.php';
        ?>
    </div>
</div>
<?php require VIEW_PATH . '/layout/footer.php'; ?>

