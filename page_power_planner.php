  <?php 
  error_reporting (E_ALL ^ E_NOTICE); 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  include ("inc/shelly_functions.php"); 
  global $day_array;
  $event_id = $_SESSION['event_id'];

  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }
  $mode = ""; //init
 if ( isset ( $_REQUEST['mode'] )) {
    $mode = mysqli_real_escape_string( $cn, $_REQUEST['mode']);
 }
  $filled   = check_if_table_is_filled();

  global $event_array;
  if ( $_SESSION['client_id'] == 1 || $_SESSION['client_id'] == 2 || $_SESSION['client_id'] == 3) {
       # admins mogen alle events zien 
     
      $event_array = array();
       $sql = "SELECT event_id
                 FROM photobooth_event_ids";
       $result             = mysqli_query( $cn , $sql );
       while ( $return     = mysqli_fetch_object ( $result ) ) {
            $event_id   = $return->event_id;
            array_push ( $event_array, $event_id );
        }
  } else {
    $event_array = array($_SESSION['event_id']);
  }

  get_event_data ();


  if ( $filled == 0 ) { fill_table(); }
  get_all_days_of_week ();
  
  if ( $mode == 'turn_off_planning'){
    $sql = "UPDATE photobooth_power_planner_used
               SET use_power_planner = '0',
                   deactivated_by = '" . $_SESSION['client_name'] . "',
                   deactivated_at = NOW()
             WHERE event_id    = '" . $_SESSION['event_id'] . "'";
    $result = mysqli_query( $cn , $sql );
  }
  if ( $mode == 'turn_on_planning'){
    $sql = "UPDATE photobooth_power_planner_used
               SET use_power_planner = '1',
                   reactivated_by = '" . $_SESSION['client_name'] . "',
                   reactivated_at = NOW()
             WHERE event_id    = '" . $_SESSION['event_id'] . "'";
    $result = mysqli_query( $cn , $sql );
  }
  function check_if_table_is_filled() {
    global $cn;
    $sql = "SELECT *
              FROM photobooth_power_planner
             WHERE event_id    = '" . $_SESSION['event_id'] . "'";
    $result = mysqli_query( $cn , $sql );
    $aantal = mysqli_num_rows ( $result );

    if ( $aantal == 0 ) {
        return 0;
    } else {
        return 1;
    }
  }


  function   get_event_data () {
    global $cn;
    global $event_array; 
    global $event_data_array;
    
    // init vars
    $payter_api_key_prod = "";
    $payter_terminal_id  = "";
   
  

    foreach ($event_array as $event_id) {
    $sql = "SELECT 
                   event_name,
                   payter_terminal_id
            FROM event
            WHERE event_id = '" . $event_id . "'";
    $result             = mysqli_query( $cn , $sql );
    if ( $result ) {
      while ( $return     = mysqli_fetch_object ( $result ) ) {
        if ( isset ( $return->event_name)  )          { $event_name           = $return->event_name;          }
        if ( isset ( $return->payter_terminal_id)  )  { $payter_terminal_id   = $return->payter_terminal_id;  }
        if ( isset ( $return->payter_api_key_prod)  ) { $payter_api_key_prod  = $return->payter_api_key_prod; }
       
          $event_data_array[$event_id]['event_name']          = $event_name;
          $event_data_array[$event_id]['payter_terminal_id']  = $payter_terminal_id;
          $event_data_array[$event_id]['payter_api_key_prod'] = $payter_api_key_prod;
          }
        } // end if result
    }
}

function get_power_planner_used ( $event_id )
{
    global $cn;
    $sql = "SELECT use_power_planner
              FROM photobooth_power_planner_used
             WHERE event_id = '" . $event_id . "'";
    $result             = mysqli_query( $cn , $sql );
    $return             = mysqli_fetch_object ( $result );
    $use_power_planner  = $return->use_power_planner;
    return $use_power_planner;
}

function status_according_to_power_planner ( $event_id )
{
    global $cn;
    $day_of_week = date ("w"); //w - A numeric representation of the day (0 for Sunday, 6 for Saturday)
    $time        = date("H:i:s");
    $sql2 = "SELECT power
                  FROM photobooth_power_planner
                 WHERE event_id    = '" . $event_id . "'
                   AND day_of_week = $day_of_week
                   AND '" . $time . "' BETWEEN start_time 
                   AND end_time";
    //echo $sql2 . "<br>";
    $result2 = mysqli_query( $cn , $sql2 );
    $return2 = mysqli_fetch_object ( $result2 );
    $power   = $return2->power;  
    return $power;
}

