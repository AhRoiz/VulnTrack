<?php
/**
 * Partial: tabel tiket (DataTables).
 * Variabel: $tickets (array), $assetMap (array id => asset), $tableId (string), $compact (bool)
 */
$compact = $compact ?? false;
?>
<div class="table-responsive">
    <table id="<?= e($tableId ?? 'ticketTable') ?>" class="table table-hover align-middle js-datatable vt-table w-100" data-order-col="0" data-order-dir="desc">
        <thead>
        <tr>
            <th>ID</th>
            <th>Judul</th>
            <?php if (!$compact): ?><th>Aset</th><?php endif; ?>
            <th>Severity</th>
            <th>CVSS</th>
            <th>Status</th>
            <th>SLA</th>
            <?php if (!$compact): ?><th>Assignee</th><?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($tickets as $t):
            $asset = $assetMap[$t['asset_id']] ?? null;
            $assignee = Auth::findUser((int) $t['assignee_id']);
            $sla = Sla::status($t);
            $sevRank = array_search($t['severity'], SEVERITIES, true); ?>
            <tr>
                <td data-order="<?= (int) $t['id'] ?>">
                    <a href="detail_ticket.php?id=<?= (int) $t['id'] ?>" class="vt-mono fw-semibold text-decoration-none"><?= e(ticket_code((int) $t['id'])) ?></a>
                </td>
                <td>
                    <a href="detail_ticket.php?id=<?= (int) $t['id'] ?>" class="text-body text-decoration-none fw-medium vt-title-cell"><?= e($t['title']) ?></a>
                </td>
                <?php if (!$compact): ?>
                    <td class="small"><?= e($asset['asset_name'] ?? '-') ?></td>
                <?php endif; ?>
                <td data-order="<?= 10 - (int) $sevRank ?>"><?= severity_badge($t['severity']) ?></td>
                <td class="vt-mono"><?= e(number_format((float) $t['cvss_score'], 1)) ?></td>
                <td data-order="<?= (int) array_search($t['status'], STATUSES, true) ?>"><?= status_badge($t['status']) ?></td>
                <td data-order="<?= $sla['seconds'] === PHP_INT_MAX ? PHP_INT_MAX : (int) $sla['seconds'] ?>">
                    <?= sla_badge($t) ?>
                    <div class="small text-muted mt-1"><?= e($sla['remaining']) ?></div>
                </td>
                <?php if (!$compact): ?>
                    <td class="small"><?= e($assignee['name'] ?? '-') ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

