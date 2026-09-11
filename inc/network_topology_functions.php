<?php
# ---------------------------------------------------------------------------
#  Gedeelde validatie voor de netwerk topologie (page_network.php).
#  Gebruikt door ajax_network_save.php (opslaan bij een fotokast) en
#  ajax_network_template.php (opslaan als voorbeeld opstelling).
#
#  LET OP: de lijst met types/verbindingssoorten en de breedtegrenzen staan ook in
#  js/network_topology.js (NODE_TYPES / TYPE_ORDER / EDGE_KINDS / NODE_W*). Voeg je daar
#  iets toe, pas het hier dan ook aan — anders wordt het bij het opslaan stilzwijgend
#  omgezet naar 'other' respectievelijk 'utp'.
# ---------------------------------------------------------------------------

const MS_MAX_NODES  = 200;
const MS_MAX_EDGES  = 400;
const MS_CANVAS_W   = 1500;
const MS_CANVAS_H   = 860;
const MS_NODE_H     = 66;
const MS_NODE_W_DEF = 168;
const MS_NODE_W_MIN = 110;
const MS_NODE_W_MAX = 460;

function ms_network_valid_types() {
    return array('router4g4p','router4g2p','router4p','modem4g','wifimodule','switch','ap',
                 'internet','venuelan','venuewifi',
                 'pc','printer','payter','camera','screen','hdmisplit','ledlight','outlet','relay',
                 'shelly','shellydim','shellydim2','shellyh','other');
}

function ms_network_valid_kinds() {
    return array('utp','wifi','mobile','usb','power','hdmi','dp');
}

function ms_txt( $v, $max ) {
    $v = is_scalar($v) ? (string)$v : '';
    $v = str_replace(array("\r\n", "\r"), "\n", $v);
    if ( function_exists('mb_substr') ) { return mb_substr( $v, 0, $max ); }
    return substr( $v, 0, $max );
}

function ms_num( $v, $min, $max ) {
    $v = is_numeric($v) ? (float)$v : 0;
    return (int) round( max( $min, min( $max, $v ) ) );
}

function ms_id( $v ) {
    $v = is_scalar($v) ? (string)$v : '';
    return preg_match('/^[A-Za-z0-9_-]{1,40}$/', $v) ? $v : '';
}

# Bouwt de tekening opnieuw op uit ruwe invoer: onbekende types/soorten vallen terug op
# een veilige standaard, dubbele en lege id's verdwijnen, verbindingen naar niet-bestaande
# elementen worden weggegooid en posities blijven binnen het canvas.
# Geeft array('nodes' => ..., 'edges' => ...) terug.
function ms_network_sanitize( $payload ) {
    $valid_types = ms_network_valid_types();
    $valid_kinds = ms_network_valid_kinds();

    $nodes = array();
    $ids   = array();
    $raw_nodes = ( isset($payload['nodes']) && is_array($payload['nodes']) ) ? $payload['nodes'] : array();
    foreach ( $raw_nodes as $n ) {
        if ( !is_array($n) ) { continue; }
        if ( count($nodes) >= MS_MAX_NODES ) { break; }
        $id = ms_id( isset($n['id']) ? $n['id'] : '' );
        if ( $id === '' || isset($ids[$id]) ) { continue; }   // leeg of dubbel id overslaan
        $type = ( isset($n['type']) && in_array($n['type'], $valid_types, true) ) ? $n['type'] : 'other';
        # 'w' ontbreekt bij tekeningen van voor de breedte-greep: dan de standaardbreedte
        $w = ( isset($n['w']) && is_numeric($n['w']) )
             ? ms_num( $n['w'], MS_NODE_W_MIN, MS_NODE_W_MAX )
             : MS_NODE_W_DEF;
        $ids[$id] = true;
        $nodes[] = array(
            'id'    => $id,
            'type'  => $type,
            'label' => ms_txt( isset($n['label']) ? $n['label'] : '', 80 ),
            'ip'    => ms_txt( isset($n['ip'])    ? $n['ip']    : '', 64 ),
            'note'  => ms_txt( isset($n['note'])  ? $n['note']  : '', 2000 ),
            'w'     => $w,
            'x'     => ms_num( isset($n['x']) ? $n['x'] : 0, 0, MS_CANVAS_W - $w ),
            'y'     => ms_num( isset($n['y']) ? $n['y'] : 0, 0, MS_CANVAS_H - MS_NODE_H ),
        );
    }

    $edges     = array();
    $edge_ids  = array();
    $raw_edges = ( isset($payload['edges']) && is_array($payload['edges']) ) ? $payload['edges'] : array();
    foreach ( $raw_edges as $e ) {
        if ( !is_array($e) ) { continue; }
        if ( count($edges) >= MS_MAX_EDGES ) { break; }
        $id   = ms_id( isset($e['id'])   ? $e['id']   : '' );
        $from = ms_id( isset($e['from']) ? $e['from'] : '' );
        $to   = ms_id( isset($e['to'])   ? $e['to']   : '' );
        // alleen verbindingen tussen twee bestaande, verschillende elementen
        if ( $id === '' || isset($edge_ids[$id]) ) { continue; }
        if ( !isset($ids[$from]) || !isset($ids[$to]) || $from === $to ) { continue; }
        $edge_ids[$id] = true;
        $edges[] = array(
            'id'    => $id,
            'from'  => $from,
            'to'    => $to,
            'kind'  => ( isset($e['kind']) && in_array($e['kind'], $valid_kinds, true) ) ? $e['kind'] : 'utp',
            'label' => ms_txt( isset($e['label']) ? $e['label'] : '', 60 ),
            'note'  => ms_txt( isset($e['note'])  ? $e['note']  : '', 2000 ),
        );
    }

    return array( 'nodes' => $nodes, 'edges' => $edges );
}
