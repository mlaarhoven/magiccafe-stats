  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
 // error_reporting(E_ALL & ~E_NOTICE);


  $event_id = $_SESSION['event_id'];
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }
  $year_aangepast = 0; //init
  $month_aangepast = 0; //init
  $mode = ""; //init
  if ( isset ( $_REQUEST['mode'] )) { $mode     = mysqli_real_escape_string( $cn, $_REQUEST['mode']);}
  
 
  
  if ( $mode == "save_year" )
    {
    $year   = mysqli_real_escape_string( $cn, $_REQUEST['year']);
    $_SESSION['year'] = $year;
    $year_aangepast = 1; 
    }
  if ( $mode == "save_month" )
    {
    $month   = mysqli_real_escape_string( $cn, $_REQUEST['month']);
    $_SESSION['month'] = $month;
    $month_aangepast = 1; 
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

  if ( $_SESSION['month'] == "" )
    { 
    $month = date("m");
    $_SESSION['month'] = $month;
    }
  else 
    {
    $month = $_SESSION['month'];
    }   
    
$month_met_nul = "0" . $month;

 if ( substr( $month_met_nul, 0,2) == '00') 
    { 
    $month_met_nul = substr( $month_met_nul, 1,2);
    }   

  if ( $month >= 10 ) 
    { 
    $month_met_nul = $month;
    }   

  
  
  if ( isLeapYear ( $year ) == true ) 
    { 
    // leap year - schrikkeljaar
    $daysOfMonth      = array( 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    }
  else
    {
    $daysOfMonth      = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    } 

 

  $dateStart = $year . "-" . ( $month_met_nul ) . "-01"; 
  $dateEnd   = $year . "-" . ( $month_met_nul ) . "-" . $daysOfMonth[ $month -1 ]; 
  echo "dateStart = " . $dateStart . "<br>";
  function isLeapYear($year)  {
    if (($year/4)  != floor($year/4))  return false;
    if (($year/100) != floor($year/100)) return true;
    if (($year/400) != floor($year/400)) return false;
    return true;
    }
                          
  ?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Omzet per avond</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
    
  </header>
  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">

          <!-- /.row-->
          <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card">
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Omzet per avond</h2>
                    </div>
                  <div class="card-body">
                  <a href="page_invoice_overview.php" class="btn btn-success">Maandafrekening</a>
                  <a href="page_monthly_overview.php" class="btn btn-info">Omzet per maand</a>
                  <a href="page_revenue_per_evening.php" class="btn btn-info">Omzet per werkdag/avond</a>
                  <a href="page_revenue_per_workweek.php" class="btn btn-info">Omzet per werkweek</a>
                  <a href="page_session_overview.php?aantal=40" class="btn btn-warning">Sessie overzicht</a>
                  <a href="page_transaction_overview.php" class="btn btn-warning">Transactie overzicht</a><br><br>
                    <?php
                  if ( $year_aangepast == 1 ) {
                        ?>
                        <div class="alert alert-success" role="alert">
                          Het getoonde jaar is aangepast naar: <?php echo $year; ?>
                        </div>
                        <?php
                      } 

                   if ( $month_aangepast == 1 ) {
                        ?>
                        <div class="alert alert-success" role="alert">
                          De getoonde maand is aangepast naar: <?php echo $month; ?>
                        </div>
                        <?php
                      } 
                    ?>   
              
                    Kies een ander jaar:
                    <form action="page_revenue_per_evening.php" action="post">
                        <input type="hidden" name="mode" value="save_year">
                        
                        <select name="year" onchange="this.form.submit()">
                            <?php 
                            for ( $y = 2023; $y < 2060; $y ++ )
                            {
                              ?><option value="<?php echo $y; ?>"
                              <? if ( $y == $year ) { echo " selected"; } ?>
                              ><?php echo $y; ?></option><?php
                            }
                            ?>
                        </select>
                      </form>
                     Kies een andere maand:
                    <form action="page_revenue_per_evening.php" action="post">
                        <input type="hidden" name="mode" value="save_month">
                        
                        <select name="month" onchange="this.form.submit()">
                        <?php 
                            for ( $m = 1; $m <= 12; $m ++ )
                            {
                              ?><option value="<?php echo $m; ?>"
                              <? if ( $m == $month ) { echo " selected"; } ?>
                              ><?php echo $m; ?></option><?php
                            } 
                            ?>
                        </select>
                      </form> 
                
                      <br>
                      <br>
                  <h3>Aantallen en bedragen per werkdag</h3> 
                  <br>   
                  <table class="table table-responsive-sm table-bordered table-striped table-sm">
                      <thead>
                        <tr>
                       
                        <td align="right"><strong>werkdag</strong></td>
                        <td align="right"><strong>aantal print betaald</strong></td>
                        <td align="right"><strong>aantal print gratis</strong></td>
                        <td align="right"><strong>aantal qr betaald</strong></td>
                        <td align="right"><strong>aantal qr gratis</strong></td>
                        <td align="right"><strong>bedrag print</strong></td>
                        <td align="right"><strong>bedrag qr</strong></td>
                        <td align="right"><strong>bedrag totaal</strong></td>
                        
                      </tr>  
                    </thead>
                    
                      <?php
                      setlocale(LC_MONETARY, "nl_NL");    
                        $sql = "SELECT gratis, sessie_soort, 
                                       event_id, 
                                       werkdag,
                                       COUNT(*)   as aantal, 
                                       SUM(prijs) as totaalbedrag
                                  FROM log_sessionOK
                                  WHERE event_id      = '" . $event_id . "'
                                  AND MONTH(werkdag)  = '" . $month    . "'
                                  AND YEAR(werkdag)   = '" . $year     . "'
                                GROUP BY event_id, gratis, sessie_soort, werkdag  
                                ORDER BY `werkdag` DESC";

                          //echo $sql;
                          $result = mysqli_query( $cn , $sql );
                          while ( $return	= mysqli_fetch_object ( $result ) )
                            {
                              $werkdag                  = $return->werkdag;
                              $gratis                   = $return->gratis;
                              $sessie_soort             = $return->sessie_soort;
                              $aantal_print_betaald     = 0;
                              $aantal_print_gratis      = 0;
                              $aantal_qr_betaald        = 0;
                              $aantal_qr_gratis         = 0;
                              $bedrag_print             = 0;
                              $bedrag_qr                = 0;
                              $bedrag_totaal            = 0;
                              
                              if ( $sessie_soort == 'print' ){
                                if ( $gratis == 0 ) {
                                  $aantal_print_betaald       = $return->aantal;
                                }
                                if ( $gratis == 1 ) {
                                  $aantal_print_gratis       = $return->aantal;
                                }
                                $bedrag_print       = $return->totaalbedrag/100;
                              }

                              if ( $sessie_soort == 'qr' ){
                                if ( $gratis == 0 ) {
                                  $aantal_qr_betaald               = $return->aantal;
                                }
                                if ( $gratis == 1 ) {
                                  $aantal_qr_gratis               = $return->aantal;
                                }
                                $bedrag_qr               = $return->totaalbedrag/100;
                              }

                              $werkdag_array[ $werkdag ][ 'bedrag_print']         += $bedrag_print;
                              $werkdag_array[ $werkdag ][ 'bedrag_qr']            += $bedrag_qr;
                              $werkdag_array[ $werkdag ][ 'bedrag_totaal']        += $bedrag_print;
                              $werkdag_array[ $werkdag ][ 'bedrag_totaal']        += $bedrag_qr;
                              $werkdag_array[ $werkdag ][ 'aantal_print_betaald'] += $aantal_print_betaald;
                              $werkdag_array[ $werkdag ][ 'aantal_print_gratis']  += $aantal_print_gratis;
                              $werkdag_array[ $werkdag ][ 'aantal_qr_betaald']    += $aantal_qr_betaald;
                              $werkdag_array[ $werkdag ][ 'aantal_qr_gratis']     += $aantal_qr_gratis;
                            }

                            $i = 0;
                            $keys = array_keys($werkdag_array);

                            foreach ($werkdag_array as $value) {
                             
                              ?><tr><td  nowrap="nowrap"><?php echo $keys [ $i ]; ?></td>
                                <td align="right"><?php echo $value['aantal_print_betaald']; ?></td>
                                <td align="right"><?php echo $value['aantal_print_gratis']; ?></td>
                                <td align="right"><?php echo $value['aantal_qr_betaald']; ?></td>
                                <td align="right"><?php echo $value['aantal_qr_gratis']; ?></td>
                                <td align="right">&euro; <?php echo number_format( $value['bedrag_print'],2 ,",","." ); ?></td>
                                <td align="right">&euro; <?php echo number_format( $value['bedrag_qr'],2 ,",","." ); ?></td>
                                <td align="right">&euro; <?php echo number_format( $value['bedrag_totaal'],2 ,",","." ); ?></td></tr>
                               <?php 
                              $i++;
                              
                          } 
                      ?>
                    
                  </table>

                  <div style="width:auto; height: 2000px;">
                      <h3>Aantallen</h3>
                        <canvas id="myChart_aantallen" ></canvas>
                        <br>
                      <h3>Bedragen</h3>
                        <canvas id="myChart_bedragen" ></canvas>
                      </div>

                      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                      <script>
                        const ctx = document.getElementById('myChart_aantallen');

                        new Chart(ctx, {
                          type: 'bar',
                          data: {
                            labels: [<?php
                             $lastDay = $daysOfMonth[ $month -1 ];
                             for ( $werkdag = 1; $werkdag <=  $lastDay; $werkdag ++ )
                                    {
                                    if ( $werkdag < 10 ) { $werkdag = "0" . $werkdag; }
                                    $date = $year . "-" . $month_met_nul . "-" . $werkdag;
                                    ?><?php echo "'" . $date . "',"; 
                                    }
                                      ?> ],
                            datasets: [
                              {
                              label: 'Aantal print betaald',
                              data: [
                                <?php  
                                  for ( $werkdag = 1; $werkdag <=  $lastDay; $werkdag ++ )
                                    {
                                    if ( $werkdag < 10 ) { $werkdag = "0" . $werkdag; }  
                                    $date = $year . "-" . $month_met_nul . "-" . $werkdag;
                                    ?><?php echo "'" . ( $werkdag_array[ $date ][ 'aantal_print_betaald'] ) . "',"; 
                                    }
                                    ?>   
                                     ],
                              borderWidth: 1
                              },
                              {
                              label: 'Aantal print gratis',
                              data: [
                                <?php  
                                  for ( $werkdag = 1; $werkdag <=  $lastDay; $werkdag ++ )
                                    {
                                    if ( $werkdag < 10 ) { $werkdag = "0" . $werkdag; }  
                                    $date = $year . "-" . $month_met_nul . "-" . $werkdag;
                                    ?><?php echo "'" . ( $werkdag_array[ $date ][ 'aantal_print_gratis'] ) . "',"; 
                                    }
                                    ?>   
                                     ],
                              borderWidth: 1
                              },
                              {
                              label: 'Aantal qr betaald',
                              data: [
                                <?php  
                                  for ( $werkdag = 1; $werkdag <=  $lastDay; $werkdag ++ )
                                    {
                                    if ( $werkdag < 10 ) { $werkdag = "0" . $werkdag; }  
                                    $date = $year . "-" . $month_met_nul . "-" . $werkdag;
                                    ?><?php echo "'" . ( $werkdag_array[ $date ][ 'aantal_qr_betaald'] ) . "',"; 
                                    }
                                    ?>   
                                     ],
                              borderWidth: 1
                              },
                              {
                              label: 'Aantal qr gratis',
                              data: [
                                <?php  
                                  for ( $werkdag = 1; $werkdag <=  $lastDay; $werkdag ++ )
                                    {
                                    if ( $werkdag < 10 ) { $werkdag = "0" . $werkdag; }  
                                    $date = $year . "-" . $month_met_nul . "-" . $werkdag;
                                    ?><?php echo "'" . ( $werkdag_array[ $date ][ 'aantal_qr_gratis'] ) . "',"; 
                                    }
                                    ?>   
                                     ],
                              borderWidth: 1
                              }
                            ]
                          },
                          options: {
                            scales: {
                              x: {
                                stacked: true,
                              },
                              y: {
                                beginAtZero: true,
                                stacked: true,
                              }
                            }
                          }
                        });
                      </script>


                      <script>
                        const ctx2 = document.getElementById('myChart_bedragen');

                        new Chart(ctx2, {
                          type: 'bar',
                          data: {
                            labels: [<?php
                             for ( $werkdag = 1; $werkdag <=  $lastDay; $werkdag ++ )
                                    {
                                    if ( $werkdag < 10 ) { $werkdag = "0" . $werkdag; }
                                    $date = $year . "-" . $month_met_nul . "-" . $werkdag;
                                    ?><?php echo "'" . $date . "',"; 
                                    }
                                      ?> ],
                            datasets: [
                              {
                              label: 'Bedrag print',
                              data: [
                                <?php  
                                  for ( $werkdag = 1; $werkdag <=  $lastDay; $werkdag ++ )
                                    {
                                    if ( $werkdag < 10 ) { $werkdag = "0" . $werkdag; }  
                                    $date = $year . "-" . $month_met_nul . "-" . $werkdag;
                                    ?><?php echo "'" . ( $werkdag_array[ $date ][ 'bedrag_print'] ) . "',"; 
                                    }
                                    ?>   
                                     ],
                              borderWidth: 1
                              },
                              {
                              label: 'Bedrag QR',
                              data: [
                                <?php  
                                  for ( $werkdag = 1; $werkdag <=  $lastDay; $werkdag ++ )
                                    {
                                    if ( $werkdag < 10 ) { $werkdag = "0" . $werkdag; }  
                                    $date = $year . "-" . $month_met_nul . "-" . $werkdag;
                                    ?><?php echo "'" . ( $werkdag_array[ $date ][ 'bedrag_qr'] ) . "',"; 
                                    }
                                    ?>   
                                     ],
                              borderWidth: 1
                              }
                            ]
                          },
                          options: {
                            scales: {
                              x: {
                                stacked: true,
                              },
                              y: {
                                beginAtZero: true,
                                stacked: true,
                              }
                            }
                          }
                        });
                      </script>
                  </div>
                </div>
              </div>
            </div>
          </div>          
        </div>
      </div>
    </main>
    <footer class="c-footer">
      <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> © <?php echo date('Y'); ?> MagicShoot.</div>
      <div class="ml-auto">Powered by&nbsp;<a href="https://www.MagicShoot.nl/">MagicShoot</a></div>
    </footer>
  </div>
</div>  
<?php include("partial_footer.php"); ?>
