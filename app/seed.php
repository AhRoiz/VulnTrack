<?php
/**
 * Data awal (seed) - dibuat otomatis saat storage/data.json belum ada.
 * Waktu dibuat relatif terhadap "sekarang" agar indikator SLA bervariasi.
 */

declare(strict_types=1);

$ago = fn (float $hours): string => date('Y-m-d H:i:s', (int) (time() - $hours * 3600));

$assets = [
    ['id' => 1, 'asset_name' => 'Customer Portal',     'environment' => 'production',  'description' => 'Portal web pelanggan (portal.example.com).'],
    ['id' => 2, 'asset_name' => 'Payment API Gateway', 'environment' => 'production',  'description' => 'REST API pemrosesan pembayaran & invoice.'],
    ['id' => 3, 'asset_name' => 'Internal HR System',  'environment' => 'staging',     'description' => 'Aplikasi HRIS internal karyawan.'],
    ['id' => 4, 'asset_name' => 'Mobile Backend API',  'environment' => 'staging',     'description' => 'Backend untuk aplikasi Android & iOS.'],
    ['id' => 5, 'asset_name' => 'Marketing CMS',       'environment' => 'development', 'description' => 'CMS landing page & blog perusahaan.'],
];

/**
 * Definisi tiket contoh:
 * [title, desc, asset, vector, createdHoursAgo, history[[action, from, to, hoursAgo, userId]], notes[[summary, url, hoursAgo]]]
 */
$defs = [
    [
        'IDOR pada endpoint /api/v1/invoices/{id}',
        "Endpoint mengembalikan data invoice milik pengguna lain hanya dengan mengganti parameter ID. Tidak ada validasi kepemilikan objek di sisi server.\n\nLangkah reproduksi:\n1. Login sebagai user A\n2. GET /api/v1/invoices/1043 (milik user B)\n3. Response 200 berisi data sensitif user B",
        2, 'CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:N', 40,
        [
            ['Triage selesai, ditugaskan ke Budi Developer', 'Open', 'Triaged', 39, 2],
            ['Developer mulai melakukan perbaikan', 'Triaged', 'In Remediation', 30, 3],
            ['Patch disubmit, menunggu re-test', 'In Remediation', 'Re-test', 20, 3],
            ['Re-test GAGAL: Endpoint /invoices/{id}/pdf masih bisa diakses lintas user.', 'Re-test', 'In Remediation', 10, 2],
        ],
        [['Menambahkan pengecekan ownership pada InvoiceController@show.', 'https://github.com/example/payment-api/pull/212', 20]],
    ],
    [
        'SQL Injection pada form login Customer Portal',
        "Parameter `username` pada POST /login dirangkai langsung ke query SQL. Payload `' OR 1=1-- -` berhasil melewati autentikasi.",
        1, 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H', 20,
        [
            ['Triage selesai, ditugaskan ke Budi Developer', 'Open', 'Triaged', 19.5, 2],
            ['Developer mulai melakukan perbaikan', 'Triaged', 'In Remediation', 18, 3],
            ['Patch disubmit, menunggu re-test', 'In Remediation', 'Re-test', 6, 3],
        ],
        [['Mengganti query string concatenation dengan PDO prepared statements di AuthRepository.', 'https://github.com/example/customer-portal/pull/88', 6]],
    ],
    [
        'Stored XSS pada kolom komentar tiket support',
        'Input komentar tidak di-encode saat dirender sehingga payload <script> tersimpan dan tereksekusi di browser agen support.',
        1, 'CVSS:3.1/AV:N/AC:L/PR:L/UI:R/S:C/C:L/I:L/A:N', 72,
        [['Triage selesai, ditugaskan ke Budi Developer', 'Open', 'Triaged', 70, 2]],
        [],
    ],
    [
        'Tidak ada rate limiting pada endpoint verifikasi OTP',
        'Endpoint POST /auth/otp/verify tidak membatasi jumlah percobaan sehingga OTP 6 digit dapat di-brute force.',
        4, 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:N/A:N', 9 * 24,
        [],
        [],
    ],
    [
        'Verbose error menampilkan stack trace & path server',
        'Halaman error 500 menampilkan stack trace lengkap, versi framework, dan absolute path server.',
        3, 'CVSS:3.1/AV:N/AC:H/PR:N/UI:N/S:U/C:L/I:N/A:N', 10 * 24,
        [
            ['Triage selesai, ditugaskan ke Budi Developer', 'Open', 'Triaged', 236, 2],
            ['Developer mulai melakukan perbaikan', 'Triaged', 'In Remediation', 200, 3],
            ['Patch disubmit, menunggu re-test', 'In Remediation', 'Re-test', 140, 3],
            ['Re-test LULUS, tiket ditutup', 'Re-test', 'Closed', 120, 2],
        ],
        [['Set APP_DEBUG=false di staging dan menambahkan custom error handler.', 'https://github.com/example/hris/commit/9f3c2ab', 140]],
    ],
    [
        'SSRF pada fitur fetch avatar dari URL',
        'Parameter `avatar_url` diproses server tanpa allowlist sehingga dapat mengakses metadata cloud (169.254.169.254).',
        4, 'CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:C/C:H/I:N/A:N', 2,
        [],
        [],
    ],
    [
        'Library jQuery 1.12 usang dengan CVE publik',
        'Marketing CMS memuat jQuery 1.12.4 yang rentan terhadap CVE-2020-11022/11023.',
        5, 'CVSS:3.1/AV:N/AC:H/PR:N/UI:R/S:U/C:L/I:L/A:N', 20 * 24,
        [
            ['Triage selesai, ditugaskan ke Budi Developer', 'Open', 'Triaged', 470, 1],
            ['Developer mulai melakukan perbaikan', 'Triaged', 'In Remediation', 400, 3],
            ['Patch disubmit, menunggu re-test', 'In Remediation', 'Re-test', 380, 3],
            ['Re-test LULUS, tiket ditutup', 'Re-test', 'Closed', 370, 2],
        ],
        [['Upgrade jQuery ke 3.7.1 dan menghapus plugin yang tidak kompatibel.', 'https://github.com/example/marketing-cms/pull/31', 380]],
    ],
];

