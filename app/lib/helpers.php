<?php
/**
 * Helper global (escaping, redirect, flash message, badge UI, dsb).
 */

declare(strict_types=1);

/** Escape output HTML (mitigasi XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** Simpan pesan flash (ditampilkan sebagai toast pada request berikutnya). */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** Simpan input lama & error validasi untuk ditampilkan ulang setelah redirect (PRG pattern). */
function with_old_input(array $input, array $errors): void
{
    unset($input['_csrf']);
    $_SESSION['_old'] = $input;
    $_SESSION['_errors'] = $errors;
}

function pull_old_input(): array
{
    $data = [$_SESSION['_old'] ?? [], $_SESSION['_errors'] ?? []];
    unset($_SESSION['_old'], $_SESSION['_errors']);
    return $data;
}

/** Hentikan request dengan halaman error. */
function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    $titles = [403 => 'Akses Ditolak', 404 => 'Tidak Ditemukan', 405 => 'Metode Tidak Diizinkan', 419 => 'Sesi Kedaluwarsa'];
    $title = $titles[$code] ?? 'Terjadi Kesalahan';
    require VIEW_PATH . '/error.php';
    exit;
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        abort(405, 'Endpoint ini hanya menerima request POST.');
    }
    Csrf::verify();
}

function current_page(): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '');
}

function fmt_date(?string $date, string $format = 'd M Y, H:i'): string
{
    return $date ? date($format, strtotime($date)) : '-';
}

function ticket_code(int $id): string
{
    return 'VT-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

/* ---------------------------- Badge UI ---------------------------- */

function severity_class(string $severity): string
{
    return match ($severity) {
        'Critical' => 'sev-critical',
        'High'     => 'sev-high',
        'Medium'   => 'sev-medium',
        default    => 'sev-low',
    };
}

function severity_badge(string $severity): string
{
    return '<span class="badge ' . severity_class($severity) . '">' . e($severity) . '</span>';
}

function status_badge(string $status): string
{
    $cls = match ($status) {
        'Open'           => 'st-open',
        'Triaged'        => 'st-triaged',
        'In Remediation' => 'st-remediation',
        'Re-test'        => 'st-retest',
        'Closed'         => 'st-closed',
        default          => 'bg-secondary',
    };
    return '<span class="badge ' . $cls . '">' . e($status) . '</span>';
}

function sla_badge(array $vuln): string
{
    $s = Sla::status($vuln);
    $text = $s['class'] === 'warning' ? 'text-dark' : '';
    return '<span class="badge bg-' . $s['class'] . ' ' . $text . '" title="' . e($s['remaining']) . '">' . e($s['label']) . '</span>';
}

function role_badge(string $role): string
{
    $cls = match ($role) {
        'admin'   => 'bg-dark',
        'analyst' => 'bg-primary',
        default   => 'bg-success',
    };
    return '<span class="badge ' . $cls . '">' . e(Auth::roleLabel($role)) . '</span>';
}

function env_badge(string $env): string
{
    $cls = match ($env) {
        'production' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
        'staging'    => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        default      => 'bg-info-subtle text-info-emphasis border border-info-subtle',
    };
    return '<span class="badge ' . $cls . '">' . e(ucfirst($env)) . '</span>';
}

/** Tampilkan pesan error validasi untuk sebuah field. */
function field_error(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<div class="invalid-feedback d-block">' . e($errors[$field]) . '</div>' : '';
}

function invalid(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' is-invalid' : '';
}

