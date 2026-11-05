<?php
/**
 * Workflow - Strict state-machine siklus hidup kerentanan.
 *
 *   Open ──triage──▶ Triaged ──start──▶ In Remediation ──submit_patch──▶ Re-test ──pass──▶ Closed
 *                                            ▲                              │
 *                                            └───────────── fail ───────────┘
 *
 * Aturan kunci: Developer TIDAK PERNAH bisa menutup tiket. Penutupan hanya
 * terjadi lewat verifikasi (Re-test) oleh Security Analyst / Admin.
 */

declare(strict_types=1);

final class Workflow
{
    public const ACTIONS = [
        'triage' => [
            'label' => 'Triage & Tugaskan',
            'from'  => ['Open'],
            'to'    => 'Triaged',
            'roles' => ['admin', 'analyst'],
        ],
        'start_remediation' => [
            'label'         => 'Mulai Perbaikan',
            'from'          => ['Open', 'Triaged'],
            'to'            => 'In Remediation',
            'roles'         => ['developer'],
            'assignee_only' => true,
        ],
        'submit_patch' => [
            'label'         => 'Submit Patch',
            'from'          => ['Open', 'Triaged', 'In Remediation'],
            'to'            => 'Re-test',
            'roles'         => ['developer'],
            'assignee_only' => true,
        ],
        'verify_pass' => [
            'label' => 'Verifikasi Lulus',
            'from'  => ['Re-test'],
            'to'    => 'Closed',
            'roles' => ['admin', 'analyst'],
        ],
        'verify_fail' => [
            'label' => 'Verifikasi Gagal',
            'from'  => ['Re-test'],
            'to'    => 'In Remediation',
            'roles' => ['admin', 'analyst'],
        ],
    ];

    public static function exists(string $action): bool
    {
        return isset(self::ACTIONS[$action]);
    }

    /** Apakah user boleh menjalankan aksi pada tiket ini (berdasarkan role + status + kepemilikan). */
    public static function can(string $action, array $vuln, array $user): bool
    {
        $def = self::ACTIONS[$action] ?? null;
        if ($def === null) {
            return false;
        }
        if (!in_array($user['role'], $def['roles'], true)) {
            return false;
        }
        if (!in_array($vuln['status'], $def['from'], true)) {
            return false;
        }
        if (!empty($def['assignee_only']) && (int) $vuln['assignee_id'] !== (int) $user['id']) {
            return false;
        }
        return true;
    }

    /** Daftar aksi yang tersedia untuk user pada tiket ini. */
    public static function available(array $vuln, array $user): array
    {
        return array_values(array_filter(array_keys(self::ACTIONS), fn ($a) => self::can($a, $vuln, $user)));
    }

    public static function target(string $action): string
    {
        return self::ACTIONS[$action]['to'];
    }
}

