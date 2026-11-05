<?php
$currentUser = Auth::user();
$page = current_page();
$visibleTickets = $currentUser ? vulns()->visibleTo($currentUser) : [];
$sidebarStats = vulns()->stats($visibleTickets);

$activeMap = [
    'detail_ticket.php' => 'vulnerabilities.php',
];
$activePage = $activeMap[$page] ?? $page;

$navItems = [
    [
        'file'  => 'dashboard.php',
        'label' => 'Dashboard',
        'icon'  => 'bi-grid-1x2',
        'roles' => ['admin', 'analyst', 'developer'],
        'badge' => null,
    ],
    [
        'file'  => 'vulnerabilities.php',
        'label' => 'Kerentanan',
        'icon'  => 'bi-bug',
        'roles' => ['admin', 'analyst', 'developer'],
        'badge' => $sidebarStats['active'] > 0 ? (string) $sidebarStats['active'] : null,
    ],
    [
        'file'  => 'create_ticket.php',
        'label' => 'Tiket Baru',
        'icon'  => 'bi-plus-square',
        'roles' => ['admin', 'analyst'],
        'badge' => null,
    ],
    [
        'file'  => 'forum.php',
        'label' => 'Forum Diskusi',
        'icon'  => 'bi-chat-left-text',
        'roles' => ['admin', 'analyst', 'developer'],
        'badge' => null,
    ],
    [
        'file'  => 'assets.php',
        'label' => 'Aset',
        'icon'  => 'bi-hdd-network',
        'roles' => ['admin'],
        'badge' => null,
    ],
    [
        'file'  => 'audit_log.php',
        'label' => 'Audit Log',
        'icon'  => 'bi-journal-text',
        'roles' => ['admin', 'analyst', 'developer'],
        'badge' => null,
    ],
    [
        'file'  => 'guide.php',
        'label' => 'Bantuan & Panduan',
        'icon'  => 'bi-question-circle',
        'roles' => ['admin', 'analyst', 'developer'],
        'badge' => null,
    ],
];
?>
<aside class="vt-sidebar" id="appSidebar" aria-label="Menu samping aplikasi">
    <div class="vt-sidebar-header">
        <a class="vt-brand" href="dashboard.php">
            <span class="vt-logo" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
            <span class="vt-brand-text">Vuln<strong>Track</strong></span>
        </a>
        <button type="button" class="vt-sidebar-close d-lg-none" id="sidebarCloseBtn" aria-label="Tutup menu navigasi">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <?php if ($currentUser): ?>
        <div class="vt-sidebar-user">
            <span class="vt-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($currentUser['name'], 0, 1))) ?></span>
            <div class="vt-sidebar-user-meta">
                <div class="vt-sidebar-user-name"><?= e($currentUser['name']) ?></div>
                <div class="vt-sidebar-user-role"><?= e(Auth::roleLabel($currentUser['role'])) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <nav class="vt-sidebar-nav" aria-label="Navigasi utama">
        <ul class="vt-nav-list list-unstyled mb-0">
            <?php foreach ($navItems as $item): ?>
                <?php if ($currentUser && in_array($currentUser['role'], $item['roles'], true)):
                    $isActive = $activePage === $item['file']; ?>
                    <li>
                        <a class="vt-nav-item <?= $isActive ? 'active' : '' ?>"
                           href="<?= e($item['file']) ?>"
                           <?= $isActive ? 'aria-current="page"' : '' ?>>
                            <i class="bi <?= e($item['icon']) ?> vt-nav-icon" aria-hidden="true"></i>
                            <span class="vt-nav-label"><?= e($item['label']) ?></span>
                            <?php if ($item['badge'] !== null): ?>
                                <span class="vt-nav-badge" title="Tiket aktif"><?= e($item['badge']) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>

    <?php if ($currentUser): ?>
        <div class="vt-sidebar-footer">
            <form action="logout.php" method="post" class="m-0">
                <?= Csrf::field() ?>
                <button type="submit" class="vt-sidebar-logout">
                    <i class="bi bi-box-arrow-right vt-nav-icon" aria-hidden="true"></i>
                    <span class="vt-nav-label">Keluar</span>
                </button>
            </form>
        </div>
    <?php endif; ?>
</aside>
