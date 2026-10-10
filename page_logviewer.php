<?php
# ---------------------------------------------------------------------------
#  page_logviewer.php — logregels bekijken, nieuwste bovenaan. Admin only.
#
#  Leest rechtstreeks uit de `log` tabel. Filters: soort logregel (log_level,
#  meerdere tegelijk aan te vinken) en fotokast (keuzelijst). De pagina ververst
#  zichzelf elke 30 seconden; dat is uit te zetten met het vinkje in de balk.
#
#  Let op bij wijzigen:
#  - Sorteren gebeurt op `log_created` ZONDER tweede sorteerkolom. Zet je er
#    ", log_id DESC" achter, dan kan MariaDB de log_created index niet meer voor
#    de volgorde gebruiken en valt hij terug op een filesort over miljoenen
#    rijen (gemeten: 0,1s wordt ~2,5s). Niet doen.
#  - `log_created` komt van de fotokast zelf (api/post/logging.php neemt log_date
#    over zoals de kast hem aanlevert) en kan dus voor- of achterlopen op de
#    serverklok. Een regel die via cron_process_logfiles.php een dag later wordt
#    ingelezen krijgt zijn ORIGINELE tijd, en staat dus niet bovenaan maar op
#    zijn eigen plek in de tijdlijn.
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

global $cn;

# ---------------------------------------------------------------------------
#  Soorten logregels — dit is log_level uit de `log` tabel.
#  De kleur volgt de afspraak: error rood, waarschuwing oranje, de rest geel.
# ---------------------------------------------------------------------------
$log_soorten = array(
    30  => array( 'label' => 'Error',        'kleur' => 'error'   ),
    20  => array( 'label' => 'Waarschuwing', 'kleur' => 'warning' ),
    1   => array( 'label' => 'Heartbeat',    'kleur' => 'rest'    ),
    10  => array( 'label' => 'Melding',      'kleur' => 'rest'    ),
    100 => array( 'label' => 'Stat',         'kleur' => 'rest'    ),
    0   => array( 'label' => 'Onbekend',     'kleur' => 'rest'    ),
);

# ---------------------------------------------------------------------------
#  Fotokasten voor de keuzelijst
# ---------------------------------------------------------------------------
$fotokasten   = array();   # rijen voor de keuzelijst
$event_namen  = array();   # event_id => naam, voor de tabel
$sql = "SELECT event_id, name, dashboard_show
          FROM photobooth_event_ids
      ORDER BY dashboard_order_nr ASC, event_id ASC";
$result = mysqli_query( $cn, $sql );
if ( $result ) {
    while ( $row = $result->fetch_object() ) {
        $fotokasten[] = $row;
        $event_namen[ (int)$row->event_id ] = $row->name;
    }
}

# ---------------------------------------------------------------------------
#  Filters uitlezen
# ---------------------------------------------------------------------------

# Verborgen veld 'gefilterd': zonder dat kun je "eerste keer op de pagina" niet
# onderscheiden van "alle vinkjes uitgezet" — in beide gevallen stuurt de browser
# namelijk geen enkel soort[] mee.
$is_gefilterd = isset( $_GET['gefilterd'] );

$gekozen_soorten = array();
if ( isset( $_GET['soort'] ) && is_array( $_GET['soort'] ) ) {
    foreach ( $_GET['soort'] as $soort ) {
        if ( array_key_exists( (int)$soort, $log_soorten ) ) { $gekozen_soorten[] = (int)$soort; }
    }
}
if ( !$is_gefilterd ) {
    # Eerste keer op de pagina: alleen de errors. Daar kom je in de praktijk voor;
    # de rest (meldingen, stats en vooral de heartbeats - elke kast stuurt er elke
    # minuut een) verdringt anders meteen alles wat de moeite waard is.
    # Wil je standaard meer zien, zet er dan de gewenste log_levels bij:
    # 30 error, 20 waarschuwing, 10 melding, 100 stat, 1 heartbeat, 0 onbekend.
    $gekozen_soorten = array( 30 );
}
$gekozen_soorten = array_unique( $gekozen_soorten );

