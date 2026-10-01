<?php
# ---------------------------------------------------------------------------
#  page_invoice_all.php — maandafrekening van alle kasten op één pagina. Admin only.
#
#  Toont per kast de sets van de gekozen maand (zelfde bron en kolommen als
#  page_invoice_overview.php), met een vinkje per mogelijke verhuurregel (prijs = 0).
#  Een verhuurregel die nog niet gefactureerd én niet aangevinkt is staat rood/vet:
#  die moet nog bekeken worden. Het vinkje wordt direct opgeslagen via save_rental.php
#  (zelfde tabel photobooth_sequence_rental als de pagina per kast).
#
#  "Exporteer alles naar Facturatiesysteem" roept per aangevinkte kast
#  invoice_export_month() aan (inc/invoice_export_functions.php) — exact dezelfde
#  export als de knop op page_invoice_overview.php.
#
#  Let op bij wijzigen:
#  - Nogmaals exporteren maakt DUBBELE factuurregels aan (charges worden altijd
#    ge-INSERT). Een kast met al een photobooth_orders-regel voor die maand wordt
#    daarom nooit opnieuw geexporteerd; dat wordt ook server-side gecontroleerd.
#  - Kasten in invoice_excluded_event_ids() (test event, flora, 115) krijgen nooit
#    een creditfactuur en staan dus niet in de lijst.
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
include ('../inc/connection_invoicesystem.inc.php'); // connectie met het facturatiesysteem ($cn_info)
include ("inc/invoice_export_functions.php");

# exporteren mag alleen client 1, net als de knop op page_invoice_overview.php
$mag_exporteren = ( $_SESSION['client_id'] == 1 );

# --- jaar + maand: standaard de vorige maand (je factureert aan het begin van de nieuwe) --- #
$vorige = strtotime( date('Y-m-01') . ' -1 month' );
$year   = isset( $_REQUEST['year'] )  ? (int)$_REQUEST['year']  : (int)date( 'Y', $vorige );
$month  = isset( $_REQUEST['month'] ) ? (int)$_REQUEST['month'] : (int)date( 'n', $vorige );
if ( $year < 2023 || $year > 2060 ) { $year  = (int)date( 'Y', $vorige ); }
if ( $month < 1  || $month > 12 )   { $month = (int)date( 'n', $vorige ); }

$peildatum = sprintf( '%04d-%02d-01', $year, $month );
$excluded  = implode( ',', array_map( 'intval', invoice_excluded_event_ids() ) );

function eur( $bedrag ) {
  return '&euro; ' . number_format( $bedrag, 2, ',', '.' );
}

