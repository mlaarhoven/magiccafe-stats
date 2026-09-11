<?php
# ---------------------------------------------------------------------------
#  page_network.php — netwerkconfiguratie / topologie per fotokast
#  Admin only. Tekening + basis IP + modem type + notities worden opgeslagen
#  in photobooth_network_topology (1 rij per event_id).
#  Opslaan gaat via ajax_network_save.php.
# ---------------------------------------------------------------------------

# rolcheck vóór partial_header, zodat header() nog kan redirecten
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if ( !isset($_SESSION['client_id']) || $_SESSION['client_role'] != 'admin' ) {
    header('Location: https://stats.magicshoot.nl/index.php');
    exit;
}

include ("partial_header.php");
include ("partial_nav.php");
include ("inc/invul_functions.php");

# ---- lijst met fotokasten voor de keuzelijst ----
# Alleen kasten met dashboard_show = 1; gearchiveerde/verborgen kasten horen hier niet in.
$events = array();
$sql = "SELECT e.event_id,
               e.name,
               ( SELECT COUNT(*) FROM photobooth_network_topology t WHERE t.event_id = e.event_id ) AS heeft_config
          FROM photobooth_event_ids e
         WHERE e.dashboard_show = 1
      ORDER BY e.name ASC";
$result = mysqli_query( $cn, $sql );
if ( $result ) { while ( $row = $result->fetch_object() ) { $events[] = $row; } }

# ---- welke fotokast tonen we? (los van de sidebar-selectie) ----
# De lijst hierboven is meteen de toegangscontrole: staat de gevraagde kast er niet in
# (verborgen, of onzin in de URL), dan valt hij terug op de eerste uit de lijst. Zo kan de
# keuzelijst nooit iets anders aanwijzen dan wat de pagina daadwerkelijk laat zien.
$event_id = (int) $_SESSION['event_id'];
if ( isset($_REQUEST['event_id']) && (int)$_REQUEST['event_id'] > 0 ) {
    $event_id = (int) $_REQUEST['event_id'];
}

# naam uit photobooth_event_ids (get_event_name() leest de event tabel, waar niet elke
# event_id in voorkomt en dan een PHP warning geeft)
$event_naam = '';
foreach ( $events as $ev ) { if ( (int)$ev->event_id == $event_id ) { $event_naam = $ev->name; } }
if ( $event_naam === '' ) {
    if ( count($events) > 0 ) {
        $event_id   = (int) $events[0]->event_id;
        $event_naam = $events[0]->name;
    } else {
        $event_id   = 0;
        $event_naam = 'Geen fotokast beschikbaar';
    }
}

# ---- opgeslagen configuratie ophalen ----
$base_ip = ''; $modem_type = ''; $shelly_type = ''; $topology_note = ''; $topology_json = ''; $updated_txt = '';
$sql = "SELECT t.base_ip, t.modem_type, t.shelly_type, t.topology_note, t.topology_json, t.topology_updated,
               c.client_name
          FROM photobooth_network_topology t
     LEFT JOIN photobooth_stat_client c ON c.client_id = t.updated_by_client_id
         WHERE t.event_id = '" . $event_id . "'";
$result = mysqli_query( $cn, $sql );
if ( $result && ( $row = $result->fetch_object() ) ) {
    $base_ip       = $row->base_ip;
    $modem_type    = $row->modem_type;
    $shelly_type   = $row->shelly_type;
    $topology_note = (string) $row->topology_note;
    $topology_json = (string) $row->topology_json;
    if ( $row->topology_updated ) {
        $updated_txt = 'Laatst gewijzigd: ' . date( 'd-m-Y H:i', strtotime($row->topology_updated) );
        if ( $row->client_name ) { $updated_txt .= ' door ' . $row->client_name; }
    }
}