# bekende ruis verbergen? (standaard aan)
# Zie logviewer_ruis_filter() hieronder voor wat daaronder valt.
$verberg_ruis = $is_gefilterd ? isset( $_GET['verberg_ruis'] ) : true;

# fotokast: 'alle' of een event_id
$event_keuze = isset( $_GET['event_id'] ) ? trim( $_GET['event_id'] ) : 'alle';
if ( $event_keuze !== 'alle' && !is_numeric( $event_keuze ) ) { $event_keuze = 'alle'; }

# regels per pagina
$per_pagina_opties = array( 25, 50, 100, 200 );
$per_pagina = isset( $_GET['per_pagina'] ) ? (int)$_GET['per_pagina'] : 25;
if ( !in_array( $per_pagina, $per_pagina_opties ) ) { $per_pagina = 25; }

$pagina = isset( $_GET['pagina'] ) ? (int)$_GET['pagina'] : 1;
if ( $pagina < 1 ) { $pagina = 1; }

# ---------------------------------------------------------------------------
#  WHERE opbouwen
# ---------------------------------------------------------------------------
$where       = array();
$geen_soort  = ( count( $gekozen_soorten ) == 0 );

# Staat alles aan, dan géén log_level filter meesturen: dat is sneller én een
# eventueel nieuw log_level (dat hier nog niet bekend is) blijft dan zichtbaar.
if ( !$geen_soort && count( $gekozen_soorten ) < count( $log_soorten ) ) {
    $where[] = "log_level IN (" . implode( ',', array_map( 'intval', $gekozen_soorten ) ) . ")";
}