function get_shelly_status ( $event_id ) {
    $shelly_device_status_array   = get_one_shelly_status ( $event_id );
    $return_array['online']       = $shelly_device_status_array[$event_id]['online'];
    $return_array['ingeschakeld'] = $shelly_device_status_array[ $event_id]['switch:0'];
    return $return_array;
}

  function fill_table () {
    global $cn;
    for ( $part = 0; $part < 48; $part ++ )
    {
        $start = "00:00";
        
        $start_time = strtotime($start . ' + ' . $part * 30       . ' minute');
        $end_time   = strtotime($start . ' + ' . ($part + 1) * 30 . ' minute - 1 second');
        $start_time2 = date( "H:i:s", $start_time );
        $end_time2   = date( "H:i:s", $end_time );
        for ( $day_of_week = 0; $day_of_week < 7; $day_of_week ++ ) {

           $sql2 = "INSERT INTO photobooth_power_planner
                            SET event_id    = '" . $_SESSION['event_id']    . "',
                                day_of_week = '" . $day_of_week             . "',
                                start_time  = '" . $start_time2              . "',
                                end_time    = '" . $end_time2                . "'";
           $result2 = mysqli_query( $cn , $sql2 );
        
        }
   }
 }

function get_all_days_of_week () {
   global $cn;
   global $day_array;
   $sql = "SELECT *
              FROM photobooth_power_planner
             WHERE event_id    = '" . $_SESSION['event_id'] . "'
             ORDER BY day_of_week ASC";
  //echo $sql;
  $result = mysqli_query( $cn , $sql );
   while ( $return	= mysqli_fetch_object ( $result ) ) {
            $day_of_week  = $return->day_of_week;
            $start_time   = $return->start_time;
            $end_time     = $return->end_time;
            $power        = $return->power;
            $day_array[$day_of_week][$start_time][$end_time] = $power;
           
    }
}
 
function check_if_planners_is_in_use() {
    global $cn;
    $sql = "SELECT use_power_planner
              FROM photobooth_power_planner_used
             WHERE event_id    = '" . $_SESSION['event_id'] . "'";
    $result = mysqli_query( $cn , $sql );
    $return	= mysqli_fetch_object ( $result );
    $use_power_planner  = $return->use_power_planner;
    return $use_power_planner;
  }


  ?>
  <style>
  .table_planner {
      margin:0;
      padding:1px;
      border:0;
      font-size: 10px;
  }
  .table_planner td {
      font-size: 10px;
      border: 1px solid;
  }
  .power_on {
      background: #0f0;
  }
  .power_off {
      background: #f00;
  }
  .huidig {
    border: 4px solid ;
    background: #f4f;
  }

</style>


<script>
    function tdclick( day_of_week, start_time, end_time, power)
        {
            //console.log('td clicked day_of_week = ' + day_of_week + ' start_time= '+ start_time + ' end_time=' + end_time + ' power=' + power);
            // toggle on to off and off to on
            $.post( "save_power_setting.php",  { day_of_week: day_of_week, start_time:start_time, end_time:end_time, power:power}, function( data ) 
                {
                var obj = jQuery.parseJSON( data );
                });
                // reload window
                location.reload();
            

        }
