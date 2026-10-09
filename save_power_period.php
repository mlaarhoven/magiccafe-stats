<?php
# Slaat een hele periode in de power planner in één keer op (drag-selectie in page_power_planner.php).
# Een periode is een rechthoek: dag day_from t/m day_to, en binnen elke dag de halfuur-vakjes
# van start_time t/m end_time. Vervangt het per-vakje save_power_setting.php.
global $cn;

include '../inc/connection.inc.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

function fout ( $code, $melding ) {
    http_response_code( $code );
    echo json_encode( array( 'ok' => false, 'error' => $melding ) );
    exit;
}

if ( !isset ( $_SESSION['client_id'] ) ) {
    fout( 401, 'Niet ingelogd' );
}
# check role
if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    fout( 403, 'Geen rechten' );
}
if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    fout( 405, 'Alleen POST' );
}

$event_id   = (int) $_SESSION['event_id'];
$day_from   = isset( $_POST['day_from'] )   ? $_POST['day_from']   : '';
$day_to     = isset( $_POST['day_to'] )     ? $_POST['day_to']     : '';
$start_time = isset( $_POST['start_time'] ) ? $_POST['start_time'] : '';
$end_time   = isset( $_POST['end_time'] )   ? $_POST['end_time']   : '';
$power      = isset( $_POST['power'] )      ? $_POST['power']      : '';

if ( !preg_match( '/^[0-6]$/', $day_from ) || !preg_match( '/^[0-6]$/', $day_to ) || $day_from > $day_to ) {
    fout( 400, 'Ongeldige dagen' );
}
$tijd_patroon = '/^([01][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/';
if ( !preg_match( $tijd_patroon, $start_time ) || !preg_match( $tijd_patroon, $end_time ) || $start_time > $end_time ) {
    fout( 400, 'Ongeldige tijden' );
}
if ( $power !== '0' && $power !== '1' ) {
    fout( 400, 'Ongeldige power' );
}

$stmt = mysqli_prepare( $cn, "UPDATE photobooth_power_planner
                                 SET power       = ?
                               WHERE event_id    = ?
                                 AND day_of_week BETWEEN ? AND ?
                                 AND start_time >= ?
                                 AND end_time   <= ?" );
if ( !$stmt ) {
    fout( 500, 'Opslaan mislukt' );
}
mysqli_stmt_bind_param( $stmt, 'iiiiss', $power, $event_id, $day_from, $day_to, $start_time, $end_time );
if ( !mysqli_stmt_execute( $stmt ) ) {
    fout( 500, 'Opslaan mislukt' );
}

echo json_encode( array( 'ok' => true, 'affected' => mysqli_stmt_affected_rows( $stmt ) ) );
