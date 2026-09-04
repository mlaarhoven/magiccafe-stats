<?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");


  $event_id = 429; //$_SESSION['event_id'];
  $shelly_device_id = 'fcb46732db';
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }

  $mode = ""; //init
  $period = ""; //init
  
  if ( isset ( $_REQUEST['mode'] ))   { $mode     = mysqli_real_escape_string( $cn, $_REQUEST['mode']);}
  if ( isset ( $_REQUEST['period'] )) { $mode     = mysqli_real_escape_string( $cn, $_REQUEST['period']);}

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
        <li class="breadcrumb-item">Vochtigheid en temperatuur en dauwpunt>

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
                      <h2 class="text-white text-uppercase">Vochtigheid en temperatuur en dauwpunt</h2>
                     
                    </div>
                  
                  <div class="card-body">
                 
               
                     <?php
                    
                    
                     $sql = "SELECT datetime, humidity, temperature, dauwpunt
                               FROM  photobooth_hum_temp
                              WHERE event_id = '" . $event_id . "'
                              AND shelly_device_id =   '$shelly_device_id'
                               AND datetime between date_sub(now(),INTERVAL ". $period .") and now()
                              ORDER BY datetime ASC";
                     

                     $result               = mysqli_query( $cn , $sql );
                     while ( $return	   = mysqli_fetch_object ( $result ) )
                       {
                         $datetime      = $return->datetime;
                         $humidity      = $return->humidity;
                         $temperature   = $return->temperature;
                         $dauwpunt      = $return->dauwpunt;
                         $date_array[]  = $datetime;
                         $value_array[ $datetime ][ 'humidity']      = $humidity;
                         $value_array[ $datetime ][ 'temperature']   = $temperature;
                         $value_array[ $datetime ][ 'dauwpunt']      = $dauwpunt;
                       }

                  
                      
              
      
          ?>

           <div style="width:auto; height: 800px;">
                 <canvas id="myChart" ></canvas>
               </div>

              <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>-->
              
              <script src="https://cdn.jsdelivr.net/npm/chart.js@^4"></script>
              <script src="https://cdn.jsdelivr.net/npm/luxon@^2"></script>
              <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@^1"></script>

               <script>
                 const ctx = document.getElementById('myChart');
               
                
                 new Chart(ctx, {
                   type: 'line',
                   data: {
                    labels: [ 
                      
                    //  luxon.DateTime.now().minus(6 * 86400000).toISO(),
                    //  luxon.DateTime.now().minus(5 * 86400000).toISO(),
                    //  luxon.DateTime.now().minus(4 * 86400000).toISO(),
                    //  luxon.DateTime.now().minus(3 * 86400000).toISO(),
                    //  luxon.DateTime.now().minus(2 * 86400000).toISO(),
                    //  luxon.DateTime.now().minus(1 * 86400000).toISO(),
                      luxon.DateTime.now().minus(0 * 86400000).toISO()
                        
                      ],
                    datasets: [
                        {
                        label: 'Relatieve luchtvochtigheid %',
                        fill: false,
                        borderWidth: 1.1,
                        // tension: 0.4,
                        // cubicInterpolationMode: 'monotone',
                        pointStyle: false,
                        
                        data: [
                          <?php  
                          foreach (  $date_array as $date )
                              {
                              $date_iso = date("Y-m-d\TH:i:s"  ,strtotime( $date )). ".000Z";
                              echo "{x: '" . $date_iso. "',";  
                              echo "y: '" . $value_array[ $date ]['humidity'] . "'},";  
                              }
                              ?>   
                              ],
                        }, 
                      
                        {
                        label: 'Temperatuur in Celsius',
                        fill: false,
                        borderWidth: 1.1,
                        // tension: 0.4,
                        // cubicInterpolationMode: 'monotone',
                        pointStyle: false,
                        data: [
                          <?php  
                          foreach (  $date_array as $date )
                              {
                              $date_iso = date("Y-m-d\TH:i:s"  ,strtotime( $date )). ".000Z";
                              echo "{x: '" . $date_iso. "',";  
                              echo "y: '" . $value_array[ $date ]['temperature'] . "'},";  
                                      }
                              ?>   
                              ],
                          },

                          {
                        label: 'Dauwpunt  in Celsius',
                        fill: false,
                        borderWidth: 1.1,
                        // tension: 0.4,
                        // cubicInterpolationMode: 'monotone',
                        pointStyle: false,
                        data: [
                          <?php  
                          foreach (  $date_array as $date )
                              {
                              $date_iso = date("Y-m-d\TH:i:s"  ,strtotime( $date )). ".000Z";
                              echo "{x: '" . $date_iso. "',";  
                              echo "y: '" . $value_array[ $date ]['dauwpunt'] . "'},";  
                                      }
                              ?>   
                              ],
                          }
                      ]
                      },
                      options: {
                          plugins: {
                            title: {
                              text: 'Shelly Plus and Add On with DHT22 sensor',
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
                              title: {
                                display: true,
                                text: 'Waarde (% en Celsius)'
                              }
                            }
                          },
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