</script>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Power planner</li>
        
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
              <div class="col-sm-12 col-md-6 col-lg-6">
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Power planner</h2>
                    </div>
                    
                    <div class="card-body">
                    <?php
                    $event_id = $_SESSION['event_id'];
                    $shelly_result      = get_shelly_status ( $event_id );
                    $power_planner_use  = get_power_planner_used ( $event_id ) ;
                    $power              = status_according_to_power_planner ( $event_id );
                    ?>
                    <h2>Power planner (fotokast aan en uit)</h2>
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                         <tr>
                            <td>Powerplanner aan/uit zetten</td>
                            <td>
                              <?php
                                if ( $power_planner_use == 1 ) { 
                                    ?><a href="page_power_planner.php?mode=turn_off_planning" class="btn btn-info">Zet planner uit</a><?php 
                                } else  { 
                                    ?><a href="page_power_planner.php?mode=turn_on_planning" class="btn btn-success">Zet planner aan</a><?php
                                } ?>
                            </td>
                          </tr>
                          <tr>
                            <td>Remote Switch online</td>
                            <td><?php 
                                if ( $shelly_result['online'] > 0 ) { 
                                    ?><span class="badge rounded-pill  btn-success">Fotokast kan op afstand aan- en uitgezet worden</span><?php
                                } else { 
                                    ?><span class="badge rounded-pill btn-danger">Fotokast kan NIET op afstand aan en uit gezet worden</span><?php
                                } ?>
                            </td>
                            
                        </tr>
                        <tr>
                            <td>Power planner </td>
                            <td><?php 
                                if ( $power_planner_use == 0 ) { 
                                    ?><span class="badge rounded-pill  btn-info">Planner is niet in gebruik</span><?php
                                } else { 
                                    ?><span class="badge rounded-pill btn-success">Planner is in gebruik</span><?php
                                } ?></td>
                        </tr>
                        <tr>
                            <td>Power volgens planner</td>
                            <td><?php 
                                if ( $power == 1 ) 
                                    { 
                                    ?><span class="badge rounded-pill btn-success">Moet aan staan</span><?php
                                } else { 
                                        ?><span class="badge rounded-pill btn-info">Moet uit staan</span><?php
                                    
                                } ?>
                            </td>
                            
                        </tr>
                        <tr>
                            <td>Power status werkelijk</td>
                            <td><?php 
                                if ( $shelly_result['ingeschakeld'] == 1 ) 
                                    { 
                                    ?><span class="badge rounded-pill btn-success">Fotokast staat aan</span><?php
                                } else { 
                                        ?><span class="badge rounded-pill btn-info">Fotokast staat uit</span><?php
                                    
                                } ?>
                            </td>
                            
                        </tr>
                    </table>
                        
                    <p>Standaard staat de planner <strong>aan</strong>.<br> 
                    Voor testen of voor afwijkende momenten kan je de planner uitzetten en de kast <a href="https://stats.magicshoot.nl/page_shelly_control.php">handmatig</a> aan en uitzetten.
                    <br><br>Als je de fotokast handmatig aan of uit zet, of als je de powerplanner handmatig uit zet, dan wordt de powerplanner na 8 uur weer automatisch ingeschakeld.
                  </p>
                  
                        
                      
                    <h2>Planning</h2>
                    <p>groen = aan, rood = uit. Klik op een vakje om te wisselen tussen aan of uit.<br>Let op: de week start op <strong>zondag</strong>!</p>
                    <table class="table_planner">
                        <tr>
                            <td><strong>starttijd</strong></td>
                            <td><strong>zondag<strong></td>
                            <td><strong>maandag<strong></td>
                            <td><strong>dinsdag<strong></td>
                            <td><strong>woensdag<strong></td>
                            <td><strong>donderdag<strong></td>
                            <td><strong>vrijdag<strong></td>
                            <td><strong>zaterdag<strong></td>
                            
                        </tr> 
                        <?php 
                          

                            for ( $part = 0; $part < 48; $part ++ )
                            {
                                $start = "00:00";
                                
                                $start_time = strtotime($start . ' + ' . $part * 30       . ' minute');
                                $end_time   = strtotime($start . ' + ' . ($part + 1) * 30 . ' minute - 1 second');
                                $start_time2 = date( "H:i:s", $start_time );
                                $start_time3 = date( "H:i", $start_time );
                                $end_time2   = date( "H:i:s", $end_time );


                                ?>
                                <tr>
                                    <td align="center"><?php echo $start_time3;?></td>
                                    <?php
                                    for ( $day_of_week = 0; $day_of_week < 7; $day_of_week ++ )
                                        {
                                        $power = $day_array[$day_of_week][$start_time2][$end_time2];
                                        if ( $power == 0 ) { $new_power = 1; }
                                        if ( $power == 1 ) { $new_power = 0; }
                                        $vars             = '"' . $day_of_week . '","' . $start_time2 .'","' .$end_time2 . '","' . $new_power . '"';
                                        $current_time     = time();
                                        $start_date_time  = strtotime ( date("Y") . "-" . date("m") . "-" . date("d") . " " . $start_time2);
                                        $end_date_time    = strtotime ( date("Y") . "-" . date("m") . "-" . date("d") . " " . $end_time2 );
                                        $day_of_week_now  = date('w') ;  
                                       
                                        if ( ($current_time > $start_date_time) && ($current_time < $end_date_time) && ($day_of_week_now) == $day_of_week ) {
                                          $border_current_time = "3";
                                        } else {
                                          $border_current_time = "1";
                                        }

                                        if ( $power == 1 ) {?><td class="power_on"  style="border: <?php echo $border_current_time; ?>px solid #000;"  onclick='tdclick(<?php echo $vars; ?>)';></td><?php }
                                        if ( $power == 0 ) {?><td class="power_off" style="border: <?php echo $border_current_time; ?>px solid #000;"  onclick='tdclick(<?php echo $vars; ?>)';></td><?php }
                                        }
                                    ?>
                                </tr>    
                                <?php    
                            }
                            ?>   
                           
                       
                          </table>
                          <br>
                          <h2>Cronjob</h2>
                        <p>De planner checkt iedere 10 minuten of de kast aan of uitgezet moet worden met een cronjob.<br>
                        Als je niet wilt wachten kan je de cronjob ook direct aanroepen met <a href="https://api.magicshoot.nl/cron/cron_power_planner_cafekasten.php">deze link</a>.
                          </p>
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