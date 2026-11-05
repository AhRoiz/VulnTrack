<?php
$user = Auth::user();
?>
<div class="vt-app-wrapper" id="appWrapper">
    <?php require VIEW_PATH . '/layout/sidebar.php'; ?>
    <div class="vt-sidebar-backdrop" id="sidebarBackdrop" hidden></div>

    <div class="vt-main-column">
        <header class="vt-topbar">
            <div class="d-flex align-items-center gap-2">
                <button type="button"
                        class="vt-topbar-btn d-lg-none"
                        id="mobileMenuBtn"
                        aria-controls="appSidebar"
                        aria-expanded="false">
                    <i class="bi bi-list fs-5" aria-hidden="true"></i>
                    <span>Menu</span>
                </button>

                <button type="button"
                        class="vt-topbar-btn vt-topbar-icon-btn d-none d-lg-inline-flex"
                        id="desktopSidebarBtn"
                        aria-controls="appSidebar"
                        aria-expanded="true"
                        aria-label="Ciutkan atau bentangkan sidebar">
                    <i class="bi bi-layout-sidebar" aria-hidden="true"></i>
                </button>

                <span class="vt-topbar-context d-none d-md-inline">
                    Manajemen Siklus Hidup Kerentanan &amp; Remediasi SLA
                </span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <?php if (($sidebarStats['overdue'] ?? 0) > 0): ?>
                    <a href="vulnerabilities.php?sla=overdue" class="vt-topbar-alert" title="Lihat tiket melewati batas SLA">
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                        <span><?= (int) $sidebarStats['overdue'] ?> Overdue</span>
                    </a>
                <?php endif; ?>

                <?php if (Auth::hasRole('admin', 'analyst') && current_page() !== 'create_ticket.php'): ?>
                    <a href="create_ticket.php" class="btn btn-primary btn-sm vt-btn-primary">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>
                        <span>Laporkan Celah</span>
                    </a>
                <?php endif; ?>

                <?php if ($user): ?>
                    <div class="dropdown">
                        <button class="vt-user-trigger dropdown-toggle"
                                type="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-label="Menu akun <?= e($user['name']) ?>">
                            <span class="vt-avatar vt-avatar-sm" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($user['name'], 0, 1))) ?></span>
                            <span class="d-none d-sm-inline text-start lh-sm">
                                <span class="d-block fw-semibold vt-user-trigger-name"><?= e($user['name']) ?></span>
                                <span class="d-block vt-user-trigger-role"><?= e(Auth::roleLabel($user['role'])) ?></span>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end vt-dropdown-menu">
                            <li class="px-3 py-2">
                                <div class="fw-semibold small"><?= e($user['name']) ?></div>
                                <div class="small text-secondary"><?= e($user['email']) ?></div>
                                <div class="mt-2"><?= role_badge($user['role']) ?></div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form action="logout.php" method="post" class="m-0">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="dropdown-item text-danger fw-medium py-2">
                                        <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Keluar Sesi
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <main id="main-content" class="vt-content">
