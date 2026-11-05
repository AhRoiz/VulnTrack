<?php
/**
 * Unduh file PoC dengan pemeriksaan autentikasi & otorisasi (file disimpan di luar web root).
 */
require __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$ticket = $id ? vulns()->find($id) : null;

if ($ticket === null || empty($ticket['poc_file']) || !vulns()->canView($ticket, $user)) {
    abort(404, 'File PoC tidak ditemukan.');
}

// Nama file tersimpan dibuat sistem (hex acak) — tetap divalidasi untuk mencegah path traversal
if (!preg_match('/^[a-f0-9]{32}\.(png|jpe?g|pdf|txt)$/', $ticket['poc_file'])) {
    abort(404, 'File PoC tidak valid.');
}
$path = UPLOAD_PATH . '/' . $ticket['poc_file'];
if (!is_file($path)) {
    abort(404, 'File PoC tidak ditemukan di penyimpanan.');
}

$ext  = pathinfo($path, PATHINFO_EXTENSION);
$name = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $ticket['poc_original']) ?: 'poc.' . $ext;

header('Content-Type: ' . (POC_ALLOWED[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . $name . '"');
header("Content-Security-Policy: default-src 'none'");
readfile($path);

