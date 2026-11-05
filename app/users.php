<?php
/**
 * Akun pengguna hardcoded (simulasi, pengganti tabel `users`).
 * Password semua akun: password123 (disimpan sebagai hash Bcrypt).
 */

declare(strict_types=1);

return [
    1 => [
        'id' => 1,
        'name' => 'Administrator',
        'email' => 'admin@vulntrack.com',
        'password' => '$2y$10$emcKewURNuEhOxEA3JlOPOIiu5qMeOc9V41.YpiWp5MJizow75WHO',
        'role' => 'admin',
        'created_at' => '2026-01-01 08:00:00',
    ],
    2 => [
        'id' => 2,
        'name' => 'Sarah Analyst',
        'email' => 'analyst@vulntrack.com',
        'password' => '$2y$10$emcKewURNuEhOxEA3JlOPOIiu5qMeOc9V41.YpiWp5MJizow75WHO',
        'role' => 'analyst',
        'created_at' => '2026-01-01 08:00:00',
    ],
    3 => [
        'id' => 3,
        'name' => 'Budi Developer',
        'email' => 'dev@vulntrack.com',
        'password' => '$2y$10$emcKewURNuEhOxEA3JlOPOIiu5qMeOc9V41.YpiWp5MJizow75WHO',
        'role' => 'developer',
        'created_at' => '2026-01-01 08:00:00',
    ],
];

