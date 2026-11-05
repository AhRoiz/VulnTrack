<?php
require_once __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();

$tickets  = vulns()->visibleTo($user);
$stats    = vulns()->stats($tickets);
$assetMap = assets()->map();

$kpis = [
    [
        'label'   => 'Total Tiket',
        'value'   => $stats['total'],
        'icon'    => 'bi-folder2-open',
        'cls'     => 'vt-kpi-box-primary',
        'href'    => 'vulnerabilities.php',
        'caption' => $stats['active'] . ' aktif',
    ],
    [
        'label'   => 'Open',
        'value'   => $stats['by_status']['Open'],
        'icon'    => 'bi-exclamation-circle',
        'cls'     => '',
        'href'    => 'vulnerabilities.php?status=Open',
        'caption' => 'Antrean triage',
    ],
    [
        'label'   => 'Triaged',
        'value'   => $stats['by_status']['Triaged'],
        'icon'    => 'bi-person-check',
        'cls'     => '',
        'href'    => 'vulnerabilities.php?status=Triaged',
        'caption' => 'Siap dikerjakan',
    ],
    [
        'label'   => 'In Remediation',
        'value'   => $stats['by_status']['In Remediation'],
        'icon'    => 'bi-wrench-adjustable',
        'cls'     => '',
        'href'    => 'vulnerabilities.php?status=' . urlencode('In Remediation'),
        'caption' => 'Dalam perbaikan',
    ],
    [
        'label'   => 'Re-test',
        'value'   => $stats['by_status']['Re-test'],
        'icon'    => 'bi-arrow-repeat',
        'cls'     => '',
        'href'    => 'vulnerabilities.php?status=Re-test',
        'caption' => 'Siap diuji ulang',
    ],
    [
        'label'   => 'Closed',
        'value'   => $stats['by_status']['Closed'],
        'icon'    => 'bi-check2-square',
        'cls'     => '',
        'href'    => 'vulnerabilities.php?status=Closed',
        'caption' => 'Selesai',
    ],
];

$chartData = [
    'severity' => [
        'labels' => array_keys($stats['by_severity']),
        'data'   => array_values($stats['by_severity']),
    ],
    'status' => [
        'labels' => array_keys($stats['by_status']),
        'data'   => array_values($stats['by_status']),
    ],
];

$pageTitle = 'Dashboard';
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<div class="vt-page-header">
    <h1 class="fw-bold mb-0">Dashboard</h1>
    <div class="small text-secondary d-flex align-items-center gap-1">
        <i class="bi bi-clock" aria-hidden="true"></i>
        <span><?= e(date('d M Y, H:i')) ?> WIB</span>
    </div>
</div>

<?php if ($stats['overdue'] > 0): ?>
    <div class="vt-alert-overdue d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4" role="alert">
        <div class="d-flex align-items-center gap-2 small fw-semibold">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            <span><?= (int) $stats['overdue'] ?> tiket melewati tenggat SLA.</span>
        </div>
        <a href="vulnerabilities.php?sla=overdue" class="btn btn-danger btn-sm">
            Lihat Overdue
        </a>
    </div>
<?php endif; ?>

<section aria-label="Metrik tiket" class="mb-4">
    <div class="vt-kpi-grid">
        <?php foreach ($kpis as $kpi): ?>
            <a href="<?= e($kpi['href']) ?>" class="vt-kpi-box <?= e($kpi['cls']) ?>">
                <span class="vt-kpi-stamp" aria-hidden="true">
                    <i class="bi <?= e($kpi['icon']) ?>"></i>
                </span>
                <span class="vt-kpi-body">
                    <span class="vt-kpi-label"><?= e($kpi['label']) ?></span>
                    <span class="vt-kpi-value"><?= (int) $kpi['value'] ?></span>
                    <span class="vt-kpi-action"><?= e($kpi['caption']) ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card vt-card h-100">
            <div class="card-header vt-card-header">
                <h2 class="vt-card-title">Tiket per Severity</h2>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <?php if ($stats['total'] === 0): ?>
                    <div class="vt-empty-state">
                        <div class="vt-empty-icon"><i class="bi bi-pie-chart" aria-hidden="true"></i></div>
                        <div class="fw-semibold text-body">Belum ada data severity</div>
                        <div class="small">Data tampil setelah tiket pertama dibuat.</div>
                    </div>
                <?php else: ?>
                    <div class="vt-chart-wrap">
                        <canvas id="severityChart" role="img" aria-label="Diagram proporsi tiket per tingkat keparahan"></canvas>
                        <p id="severityChartError" class="vt-empty-state small mb-0" hidden>
                            Grafik gagal dimuat. Lihat rincian pada tabel tiket di bawah.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card vt-card h-100">
            <div class="card-header vt-card-header">
                <h2 class="vt-card-title">Tiket per Tahap Workflow</h2>
            </div>
            <div class="card-body">
                <?php if ($stats['total'] === 0): ?>
                    <div class="vt-empty-state">
                        <div class="vt-empty-icon"><i class="bi bi-bar-chart" aria-hidden="true"></i></div>
                        <div class="fw-semibold text-body">Belum ada data workflow</div>
                        <div class="small">Distribusi status tampil saat tiket tersedia.</div>
                    </div>
                <?php else: ?>
                    <div class="vt-chart-wrap">
                        <canvas id="statusChart" role="img" aria-label="Diagram batang jumlah tiket per tahap workflow"></canvas>
                        <p id="statusChartError" class="vt-empty-state small mb-0" hidden>
                            Grafik gagal dimuat. Gunakan filter status pada halaman Kerentanan.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card vt-card">
    <div class="card-header vt-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="vt-card-title">Tiket Terbaru</h2>
        <div class="d-flex flex-wrap gap-2">
            <a href="vulnerabilities.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                <span>Filter</span>
            </a>
            <?php if (Auth::hasRole('admin', 'analyst')): ?>
                <a href="create_ticket.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    <span>Tiket Baru</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($tickets)): ?>
            <div class="vt-empty-state">
                <div class="vt-empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                <div class="fw-semibold text-body mb-1">Belum ada tiket</div>
                <?php if ($user['role'] === 'developer'): ?>
                    <p class="small mb-0">Tidak ada tiket yang ditugaskan ke akun Anda.</p>
                <?php else: ?>
                    <p class="small mb-3">Buat tiket pertama untuk mulai melacak remediasi.</p>
                    <a href="create_ticket.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span>Tiket Baru</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php
            $tableId = 'dashboardTable';
            $compact = false;
            require VIEW_PATH . '/partials/ticket_table.php';
            ?>
        <?php endif; ?>
    </div>
</div>

<script type="application/json" id="dashboard-data"><?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?php
$useCharts = true;
$scripts = ['assets/js/dashboard.js'];
require VIEW_PATH . '/layout/footer.php';
