<?php
/**
 * Halaman error generik. Variabel: $code, $title, $message.
 */
$pageTitle = $title;
$withNavbar = Auth::check();
require VIEW_PATH . '/layout/header.php';
if ($withNavbar) {
    require VIEW_PATH . '/layout/navbar.php';
}
?>
<div class="d-flex align-items-center justify-content-center" style="min-height: 60vh">
    <div class="text-center">
        <div class="display-1 fw-bold text-body-tertiary"><?= (int) $code ?></div>
        <h1 class="h4 fw-semibold"><?= e($title) ?></h1>
        <p class="text-muted"><?= e($message) ?></p>
        <a href="<?= Auth::check() ? 'dashboard.php' : 'login.php' ?>" class="btn btn-primary">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>
<?php require VIEW_PATH . '/layout/footer.php'; ?>

