  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");

  $event_id    = $_SESSION['event_id'];
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }
  $date_time  = mysqli_real_escape_string( $cn, $_REQUEST['date_time']);
  $end_time_hour = "00"; //init

 
         
  ?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item">Set kiezer</li>
        
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
                      <h2 class="text-white text-uppercase">Set kiezer</h2>
                    </div>
                    <div class="card-body">


                    <?php
                    $set_id_stored      = ""; //init
                    $start_date_stored  = "";
                    $end_date_stored    = "";
                    $start_time_stored  = "";
                    $end_time_stored    = "";
                    // bepaal met de date_time of er al een set_id opgeslagen is
                    //echo "datetime = " . $date_time;
                    $date_time_splitted = explode ( " " , $date_time);
                    $start_date = $date_time_splitted[0];
                    $start_time = $date_time_splitted[1];
                    //echo "start_date = " . $start_date ."<br>";
                    //echo "start_time = " . $start_time ."<br>";
                    $sql =   "SELECT *
                                FROM photobooth_set_planner
                               WHERE event_id   = '" . $_SESSION['event_id'] . "' 
                                 AND start_date = '" . $start_date           . "' AND
                                     start_time = '" . $start_time           . "'";

                    $result               = mysqli_query( $cn , $sql );
                    
                    if ( $result->num_rows > 0 ) {
                      $return	              = mysqli_fetch_object ( $result );
                      $set_id_stored        = $return->set_id;    
                      if ( isset ($return->start_date)) { $start_date_stored    = $return->start_date; }
                      if ( isset ($return->end_date))   { $end_date_stored      = $return->end_date;   }
                      if ( isset ($return->start_time)) { $start_time_stored    = $return->start_time; }
                      if ( isset ($return->end_time))   { $end_time_stored      = $return->end_time;   }
                    }
                    
                    if ( $set_id_stored =="" )
                      {
                      // nog geen record opgeslagen, kies end_date, end_time, start_time
                      
                      $end_date_time    = date("Y-m-d H:i:s",strtotime("+1 hour",strtotime( $date_time )));
                      $end_date         = date("Y-m-d",strtotime($end_date_time));   
                      $end_time         = date("H:i:s",strtotime($end_date_time));
                      $end_time_hour    = date("H",strtotime($end_date_time));
                      }
                    else {
                      $end_date    = $end_date_stored;
                      $start_time  = $start_time_stored;
                      $end_time    = $end_time_stored;
                      $end_date_time = $end_date . " " . $end_time;
                    }  
                    ?>
                    <script>
                     function checkIfVerhuur(selectObject){
                     
                      let set_array = [
                        <?php
                        $sql =   "SELECT *
                                    FROM photobooth_set
                                    WHERE event_id = '" . $_SESSION['event_id']. "'
                                ORDER BY set_id ASC";
                        
                        $result = mysqli_query( $cn , $sql );
                        while ( $return	= mysqli_fetch_object ( $result ) )
                            {
                            $set_id             = $return->set_id;
                            $set_price_print    = $return->set_price_print;
                            ?>
                            <?php echo "[" . $set_id . "," . $set_price_print . "],"; 
                            }
                            ?>
                          
                      ];
                      var set_id      = selectObject.value;  
                      console.log ( " gekozen set_id = " + set_id );
                      // loop the outer array
                      for (let i = 0; i < set_array.length; i++) {
                          // get the size of the inner array
                          var innerArrayLength = set_array[i].length;
                          // loop the inner array
                          for (let j = 0; j < innerArrayLength; j++) {
                              //console.log('[' + i + ',' + j + '] = ' + set_array[i][j]);
                              if ( set_array[i][j] == set_id )
                              {
                                //console.log ( " set_price_print = " + set_array[i][1] );
                                var set_price_print = set_array[i][1];
                              }

                          }
                      }
                      
                      
                      console.log ( " set_price_print = " + set_price_print );
                      if ( set_price_print == 0)
                      {
                        
                        alert( "De gekozen set is een Verhuur set met losse verkoopprijs = 0 en tijdsduur 4 uur, zet de begintijd bijvoorbeeld op 19:00 en de eindtijd = 23:00. De afgesproken Verhuur prijs voor 4 uur wordt doorberekend aan de bar. ");
                        var startTimeSelect = document.getElementById("start_time");
                        var endTimeSelect   = document.getElementById("end_time");
                        var endDateSelect   = document.getElementById("end_date");
                        startTimeSelect.value  = "20:00:00";
                        endTimeSelect.value    = "22:00:00";
                        var startDate = "<?php echo $start_date; ?>";
                        console.log( "start date=" + startDate );
                        endDateSelect.value    = startDate; // eind datum is de startdatum
                        
                      }
                     }
                      function endDateChanged(selectObject)
                      {
                        var end_date      = selectObject.value;  
                        var start_date    ="<?php echo $start_date;?>";
                        var end_time_hour ="<?php echo $end_time_hour;?>";
                        var endTimeSelect = document.getElementById("end_time");
                        var h = 0;
                        var hour = 0;

                        if ( end_date == start_date )
                          {
                            // - als de end_date = start_date ligt dan toon je een select van de start_time + 1 uur tot 00:00 uur
                            console.log("end_date = start_date");

                            // verwijder eerst bestaande opties
                            for ( h = 0; h < 24; h ++)
                            {
                              endTimeSelect.remove('00:00:00');  
                            }

                            for ( h = end_time_hour; h < 24; h ++)
                            {
                              console.log ( "h=" + h);
                              if ( h < 10 ) { hour = "0" + h + ":00:00"; } else { hour = h  + ":00:00";}

                              var newOption = document.createElement("option");
                              newOption.text = hour;
                              endTimeSelect.add(newOption);
                                  
                            };

                          }
                        else {
                          // - als de end_date 1 dag na de start_date ligt dan kan je een end_time van 00:00 uur tot 23:00 uur tonen
                          console.log("end_date > start_date");
                        
                          // verwijder eerst bestaande opties
                          for ( h = 0; h < 24; h ++)
                          {
                            endTimeSelect.remove('00:00:00');  
                          }
                          
                          
                          
                          for ( h = 0; h < 24; h ++)
                          {
                                if ( h < 10 ) { hour = "0" + h + ":00:00"; } else { hour = h  + ":00:00";}

                                var newOption = document.createElement("option");
                                newOption.text = hour;
                                endTimeSelect.add(newOption);
                                 
                          };
                        } 
                      }
                    </script>
                    <form action="page_set_planner.php" action="post">
                        <input type="hidden" name="mode" value="save_set_with_date_time">
                        <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
                        <table class="table table-responsive-sm table-bordered table-striped table-sm">
                            <thead>
                                <th>start datum</th>    
                                <th>start tijd</th>    
                                <th>eind datum</th>    
                                <th>eind tijd</th>    
                            <tr>
                                <td><?php echo $start_date ; ?></td>
                                <td>
                                  <select name="start_time" id="start_time">
                                   <?php 
                                   for ( $h = 0; $h < 24; $h ++ ) {
                                      if ( $h < 10 )
                                      {
                                        $option_1 = "0" . $h . ":00:00"; 
                                        $option_2 = "0" . $h . ":30:00"; 

                                      } else {
                                        $option_1 = $h . ":00:00";
                                        $option_2 = $h . ":30:00";
                                      }
                                      
                                      ?><option value="<?php echo $option_1; ?>"
                                        <? if ( $option_1 == $start_time ) { echo " selected"; } ?>
                                        ><?php echo $option_1; ?></option>
                                        <option value="<?php echo $option_2; ?>"
                                        <? if ( $option_2 == $start_time ) { echo " selected"; } ?>
                                        ><?php echo $option_2; ?></option>
                                        <?php
                                        }
                                    ?>
                                  </select>  
                                
                                </td>
                                <td>
                                   <select name="end_date" id="end_date" onChange="endDateChanged(this)">
                                   <?php 
                                   // start vanaf de end_date + 14 dagen
                                   for ( $d = 0; $d < 14; $d ++ ) 
                                    {
                                    $end_date_option =  date ("Y-m-d", strtotime( "+ $d day", strtotime($end_date_time)));

                                    ?><option value="<?php echo $end_date_option; ?>"
                                        <? if ( $end_date_option == $end_date ) { echo " selected"; } ?>
                                        ><?php echo $end_date_option; ?></option><?php
                                        }
                                    ?>
                                  </select>  
                                
                                </td>
                                <td>
                                  <?php 
                                  if ( substr($end_time_hour, 0,1) == '0' ) 
                                    { 
                                    $end_time_hour = substr($end_time_hour, 1,1);
                                    }; 
                                  ?>
                                <select name="end_time" id="end_time">
                                   <?php 
                                   // start vanaf de start_time + 1 uur
                                  
                                   for ( $h = $end_time_hour; $h < 24; $h ++ ) {
                                      if ( $h < 10 )
                                      {
                                        $option_1 = "0" . $h . ":00:00"; 
                                        $option_2 = "0" . $h . ":30:00"; 
                                      } else {
                                        $option_1 = $h . ":00:00";
                                        $option_2 = $h . ":30:00";
                                      }
                                      
                                      ?><option value="<?php echo $option_1; ?>"
                                        <? if ( $option_1 == $end_time ) { echo " selected"; } ?>
                                        ><?php echo $option_1; ?></option>

                                        <option value="<?php echo $option_2; ?>"
                                        <? if ( $option_2 == $end_time ) { echo " selected"; } ?>
                                        ><?php echo $option_2; ?></option>
                                        
                                        
                                        <?php
                                        }
                                    ?>
                                  </select>  
                                </td>
                            </tr>
                          
                            <tr>
                                <td>Kies set</td>
                                <td><select name="set_id" onChange="checkIfVerhuur(this)">
                                <?php
                                $sql =   "SELECT *
                                            FROM photobooth_set
                                            WHERE event_id = '" . $_SESSION['event_id']. "'
                                        ORDER BY set_id ASC";
                                
                                $result = mysqli_query( $cn , $sql );
                                while ( $return	= mysqli_fetch_object ( $result ) )
                                    {
                                    $set_id             = $return->set_id;
                                    $set_name           = $return->set_name;
                                    $is_base_set        = $return->is_base_set;
                                    ?><option value="<?php echo $set_id; ?>"
                                    <? if ( $set_id == $set_id_stored ) { echo " selected"; } ?>
                                    ><?php echo $set_name . " - " . $set_id; ?></option><?php
                                    }
                                ?>
                                </select></td>
                            </tr>    
                        </select>
                        </table>
                        <button class="btn btn-info">Opslaan</button>
                    </form>
                    
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