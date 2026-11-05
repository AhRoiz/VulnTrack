<?php
$flashes = pull_flashes();
$withNavbar = $withNavbar ?? true;
?>
<?php if ($withNavbar): ?>
        </main>
        <footer class="vt-footer">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>&copy; <?= date('Y') ?> <strong><?= e(APP_NAME) ?></strong></span>
                <div class="d-flex align-items-center gap-3">
                    <a href="guide.php" class="text-secondary text-decoration-none">Panduan</a>
                    <span class="vt-mono">v<?= e(APP_VERSION) ?></span>
                </div>
            </div>
        </footer>
    </div>
</div>
<?php endif; ?>

<div class="toast-container position-fixed top-0 end-0 p-3 vt-toast-layer">
    <?php foreach ($flashes as $f):
        $icon = match ($f['type']) {
            'success' => 'bi-check-circle-fill text-success',
            'danger'  => 'bi-x-octagon-fill text-danger',
            'warning' => 'bi-exclamation-triangle-fill text-warning',
            default   => 'bi-info-circle-fill vt-text-accent',
        }; ?>
        <div class="toast vt-toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
            <div class="d-flex align-items-start p-3 gap-2">
                <i class="bi <?= $icon ?> fs-5" aria-hidden="true"></i>
                <div class="flex-grow-1 small fw-medium"><?= e($f['message']) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Tutup notifikasi"></button>
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
<script src="assets/js/app.js?v=<?= e(APP_VERSION) ?>-v3"></script>
<?php foreach ($scripts ?? [] as $src): ?>
<script src="<?= e($src) ?>?v=<?= e(APP_VERSION) ?>-v3"></script>
<?php endforeach; ?>
</body>
</html>
