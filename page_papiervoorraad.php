  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");


  $event_id = $_SESSION['event_id'];
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }
  $mode     = "";
  $period   = "";
  $maand    = "";
  if ( isset ($_REQUEST['mode']))   { $mode     = mysqli_real_escape_string( $cn, $_REQUEST['mode']);  }
  if ( isset ($_REQUEST['period'])) { $period   = mysqli_real_escape_string( $cn, $_REQUEST['period']);}
  if ( $period == "" ) { $period = '7 DAY';}
 
  
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

  if ( $maand < 10 ) 
    { 
    $maand_met_nul = "0" . $maand;
    }   
  
  if ( $maand >= 10 ) 
    { 
    $maand_met_nul = $maand;
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
        <li class="breadcrumb-item">Papiervoorraad

        </li>
        
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
                      <h2 class="text-white text-uppercase">Papiervoorraad</h2>
                    
                    </div>
                  
                  <div class="card-body">
                    <a href="page_printer_overview.php" class="btn btn-info">Printer status</a>
                    <a href="page_papierverbruik.php" class="btn btn-info">Papier verbruik</a>
                    <a href="page_papiervoorraad.php" class="btn btn-info">Papier voorraad</a>
                    <?php
                    # in_stock
                    $sql = "SELECT number_of_rolls 
                              FROM photobooth_media_in_stock
                             WHERE event_id = '" . $_SESSION['event_id'] . "'";
                    $result            = mysqli_query( $cn , $sql );
                    $return	           = mysqli_fetch_object ( $result );
                    $number_of_rolls_in_stock      = $return->number_of_rolls;

                    # supplied
                    $array_supplied= array();
                    $sql = "SELECT date_modified, number_of_rolls
                              FROM photobooth_media
                             WHERE  event_id = '" . $_SESSION['event_id'] . "'";
                    $result            = mysqli_query( $cn , $sql );
                    //echo $sql;
                    while ( $return	   = mysqli_fetch_object ( $result ) ) {
                       $number_of_rolls_supplied             = $return->number_of_rolls;
                       $date_supplied                        = $return->date_modified;
                       $date_supplied_array[]                = $date_supplied;
                       $array_supplied [ $date_supplied ]    = $number_of_rolls_supplied;
                    }
                    //echo '<pre>';
                    //print_r($date_supplied_array);
                    //print_r($array_supplied );
                    //echo '</pre>';
                    ?>
                    <h2> Aantal rollen op voorraad</h2>
                    <p>Aantal rollen op voorraad op dit moment: <strong><?php echo $number_of_rolls_in_stock; ?></strong></p><br>

                     <h2>Aangeleverd en verbruikt</h2>   
                <div style="width:auto; height: 600px;">
                 <canvas id="myChart" ></canvas>
                 </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js@^4"></script>
                <script src="https://cdn.jsdelivr.net/npm/luxon@^2"></script>
                <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@^1"></script>

                 <script>
                   const ctx = document.getElementById('myChart');
                 
                  
                   new Chart(ctx, {
                     type: 'bar',
                     data: {
                      labels: [ 
                        <?php 


                        echo 'luxon.DateTime.now().minus(365 * 86400000).toISO(),
                                luxon.DateTime.now().minus(330 * 86400000).toISO(),
                                luxon.DateTime.now().minus(300 * 86400000).toISO(),
                                luxon.DateTime.now().minus(270 * 86400000).toISO(),
                                luxon.DateTime.now().minus(240 * 86400000).toISO(),
                                luxon.DateTime.now().minus(210 * 86400000).toISO(),
                                luxon.DateTime.now().minus(180 * 86400000).toISO(),
                                luxon.DateTime.now().minus(150 * 86400000).toISO(),
                                luxon.DateTime.now().minus(120 * 86400000).toISO(),
                                luxon.DateTime.now().minus(90 * 86400000).toISO(),
                                luxon.DateTime.now().minus(60 * 86400000).toISO(),
                                luxon.DateTime.now().minus(30 * 86400000).toISO(),
                                luxon.DateTime.now().minus(0 * 86400000).toISO(),';
                                
                       ?> 

                      
                          
                        ],
                      datasets: [
                          {
                          label: 'Aantal rollen aangeleverd',
                          fill: false,
                          borderWidth: 1.1,
                          pointStyle: false,
                          yAxisID: 'y',
                          data: [
                            <?php  
                            foreach (  $date_supplied_array as $date )
                                {
                                //$date_iso = date("Y-m-d\TH:i:s"  ,strtotime( $date )). ".000Z";
                                if( $array_supplied[ $date ] <0 ) {
                                  $date_iso = date("c", strtotime( $date ));
                                  echo "{x: '" . $date_iso. "',";  
                                  echo "y: " . abs($array_supplied[ $date ]) . "},";  
                                  }
                                }
                                ?>   
                                ],
                          },
                          {
                          label: 'Aantal rollen vervangen',
                          fill: false,
                          borderWidth: 1.1,
                          pointStyle: false,
                          yAxisID: 'y',
                          data: [
                            <?php  
                            foreach (  $date_supplied_array as $date )
                                {
                                if( $array_supplied[ $date ] >0 ) {
                                  $date_iso = date("c", strtotime( $date ));
                                  echo "{x: '" . $date_iso. "',";  
                                  echo "y: " . $array_supplied[ $date ] . "},";  
                                  }
                                }
                                ?>   
                                ],
                          },
                          
                      
                        ]
                        } , 
                        options: {
                            plugins: {
                                title: {
                                    text: 'Aangeleverd en verbruikte rollen',
                                    display: true
                                }
                            },
                            
                            scales: {
                                    x: {
                                        type: 'time',
                                        time: {
                                            unit: 'day',
                                            unitStepSize: 1,
                                            displayFormats: {
                                                'day': ' DD'
                                            }, 
                                        },
                                        title: {
                                        display: true,
                                        text: 'Datum'
                                        }
                                    },
                                    y: {
                                        type: 'linear',
                                        display: true,
                                        position: 'left',
                                        beginAtZero: true,
                                    },
                                   
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