# nodes/edges als veilige JS-literal
$topology = json_decode( $topology_json, true );
if ( !is_array($topology) ) { $topology = array(); }
if ( !isset($topology['nodes']) || !is_array($topology['nodes']) ) { $topology['nodes'] = array(); }
if ( !isset($topology['edges']) || !is_array($topology['edges']) ) { $topology['edges'] = array(); }
$topology_js = json_encode( array( 'nodes' => $topology['nodes'], 'edges' => $topology['edges'] ),
                            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

# ---- eigen voorbeeld opstellingen (templates) ----
$templates = array();
$sql = "SELECT t.template_id, t.template_name, t.created_from_event_id, t.template_created,
               e.name AS bron_naam, c.client_name
          FROM photobooth_network_template t
     LEFT JOIN photobooth_event_ids  e ON e.event_id  = t.created_from_event_id
     LEFT JOIN photobooth_stat_client c ON c.client_id = t.created_by_client_id
      ORDER BY t.template_name ASC";
$result = mysqli_query( $cn, $sql );
if ( $result ) { while ( $row = $result->fetch_object() ) { $templates[] = $row; } }

# ---- modem/SIM gegevens uit de event tabel als extra context ----
$modem_serial = ''; $modem_phonenumber = '';
$result = mysqli_query( $cn, "SELECT modem_serial, modem_phonenumber FROM event WHERE event_id = '" . $event_id . "'" );
if ( $result && ( $row = $result->fetch_object() ) ) {
    $modem_serial      = $row->modem_serial;
    $modem_phonenumber = $row->modem_phonenumber;
}
?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo htmlspecialchars( $event_naam ); ?></strong></li>
        <li class="breadcrumb-item active">Netwerk</li>
      </ol>
    </div>

    <style>
      /* ---------- netwerk topologie ---------- */
      #msCanvasWrap { border:1px solid #d8dbe0; border-radius:6px; background:#fbfcfd; overflow:auto; max-height:74vh; }
      #msCanvas { display:block; background-image:linear-gradient(#eef1f5 1px, transparent 1px),
                                                  linear-gradient(90deg, #eef1f5 1px, transparent 1px);
                  background-size:20px 20px; }
      #msCanvas.is-connecting { cursor:crosshair; }

      .ms-node-box   { fill:#fff; stroke:#9aa5b1; stroke-width:1.4; }
      .ms-node-icon  { color:#321fdb; fill:none; stroke:currentColor; stroke-width:1.6;
                       stroke-linecap:round; stroke-linejoin:round; pointer-events:none; }
      .ms-node-label { font:600 13px/1 -apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; fill:#2a2f36; pointer-events:none; }
      .ms-node-ip    { font:400 12px/1 ui-monospace,SFMono-Regular,Consolas,monospace; fill:#5c6873; pointer-events:none; }
      .ms-node-notemark     { fill:#f9b115; }
      .ms-node-notemark-txt { font:700 9px/1 sans-serif; fill:#fff; text-anchor:middle; pointer-events:none; }
      .ms-node .ms-node-box { transition:stroke .12s, fill .12s; }
      .ms-node-resize { display:none; fill:#321fdb; stroke:#fff; stroke-width:1.5; cursor:ew-resize; }
      body.ms-editing .ms-node-resize { display:block; opacity:.45; }
      body.ms-editing .ms-node:hover .ms-node-resize,
      body.ms-editing .ms-node.is-selected .ms-node-resize { opacity:1; }
      .ms-node-resize:hover { fill:#1b0fb3; }
      body.ms-editing .ms-node { cursor:move; }
      body.ms-editing .ms-node:hover .ms-node-box { stroke:#321fdb; }
      .ms-node.is-selected    .ms-node-box { stroke:#321fdb; stroke-width:2.6; fill:#f4f3ff; }
      .ms-node.is-connect-src .ms-node-box { stroke:#2eb85c; stroke-width:2.6; stroke-dasharray:5 3; }

      .ms-marquee { fill:rgba(50,31,219,.07); stroke:#321fdb; stroke-width:1; stroke-dasharray:4 3; pointer-events:none; }
      body.ms-editing #msCanvas { -webkit-user-select:none; -moz-user-select:none; user-select:none; }

      .ms-edge-hit { stroke:transparent; stroke-width:16; fill:none; pointer-events:stroke; }
      body.ms-editing .ms-edge { cursor:pointer; }
      .ms-edge-label    { font:600 11px/1 -apple-system,Segoe UI,Roboto,Arial,sans-serif; fill:#3c4b64; text-anchor:middle; pointer-events:none; }
      .ms-edge-label-bg { fill:#fff; stroke:#d8dbe0; stroke-width:1; pointer-events:none; }

      #msPaletteCol { width:210px; flex:0 0 210px; }
      #msPropsCol   { width:250px; flex:0 0 250px; }
      .ms-pal-group { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#8a93a2;
                      margin:10px 0 4px; font-weight:700; }
      .ms-pal-item  { display:flex; align-items:center; gap:8px; padding:5px 7px; margin-bottom:3px;
                      border:1px solid #e4e7ea; border-radius:5px; background:#fff; cursor:grab; font-size:.82rem; }
      .ms-pal-item:hover { border-color:#321fdb; background:#f4f3ff; }
      .ms-pal-item span  { line-height:1.15; }
      .ms-pal-item svg, .ms-inline-icon { color:#321fdb; fill:none; stroke:currentColor; stroke-width:1.6;
                                          stroke-linecap:round; stroke-linejoin:round; flex:0 0 auto; }
      .ms-legend-item { display:inline-flex; align-items:center; gap:5px; margin-right:16px; font-size:.8rem; color:#5c6873; }
      .ms-kind-chip   { display:inline-block; width:10px; height:10px; border-radius:2px; vertical-align:middle; }
      .ms-note-view   { white-space:pre-wrap; }

      /* lees-tabellen: naam-kolom ruim genoeg voor "Netwerk locatie (UTP)" e.d.,
         de notitie-kolom vangt de rest op. table-responsive laat smalle schermen scrollen
         in plaats van de naam samen te persen. */
      .ms-table-nodes { min-width:580px; }
      .ms-table-nodes th:nth-child(1), .ms-table-nodes td:nth-child(1) { width:30%; min-width:200px; }
      .ms-table-nodes th:nth-child(2), .ms-table-nodes td:nth-child(2) { width:19%; min-width:130px; }
      .ms-table-nodes th:nth-child(3), .ms-table-nodes td:nth-child(3) { width:16%; min-width:120px; white-space:nowrap; }
      .ms-table-nodes th:nth-child(4), .ms-table-nodes td:nth-child(4) { width:35%; }
      .ms-table-edges { min-width:520px; }
      .ms-table-edges th:nth-child(1), .ms-table-edges td:nth-child(1),
      .ms-table-edges th:nth-child(2), .ms-table-edges td:nth-child(2) { width:23%; min-width:120px; }
      .ms-table-edges th:nth-child(3), .ms-table-edges td:nth-child(3) { width:16%; min-width:100px; white-space:nowrap; }
      #msPalette, #msProps { max-height:74vh; overflow-y:auto; }
    </style>
  </header>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">

          <div id="msMsg" style="display:none"></div>

          <!-- ============ kop: fotokast kiezen + knoppen ============ -->
          <div class="card">
            <div class="card-header bg-facebook d-flex align-items-center flex-wrap">
              <h4 class="text-white mb-0 mr-auto">Netwerk configuratie</h4>
              <form method="get" action="page_network.php" class="form-inline mr-3 my-1">
                <label class="text-white mr-2 mb-0" for="msEventPick">Fotokast:</label>
                <select class="form-control form-control-sm" id="msEventPick" name="event_id"
                        onchange="this.form.submit()">
                  <?php foreach ( $events as $ev ) { ?>
                  <option value="<?php echo (int)$ev->event_id; ?>"<?php echo ( (int)$ev->event_id == $event_id ? ' selected' : '' ); ?>>
                    <?php echo htmlspecialchars($ev->name) . ' (' . (int)$ev->event_id . ')' . ( $ev->heeft_config ? ' ✔' : '' ); ?>
                  </option>
                  <?php } ?>
                </select>
                <noscript><button type="submit" class="btn btn-sm btn-light ml-2">Toon</button></noscript>
              </form>
              <div class="my-1">
                <button type="button" class="btn btn-sm btn-light" id="msEditBtn">Bewerken</button>
                <button type="button" class="btn btn-sm btn-success" id="msSaveBtn" style="display:none">Opslaan</button>
                <button type="button" class="btn btn-sm btn-light" id="msCancelBtn" style="display:none">Annuleren</button>
              </div>
            </div>

            <div class="card-body">
              <span id="msDirty" class="badge badge-warning mb-2" style="display:none">Niet-opgeslagen wijzigingen</span>

              <div class="row">
                <div class="col-md-3">
                  <label class="small text-muted mb-1">Basis IP adres (modem / router)</label>
                  <p class="ms-meta-view mb-3"><strong id="msBaseIpView"><?php echo $base_ip !== '' ? htmlspecialchars($base_ip) : '—'; ?></strong></p>
                  <input type="text" class="form-control form-control-sm mb-3 ms-meta-edit" id="msBaseIp"
                         style="display:none" placeholder="bv. 192.168.1.1"
                         value="<?php echo htmlspecialchars($base_ip); ?>">
                </div>
                <div class="col-md-3">
                  <label class="small text-muted mb-1">Modem type</label>
                  <p class="ms-meta-view mb-3"><strong id="msModemTypeView"><?php echo $modem_type !== '' ? htmlspecialchars($modem_type) : '—'; ?></strong></p>
                  <input type="text" class="form-control form-control-sm mb-3 ms-meta-edit" id="msModemType"
                         style="display:none" placeholder="bv. Teltonika RUT241"
                         value="<?php echo htmlspecialchars($modem_type); ?>">
                </div>
                <div class="col-md-3">
                  <label class="small text-muted mb-1">Shelly type (schakelaar)</label>
                  <p class="ms-meta-view mb-3"><strong id="msShellyTypeView"><?php echo $shelly_type !== '' ? htmlspecialchars($shelly_type) : '—'; ?></strong></p>
                  <input type="text" class="form-control form-control-sm mb-3 ms-meta-edit" id="msShellyType"
                         style="display:none" placeholder="bv. Shelly Pro 1PM (SPSW-201PE16EU)"
                         value="<?php echo htmlspecialchars($shelly_type); ?>">
                </div>
                <div class="col-md-3">
                  <label class="small text-muted mb-1">Modem gegevens uit de event tabel</label>
                  <p class="mb-3 small">
                    Serienummer: <strong><?php echo htmlspecialchars($modem_serial); ?></strong><br>
                    SIM nummer: <strong><?php echo htmlspecialchars($modem_phonenumber); ?></strong><br>
                    <a href="page_modem_overview.php">Modem en SIM pagina &rsaquo;</a>
                  </p>
                </div>
              </div>

              <label class="small text-muted mb-1">Notitie bij de hele configuratie</label>
              <p class="ms-meta-view ms-note-view mb-0" id="msNoteView"><?php echo $topology_note !== '' ? htmlspecialchars($topology_note) : '—'; ?></p>
              <textarea class="form-control form-control-sm ms-meta-edit" id="msNote" rows="3" style="display:none"
                        placeholder="Bijzonderheden over deze netwerkopstelling…"><?php echo htmlspecialchars($topology_note); ?></textarea>

              <p class="small text-muted mt-2 mb-0" id="msUpdatedView"><?php echo htmlspecialchars($updated_txt); ?></p>
            </div>
          </div>

          <!-- ============ tekening ============ -->
          <div class="card">
            <div class="card-header d-flex align-items-center flex-wrap">
              <strong class="mr-auto">Topologie</strong>

              <div id="msEditTools" class="form-inline" style="display:none">
                <select class="form-control form-control-sm mr-2" id="msTemplate" title="Begin vanaf een bestaande opstelling">
                  <option value="">Voorbeeld opstelling…</option>
                  <optgroup label="Eigen voorbeelden" id="msTplGroup">
                    <?php foreach ( $templates as $t ) { ?>
                    <option value="db:<?php echo (int)$t->template_id; ?>"><?php echo htmlspecialchars($t->template_name); ?></option>
                    <?php } ?>
                  </optgroup>
                  <optgroup label="Standaard opstellingen">
                    <option value="t1">1 &mdash; 4-poorts 4G router (alleen 4G)</option>
                    <option value="t2">2 &mdash; 4-poorts 4G router + WAN naar locatienetwerk</option>
                    <option value="t3">3 &mdash; 4-poorts router + los 4G modem op WAN</option>
                    <option value="t4">4 &mdash; 2-poorts 4G router + switch in de kast</option>
                    <option value="t5">5 &mdash; router + WiFi module op WAN naar locatie-WiFi</option>
                  </optgroup>
                </select>
                <button type="button" class="btn btn-sm btn-outline-secondary mr-2" id="msTplSaveBtn"
                        title="Bewaar de huidige tekening als voorbeeld, om te hergebruiken bij een andere fotokast">Bewaar als voorbeeld</button>
                <button type="button" class="btn btn-sm btn-outline-secondary mr-3" id="msTplManageBtn"
                        title="Eigen voorbeelden verwijderen">&hellip;</button>
                <button type="button" class="btn btn-sm btn-outline-primary mr-2" id="msConnectBtn"
                        title="Selecteer een element en klik hier (of dubbelklik het element), klik daarna het tweede element">Verbinden</button>
                <label class="mr-3 mb-0 small"><input type="checkbox" id="msSnap" checked> uitlijnen op raster</label>
              </div>

              <div class="btn-group btn-group-sm ml-2">
                <button type="button" class="btn btn-outline-secondary" id="msZoomOut">&minus;</button>
                <button type="button" class="btn btn-outline-secondary" id="msZoomReset"><span id="msZoomLabel">100%</span></button>
                <button type="button" class="btn btn-outline-secondary" id="msZoomIn">+</button>
              </div>
            </div>

            <div class="card-body">
              <div id="msConnectHint" class="alert alert-info py-2 px-3" style="display:none">
                Klik nu op het element waar de verbinding naartoe gaat. (Esc = annuleren)
              </div>

              <div class="d-flex" style="gap:14px">
                <div id="msPaletteCol" style="display:none">
                  <div class="small text-muted">Sleep een element naar de tekening:</div>
                  <div id="msPalette"></div>
                </div>

                <div class="flex-fill" style="min-width:0">
                  <div id="msCanvasWrap">
                    <svg id="msCanvas" xmlns="http://www.w3.org/2000/svg">
                      <g id="msEdgeLayer"></g>
                      <g id="msNodeLayer"></g>
                      <g id="msOverlayLayer"></g>
                    </svg>
                  </div>
                  <div id="msLegend" class="mt-2"></div>
                </div>

                <div id="msPropsCol" style="display:none">
                  <div class="small text-muted mb-2">Eigenschappen</div>
                  <div id="msProps"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- ============ lees-overzicht ============ -->
          <div class="row">
            <div class="col-lg-6">
              <div class="card">
                <div class="card-header"><strong>Elementen</strong></div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 ms-table-nodes">
                      <thead class="thead-light">
                        <tr><th>Naam</th><th>Type</th><th>IP adres</th><th>Notitie</th></tr>
                      </thead>
                      <tbody id="msNodeTable"></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card">
                <div class="card-header"><strong>Verbindingen</strong></div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 ms-table-edges">
                      <thead class="thead-light">
                        <tr><th>Van</th><th>Naar</th><th>Soort</th><th>Label</th><th>Notitie</th></tr>
                      </thead>
                      <tbody id="msEdgeTable"></tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <input type="hidden" id="msEventId" value="<?php echo $event_id; ?>">
          <input type="hidden" id="msEventName" value="<?php echo htmlspecialchars($event_naam); ?>">

          <!-- ============ eigen voorbeelden beheren ============ -->
          <div class="modal fade" id="msTplModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Eigen voorbeeld opstellingen</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Sluiten"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                  <p class="text-muted small">Een voorbeeld is een kopie van de tekening zoals die op het moment
                    van bewaren was. Later wijzigen van de fotokast verandert het voorbeeld niet, en andersom ook niet.</p>
                  <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                      <thead class="thead-light">
                        <tr><th>Naam</th><th>Gemaakt vanaf</th><th>Bewaard</th><th></th></tr>
                      </thead>
                      <tbody id="msTplList">
                        <?php if ( count($templates) === 0 ) { ?>
                        <tr><td colspan="4" class="text-muted">Nog geen eigen voorbeelden bewaard.</td></tr>
                        <?php } foreach ( $templates as $t ) { ?>
                        <tr data-tpl-id="<?php echo (int)$t->template_id; ?>">
                          <td><?php echo htmlspecialchars($t->template_name); ?></td>
                          <td><?php echo htmlspecialchars( $t->bron_naam !== null ? $t->bron_naam : '—' ); ?></td>
                          <td><?php
                                echo $t->template_created ? date('d-m-Y H:i', strtotime($t->template_created)) : '';
                                if ( $t->client_name ) { echo ' &middot; ' . htmlspecialchars($t->client_name); }
                              ?></td>
                          <td class="text-right">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-tpl-del="<?php echo (int)$t->template_id; ?>">Verwijderen</button>
                          </td>
                        </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Sluiten</button>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </main>
    <footer class="c-footer">
      <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> &copy; <?php echo date('Y'); ?> MagicShoot.</div>
      <div class="ml-auto">Powered by&nbsp;<a href="https://www.MagicShoot.nl/">MagicShoot</a></div>
    </footer>
  </div>
</div>

<!-- ?v=filemtime: de server stuurt voor .js een Cache-Control van een jaar, dus zonder deze
     parameter blijft elke browser de oude versie draaien na een wijziging. Dat is een keer
     misgegaan (11-09-2026): de HTML kwam vers uit PHP en toonde het nieuwe veld, maar de
     gecachete JS stuurde het niet mee, waarna de opslag het veld leegschreef. -->
<script src="js/network_topology.js?v=<?php echo filemtime(__DIR__ . '/js/network_topology.js'); ?>"></script>
<script>
  MSNet.init(<?php echo $topology_js; ?>);
</script>

<?php include("partial_footer.php"); ?>
