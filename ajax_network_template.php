<?php
# ---------------------------------------------------------------------------
#  ajax_network_template.php — eigen 'voorbeeld opstellingen' (page_network.php)
#
#  POST/GET 'action':
#    save   — huidige tekening bewaren als voorbeeld (POST 'name' + 'payload')
#    get    — één voorbeeld ophalen om op het canvas te zetten ('template_id')
#    delete — voorbeeld verwijderen ('template_id')
#
#  Antwoord altijd JSON. Validatie van nodes/edges via inc/network_topology_functions.php,
#  dezelfde als bij het opslaan bij een fotokast.
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

$action    = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$client_id = (int) $_SESSION['client_id'];

# ---------------------------------------------------------------- opslaan
if ( $action === 'save' ) {

    $name = trim( ms_txt( isset($_POST['name']) ? $_POST['name'] : '', 120 ) );
    if ( $name === '' ) {
        echo json_encode(['success' => false, 'error' => 'Geef het voorbeeld een naam']);
        exit;
    }

    $payload = json_decode( isset($_POST['payload']) ? $_POST['payload'] : '', true );
    if ( !is_array($payload) ) {
        echo json_encode(['success' => false, 'error' => 'Ongeldige gegevens']);
        exit;
    }

    $clean = ms_network_sanitize( $payload );
    if ( count($clean['nodes']) === 0 ) {
        echo json_encode(['success' => false, 'error' => 'De tekening is leeg — er valt niets te bewaren']);
        exit;
    }

    $template_json = json_encode( $clean );
    if ( $template_json === false ) {
        echo json_encode(['success' => false, 'error' => 'Tekening kon niet worden verwerkt']);
        exit;
    }

    # bestaat er al een voorbeeld met deze naam? dan overschrijven (na bevestiging in de UI)
    $overwrite  = ( isset($_POST['overwrite']) && $_POST['overwrite'] === '1' );
    $name_esc   = mysqli_real_escape_string( $cn, $name );
    $existing   = 0;
    $r = mysqli_query( $cn, "SELECT template_id FROM photobooth_network_template
                              WHERE template_name = '" . $name_esc . "'" );
    if ( $r && ( $row = $r->fetch_object() ) ) { $existing = (int) $row->template_id; }
    if ( $existing && !$overwrite ) {
        echo json_encode(['success' => false, 'exists' => true,
                          'error' => 'Er bestaat al een voorbeeld met deze naam.']);
        exit;
    }

    $base_ip    = mysqli_real_escape_string( $cn, ms_txt( isset($payload['base_ip'])    ? $payload['base_ip']    : '', 64 ) );
    $modem_type = mysqli_real_escape_string( $cn, ms_txt( isset($payload['modem_type']) ? $payload['modem_type'] : '', 128 ) );
    $note       = mysqli_real_escape_string( $cn, ms_txt( isset($payload['note'])       ? $payload['note']       : '', 8000 ) );
    $json_esc   = mysqli_real_escape_string( $cn, $template_json );
    $event_id   = (int) ( isset($payload['event_id']) ? $payload['event_id'] : 0 );
    $now        = date('Y-m-d H:i:s');

    if ( $existing ) {
        $sql = "UPDATE photobooth_network_template
                   SET base_ip               = '" . $base_ip    . "',
                       modem_type            = '" . $modem_type . "',
                       template_note         = '" . $note       . "',
                       template_json         = '" . $json_esc   . "',
                       created_from_event_id = '" . $event_id   . "',
                       created_by_client_id  = '" . $client_id  . "',
                       template_created      = '" . $now        . "'
                 WHERE template_id = '" . $existing . "'";
    } else {
        $sql = "INSERT INTO photobooth_network_template
                   SET template_name         = '" . $name_esc   . "',
                       base_ip               = '" . $base_ip    . "',
                       modem_type            = '" . $modem_type . "',
                       template_note         = '" . $note       . "',
                       template_json         = '" . $json_esc   . "',
                       created_from_event_id = '" . $event_id   . "',
                       created_by_client_id  = '" . $client_id  . "',
                       template_created      = '" . $now        . "'";
    }

    if ( !mysqli_query( $cn, $sql ) ) {
        echo json_encode(['success' => false, 'error' => 'Databasefout: ' . mysqli_error($cn)]);
        exit;
    }

    echo json_encode([
        'success'     => true,
        'template_id' => $existing ? $existing : (int) mysqli_insert_id($cn),
        'name'        => $name,
        'replaced'    => $existing ? true : false,
        'nodes'       => count($clean['nodes']),
        'edges'       => count($clean['edges']),
    ]);
    exit;
}

# ---------------------------------------------------------------- ophalen
if ( $action === 'get' ) {

    $template_id = (int) ( isset($_REQUEST['template_id']) ? $_REQUEST['template_id'] : 0 );
    $r = mysqli_query( $cn, "SELECT template_name, base_ip, modem_type, template_note, template_json
                               FROM photobooth_network_template
                              WHERE template_id = '" . $template_id . "'" );
    if ( !$r || !( $row = $r->fetch_object() ) ) {
        echo json_encode(['success' => false, 'error' => 'Voorbeeld niet gevonden']);
        exit;
    }

    # ook bij het teruglezen opnieuw valideren: de tekening kan van voor een
    # wijziging in de types komen
    $stored = json_decode( (string) $row->template_json, true );
    $clean  = ms_network_sanitize( is_array($stored) ? $stored : array() );

    echo json_encode([
        'success'    => true,
        'name'       => $row->template_name,
        'base_ip'    => $row->base_ip,
        'modem_type' => $row->modem_type,
        'note'       => (string) $row->template_note,
        'nodes'      => $clean['nodes'],
        'edges'      => $clean['edges'],
    ]);
    exit;
}

# ---------------------------------------------------------------- verwijderen
if ( $action === 'delete' ) {

    $template_id = (int) ( isset($_POST['template_id']) ? $_POST['template_id'] : 0 );
    if ( $template_id <= 0 ) {
        echo json_encode(['success' => false, 'error' => 'Ongeldig voorbeeld']);
        exit;
    }
    if ( !mysqli_query( $cn, "DELETE FROM photobooth_network_template
                               WHERE template_id = '" . $template_id . "'" ) ) {
        echo json_encode(['success' => false, 'error' => 'Databasefout: ' . mysqli_error($cn)]);
        exit;
    }
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Onbekende actie']);
