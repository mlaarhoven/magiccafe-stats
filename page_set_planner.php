  <?php
  error_reporting (E_ALL ^ E_NOTICE);
  include ("partial_header.php");
  include ("partial_nav.php");
  include ("inc/invul_functions.php");

  $event_id = $_SESSION['event_id'];
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }

  $mode = ""; //init
  $year_aangepast = 0 ; //init
  $set_planning_opgeslagen = "";
  if ( isset($_REQUEST['mode']) ) {
    $mode = mysqli_real_escape_string( $cn, $_REQUEST['mode']);
  }

  if ( $mode == "save_set_with_date_time" )
    {
    $start_date   = mysqli_real_escape_string( $cn, $_REQUEST['start_date']);
    $end_date     = mysqli_real_escape_string( $cn, $_REQUEST['end_date']);
    $start_time   = mysqli_real_escape_string( $cn, $_REQUEST['start_time']);
    $end_time     = mysqli_real_escape_string( $cn, $_REQUEST['end_time']);
    $set_id       = mysqli_real_escape_string( $cn, $_REQUEST['set_id']);
    $base_set_id  = get_base_set_id();

    $start_date_time = $start_date . " " . $start_time;
    $end_date_time   = $end_date . " " . $end_time;

    if ( strtotime( $start_date_time ) < strtotime ( $end_date_time ) )
      {
        $sql = "REPLACE INTO photobooth_set_planner
                        SET event_id        = '" . $event_id        . "',
                            start_date      = '" . $start_date      . "',
                            end_date        = '" . $end_date        . "',
                            start_time      = '" . $start_time      . "',
                            end_time        = '" . $end_time        . "',
                            start_date_time = '" . $start_date_time . "',
                            end_date_time   = '" . $end_date_time   . "',
                            set_id          = '" . $set_id          . "'";
        $result = mysqli_query( $cn , $sql );
        if ( $result ) { $set_planning_opgeslagen = 1; } else {
          echo "Error mysql " . $sql ." " . mysqli_error(( $cn));
        }
        $sql2 = "DELETE FROM photobooth_set_planner
                      WHERE event_id = '" . $event_id  . "' AND
                            set_id   = '" . $base_set_id . "'";
        mysqli_query( $cn , $sql2 );
      }
    else
      {
      $set_planning_opgeslagen = "FOUTMELDING: de eind datum en tijd moet na de start datum en tijd liggen!";
      }
    }

  if ( $mode == "save_year" )
    {
    $year = mysqli_real_escape_string( $cn, $_REQUEST['year']);
    $_SESSION['year'] = $year;
    $year_aangepast = 1;
    }

  if ( $mode == "save_month_year" )
    {
    $_SESSION['month'] = (int)($_REQUEST['month'] ?? 1);
    $_SESSION['year']  = (int)($_REQUEST['year']  ?? date('Y'));
    }

  if ( $_SESSION['year'] == "" )
    {
    $year = date("Y");
    $_SESSION['year'] = $year;
    }
  else
    {
    $year = $_SESSION['year'];
    }

  if ( $mode == "save_month" )
    {
    $month = mysqli_real_escape_string( $cn, $_REQUEST['month']);
    $_SESSION['month'] = $month;
    $month_aangepast = 1;
    }

  if ( $_SESSION['month'] == "" )
    {
    $month = date("m");
    $_SESSION['month'] = $month;
    }
  else
    {
    $month = $_SESSION['month'];
    }

  if ( isLeapYear ( $year ) == true )
    {
    $daysOfMonth = array( 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    }
  else
    {
    $daysOfMonth = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    }

  $dateStart = $year . "-01-01";
  $dateEnd   = $year . "-12-31";

  $array_planner = array();
  $array_color   = array();

  $sql = "SELECT *
            FROM photobooth_set_planner
           WHERE event_id = '" . $_SESSION['event_id']. "'
             AND YEAR(start_date)  = '" . $year  . "'
             AND MONTH(start_date) = '" . $month . "'
           ORDER BY start_date_time ASC";

  $result = mysqli_query( $cn , $sql );
  while ( $return = mysqli_fetch_object ( $result ) )
    {
    $set_id          = $return->set_id;
    $start_date      = $return->start_date;
    $end_date        = $return->end_date;
    $start_time      = $return->start_time;
    $end_time        = $return->end_time;
    $start_date_time = $start_date . " " . $start_time;
    $end_date_time   = $end_date   . " " . $end_time;

    $dates = returnBetweenDates ( $start_date_time, $end_date_time );
    foreach ($dates as $date) {
      $array_planner[$date] = $set_id;
      }
    $color = get_set_color_from_db ( $set_id );
    $array_color[ $set_id ] = $color;
    }

function isLeapYear($year)
    {
    if (($year/4)   != floor($year/4))   return false;
    if (($year/100) != floor($year/100)) return true;
    if (($year/400) != floor($year/400)) return false;
    return true;
    }

function returnBetweenDates( $startDate, $endDate )
  {
    $startStamp = strtotime( $startDate );
    $endStamp   = strtotime( $endDate );
    if ( $endStamp > $startStamp ) {
        while ( $endStamp >= $startStamp ) {
            $dateArr[] = date( 'Y-m-d H:i:s', $startStamp );
            $startStamp = strtotime( '+1 hour', $startStamp );
        }
        return $dateArr;
    } else {
        return $startDate;
    }
  }

function get_set_name ( $set_id )
    {
    global $cn;
    $sql    = "SELECT set_name FROM photobooth_set
                WHERE event_id = '" . $_SESSION['event_id'] . "'
                  AND set_id   = '" . $set_id . "'";
    $result = mysqli_query( $cn , $sql );
    $return = mysqli_fetch_object ( $result );
    return $return->set_name;
    }

function get_set_color_from_db ( $set_id )
    {
    global $cn;
    $set_color = "#f00";
    $sql    = "SELECT set_color FROM photobooth_set
                WHERE event_id = '" . $_SESSION['event_id'] . "'
                  AND set_id   = '" . $set_id . "'";
    $result = mysqli_query( $cn , $sql );
    if ( $result->num_rows > 0 ) {
        $return    = mysqli_fetch_object ( $result );
        $set_color = $return->set_color;
    }
    return $set_color;
    }

function get_base_set_id ()
    {
    global $cn;
    $sql    = "SELECT set_id FROM photobooth_set
                WHERE event_id  = '" . $_SESSION['event_id'] . "'
                  AND is_base_set = '1'";
    $result = mysqli_query( $cn , $sql );
    $return = mysqli_fetch_object ( $result );
    return $return->set_id;
    }
  // Dutch locale name arrays — ISO day index 1=Ma … 7=Zo
  $nl_dag   = ['', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];
  $nl_maand_kort = ['', 'jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];
  $nl_maand_lang = ['', 'Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni', 'Juli', 'Augustus', 'September', 'Oktober', 'November', 'December'];
  ?> <style>
  .row_1 {
    display: block;
    height: 26px;
    border: 0;
    border-bottom: 1px solid #bbb;
    margin: 0;
    padding: 0;
  }
  .row_1:hover { filter: brightness(0.82); cursor: pointer; }
  .table_planner { margin: 0; padding: 0; border: 0; }
  .table_planner td { border: 0; padding: 0; width: 44px; }
  .table_planner td.time-axis-col { width: 32px; vertical-align: top; border-right: 1px solid #ccc; }
  .time-axis-col table td {
    height: 26px;
    width: 30px;
    font-size: 10px;
    color: #555;
    text-align: right;
    padding: 0 4px 0 0;
    vertical-align: middle;
    line-height: 26px;
    border: 0;
  }
  .table_planner td.day-header { font-size: 10px; text-align: center; padding-bottom: 4px; border-left: 1px solid #ccc; }
  .table_planner td.day-col { vertical-align: top; width: 44px; border-left: 1px solid #ccc; }
</style>
    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Set planner</li>
      </ol>
    </div>
  </header>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">
          <div class="row">
            <div class="col-sm-12">
              <div class="card">
                <div class="card-header bg-facebook content-center">
                  <h2 class="text-white text-uppercase">Set planner - <?php echo $year; ?></h2>
                </div>
                <div class="card-body">
                  <p>Als je een set plant, dan wordt de fotokast aangezet op de tijden die je ingeeft bij de set.<br>
                  De powerplanner wordt dan tijdelijk genegeerd.<br>
                  Na de eindtijd van de set neemt de powerplanner het aan- en uitzetten weer over.</p>
                  <p>Als je de fotokast handmatig uit zet, en de set_planner staat aan, dan zal hij binnen 3 minuten weer aan gezet worden. Houd hier rekening mee.</p>

                  <?php if ( $year_aangepast == 1 ) { ?>
                  <div class="alert alert-success" role="alert">
                    Het getoonde jaar is aangepast naar: <?php echo $year; ?>
                  </div>
                  <?php } ?>

                  <?php if ( $set_planning_opgeslagen == 1 ) { ?>
                  <div class="alert alert-success" role="alert">De set is aangepast.</div>
                  <?php } elseif ( $set_planning_opgeslagen != "" ) { ?>
                  <div class="alert alert-warning" role="alert"><?php echo $set_planning_opgeslagen; ?></div>
                  <?php } ?>

                  Kies een ander jaar:
                  <form action="page_set_planner.php" method="post">
                    <input type="hidden" name="mode" value="save_year">
                    <select name="year" onchange="this.form.submit()">
                      <?php for ( $y = 2023; $y < 2060; $y++ ) { ?>
                      <option value="<?= $y ?>"<?= ($y == $year) ? ' selected' : '' ?>><?= $y ?></option>
                      <?php } ?>
                    </select>
                  </form>

                  <br>
                  <?php
                    $prev_m = (int)$month - 1; $prev_y = (int)$year;
                    if ($prev_m < 1)  { $prev_m = 12; $prev_y--; }
                    $next_m = (int)$month + 1; $next_y = (int)$year;
                    if ($next_m > 12) { $next_m = 1;  $next_y++; }
                  ?>
                  Navigeer per maand:
                  <div class="d-flex align-items-center mt-1 mb-2" style="gap:6px;flex-wrap:wrap">
                    <form action="page_set_planner.php" method="post" style="margin:0">
                      <input type="hidden" name="mode"  value="save_month_year">
                      <input type="hidden" name="month" value="<?= $prev_m ?>">
                      <input type="hidden" name="year"  value="<?= $prev_y ?>">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">&#8249; vorige</button>
                    </form>
                    <form action="page_set_planner.php" method="post" style="margin:0">
                      <input type="hidden" name="mode" value="save_month">
                      <select name="month" onchange="this.form.submit()" class="form-control form-control-sm" style="width:auto">
                        <?php for ($m = 1; $m <= 12; $m++) { ?>
                        <option value="<?= $m ?>"<?= ((int)$m === (int)$month) ? ' selected' : '' ?>>
                          <?= sprintf('%02d', $m) ?> &ndash; <?= $nl_maand_lang[$m] ?>
                        </option>
                        <?php } ?>
                      </select>
                    </form>
                    <form action="page_set_planner.php" method="post" style="margin:0">
                      <input type="hidden" name="mode"  value="save_month_year">
                      <input type="hidden" name="month" value="<?= $next_m ?>">
                      <input type="hidden" name="year"  value="<?= $next_y ?>">
                      <button type="submit" class="btn btn-sm btn-outline-secondary">volgende &#8250;</button>
                    </form>
                  </div>
                </div>

                <div class="card-body" style="overflow-x:auto">
                <?php
                  $maand = (int)$month - 1;
                  if ($maand < 9) { $dateStart = $year . "-0" . ($maand+1) . "-01"; }
                  else            { $dateStart = $year . "-"  . ($maand+1) . "-01"; }
                  if ($maand < 9) { $dateEnd = $year . "-0" . ($maand+1) . "-" . $daysOfMonth[$maand]; }
                  else            { $dateEnd = $year . "-"  . ($maand+1) . "-" . $daysOfMonth[$maand]; }

                  $base_set_id    = get_base_set_id();
                  $base_set_color = get_set_color_from_db($base_set_id);
                ?>
                <table class="table_planner">
                  <!-- Day header row -->
                  <tr>
                    <td class="time-axis-col"></td>
                    <?php
                      $current_date = $dateStart;
                      while (strtotime($current_date) <= strtotime($dateEnd)) {
                        $ts = strtotime($current_date);
                        echo '<td class="day-header">'
                           . $nl_dag[(int)date('N', $ts)] . '<br>'
                           . date('d', $ts) . ' ' . $nl_maand_kort[(int)date('n', $ts)]
                           . '</td>';
                        $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));
                      }
                    ?>
                  </tr>
                  <!-- Hour rows -->
                  <tr>
                    <td class="time-axis-col">
                      <table>
                        <?php for ($h = 0; $h < 24; $h++) { ?>
                        <tr><td><?= sprintf('%02d', $h) ?></td></tr>
                        <?php } ?>
                      </table>
                    </td>
                    <?php
                      $current_date = $dateStart;
                      while (strtotime($current_date) <= strtotime($dateEnd)) {
                    ?>
                    <td class="day-col">
                      <table class="table_planner" style="width:100%">
                        <?php for ($hour = 0; $hour < 24; $hour++):
                          $h  = sprintf('%02d', $hour);
                          $dt = $current_date . ' ' . $h . ':00:00';
                          $is_now = ($dt === date('Y-m-d H:00:00'));
                          $sid = '';
                          if (isset($array_planner[$dt])) {
                            $sid = $array_planner[$dt];
                          } else {
                            $half = $current_date . ' ' . $h . ':30:00';
                            if (isset($array_planner[$half])) $sid = $array_planner[$half];
                          }
                          $bg      = ($sid !== '') ? $array_color[$sid] : $base_set_color;
                          $outline = $is_now ? 'outline:2px solid #000;outline-offset:-2px;' : '';
                          $ts_day  = strtotime($current_date);
                          $tip     = htmlspecialchars($nl_dag[(int)date('N', $ts_day)] . ' ' . date('d', $ts_day) . ' ' . $nl_maand_kort[(int)date('n', $ts_day)] . ' · ' . $h . ':00 — klik om set te plannen');
                        ?>
                        <tr class="row_1"
                            style="background-color:<?= $bg ?>;<?= $outline ?>"
                            title="<?= $tip ?>"
                            onClick="window.location='page_set_choose.php?date_time=<?= $dt ?>'">
                          <td></td>
                        </tr>
                        <?php endfor; ?>
                      </table>
                    </td>
                    <?php
                        $current_date = date('Y-m-d', strtotime('+1 day', strtotime($current_date)));
                      } // end days
                    ?>
                  </tr>
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
<?php include("partial_footer.php"); ?>
