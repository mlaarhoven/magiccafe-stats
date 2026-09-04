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
  

  $mode             = ""; //init
  $year_aangepast   = 0; //init
  $month_aangepast  = 0; //init
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
    

  if ( $month < 10 ) 
    { 
    $month_met_nul = "0" . $month;
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
        <li class="breadcrumb-item">Aantallen per maand voor: <?php echo $year; ?></li>
        
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
              <div class="col-sm-9 col-md-9 col-lg-9">
                <div class="card">
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Aantallen per maand - <?php echo $year; ?></h2>
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
                    <form action="page_monthly_overview.php" action="post">
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

                 <!--     Kies een andere maand:
                    <form action="page_monthly_overview.php" action="post">
                        <input type="hidden" name="mode" value="save_month">
                        
                        <select name="month" onchange="this.form.submit()">
                            <?php 
                           /* for ( $m = 1; $m <= 12; $m ++ )
                            {
                              ?><option value="<?php echo $m; ?>"
                              <? if ( $m == $month ) { echo " selected"; } ?>
                              ><?php echo $m; ?></option><?php
                            } */
                            ?>
                        </select>
                      </form> -->
                      <br>
                      <br>
                      <p><strong>Let op</strong>: de bedragen in de tabel zijn inclusief btw (om ze te kunnen vergelijken met de getallen uit Payter).</p>
                      <p>De controle bedragen/aantallen van Payter komen met maximaal 10 minuten vertraging binnen!</p>
                  <table class="table table-responsive-sm table-bordered table-striped table-sm">
                      <thead>
                        <tr>
                       
                        <td align="right"><strong>jaar</strong></td>
                        <td align="right"><strong>maand</strong></td>
                        <td align="right"><strong>aantal prints</strong></td>
                        <td align="right"><strong>bedrag prints</strong></td>
                        <td align="right"><strong>aantal qr</strong></td>
                        <td align="right"><strong>bedrag qr</strong></td>
                        <td align="right"><strong>totaal aantal transacties</strong></td>
                        <td align="right"><strong>bedrag totaal</strong></td>
			<td align="right"><strong>controle aantal Payter</strong></td>
                        <td align="right"><strong>controle bedrag Payter</strong></td>
                      </tr>  
                    </thead>
// TODO join txnsn+txnid
                      <?php
                          setlocale(LC_MONETARY, "nl_NL");    
                          $sql = 'SELECT
                              -- payter
                              payterOK.event_id,
                              YEAR(payterOK.txnTimestampLocal) as jaar,
                              MONTH(payterOK.txnTimestampLocal) AS maand,
                              COUNT(payterOK.transactionId) as nr_payter,
                              SUM(payterOK.committedAmount)/100 AS amount_payter,

                              -- log_session
                              COUNT(log_sessionOK.txnid) as nr_total,
                              SUM(log_sessionOK.prijs)/100 AS amount_total,
                              SUM( if(log_sessionOK.sessie_soort="print",1,0)) AS nr_print,
                              SUM( if(log_sessionOK.sessie_soort="print",prijs,0))/100 AS amount_print,
                              SUM( if(log_sessionOK.sessie_soort="qr",1,0)) AS nr_QR,
                              SUM( if(log_sessionOK.sessie_soort="qr",prijs,0))/100 AS amount_QR

                            FROM payterOK LEFT JOIN log_sessionOK ON 
                                    payterOK.serialNumber =log_sessionOK.txnsn AND
                                    payterOK.transactionId=log_sessionOK.txnid

                            WHERE       payterOK.event_id='.$event_id.'
                              AND YEAR( payterOK.txnTimestampLocal) = '. $year .'

                            GROUP BY payterOK.event_id, YEAR(payterOK.txnTimestampLocal), MONTH(payterOK.txnTimestampLocal)';

                          //echo $sql . "<br>";
                         
                         
                          $result      = mysqli_query( $cn , $sql );
                          while ( $return	     = mysqli_fetch_object ( $result ) )
                            {
                              $jaar           = $return->jaar;
                              $maand          = $return->maand;
                              $nr_payter      = $return->nr_payter;
                              $amount_payter  = $return->amount_payter;

                              $nr_total       = $return->nr_total;
                              $amount_total   = $return->amount_total;
                              $nr_print       = $return->nr_print;
                              $amount_print   = $return->amount_print;
                              $nr_QR          = $return->nr_QR;
                              $amount_QR      = $return->amount_QR;

                              $maand_array[ $maand ] = $amount_payter;
                              ?><tr>
                                  <td align="right"><?php echo $jaar; ?></td>
                                  <td align="right"><?php echo $maand; ?></td>

                                  <td align="right"><?php echo $nr_print; ?></td>
                                  <td align="right">&euro; <?php echo number_format( $amount_print,2 ,",","." ); ?></td>
                                  <td align="right"><?php echo $nr_QR; ?></td>
                                  <td align="right">&euro; <?php echo number_format( $amount_QR,2 ,",","." ); ?></td>
                                  <td align="right"<?php echo ($nr_total!=$nr_payter)?' style="color:red"':'';?>><?php echo $nr_total; ?></td>
                                  <td align="right"<?php echo ($amount_total!=$amount_payter)?' style="color:red"':'';?>>&euro; <?php echo number_format( $amount_total  ,2 ,",","." ); ?></td>

                                  <td align="right"><strong><?php echo $nr_payter; ?></strong></td>
                                  <td align="right"><strong>&euro; <?php echo number_format( $amount_payter  ,2 ,",","." ); ?></strong></td>

                                </tr><?php

                            }

                      ?>
                    
                  </table>

                  <div style="width:auto; height: 800px;">
                        <canvas id="myChart" ></canvas>
                      </div>

                      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                      <script>
                        const ctx = document.getElementById('myChart');

                        new Chart(ctx, {
                          type: 'bar',
                          data: {
                            labels: [<?php
                             for ( $maand = 1; $maand <= 12; $maand ++ )
                                    {
                                    ?><?php echo $maand . ","; 
                                    }
                                      ?> ],
                            datasets: [{
                              label: 'Verkoop per maand inclusief btw <?php echo $year; ?>',
                              data: [
                                <?php  
                                  for ( $maand = 1; $maand <= 12; $maand ++ )
                                    {
                                    ?><?php 
                                    
                                    if ( array_key_exists ( $maand, $maand_array )) {
                                      echo ( $maand_array[ $maand ] ) . ","; 
                                    } else { echo "0,"; }
                                    }
                                    ?>   
                                     ],
                              borderWidth: 1
                            }]
                          },
                          options: {
                            scales: {
                              y: {
                                beginAtZero: true
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
