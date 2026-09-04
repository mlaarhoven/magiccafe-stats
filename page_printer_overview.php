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
  
  ?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Printer status</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">


       
          <?php     
          # haal SENSORS status op        
          $sql = "SELECT *
                    FROM log
                   WHERE event_id   = '" . $event_id . "' 
                     AND log_action = 'sensors' 
                     ORDER BY log_id DESC
                     LIMIT 0,1
                    ";
          
          $result   = mysqli_query( $cn , $sql );
          if ( !$result ){echo "ERROR bij ophalen sensors";}
          $counter          = 0;
          $return           = $result->fetch_object();
          $log_id           = $return->log_id;
          $log_session_id   = $return->log_session_id;
          $log_created      = $return->log_created;
          $details          = $return->log_details;


          # haal COUNTER status op
          $sql_1   = "SELECT log_details, log_created
                       FROM log
                      WHERE event_id        = '" . $event_id         . "' 
                        AND log_action      = 'counter'
                   ORDER BY log_id DESC
                      LIMIT 0,1";
          $result_1                   = mysqli_query( $cn , $sql_1 );
          $return_1                   = $result_1->fetch_object();
          $print_counter              = $return_1->log_details;
          $log_created_print_counter  = $return_1->log_created;


          # haal PRINT KLAAR status op
          $sql2 = "SELECT *
                    FROM log
                   WHERE event_id   = '" . $event_id . "' 
                     AND log_action = 'PRINT KLAAR' 
                     ORDER BY log_id DESC
                     LIMIT 0,1
                    ";
          
          //echo $sql;
          $result2                   = mysqli_query( $cn , $sql2 );
          $return2                   = $result2->fetch_object();
          $print_ready_status        = $return2->log_details;
          if ( $print_ready_status  == "") { $print_ready_status = "alles is okee";}
            ?> 
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
            
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Printer status</h2>
                    
                    </div>
                   
                  <div class="card-body">
                  <a href="page_printer_overview.php" class="btn btn-info">Printer status</a>
                      <a href="page_papierverbruik.php" class="btn btn-info">Papier verbruik</a>
                      <a href="page_papiervoorraad.php" class="btn btn-info">Papier voorraad</a>
                    <h2>Laatst gemeten status</h2>
                    <p>Let op: on/off geeft alleen aan of tijdens het ophalen van de status de printer on of off was, dat is meestal de laatste logregel, dus geen live meting. <br>
                      Kijk even goed naar de Log datum en tijd.</p><br>
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                        <thead>
                          <tr>
                                                   
                            <th>Log ID</th>
                            <th>Log session ID</th>
                            <th>Log datum tijd</th> 
                            <th>Prints voorraad</th>
                            <th>Prints voorraad datum en tijd</th>
                            <th>Printer status</th>
                            <th>On/Off</th>
                            <th>Temp printkop</th>
                            <th>Temp papierrol</th>
                            <th>Lucht vochtigheid</th>
                          </tr>
                        </thead>
                        <tbody>
                        <?php
                        // init vars
                        $humidity   = "";
                        $head_temp  = "";
                        $media_temp = "";
                        
                        if ( $details <> 'error') {
                          $status = 'on';
                          // format: Head temp:21,3 media temp:19,6 humidity:61%
                          $details_array = explode( ":", $details );
                          $head_temp  = $details_array[1];
                          $head_temp  = substr( $head_temp, 0,4);
                          $media_temp = $details_array[2];
                          $media_temp =substr( $media_temp, 0,4);
                          $humidity   = $details_array[3];
                        }
                        else
                          { 
                          $status = 'off';
                        }
                         
                          ?>
                          <tr>
                            <td><?php echo $log_id; ?></td>
                            <td><?php echo $log_session_id; ?></td>
                            <td><?php echo $log_created; ?></td>
                            <td><strong><?php echo $print_counter; ?></strong></td>
                            <td><?php echo $log_created_print_counter; ?></td>
                            <td><?php echo $print_ready_status; ?></td>
                            <td><?php echo $status; ?></td>
                            <td><?php echo $head_temp; ?></td>
                            <td><?php echo $media_temp; ?></td>
                            <td><?php echo $humidity; ?></td>
                          </tr>
                        </tbody>
                      </table>

                  <br>        
                  <h2>Alle printer foutmeldingen</h2>
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                        <thead>
                          <tr>
                            <th>Log ID</th>
                            <th>Log session ID</th>
                            <th>Log datum tijd</th> 
                            <th>Log details</th> 
                          </tr>   
                        </thead>
                            <?php
                            $sql = "SELECT *
                                      FROM log
                                      WHERE event_id = '" . $_SESSION['event_id'] . "'
                                        AND log_action = 'printerstatus'
                                        AND log_details <> 'Idle'
                                        ORDER BY log_created DESC";

                            //echo $sql;
                            $result  = mysqli_query( $cn , $sql );
                            $aantal  = mysqli_num_rows ( $result ) ;
                            if ( $aantal == 0 ) {
                              ?><tr>
                                
                              <td colspan="4">Er zijn geen foutmeldingen.</td>
                              
                            </tr>
                              <?php
                            }
                            while ( $return = mysqli_fetch_object ( $result ) )
                              {
                              $log_id          = $return->log_id;
                              $log_session_id  = $return->log_session_id;
                              $log_created     = $return->log_created;
                              $log_details     = $return->log_details;
                              ?><tr>
                                
                                <td><?php echo $log_id; ?></td>
                                <td><?php echo $log_session_id; ?></td>
                                <td><?php echo $log_created; ?></td>
                                <td><?php echo $log_details; ?></td>
                            
                              </tr><?php  
                              } ?>

                            </table>
                     <br>
                     <h2>Mogelijke oorzaken foutmeldingen</h2>
                      
                     <strong>Ribbon error - folie plakt vast aan het papier</strong> 
                     <p>Bij een te hoge luchtvochtigheid in combinatie met een te lage papiertemperatuur bestaat de kans dat er vocht condenseert<br> 
                        op de koude rol papier. Hierdoor kan het voorkomen dat de folie vastplakt aan het papier en de printer in storing gaat. </p>
                     <p>Een oplossing hiervoor is de printer en de rol papier verwarmen met warme lucht.</p>
                     <p>In de grafiek onderaan deze pagina kan je de gemeten vochtigheid en temperatuur zien van de rol.</p>
                     <br>
                      
                     <strong>Paper error - papier zit vast</strong>        
                     <p>Als de printer teveel naar links in de kast schuift dan kan het zijn dat het strookje niet uitgeworpen kan worden, doordat het papier tegen de buitenkast aan komt. <br>
                     Let erop dat je het schuifje omhoog doet om de lade waarop de printer staat, tegen te houden.</p>
                     <br> 
                     <h2>Grafiek</h2>
                     <p>Toon de laatste 50 metingen van de temperatuur van het papier en de printkop en de luchtvochtigheid in een grafiek</p>
                 
                 <?php
                 
                   $sql = "SELECT log_created, log_details 
                             FROM log
                            WHERE event_id = '" . $_SESSION['event_id'] . "'
                              AND log_action = 'sensors'
                              AND log_created between date_sub(now(),INTERVAL 1 WEEK) and now()
                              ORDER BY log_created ASC";

                     //echo $sql;
                    
                     $date_array= array();

                     $result               = mysqli_query( $cn , $sql );
                     while ( $return	     = mysqli_fetch_object ( $result ) )
                       {
                         $log_created          = $return->log_created;
                         $log_details          = $return->log_details;
                         $details_array        = explode( ":", $log_details );
                         
                         $head_temp  = $details_array[1];
                         if ( substr( $head_temp, 3,1) == 'm' )
                          {
                          $head_temp  = substr( $head_temp, 0,3);
                          }
                         else
                         {
                          $head_temp  = substr( $head_temp, 0,4);
                         } 
                         $head_temp = str_replace (',','.',$head_temp);
                         
                         $media_temp = $details_array[2];
                         
                         if ( substr( $media_temp, 3,1) == 'h' )
                          {
                            $media_temp = substr( $media_temp, 0,3);
                          }
                         else {
                          $media_temp = substr( $media_temp, 0,4);
                         }   
                         
                         $media_temp = str_replace (',','.', $media_temp);

                         $humidity   = $details_array[3];
                         if ( substr( $humidity, 3,1) == '%' )
                          {
                          $humidity   = substr( $humidity, 0,3);
                          }
                         else
                         {
                          $humidity   = substr( $humidity, 0,4);
                         } 
                         $humidity   = str_replace ( '%','', $humidity);
                         $humidity   = str_replace ( ',','.', $humidity);
                       
                         $date_array[] = $log_created ;
                         $value_array[ $log_created ][ 'head_temp']  = $head_temp;
                         $value_array[ $log_created ][ 'media_temp'] = $media_temp;
                         $value_array[ $log_created ][ 'humidity']   = $humidity;
                       }
                      

                      
                 ?>
               
            <?php 
            echo "<pre>"; 
            //print_r ( $date_array );
            //print_r ( $value_array );
            echo "</pre>"; 
            ?>

             <div style="width:auto; height: 600px;">
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
                          label: 'Papier temperatuur in Celsius',
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
                                echo "y: '" . $value_array[ $date ]['media_temp'] . "'},";  
                                        }
                                ?>   
                                ],
                          }, 
                        
                          {
                          label: 'Printkop temperatuur in Celsius',
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
                                echo "y: '" . $value_array[ $date ]['head_temp'] . "'},";  
                                }
                                ?>   
                                ],                          
                          } 
                        ]
                        },
                        options: {
                            plugins: {
                              title: {
                                text: 'Printer sensor grafiek',
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
                  <br>
                  <h3>Dauwpunt</h3>
                  <p>In onderstaande grafiek kan je zien wat het dauwpunt is bij een bepaalde luchttemperatuur & luchtvochtigheid. 
                    <br>Als de mediatemperatuur de dauwpunt temp bereikt dan zal er waterdamp uit de lucht condenseren op de papierrol. <br>
                    Overigens is dit alleen als de rol echt heel koud is en de luchtvochtigheid erg hoog, bijvoorbeeld doordat er 20 mensen binnenkomen.</p>
                    <p>Voorbeeld: als de kamertemperatuur 21 graden is en in de grafiek hierboven lees je af dat de relatieve luchtvochtigheid 47% is,<br>
                     dan kijk je bij 21 graden, wat de waarde is tussen de bruine (40%) en groene (50%) lijn, en daar lees je het dauwpunt af.<br>
                      Dat is ongeveer 8 a 9 graden. Als de papierrol kouder wordt dan 8 graden dan zal er vocht op condenseren en kan de folie vastplakken.. <br>
                      In een ruimte waar de hele tijd verwarming is zal het niet zo gauw gebeuren dat de temperatuur van het papier veel kouder wordt dan de kamertemperatuur. <br>
                      Maar stel je slaat de dozen papier op in een niet verwarmde ruimte in de winter, dan kan het zijn dat een nieuwe rol veel kouder is dan de kamertemperatuur.<br> 
                      Dan is het dus aan te raden de doos papier vast in een warmere ruimte te zetten, voordat deze gebruikt wordt.
                    </p>
                    <br>
                  <img src="img/dauwpunt.svg.png" width="500">
                


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