<?php
/**
 * Endpoint aksi state-machine tiket (POST only + CSRF).
 * Semua validasi role/status/kepemilikan dilakukan di server (Workflow + VulnerabilityService).
 */
require __DIR__ . '/../app/bootstrap.php';

$user = Auth::requireLogin();
require_post();

$id     = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$action = (string) ($_POST['action'] ?? '');
$back   = 'detail_ticket.php?id=' . $id;

try {
    $message = vulns()->applyAction($id, $action, $user, $_POST);
    flash('success', $message);
} catch (ValidationException $ex) {
    with_old_input($_POST, $ex->errors);
    flash('danger', $ex->first());
} catch (DomainException $ex) {
    flash('danger', $ex->getMessage());
}

// Jika developer tidak lagi berhak melihat tiket, kembali ke dashboard
$ticket = vulns()->find($id);
redirect($ticket && vulns()->canView($ticket, $user) ? $back : 'dashboard.php');

