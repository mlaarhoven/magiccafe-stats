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
        <li class="breadcrumb-item">Omzet per werk week</li>
        
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
                      <h2 class="text-white text-uppercase">Omzet per werk week</h2>
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
                    <form action="page_revenue_per_workweek.php" action="post">
                        <input type="hidden" name="mode" value="save_year">
                        
                        <select name="year" onchange="this.form.submit()">
                            <?php 
                            for ( $y = 2023; $y < 2060; $y ++ )
                            {
                              ?><option value="<?php echo $y; ?>"
                              <?php if ( $y == $year ) { echo " selected"; } ?>
                              ><?php echo $y; ?></option><?php
                            }
                            ?>
                        </select>
                      </form>
                   
                      <br>
                      <br>
                      
                  <table class="table table-responsive-sm table-bordered table-striped table-sm">
                      <thead>
                        <tr>
                       
                        <td align="right"><strong>werk week</strong></td>
                        <td align="right"><strong>aantal</strong></td>
                        <td align="right"><strong>bedrag totaal</strong></td>
                        
                      </tr>  
                    </thead>
                    
                      <?php
                      setlocale(LC_MONETARY, "nl_NL");    
                        $sql = "SELECT event_id,
                                       DATE_FORMAT(werkdag,'%x-%v') AS week, 
                                       COUNT(*) AS aantal, 
                                       SUM(prijs) AS totaalbedrag
                                  FROM log_sessionOK
                                 WHERE event_id        = '" . $event_id . "'
                                   
                                   AND YEAR(werkdag)   = '" . $year     . "'
                              GROUP BY event_id, week  
                              ORDER BY `werkdag` DESC";

                          //echo $sql . "<br>";
                         
                         
                          $result = mysqli_query( $cn , $sql );
                          while ( $return	= mysqli_fetch_object ( $result ) )
                            {
                              $week                       = $return->week;
                              $aantal                     = $return->aantal;
                              $totaalbedrag               = $return->totaalbedrag;
                              
                              $workweek_array[ $week ]  = $totaalbedrag/100;
                              ?><tr>
                                  <td align="right"><?php echo $week; ?></td>
                                  <td align="right"><?php echo $aantal; ?></td>
                                  <td align="right">&euro; <?php echo number_format( $totaalbedrag/100,2 ,",","." ); ?></td>

                                </tr><?php

                            }
                            echo "<pre>";
                            //print_r ( $werkdag_array );
                            echo "</pre>";

                      ?>
                    
                  </table>

                  <div style="width:auto; height: 600px;">
                        <canvas id="myChart" ></canvas>
                      </div>

                      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                      <script>
                        const ctx = document.getElementById('myChart');

                        new Chart(ctx, {
                          type: 'bar',
                          data: {
                            labels: [<?php
                             for ( $workweek = 1; $workweek <= 52; $workweek ++ )
                                    {
                                    if ( $workweek < 10 ) { $workweek = "0" . $workweek; }  
                                    ?><?php echo "'" . $year . "-" . $workweek . "',"; 
                                    }
                                      ?> ],
                            datasets: [{
                              label: 'Bedrag per week',
                              data: [
                                <?php  
                                  for ( $workweek = 1; $workweek <= 52; $workweek ++ )
                                  {
                                    if ( $workweek < 10 ) { $workweek = "0" . $workweek; }  
                                    $date = $year . "-" . $workweek;
                                    ?><?php echo "'" . ( $workweek_array[ $date ] ) . "',"; 
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