# ---------------------------------------------------------------------------
#  Bekende ruis verbergen
#
#  Dit zijn meldingen die de fotokast terecht stuurt, maar die geen storing zijn:
#    - de betaalterminal is er nog niet als de kast net is opgestart (hij boot
#      langzamer dan de pc) - dat duurt een halve tot hele minuut;
#    - FLORA VIDEO (logt onder event_id 5) en FLORA FOTO (83) hebben geen sessies,
#      dus "geen sessie-bestand" hoort daar gewoon zo.
#
#  De twee soorten worden NIET op dezelfde manier herkend, en dat is met opzet:
#
#  1. Flora (en het uploadscript) - puur op handtekening, ongeacht log_level. Die
#     kasten hebben nu eenmaal geen sessies, dus die melding is daar altijd ruis, of
#     hij nu als error of als waarschuwing is weggeschreven. Werkt daardoor ook op
#     oude regels - en dat is hier het hele punt, want de historie wordt bewust niet
#     omgezet (besluit gebruiker 11-09-2026).
#     LET OP: dit filter verbergt die regels alleen in dit scherm. api/post/logging.php
#     is hiervoor met opzet NIET aangepast, dus ze blijven gewoon een mail sturen
#     (1x per 24 uur per unieke fout). Dat is een bewuste keuze: het scherm opruimen
#     mag, maar het alarm uitzetten is een andere beslissing.
#
#  2. Terminal-opstart - de handtekening is hier NIET genoeg: exact dezelfde melding
#     is een échte storing zodra de terminal midden op de avond wegvalt. Er zijn twee
#     manieren waarop een regel als opstart herkend wordt, en allebei zijn nodig:
#
#       a. `log_level = 20` - api/post/logging.php heeft bij het opslaan al gemeten of
#          de melding binnen 120 seconden na de Start-regel van die kast viel, en hem
#          dan als waarschuwing weggeschreven. Geldt voor alles vanaf 11-09-2026 10:53.
#
#       b. `log_id_apps <= 40` - voor alles daarvóór (en als vangnet). log_id_apps is
#          de logteller van de fotokast zelf; die begint bij elke (her)start weer bij 1.
#          Een opstartfout zit dus altijd in de eerste tientallen regels van een run.
#
#     Waarom b. geen noodgreep is: gemeten over 60 dagen verbergt b. 6176 regels en
#     a. 6163 - vrijwel dezelfde verzameling. Bij de 4 gevallen waar ze verschilden is
#     nagekeken wat er aan de hand was: twee keer bleef de storing gewoon zichtbaar
#     (60 resp. 84 andere regels van hetzelfde incident), en twee keer (511 op
#     05-09, 483 op 07-09) bleek het een opstartreeks te zijn waarvan de Start-regel
#     nooit in de database is beland - precies het geval waarin de tijdregel faalt en
#     log_id_apps het wél goed heeft. Grens 40: bij 60 zou hij 18 échte storingsregels
#     extra verbergen voor maar 17 ruisregels winst.
#
#  Daarom staat hier geen subquery op de Start-regels. Dat is geen detail maar de
#  hele reden dat dit filter bruikbaar is: mét die subquery duurt de COUNT(*) voor de
#  paginering ~40 seconden, zonder blijft hij onder de seconde. Zet er dus nooit
#  alsnog een EXISTS op de log tabel in.
#
#  Dit filter verbergt alleen in dit scherm. De mail-logica in api/post/logging.php
#  staat hier los van en blijft ongemoeid: een echte storing mailt hoe dan ook, ook
#  als hij hier onverhoopt verborgen zou worden. Dat is het vangnet dat b. verantwoord
#  maakt - een schermfilter mag benaderen, een alarm niet.
#
#  De handtekeningen hieronder moeten gelijk blijven aan die in api/post/logging.php.
# ---------------------------------------------------------------------------
function logviewer_ruis_filter() {
    return " NOT ( "
           # Flora en het uploadscript: altijd ruis, ongeacht level.
           # event_id 0 = het uploadscript, dat geen event_id meestuurt; die regels
           # vuren in dezelfde seconde als die van FLORA FOTO (83).
         . "      ( log_action = 'SessionIdFile' AND log_details = 'SessionIdFilename==\\\"\\\"' "
         . "        AND event_id IN (0,5,35,83) ) "
           # Shelly BAD_REQUEST: de powerplanner schakelt elke kast met eerst kanaal 1
           # en dan kanaal 0, en niet elke kast heeft een tweede kanaal in gebruik.
           # Sinds 11-09-2026 logt shelly_functions.php deze niet meer; dit verbergt de
           # ~955 regels die er al stonden. Andere shelly-fouten (DEVICE_OFFLINE,
           # DEVICE_FAILED_COMMAND, TOO_MANY_REQUESTS) blijven gewoon zichtbaar.
         . "   OR ( log_action = 'set_switch' "
         . "        AND log_details = '/stats/inc/shelly_functions.php error:BAD_REQUEST' ) "
           # betaalterminal aan het opstarten: level 20 (door logging.php gemeten)
           # of in de eerste 40 logregels van een run (zie uitleg hierboven)
         . "   OR ( ( ( log_action = 'HOME'     AND log_details = 'geen terminal' ) "
         . "       OR ( log_action = 'Terminal' AND log_details LIKE '%Online: False%' ) ) "
         . "        AND ( log_level = 20 OR log_id_apps <= 40 ) ) "
         . " ) ";
}

$event_ids_filter = array();
if ( $event_keuze !== 'alle' ) {
    $event_ids_filter[] = (int)$event_keuze;
    # FLORA VIDEO logt zijn eigen regels onder event_id 5 in plaats van 35 (bekende
    # fout in de fotokast-software, geen fout hier). Beide meenemen, anders blijft
    # de tabel leeg voor die kast.
    if ( (int)$event_keuze == 35 ) { $event_ids_filter[] = 5; }
    $where[] = "event_id IN (" . implode( ',', $event_ids_filter ) . ")";
}

if ( $verberg_ruis ) { $where[] = logviewer_ruis_filter(); }

$where_sql = count( $where ) ? " WHERE " . implode( " AND ", $where ) : "";

# ---------------------------------------------------------------------------
#  Tellen + ophalen
# ---------------------------------------------------------------------------
$totaal        = 0;
$aantal_paginas = 1;
$regels        = array();