# --- gegevens ophalen: alles in een paar gegroepeerde queries --- #
function laad_kasten( $peildatum, $excluded, $year, $month ) {
  global $cn;
  $kasten = array();

  # kasten met een prijsafspraak in deze maand
  $sql = "SELECT p.event_id, p.percentage_bar, p.rental_price,
                 e.event_name, c.client_name, c.customer_id
            FROM photobooth_percentage_pricing p
       LEFT JOIN event  e ON e.event_id  = p.event_id
       LEFT JOIN client c ON c.client_id = e.client_id
           WHERE '" . $peildatum . "' BETWEEN p.start_date AND p.end_date
             AND p.event_id > 0
             AND p.event_id NOT IN (" . $excluded . ")
        ORDER BY e.event_name";
  $result = mysqli_query( $cn, $sql );
  while ( $r = mysqli_fetch_object( $result ) ) {
    if ( isset( $kasten[ $r->event_id ] ) ) { continue; } # eerste afspraak telt, net als per kast
    $kasten[ $r->event_id ] = array(
      'event_id'       => (int)$r->event_id,
      'naam'           => $r->event_name ? $r->event_name : 'event ' . $r->event_id,
      'klant'          => $r->client_name,
      'customer_id'    => (int)$r->customer_id,
      'percentage_bar' => (float)$r->percentage_bar,
      'rental_price'   => (float)$r->rental_price,
      'sets'           => array(),
      'payter_aantal'  => 0,
      'payter_bedrag'  => 0,
      'heeft_payter'   => false,
      'geexporteerd'   => false,
    );
  }
  if ( count( $kasten ) == 0 ) { return $kasten; }
  $ids = implode( ',', array_keys( $kasten ) );

  # werk de log_session tabel bij met de laatste logs (1x voor alle kasten)
  mysqli_query( $cn, 'CALL FillSequence()' );

  # verhuur-vinkjes en al gefactureerde set_sequences
  $verhuur = array();
  $result  = mysqli_query( $cn, "SELECT event_id, set_sequence FROM photobooth_sequence_rental WHERE event_id IN ($ids)" );
  while ( $r = mysqli_fetch_object( $result ) ) { $verhuur[ $r->event_id ][ $r->set_sequence ] = true; }

  $gefactureerd = array();
  $result = mysqli_query( $cn, "SELECT DISTINCT event_id, set_sequence FROM photobooth_charge WHERE event_id IN ($ids)" );
  while ( $r = mysqli_fetch_object( $result ) ) { $gefactureerd[ $r->event_id ][ $r->set_sequence ] = true; }

  # sets van deze maand (zelfde query als page_invoice_overview.php, maar voor alle kasten)
  $sql = 'SELECT event_id,
                 set_sequence,
                 set_id,
                 set_name,
                 sessie_soort,
                 prijs,
                 MIN(sessie_timestamp) AS van,
                 MAX(sessie_timestamp) AS tot,
                 SUM(prijs) AS totaal_log,
                 COUNT(*) AS aantal
            FROM log_sessionOK
           WHERE event_id IN (' . $ids . ')
             AND YEAR(sessie_timestamp)  = ' . $year . '
             AND MONTH(sessie_timestamp) = ' . $month . '
        GROUP BY event_id, set_sequence
        ORDER BY event_id, van';
  $result = mysqli_query( $cn, $sql );
  while ( $r = mysqli_fetch_object( $result ) ) {
    $kasten[ $r->event_id ]['sets'][] = array(
      'set_sequence' => (int)$r->set_sequence,
      'set_id'       => $r->set_id,
      'set_name'     => $r->set_name,
      'soort'        => $r->sessie_soort,
      'prijs'        => (float)$r->prijs,
      'van'          => $r->van,
      'tot'          => $r->tot,
      'bedrag'       => (float)$r->totaal_log,
      'aantal'       => (int)$r->aantal,
      'verhuur'      => isset( $verhuur[ $r->event_id ][ $r->set_sequence ] ),
      'gefactureerd' => isset( $gefactureerd[ $r->event_id ][ $r->set_sequence ] ),
    );
  }

  # controle Payter (zelfde bron als de controletabel op page_invoice_overview.php)
  $sql = 'SELECT event_id, COUNT(transactionId) AS aantal, SUM(committedAmount)/100 AS bedrag
            FROM payterOK
           WHERE event_id IN (' . $ids . ')
             AND YEAR(txnTimestampLocal)  = ' . $year . '
             AND MONTH(txnTimestampLocal) = ' . $month . '
        GROUP BY event_id';
  $result = mysqli_query( $cn, $sql );
  while ( $r = mysqli_fetch_object( $result ) ) {
    $kasten[ $r->event_id ]['payter_aantal'] = (int)$r->aantal;
    $kasten[ $r->event_id ]['payter_bedrag'] = (float)$r->bedrag;
    $kasten[ $r->event_id ]['heeft_payter']  = true;
  }

  # al geexporteerd?
  $result = mysqli_query( $cn, "SELECT event_id FROM photobooth_orders WHERE event_id IN ($ids) AND order_maand = $month AND order_jaar = $year" );
  while ( $r = mysqli_fetch_object( $result ) ) { $kasten[ $r->event_id ]['geexporteerd'] = true; }

  # afgeleide totalen + status per kast
  foreach ( $kasten as $id => $k ) {
    $t = array( 'aantal' => 0, 'bedrag' => 0, 'aantal_verhuur' => 0, 'verhuur' => 0, 'te_bekijken' => 0, 'nieuw_na_export' => 0 );
    foreach ( $k['sets'] as $s ) {
      if ( $s['prijs'] > 0 ) {
        $t['aantal'] += $s['aantal'];
        $t['bedrag'] += $s['bedrag'];
      } else if ( $s['verhuur'] ) {
        $t['aantal_verhuur'] += $s['aantal'];
        $t['verhuur']        -= $k['rental_price'];
      }
      if ( $s['prijs'] == 0 && !$s['gefactureerd'] && !$s['verhuur'] ) { $t['te_bekijken']++; }
      if ( !$s['gefactureerd'] ) { $t['nieuw_na_export']++; }
    }
    if ( !$k['geexporteerd'] ) { $t['nieuw_na_export'] = 0; }

    if      ( $k['geexporteerd'] )      { $status = 'geexporteerd'; }
    else if ( $k['customer_id'] <= 0 )  { $status = 'geen_klant'; }
    else if ( count( $k['sets'] ) == 0 ) { $status = 'geen_sessies'; }
    else                                 { $status = 'klaar'; }

    $kasten[ $id ]['totaal'] = $t;
    $kasten[ $id ]['status'] = $status;
  }
  return $kasten;
}

