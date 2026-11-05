<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once APP_PATH . '/lib/helpers.php';

date_default_timezone_set(APP_TIMEZONE);

spl_autoload_register(function (string $class): void {
    $file = APP_PATH . '/lib/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('VULNTRACKSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

if (!function_exists('store')) {
    function store(): JsonStore
    {
        static $store = null;
        return $store ??= new JsonStore(DATA_FILE);
    }
}

if (!function_exists('vulns')) {
    function vulns(): VulnerabilityService
    {
        static $svc = null;
        return $svc ??= new VulnerabilityService(store());
    }
}

if (!function_exists('assets')) {
    function assets(): AssetRepository
    {
        static $repo = null;
        return $repo ??= new AssetRepository(store());
    }
}

// Sinkronisasi data ke $_SESSION['vulnerabilities'] sesuai spesifikasi Checkpoint 1
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION['vulnerabilities'] = vulns()->all();
}

