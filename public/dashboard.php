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
        'cls'     => 'kpi-total vt-kpi-box-primary',
        'href'    => 'vulnerabilities.php',
        'caption' => $stats['active'] . ' aktif',
    ],
    [
        'label'   => 'Open',
        'value'   => $stats['by_status']['Open'],
        'icon'    => 'bi-exclamation-circle',
        'cls'     => 'kpi-open',
        'href'    => 'vulnerabilities.php?status=Open',
        'caption' => 'Menunggu triage',
    ],
    [
        'label'   => 'Triaged',
        'value'   => $stats['by_status']['Triaged'],
        'icon'    => 'bi-person-check',
        'cls'     => 'kpi-triaged',
        'href'    => 'vulnerabilities.php?status=Triaged',
        'caption' => 'Siap dikerjakan',
    ],
    [
        'label'   => 'In Remediation',
        'value'   => $stats['by_status']['In Remediation'],
        'icon'    => 'bi-wrench-adjustable',
        'cls'     => 'kpi-remediation',
        'href'    => 'vulnerabilities.php?status=' . urlencode('In Remediation'),
        'caption' => 'Sedang diperbaiki',
    ],
    [
        'label'   => 'Re-test',
        'value'   => $stats['by_status']['Re-test'],
        'icon'    => 'bi-arrow-repeat',
        'cls'     => 'kpi-retest',
        'href'    => 'vulnerabilities.php?status=Re-test',
        'caption' => 'Menunggu verifikasi',
    ],
    [
        'label'   => 'Closed',
        'value'   => $stats['by_status']['Closed'],
        'icon'    => 'bi-check2-square',
        'cls'     => 'kpi-closed',
        'href'    => 'vulnerabilities.php?status=Closed',
        'caption' => 'Terverifikasi aman',
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
    <div>
        <h1 class="fw-bold mb-0">Dashboard Keamanan</h1>
        <p class="vt-page-subtitle">
            <?php if ($user['role'] === 'developer'): ?>
                Menampilkan antrean kerentanan yang ditugaskan kepada Anda beserta tenggat SLA aktif.
            <?php else: ?>
                Ringkasan temuan kerentanan lintas aset, tahap workflow remediasi, dan kepatuhan SLA.
            <?php endif; ?>
        </p>
    </div>
    <div class="small text-secondary d-flex align-items-center gap-1">
        <i class="bi bi-clock" aria-hidden="true"></i>
        <span>Diperbarui <?= e(date('d M Y, H:i')) ?> WIB</span>
    </div>
</div>

<?php if ($stats['overdue'] > 0): ?>
    <div class="vt-alert-overdue d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4" role="alert">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-exclamation-triangle-fill fs-4" aria-hidden="true"></i>
            <div>
                <div class="fw-bold"><?= (int) $stats['overdue'] ?> tiket melewati batas waktu SLA.</div>
                <div class="small">Prioritaskan perbaikan atau verifikasi pada tiket yang terlambat agar target remediasi terpenuhi.</div>
            </div>
        </div>
        <a href="vulnerabilities.php?sla=overdue" class="btn btn-danger btn-sm">
            Lihat Tiket Overdue
        </a>
    </div>
<?php endif; ?>

<section aria-label="Ringkasan metrik tiket" class="mb-4">
    <div class="vt-kpi-grid">
        <?php foreach ($kpis as $kpi): ?>
            <a href="<?= e($kpi['href']) ?>" class="vt-kpi-box <?= e($kpi['cls']) ?>">
                <span class="vt-kpi-stamp" aria-hidden="true">
                    <i class="bi <?= e(strtolower($kpi['icon'])) ?>"></i>
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
                <h2 class="vt-card-title">Proporsi Temuan Berdasarkan Severity</h2>
                <p class="vt-card-subtitle">Distribusi tingkat keparahan CVSS v3.1</p>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <?php if ($stats['total'] === 0): ?>
                    <div class="vt-empty-state">
                        <div class="vt-empty-icon"><i class="bi bi-pie-chart" aria-hidden="true"></i></div>
                        <div class="fw-semibold text-body">Belum ada data severity</div>
                        <div class="small">Grafik akan muncul setelah laporan kerentanan pertama tercatat.</div>
                    </div>
                <?php else: ?>
                    <div class="vt-chart-wrap">
                        <canvas id="severityChart" role="img" aria-label="Diagram proporsi kerentanan berdasarkan tingkat keparahan"></canvas>
                        <p id="severityChartError" class="vt-empty-state small mb-0" hidden>
                            Grafik tidak dapat dimuat. Gunakan tabel daftar tiket di bawah untuk melihat rincian severity.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card vt-card h-100">
            <div class="card-header vt-card-header">
                <h2 class="vt-card-title">Sebaran Tiket di Tiap Tahap Workflow</h2>
                <p class="vt-card-subtitle">Jumlah tiket aktif hingga selesai diverifikasi</p>
            </div>
            <div class="card-body">
                <?php if ($stats['total'] === 0): ?>
                    <div class="vt-empty-state">
                        <div class="vt-empty-icon"><i class="bi bi-bar-chart" aria-hidden="true"></i></div>
                        <div class="fw-semibold text-body">Belum ada data workflow</div>
                        <div class="small">Distribusi status akan tampil saat tiket tersedia.</div>
                    </div>
                <?php else: ?>
                    <div class="vt-chart-wrap">
                        <canvas id="statusChart" role="img" aria-label="Diagram batang jumlah tiket per tahap workflow"></canvas>
                        <p id="statusChartError" class="vt-empty-state small mb-0" hidden>
                            Grafik tidak dapat dimuat. Gunakan filter status pada halaman Kerentanan untuk melihat rinciannya.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card vt-card">
    <div class="card-header vt-card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="vt-card-title">Daftar Tiket Kerentanan</h2>
            <p class="vt-card-subtitle">Urutan terbaru berdasarkan nomor tiket</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="vulnerabilities.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-funnel" aria-hidden="true"></i>
                <span>Filter Lanjutan</span>
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
                <div class="fw-semibold text-body mb-1">Belum ada tiket kerentanan</div>
                <?php if ($user['role'] === 'developer'): ?>
                    <p class="small mb-0">Saat ini tidak ada celah keamanan yang ditugaskan kepada akun Anda.</p>
                <?php else: ?>
                    <p class="small mb-3">Catat temuan keamanan pertama untuk mulai melacak proses remediasi dan SLA.</p>
                    <a href="create_ticket.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span>Laporkan Celah Baru</span>
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
