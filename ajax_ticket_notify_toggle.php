<?php
ob_start(); // buffer any stray output so it never corrupts the JSON response

include '../inc/connection.inc.php';
if (session_status() === PHP_SESSION_NONE) session_start();

ob_end_clean(); // discard anything output during the includes
header('Content-Type: application/json');

if (!isset($_SESSION['client_id']) || $_SESSION['client_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Geen toegang']);
    exit;
}

$toggle_id = (int)($_POST['client_id'] ?? 0);
$value     = (int)($_POST['value'] ?? 0) ? 1 : 0;

if (!$toggle_id) {
    echo json_encode(['success' => false, 'error' => 'Ongeldig client_id']);
    exit;
}

$chk = mysqli_query($cn, "SELECT client_id FROM photobooth_stat_client
                           WHERE client_id='$toggle_id' AND client_role='admin' AND client_active=1");
if (!$chk || mysqli_num_rows($chk) === 0) {
    echo json_encode(['success' => false, 'error' => 'Beheerder niet gevonden']);
    exit;
}

mysqli_query($cn, "UPDATE photobooth_stat_client SET notify_new_tickets='$value' WHERE client_id='$toggle_id'");

echo json_encode(['success' => true]);
