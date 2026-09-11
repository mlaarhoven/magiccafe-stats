<?php
# ---------------------------------------------------------------------------
#  ajax_network_save.php — opslaan van de netwerk topologie (page_network.php)
#  Verwacht POST 'payload' = JSON { event_id, base_ip, modem_type, note, nodes, edges }
#  Antwoord: {"success":true,"updated":"..."} of {"success":false,"error":"..."}
#  De validatie van nodes/edges zit in inc/network_topology_functions.php (gedeeld
#  met ajax_network_template.php).
# ---------------------------------------------------------------------------
ob_start(); // vang eventuele stray output uit de includes op

include '../inc/connection.inc.php';
require_once __DIR__ . '/inc/network_topology_functions.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

ob_end_clean();
header('Content-Type: application/json');

if ( !isset($_SESSION['client_id']) || $_SESSION['client_role'] !== 'admin' ) {
    echo json_encode(['success' => false, 'error' => 'Geen toegang']);
    exit;
}

$payload = json_decode( isset($_POST['payload']) ? $_POST['payload'] : '', true );
if ( !is_array($payload) ) {
    echo json_encode(['success' => false, 'error' => 'Ongeldige gegevens']);
    exit;
}

$event_id = (int) ( isset($payload['event_id']) ? $payload['event_id'] : 0 );
if ( $event_id <= 0 ) {
    echo json_encode(['success' => false, 'error' => 'Ongeldig event_id']);
    exit;
}
$chk = mysqli_query( $cn, "SELECT event_id FROM photobooth_event_ids WHERE event_id = '" . $event_id . "'" );
if ( !$chk || mysqli_num_rows($chk) === 0 ) {
    echo json_encode(['success' => false, 'error' => 'Onbekende fotokast']);
    exit;
}

$clean         = ms_network_sanitize( $payload );
$topology_json = json_encode( $clean );
if ( $topology_json === false ) {
    echo json_encode(['success' => false, 'error' => 'Tekening kon niet worden verwerkt']);
    exit;
}

$base_ip    = mysqli_real_escape_string( $cn, ms_txt( isset($payload['base_ip'])    ? $payload['base_ip']    : '', 64 ) );
$modem_type = mysqli_real_escape_string( $cn, ms_txt( isset($payload['modem_type']) ? $payload['modem_type'] : '', 128 ) );
$shelly_type= mysqli_real_escape_string( $cn, ms_txt( isset($payload['shelly_type']) ? $payload['shelly_type'] : '', 128 ) );
$note       = mysqli_real_escape_string( $cn, ms_txt( isset($payload['note'])       ? $payload['note']       : '', 8000 ) );
$json_esc   = mysqli_real_escape_string( $cn, $topology_json );
$client_id  = (int) $_SESSION['client_id'];
$now        = date('Y-m-d H:i:s');

$sql = "INSERT INTO photobooth_network_topology
           SET event_id             = '" . $event_id  . "',
               base_ip              = '" . $base_ip   . "',
               modem_type           = '" . $modem_type. "',
               shelly_type          = '" . $shelly_type. "',
               topology_note        = '" . $note      . "',
               topology_json        = '" . $json_esc  . "',
               updated_by_client_id = '" . $client_id . "',
               topology_updated     = '" . $now       . "'
        ON DUPLICATE KEY UPDATE
               base_ip              = VALUES(base_ip),
               modem_type           = VALUES(modem_type),
               shelly_type          = VALUES(shelly_type),
               topology_note        = VALUES(topology_note),
               topology_json        = VALUES(topology_json),
               updated_by_client_id = VALUES(updated_by_client_id),
               topology_updated     = VALUES(topology_updated)";

if ( !mysqli_query( $cn, $sql ) ) {
    echo json_encode(['success' => false, 'error' => 'Databasefout: ' . mysqli_error($cn)]);
    exit;
}

$who = '';
$r = mysqli_query( $cn, "SELECT client_name FROM photobooth_stat_client WHERE client_id = '" . $client_id . "'" );
if ( $r && ( $row = $r->fetch_object() ) ) { $who = ' door ' . $row->client_name; }

echo json_encode([
    'success' => true,
    'updated' => 'Laatst gewijzigd: ' . date('d-m-Y H:i', strtotime($now)) . $who,
    'nodes'   => count($clean['nodes']),
    'edges'   => count($clean['edges']),
]);
