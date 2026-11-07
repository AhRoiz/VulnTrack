<?php

declare(strict_types=1);

define('APP_NAME', 'VulnTrack');
define('APP_VERSION', '0.1.0-mvp');
define('APP_TIMEZONE', 'Asia/Jakarta');

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('VIEW_PATH', APP_PATH . '/views');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('UPLOAD_PATH', STORAGE_PATH . '/uploads');

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'vulntrack');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

define('POC_MAX_BYTES', 2 * 1024 * 1024);
define('POC_ALLOWED', [
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'pdf'  => 'application/pdf',
    'txt'  => 'text/plain',
]);

// Target SLA dalam jam per severity (Critical: 24j, High: 72j, Medium: 7h, Low: 14h)
define('SLA_HOURS', [
    'Critical' => 24,
    'High'     => 72,
    'Medium'   => 7 * 24,
    'Low'      => 14 * 24,
]);

define('STATUSES', ['Open', 'Triaged', 'In Remediation', 'Re-test', 'Closed']);
define('SEVERITIES', ['Critical', 'High', 'Medium', 'Low']);