if ( !$geen_soort ) {
    $sql    = "SELECT COUNT(*) AS aantal FROM log" . $where_sql;
    $result = mysqli_query( $cn, $sql );
    if ( $result && ( $row = $result->fetch_object() ) ) { $totaal = (int)$row->aantal; }

    $aantal_paginas = ( $totaal > 0 ) ? (int)ceil( $totaal / $per_pagina ) : 1;
    if ( $pagina > $aantal_paginas ) { $pagina = $aantal_paginas; }
    $offset = ( $pagina - 1 ) * $per_pagina;

    $sql = "SELECT log_id, log_id_apps, log_session_id, event_id, log_level,
                   log_created, log_source, log_action, log_ip, log_details
              FROM log" . $where_sql . "
          ORDER BY log_created DESC
             LIMIT " . (int)$per_pagina . " OFFSET " . (int)$offset;
    $result = mysqli_query( $cn, $sql );
    if ( $result ) {
        while ( $row = $result->fetch_object() ) { $regels[] = $row; }
    } else {
        echo '<div class="alert alert-danger">Fout bij ophalen logregels: ' . htmlspecialchars( mysqli_error( $cn ) ) . '</div>';
    }
}

$eerste_regel = ( $totaal > 0 ) ? ( ( $pagina - 1 ) * $per_pagina ) + 1 : 0;
$laatste_regel = min( $pagina * $per_pagina, $totaal );

# ---------------------------------------------------------------------------
#  Hulpjes
# ---------------------------------------------------------------------------

# basis-URL met alle filters behalve de pagina zelf
function logviewer_url( $pagina_nr ) {
    global $gekozen_soorten, $event_keuze, $per_pagina, $verberg_ruis;
    $params = array(
        'gefilterd'  => 1,
        'event_id'   => $event_keuze,
        'per_pagina' => $per_pagina,
        'pagina'     => (int)$pagina_nr,
    );
    if ( $verberg_ruis ) { $params['verberg_ruis'] = 1; }
    $qs = http_build_query( $params );
    foreach ( $gekozen_soorten as $soort ) { $qs .= '&soort%5B%5D=' . (int)$soort; }
    return 'page_logviewer.php?' . $qs;
}

# naam van een fotokast bij een event_id uit de logtabel
function logviewer_kastnaam( $event_id ) {
    global $event_namen;
    $event_id = (int)$event_id;

    # FLORA VIDEO schrijft zijn regels weg onder event_id 5
    if ( $event_id == 5 ) { return 'FLORA VIDEO (logt als 5)'; }

    if ( isset( $event_namen[ $event_id ] ) ) { return $event_namen[ $event_id ]; }

    # Negatieve event_id's zijn met opzet zo opgeslagen: de historie van een kast
    # die opnieuw in gebruik is genomen, omgeklapt naar het negatieve id.
    if ( $event_id < 0 && isset( $event_namen[ abs( $event_id ) ] ) ) {
        return $event_namen[ abs( $event_id ) ] . ' (afgesloten periode)';
    }
    if ( $event_id == 0 ) { return 'geen fotokast'; }
    return 'onbekend';
}

