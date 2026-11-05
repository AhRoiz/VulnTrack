<?php

declare(strict_types=1);

final class Sla
{
    public static function hours(string $severity): int
    {
        return SLA_HOURS[$severity] ?? SLA_HOURS['Low'];
    }

    public static function deadline(string $severity, ?DateTimeImmutable $from = null): string
    {
        $from ??= new DateTimeImmutable();
        return $from->modify('+' . self::hours($severity) . ' hours')->format('Y-m-d H:i:s');
    }

    /** @return array{key:string,label:string,class:string,remaining:string,seconds:int} */
    public static function status(array $v): array
    {
        $deadline = strtotime($v['sla_deadline']);
        $created  = strtotime($v['created_at']);

        if ($v['status'] === 'Closed') {
            $closed = strtotime($v['closed_at'] ?? $v['updated_at']);
            return $closed <= $deadline
                ? ['key' => 'met', 'label' => 'SLA Terpenuhi', 'class' => 'success', 'remaining' => 'Ditutup tepat waktu', 'seconds' => PHP_INT_MAX]
                : ['key' => 'breached', 'label' => 'SLA Terlewati', 'class' => 'secondary', 'remaining' => 'Ditutup terlambat ' . self::human($closed - $deadline), 'seconds' => PHP_INT_MAX];
        }

        $remaining = $deadline - time();
        $window    = max(1, $deadline - $created);

        if ($remaining < 0) {
            return ['key' => 'overdue', 'label' => 'Overdue', 'class' => 'danger', 'remaining' => 'Terlambat ' . self::human(-$remaining), 'seconds' => $remaining];
        }
        if ($remaining / $window <= 0.25) {
            return ['key' => 'due_soon', 'label' => 'Segera Jatuh Tempo', 'class' => 'warning', 'remaining' => 'Sisa ' . self::human($remaining), 'seconds' => $remaining];
        }
        return ['key' => 'on_track', 'label' => 'On Track', 'class' => 'success', 'remaining' => 'Sisa ' . self::human($remaining), 'seconds' => $remaining];
    }

    public static function human(int $seconds): string
    {
        $d = intdiv($seconds, 86400);
        $h = intdiv($seconds % 86400, 3600);
        $m = intdiv($seconds % 3600, 60);
        if ($d > 0) {
            return "{$d} hari {$h} jam";
        }
        if ($h > 0) {
            return "{$h} jam {$m} mnt";
        }
        return max(1, $m) . ' mnt';
    }
}
