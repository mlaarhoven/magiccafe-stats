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

  $mode = ""; //init
  $period = ""; //init
  
  if ( isset ( $_REQUEST['mode'] ))   { $mode     = mysqli_real_escape_string( $cn, $_REQUEST['mode']);}
  if ( isset ( $_REQUEST['period'] )) { $period   = mysqli_real_escape_string( $cn, $_REQUEST['period']);}

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
        <li class="breadcrumb-item">Papierverbruik - <?php echo $period; ?>

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
                      <h2 class="text-white text-uppercase">Papierverbruik - <?php echo $period; ?></h2>
                     
                    </div>
                  
                  <div class="card-body">
                  <a href="page_printer_overview.php" class="btn btn-info">Printer status</a>
                      <a href="page_papierverbruik.php" class="btn btn-info">Papier verbruik</a>
                      <a href="page_papiervoorraad.php" class="btn btn-info">Papier voorraad</a>
                  Kies een andere periode:
                    <form action="page_papierverbruik.php" action="post">
                        <select name="period" onchange="this.form.submit()">
                            <option value='2 DAY' <?php if ( $period == '2 DAY') { echo "selected ";} ?>>2 dagen</option>
                            <option value='4 DAY' <?php if ( $period == '4 DAY') { echo "selected ";} ?>>4 dagen</option>
                            <option value='7 DAY' <?php if ( $period == '7 DAY') { echo "selected ";} ?>>7 dagen</option>
                            <option value='30 DAY' <?php if ( $period == '30 DAY') { echo "selected ";} ?>>30 dagen</option>
                            <option value='60 DAY' <?php if ( $period == '60 DAY') { echo "selected ";} ?>>60 dagen</option>
                            <option value='90 DAY' <?php if ( $period == '90 DAY') { echo "selected ";} ?>>90 dagen</option>
                            <option value='365 DAY' <?php if ( $period == '365 DAY') { echo "selected ";} ?>>365 dagen</option>

                        </select>
                      </form>
                     <?php
                     # mediacounter vd printer
                    
                     $sql = "SELECT log_created, log_details 
                             FROM log
                            WHERE event_id = '" . $_SESSION['event_id'] . "'
                              AND log_action      = 'counter'
                              AND log_details    != '-1'
                              AND log_created between date_sub(now(),INTERVAL ". $period .") and now()
                              ORDER BY log_created ASC";
                     

                     $result               = mysqli_query( $cn , $sql );
                     while ( $return	   = mysqli_fetch_object ( $result ) )
                       {
                         $log_created      = $return->log_created;
                         $print_counter    = $return->log_details;
                         $date_array[]     = $log_created;
                         $value_array[ $log_created]['printcounter'] = $print_counter;
                       }

                  
                      
                      ?>
                    
             <div style="width:auto; height: 600px;">
                   <canvas id="myChart" ></canvas>
                 </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js@^4"></script>
                <script src="https://cdn.jsdelivr.net/npm/luxon@^2"></script>
                <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@^1"></script>

                 <script>
                   const ctx = document.getElementById('myChart');
                 
                  
                   new Chart(ctx, {
                     type: 'line',
                     data: {
                      labels: [ 
                        <?php 
                        if ( $period == '2 DAY' ) {
                          echo 'luxon.DateTime.now().minus(1 * 86400000).toISO(),
                                luxon.DateTime.now().minus(0 * 86400000).toISO()';
                        }

                        if ( $period == '4 DAY' ) {
                          echo 'luxon.DateTime.now().minus(3 * 86400000).toISO(),
                                luxon.DateTime.now().minus(2 * 86400000).toISO(),
                                luxon.DateTime.now().minus(1 * 86400000).toISO(),
                                luxon.DateTime.now().minus(0 * 86400000).toISO(),';
                        }

                        if ( $period == '7 DAY' ) {
                          echo 'luxon.DateTime.now().minus(6 * 86400000).toISO(),
                                luxon.DateTime.now().minus(5 * 86400000).toISO(),
                                luxon.DateTime.now().minus(4 * 86400000).toISO(),
                                luxon.DateTime.now().minus(3 * 86400000).toISO(),
                                luxon.DateTime.now().minus(2 * 86400000).toISO(),
                                luxon.DateTime.now().minus(1 * 86400000).toISO(),
                                luxon.DateTime.now().minus(0 * 86400000).toISO(),';
                        }

                        if ( $period == '30 DAY' ) {
                          echo '
                                luxon.DateTime.now().minus(20 * 86400000).toISO(),
                                luxon.DateTime.now().minus(10 * 86400000).toISO(),
                                luxon.DateTime.now().minus(0 * 86400000).toISO(),';
                        }

                        if ( $period == '90 DAY' ) {
                          echo 'luxon.DateTime.now().minus(90 * 86400000).toISO(),
                                luxon.DateTime.now().minus(60 * 86400000).toISO(),
                                luxon.DateTime.now().minus(30 * 86400000).toISO(),
                                luxon.DateTime.now().minus(0 * 86400000).toISO(),';
                        }

                        if ( $period == '365 DAY' ) {
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
                        } ?>

                      
                          
                        ],
                      datasets: [
                          {
                          label: 'Aantal prints dat nog gemaakt kan worden met deze rol papier (media counter)',
                          fill: false,
                          borderWidth: 1.1,
                          pointStyle: false,
                          yAxisID: 'y',
                          data: [
                            <?php  
                            foreach (  $date_array as $date )
                                {
                                $date_iso = date("Y-m-d\TH:i:s"  ,strtotime( $date )). ".000Z";
                                echo "{x: '" . $date_iso. "',";  
                                echo "y: '" . $value_array[ $date ]['printcounter'] . "'},";  
                                }
                                ?>   
                                ],
                          },
                          
                      
                        ]
                        } , 
                        options: {
                            plugins: {
                                title: {
                                    text: 'Papier verbruik (media counter)',
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

<!--
                  <h2>Gemiddeld aantal prints per dagsoort [ TO DO ]</h2>
                  <p>Bepaal wat over het jaar genomen het gemiddelde aantal prints per soort dag is (dus hoeveel prints gemiddeld op een donderdag avond, 
                    waarbij deze avond dan start op donderdag en eindigd op vrijdag ochtend).
                    Zet dit uit tegen het aantal prints left en dan kan je zien of je de rol moet vervangen.</p>
                  <?php
                     # mediacounter vd printer
                     $sql = "SELECT log_created, log_details 
                             FROM log
                            WHERE event_id = '" . $_SESSION['event_id'] . "'
                              AND log_action      = 'counter'
                              AND log_created between date_sub(now(),INTERVAL 1 WEEK) and now()
                              ORDER BY log_created ASC";
                     $date_array= array();

                     $result               = mysqli_query( $cn , $sql );
                     while ( $return	   = mysqli_fetch_object ( $result ) )
                       {
                         $log_created      = $return->log_created;
                         $print_counter    = $return->log_details;
                         $date_array[]     = $log_created;
                         $value_array[ $log_created]['printcounter'] = $print_counter;
                       }

                     # gemiddeld aantal prints per dagsoort
                     # [ to do : query bedenken ]
                     $sql = "SELECT print_einde, print_counter
                               FROM log_session
                              WHERE event_id = '" . $_SESSION['event_id'] . "'
                                
                                AND print_einde between date_sub(now(),INTERVAL 1 WEEK) and now()
                           ORDER BY print_einde ASC";

                      //echo $sql;
                        
                      $date_array_life= array();

                      $result              = mysqli_query( $cn , $sql );
                    /*
                      while ( $return	   = mysqli_fetch_object ( $result ) )
                            {
                            $print_einde                = $return->print_einde;
                            $print_counter_life         = $return->print_counter;
                            $date_array_life[]          = $print_einde;
                            $value_array_life[ $print_einde]['printcounter_life'] = $print_counter_life;
                            }
                      */
                      ?>
                    
             <div style="width:auto; height: 600px;">
                   <canvas id="myChart2" ></canvas>
                 </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js@^4"></script>
                <script src="https://cdn.jsdelivr.net/npm/luxon@^2"></script>
                <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@^1"></script>

                 <script>
                   const ctx2 = document.getElementById('myChart2');
                 
                  
                   new Chart(ctx2, {
                     type: 'line',
                     data: {
                      labels: [ 
                        
                        luxon.DateTime.now().minus(6 * 86400000).toISO(),
                        luxon.DateTime.now().minus(5 * 86400000).toISO(),
                        luxon.DateTime.now().minus(4 * 86400000).toISO(),
                        luxon.DateTime.now().minus(3 * 86400000).toISO(),
                        luxon.DateTime.now().minus(2 * 86400000).toISO(),
                        luxon.DateTime.now().minus(1 * 86400000).toISO(),
                        luxon.DateTime.now().minus(0 * 86400000).toISO()
                          
                        ],
                      datasets: [
                          {
                          label: 'Media counter - prints left',
                          fill: false,
                          borderWidth: 1.1,
                          pointStyle: false,
                          yAxisID: 'y',
                          data: [
                            <?php  
                            foreach (  $date_array as $date )
                                {
                                $date_iso = date("Y-m-d\TH:i:s"  ,strtotime( $date )). ".000Z";
                                echo "{x: '" . $date_iso. "',";  
                                echo "y: '" . $value_array[ $date ]['printcounter'] . "'},";  
                                }
                                ?>   
                                ],
                          },
                          
                          {
                          label: 'Gemiddeld aantal prints per dagsoort',
                          fill: false,
                          borderWidth: 1.1,
                         
                          pointStyle: false,
                          yAxisID: 'y',
                          data: [
                                <?php  
                                foreach (  $date_array_life as $date )
                                    {
                                    $date_iso = date("Y-m-d\TH:i:s"  ,strtotime( $date )). ".000Z";
                                    echo "{x: '" . $date_iso. "',";  
                                    echo "y: '" . $value_array_life[ $date ]['printcounter_life'] . "'},";  
                                    }
                                    ?>   
                                    ],
                          }
                        ]
                        } ,
                        options: {
                            plugins: {
                                title: {
                                    text: 'Gemiddeld verbruik per dag versus aanal prints left',
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
                                                'day': ' DDDD'
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
                                    },

                                }
                            }
                        });
                  </script>    
                  <br>

                      -->
                  <strong> Aantal prints per startdatum</strong>  
                  <table class="table table-responsive-sm table-bordered table-striped table-sm">
                     
                  
                <?php 
                # AANTAL PRINTS PER DAG SOORT
                $sql = "SELECT print_einde, COUNT(print_counter) as print_counter
                          FROM log_session
                         WHERE event_id            = '" . $event_id . "' 
                           AND print_einde   between date_sub(now(),INTERVAL ". $period.") and now()
                           AND disposition         = 'APPROVED' 
                           AND state               = 'COMMITTED'
                           AND sessie_soort        = 'print'
                           GROUP BY DAY(print_einde)";
                //echo $sql;
                $result           = mysqli_query( $cn , $sql );
                ?><tr><?php
                while ( $return           = $result->fetch_object() )
                    {
                    $print_einde      = $return->print_einde;
                    $print_counter    = $return->print_counter;
                
                    //$dag_aantal_geprinte_strookjes_array[ $print_einde ] = $print_counter;
                    ?>
                    <td><?php echo $print_einde; ?></td>
                    
                    <?php
                    } ?>
                </tr>
                <tr><?php 
                $sql = "SELECT print_einde, COUNT(print_counter) as print_counter
                        FROM log_session
                    WHERE event_id            = '" . $event_id . "' 
                        AND print_einde   between date_sub(now(),INTERVAL ". $period.") and now()
                        AND disposition         = 'APPROVED' 
                        AND state               = 'COMMITTED'
                        AND sessie_soort        = 'print'
                        GROUP BY DAY(print_einde)";
               
                $result           = mysqli_query( $cn , $sql );
                while ( $return           = $result->fetch_object() )
                    {
                    $print_einde      = $return->print_einde;
                    $print_counter    = $return->print_counter;
                
                    //$dag_aantal_geprinte_strookjes_array[ $print_einde ] = $print_counter;
                    ?>
                    <td><?php echo $print_counter; ?></td>
                    
                    <?php
                    } ?>
                </tr><?php  
                 
               // echo "<pre>";
               // print_r ( $dag_aantal_geprinte_strookjes_array );
               // echo "</pre>";
                  
                  ?>
               
                </table>
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