# paginering, wordt boven én onder de tabel getekend
function logviewer_paginatie( $pagina, $aantal_paginas ) {
    if ( $aantal_paginas < 1 ) { $aantal_paginas = 1; }

    $html  = '<ul class="pagination pagination-sm mb-0 flex-wrap">';

    $uit   = ( $pagina <= 1 ) ? ' disabled' : '';
    $html .= '<li class="page-item' . $uit . '"><a class="page-link" href="' . htmlspecialchars( logviewer_url( 1 ) ) . '">&laquo; Eerste</a></li>';
    $html .= '<li class="page-item' . $uit . '"><a class="page-link" href="' . htmlspecialchars( logviewer_url( $pagina - 1 ) ) . '">&lsaquo; Vorige</a></li>';

    # venster van 2 pagina's links en rechts
    $van = max( 1, $pagina - 2 );
    $tot = min( $aantal_paginas, $pagina + 2 );
    if ( $van > 1 ) { $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>'; }
    for ( $i = $van; $i <= $tot; $i++ ) {
        $actief = ( $i == $pagina ) ? ' active' : '';
        $html  .= '<li class="page-item' . $actief . '"><a class="page-link" href="' . htmlspecialchars( logviewer_url( $i ) ) . '">' . $i . '</a></li>';
    }
    if ( $tot < $aantal_paginas ) { $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>'; }

    $uit   = ( $pagina >= $aantal_paginas ) ? ' disabled' : '';
    $html .= '<li class="page-item' . $uit . '"><a class="page-link" href="' . htmlspecialchars( logviewer_url( $pagina + 1 ) ) . '">Volgende &rsaquo;</a></li>';
    $html .= '<li class="page-item' . $uit . '"><a class="page-link" href="' . htmlspecialchars( logviewer_url( $aantal_paginas ) ) . '">Laatste &raquo;</a></li>';

    $html .= '</ul>';
    return $html;
}

$kast_titel = ( $event_keuze === 'alle' ) ? 'Alle fotokasten' : logviewer_kastnaam( $event_keuze );
?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo htmlspecialchars( $kast_titel ); ?></strong></li>
        <li class="breadcrumb-item active">Logviewer</li>
      </ol>
    </div>
  </header>

  <style>
    /* Kleur per soort: error rood, waarschuwing oranje, de rest geel.
       De regel krijgt een lichte tint zodat de tekst leesbaar blijft; de sterke
       kleur zit in het balkje links en in het label in de kolom Soort. */
    .ms-log td            { vertical-align: top; font-size: 0.84rem; }
    .ms-log tr.ms-error   { background: #fdeaea; }
    .ms-log tr.ms-warning { background: #fff4e5; }
    .ms-log tr.ms-rest    { background: #fffce0; }
    .ms-log tr.ms-error   td:first-child { box-shadow: inset 4px 0 0 0 #e55353; }
    .ms-log tr.ms-warning td:first-child { box-shadow: inset 4px 0 0 0 #f9b115; }
    .ms-log tr.ms-rest    td:first-child { box-shadow: inset 4px 0 0 0 #e9cf00; }

    .ms-soort { display:inline-block; padding:2px 8px; border-radius:4px;
                font-size:0.72rem; font-weight:700; text-transform:uppercase;
                letter-spacing:0.03em; white-space:nowrap; }
    .ms-soort-error   { background:#e55353; color:#fff; }
    .ms-soort-warning { background:#f9b115; color:#fff; }
    .ms-soort-rest    { background:#f3e200; color:#4b4200; }

    .ms-tijd    { white-space: nowrap; font-variant-numeric: tabular-nums; }
    .ms-details { white-space: pre-wrap; word-break: break-word; max-width: 520px; font-family: Menlo, Consolas, monospace; font-size: 0.78rem; }
    .ms-actie   { word-break: break-word; max-width: 220px; }
    .ms-muted   { color:#768192; }

    .ms-filterbalk .form-check { margin-right: 1.1rem; }
    .ms-filterbalk label       { margin-bottom: 0; }
  </style>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">

          <div class="card">
            <div class="card-header bg-facebook content-center">
              <h2 class="text-white text-uppercase mb-0">Logviewer</h2>
            </div>
            <div class="card-body">

              <!-- ------------------------------------------------- filters -->
              <form method="get" action="page_logviewer.php" class="ms-filterbalk mb-3" id="filterform">
                <input type="hidden" name="gefilterd" value="1">
                <!-- bewust geen 'pagina' in het formulier: na het wijzigen van een
                     filter hoor je weer op pagina 1 te beginnen -->

                <div class="form-group mb-2">
                  <label class="font-weight-bold mr-3">Soort logregel</label>
                  <div class="d-flex flex-wrap">
                    <?php foreach ( $log_soorten as $level => $soort ) { ?>
                      <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox"
                               name="soort[]" value="<?php echo (int)$level; ?>"
                               id="soort_<?php echo (int)$level; ?>"
                               onchange="document.getElementById('filterform').submit();"
                               <?php echo in_array( $level, $gekozen_soorten ) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="soort_<?php echo (int)$level; ?>">
                          <span class="ms-soort ms-soort-<?php echo $soort['kleur']; ?>"><?php echo htmlspecialchars( $soort['label'] ); ?></span>
                          <span class="ms-muted">(<?php echo (int)$level; ?>)</span>
                        </label>
                      </div>
                    <?php } ?>
                  </div>
                </div>

                <div class="form-row align-items-end">
                  <div class="col-sm-4 col-md-3 mb-2">
                    <label for="event_id" class="font-weight-bold">Fotokast</label>
                    <select class="form-control form-control-sm" name="event_id" id="event_id"
                            onchange="document.getElementById('filterform').submit();">
                      <option value="alle" <?php echo ( $event_keuze === 'alle' ) ? 'selected' : ''; ?>>Alle fotokasten</option>
                      <?php foreach ( $fotokasten as $kast ) { ?>
                        <option value="<?php echo (int)$kast->event_id; ?>"
                          <?php echo ( $event_keuze !== 'alle' && (int)$event_keuze == (int)$kast->event_id ) ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars( $kast->name ); ?> (<?php echo (int)$kast->event_id; ?>)<?php echo ( $kast->dashboard_show == 0 ) ? ' — verborgen' : ''; ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>

                  <div class="col-sm-3 col-md-2 mb-2">
                    <label for="per_pagina" class="font-weight-bold">Regels per pagina</label>
                    <select class="form-control form-control-sm" name="per_pagina" id="per_pagina"
                            onchange="document.getElementById('filterform').submit();">
                      <?php foreach ( $per_pagina_opties as $optie ) { ?>
                        <option value="<?php echo $optie; ?>" <?php echo ( $per_pagina == $optie ) ? 'selected' : ''; ?>><?php echo $optie; ?></option>
                      <?php } ?>
                    </select>
                  </div>

                  <div class="col-sm-5 col-md-4 mb-2">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="verberg_ruis" value="1"
                             id="verberg_ruis"
                             onchange="document.getElementById('filterform').submit();"
                             <?php echo $verberg_ruis ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="verberg_ruis">
                        Bekende ruis verbergen
                        <span class="ms-muted" title="Betaalterminal die nog aan het opstarten is, en 'geen sessie-bestand' bij de Flora-kasten. Dezelfde melding als échte storing (rood) blijft gewoon staan.">(?)</span>
                      </label>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-1">Toon</button>
                    <a href="page_logviewer.php" class="btn btn-sm btn-outline-secondary mt-1">Filters wissen</a>
                  </div>

                  <div class="col-md-3 mb-2 text-md-right">
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="checkbox" id="autoververs" checked>
                      <label class="form-check-label" for="autoververs">
                        Auto-ververs <span id="aftellen" class="ms-muted">(30s)</span>
                      </label>
                    </div>
                    <div class="ms-muted" style="font-size:0.78rem">Bijgewerkt: <?php echo date('H:i:s'); ?></div>
                  </div>
                </div>
              </form>

              <?php if ( $geen_soort ) { ?>
                <div class="alert alert-warning mb-0">
                  Er is geen enkel soort logregel aangevinkt, dus er valt niets te tonen.
                  Vink hierboven minimaal één soort aan.
                </div>
              <?php } else { ?>

                <!-- --------------------------------------- paginering boven -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                  <div class="ms-muted">
                    <?php if ( $totaal > 0 ) { ?>
                      Regel <strong><?php echo number_format( $eerste_regel, 0, ',', '.' ); ?></strong>
                      t/m <strong><?php echo number_format( $laatste_regel, 0, ',', '.' ); ?></strong>
                      van <strong><?php echo number_format( $totaal, 0, ',', '.' ); ?></strong>
                      (pagina <?php echo $pagina; ?> van <?php echo $aantal_paginas; ?>)
                    <?php } else { ?>
                      Geen logregels gevonden met deze filters.
                    <?php } ?>
                  </div>
                  <?php echo logviewer_paginatie( $pagina, $aantal_paginas ); ?>
                </div>

                <!-- ---------------------------------------------- de tabel -->
                <div class="table-responsive">
                  <table class="table table-sm table-bordered mb-0 ms-log">
                    <thead class="thead-light">
                      <tr>
                        <th style="width:140px">Tijd</th>
                        <th style="width:150px">Fotokast</th>
                        <th style="width:110px">Soort</th>
                        <th style="width:95px">Bron</th>
                        <th>Actie</th>
                        <th>Details</th>
                        <th style="width:115px">IP</th>
                        <th style="width:80px">Sessie</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if ( count( $regels ) == 0 ) { ?>
                        <tr><td colspan="8" class="text-center ms-muted py-3">Geen logregels gevonden.</td></tr>
                      <?php }
                      foreach ( $regels as $regel ) {
                          $level = (int)$regel->log_level;
                          # onbekend log_level? dan behandelen als "rest" (geel)
                          $soort = isset( $log_soorten[ $level ] )
                                 ? $log_soorten[ $level ]
                                 : array( 'label' => 'Level ' . $level, 'kleur' => 'rest' );
                      ?>
                        <tr class="ms-<?php echo $soort['kleur']; ?>">
                          <td class="ms-tijd"><?php echo date( 'd-m-Y H:i:s', strtotime( $regel->log_created ) ); ?></td>
                          <td>
                            <?php echo htmlspecialchars( logviewer_kastnaam( $regel->event_id ) ); ?>
                            <span class="ms-muted">(<?php echo (int)$regel->event_id; ?>)</span>
                          </td>
                          <td><span class="ms-soort ms-soort-<?php echo $soort['kleur']; ?>"><?php echo htmlspecialchars( $soort['label'] ); ?></span></td>
                          <td><?php echo htmlspecialchars( $regel->log_source ); ?></td>
                          <td class="ms-actie"><?php echo htmlspecialchars( $regel->log_action ); ?></td>
                          <td class="ms-details"><?php echo htmlspecialchars( $regel->log_details ); ?></td>
                          <td class="ms-muted"><?php echo htmlspecialchars( $regel->log_ip ); ?></td>
                          <td class="ms-muted"><?php echo ( (int)$regel->log_session_id > 0 ) ? (int)$regel->log_session_id : ''; ?></td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>

                <!-- --------------------------------------- paginering onder -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
                  <div class="ms-muted">
                    <?php if ( $totaal > 0 ) { ?>
                      Regel <strong><?php echo number_format( $eerste_regel, 0, ',', '.' ); ?></strong>
                      t/m <strong><?php echo number_format( $laatste_regel, 0, ',', '.' ); ?></strong>
                      van <strong><?php echo number_format( $totaal, 0, ',', '.' ); ?></strong>
                      (pagina <?php echo $pagina; ?> van <?php echo $aantal_paginas; ?>)
                    <?php } ?>
                  </div>
                  <?php echo logviewer_paginatie( $pagina, $aantal_paginas ); ?>
                </div>

                <p class="ms-muted mt-3 mb-0" style="font-size:0.78rem">
                  De tijd is de tijd van de fotokast zelf, niet die van de server — de klok van een kast
                  kan een paar minuten voor- of achterlopen. Logregels die de kast pas later doorgeeft
                  (een dag later ingelezen, of opnieuw opgestuurd na een herstart) houden hun
                  oorspronkelijke tijd en staan dus niet bovenaan, maar op hun eigen plek in de tijdlijn.
                </p>

              <?php } ?>

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

<script>
// Elke 30 seconden opnieuw laden, met dezelfde filters en dezelfde pagina
// (location.reload houdt de querystring aan). Het vinkje zet het af en aan en
// blijft onthouden, zodat je rustig een lange foutmelding kunt lezen zonder dat
// de pagina onder je handen wegspringt.
(function () {
  var seconden   = 30;
  var resterend  = seconden;
  var vinkje     = document.getElementById('autoververs');
  var aftellen   = document.getElementById('aftellen');
  var bewaarnaam = 'ms_logviewer_autoververs';

  try {
    if ( window.localStorage && localStorage.getItem(bewaarnaam) === 'uit' ) { vinkje.checked = false; }
  } catch (e) { /* private mode: dan gewoon standaard aan */ }

  vinkje.addEventListener('change', function () {
    resterend = seconden;
    try {
      if ( window.localStorage ) { localStorage.setItem(bewaarnaam, vinkje.checked ? 'aan' : 'uit'); }
    } catch (e) {}
    toon();
  });

  function toon() {
    aftellen.textContent = vinkje.checked ? '(' + resterend + 's)' : '(uit)';
  }

  setInterval(function () {
    if ( !vinkje.checked ) { toon(); return; }
    resterend--;
    toon();
    if ( resterend <= 0 ) { location.reload(); }
  }, 1000);

  toon();
})();
</script>

<?php include("partial_footer.php"); ?>
