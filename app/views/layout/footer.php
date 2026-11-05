<?php
/**
 * Layout: penutup halaman, toast flash message & script CDN.
 * Variabel opsional: $scripts (array path JS tambahan), $useCharts (bool), $withNavbar (bool).
 */
$flashes = pull_flashes();
$withNavbar = $withNavbar ?? true;
?>
<?php if ($withNavbar): ?>
</main>
<footer class="vt-footer text-center small text-muted py-3">
    &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot; Collaborative Vulnerability Lifecycle Platform &middot; v<?= e(APP_VERSION) ?>
</footer>
<?php endif; ?>

<!-- Toast container (flash messages) -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
    <?php foreach ($flashes as $f):
        $icon = match ($f['type']) {
            'success' => 'bi-check-circle-fill text-success',
            'danger'  => 'bi-x-octagon-fill text-danger',
            'warning' => 'bi-exclamation-triangle-fill text-warning',
            default   => 'bi-info-circle-fill text-primary',
        }; ?>
        <div class="toast vt-toast border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
            <div class="d-flex align-items-start p-3 gap-2">
                <i class="bi <?= $icon ?> fs-5"></i>
                <div class="flex-grow-1"><?= e($f['message']) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Tutup"></button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<?php if (!empty($useCharts)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<?php endif; ?>
<script src="assets/js/app.js?v=<?= e(APP_VERSION) ?>"></script>
<?php foreach ($scripts ?? [] as $src): ?>
<script src="<?= e($src) ?>?v=<?= e(APP_VERSION) ?>"></script>
<?php endforeach; ?>
</body>
</html>

