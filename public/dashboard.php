<?php
/**
 * Dashboard: KPI cards, chart severity & status, tabel tiket.
 */
require __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();

// ---------- Data ----------
$tickets  = vulns()->visibleTo($user);          // developer otomatis hanya tiket miliknya
$stats    = vulns()->stats($tickets);
$assetMap = assets()->map();

$kpis = [
    ['Total Tiket',     $stats['total'],                         'bi-collection',        'kpi-total'],
    ['Open',            $stats['by_status']['Open'],             'bi-envelope-open',     'kpi-open'],
    ['Triaged',         $stats['by_status']['Triaged'],          'bi-person-check',      'kpi-triaged'],
    ['In Remediation',  $stats['by_status']['In Remediation'],   'bi-tools',             'kpi-remediation'],
    ['Re-test',         $stats['by_status']['Re-test'],          'bi-arrow-repeat',      'kpi-retest'],
    ['Closed',          $stats['by_status']['Closed'],           'bi-check2-circle',     'kpi-closed'],
];

// Data yang di-inject ke JavaScript (Chart.js)
$chartData = [
    'severity' => ['labels' => array_keys($stats['by_severity']), 'data' => array_values($stats['by_severity'])],
    'status'   => ['labels' => array_keys($stats['by_status']),   'data' => array_values($stats['by_status'])],
];

// ---------- Tampilan ----------
$pageTitle = 'Dashboard';
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <div class="vt-eyebrow text-muted small fw-semibold text-uppercase">Security Overview</div>
        <h1 class="h3 fw-bold mb-0">Dashboard</h1>
        <p class="text-muted mb-0 small">
            <?php if ($user['role'] === 'developer'): ?>
                Menampilkan tiket yang ditugaskan kepada Anda.
            <?php else: ?>
                Ringkasan seluruh temuan kerentanan di semua aset.
            <?php endif; ?>
        </p>
    </div>
    <div class="small text-muted"><i class="bi bi-clock me-1"></i>Diperbarui <?= e(date('d M Y, H:i')) ?> WIB</div>
</div>

<?php if ($stats['overdue'] > 0): ?>
    <div class="alert vt-alert-overdue d-flex align-items-center gap-3 mb-4" role="alert">
        <i class="bi bi-alarm-fill fs-4"></i>
        <div>
            <strong><?= (int) $stats['overdue'] ?> tiket melewati batas SLA.</strong>
            Segera tindak lanjuti untuk menjaga kepatuhan remediasi.
        </div>
    </div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <?php foreach ($kpis as [$label, $value, $icon, $cls]): ?>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card vt-kpi <?= e($cls) ?> h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="vt-kpi-icon"><i class="bi <?= e($icon) ?>"></i></div>
                    <div>
                        <div class="vt-kpi-value"><?= (int) $value ?></div>
                        <div class="vt-kpi-label"><?= e($label) ?></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Charts -->
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card vt-card h-100">
            <div class="card-header vt-card-header">
                <i class="bi bi-pie-chart me-2"></i>Distribusi Severity
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div class="vt-chart-wrap"><canvas id="severityChart" aria-label="Pie chart severity"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card vt-card h-100">
            <div class="card-header vt-card-header">
                <i class="bi bi-bar-chart me-2"></i>Tiket per Status
            </div>
            <div class="card-body">
                <div class="vt-chart-wrap vt-chart-wide"><canvas id="statusChart" aria-label="Bar chart status"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Ticket table -->
<div class="card vt-card">
    <div class="card-header vt-card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-task me-2"></i>Daftar Tiket</span>
        <?php if (Auth::hasRole('admin', 'analyst')): ?>
            <a href="create_ticket.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Tiket Baru</a>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php
        $tableId = 'dashboardTable';
        $compact = false;
        require VIEW_PATH . '/partials/ticket_table.php';
        ?>
    </div>
</div>

<script type="application/json" id="dashboard-data"><?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php
$useCharts = true;
$scripts = ['assets/js/dashboard.js'];
require VIEW_PATH . '/layout/footer.php';

