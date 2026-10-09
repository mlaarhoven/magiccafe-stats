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
                     var set_array = [
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

                     // verhuur set = set met losse verkoopprijs ( set_price_print ) 0
                     function isVerhuurSet(set_id)
                     {
                        for (let i = 0; i < set_array.length; i++) {
                          if ( set_array[i][0] == set_id ) { return set_array[i][1] == 0; }
                        }
                        return false;
                     }

                     function checkIfVerhuur(selectObject){
                      var set_id      = selectObject.value;
                      console.log ( " gekozen set_id = " + set_id );
                      if ( isVerhuurSet(set_id) )
                      {
                        
                        alert( "De gekozen set is een Verhuur set met losse verkoopprijs = 0 en tijdsduur 4 uur, de eindtijd is automatisch op begintijd + 4 uur gezet. De afgesproken Verhuur prijs voor 4 uur wordt doorberekend aan de bar. ");
                        setVerhuurEndTime();
                      }
                     }

                     // verhuur set: eindtijd = begintijd + 4 uur (over middernacht → eind datum = volgende dag)
                     function setVerhuurEndTime()
                     {
                        var startTimeSelect = document.getElementById("start_time");
                        var endTimeSelect   = document.getElementById("end_time");
                        var endDateSelect   = document.getElementById("end_date");
                        var startDate       = "<?php echo $start_date; ?>";

                        var parts = startTimeSelect.value.split(":");
                        var d = new Date(startDate + "T00:00:00");
                        d.setHours(parseInt(parts[0], 10) + 4, parseInt(parts[1], 10), 0, 0);

                        var pad     = function(n) { return ( n < 10 ? "0" : "" ) + n; };
                        var endDate = d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate());
                        var endTime = pad(d.getHours()) + ":" + pad(d.getMinutes()) + ":00";
                        console.log( "verhuur eind = " + endDate + " " + endTime );

                        // eind datum zetten en de eindtijd-lijst voor die datum opnieuw opbouwen
                        endDateSelect.value = endDate;
                        endDateChanged(endDateSelect);

                        // de lijst voor de startdatum begint pas bij het oorspronkelijke tijdslot + 1 uur;
                        // is de begintijd vervroegd en ontbreekt de eindtijd, voeg hem dan alsnog toe
                        endTimeSelect.value = endTime;
                        if ( endTimeSelect.value !== endTime )
                        {
                          endTimeSelect.add(new Option(endTime, endTime), 0);
                          endTimeSelect.value = endTime;
                        }
                     }

                     // begintijd gewijzigd terwijl er een verhuur set gekozen is → eindtijd meeschuiven
                     function startTimeChanged()
                     {
                        var setSelect = document.getElementsByName("set_id")[0];
                        if ( setSelect && isVerhuurSet(setSelect.value) ) { setVerhuurEndTime(); }
                     }
                      function endDateChanged(selectObject)
                      {
                        var end_date      = selectObject.value;  
                        var start_date    ="<?php echo $start_date;?>";
                        var end_time_hour ="<?php echo $end_time_hour;?>";
                        var endTimeSelect = document.getElementById("end_time");
                        var h = 0;
                        var start_h = 0;

                        // verwijder eerst alle bestaande opties (48 per dag: hele en halve uren)
                        endTimeSelect.options.length = 0;

                        if ( end_date == start_date )
                          {
                            // - als de end_date = start_date ligt dan toon je een select van de start_time + 1 uur tot 00:00 uur
                            console.log("end_date = start_date");
                            start_h = parseInt(end_time_hour, 10);
                          }
                        else {
                          // - als de end_date 1 dag na de start_date ligt dan kan je een end_time van 00:00 uur tot 23:30 uur tonen
                          console.log("end_date > start_date");
                          start_h = 0;
                        }

                        for ( h = start_h; h < 24; h ++)
                        {
                          var hh = ( h < 10 ) ? "0" + h : "" + h;
                          endTimeSelect.add(new Option(hh + ":00:00", hh + ":00:00"));
                          endTimeSelect.add(new Option(hh + ":30:00", hh + ":30:00"));
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
                                  <select name="start_time" id="start_time" onChange="startTimeChanged()">
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