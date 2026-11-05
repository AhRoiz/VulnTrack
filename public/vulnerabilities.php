<?php
require_once __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();

$all = vulns()->visibleTo($user);
$assets = assets()->all();
$assetMap = assets()->map();
$developers = Auth::usersByRole('developer');

$rawQ = trim((string) ($_GET['q'] ?? ''));
$rawStatus = (string) ($_GET['status'] ?? '');
$rawSeverity = (string) ($_GET['severity'] ?? '');
$rawAssetId = (string) ($_GET['asset_id'] ?? '');
$rawAssigneeId = (string) ($_GET['assignee_id'] ?? '');
$rawSla = (string) ($_GET['sla'] ?? '');

$filters = [
    'q'           => $rawQ,
    'status'      => in_array($rawStatus, STATUSES, true) ? $rawStatus : '',
    'severity'    => in_array($rawSeverity, SEVERITIES, true) ? $rawSeverity : '',
    'asset_id'    => ctype_digit($rawAssetId) ? (int) $rawAssetId : '',
    'assignee_id' => ($rawAssigneeId === 'unassigned' || ctype_digit($rawAssigneeId)) ? $rawAssigneeId : '',
    'sla'         => in_array($rawSla, ['overdue', 'due_soon', 'on_track', 'met', 'breached'], true) ? $rawSla : '',
];

$tickets = array_filter($all, function (array $t) use ($filters): bool {
    if ($filters['q'] !== '') {
        $q = mb_strtolower($filters['q']);
        $code = mb_strtolower(ticket_code((int) $t['id']));
        $idStr = (string) $t['id'];
        $title = mb_strtolower((string) ($t['title'] ?? ''));
        $desc = mb_strtolower((string) ($t['description'] ?? ''));
        $cve = mb_strtolower((string) ($t['cvss_vector'] ?? ''));
        if (!str_contains($code, $q) && !str_contains($idStr, $q) && !str_contains($title, $q) && !str_contains($desc, $q) && !str_contains($cve, $q)) {
            return false;
        }
    }

    if ($filters['status'] !== '' && $t['status'] !== $filters['status']) {
        return false;
    }

    if ($filters['severity'] !== '' && $t['severity'] !== $filters['severity']) {
        return false;
    }

    if ($filters['asset_id'] !== '' && (int) $t['asset_id'] !== (int) $filters['asset_id']) {
        return false;
    }

    if ($filters['assignee_id'] !== '') {
        if ($filters['assignee_id'] === 'unassigned') {
            if (!empty($t['assignee_id'])) {
                return false;
            }
        } elseif ((int) $t['assignee_id'] !== (int) $filters['assignee_id']) {
            return false;
        }
    }

    if ($filters['sla'] !== '') {
        $slaStatus = Sla::status($t);
        if ($slaStatus['key'] !== $filters['sla']) {
            return false;
        }
    }

    return true;
});

$tickets = array_values($tickets);
$counts = vulns()->stats($all)['by_status'];
$totalAll = count($all);
$totalFiltered = count($tickets);

$pageTitle = 'Kerentanan';
require VIEW_PATH . '/layout/header.php';
require VIEW_PATH . '/layout/navbar.php';
?>
<div class="vt-page-header">
    <div>
        <h1 class="fw-bold mb-0">Daftar Kerentanan</h1>
        <p class="vt-page-subtitle">Saring laporan berdasarkan tahap workflow, tingkat keparahan CVSS, aset, atau batas SLA.</p>
    </div>
    <?php if (Auth::hasRole('admin', 'analyst')): ?>
        <a href="create_ticket.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Laporkan Celah Baru</span>
        </a>
    <?php endif; ?>
</div>

<?php require VIEW_PATH . '/partials/vulnerability_filter.php'; ?>

<div class="card vt-card">
    <div class="card-body">
        <?php if ($totalAll === 0): ?>
            <div class="vt-empty-state">
                <div class="vt-empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></div>
                <h2 class="h6 fw-semibold mb-1">Belum ada tiket kerentanan</h2>
                <p class="small mb-3">Belum ada temuan keamanan yang tercatat untuk hak akses akun Anda.</p>
                <?php if (Auth::hasRole('admin', 'analyst')): ?>
                    <a href="create_ticket.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        <span>Laporkan Celah Baru</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif (empty($tickets)): ?>
            <div class="vt-empty-state">
                <div class="vt-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></div>
                <h2 class="h6 fw-semibold mb-1">Tidak ada tiket yang cocok dengan filter</h2>
                <p class="small mb-3">Ubah kata kunci pencarian atau hapus sebagian kriteria filter untuk menampilkan tiket lainnya.</p>
                <a href="vulnerabilities.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                    <span>Reset Semua Filter</span>
                </a>
            </div>
        <?php else: ?>
            <?php
            $tableId = 'vulnTable';
            $compact = false;
            require VIEW_PATH . '/partials/ticket_table.php';
            ?>
        <?php endif; ?>
    </div>
</div>
<?php require VIEW_PATH . '/layout/footer.php'; ?>
