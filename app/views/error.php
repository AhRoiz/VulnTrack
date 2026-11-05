<?php
$pageTitle = $title;
$withNavbar = Auth::check();
require VIEW_PATH . '/layout/header.php';
if ($withNavbar) {
    require VIEW_PATH . '/layout/navbar.php';
}
?>
<?php if (!$withNavbar): ?><main id="main-content" class="container py-5"><?php endif; ?>
<div class="d-flex align-items-center justify-content-center py-5">
    <div class="card vt-card p-4 p-md-5 text-center" style="max-width: 480px; width: 100%;">
        <div class="display-4 fw-bold text-secondary vt-mono mb-2"><?= (int) $code ?></div>
        <h1 class="h4 fw-bold mb-2"><?= e($title) ?></h1>
        <p class="text-secondary small mb-4"><?= e($message) ?></p>
        <div>
            <a href="<?= Auth::check() ? 'dashboard.php' : 'login.php' ?>" class="btn btn-primary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span><?= Auth::check() ? 'Kembali ke Dashboard' : 'Ke Halaman Masuk' ?></span>
            </a>
        </div>
    </div>
</div>
<?php if (!$withNavbar): ?></main><?php endif; ?>
<?php require VIEW_PATH . '/layout/footer.php'; ?>