# --- exporteren --- #
$export_resultaat = array();
if ( isset( $_POST['mode'] ) && $_POST['mode'] == 'export_all' && $mag_exporteren ) {
  $gekozen = isset( $_POST['export_event'] ) ? array_map( 'intval', (array)$_POST['export_event'] ) : array();
  # status opnieuw bepalen op de server: de pagina kan verouderd zijn of dubbel verstuurd
  $kasten_nu = laad_kasten( $peildatum, $excluded, $year, $month );
  foreach ( array_unique( $gekozen ) as $eid ) {
    if ( !isset( $kasten_nu[ $eid ] ) ) {
      $export_resultaat[] = array( 'naam' => "event $eid", 'ok' => false, 'regels' => array( 'Geen prijsafspraak voor deze maand of uitgesloten van facturatie, overgeslagen.' ) );
      continue;
    }
    $k = $kasten_nu[ $eid ];
    if ( $k['status'] != 'klaar' || invoice_export_status( $eid, $month, $year ) ) {
      $reden = array( 'geexporteerd' => 'Al geexporteerd, overgeslagen (nogmaals exporteren geeft dubbele factuurregels).',
                      'geen_klant'   => 'Geen customer_id bij de klant, overgeslagen.',
                      'geen_sessies' => 'Geen sessies in deze maand, overgeslagen.',
                      'klaar'        => 'Al geexporteerd, overgeslagen.' );
      $export_resultaat[] = array( 'naam' => $k['naam'], 'ok' => false, 'regels' => array( $reden[ $k['status'] ] ) );
      continue;
    }
    $export_resultaat[] = array( 'naam' => $k['naam'], 'ok' => true, 'regels' => invoice_export_month( $eid, $month, $year ) );
  }
}

$kasten = laad_kasten( $peildatum, $excluded, $year, $month );
$aantal_klaar = 0;
foreach ( $kasten as $k ) { if ( $k['status'] == 'klaar' ) { $aantal_klaar++; } }

$labels     = array( 'geexporteerd' => 'Al geëxporteerd', 'geen_klant' => 'Geen customer_id', 'geen_sessies' => 'Geen sessies', 'klaar' => 'Nog niet geëxporteerd' );
$maandnamen = array( 1 => 'januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december' );
?>

