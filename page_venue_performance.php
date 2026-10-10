  <?php
  include ("partial_header.php");
  include ("partial_nav.php");
  include ("inc/invul_functions.php");

  # ---------------------------------------------------------------------------
  # Prestatieoverzicht per venue - bedoeld om uit te printen (PDF) en te
  # bespreken met de venue na 3, 6 of 12 maanden.
  # Bron: log_sessionOK (zelfde view als page_invoice_overview.php gebruikt),
  # afspraken uit photobooth_percentage_pricing, venue naam uit client.
  # ---------------------------------------------------------------------------

  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }

  $event_id = (int) $_SESSION['event_id'];

  # --- periode: 3, 6 of 12 maanden, standaard 12 --- #
  $maanden = 12;
  if ( isset ( $_REQUEST['maanden'] ) && in_array( (int) $_REQUEST['maanden'], array( 3, 6, 12 ) ) ) {
    $maanden = (int) $_REQUEST['maanden'];
  }

  # altijd vanaf de 1e van de maand rekenen, anders gaat strtotime de mist in
  # bij maanden met een verschillend aantal dagen (31 jan -3 maanden = 31 okt, ok,
  # maar 31 maart -1 maand = 3 maart).
  $deze_maand    = date('Y-m-01');
  $periode_start = date('Y-m-01', strtotime('-' . ( $maanden - 1 ) . ' months', strtotime( $deze_maand ) ) );
  $periode_eind  = date('Y-m-t');

  $maand_namen = array( 1 => 'januari', 'februari', 'maart', 'april', 'mei', 'juni',
                             'juli', 'augustus', 'september', 'oktober', 'november', 'december' );
  $maand_kort  = array( 1 => 'jan', 'feb', 'mrt', 'apr', 'mei', 'jun',
                             'jul', 'aug', 'sep', 'okt', 'nov', 'dec' );
  $dag_namen   = array( 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag' );
  $dag_kort    = array( 'ma', 'di', 'wo', 'do', 'vr', 'za', 'zo' );

  # --- venue gegevens ophalen --- #
  # de naam van de venue komt uit client.client_name (zoals gevraagd),
  # event_name is de naam van de fotokast zelf.
  $venue_naam  = '';
  $venue_plaats = '';
  $kast_naam   = '';

  $sql = "SELECT client.client_name,
                 client.client_place,
                 event.event_name,
                 event.event_place
            FROM event
       LEFT JOIN client ON client.client_id = event.client_id
           WHERE event.event_id = " . $event_id;
  $result = mysqli_query( $cn , $sql );
  if ( $result && ( $return = mysqli_fetch_object( $result ) ) ) {
    $venue_naam   = trim( $return->client_name );
    $venue_plaats = trim( $return->client_place );
    $kast_naam    = trim( $return->event_name );
    if ( $venue_plaats == '' ) { $venue_plaats = trim( $return->event_place ); }
  }
  if ( $venue_naam == '' ) {
    # niet elke event_id staat in de event tabel - val terug op de kastnaam
    $sql = "SELECT name FROM photobooth_event_ids WHERE event_id = " . $event_id;
    $result = mysqli_query( $cn , $sql );
    if ( $result && ( $return = mysqli_fetch_object( $result ) ) ) { $venue_naam = trim( $return->name ); }
  }
  if ( $venue_naam == '' ) { $venue_naam = 'Onbekende venue'; }

  # --- zorg dat log_session bijgewerkt is met de laatste logs --- #
  mysqli_query( $cn, 'CALL FillSequence()' );

  # --- omzet + aantallen per maand --- #
  # LET OP: bewust alleen de positieve event_id. Rijen met de negatieve id horen bij
  # een eerder (afgesloten) leven van dezelfde kast en horen niet in dit overzicht.
  $sql = "SELECT DATE_FORMAT(sessie_timestamp,'%Y-%m')                                            AS ym,
                 SUM(prijs)/100                                                                   AS omzet,
                 SUM(IF(prijs>0 AND sessie_soort='print',1,0))                                    AS prints,
                 SUM(IF(prijs>0 AND sessie_soort='qr',1,0))                                       AS qr,
                 SUM(IF(prijs>0 AND (sessie_soort IS NULL OR sessie_soort NOT IN ('print','qr')),1,0)) AS overig,
                 SUM(IF(prijs>0,1,0))                                                             AS betaald,
                 SUM(IF(prijs=0,1,0))                                                             AS gratis,
                 COUNT(DISTINCT werkdag)                                                          AS dagen
            FROM log_sessionOK
           WHERE event_id = " . $event_id . "
             AND sessie_timestamp >= '" . $periode_start . " 00:00:00'
             AND sessie_timestamp <= '" . $periode_eind  . " 23:59:59'
        GROUP BY ym";
  $per_maand_db = array();
  $result = mysqli_query( $cn , $sql );
  if ( $result ) {
    while ( $return = mysqli_fetch_object( $result ) ) {
      $per_maand_db[ $return->ym ] = $return;
    }
  }

  # --- prijsafspraken --- #
  # Bewust ALLE vastgelegde afspraken van deze fotokast, ook die van voor of na de
  # getoonde periode. In een gesprek met de venue wil je de hele reeks kunnen laten
  # zien - event 516 heeft er bijvoorbeeld twee, waarvan de tweede pas later ingaat.
  # De regels die in de getoonde periode vallen worden in de tabel gemarkeerd.
  $afspraken = array();
  $sql = "SELECT start_date, end_date, percentage_bar, rental_price
            FROM photobooth_percentage_pricing
           WHERE event_id = " . $event_id . "
        ORDER BY start_date";
  $result = mysqli_query( $cn , $sql );
  if ( $result ) {
    while ( $return = mysqli_fetch_object( $result ) ) { $afspraken[] = $return; }
  }

  # zoek de afspraak die geldt op de 1e van een maand (zelfde logica als page_invoice_overview.php)
  function get_afspraak( $eerste_van_de_maand ) {
    global $afspraken;
    foreach ( $afspraken as $a ) {
      if ( $eerste_van_de_maand >= $a->start_date && $eerste_van_de_maand <= $a->end_date ) { return $a; }
    }
    return null;
  }

  # --- rijen per maand opbouwen, ook de maanden zonder omzet --- #
  $rows            = array();
  $totaal_omzet    = 0;
  $totaal_prints   = 0;
  $totaal_qr       = 0;
  $totaal_overig   = 0;
  $totaal_gratis   = 0;
  $totaal_dagen    = 0;
  $totaal_uitkering = 0;
  $beste_maand     = null;

  for ( $i = 0; $i < $maanden; $i ++ ) {
    $eerste = date('Y-m-01', strtotime('+' . $i . ' months', strtotime( $periode_start ) ) );
    $ym     = substr( $eerste, 0, 7 );
    $jaar   = (int) substr( $ym, 0, 4 );
    $mnd    = (int) substr( $ym, 5, 2 );

    $omzet = 0; $prints = 0; $qr = 0; $overig = 0; $gratis = 0; $dagen = 0;
    if ( isset ( $per_maand_db[ $ym ] ) ) {
      $d      = $per_maand_db[ $ym ];
      $omzet  = (float) $d->omzet;
      $prints = (int)   $d->prints;
      $qr     = (int)   $d->qr;
      $overig = (int)   $d->overig;
      $gratis = (int)   $d->gratis;
      $dagen  = (int)   $d->dagen;
    }

    $a          = get_afspraak( $eerste );
    $percentage = $a ? (float) $a->percentage_bar : 0;
    $verhuur    = $a ? (float) $a->rental_price   : 0;
    $heeft_afspraak = $a ? 1 : 0;

    $omzet_ex   = $omzet / 1.21;
    $uitkering  = $percentage * $omzet_ex;

    $row = array(
      'ym'         => $ym,
      'label'      => $maand_kort[ $mnd ] . ' ' . substr( $jaar, 2, 2 ),
      'label_lang' => $maand_namen[ $mnd ] . ' ' . $jaar,
      'omzet'      => round( $omzet, 2 ),
      'omzet_ex'   => round( $omzet_ex, 2 ),
      'prints'     => $prints,
      'qr'         => $qr,
      'overig'     => $overig,
      'gratis'     => $gratis,
      'dagen'      => $dagen,
      'percentage' => $percentage,
      'verhuur'    => $verhuur,
      'afspraak'   => $heeft_afspraak,
      'uitkering'  => round( $uitkering, 2 ),
      'lopend'     => ( $ym == substr( $deze_maand, 0, 7 ) ) ? 1 : 0
    );
    $rows[] = $row;

    $totaal_omzet     += $omzet;
    $totaal_prints    += $prints;
    $totaal_qr        += $qr;
    $totaal_overig    += $overig;
    $totaal_gratis    += $gratis;
    $totaal_dagen     += $dagen;
    $totaal_uitkering += $uitkering;

    if ( $beste_maand === null || $omzet > $beste_maand['omzet'] ) { $beste_maand = $row; }
  }

  # --- afspraken klaarzetten voor de tabel --- #
  function datum_nl( $datum ) {
    global $maand_namen;
    $t = strtotime( $datum );
    if ( !$t ) { return $datum; }
    return (int) date( 'j', $t ) . ' ' . $maand_namen[ (int) date( 'n', $t ) ] . ' ' . date( 'Y', $t );
  }
  # een einddatum ver in de toekomst betekent in de praktijk 'tot nader order' -
  # '30 november 2055' leest raar in een rapport, 'vanaf 1 december 2026' niet
  $ver_weg = date( 'Y-m-d', strtotime( '+5 years' ) );

  $afspraak_records = array();
  foreach ( $afspraken as $a ) {
    $afspraak_records[] = array(
      'periode'    => ( $a->end_date >= $ver_weg )
                        ? 'vanaf ' . datum_nl( $a->start_date )
                        : datum_nl( $a->start_date ) . ' t/m ' . datum_nl( $a->end_date ),
      'percentage' => (float) $a->percentage_bar,
      'verhuur'    => (float) $a->rental_price,
      'in_periode' => ( $a->end_date >= $periode_start && $a->start_date <= $periode_eind ) ? 1 : 0
    );
  }
  # de uitleg bij de markering heeft alleen zin als er echt iets gemarkeerd is
  # en er ook regels buiten de periode staan
  $in_periode = 0;
  $buiten_periode = 0;
  foreach ( $afspraak_records as $a ) { if ( $a['in_periode'] ) { $in_periode ++; } else { $buiten_periode ++; } }
  $markeer_afspraken = ( $in_periode > 0 && $buiten_periode > 0 ) ? 1 : 0;

  $maanden_zonder_afspraak = 0;
  foreach ( $rows as $r ) { if ( !$r['afspraak'] ) { $maanden_zonder_afspraak ++; } }

  # --- omzet per dag van de week (op werkdag, dus de nacht telt bij de avond ervoor) --- #
  $week = array();
  for ( $d = 0; $d < 7; $d ++ ) {
    $week[ $d ] = array( 'dag' => $dag_namen[ $d ], 'kort' => $dag_kort[ $d ],
                         'omzet' => 0, 'prints' => 0, 'dagen' => 0, 'gem' => 0, 'aandeel' => 0 );
  }
  $sql = "SELECT WEEKDAY(werkdag)                                AS wd,
                 COUNT(DISTINCT werkdag)                         AS dagen,
                 SUM(prijs)/100                                  AS omzet,
                 SUM(IF(prijs>0 AND sessie_soort='print',1,0))   AS prints
            FROM log_sessionOK
           WHERE event_id = " . $event_id . "
             AND sessie_timestamp >= '" . $periode_start . " 00:00:00'
             AND sessie_timestamp <= '" . $periode_eind  . " 23:59:59'
        GROUP BY wd";
  $result = mysqli_query( $cn , $sql );
  if ( $result ) {
    while ( $return = mysqli_fetch_object( $result ) ) {
      $wd = (int) $return->wd;
      if ( !isset( $week[ $wd ] ) ) { continue; }
      $week[ $wd ]['omzet']  = round( (float) $return->omzet, 2 );
      $week[ $wd ]['prints'] = (int) $return->prints;
      $week[ $wd ]['dagen']  = (int) $return->dagen;
    }
  }
  $beste_dag = null;
  foreach ( $week as $wd => $w ) {
    $week[ $wd ]['gem']     = $w['dagen'] > 0 ? round( $w['omzet'] / $w['dagen'], 2 ) : 0;
    $week[ $wd ]['aandeel'] = $totaal_omzet > 0 ? round( 100 * $w['omzet'] / $totaal_omzet, 1 ) : 0;
    if ( $beste_dag === null || $w['omzet'] > $week[ $beste_dag ]['omzet'] ) { $beste_dag = $wd; }
  }

  # --- omzet per tijdstip van de dag --- #
  # de as loopt van 08:00 tot 08:00, gelijk aan de werkdag-grens in log_sessionOK
  $uur_data = array();
  for ( $u = 0; $u < 24; $u ++ ) { $uur_data[ $u ] = array( 'omzet' => 0, 'aantal' => 0, 'prints' => 0 ); }
  $sql = "SELECT HOUR(sessie_timestamp)                          AS uur,
                 SUM(prijs)/100                                  AS omzet,
                 SUM(IF(prijs>0,1,0))                            AS aantal,
                 SUM(IF(prijs>0 AND sessie_soort='print',1,0))   AS prints
            FROM log_sessionOK
           WHERE event_id = " . $event_id . "
             AND sessie_timestamp >= '" . $periode_start . " 00:00:00'
             AND sessie_timestamp <= '" . $periode_eind  . " 23:59:59'
        GROUP BY uur";
  $result = mysqli_query( $cn , $sql );
  if ( $result ) {
    while ( $return = mysqli_fetch_object( $result ) ) {
      $u = (int) $return->uur;
      $uur_data[ $u ] = array( 'omzet'  => round( (float) $return->omzet, 2 ),
                               'aantal' => (int) $return->aantal,
                               'prints' => (int) $return->prints );
    }
  }
  $uren      = array();
  $beste_uur = null;
  for ( $i = 0; $i < 24; $i ++ ) {
    $u = ( $i + 8 ) % 24;   // 08:00 ... 07:00
    $uren[] = array(
      'uur'     => $u,
      'label'   => str_pad( $u, 2, '0', STR_PAD_LEFT ),
      'tijdvak' => str_pad( $u, 2, '0', STR_PAD_LEFT ) . ':00 - ' . str_pad( ( $u + 1 ) % 24, 2, '0', STR_PAD_LEFT ) . ':00',
      'omzet'   => $uur_data[ $u ]['omzet'],
      'aantal'  => $uur_data[ $u ]['aantal'],
      'prints'  => $uur_data[ $u ]['prints'],
      'aandeel' => $totaal_omzet > 0 ? round( 100 * $uur_data[ $u ]['omzet'] / $totaal_omzet, 1 ) : 0
    );
    if ( $beste_uur === null || $uur_data[ $u ]['omzet'] > $uur_data[ $beste_uur ]['omzet'] ) { $beste_uur = $u; }
  }

  # --- opmaakhulpjes voor de tekstregels hieronder --- #
  function bedrag( $n ) { return '€ ' . number_format( $n, 2, ',', '.' ); }
  function aantal( $n ) { return number_format( $n, 0, ',', '.' ); }

  # --- kengetallen --- #
  $gem_omzet_maand = $maanden > 0 ? $totaal_omzet / $maanden : 0;
  $gem_omzet_dag   = $totaal_dagen > 0 ? $totaal_omzet / $totaal_dagen : 0;
  $periode_tekst   = $rows[0]['label_lang'] . ' t/m ' . $rows[ count($rows)-1 ]['label_lang'];

  # --- 'in het kort': een keer opgebouwd, gebruikt op het scherm en in de PDF --- #
  $in_het_kort = array();
  $in_het_kort[] = 'Totale omzet ' . bedrag( $totaal_omzet ) . ' inclusief btw over ' . $maanden .
                   ' maanden, gemiddeld ' . bedrag( $gem_omzet_maand ) . ' per maand.';
  if ( $totaal_dagen > 0 ) {
    $in_het_kort[] = 'Er zijn ' . aantal( $totaal_prints ) . ' fotostroken geprint' .
                     ( $totaal_qr > 0 ? ' en ' . aantal( $totaal_qr ) . ' QR-codes verkocht' : '' ) .
                     ', verdeeld over ' . aantal( $totaal_dagen ) . ' dagen dat de fotokast aan het werk was ' .
                     '(gemiddeld ' . bedrag( $gem_omzet_dag ) . ' per dag).';
  }
  if ( $beste_maand && $beste_maand['omzet'] > 0 ) {
    $in_het_kort[] = 'De beste maand was ' . $beste_maand['label_lang'] . ' met ' . bedrag( $beste_maand['omzet'] ) .
                     ' uit ' . aantal( $beste_maand['prints'] ) . ' prints.';
  }
  if ( $beste_dag !== null && $week[ $beste_dag ]['omzet'] > 0 ) {
    $bd = $week[ $beste_dag ];
    $in_het_kort[] = 'De beste dag van de week is ' . $bd['dag'] . ': ' . number_format( $bd['aandeel'], 1, ',', '.' ) .
                     ' % van de omzet, gemiddeld ' . bedrag( $bd['gem'] ) . ' per ' . $bd['dag'] . '.';
  }
  if ( $beste_uur !== null && $uur_data[ $beste_uur ]['omzet'] > 0 ) {
    $in_het_kort[] = 'Het drukste tijdvak is ' . str_pad( $beste_uur, 2, '0', STR_PAD_LEFT ) . ':00 - ' .
                     str_pad( ( $beste_uur + 1 ) % 24, 2, '0', STR_PAD_LEFT ) . ':00.';
  }
  if ( $totaal_gratis > 0 ) {
    $in_het_kort[] = 'Daarnaast waren er ' . aantal( $totaal_gratis ) . ' gratis sessies (verhuur); ' .
                     'die tellen niet mee in de omzet hierboven.';
  }
  $in_het_kort[] = 'Uitkering aan de venue volgens de afspraken: ' . bedrag( $totaal_uitkering ) . ' exclusief btw.';

  # --- logo's als data-uri, zodat de PDF ze zonder extra request kan gebruiken --- #
  function logo_data_uri( $pad ) {
    if ( !is_readable( $pad ) ) { return ''; }
    return 'data:image/png;base64,' . base64_encode( file_get_contents( $pad ) );
  }
  $logo_magicshoot  = logo_data_uri( __DIR__ . '/img/logo_magicshoot_pdf.png' );
  $logo_magicstrook = logo_data_uri( __DIR__ . '/img/logo_magicstrook_pdf.png' );

  # --- alles wat de grafieken en de PDF nodig hebben --- #
  $js_data = array(
    'venue'        => $venue_naam,
    'plaats'       => $venue_plaats,
    'kast'         => $kast_naam,
    'event_id'     => $event_id,
    'maanden'      => $maanden,
    'periode'      => $periode_tekst,
    'gemaakt_op'   => date('d-m-Y'),
    'rows'         => $rows,
    'afspraken'    => $afspraak_records,
    'markeer_afspraken'       => $markeer_afspraken,
    'maanden_zonder_afspraak' => $maanden_zonder_afspraak,
    'week'         => array_values( $week ),
    'uren'         => $uren,
    'beste_dag'    => $beste_dag !== null ? $week[ $beste_dag ] : null,
    'beste_uur'    => $beste_uur !== null ? array( 'tijdvak' => str_pad( $beste_uur, 2, '0', STR_PAD_LEFT ) . ':00 - ' . str_pad( ( $beste_uur + 1 ) % 24, 2, '0', STR_PAD_LEFT ) . ':00',
                                                   'omzet'   => $uur_data[ $beste_uur ]['omzet'],
                                                   'aandeel' => $totaal_omzet > 0 ? round( 100 * $uur_data[ $beste_uur ]['omzet'] / $totaal_omzet, 1 ) : 0 ) : null,
    'beste_maand'  => $beste_maand,
    'in_het_kort'  => $in_het_kort,
    'totalen'      => array(
      'omzet'      => round( $totaal_omzet, 2 ),
      'omzet_ex'   => round( $totaal_omzet / 1.21, 2 ),
      'prints'     => $totaal_prints,
      'qr'         => $totaal_qr,
      'overig'     => $totaal_overig,
      'gratis'     => $totaal_gratis,
      'dagen'      => $totaal_dagen,
      'uitkering'  => round( $totaal_uitkering, 2 ),
      'gem_maand'  => round( $gem_omzet_maand, 2 ),
      'gem_dag'    => round( $gem_omzet_dag, 2 )
    ),
    'logo_magicshoot'  => $logo_magicshoot,
    'logo_magicstrook' => $logo_magicstrook
  );
  ?>

    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo htmlspecialchars( get_event_name( $event_id ) ); ?></strong></li>
        <li class="breadcrumb-item">Prestatieoverzicht venue - <?php echo htmlspecialchars( $periode_tekst ); ?></li>
      </ol>
    </div>

  </header>
  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">

          <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12">
              <div class="card">
                <div class="card-header bg-facebook content-center">
                  <h2 class="text-white text-uppercase">Prestatieoverzicht venue - <?php echo htmlspecialchars( $venue_naam ); ?></h2>
                </div>
                <div class="card-body">

                  <a href="page_invoice_overview.php" class="btn btn-success">Maandafrekening</a>
                  <a href="page_monthly_overview.php" class="btn btn-info">Omzet per maand</a>
                  <a href="page_revenue_per_evening.php" class="btn btn-info">Omzet per werkdag/avond</a>
                  <a href="page_revenue_per_workweek.php" class="btn btn-info">Omzet per werkweek</a>
                  <a href="page_session_overview.php?aantal=40" class="btn btn-warning">Sessie overzicht</a>
                  <a href="page_transaction_overview.php" class="btn btn-warning">Transactie overzicht</a>
                  <a href="page_venue_performance.php" class="btn btn-primary">Prestatieoverzicht venue (PDF)</a><br><br>

                  <div class="ms-toolbar">
                    <div>
                      <span class="ms-toolbar-label">Periode</span>
                      <div class="btn-group btn-group-sm" role="group">
                        <?php foreach ( array( 3, 6, 12 ) as $m ) { ?>
                          <a href="page_venue_performance.php?maanden=<?php echo $m; ?>"
                             class="btn <?php echo ( $m == $maanden ) ? 'btn-primary' : 'btn-outline-primary'; ?>"><?php echo $m; ?> maanden</a>
                        <?php } ?>
                      </div>
                    </div>
                    <div class="ms-toolbar-spacer"></div>
                    <div>
                      <button type="button" class="btn btn-primary" id="btn_pdf">
                        <svg class="c-icon mr-1"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-cloud-download"></use></svg>
                        Download PDF
                      </button>
                    </div>
                  </div>

                  <p class="ms-muted mt-3 mb-4">
                    <?php echo htmlspecialchars( $venue_naam ); ?><?php if ( $venue_plaats != '' ) { echo ' &middot; ' . htmlspecialchars( $venue_plaats ); } ?>
                    &middot; fotokast <?php echo htmlspecialchars( $kast_naam != '' ? $kast_naam : ('event ' . $event_id) ); ?>
                    &middot; <?php echo htmlspecialchars( $periode_tekst ); ?>
                    <?php if ( $rows[ count($rows)-1 ]['lopend'] ) { ?>
                      <br><em>De laatste maand is nog niet compleet - die loopt nu.</em>
                    <?php } ?>
                  </p>

                  <!-- kengetallen -->
                  <div class="ms-kpi-row">
                    <div class="ms-kpi ms-kpi-hero">
                      <div class="ms-kpi-label">Totale omzet incl. btw</div>
                      <div class="ms-kpi-value">&euro; <?php echo number_format( $totaal_omzet, 2, ',', '.' ); ?></div>
                      <div class="ms-kpi-sub">gemiddeld &euro; <?php echo number_format( $gem_omzet_maand, 2, ',', '.' ); ?> per maand</div>
                    </div>
                    <div class="ms-kpi">
                      <div class="ms-kpi-label">Aantal prints</div>
                      <div class="ms-kpi-value"><?php echo number_format( $totaal_prints, 0, ',', '.' ); ?></div>
                      <div class="ms-kpi-sub"><?php echo number_format( $totaal_qr, 0, ',', '.' ); ?> QR-codes &middot; <?php echo number_format( $totaal_gratis, 0, ',', '.' ); ?> gratis</div>
                    </div>
                    <div class="ms-kpi">
                      <div class="ms-kpi-label">Actieve dagen</div>
                      <div class="ms-kpi-value"><?php echo number_format( $totaal_dagen, 0, ',', '.' ); ?></div>
                      <div class="ms-kpi-sub">gemiddeld &euro; <?php echo number_format( $gem_omzet_dag, 2, ',', '.' ); ?> per dag</div>
                    </div>
                    <div class="ms-kpi">
                      <div class="ms-kpi-label">Uitkering venue excl. btw</div>
                      <div class="ms-kpi-value">&euro; <?php echo number_format( $totaal_uitkering, 2, ',', '.' ); ?></div>
                      <div class="ms-kpi-sub">volgens de afspraken hieronder</div>
                    </div>
                  </div>

                  <!-- afspraken -->
                  <h3 class="ms-h3">Afspraken</h3>
                  <table class="table table-bordered table-sm ms-table" style="max-width:720px">
                    <thead class="thead-light">
                      <tr>
                        <th>Periode</th>
                        <th class="text-right">Percentage venue</th>
                        <th class="text-right">Verhuurprijs per avond</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ( $afspraak_records as $a ) { ?>
                        <tr<?php echo $a['in_periode'] ? ' class="ms-actief"' : ''; ?>>
                          <td><?php echo htmlspecialchars( $a['periode'] ); ?></td>
                          <td class="text-right"><?php echo number_format( $a['percentage'] * 100, 0, ',', '.' ); ?> %</td>
                          <td class="text-right">&euro; <?php echo number_format( $a['verhuur'], 2, ',', '.' ); ?></td>
                        </tr>
                      <?php }
                      if ( count( $afspraak_records ) == 0 ) { ?>
                        <tr><td colspan="3" class="ms-muted">Er is voor deze fotokast geen afspraak vastgelegd.</td></tr>
                      <?php } ?>
                    </tbody>
                  </table>
                  <?php if ( $markeer_afspraken ) { ?>
                    <p class="ms-muted">De gemarkeerde regels gelden in de getoonde periode.</p>
                  <?php }
                  if ( $maanden_zonder_afspraak > 0 ) { ?>
                    <p class="ms-muted">Voor <?php echo $maanden_zonder_afspraak; ?> van de <?php echo $maanden; ?> getoonde maanden is geen afspraak vastgelegd; die maanden tellen met 0 % mee in de uitkering.</p>
                  <?php } ?>

                  <h3 class="ms-h3">In het kort</h3>
                  <ul class="ms-kort">
                    <?php foreach ( $in_het_kort as $regel ) { ?>
                      <li><?php echo htmlspecialchars( $regel ); ?></li>
                    <?php } ?>
                  </ul>

                  <!-- grafieken -->
                  <h3 class="ms-h3">Omzet per maand</h3>
                  <p class="ms-muted">Bedragen inclusief btw.</p>
                  <div class="ms-chart"><canvas id="chart_omzet"></canvas></div>

                  <h3 class="ms-h3">Aantal prints per maand</h3>
                  <div class="ms-chart"><canvas id="chart_prints"></canvas></div>

                  <h3 class="ms-h3">Welke dag van de week is het drukst?</h3>
                  <p class="ms-muted">Een avond telt bij de dag waarop hij begon: de nacht na zaterdag telt mee als zaterdag.</p>
                  <div class="ms-chart"><canvas id="chart_week"></canvas></div>

                  <h3 class="ms-h3">Op welk tijdstip wordt de fotokast gebruikt?</h3>
                  <p class="ms-muted">De as loopt van 08:00 's ochtends tot 08:00 de volgende ochtend.</p>
                  <div class="ms-chart"><canvas id="chart_uur"></canvas></div>

                  <!-- cijfers -->
                  <h3 class="ms-h3">Cijfers per maand</h3>
                  <table class="table table-bordered table-striped table-sm ms-table">
                    <thead class="thead-light">
                      <tr>
                        <th>Maand</th>
                        <th class="text-right">Actieve dagen</th>
                        <th class="text-right">Prints</th>
                        <th class="text-right">QR</th>
                        <th class="text-right">Gratis</th>
                        <th class="text-right">Omzet incl. btw</th>
                        <th class="text-right">Omzet excl. btw</th>
                        <th class="text-right">% venue</th>
                        <th class="text-right">Uitkering venue</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ( $rows as $r ) { ?>
                        <tr>
                          <td><?php echo htmlspecialchars( $r['label_lang'] ); ?><?php if ( $r['lopend'] ) { echo ' <span class="ms-muted">(loopt nog)</span>'; } ?></td>
                          <td class="text-right"><?php echo $r['dagen']; ?></td>
                          <td class="text-right"><?php echo number_format( $r['prints'], 0, ',', '.' ); ?></td>
                          <td class="text-right"><?php echo number_format( $r['qr'], 0, ',', '.' ); ?></td>
                          <td class="text-right"><?php echo number_format( $r['gratis'], 0, ',', '.' ); ?></td>
                          <td class="text-right">&euro; <?php echo number_format( $r['omzet'], 2, ',', '.' ); ?></td>
                          <td class="text-right">&euro; <?php echo number_format( $r['omzet_ex'], 2, ',', '.' ); ?></td>
                          <td class="text-right"><?php echo $r['afspraak'] ? number_format( $r['percentage'] * 100, 0, ',', '.' ) . ' %' : '-'; ?></td>
                          <td class="text-right">&euro; <?php echo number_format( $r['uitkering'], 2, ',', '.' ); ?></td>
                        </tr>
                      <?php } ?>
                      <tr class="ms-total">
                        <td><strong>Totaal</strong></td>
                        <td class="text-right"><strong><?php echo $totaal_dagen; ?></strong></td>
                        <td class="text-right"><strong><?php echo number_format( $totaal_prints, 0, ',', '.' ); ?></strong></td>
                        <td class="text-right"><strong><?php echo number_format( $totaal_qr, 0, ',', '.' ); ?></strong></td>
                        <td class="text-right"><strong><?php echo number_format( $totaal_gratis, 0, ',', '.' ); ?></strong></td>
                        <td class="text-right"><strong>&euro; <?php echo number_format( $totaal_omzet, 2, ',', '.' ); ?></strong></td>
                        <td class="text-right"><strong>&euro; <?php echo number_format( $totaal_omzet / 1.21, 2, ',', '.' ); ?></strong></td>
                        <td class="text-right"></td>
                        <td class="text-right"><strong>&euro; <?php echo number_format( $totaal_uitkering, 2, ',', '.' ); ?></strong></td>
                      </tr>
                    </tbody>
                  </table>

                  <h3 class="ms-h3">Cijfers per dag van de week</h3>
                  <table class="table table-bordered table-striped table-sm ms-table" style="max-width:820px">
                    <thead class="thead-light">
                      <tr>
                        <th>Dag</th>
                        <th class="text-right">Actieve dagen</th>
                        <th class="text-right">Prints</th>
                        <th class="text-right">Omzet incl. btw</th>
                        <th class="text-right">Gemiddeld per dag</th>
                        <th class="text-right">Aandeel</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ( $week as $w ) { ?>
                        <tr>
                          <td><?php echo ucfirst( $w['dag'] ); ?></td>
                          <td class="text-right"><?php echo $w['dagen']; ?></td>
                          <td class="text-right"><?php echo number_format( $w['prints'], 0, ',', '.' ); ?></td>
                          <td class="text-right">&euro; <?php echo number_format( $w['omzet'], 2, ',', '.' ); ?></td>
                          <td class="text-right">&euro; <?php echo number_format( $w['gem'], 2, ',', '.' ); ?></td>
                          <td class="text-right"><?php echo number_format( $w['aandeel'], 1, ',', '.' ); ?> %</td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>

                  <h3 class="ms-h3">Cijfers per tijdstip</h3>
                  <table class="table table-bordered table-striped table-sm ms-table" style="max-width:820px">
                    <thead class="thead-light">
                      <tr>
                        <th>Tijdvak</th>
                        <th class="text-right">Prints</th>
                        <th class="text-right">Transacties</th>
                        <th class="text-right">Omzet incl. btw</th>
                        <th class="text-right">Aandeel</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $iets_getoond = false;
                      foreach ( $uren as $u ) {
                        if ( $u['aantal'] == 0 && $u['omzet'] == 0 ) { continue; }
                        $iets_getoond = true;
                        ?>
                        <tr>
                          <td><?php echo $u['tijdvak']; ?></td>
                          <td class="text-right"><?php echo number_format( $u['prints'], 0, ',', '.' ); ?></td>
                          <td class="text-right"><?php echo number_format( $u['aantal'], 0, ',', '.' ); ?></td>
                          <td class="text-right">&euro; <?php echo number_format( $u['omzet'], 2, ',', '.' ); ?></td>
                          <td class="text-right"><?php echo number_format( $u['aandeel'], 1, ',', '.' ); ?> %</td>
                        </tr>
                      <?php }
                      if ( !$iets_getoond ) { ?>
                        <tr><td colspan="5" class="ms-muted">Geen sessies in deze periode.</td></tr>
                      <?php } ?>
                    </tbody>
                  </table>

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

<style>
  .ms-toolbar        { display:flex; align-items:center; flex-wrap:wrap; gap:12px; }
  .ms-toolbar-spacer { flex:1 1 auto; }
  .ms-toolbar-label  { display:block; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#8a8a8a; margin-bottom:3px; }
  .ms-h3             { font-size:1.05rem; font-weight:600; color:#1a1a1a; margin:34px 0 4px; }
  .ms-muted          { color:#8a8a8a; font-size:.86rem; }
  .ms-table          { font-variant-numeric:tabular-nums; }
  .ms-table th       { font-weight:600; font-size:.82rem; }
  .ms-total td       { border-top:2px solid #0091c7 !important; background:#f2f9fc !important; }
  .ms-actief td      { background:#f2f9fc !important; font-weight:600; }
  .ms-kort           { margin:6px 0 0; padding-left:18px; max-width:760px; }
  .ms-kort li        { margin-bottom:5px; color:#1a1a1a; }
  .ms-kort li::marker{ color:#0091c7; }
  .ms-chart          { position:relative; height:300px; margin:10px 0 6px; }
  .ms-kpi-row        { display:flex; flex-wrap:wrap; gap:14px; margin:6px 0 10px; }
  .ms-kpi            { flex:1 1 180px; border:1px solid #e6e9ec; border-left:3px solid #0091c7; border-radius:4px; padding:12px 14px; background:#fbfdfe; }
  .ms-kpi-label      { font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:#8a8a8a; }
  .ms-kpi-value      { font-size:1.55rem; font-weight:600; color:#1a1a1a; line-height:1.25; }
  .ms-kpi-hero .ms-kpi-value { font-size:2.1rem; }
  .ms-kpi-sub        { font-size:.78rem; color:#595959; }
  #pdf_workbench     { position:fixed; left:-10000px; top:0; width:920px; }
</style>

<!-- werkbank voor de PDF-grafieken: staat buiten beeld, wordt op groot formaat getekend -->
<div id="pdf_workbench">
  <canvas id="pdf_chart_omzet"  width="920" height="430"></canvas>
  <canvas id="pdf_chart_prints" width="920" height="430"></canvas>
  <canvas id="pdf_chart_week"   width="920" height="400"></canvas>
  <canvas id="pdf_chart_uur"    width="920" height="400"></canvas>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
<script>
(function () {
  'use strict';

  var D = <?php echo json_encode( $js_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?>;

  /* ---------- kleuren ---------- */
  var C = {
    accent : '#0091c7',   /* MagicShoot cyaan, donker genoeg voor 3:1 op wit */
    ink    : '#1a1a1a',
    ink2   : '#595959',
    ink3   : '#8a8a8a',
    grid   : '#e6e9ec',
    band   : '#f2f9fc',
    pink   : '#c8006e'    /* MagicStrook, alleen als accentlijntje */
  };

  /* ---------- opmaak ---------- */
  function eur( n )  { return '€ ' + Number( n || 0 ).toLocaleString( 'nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 } ); }
  function eur0( n ) { return '€ ' + Math.round( Number( n || 0 ) ).toLocaleString( 'nl-NL' ); }
  function num( n )  { return Number( n || 0 ).toLocaleString( 'nl-NL' ); }
  function pct( n )  { return Number( n || 0 ).toLocaleString( 'nl-NL', { minimumFractionDigits: 1, maximumFractionDigits: 1 } ) + ' %'; }

  /* Zet het maximum op de grafiek zelf. Bewust maar op een punt: alle
     getallen staan al in de tabel, dus de grafiek hoeft alleen de vorm
     en de uitschieter te vertellen. */
  function topLabelPlugin( formatter, fontSize ) {
    return {
      id: 'topLabel',
      afterDatasetsDraw: function ( chart ) {
        var ds   = chart.data.datasets[0];
        var meta = chart.getDatasetMeta( 0 );
        if ( !ds || !meta || !meta.data.length ) { return; }
        var best = -1, bi = -1, i;
        for ( i = 0; i < ds.data.length; i ++ ) { if ( ds.data[i] > best ) { best = ds.data[i]; bi = i; } }
        if ( bi < 0 || best <= 0 ) { return; }
        var bar = meta.data[ bi ];
        var cx  = chart.ctx;
        cx.save();
        cx.font         = '600 ' + ( fontSize || 12 ) + 'px system-ui, -apple-system, "Segoe UI", Helvetica, Arial, sans-serif';
        cx.fillStyle    = C.ink;
        cx.textAlign    = 'center';
        cx.textBaseline = 'bottom';
        cx.fillText( formatter( best ), bar.x, bar.y - 6 );
        cx.restore();
      }
    };
  }

  var whiteBg = {
    id: 'whiteBg',
    beforeDraw: function ( chart ) {
      var cx = chart.ctx;
      cx.save();
      cx.globalCompositeOperation = 'destination-over';
      cx.fillStyle = '#ffffff';
      cx.fillRect( 0, 0, chart.width, chart.height );
      cx.restore();
    }
  };

  function barConfig( labels, values, opts ) {
    opts = opts || {};
    return {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [ {
          data            : values,
          backgroundColor : C.accent,
          borderRadius    : { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
          borderSkipped   : 'bottom',
          maxBarThickness : opts.thickness || 28
        } ]
      },
      options: {
        responsive          : opts.responsive !== false,
        maintainAspectRatio : false,
        animation           : false,   /* niets te animeren op een rapportpagina; tekent meteen */
        devicePixelRatio    : opts.dpr || undefined,
        layout              : { padding: { top: 22, right: 4, bottom: 0, left: 0 } },
        plugins: {
          legend  : { display: false },   /* een reeks: de titel zegt al wat er staat */
          tooltip : {
            backgroundColor : '#ffffff',
            titleColor      : C.ink,
            bodyColor       : C.ink2,
            borderColor     : C.grid,
            borderWidth     : 1,
            padding         : 10,
            displayColors   : false,
            callbacks       : { label: function ( ctx ) { return opts.tip ? opts.tip( ctx.dataIndex ) : String( ctx.parsed.y ); } }
          }
        },
        scales: {
          x: {
            grid   : { display: false, drawBorder: false },
            border : { color: C.grid },
            ticks  : { color: C.ink2, font: { size: opts.tickSize || 11 }, maxRotation: 0, autoSkip: true }
          },
          y: {
            beginAtZero : true,
            grid        : { color: C.grid, drawBorder: false, lineWidth: 1 },
            border      : { display: false },
            ticks       : { color: C.ink3, font: { size: opts.tickSize || 11 }, maxTicksLimit: 6,
                            callback: function ( v ) { return opts.yfmt ? opts.yfmt( v ) : v; } }
          }
        }
      }
    };
  }

  /* ---------- reeksen ---------- */
  var mLabels = D.rows.map( function ( r ) { return r.label; } );
  var mOmzet  = D.rows.map( function ( r ) { return r.omzet; } );
  var mPrints = D.rows.map( function ( r ) { return r.prints; } );
  var wLabels = D.week.map( function ( w ) { return w.kort; } );
  var wOmzet  = D.week.map( function ( w ) { return w.omzet; } );
  var uLabels = D.uren.map( function ( u ) { return u.label; } );
  var uOmzet  = D.uren.map( function ( u ) { return u.omzet; } );

  var tipOmzet  = function ( i ) { return D.rows[i].label_lang + ': ' + eur( D.rows[i].omzet ) + '  (' + num( D.rows[i].prints ) + ' prints)'; };
  var tipPrints = function ( i ) { return D.rows[i].label_lang + ': ' + num( D.rows[i].prints ) + ' prints  (' + eur( D.rows[i].omzet ) + ')'; };
  var tipWeek   = function ( i ) { return D.week[i].dag + ': ' + eur( D.week[i].omzet ) + '  (' + num( D.week[i].dagen ) + ' dagen, gem. ' + eur( D.week[i].gem ) + ')'; };
  var tipUur    = function ( i ) { return D.uren[i].tijdvak + ': ' + eur( D.uren[i].omzet ) + '  (' + num( D.uren[i].aantal ) + ' transacties)'; };

  /* ---------- grafieken op het scherm ---------- */
  Chart.defaults.font.family = 'system-ui, -apple-system, "Segoe UI", Helvetica, Arial, sans-serif';

  function screenChart( id, cfg, plugin ) {
    var el = document.getElementById( id );
    if ( !el ) { return null; }
    return new Chart( el, Object.assign( {}, cfg, { plugins: [ plugin ] } ) );
  }

  screenChart( 'chart_omzet',  barConfig( mLabels, mOmzet,  { tip: tipOmzet,  yfmt: eur0 } ),                  topLabelPlugin( eur0, 12 ) );
  screenChart( 'chart_prints', barConfig( mLabels, mPrints, { tip: tipPrints, yfmt: num } ),                   topLabelPlugin( num, 12 ) );
  screenChart( 'chart_week',   barConfig( wLabels, wOmzet,  { tip: tipWeek,   yfmt: eur0, thickness: 44 } ),   topLabelPlugin( eur0, 12 ) );
  screenChart( 'chart_uur',    barConfig( uLabels, uOmzet,  { tip: tipUur,    yfmt: eur0, thickness: 22 } ),   topLabelPlugin( eur0, 12 ) );

  /* ---------- dezelfde grafieken, maar op PDF-formaat ---------- */
  /* JPEG en niet PNG: jsPDF neemt JPEG rechtstreeks over, terwijl het een PNG
     ongecomprimeerd opslaat - dat gaf een PDF van tientallen MB's. Op ~390 dpi
     is er van de compressie niets te zien. */
  function pdfChart( id, cfg, plugin ) {
    var el    = document.getElementById( id );
    var ratio = Number( el.getAttribute( 'height' ) ) / Number( el.getAttribute( 'width' ) );
    var ch    = new Chart( el, Object.assign( {}, cfg, { plugins: [ whiteBg, plugin ] } ) );
    var img   = ch.toBase64Image( 'image/jpeg', 0.92 );
    ch.destroy();
    return { data: img, h: CW * ratio };
  }

  function buildChartImages() {
    var base = { responsive: false, dpr: 3, tickSize: 13 };
    return {
      omzet : pdfChart( 'pdf_chart_omzet',  barConfig( mLabels, mOmzet,  Object.assign( { yfmt: eur0, thickness: 30 }, base ) ), topLabelPlugin( eur0, 15 ) ),
      prints: pdfChart( 'pdf_chart_prints', barConfig( mLabels, mPrints, Object.assign( { yfmt: num,  thickness: 30 }, base ) ), topLabelPlugin( num,  15 ) ),
      week  : pdfChart( 'pdf_chart_week',   barConfig( wLabels, wOmzet,  Object.assign( { yfmt: eur0, thickness: 46 }, base ) ), topLabelPlugin( eur0, 15 ) ),
      uur   : pdfChart( 'pdf_chart_uur',    barConfig( uLabels, uOmzet,  Object.assign( { yfmt: eur0, thickness: 24 }, base ) ), topLabelPlugin( eur0, 15 ) )
    };
  }

  /* ---------- PDF ---------- */
  var PAGE_W = 210, PAGE_H = 297, M = 15, CW = PAGE_W - 2 * M, FOOT = 18;
  var TOP    = 36;               /* eerste regel onder de paginakop */

  function rgb( hex ) {
    return [ parseInt( hex.substr( 1, 2 ), 16 ), parseInt( hex.substr( 3, 2 ), 16 ), parseInt( hex.substr( 5, 2 ), 16 ) ];
  }
  function setFill( doc, hex ) { var c = rgb( hex ); doc.setFillColor( c[0], c[1], c[2] ); }
  function setText( doc, hex ) { var c = rgb( hex ); doc.setTextColor( c[0], c[1], c[2] ); }
  function setDraw( doc, hex ) { var c = rgb( hex ); doc.setDrawColor( c[0], c[1], c[2] ); }

  function pageHeader( doc, subtitel ) {
    if ( D.logo_magicshoot ) { doc.addImage( D.logo_magicshoot, 'PNG', M, 12, 42, 42 * 95 / 650, 'logo_ms', 'FAST' ); }
    doc.setFont( 'helvetica', 'normal' ).setFontSize( 8.5 );
    setText( doc, C.ink3 );
    doc.text( subtitel, PAGE_W - M, 15.5, { align: 'right' } );
    doc.text( D.venue + ( D.plaats ? ' · ' + D.plaats : '' ), PAGE_W - M, 19.5, { align: 'right' } );
    setDraw( doc, C.accent );
    doc.setLineWidth( 0.6 );
    doc.line( M, 24, PAGE_W - M, 24 );
  }

  function pageFooters( doc ) {
    var total = doc.getNumberOfPages(), p;
    for ( p = 1; p <= total; p ++ ) {
      doc.setPage( p );
      setDraw( doc, C.grid );
      doc.setLineWidth( 0.2 );
      doc.line( M, PAGE_H - 14, PAGE_W - M, PAGE_H - 14 );
      if ( D.logo_magicstrook ) { doc.addImage( D.logo_magicstrook, 'PNG', M, PAGE_H - 12, 7, 7, 'logo_mstrook', 'FAST' ); }
      doc.setFont( 'helvetica', 'normal' ).setFontSize( 7.5 );
      setText( doc, C.ink3 );
      doc.text( 'MagicStrook · een product van MagicShoot · magicshoot.nl', M + 9.5, PAGE_H - 7.6 );
      doc.text( 'Pagina ' + p + ' van ' + total, PAGE_W - M, PAGE_H - 7.6, { align: 'right' } );
    }
  }

  function sectionTitle( doc, y, tekst, onderschrift ) {
    doc.setFont( 'helvetica', 'bold' ).setFontSize( 12 );
    setText( doc, C.ink );
    doc.text( tekst, M, y );
    if ( onderschrift ) {
      doc.setFont( 'helvetica', 'normal' ).setFontSize( 8.5 );
      setText( doc, C.ink3 );
      doc.text( onderschrift, M, y + 4.6 );
      return y + 4.6;
    }
    return y;
  }

  function kpiBox( doc, x, y, w, h, label, waarde, sub, groot ) {
    setFill( doc, '#fbfdfe' );
    setDraw( doc, C.grid );
    doc.setLineWidth( 0.2 );
    doc.roundedRect( x, y, w, h, 1.2, 1.2, 'FD' );
    setFill( doc, C.accent );
    doc.rect( x, y, 1.1, h, 'F' );
    doc.setFont( 'helvetica', 'normal' ).setFontSize( 7 );
    setText( doc, C.ink3 );
    doc.text( label, x + 4.5, y + 5.6 );
    doc.setFont( 'helvetica', 'bold' ).setFontSize( groot ? 15 : 12.5 );
    setText( doc, C.ink );
    doc.text( waarde, x + 4.5, y + ( groot ? 13.6 : 13.2 ) );
    if ( sub ) {
      doc.setFont( 'helvetica', 'normal' ).setFontSize( 7 );
      setText( doc, C.ink2 );
      doc.text( sub, x + 4.5, y + 18.6 );
    }
  }

  /* Wordt in buildPdf() ingevuld. Zorgt dat elke pagina precies een keer een
     kop krijgt, ook de vervolgpagina's die autoTable zelf aanmaakt. */
  var stampHeader = function () {};

  function tableStyle( extra ) {
    extra = extra || {};
    var markeer = extra.markeer;   /* functie(rijnummer) -> true om die rij te accentueren */
    delete extra.markeer;
    return Object.assign( {
      didDrawPage : function ( data ) { stampHeader( data.doc ); },
      didParseCell: function ( data ) {
        /* de eerste kolom is tekst, de rest getallen - kop en totaalregel mee laten uitlijnen */
        if ( data.column.index > 0 && ( data.section === 'head' || data.section === 'foot' ) ) {
          data.cell.styles.halign = 'right';
        }
        if ( markeer && data.section === 'body' && markeer( data.row.index ) ) {
          data.cell.styles.fillColor = rgb( C.band );
          data.cell.styles.fontStyle = 'bold';
        }
      },
      theme       : 'grid',
      margin      : { left: M, right: M, top: TOP, bottom: FOOT },
      styles      : { font: 'helvetica', fontSize: 8, cellPadding: 1.8, lineColor: rgb( C.grid ), lineWidth: 0.15, textColor: rgb( C.ink ) },
      headStyles  : { fillColor: rgb( C.accent ), textColor: [ 255, 255, 255 ], fontStyle: 'bold', fontSize: 8 },
      alternateRowStyles : { fillColor: [ 249, 250, 251 ] },
      footStyles  : { fillColor: rgb( C.band ), textColor: rgb( C.ink ), fontStyle: 'bold', fontSize: 8 }
    }, extra || {} );
  }

  function buildPdf() {
    var imgs = buildChartImages();
    var doc  = new window.jspdf.jsPDF( { unit: 'mm', format: 'a4', orientation: 'portrait' } );
    var t    = D.totalen;
    var y;

    /* De hoogte van de tabellen hangt af van de data, dus alles onder de kop
       stroomt: past een blok niet meer, dan begint het op een nieuwe pagina. */
    var kopregel = 'Prestatieoverzicht fotokast';
    var gestempeld = {};
    stampHeader = function ( d ) {
      var p = ( d || doc ).getCurrentPageInfo().pageNumber;
      if ( gestempeld[ p ] ) { return; }
      gestempeld[ p ] = true;
      pageHeader( d || doc, kopregel );
    };
    function nieuwePagina() {
      doc.addPage();
      stampHeader( doc );
      return TOP;
    }
    function ruimte( yNu, nodig ) {
      if ( yNu + nodig <= PAGE_H - FOOT ) { return yNu; }
      return nieuwePagina();
    }

    /* de tekstregels van 'in het kort', met een bolletje ervoor */
    function kortBlok( yNu ) {
      yNu = ruimte( yNu, 14 + D.in_het_kort.length * 6 );
      yNu = sectionTitle( doc, yNu, 'In het kort' ) + 7;
      doc.setFont( 'helvetica', 'normal' ).setFontSize( 9.5 );
      D.in_het_kort.forEach( function ( regel ) {
        var regels = doc.splitTextToSize( regel, CW - 7 );
        yNu = ruimte( yNu, regels.length * 4.6 + 2 );
        setFill( doc, C.accent );
        doc.circle( M + 1.3, yNu - 1.3, 0.85, 'F' );
        setText( doc, C.ink );
        doc.text( regels, M + 5, yNu );
        yNu += regels.length * 4.6 + 1.8;
      } );
      return yNu;
    }

    /* een grafiek met kop, eventueel onderschrift en eventueel een zin eronder */
    function grafiekBlok( yNu, grafiek, titel, sub, zin ) {
      var nodig = ( sub ? 10.6 : 6 ) + 4 + grafiek.h + ( zin ? 9 : 2 );
      yNu = ruimte( yNu, nodig );
      yNu = sectionTitle( doc, yNu, titel, sub );
      doc.addImage( grafiek.data, 'JPEG', M, yNu + 4, CW, grafiek.h );
      yNu = yNu + 4 + grafiek.h;
      if ( zin ) {
        doc.setFont( 'helvetica', 'normal' ).setFontSize( 9 );
        setText( doc, C.ink2 );
        doc.text( doc.splitTextToSize( zin, CW ), M, yNu + 5 );
        yNu += 9;
      }
      return yNu + 12;
    }

    /* ---- pagina 1: wie, wat, afspraken, omzet ---- */
    stampHeader( doc );

    doc.setFont( 'helvetica', 'bold' ).setFontSize( 22 );
    setText( doc, C.ink );
    var naamRegels = doc.splitTextToSize( D.venue, CW );
    doc.text( naamRegels, M, TOP );
    y = TOP + ( naamRegels.length - 1 ) * 8.5;

    doc.setFont( 'helvetica', 'normal' ).setFontSize( 10 );
    setText( doc, C.ink2 );
    doc.text( D.periode + '  ·  ' + D.maanden + ' maanden' + ( D.kast ? '  ·  ' + D.kast : '' ), M, y + 7 );

    y = y + 14;
    var bw = ( CW - 3 * 4 ) / 4, bh = 23;
    kpiBox( doc, M,                  y, bw, bh, 'TOTALE OMZET INCL. BTW', eur( t.omzet ),     'gem. ' + eur( t.gem_maand ) + ' per maand', true );
    kpiBox( doc, M + ( bw + 4 ),     y, bw, bh, 'AANTAL PRINTS',          num( t.prints ),    num( t.qr ) + ' QR · ' + num( t.gratis ) + ' gratis', false );
    kpiBox( doc, M + 2 * ( bw + 4 ), y, bw, bh, 'ACTIEVE DAGEN',          num( t.dagen ),     'gem. ' + eur( t.gem_dag ) + ' per dag', false );
    kpiBox( doc, M + 3 * ( bw + 4 ), y, bw, bh, 'UITKERING VENUE EX. BTW', eur( t.uitkering ), 'volgens de afspraken', false );

    y = ruimte( y + bh + 12, 22 );
    y = sectionTitle( doc, y, 'Afspraken' );

    doc.autoTable( tableStyle( {
      startY : y + 4,
      head   : [ [ 'Periode', 'Percentage venue', 'Verhuurprijs per avond' ] ],
      body   : D.afspraken.length
                 ? D.afspraken.map( function ( a ) {
                     return [ a.periode, Math.round( a.percentage * 100 ) + ' %', eur( a.verhuur ) ];
                   } )
                 : [ [ 'Er is voor deze fotokast geen afspraak vastgelegd.', '', '' ] ],
      columnStyles     : { 1: { halign: 'right' }, 2: { halign: 'right' } },
      tableWidth       : 130,
      markeer          : function ( i ) { return D.afspraken[ i ] && D.afspraken[ i ].in_periode; }
    } ) );

    y = doc.lastAutoTable.finalY;
    var noten = [];
    if ( D.markeer_afspraken )       { noten.push( 'De gemarkeerde regels gelden in de getoonde periode.' ); }
    if ( D.maanden_zonder_afspraak ) { noten.push( 'Voor ' + D.maanden_zonder_afspraak + ' van de ' + D.maanden +
                                                   ' getoonde maanden is geen afspraak vastgelegd; die maanden tellen met 0 % mee in de uitkering.' ); }
    if ( noten.length ) {
      doc.setFont( 'helvetica', 'normal' ).setFontSize( 7.5 );
      setText( doc, C.ink3 );
      var notenRegels = doc.splitTextToSize( noten.join( ' ' ), CW );
      doc.text( notenRegels, M, y + 4.5 );
      y = y + 1.5 + notenRegels.length * 3.2;
    }

    kortBlok( y + 12 );

    /* ---- pagina 2: hoe draait het per maand ---- */
    y = nieuwePagina();
    y = grafiekBlok( y, imgs.omzet, 'Omzet per maand',
                     'Bedragen inclusief btw. Alle cijfers staan in de tabel achterin.' );
    y = grafiekBlok( y, imgs.prints, 'Aantal prints per maand' );

    /* ---- pagina 3: wanneer wordt de kast gebruikt ---- */
    y = nieuwePagina();

    var zinDag = null;
    if ( D.beste_dag && D.beste_dag.omzet > 0 ) {
      zinDag = 'Beste dag: ' + D.beste_dag.dag + ' - ' + eur( D.beste_dag.omzet ) + ' (' + pct( D.beste_dag.aandeel ) +
               ' van de omzet), gemiddeld ' + eur( D.beste_dag.gem ) + ' per ' + D.beste_dag.dag + '.';
    }
    var zinUur = null;
    if ( D.beste_uur && D.beste_uur.omzet > 0 ) {
      zinUur = 'Drukste tijdvak: ' + D.beste_uur.tijdvak + ' - ' + eur( D.beste_uur.omzet ) +
               ' (' + pct( D.beste_uur.aandeel ) + ' van de omzet).';
    }

    y = grafiekBlok( y, imgs.week, 'Welke dag van de week is het drukst?',
                     'Een avond telt bij de dag waarop hij begon: de nacht na zaterdag telt mee als zaterdag.', zinDag );
    y = grafiekBlok( y, imgs.uur, 'Op welk tijdstip wordt de fotokast gebruikt?',
                     "De as loopt van 08:00 's ochtends tot 08:00 de volgende ochtend.", zinUur );

    /* ---- de cijfers ---- */
    nieuwePagina();

    y = sectionTitle( doc, TOP, 'Cijfers per maand' );
    doc.autoTable( tableStyle( {
      startY : y + 4,
      head   : [ [ 'Maand', 'Dagen', 'Prints', 'QR', 'Gratis', 'Omzet incl. btw', 'Omzet excl. btw', '% venue', 'Uitkering venue' ] ],
      body   : D.rows.map( function ( r ) {
        return [
          r.label_lang + ( r.lopend ? ' (loopt nog)' : '' ),
          num( r.dagen ), num( r.prints ), num( r.qr ), num( r.gratis ),
          eur( r.omzet ), eur( r.omzet_ex ),
          r.afspraak ? Math.round( r.percentage * 100 ) + ' %' : '-',
          eur( r.uitkering )
        ];
      } ),
      foot : [ [ 'Totaal', num( t.dagen ), num( t.prints ), num( t.qr ), num( t.gratis ),
                 eur( t.omzet ), eur( t.omzet_ex ), '', eur( t.uitkering ) ] ],
      columnStyles : { 0: { cellWidth: 30 }, 1: { halign: 'right' }, 2: { halign: 'right' }, 3: { halign: 'right' },
                       4: { halign: 'right' }, 5: { halign: 'right' }, 6: { halign: 'right' },
                       7: { halign: 'right' }, 8: { halign: 'right' } },
      styles : { font: 'helvetica', fontSize: 7.4, cellPadding: 1.6, lineColor: rgb( C.grid ), lineWidth: 0.15, textColor: rgb( C.ink ) }
    } ) );

    /* een tabel liever in zijn geheel op de volgende pagina dan halverwege afgekapt */
    function tabelHoogte( rijen ) { return Math.min( 14 + rijen * 6.6, PAGE_H - TOP - FOOT ); }

    y = ruimte( doc.lastAutoTable.finalY + 12, 8 + tabelHoogte( 7 ) );
    y = sectionTitle( doc, y, 'Cijfers per dag van de week' );
    doc.autoTable( tableStyle( {
      startY : y + 4,
      head   : [ [ 'Dag', 'Actieve dagen', 'Prints', 'Omzet incl. btw', 'Gemiddeld per dag', 'Aandeel' ] ],
      body   : D.week.map( function ( w ) {
        return [ w.dag.charAt( 0 ).toUpperCase() + w.dag.slice( 1 ), num( w.dagen ), num( w.prints ), eur( w.omzet ), eur( w.gem ), pct( w.aandeel ) ];
      } ),
      columnStyles : { 1: { halign: 'right' }, 2: { halign: 'right' }, 3: { halign: 'right' }, 4: { halign: 'right' }, 5: { halign: 'right' } },
      tableWidth   : 150
    } ) );

    y = doc.lastAutoTable.finalY + 12;
    var uurRijen = D.uren.filter( function ( u ) { return u.aantal > 0 || u.omzet > 0; } );
    if ( uurRijen.length ) {
      y = ruimte( y, 8 + tabelHoogte( uurRijen.length ) );
      y = sectionTitle( doc, y, 'Cijfers per tijdstip' );
      doc.autoTable( tableStyle( {
        startY : y + 4,
        head   : [ [ 'Tijdvak', 'Prints', 'Transacties', 'Omzet incl. btw', 'Aandeel' ] ],
        body   : uurRijen.map( function ( u ) { return [ u.tijdvak, num( u.prints ), num( u.aantal ), eur( u.omzet ), pct( u.aandeel ) ]; } ),
        columnStyles : { 1: { halign: 'right' }, 2: { halign: 'right' }, 3: { halign: 'right' }, 4: { halign: 'right' } },
        tableWidth   : 130
      } ) );
      y = doc.lastAutoTable.finalY;
    }

    y = ruimte( y, 16 );
    doc.setFont( 'helvetica', 'normal' ).setFontSize( 7.5 );
    setText( doc, C.ink3 );
    doc.text( doc.splitTextToSize(
      'Uitkering venue = het afgesproken percentage over de omzet exclusief btw. De verhuurprijs per avond wordt apart ' +
      'verrekend en zit niet in deze bedragen. Gemaakt op ' + D.gemaakt_op + '.', CW ), M, y + 7 );

    pageFooters( doc );

    var naam = 'prestatieoverzicht_' + D.venue.toLowerCase().replace( /[^a-z0-9]+/g, '_' ).replace( /^_|_$/g, '' ) +
               '_' + D.maanden + 'mnd_' + D.gemaakt_op.split( '-' ).reverse().join( '' ) + '.pdf';
    doc.save( naam );
  }

  var btn = document.getElementById( 'btn_pdf' );
  btn.addEventListener( 'click', function () {
    var oud = btn.innerHTML;
    btn.disabled  = true;
    btn.innerHTML = 'PDF maken...';
    setTimeout( function () {
      try {
        buildPdf();
      } catch ( e ) {
        alert( 'Het maken van de PDF is niet gelukt: ' + e.message );
        if ( window.console ) { console.error( e ); }
      }
      btn.disabled  = false;
      btn.innerHTML = oud;
    }, 30 );
  } );
})();
</script>

<?php include("partial_footer.php"); ?>
