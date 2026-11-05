<?php
/**
 * Layout: Navbar utama (menu berbasis role + info user login).
 */
$user = Auth::user();
$page = current_page();

// Menu: [file, label, ikon, roles yang boleh melihat]
$menu = [
    ['dashboard.php',       'Dashboard',  'bi-speedometer2',     ['admin', 'analyst', 'developer']],
    ['vulnerabilities.php', 'Kerentanan', 'bi-bug',              ['admin', 'analyst', 'developer']],
    ['assets.php',          'Aset',       'bi-hdd-network',      ['admin']],
    ['audit_log.php',       'Audit Log',  'bi-journal-text',     ['admin', 'analyst', 'developer']],
];
$activeMap = ['create_ticket.php' => 'vulnerabilities.php', 'detail_ticket.php' => 'vulnerabilities.php'];
$active = $activeMap[$page] ?? $page;
?>
<nav class="navbar navbar-expand-lg navbar-dark vt-navbar sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
            <span class="vt-logo"><i class="bi bi-shield-lock-fill"></i></span>
            <span class="fw-bold">Vuln<span class="text-info">Track</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <?php foreach ($menu as [$file, $label, $icon, $roles]): ?>
                    <?php if (in_array($user['role'], $roles, true)): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $active === $file ? 'active' : '' ?>" href="<?= e($file) ?>" <?= $active === $file ? 'aria-current="page"' : '' ?>>
                                <i class="bi <?= e($icon) ?> me-1"></i><?= e($label) ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <?php if (Auth::hasRole('admin', 'analyst')): ?>
                    <a href="create_ticket.php" class="btn btn-sm btn-info fw-semibold">
                        <i class="bi bi-plus-lg me-1"></i>Laporkan Celah
                    </a>
                <?php endif; ?>

                <div class="dropdown">
                    <button class="btn btn-link nav-link d-flex align-items-center gap-2 text-white text-decoration-none dropdown-toggle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="vt-avatar"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
                        <span class="text-start lh-sm d-none d-md-block">
                            <span class="d-block fw-semibold small"><?= e($user['name']) ?></span>
                            <span class="d-block vt-role-label"><?= e(Auth::roleLabel($user['role'])) ?></span>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li class="px-3 py-2">
                            <div class="fw-semibold"><?= e($user['name']) ?></div>
                            <div class="small text-muted"><?= e($user['email']) ?></div>
                            <div class="mt-1"><?= role_badge($user['role']) ?></div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="logout.php" method="post" class="m-0">
                                <?= Csrf::field() ?>
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
<main class="container-fluid px-4 py-4">