<style>
  .verhuur-open td { color: #e55353; font-weight: 700; }
  .verhuur-ja td   { background: #e9f7ef; }
  .kast-card { scroll-margin-top: 70px; }
  .status-badge { padding: 3px 9px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; color: #fff; white-space: nowrap; }
  .status-geexporteerd { background: #2eb85c; }
  .status-geen_klant   { background: #e55353; }
  .status-geen_sessies { background: #9da5b1; }
  .status-klaar        { background: #f9b115; }
  .te-bekijken { color: #e55353; font-weight: 700; }
  .export-balk { position: sticky; bottom: 0; z-index: 10; background: #fff; border-top: 2px solid #3b5998; padding: 12px 16px; box-shadow: 0 -2px 6px rgba(0,0,0,.08); }
  .table-sm td { vertical-align: middle; }
</style>

    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item active">Maandafrekening alle kasten: <?php echo $maandnamen[ $month ] . ' ' . $year; ?></li>
      </ol>
    </div>
  </header>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">

          <div class="card">
            <div class="card-header bg-facebook content-center">
              <h2 class="text-white text-uppercase">Maandafrekening alle kasten - <?php echo $maandnamen[ $month ] . ' ' . $year; ?></h2>
            </div>
            <div class="card-body">
              <a href="page_invoice_overview.php" class="btn btn-success">Maandafrekening per kast</a><br><br>

              <form method="get" action="page_invoice_all.php" class="form-inline mb-3">
                <label class="mr-2">Jaar</label>
                <select name="year" class="form-control form-control-sm mr-3" onchange="this.form.submit()">
                  <?php for ( $y = 2023; $y <= (int)date('Y') + 1; $y++ ) { ?>
                    <option value="<?php echo $y; ?>"<?php if ( $y == $year ) { echo ' selected'; } ?>><?php echo $y; ?></option>
                  <?php } ?>
                </select>
                <label class="mr-2">Maand</label>
                <select name="month" class="form-control form-control-sm" onchange="this.form.submit()">
                  <?php for ( $m = 1; $m <= 12; $m++ ) { ?>
                    <option value="<?php echo $m; ?>"<?php if ( $m == $month ) { echo ' selected'; } ?>><?php echo $m . ' - ' . $maandnamen[ $m ]; ?></option>
                  <?php } ?>
                </select>
              </form>

              <?php if ( count( $export_resultaat ) > 0 ) { ?>
                <div class="alert alert-info">
                  <strong>Resultaat export</strong>
                  <?php foreach ( $export_resultaat as $res ) { ?>
                    <div class="mt-2">
                      <strong style="color:<?php echo $res['ok'] ? '#2eb85c' : '#e55353'; ?>"><?php echo htmlspecialchars( $res['naam'] ); ?></strong>
                      <?php if ( !$res['ok'] ) { echo ' — ' . htmlspecialchars( $res['regels'][0] ); } else { ?>
                        <details><summary><?php echo count( $res['regels'] ); ?> regels</summary>
                          <small><?php foreach ( $res['regels'] as $regel ) { echo htmlspecialchars( $regel ) . '<br>'; } ?></small>
                        </details>
                      <?php } ?>
                    </div>
                  <?php } ?>
                </div>
              <?php } ?>

              <p>Bedragen zijn inclusief BTW. Mogelijke verhuur (sets met prijs &euro; 0) die nog niet gefactureerd is en niet is aangevinkt staat
                 <span class="te-bekijken">rood</span>: vink aan als de verhuur in rekening gebracht moet worden, laat leeg als hij gratis was.
                 Het vinkje wordt direct opgeslagen.</p>

              <?php if ( count( $kasten ) == 0 ) { ?>
                <div class="alert alert-warning">Geen kasten met een prijsafspraak in deze maand.</div>
              <?php } else { ?>

              <form method="post" action="page_invoice_all.php" id="export_form">
                <input type="hidden" name="mode"  value="export_all">
                <input type="hidden" name="year"  value="<?php echo $year; ?>">
                <input type="hidden" name="month" value="<?php echo $month; ?>">

                <h3>Overzicht</h3>
                <table class="table table-bordered table-striped table-sm">
                  <thead class="thead-light">
                    <tr>
                      <th>Kast</th>
                      <th class="text-right">sets</th>
                      <th class="text-right">verhuur te bekijken</th>
                      <th class="text-right">opbrengst (log)</th>
                      <th class="text-right">verhuur</th>
                      <th class="text-right">verrekenen bar (IN BTW)</th>
                      <th>status</th>
                      <th class="text-center">exporteren</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php foreach ( $kasten as $k ) {
                    $t = $k['totaal']; ?>
                    <tr>
                      <td><a href="#kast<?php echo $k['event_id']; ?>"><?php echo htmlspecialchars( $k['naam'] ); ?></a> <small class="text-muted">(<?php echo $k['event_id']; ?>)</small></td>
                      <td class="text-right"><?php echo count( $k['sets'] ); ?></td>
                      <td class="text-right" id="tebekijken<?php echo $k['event_id']; ?>"><?php echo $t['te_bekijken'] > 0 ? '<span class="te-bekijken">' . $t['te_bekijken'] . '</span>' : '0'; ?></td>
                      <td class="text-right"><?php echo eur( $t['bedrag'] / 100 ); ?></td>
                      <td class="text-right" id="sumverhuur<?php echo $k['event_id']; ?>"><?php echo eur( $t['verhuur'] ); ?></td>
                      <td class="text-right" id="sumverrekenen<?php echo $k['event_id']; ?>"><strong><?php echo eur( $k['percentage_bar'] * $k['payter_bedrag'] + $t['verhuur'] ); ?></strong></td>
                      <td>
                        <span class="status-badge status-<?php echo $k['status']; ?>"><?php echo $labels[ $k['status'] ]; ?></span>
                        <?php if ( $t['nieuw_na_export'] > 0 ) { ?><br><small class="te-bekijken">let op: <?php echo $t['nieuw_na_export']; ?> set(s) na de export bijgekomen</small><?php } ?>
                      </td>
                      <td class="text-center">
                        <?php if ( $k['status'] == 'klaar' && $mag_exporteren ) { ?>
                          <input type="checkbox" name="export_event[]" value="<?php echo $k['event_id']; ?>" data-naam="<?php echo htmlspecialchars( $k['naam'] ); ?>" class="export-vink" checked>
                        <?php } else { echo '&ndash;'; } ?>
                      </td>
                    </tr>
                  <?php } ?>
                  </tbody>
                </table>
              </form>

              <?php foreach ( $kasten as $k ) {
                $t   = $k['totaal'];
                $eid = $k['event_id']; ?>
                <div class="card kast-card mt-4" id="kast<?php echo $eid; ?>" data-event="<?php echo $eid; ?>" data-rental="<?php echo $k['rental_price']; ?>" data-pct="<?php echo $k['percentage_bar']; ?>" data-payter="<?php echo $k['payter_bedrag']; ?>">
                  <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><?php echo htmlspecialchars( $k['naam'] ); ?> <small class="text-muted">(<?php echo $eid; ?><?php if ( $k['klant'] ) { echo ' &middot; ' . htmlspecialchars( $k['klant'] ); } ?>)</small></h4>
                    <span>
                      <?php echo $k['percentage_bar'] * 100; ?>% bar &middot; verhuur <?php echo eur( $k['rental_price'] ); ?> &nbsp;
                      <span class="status-badge status-<?php echo $k['status']; ?>"><?php echo $labels[ $k['status'] ]; ?></span>
                    </span>
                  </div>
                  <div class="card-body">
                    <?php if ( count( $k['sets'] ) == 0 ) { ?>
                      <p class="text-muted mb-0">Geen sessies in deze maand.</p>
                    <?php } else { ?>
                    <table class="table table-bordered table-sm mb-3">
                      <thead class="thead-light">
                        <tr>
                          <th class="text-right">set_sequence</th>
                          <th class="text-right">set_id</th>
                          <th>set_name</th>
                          <th>van</th>
                          <th>tot</th>
                          <th>soort</th>
                          <th class="text-right">prijs per stuk</th>
                          <th class="text-right">aantal</th>
                          <th class="text-right">opbrengst</th>
                          <th class="text-center">verhuur in rekening?</th>
                          <th class="text-center">gefactureerd?</th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php foreach ( $k['sets'] as $s ) {
                        $is_verhuur_regel = ( $s['prijs'] == 0 );
                        $klasse = '';
                        if ( $is_verhuur_regel && !$s['gefactureerd'] ) { $klasse = $s['verhuur'] ? 'verhuur-ja' : 'verhuur-open'; }
                        ?>
                        <tr class="<?php echo $klasse; ?>">
                          <td class="text-right"><?php echo $s['set_sequence']; ?></td>
                          <td class="text-right"><?php echo htmlspecialchars( $s['set_id'] ); ?></td>
                          <td><?php echo htmlspecialchars( $s['set_name'] ); ?></td>
                          <td><?php echo $s['van']; ?></td>
                          <td><?php echo $s['tot']; ?></td>
                          <td><?php echo htmlspecialchars( $s['soort'] ); ?></td>
                          <td class="text-right"><?php echo eur( $s['prijs'] / 100 ); ?></td>
                          <td class="text-right"><?php echo $s['aantal']; ?></td>
                          <td class="text-right"><?php echo eur( $s['bedrag'] / 100 ); ?></td>
                          <td class="text-center">
                            <?php if ( !$is_verhuur_regel ) { echo '&ndash;'; }
                            else if ( $s['gefactureerd'] ) { ?>
                              <span class="badge rounded-pill <?php echo $s['verhuur'] ? 'btn-success' : 'btn-danger'; ?> verhuur-vast" data-checked="<?php echo $s['verhuur'] ? 1 : 0; ?>"><?php echo $s['verhuur'] ? 'ja' : 'nee'; ?></span>
                            <?php } else { ?>
                              <input type="checkbox" class="verhuur-vink" data-event="<?php echo $eid; ?>" data-seq="<?php echo $s['set_sequence']; ?>"<?php if ( $s['verhuur'] ) { echo ' checked'; } ?>>
                            <?php } ?>
                          </td>
                          <td class="text-center"><?php echo $s['gefactureerd'] ? '<span class="badge rounded-pill btn-success">ja</span>' : '<span class="badge rounded-pill btn-info">nee</span>'; ?></td>
                        </tr>
                      <?php } ?>
                      </tbody>
                    </table>
                    <?php } ?>

                    <table class="table table-sm table-borderless mb-0" style="max-width:520px">
                      <tr><td>Controle Payter</td>
                          <td class="text-right"<?php echo ( $k['heeft_payter'] && ( $t['aantal'] != $k['payter_aantal'] || abs( $t['bedrag'] / 100 - $k['payter_bedrag'] ) > 0.005 ) ) ? ' style="color:#e55353;font-weight:700"' : ''; ?>>
                            <?php echo $k['payter_aantal']; ?> transacties / <?php echo eur( $k['payter_bedrag'] ); ?>
                            <small class="text-muted">(log: <?php echo $t['aantal']; ?> / <?php echo eur( $t['bedrag'] / 100 ); ?>)</small></td></tr>
                      <tr><td>Percentage opbrengst bar</td><td class="text-right"><?php echo eur( $k['percentage_bar'] * $k['payter_bedrag'] ); ?></td></tr>
                      <tr><td>Totaal verhuur</td><td class="text-right" id="verhuur<?php echo $eid; ?>"><?php echo eur( $t['verhuur'] ); ?></td></tr>
                      <tr><td><strong>Verrekenen met bar (IN BTW)</strong></td><td class="text-right" id="verrekenen<?php echo $eid; ?>"><strong><?php echo eur( $k['percentage_bar'] * $k['payter_bedrag'] + $t['verhuur'] ); ?></strong></td></tr>
                      <tr><td><strong>Verrekenen met bar (EX BTW)</strong></td><td class="text-right" id="verrekenenex<?php echo $eid; ?>"><strong><?php echo eur( ( $k['percentage_bar'] * $k['payter_bedrag'] + $t['verhuur'] ) / 1.21 ); ?></strong></td></tr>
                    </table>
                    <small><a href="#">naar boven</a></small>
                  </div>
                </div>
              <?php } ?>

              <?php } ?>

            </div>
          </div>

          <?php if ( $mag_exporteren && count( $kasten ) > 0 ) { ?>
          <div class="export-balk d-flex align-items-center">
            <div class="mr-3 small">
              LET OP: alleen gebruiken als de maand helemaal klaar is. Er worden direct factuurregels aangemaakt in het facturatiesysteem en deze zijn lastig te verwijderen!
            </div>
            <?php if ( $aantal_klaar > 0 ) { ?>
              <button type="button" class="btn btn-info ml-auto" id="export_knop" style="white-space:nowrap">Exporteer alles naar Facturatiesysteem</button>
            <?php } else { ?>
              <button type="button" class="btn btn-secondary ml-auto" disabled style="white-space:nowrap">Alles al geëxporteerd</button>
            <?php } ?>
          </div>
          <?php } ?>

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
  function eur( bedrag ) {
    return '€ ' + bedrag.toLocaleString( 'nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 } );
  }

  // totalen van één kast opnieuw uitrekenen na een vinkje (zonder herladen, scrollpositie blijft)
  function herbereken( eventId ) {
    var card   = document.getElementById( 'kast' + eventId );
    var rental = parseFloat( card.dataset.rental );
    var pct    = parseFloat( card.dataset.pct );
    var payter = parseFloat( card.dataset.payter );
    var aangevinkt = 0, open = 0;
    card.querySelectorAll( '.verhuur-vink' ).forEach( function ( v ) { if ( v.checked ) { aangevinkt++; } else { open++; } } );
    card.querySelectorAll( '.verhuur-vast' ).forEach( function ( v ) { if ( v.dataset.checked === '1' ) { aangevinkt++; } } );
    var verhuur    = -rental * aangevinkt;
    var verrekenen = pct * payter + verhuur;
    document.getElementById( 'verhuur' + eventId ).textContent    = eur( verhuur );
    document.getElementById( 'sumverhuur' + eventId ).textContent = eur( verhuur );
    document.getElementById( 'verrekenen' + eventId ).innerHTML    = '<strong>' + eur( verrekenen ) + '</strong>';
    document.getElementById( 'sumverrekenen' + eventId ).innerHTML = '<strong>' + eur( verrekenen ) + '</strong>';
    document.getElementById( 'verrekenenex' + eventId ).innerHTML  = '<strong>' + eur( verrekenen / 1.21 ) + '</strong>';
    document.getElementById( 'tebekijken' + eventId ).innerHTML    = open > 0 ? '<span class="te-bekijken">' + open + '</span>' : '0';
  }

  document.querySelectorAll( '.verhuur-vink' ).forEach( function ( vink ) {
    vink.addEventListener( 'change', function () {
      var eventId = vink.dataset.event;
      var rij     = vink.closest( 'tr' );
      vink.disabled = true;
      $.post( 'save_rental.php', { rental_boolean: vink.checked ? 'true' : 'false', set_sequence: vink.dataset.seq, event_id: eventId } )
        .done( function () {
          rij.classList.toggle( 'verhuur-ja',   vink.checked );
          rij.classList.toggle( 'verhuur-open', !vink.checked );
          herbereken( eventId );
        } )
        .fail( function () {
          vink.checked = !vink.checked;
          alert( 'Opslaan van het verhuur-vinkje is mislukt, probeer het opnieuw.' );
        } )
        .always( function () { vink.disabled = false; } );
    } );
  } );

  var knop = document.getElementById( 'export_knop' );
  if ( knop ) {
    knop.addEventListener( 'click', function () {
      var namen = [];
      document.querySelectorAll( '.export-vink:checked' ).forEach( function ( v ) { namen.push( '- ' + v.dataset.naam ); } );
      if ( namen.length === 0 ) { alert( 'Er is geen kast aangevinkt om te exporteren.' ); return; }
      var open = 0;
      document.querySelectorAll( '.export-vink:checked' ).forEach( function ( v ) {
        open += document.querySelectorAll( '#kast' + v.value + ' .verhuur-vink:not(:checked)' ).length;
      } );
      var tekst = 'Deze kasten exporteren naar het facturatiesysteem?\n\n' + namen.join( '\n' ) +
                  ( open > 0 ? '\n\nLet op: ' + open + ' verhuurregel(s) staan nog uit en worden dus NIET in rekening gebracht.' : '' ) +
                  '\n\nDe factuurregels zijn lastig te verwijderen.';
      if ( !confirm( tekst ) ) { return; }
      knop.disabled    = true;
      knop.className   = 'btn btn-secondary ml-auto';
      knop.textContent = 'Bezig met exporteren...';
      document.getElementById( 'export_form' ).submit();
    } );
  }
</script>

<?php include("partial_footer.php"); ?>
