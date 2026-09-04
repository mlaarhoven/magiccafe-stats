 
  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/shelly_functions.php");
  include ("inc/photo_thumb_functions.php");
 // error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
 // ini_set('display_errors', '0');
  global $event_array, $cn, $timer;
  setlocale(LC_MONETARY, "nl_NL");   
  
  $timer = array();

  $time_over_all  = -microtime(true); 
  $time  = -microtime(true); 



// memcached testje

$memcache = new Memcached();
$memcache->addServer("localhost", 11211);
$memcachetime = 300; // 5 minuten






  if ( $_SESSION['client_role'] == 'admin' ) 
    {
       # admins mogen alle events zien 
       $event_array = array();
       $testmode_array = array();
       $sql = "SELECT event_id,
                      test_mode,
                      name
                 FROM photobooth_event_ids 
                 WHERE dashboard_show ='1'
                  ORDER BY 	dashboard_order_nr	 ASC";
       $result             = mysqli_query( $cn , $sql );
       while ( $return     = mysqli_fetch_object ( $result ) ) {
            $event_id    = $return->event_id;
            $test_mode   = $return->test_mode;
            $name        = $return->name;
            array_push ( $event_array, $event_id );
            $testmode_array[ $event_id ] = $test_mode;
            $event_data_array[$event_id]['event_name'] = $name; 
        }
      
    } else {
        echo "You must be logged in as admin to view this page!";
        exit;
  }

  $timer['determine_event_for_current_user'] = $time += microtime(true);
  $time = -microtime(true); 


  $mode = ""; //init
  if ( isset ( $_REQUEST['mode']) ) {
       // $mode     = mysqli_real_escape_string( $cn, $_REQUEST['mode']);  
       $mode = "force_refresh"; //bij de fast page altijd refreshen!
  } 

  # shelly status: alleen op refresh wordt de echte Shelly api aangeroepen; normaal wordt de status
  # per event_id uit photobooth_device_status gelezen (gevuld door refresh hieronder of door de cron
  # api/cron/cron_get_shelly_status.php)
  $shelly_device_status_array = array(); //init

  $cookie_name = "get_latest_shelly_status";
  $get_shelly_status = 'true'; //init
  // haal waarde uit cookie , indien cookie bestaat
  if(isset($_COOKIE[$cookie_name])) {
     $get_shelly_status = $_COOKIE[$cookie_name];
    }

    // haal alleen de live shelly status op als er op refresh gedrukt is en get_latest_shelly_status = true in cookie
    if ( $get_shelly_status == 'true' && $mode == "force_refresh" ) {
                $shelly_device_status_array = get_all_shelly_status ();
                save_shelly_status_to_db ( $shelly_device_status_array );
                $timer['cached_last_refreshed_at'] = date( "Y-m-d H:i:s ");
                $_SESSION['last_updated'] = $timer['cached_last_refreshed_at'];
    }
   $timer['get_all_shelly_status'] = $time += microtime(true);

   get_event_data ();
  
  ?>


     <style>
        .al-center {
            text-align: center;
        }
        
        .al-right {
            text-align: right;
        }

        #images { 
            display: block;  
        }
        .voorbeeld { 
            display:  block; 
            border: solid 2px #eee; 
            padding:  10px; 
            margin:  5px; 
            float: left; 
            width:  250px; 
            height:  auto;
        }
        
        .card {
            width: 400px;
            margin-right: 10px;
        }
        .error { color: #f00; font-weight: bold;}

        table.devicetable thead tr {
            background-color:#000; 
            color:#fff; font-size:11px; 
            font-weight: bold;
            font-family:sans-serif; 
        }
        
        .statustext {
            display: block;
            height: auto;
            border-radius: 4px;
            width: 45px;
            color: #fff;
            font-weight: bold;
            font-size: 10px;
            margin-top: 5px;
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .statuspill {
            display: block;
            height: 18px;
            border-radius: 50%;
            width: 18px;
            color: #fff;
            font-weight: bold;
            font-size: 10px;
            margin-top: 5px;
            padding-top: 2px;
            padding-bottom: 2px;
            align: center;
        }

        .statustext_black {
            display: block;
            vertical-align: top;
            height: 30px;
            border-radius: 4px;
            width: 75px;
            color: #000;
            font-weight: bold;
            font-size: 10px;
            margin-top: 0px;
            padding-top: 0px;
            padding-bottom: 0px;
        }
        .status_black {
            background-color: black;
        }
        .status_grey {
            background-color: grey;
        }
        .status_green {
            background-color: #1ad801ff;
        }
        .status_red {
            background-color: #E30613;
        }
        .status_blue {
            background-color: #36A9E1;
        }
        .status_purple {
        background-color: #9a077aff;
        }

        .btn-width{
            display: block;
            width: 130px;
        }
        .prints_red {
            color: #E30613;
            font-weight: bold;
        }
        .foto_session {
            display: block;
            width: 250px;
            float: left;
        }
    </style>

    <script>
        function setCookie(cname, cvalue, exdays) {
            const d = new Date();
            d.setTime(d.getTime() + (exdays*24*60*60*1000));
            let expires = "expires="+ d.toUTCString();
            document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
        }

        function getCookie(cname) {
            let name = cname + "=";
            let decodedCookie = decodeURIComponent(document.cookie);
            let ca = decodedCookie.split(';');
            for(let i = 0; i <ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == ' ') {
                c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
                }
            }
            return "";
        }

        function toggleCheckbox(element) {
            var status = element.checked;
            var exdays = 15;
            //alert ( "Checkbox clicked. Status: " + element.checked);
            // write cookie
            setCookie("get_latest_shelly_status", status, exdays);
            var status = getCookie("get_latest_shelly_status");
            console.log ("status in cookie is nu: " + status);
        }

    </script>

    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        
        <li class="breadcrumb-item"><a style="text-decoration:none; " href='page_dashboard_fast.php?mode=force_refresh'><span class="btn-sm btn-info">Refresh</span></a></li>
        <li class="breadcrumb-item"><a style="text-decoration:none; " href='recreate_thumbs.php' onclick="return confirm('Alle thumbs opnieuw aanmaken kan even duren, doorgaan?');"><span class="btn-sm btn-info">Recreate alle thumbs</span></a></li>
        <?php if (  isset ($shelly_device_status_array['error']) && $shelly_device_status_array['error']<>"") {
            ?> <li class="breadcrumb-item error">Error: <?php echo $shelly_device_status_array['error']; ?></li>   
        <?php } ?>
    </li>
    
      <!-- Breadcrumb Menu-->
      </ol>
    </div>

 </header>
 

<?php
# --- FUNCTIONS --- #


function   get_event_data () {
    global $cn;
    global $event_array, $event_data_array; 
    global $timer;
    $time = -microtime(true); 
    
    foreach ($event_array as $event_id) {
    $sql = "SELECT event_name,
                   payter_terminal_id,
                   anydesk_id
                   
              FROM event
             WHERE event_id = '" . $event_id . "'";
    $result             = mysqli_query( $cn , $sql );
    while ( $return     = mysqli_fetch_object ( $result ) ) {
        
        $event_name                 = $return->event_name;
        $payter_terminal_id         = $return->payter_terminal_id;
        $anydesk_id                 = $return->anydesk_id;
       
       // $event_data_array[$event_id]['event_name']          = $event_name;
        $event_data_array[$event_id]['payter_terminal_id']  = $payter_terminal_id;
        $event_data_array[$event_id]['anydesk_id']          = $anydesk_id;
        } 
    }
    $timer['get_event_data'] = $time += microtime(true);
}

function get_power_planner_used ( $event_id )
{
    global $cn;
    global $timer;
    $use_power_planner = 0; //init
    $time = -microtime(true); 
    $sql = "SELECT *
              FROM photobooth_power_planner_used
             WHERE event_id = '" . $event_id . "'";
    $result             = mysqli_query( $cn , $sql );
    if ( $result->num_rows > 0 ) {
        $return             = mysqli_fetch_object ( $result );
        $use_power_planner  = $return->use_power_planner;
    }
    $timer[$event_id]['get_power_planner_used'] = $time += microtime(true);
    return $use_power_planner;
}

function status_according_to_power_planner ( $event_id )
{
    global $cn;
    global $timer;
    $power = 0; //init
    $timertime = -microtime(true); 
    $day_of_week = date ("w"); //w - A numeric representation of the day (0 for Sunday, 6 for Saturday)
    $time        = date("H:i:s");
    $sql2 = "SELECT power
               FROM photobooth_power_planner
              WHERE event_id    = '" . $event_id . "'
                AND day_of_week = $day_of_week
                AND '" . $time . "' BETWEEN start_time 
                AND end_time";
    $result2 = mysqli_query( $cn , $sql2 );
    if ( $result2->num_rows > 0 ) {
        $return2 = mysqli_fetch_object ( $result2 );
        $power   = $return2->power;  
    } 
    $timer[$event_id]['status_according_to_power_planner'] = $timertime += microtime(true);
    return $power;
}


function get_payter_terminal_info ( $event_id )
{
    global $event_data_array; 
    global $cn;
    global $timer;
    $online = 0; //init

    $time = -microtime(true); 
    # haal de Payter CPS api_key en de url op
    $sql =    "SELECT cps_api_url, 
                      cps_api_key_prod
                 FROM photobooth_payter_auth";
    $result              = mysqli_query( $cn , $sql );
    $return	             = mysqli_fetch_object ( $result );
    $cps_api_url         = $return->cps_api_url;
    $cps_api_key_prod    = $return->cps_api_key_prod;
    
    # terminal id komt uit tabel: event
    $payter_terminal_id   =  $event_data_array[$event_id]['payter_terminal_id'];
    
    $url  = $cps_api_url . "/terminals/" . $payter_terminal_id;
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $headr = array();
    $headr[] = 'Content-length: 0';
    $headr[] = 'Content-type: application/json';
    $headr[] = 'Authorization: CPS apikey="'. $cps_api_key_prod . '"';
    curl_setopt($curl, CURLOPT_HTTPHEADER,$headr);
    $data = curl_exec($curl);
   
    $myData = json_decode($data, true);
    if ( isset($myData['online']) ) { $online = $myData['online'];}
    $timer[$event_id]['get_payter_terminal_info'] = $time += microtime(true);
    if ( $online == 1) { return 1;} else { return 0;}

}


function get_device_status ( $event_id )
{
    global $cn;
    global $timer;
    $return_array = array();
    $time = -microtime(true); 
    $sql       = "SELECT *
                    FROM photobooth_device_status
                   WHERE event_id = '" . $event_id . "'";
    $result    = mysqli_query( $cn , $sql );
    if ( $result->num_rows > 0 ) {
        $return	   = mysqli_fetch_object ( $result );
        $return_array['modem_heartbeat']        = $return->modem_heartbeat;
        $return_array['pc_heartbeat']           = $return->pc_heartbeat;
        $return_array['shelly_online']          = $return->shelly_online;
        $return_array['shelly_out_0']           = $return->shelly_out_0;
        $return_array['shelly_out_1']           = $return->shelly_out_1;
    }
    $timer[$event_id]['get_device_status']  = $time += microtime(true);
    return $return_array;
}

function get_print_counter ( $event_id ) {
    global $cn;
    global $timer;
    $time = -microtime(true);
      # haal COUNTER status op
     $sql1   = "SELECT log_details, log_created
                   FROM log
                  WHERE event_id        = '" . $event_id . "'
                    AND log_action      = 'counter'
               ORDER BY log_id DESC
                  LIMIT 0,1";
    $result1                   = mysqli_query( $cn , $sql1 );
    if ( $result1->num_rows > 0 ) {
        $return1                    = $result1->fetch_object();
        $print_counter              = $return1->log_details;

    }
    $return = $print_counter;
    $timer[$event_id]['get_print_counter'] = $time += microtime(true);
    return $return;
}


function get_nr_of_prints_last_night ( $event_id )
{
    global $cn;
    global $timer;
    $time = -microtime(true);
    $nr_payter = 0; //init
    $startDate = time();
    $afgelopen_nacht_start =date('Y-m-d', strtotime('-1 day', $startDate)) ." 07:01:00";
    $afgelopen_nacht_eind = date('Y-m-d' ). " 07:00:00";       
  
    if( $event_id == 35 ) {
    $sql = "SELECT event_id, COUNT(video_id) AS nr_payter
        FROM video
        WHERE DATE(record_created)=CURRENT_DATE()
        AND event_id=$event_id
        GROUP BY event_id";
    } else if( $event_id == 83) {
    $sql = "SELECT event_id, COUNT(photo_id) AS nr_payter
        FROM photo
        WHERE DATE(datetime)=CURRENT_DATE()
        AND event_id=$event_id
        GROUP BY event_id";
    } else {
    $sql   ="SELECT payterOK.event_id, 
                    YEAR(payterOK.txnTimestampLocal) as jaar, 
                    MONTH(payterOK.txnTimestampLocal) AS maand, 
                    COUNT(payterOK.transactionId) as nr_payter,
                    SUM(payterOK.committedAmount)/100 AS amount_payter
               FROM payterOK
              WHERE	payterOK.txnTimestampLocal BETWEEN '". $afgelopen_nacht_start."' AND '". $afgelopen_nacht_eind."'
                AND payterOK.event_id = $event_id 
           GROUP BY payterOK.event_id";
    }
    $result = mysqli_query( $cn , $sql );
    if ( $result->num_rows > 0 ) {
        $return        = $result->fetch_object();
        $nr_payter     = $return->nr_payter;
    }
    $timer[$event_id]['get_nr_of_prints_last_night'] = $time += microtime(true);
    return ( $nr_payter );

}
function render_timer_debug ( $timer )
{
    global $event_array;
    $rows = array();

    # alle top-level tijdmetingen (float/int), losse debug-tellers en detail-arrays overslaan
    $skip_keys = array( 'cached_last_refreshed_at', 'time_over_all', 'get_all_shelly_status_detail',
                         'photo_thumb_on_the_fly_count', 'photo_thumb_on_the_fly_time' );
    foreach ( $timer as $key => $value ) {
        if ( in_array ( $key, $skip_keys ) ) { continue; }
        if ( is_numeric ( $value ) ) {
            $rows[$key] = $value;
        }
    }

    # per-kast functies (get_device_status, get_payter_terminal_info, etc) optellen over alle kasten
    $per_event_totals = array();
    $per_event_counts = array();
    foreach ( $timer as $key => $value ) {
        if ( is_array ( $value ) && in_array ( $key, $event_array ) ) {
            foreach ( $value as $func => $seconds ) {
                if ( !isset ( $per_event_totals[$func] ) ) { $per_event_totals[$func] = 0; $per_event_counts[$func] = 0; }
                $per_event_totals[$func] += $seconds;
                $per_event_counts[$func]++;
            }
        }
    }
    foreach ( $per_event_totals as $func => $seconds ) {
        $rows[$func . " (opgeteld over " . $per_event_counts[$func] . " kasten)"] = $seconds;
    }

    arsort ( $rows );

    echo "<h4>Debug: tijdsduur per onderdeel</h4>";
    echo "<table class='table table-sm table-bordered' style='max-width:700px;'>";
    echo "<thead><tr><th>Onderdeel</th><th class='al-right'>Tijd (sec)</th></tr></thead><tbody>";
    foreach ( $rows as $label => $seconds ) {
        echo "<tr><td>" . htmlspecialchars ( $label ) . "</td><td class='al-right'>" . number_format ( $seconds, 3 ) . "</td></tr>";
    }
    echo "</tbody></table>";

    if ( isset ( $timer['get_all_shelly_status_detail'] ) ) {
        $d = $timer['get_all_shelly_status_detail'];
        echo "<p><strong>Shelly API detail:</strong> " . $d['curl_calls'] . " api-call(s), "
           . number_format ( $d['curl_time'], 3 ) . " sec in curl_exec (het echte netwerk-verkeer), "
           . number_format ( $d['sleep_time'], 3 ) . " sec verplichte usleep() tussen de batches in.</p>";
    }

    if ( isset ( $timer['photo_thumb_on_the_fly_count'] ) && $timer['photo_thumb_on_the_fly_count'] > 0 ) {
        echo "<p><strong>Thumbnails on-the-fly aangemaakt:</strong> " . $timer['photo_thumb_on_the_fly_count'] . " stuks, "
           . number_format ( $timer['photo_thumb_on_the_fly_time'], 3 ) . " sec totaal (foto's die nog geen thumb hadden).</p>";
    }

    if ( isset ( $timer['time_over_all'] ) ) {
        echo "<p><strong>Totale laadtijd pagina: " . number_format ( $timer['time_over_all'], 3 ) . " sec</strong></p>";
    }
}
  # --- END FUNCTIONS --- #
  ?>


  <!-- reload page every 30 seconds -->
  <script>
    setTimeout(function(){
        window.location.reload(1);
    }, 300000);
    </script>
  <!-- end reload page every 30 seconds -->
   
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">
                 <!-- /.col-->
                 <div class="col-sm-12 col-md-3 col-lg-3">
                 
                 <div class="row">  
                  
                      <table class="table  table-bordered table-striped table-sm devicetable">
                        <thead>
                            <tr> 
                                <td class="al-center">ON</td>
                                <td>SELECT</td>
                                <td class="al-center">IMG</td>
                                <td class="al-center">LAST NIGHT</td>
                                <td class="al-center">PR LEFT</td>
                                <td class="al-center">ERRORS</td>
                            </tr>
                        </thead>    
                        <tbody>
                             <?php
                                $timer_events_loop_start = -microtime(true);
                                foreach ($event_array as $event_id) {
                                        # init
                                        $shelly_online              = 0;
                                        $shelly_state_0             = 0;
                                        $shelly_state_1             = 0;
                                        $status                     = "OK";
                                        $errors                     = array();
                                        $class_status               = "status_green";
                                        $printcounter               = 0;
                                        
                                        # --- shelly_switch_only --- #
                                        $time_shelly_switch_only = -microtime(true);
                                        $key = "shelly_switch_only-$event_id";
                                        $shelly_switch_only = $memcache->get($key);
                                        if ( $shelly_switch_only === false || $mode == "force_refresh" ) {
                                                $shelly_switch_only =get_shelly_switch_only ( $event_id );
                                                $memcache->set($key, $shelly_switch_only, $memcachetime); // Cache for x seconds
                                            }
                                        $timer[$event_id]['get_shelly_switch_only'] = $time_shelly_switch_only += microtime(true);
                                        # --- end shelly_switch_only --- #

                                        # --- device_status (incl. shelly status uit database) --- #
                                            $key = "device_status-$event_id";
                                            $device_status = $memcache->get($key);
                                            if ( $device_status === false || $mode == "force_refresh" ) {
                                                    $device_status = get_device_status ( $event_id );
                                                    $memcache->set($key, $device_status, $memcachetime); // Cache for x seconds
                                                }
                                        # --- end device_status --- #
                                        if ( isset ($device_status['modem_heartbeat'] )){ $modem_heartbeat        = $device_status['modem_heartbeat'] ; } else { $modem_heartbeat = ""; }
                                        if ( isset ($device_status['pc_heartbeat']))    { $pc_heartbeat           = $device_status['pc_heartbeat'];     } else { $pc_heartbeat = '';    }

                                        # --- shelly status (uit photobooth_device_status, gevuld door refresh of cron_power_planner_cafekasten.php) --- #
                                        if ( isset ($device_status['shelly_online'] )) { $shelly_online  = $device_status['shelly_online']; }
                                        if ( isset ($device_status['shelly_out_0']  )) { $shelly_state_0 = $device_status['shelly_out_0'];  }
                                        if ( isset ($device_status['shelly_out_1']  )) { $shelly_state_1 = $device_status['shelly_out_1'];  }
                                        # --- end shelly status --- #

                                        # --- power_planner_use --- #
                                        $key = "power_planner_use-$event_id";
                                        $power_planner_use = $memcache->get($key);
                                        if ( $power_planner_use === false || $mode == "force_refresh" ) {   
                                                $power_planner_use = get_power_planner_used ( $event_id ); 
                                                $memcache->set($key, $power_planner_use, $memcachetime); // Cache for x seconds 
                                            }
                                        # --- end power_planner_use --- #    
                                        
                                        # --- payter_terminal_online --- #
                                        $key = "payter_terminal_online-$event_id";
                                        $payter_terminal_online = $memcache->get($key);
                                        if ( $payter_terminal_online === false || $mode == "force_refresh" ) {   
                                                $payter_terminal_online = get_payter_terminal_info ( $event_id );
                                                $memcache->set($key, $payter_terminal_online, $memcachetime); // Cache for x seconds 
                                            }
                                        # --- end payter_terminal_online --- #    
                                        
                                        # --- power --- #
                                            $key = "power-$event_id";
                                            $power = $memcache->get($key);
                                            if ($power === false || $mode == "force_refresh") {   
                                                    $power = status_according_to_power_planner ( $event_id );
                                                    $memcache->set($key, $power, $memcachetime); // Cache for x seconds 
                                                }
                                        # --- end power --- #    

                                         # --- print counter --- #
                                            $key = "printer-$event_id";
                                            $printcounter = $memcache->get($key);
                                            if ($printcounter === false || $mode == "force_refresh") {   
                                                    $printcounter = get_print_counter ( $event_id );
                                                    $memcache->set($key, $printcounter, $memcachetime); // Cache for x seconds 
                                                }
                                        # --- end printer --- #

                                         # --- nr of prints Payter --- #
                                            $key = "nr_prints_payter-$event_id";
                                            $nr_of_prints = $memcache->get($key);
                                            if ( $nr_of_prints === false || $mode == "force_refresh" ) {   
                                                    $nr_of_prints = get_nr_of_prints_last_night ( $event_id );
                                                    $memcache->set($key, $nr_of_prints, $memcachetime); // Cache for x seconds 
                                                }
                                                
                                        # --- end device_status --- #  
                                                

                                        # bepaal STATUS en ERRORS
                                        # event_id 35, 83, 98 en 115 zijn oudere opstellingen zonder modem
                                        $event_ids_zonder_modem = array( 35, 83, 98, 115 );
                                        if ( $modem_heartbeat == 0 && !in_array ( $event_id, $event_ids_zonder_modem ) ) { $errors['modem'] = 'MODEM'; $status = "ERROR";}
                                        # event_id 115 heeft geen shelly
                                        $event_ids_zonder_shelly = array( 115 );
                                        if ( $get_shelly_status == 'true' && !in_array ( $event_id, $event_ids_zonder_shelly ) ) {
                                            # remote switch active
                                            if ( $shelly_online == 0 ) { $errors['remote switch'] = 'SHELLY'; $status = "ERROR";} 
                                           
                                            # remote switch state 0
                                             if ( $shelly_state_0 == 0 && $power == 1) { $errors['remote switch 0'] = 'SWITCH <> POWERPLANNER'; $status = "ERROR";} 
                                           
                                            # remote switch state 1
                                           // if ( $shelly_state_1 == 0 && $power == 1) { $errors['remote  switch 1'] = 'Remote switch 1 staat op UIT, terwijl de powerplanner hem aangezet heeft';$status = "ERROR";} 
                                        } 
                                        if ( $pc_heartbeat == 0 && $shelly_state_0 == 1  ) { $errors['remote  switch 1'] = 'PC'; $status = "ERROR";}
                                        if ( $event_id <> 35 && $event_id <> 83 ) {
                                            if ( $payter_terminal_online == 0 && $shelly_state_0 == 1  ) { $errors['payterterminal'] = 'PAYTER'; $status = "ERROR";}                                 
                                        }
                                        if ( $status == "ERROR") {  $class_status = "status_red";}

                                             # aan uit status vakje
                                             if ( in_array ( $event_id, $event_ids_zonder_shelly ) ) {
                                                # geen shelly voor dit event: dot volgt de pc_heartbeat ipv de shelly switch state, nooit rood
                                                if ( $pc_heartbeat == 1 ) {
                                                    $class_active ="status_green";
                                                    $active_text = "ON";
                                                } else {
                                                    $class_active ="status_grey";
                                                    $active_text = "OFF";
                                                }
                                            } else {

                                             if ( $shelly_online == 0 ) {
                                                $class_active ="status_red";
                                                $active_text = "OFFLINE";
                                            }


                                            if ( $shelly_online == 1 && $shelly_state_0 == 1  ) {
                                                $class_active ="status_green";
                                                $active_text = "ON";
                                            }

                                            if ( $shelly_online == 1 && $shelly_state_0 == 0 && $power == 0 ) {
                                                $class_active ="status_grey";
                                                $active_text = "OFF";
                                            }

                                            if ( $shelly_online == 1 && $shelly_state_0 == 0 && $power == 1 ) {
                                                $class_active ="status_red";
                                                $active_text = "OFF";
                                            }
                                            } // end if geen shelly
                                           ?>
                                <tr>
                                  <td class="al-center"><span class="statuspill <?php echo $class_active; ?>"></span></td>
                                  
                                  <td nowrap="nowrap"><a style="text-decoration:none; " href='index.php?mode=single&event_id=<?php echo $event_id; ?>'><span class="btn-sm btn-info btn-width"> <?php echo  $event_id . " - " . $event_data_array[$event_id]['event_name'];  ?></span></a></td>
                                  <td class="al-center"><a style="text-decoration:none; " href='index.php?event_id=<?php echo $event_id; ?>&mode=stroken'><span class="btn-sm btn-info"><img src="/img/icons/paper.png" height="18" width="20"></span></a></td>
                                  
                                  <td class="al-center"><?php echo $nr_of_prints; ?></td>          
                                  <td class="al-center <?php if ( $printcounter <= 20 ) {echo ' prints_red'; } ?>"><?php echo $printcounter; ?></td>       
                                  <td class="al-center"><span class="statustext <?php echo $class_status; ?>"><?php 
                                    if ( sizeof($errors)>0) { 
                                        foreach ($errors as $error) { 
                                            echo $error . "<br>"; 
                                            }
                                    }
                                    else { echo "OK";
                                    }
                                  
                                  ?></span></td>
                            </tr>
                           
                           

                            
                            <?php } // end foreach event
                            $timer['events_loop_total'] = $timer_events_loop_start += microtime(true);
                            ?>
                        </tbody>

                    </table>
               </div>
                <!-- end row -->
              </div>
            <div class="col-sm-12 col-md-12 col-lg-12">
                Laatste 9 strookjes<br> <?php
                    $timer_photos_loop_start = -microtime(true);
                    foreach ($event_array as $event_id) {
                        ?>
                       <div style="width:325px; border: 2px solid #444242ff; border-radius: 5px; padding: 5px;  display: block; float: none; text-align: center;">
                       <?php
                         $sql3 = "SELECT name
                                    FROM photobooth_event_ids 
                                   WHERE event_id = '" . $event_id . "'";
                        $result3    = mysqli_query( $cn , $sql3 );
                        $return3    = $result3->fetch_object();
                        $event_name = $return3->name;
                        ?>  
                       <h2> <?php echo $event_id . " - " . $event_name; ?></h2><br>
                       
                        <?php
                        $sql3 = "SELECT filename_md5
                                   FROM photo 
                                  WHERE event_id            = '" . $event_id       . "' 
                                    AND MONTH(datetime)     = '" . date('m') . "'
                                    AND YEAR(datetime)      = '" . date('Y') . "'
                                    AND DAY(datetime)       = '" . date('d') . "' 
                               ORDER BY datetime DESC 
                                  LIMIT 0,9";
                        $result3          = mysqli_query( $cn , $sql3 );
                        //echo $sql;
                        while ( $return3 = $result3->fetch_object() ){
                            $file_hash       = $return3->filename_md5;
                            $file_url  = get_photo_thumb_url ( $event_id, $file_hash );
                            ?>
                            <img src="<?php echo $file_url; ?>" width="100" style="border: 1px solid #bbb; border-radius: 4px; margin: 3px; padding: 2px;">
                            <?php
                        }
                        ?>
                        </div>
                      <br><br>
                    <?php
                        }
                        $timer['photos_loop_total'] = $timer_photos_loop_start += microtime(true);
                     ?>
                 </div>   <!-- / end col-->
          

             <div class="col-sm-12 col-md-6 col-lg-6">
                 
                 <div class="row">
                    
                 
                    <?php

                     $timer['time_over_all'] = $time_over_all += microtime(true);

                     render_timer_debug ( $timer );

                    ?>
                    </div>
                 </div>

             </div>
        
          </div>
        </div>
      </main>
      <footer class="c-footer">
          <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> © <?php echo date('Y'); ?> MagicShoot.</div>
          <div class="ml-auto">Powered by&nbsp;<a href="https://www.magicshoot.nl/">MagicShoot</a></div>
        </footer>
      </div>
    </div>  
<?php include("partial_footer.php"); ?>