$vulns = $notes = $logs = [];
$logId = $noteId = 0;

foreach ($defs as $i => [$title, $desc, $assetId, $vector, $createdAgo, $history, $patches]) {
    $id       = $i + 1;
    $metrics  = Cvss::parseVector($vector);
    $score    = Cvss::score($metrics);
    $severity = Cvss::severity($score);
    $created  = $ago($createdAgo);
    $status   = $history ? end($history)[2] : 'Open';
    $updated  = $history ? $ago(end($history)[3]) : $created;

    $vulns[] = [
        'id'           => $id,
        'title'        => $title,
        'description'  => $desc,
        'asset_id'     => $assetId,
        'reporter_id'  => 2,
        'assignee_id'  => 3,
        'cvss_vector'  => $vector,
        'cvss_score'   => $score,
        'severity'     => $severity,
        'status'       => $status,
        'poc_file'     => null,
        'poc_original' => null,
        'sla_deadline' => Sla::deadline($severity, new DateTimeImmutable($created)),
        'created_at'   => $created,
        'updated_at'   => $updated,
        'closed_at'    => $status === 'Closed' ? $updated : null,
    ];

    $logs[] = ['id' => ++$logId, 'vulnerability_id' => $id, 'user_id' => 2, 'action' => "Tiket dibuat (CVSS {$score}, {$severity})", 'old_status' => null, 'new_status' => 'Open', 'timestamp' => $created];
    foreach ($history as [$action, $from, $to, $hAgo, $uid]) {
        $logs[] = ['id' => ++$logId, 'vulnerability_id' => $id, 'user_id' => $uid, 'action' => $action, 'old_status' => $from, 'new_status' => $to, 'timestamp' => $ago($hAgo)];
    }
    foreach ($patches as [$summary, $url, $hAgo]) {
        $notes[] = ['id' => ++$noteId, 'vulnerability_id' => $id, 'user_id' => 3, 'patch_summary' => $summary, 'commit_url' => $url, 'created_at' => $ago($hAgo)];
    }
}

usort($logs, fn ($a, $b) => strcmp($a['timestamp'], $b['timestamp']));
foreach ($logs as $k => &$l) {
    $l['id'] = $k + 1;
}
unset($l);

return [
    'counters' => [
        'assets'            => count($assets),
        'vulnerabilities'   => count($vulns),
        'remediation_notes' => $noteId,
        'audit_logs'        => count($logs),
    ],
    'assets'            => $assets,
    'vulnerabilities'   => $vulns,
    'remediation_notes' => $notes,
    'audit_logs'        => $logs,
];

