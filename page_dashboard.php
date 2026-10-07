 
  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/shelly_functions.php"); 
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


if ( isset ( $_REQUEST['mode']) ) { $mode = mysqli_real_escape_string ( $cn, $_REQUEST['mode'] ); } else { $mode = ""; }

//echo "MODE = " . $mode . "<br>";
$testmode_array = array();  // Initialize array before conditionals
if ( $mode == "single") {
    $event_id = $_SESSION['event_id'];
     $event_array = array();
    array_push ( $event_array, $event_id );
} else {

  if ( $_SESSION['client_role'] == 'admin' ) 
    {
       # admins mogen alle events zien 
       $event_array = array();
       $testmode_array = array();
       $sql = "SELECT event_id,
                      test_mode
                 FROM photobooth_event_ids 
                 WHERE dashboard_show ='1'
                  ORDER BY 	dashboard_order_nr	 ASC";
       $result             = mysqli_query( $cn , $sql );
       while ( $return     = mysqli_fetch_object ( $result ) ) {
            $event_id   = $return->event_id;
            $test_mode   = $return->test_mode;
            array_push ( $event_array, $event_id );
            $testmode_array[ $event_id ] = $test_mode;
        }
      
    } else if ( $_SESSION['client_role'] == 'owner' ) 
    {
    # toon alleen alle events die bij deze owner horen
    $event_array = array();
    $sql = "SELECT event_id,
                   test_mode
              FROM photobooth_event_ids 
         LEFT JOIN photobooth_stat_client_event USING(event_id) 
             WHERE photobooth_stat_client_event.client_id ='". $_SESSION['client_id'] .  "' 
               AND photobooth_event_ids.dashboard_show ='1'
          ORDER BY 	dashboard_order_nr	 ASC";
             
    //echo $sql;
    $result             = mysqli_query( $cn , $sql );
    while ( $return     = mysqli_fetch_object ( $result ) ) 
    {
         $event_id   = $return->event_id;
         $test_mode  = $return->test_mode;
         array_push ( $event_array, $event_id );
         $testmode_array[ $event_id ] = $test_mode;
    }
    
    } else if ( $_SESSION['client_role'] == 'employee' ) 
    {
    # toon alleen alle events die bij deze employee horen
    $event_array = array();
    $sql = "SELECT event_id,
                   test_mode 
              FROM photobooth_event_ids 
         LEFT JOIN photobooth_stat_client_event USING(event_id) 
             WHERE photobooth_stat_client_event.client_id ='". $_SESSION['client_id'] . "'
               AND dashboard_show ='1'";
    $result             = mysqli_query( $cn , $sql );
    while ( $return     = mysqli_fetch_object ( $result ) ) 
    {
          $event_id   = $return->event_id;
          $test_mode  = $return->test_mode;
          array_push ( $event_array, $event_id );
          $testmode_array[ $event_id ] = $test_mode;
    }
  }
}
  $timer['determine_event_for_current_user'] = $time += microtime(true);
  $time = -microtime(true); 


  $mode = ""; //init
  if ( isset ( $_REQUEST['mode']) ) {
        $mode     = mysqli_real_escape_string( $cn, $_REQUEST['mode']);  
  } 

  # shelly status: alleen op refresh wordt de echte Shelly api aangeroepen; normaal wordt de status
  # per event_id uit photobooth_device_status gelezen (gevuld door refresh hieronder of door de cron
  # api/cron/cron_power_planner_cafekasten.php)
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
        #images{ display: block;  }
        .voorbeeld { display:  block; border: solid 2px #eee; padding:  10px; margin:  5px; float: left; width:  250px; height:  auto;}
        
        .card {
            width: 400px;
            margin-right: 10px;
        }
        .error { color: #f00; font-weight: bold;}
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
        <li class="breadcrumb-item">Home </li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Data: <?php echo isset($_SESSION['last_updated']) ? $_SESSION['last_updated'] : 'onbekend'; ?></li>
        <li class="breadcrumb-item"><a style="text-decoration:none; " href='page_dashboard.php?mode=force_refresh'><span class="btn-sm btn-info">Refresh</span></a></li> 
        <?php if (  isset ($shelly_device_status_array['error']) && $shelly_device_status_array['error']<>"") {
            ?> <li class="breadcrumb-item error">Error: <?php echo $shelly_device_status_array['error']; ?></li>   
        <?php } ?>
    </li>
    <li>
        &nbsp;&nbsp;&nbsp;<label class="checkbox-inline">
            <input type="checkbox"  id="checkbox_shelly_status" onclick="toggleCheckbox(this)"> Get remote switch status
        </label>
    </li>    
      <!-- Breadcrumb Menu-->
      </ol>
    </div>

    
        <script>
            $(document).ready(function() {
               let status = getCookie("get_latest_shelly_status");
               console.log ("status in cookie is: " + status);
               if ( status == 'true' ) { document.getElementById("checkbox_shelly_status").checked = true;}
               if ( status == 'false' ) { document.getElementById("checkbox_shelly_status").checked = false;}
               console.log ("status zou nu moeten zijn: " + document.getElementById("checkbox_shelly_status").checked);
            });
        </script>
         <style>
                      
                        
                        table.devicetable thead tr {
                            background-color:#000; 
                            color:#fff; font-size:11px; 
                            font-weight: bold;
                            font-family:sans-serif; 
                            
                        }
                        
                       
                        .statustext {
                            display: block;
                            box-sizing: border-box;
                            height: 18px;
                            border-radius: 4px;
                            width: 78px;
                            color: #fff;
                            font-weight: bold;
                            font-size: 10px;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            margin-top: 5px;
                            padding-top: 2px;
                            padding-bottom: 2px;
                            padding-left: 4px;
                            padding-right: 4px;
                        }

                        /* smalle variant voor de SWITCH-kolom: die labels (ON/OFF) zijn kort
                           en hoeven niet mee te groeien met de bredere STATUS-kolom */
                        .statustext_switch {
                            width: 42px;
                        }

                        .statustext_black {
                            display: block;
                            vertical-align: top;
                            height: 30px;
                            border-radius: 4px;
                            width: 66px;
                            color: #000;
                            font-weight: bold;
                            font-size: 10px;
                            /* datum+tijd mag hier wel over twee regels: vandaar hoogte 30px
                               en geen nowrap/ellipsis zoals bij .statustext */
                            white-space: normal;
                            overflow: visible;
                            text-overflow: clip;
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
                            background-color: #95C11F;
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
                         .status_orange {
                            background-color: #F39200;
                        }
                        .info_circle {
                            display: inline-block;
                            width: 18px;
                            height: 18px;
                            line-height: 17px;
                            border: 1px solid #36A9E1;
                            border-radius: 50%;
                            color: #36A9E1;
                            font-weight: bold;
                            font-style: italic;
                            font-size: 12px;
                            text-align: center;
                            text-decoration: none;
                        }
                        .info_circle:hover {
                            background-color: #36A9E1;
                            color: #fff;
                            text-decoration: none;
                        }
                    </style>
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
       
        $event_data_array[$event_id]['event_name']          = $event_name;
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

function get_printer_info ( $event_id ) {
    global $cn;
    global $timer;
    $time = -microtime(true); 

      # haal COUNTER status op
     $sql_1   = "SELECT log_details, log_created
                   FROM log
                  WHERE event_id        = '" . $event_id         . "' 
                    AND log_action        = 'counter'
               ORDER BY log_id DESC
                  LIMIT 0,1";
    $result_1                   = mysqli_query( $cn , $sql_1 );
   if ( $result_1->num_rows > 0 )
    {
    $return_1                   = $result_1->fetch_object();
    $print_counter              = $return_1->log_details;
    $log_created_print_counter  = $return_1->log_created;
    }


     # haal laatste printerstatus op
     # Idle of een error
    $sql2 = "SELECT *
               FROM log
              WHERE event_id   = '" . $event_id . "' 
                AND log_action = 'printerstatus' 
           ORDER BY log_id DESC
              LIMIT 0,1";
    $result2                   = mysqli_query( $cn , $sql2 );
    $return['printerstatus_datetime'] = ""; //default
    $return['printerstatus'] = "none"; //default
    if ( $result2->num_rows > 0 ) {
        $return2                            = $result2->fetch_object();
        $printerstatus                      = $return2->log_details;
        $printerstatus_datetime             = $return2->log_created;
        $return['printerstatus']            = $printerstatus;
        $return['printerstatus_datetime']   = $printerstatus_datetime;
    }

    # haal PRINT KLAAR status op
    $sql3 = "SELECT *
               FROM log
              WHERE event_id   = '" . $event_id . "' 
                AND log_action = 'PRINT KLAAR' 
           ORDER BY log_id DESC
              LIMIT 0,1";
    $result3                   = mysqli_query( $cn , $sql3 );
    $return['print_counter_datetime'] = "";
    $return['print_klaar_status'] = "";
    if ( $result3->num_rows > 0 ) {
        $return3                            = $result3->fetch_object();
        $print_klaar_status                 = $return3->log_details;
        $return['print_counter']            = $print_counter;
        $return['print_counter_datetime']   = $log_created_print_counter;
        $return['print_klaar_status']       = $print_klaar_status;

        $papier_advies_array = get_paper_advise ( $event_id, $print_counter );
        if ( $papier_advies_array )
            {
            $return['max_aantal_prints_op_deze_dayofweek']  = $papier_advies_array['max_aantal_prints_op_deze_dayofweek'];
            $return['dagnaam']                              = $papier_advies_array['dagnaam'];
            $return['vervang_rol']                          = $papier_advies_array['vervang_rol'];
            }
    }
    $rolls_in_stock = get_rolls_in_stock ( $event_id );
    $return['rolls_in_stock']                        = $rolls_in_stock;
    $timer[$event_id]['get_printer_info'] = $time += microtime(true);
    return $return;
}

function get_paper_advise ( $event_id, $print_counter )
{
    global $cn;
    global $timer;
    $time = -microtime(true); 
    # bepaal welke dag het vandaag is
    # bepaal het gemiddelde aantal foto's per deze dagsoort (ma, di, do, vr, za , zo) voor een gegeven event_id
    # bepaald of dit gemiddelde kleiner is dan de printer_counter

    # DAYOFWEEK 
    # 1 = Zondag
    # 2 = Maandag
    # 3 = Dindag
    # 4 = Woensdag
    # 5 = Donderdag
    # 6 = Vrijdag
    # 7 = Zaterdag
    $dayofweek_vandaag = date("w") + 1;
    //echo "dayofweek nr=" . $dayofweek_vandaag;
    
    $sql_1 = "SELECT count(*) as aantal, sessie_soort
                FROM log_sessionOK
               WHERE event_id           = '" . $event_id          . "'
                 AND DAYOFWEEK(werkdag) = '" . $dayofweek_vandaag . "'
                 AND sessie_soort       = 'print'
            GROUP BY event_id, sessie_soort, werkdag
            ORDER BY aantal DESC
               LIMIT 0,1";
    //echo $sql_1 . "<br>";
    $result_1 = mysqli_query( $cn , $sql_1 );
    if ( $result_1 ) {
        $return_1 = mysqli_fetch_object ( $result_1 );
        if ( $return_1 ) {
            $max_aantal_prints_op_deze_dayofweek  = $return_1->aantal;
            $result_array = array();
            $result_array['max_aantal_prints_op_deze_dayofweek'] = $max_aantal_prints_op_deze_dayofweek;
            $result_array['dagnaam'] = date('l');
            if ( $max_aantal_prints_op_deze_dayofweek >= $print_counter )
            {
                $result_array['vervang_rol'] = 1;
            } else {
                $result_array['vervang_rol'] = 0;
            }
            $timer[$event_id]['get_paper_advise'] = $time += microtime(true);
            return $result_array;
        }
    }
}

function get_rolls_in_stock ( $event_id ){
    global $cn;
    global $timer;
    $time = -microtime(true); 
    # in_stock
    $sql = "SELECT number_of_rolls 
              FROM photobooth_media_in_stock
             WHERE event_id = '" . $event_id . "'";
    $result  = mysqli_query( $cn , $sql );
    if ( $result ) {
        $return	 = mysqli_fetch_object ( $result );
        if ( $return ) {
            $number_of_rolls_in_stock = $return->number_of_rolls;
            $timer[$event_id]['get_rolls_in_stock'] = $time += microtime(true);
            return $number_of_rolls_in_stock;
        }
    }
    else { 
        $timer[$event_id]['get_rolls_in_stock'] = $time += microtime(true);
        return 0; 
    }
}

function get_latest_revenue ( $event_id )
{
    global $cn;
    global $timer;
    $time = -microtime(true); 
  
    $aantal_print_betaald   = 0;
    $aantal_print_gratis    = 0;
    $aantal_qr_betaald      = 0;
    $aantal_qr_gratis       = 0;
    $bedrag_print           = 0;
    $bedrag_qr              = 0;
    $bedrag_totaal          = 0;
    $revenue_data           = array();

     $sql = "SELECT event_id,
                    werkdag,
                    gratis,
                    sessie_soort, 
                    COUNT(*) as aantal, 
                    SUM(prijs) as totaalbedrag 
               FROM log_sessionOK 
              WHERE event_id = '" . $event_id . "'
                AND werkdag=(SELECT MAX(werkdag) AS Laatste FROM log_sessionOK WHERE event_id='" . $event_id . "')
           GROUP BY event_id,werkdag,gratis,sessie_soort;";       

    $result                 = mysqli_query( $cn , $sql );
    if ( $result->num_rows > 0 ) {
        $return	 = mysqli_fetch_object ( $result );
        $werkdag                = $return->werkdag;
        $gratis                 = $return->gratis;
        $sessie_soort           = $return->sessie_soort;

        if ( $sessie_soort == 'print' )
        {
            if ( $gratis == 0 ) { $aantal_print_betaald = $return->aantal; }
            if ( $gratis == 1 ) { $aantal_print_gratis  = $return->aantal; }
            $bedrag_print       += $return->totaalbedrag / 100;
        }

        if ( $sessie_soort == 'qr' )
        {
            if ( $gratis == 0 ) { $aantal_qr_betaald = $return->aantal; }
            if ( $gratis == 1 ) { $aantal_qr_gratis  = $return->aantal; }
            $bedrag_qr          += $return->totaalbedrag / 100;
        }
        $bedrag_totaal                            = $bedrag_print + $bedrag_qr;
        
        $revenue_data['werkdag']                  = $werkdag;
        $revenue_data['aantal_print_betaald']     = $aantal_print_betaald;
        $revenue_data['aantal_print_gratis']      = $aantal_print_gratis;
        $revenue_data['aantal_qr_betaald']        = $aantal_qr_betaald;
        $revenue_data['aantal_qr_gratis']         = $aantal_qr_gratis;
        $revenue_data['bedrag_print']             = $bedrag_print;
        $revenue_data['bedrag_qr']                = $bedrag_qr;
        $revenue_data['bedrag_totaal']            = $bedrag_totaal; 
       
    } 
    $timer[$event_id]['get_latest_revenue'] = $time += microtime(true);
    return $revenue_data;
}

function get_planner_set_id ( $event_id ) 
{
   global $cn;  
   global $timer;
   $time = -microtime(true);  
   $sql = "SELECT * FROM `photobooth_set_planner` WHERE NOW() BETWEEN start_date_time AND end_date_time AND event_id = $event_id;";
   
   $result          = mysqli_query( $cn , $sql );
   if ( $result->num_rows > 0 ) {
    $return	        = mysqli_fetch_object ( $result );
    $planner_set_id  = $return->set_id;
    $timer[$event_id]['get_planner_set_id'] = $time += microtime(true);
    return $planner_set_id;
   }
}

function get_base_set_id ( $event_id ) 
{
   global $cn;
   global $timer;
   $base_set_id = '';
   $time = -microtime(true); 
   $sql         ="SELECT set_id
                    FROM photobooth_set
                   WHERE event_id = '" . $event_id . "'
                     AND is_base_set ='1'";
   $result      = mysqli_query( $cn , $sql );
   if ( $result->num_rows > 0 ) {
    $return	    = mysqli_fetch_object ( $result );
    $base_set_id = $return->set_id;
    $timer[$event_id]['get_base_set_id'] = $time += microtime(true);
   }
    return $base_set_id;
}

function get_set ( $set_id, $event_id ) 
{
   global $cn;   
   global $timer;
   $return = array();
   $time = -microtime(true);   
   $sql        =  "SELECT *
                     FROM photobooth_set
                    WHERE set_id    = '" . $set_id   . "' 
                      AND event_id  = '" . $event_id . "'";
   $result     = mysqli_query( $cn , $sql );
   if ( $result->num_rows > 0 ) {
    $return     = mysqli_fetch_object ( $result );
    $timer[$event_id]['get_set'] = $time += microtime(true);
   }
   return $return;
}

function get_device_status ( $event_id )
{
    //OPMERKING: volgens mij worden veel velden die in deze tabel staan helemaal niet gebruik in het dashboard (uitgezet ), vraag is even of ze elders wel gebruikt worden
    // anders kan het deels (printer, shelly) uit /api/cron/cron_power_planner_cafekasten.php !
    
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

function get_software_version ( $event_id )
{
    // versie = eerste woord van log_details van de laatste 'Start' regel, bv "1.4.5.0 2-10-2026"
    // FLORA VIDEO (35) logt onder event_id 5 (bekende fout in de software); event 5 wordt ook door
    // andere bronnen gebruikt, dus daar filteren op log_source 'magicvideo'
    global $cn;
    global $timer;
    $return_array = array();
    $time = -microtime(true);
    $event_id = (int) $event_id;
    if ( $event_id == 35 ) {
        $where = "event_id = 5 AND log_source = 'magicvideo'";
    } else {
        $where = "event_id = " . $event_id;
    }
    $sql       = "SELECT log_details, log_created
                    FROM log
                   WHERE " . $where . "
                     AND log_action = 'Start'
                ORDER BY log_id DESC
                   LIMIT 1";
    $result    = mysqli_query( $cn , $sql );
    if ( $result && $result->num_rows > 0 ) {
        $return       = mysqli_fetch_object ( $result );
        $parts        = preg_split ( '/\s+/', trim ( $return->log_details ), 2 );
        $return_array['version']     = $parts[0];
        $return_array['build_date']  = isset ( $parts[1] ) ? $parts[1] : '';
        $return_array['started']     = $return->log_created;
    }
    $timer[$event_id]['get_software_version'] = $time += microtime(true);
    return $return_array;
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
                 <div class="col-sm-12 col-md-12 col-lg-12">
                 
                 <div class="row">


                <?php 
                foreach ($event_array as $event_id) {
                        # init
                        $shelly_online              = 0;
                        $shelly_state_0             = 0;
                        $shelly_state_1             = 0;

                        # event 115: no modem, power planner, remote switch, pay terminal,
                        # printer, testmode, last workday or current set in this setup - only PC status applies
                        # (35 and 83 look similar at a glance but DO have power planner + remote switch - don't add them here)
                        $minimal_setup_event_ids = array ( 115 );
                        $is_minimal_setup        = in_array ( $event_id, $minimal_setup_event_ids );

                        # --- shelly_switch_only --- #
                        $key = "shelly_switch_only-$event_id";
                        $shelly_switch_only = $memcache->get($key);
                        if ($shelly_switch_only === false || $mode == "force_refresh") {   
                                $shelly_switch_only =get_shelly_switch_only ( $event_id );  
                                $memcache->set($key, $shelly_switch_only, $memcachetime); // Cache for x seconds 
                            }
                        # --- end shelly_switch_only --- #

                        # --- device_status (incl. shelly status uit database) --- #
                            $key = "device_status-$event_id";
                            $device_status = $memcache->get($key);
                            if ($device_status === false || $mode == "force_refresh") {
                                    $device_status = get_device_status ( $event_id );
                                    $memcache->set($key, $device_status, $memcachetime); // Cache for x seconds
                                }
                        # --- end device_status --- #

                        # --- software_version (laatste 'Start' regel in log) --- #
                            $key = "software_version-$event_id";
                            $software_version = $memcache->get($key);
                            if ($software_version === false || $mode == "force_refresh") {
                                    $software_version = get_software_version ( $event_id );
                                    $memcache->set($key, $software_version, $memcachetime); // Cache for x seconds
                                }
                        # --- end software_version --- #
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
                        if ($power_planner_use === false || $mode == "force_refresh") {   
                                $power_planner_use = get_power_planner_used ( $event_id ); 
                                $memcache->set($key, $power_planner_use, $memcachetime); // Cache for x seconds 
                            }
                        # --- end power_planner_use --- #    
                        
                        # --- payter_terminal_online --- #
                        $key = "payter_terminal_online-$event_id";
                        $payter_terminal_online = $memcache->get($key);
                        if ($payter_terminal_online === false || $mode == "force_refresh") {   
                                $payter_terminal_online = get_payter_terminal_info ( $event_id );
                                $memcache->set($key, $payter_terminal_online, $memcachetime); // Cache for x seconds 
                            }
                        # --- end payter_terminal_online --- #    
                        
                        # --- printer_data --- #
                        $key = "printer_data-$event_id";
                        $printer_data = $memcache->get($key);
                        if ($printer_data === false || $mode == "force_refresh") {   
                                $printer_data = get_printer_info ( $event_id );
                                $memcache->set($key, $printer_data, $memcachetime); // Cache for x seconds 
                            }
                        # --- end printer_data --- #    
                        
                        if ( isset ( $printer_data['print_counter']) )                          { $print_counter = $printer_data['print_counter'];                                              } else { $print_counter = "";}
                        if ( isset ( $printer_data['print_counter_datetime'] ))                 { $print_counter_datetime = $printer_data['print_counter_datetime'];                            } else { $print_counter_datetime = '';}
                        if ( isset ( $printer_data['print_klaar_status']) )                     { $print_ready_status = $printer_data['print_klaar_status'];                                    } else { $print_ready_status = "";}
                        if ( isset ( $printer_data['printerstatus']) )                          { $printerstatus = $printer_data['printerstatus'];                                              } else { $printerstatus = "";}
                        if ( isset ( $printer_data['printerstatus_datetime']) )                 { $printerstatus_datetime = $printer_data['printerstatus_datetime'];                            } else { $printerstatus_datetime = "";}
                        if ( isset ( $printer_data['vervang_rol'] ) )                           { $printer_vervang_rol = $printer_data['vervang_rol'];                                          } else { $printer_vervang_rol = "";}
                        if ( isset ( $printer_data['max_aantal_prints_op_deze_dayofweek'] ) )   { $max_aantal_prints_op_deze_dayofweek = $printer_data['max_aantal_prints_op_deze_dayofweek'];  } else { $max_aantal_prints_op_deze_dayofweek = "";}
                        if ( isset ( $printer_data['dagnaam'] ) )                               { $dagnaam = $printer_data['dagnaam'];                                                          } else { $dagnaam = "";}
                        if ( isset ( $printer_data['rolls_in_stock'] ) )                        { $rolls_in_stock = $printer_data['rolls_in_stock'];                                            } else { $rolls_in_stock = "";}
                        
                        $anydesk_id             = $event_data_array[$event_id]['anydesk_id'] ;
                        # --- power --- #
                            $key = "power-$event_id";
                            $power = $memcache->get($key);
                            if ($power === false || $mode == "force_refresh") {   
                                    $power = status_according_to_power_planner ( $event_id );
                                    $memcache->set($key, $power, $memcachetime); // Cache for x seconds 
                                }
                        # --- end power --- #

                        # --- revenue_data --- #
                            $key = "revenue_data-$event_id";
                            $revenue_data = $memcache->get($key);
                            if ($revenue_data === false || $mode == "force_refresh") {   
                                     $revenue_data = get_latest_revenue ( $event_id ); 
                                    $memcache->set($key, $revenue_data, $memcachetime); // Cache for x seconds 
                                }
                        # --- end revenue_data --- #  
                        
                        # --- planner_set_id --- #
                            $key = "planner_set_id-$event_id";
                            $planner_set_id = $memcache->get($key);
                            if ($planner_set_id === false || $mode == "force_refresh") {   
                                    $planner_set_id = get_planner_set_id ( $event_id );
                                    $memcache->set($key, $planner_set_id, $memcachetime); // Cache for x seconds 
                                }
                        # --- end planner_set_id --- #  

                        # --- base_set_id --- #
                            $key = "base_set_id-$event_id";
                            $base_set_id = $memcache->get($key);
                            if ($base_set_id === false || $mode == "force_refresh") {   
                                    $base_set_id = get_base_set_id ( $event_id );
                                    $memcache->set($key, $base_set_id, $memcachetime); // Cache for x seconds 
                                }
                        # --- end base_set_id --- #  
                        
                        
                        if ( $planner_set_id == "" ) { $current_set_id = $base_set_id; }
                        if ( $planner_set_id <> "" ) { $current_set_id = $planner_set_id; }

                         # --- set_data --- #
                            $key = "set_data-$event_id";
                            $set_data = $memcache->get($key);
                            if ($set_data === false || $mode == "force_refresh") {   
                                    $set_data = get_set ( $current_set_id, $event_id );
                                    $memcache->set($key, $set_data, $memcachetime); // Cache for x seconds 
                                }
                        # --- end set_data --- #  
                       if ( isset($set_data->set_id) ){
                            $set_id                 = $set_data->set_id;
                            $basepath               = "/home/magicshoot/domains/magicshoot.nl/public_html/api/stack";
                            $path 	                = $basepath . '/event/' . $event_id .'/';
                            $set_path               = $path . 'sets/' . $set_id . '/'; 
                            $resource_path          = $set_path . 'resources/'; 
                            $strook_background      = $resource_path . 'strook.png';
                            $test_mode              = isset($testmode_array[ $event_id ]) ? $testmode_array[ $event_id ] : 0;
                       }
                        
                    ?>
                 <!-- /.card-->
                 <div class="card" style="width: 350px; margin-right: 10px;">
                    <div class="card-header
                    
                    <?php if (  $test_mode == 0 ) { echo "bg-facebook" ;} ?>
                    <?php if (  $test_mode > 0  ) { echo "bg-danger" ;} ?>
                    
                    
                    content-center">
                   
                      <h2 class="text-white text-uppercase" style="font-size: 16px; padding-top: 15px; padding-bottom: 10px;"><?php echo $event_data_array[$event_id]['event_name']; ?> - <?php echo $event_id; ?></h2>
                   
                    
                    </div>
                    <div class="card-body">
                    <p style="text-align:center;">                   
                    <?php
                    if (( $_SESSION['client_role'] == 'admin' ) || 
                       ( $_SESSION['client_role'] == 'owner' && count( $event_array ) > 1 )  || 
                       ( $_SESSION['client_role'] == 'employee' && count( $event_array ) > 1 )
                       )
                    {
                        ?><a style="text-decoration:none; " href='index.php?event_id=<?php echo $event_id; ?>'><span class="btn-sm btn-info"> Kies deze kast</span></a> <a style="text-decoration:none; " href='index.php?event_id=<?php echo $event_id; ?>&mode=stroken'><span class="btn-sm btn-info"> Stroken</span></a><?php 
                    }   
                    ?>
                    </p>

                   
                    <?php
                    # init
                    $active_text_power_planner      = "--";
                    $state_text_power_planner       = "--";
                    $active_text_modem              = "--";
                    $state_text_remote_switch_0     = "--";
                    $state_text_remote_switch_1     = "--";
                    $state_text_pc                  = "--";
                    $state_text_payment_terminal    = "--";
                    $state_text_printer             = "--";
                    $state_text_vervang_rol         = "--";
                    $state_text_test_mode           = "--";
                    $state_text_setplanner          = "--";
                    $state_text_printerstatus       = "--";
                    $class_powerplanner_active      = "status_blue";
                    $class_powerplanner_state       = "status_blue";
                    $class_modem_active             = "status_blue";
                    $class_remote_switch_active     = "status_blue";
                    $class_remote_switch_state_0    = "status_blue";
                    $class_remote_switch_state_1    = "status_blue";
                    $class_pc_active                = "status_blue";
                    $class_payment_terminal_active  = "status_blue";
                    $class_printer_active           = "status_blue";
                    $class_printer_vervang_rol      = "status_blue";
                    $class_test_mode                = "status_green";
                    $class_setplanner               = "status_blue";
                    $class_printerstatus            = "status_green";


                    # powerplanner active
                    if ( $pc_heartbeat == 0 ) { $class_powerplanner_active = "status_blue"; $active_text_power_planner = "OFF";} 
                    if ( $power_planner_use == 1 ) { $class_powerplanner_active = "status_green"; $active_text_power_planner = "IN USE";   } 

                    # powerplanner state
                    if ( $power == 0 ) { $class_powerplanner_state = "status_blue"; $state_text_power_planner = "OFF"; } 
                    if ( $power == 1 ) { $class_powerplanner_state = "status_green"; $state_text_power_planner = "ON";} 
                

                    # modem active
                    if ( $modem_heartbeat == 0 ) { $class_modem_active = "status_red"; $active_text_modem = "OFFLINE !";} 
                    if ( $modem_heartbeat == 1 ) { $class_modem_active = "status_green"; $active_text_modem = "ONLINE";} 

                    if ( $get_shelly_status == 'true' ) {
                        # remote switch active
                        if ( $shelly_online == 0 ) { $class_remote_switch_active = "status_red"; $active_text_remote_switch = "OFFLINE !";} 
                        if ( $shelly_online > 0 )  { $class_remote_switch_active = "status_green"; $active_text_remote_switch = "ONLINE";} 
                    
                        # remote switch state 0
                        if ( $shelly_state_0 == 0 && $power == 0) { $class_remote_switch_state_0 = "status_blue"; $state_text_remote_switch_0 = "OFF";} 
                        if ( $shelly_state_0 == 0 && $power == 1) { $class_remote_switch_state_0 = "status_red"; $state_text_remote_switch_0 = "OFF!";} 
                        if ( $shelly_state_0 == 1 )               { $class_remote_switch_state_0 = "status_green"; $state_text_remote_switch_0 = "ON";} 

                        # remote switch state 1
                        if ( $shelly_state_1 == 0 && $power == 0) { $class_remote_switch_state_1 = "status_blue"; $state_text_remote_switch_1 = "OFF";} 
                        if ( $shelly_state_1 == 0 && $power == 1) { $class_remote_switch_state_1 = "status_red"; $state_text_remote_switch_1 = "OFF!";} 
                        if ( $shelly_state_1 == 1 )               { $class_remote_switch_state_1 = "status_green"; $state_text_remote_switch_1 = "ON";} 
                    } else {
                        # remote switch active
                        $class_remote_switch_active = "status_purple"; $active_text_remote_switch = "NOT SET";
                    
                        # remote switch state 0
                         $class_remote_switch_state_0 = "status_purple"; $state_text_remote_switch_0 = "NOT SET";

                        # remote switch state 1
                        $class_remote_switch_state_1 = "status_purple"; $state_text_remote_switch_1 = "NOT SET";
                    }

                    # PC
                    if ( $pc_heartbeat == 0 && $shelly_state_0 == 0  ) { $class_pc_active = "status_blue"; $state_text_pc = "OFFLINE";} 
                    if ( $pc_heartbeat == 0 && $shelly_state_0 == 1  ) { $class_pc_active = "status_red"; $state_text_pc = "OFFLINE !";} 
                    if ( $pc_heartbeat == 1 )                        { $class_pc_active = "status_green"; $state_text_pc = "ONLINE";} 

                    # payment terminal
                    
                    if ( $payter_terminal_online == 0 && $shelly_state_0 == 0  ) { $class_payment_terminal_active = "status_blue"; $state_text_payment_terminal="OFFLINE"; } 
                    if ( $payter_terminal_online == 0 && $shelly_state_0 == 1  ) { $class_payment_terminal_active = "status_red"; $state_text_payment_terminal="OFFLINE !";} 
                    if ( $payter_terminal_online == 1   ) { $class_payment_terminal_active = "status_green"; $state_text_payment_terminal="ONLINE";} 
                    
                    # printer
                    if ( isset($printer_data['print_klaar_status'])) {
                        if ( $printer_data['print_klaar_status'] == '' && $pc_heartbeat == 0 ) { $class_printer_active = "status_blue"; $state_text_printer="OFFLINE";} 
                        if ( $printer_data['print_klaar_status'] == '' && $pc_heartbeat == 1 ) { $class_printer_active = "status_green"; $state_text_printer="READY";} 
                        if ( $printer_data['print_klaar_status'] <> "" ) { $class_printer_active = "status_red"; $state_text_printer= $printer_data['print_klaar_status'];} 
                    } else {

                        $class_printer_active = "status_blue"; $state_text_printer="-NONE-";
                    }

                     # print ready status ( bevat errors zoals ribbon end en paper jam)
                    if ( isset($printer_data['printerstatus'])) {
                      
                        if ( $printer_data['printerstatus'] <> "Idle" ) { $class_printerstatus = "status_red"; $state_text_printerstatus= $printer_data['printerstatus'];} 
                        if ( $printer_data['printerstatus'] == "Idle" ) { $class_printerstatus = "status_green"; $state_text_printerstatus= "IDLE";} 
                       
                        if ( $printer_data['printerstatus_datetime'] <> "" ) { $class_printererstatus_datetime = "statustext_black"; $state_text_printerstatus_datetime= $printer_data['printerstatus_datetime'];} else { $class_printererstatus_datetime = "statustext_black"; $state_text_printerstatus_datetime= "--"; }  
                    } else {

                        $class_printer_ready_active = "statustext_black"; $state_text_printer_ready="--";
                    }

                    # rol vervang advies
                    if ( $printer_vervang_rol == 1 ) { $class_printer_vervang_rol = "status_red"; $state_text_vervang_rol="YES";} 
                    if ( $printer_vervang_rol == 0 ) { $class_printer_vervang_rol = "status_green"; $state_text_vervang_rol="NO";} 

                    if ( $_SESSION['client_role'] == 'admin' ) {
                        # test-mode
                        if ( $test_mode == 0 ) { $class_test_mode = "status_green"; $state_text_test_mode="OFF";}
                        if ( $test_mode == 1 ) { $class_test_mode = "status_red"; $state_text_test_mode="GRATIS";}
                        if ( $test_mode == 2 ) { $class_test_mode = "status_red"; $state_text_test_mode="4xPRINT";}
                    }

                    # setplanner: staat er nu een geplande (niet-basis) set actief? dan negeert de cron de powerplanner
                    # zelfde conditie als cron_power_planner_cafekasten.php:98-100
                    $setplanner_override_active = ( $planner_set_id <> '' && ( $base_set_id == '' || $planner_set_id <> $base_set_id ) );
                    if ( $setplanner_override_active ) {
                        $class_setplanner = "status_orange"; $state_text_setplanner = "IN USE";
                    } else {
                        $class_setplanner = "status_green";  $state_text_setplanner = "NOT IN USE";
                    }
                    
                  
                    ?>
                    <style>
                    .al-center{
                        text-align: center;
                    }
                    .al-right{
                        text-align: right;
                    }

                    </style>
                    <table class="table  table-bordered table-striped table-sm devicetable">
                        <thead>
                            <tr>
                                <td colspan="2">DEVICE</td>

                                <td class="al-center">STATUS</td>
                                <td class="al-center">SWITCH</td>
                                
                            </tr>
                        </thead>    
                        <tbody>
                            <tr>
                                <td><img src="/img/icons/cog-wheel.png" height="20"></td>
                                <td>software</td>
                                <?php if ( isset ( $software_version['version'] ) ) { ?>
                                <td class="al-center" title="<?php echo htmlspecialchars ( 'build ' . $software_version['build_date'] . ' - laatste start ' . $software_version['started'] ); ?>"><?php echo htmlspecialchars ( $software_version['version'] ); ?></td>
                                <td></td>
                                <?php } else { ?>
                                <td class="al-center">--</td>
                                <td></td>
                                <?php } ?>
                            </tr>
                        <?php if ( $shelly_switch_only == 0 && !$is_minimal_setup ) { ?>
                        <tr>
                                <td><img src="/img/icons/modem.png" height="20"></td>
                                <td>modem</td>
                                <td class="al-center"><span class="statustext <?php echo $class_modem_active; ?>"><?php echo $active_text_modem; ?></span></td>
                                <td></td>

                            </tr>
                        <?php } ?>
                            <?php if ( !$is_minimal_setup ) { ?>
                            <tr>
                                <td><img src="/img/icons/powerplanner.png" height="20"></td>
                                <td nowrap="nowrap">power planner</td>
                                <td class="al-center"><span class="statustext <?php echo $class_powerplanner_active; ?>"><?php echo $active_text_power_planner; ?></span></td>
                                <td class="al-center"><span class="statustext statustext_switch <?php echo $class_powerplanner_state; ?>"><?php echo $state_text_power_planner; ?></span></td>

                            </tr>

                            <tr>
                                <td><img src="/img/icons/remote_switch.png" height="20"></td>
                                <td nowrap="nowrap">remote switch 0</td>
                                <td class="al-center"><span class="statustext <?php echo $class_remote_switch_active; ?>"><?php echo $active_text_remote_switch; ?></span></td>
                                <td class="al-center"><span class="statustext statustext_switch <?php echo $class_remote_switch_state_0; ?>"><?php echo $state_text_remote_switch_0; ?></span></td>

                            </tr>
                            <?php } // end check if minimal setup ?>
                            <?php if ( $shelly_switch_only > 0 && !$is_minimal_setup ) {
                                # als het een switch only device betreft?>
                            <tr>
                                <td><img src="/img/icons/remote_switch.png" height="20"></td>
                                <td nowrap="nowrap">remote switch 1 - PC</td>
                                <td class="al-center"><span class="statustext <?php echo $class_remote_switch_active; ?>"><?php echo $active_text_remote_switch; ?></span></td>
                                <td class="al-center"><span class="statustext statustext_switch <?php echo $class_remote_switch_state_1; ?>"><?php echo $state_text_remote_switch_1; ?></span></td>

                            </tr>
                            <?php } // end check if switch only ?>

                            <?php if ( $shelly_switch_only == 0 ) { ?>
                            <tr>
                                <td><img src="/img/icons/pc.png" height="20"></td>
                                <td>PC</td>
                                <td class="al-center"><span class="statustext <?php echo $class_pc_active; ?>"><?php echo $state_text_pc; ?></span></td>
                                <td></td>
                               
                            </tr>
                            <?php if ( !$is_minimal_setup ) { ?>
                            <tr>
                                <td><img src="/img/icons/payment_terminal.png" height="20"></td>
                                <td nowrap="nowrap">pay terminal</td>
                                <td class="al-center"><span class="statustext <?php echo $class_payment_terminal_active; ?>"><?php echo $state_text_payment_terminal; ?></span></td>
                                <td></td>

                            </tr>
                            <tr>
                                <td><img src="/img/icons/printer.png" height="20"></td>
                                <td>printer</td>
                                <td class="al-center"><span class="statustext <?php echo $class_printer_active; ?>"><?php echo $state_text_printer; ?></span></td>
                                <td></td>

                            </tr>
                             <tr>
                                <td><img src="/img/icons/printer.png" height="20"></td>
                                <td>printer status</td>
                                <td class="al-center"><span class="statustext <?php echo $class_printerstatus; ?>"><?php echo $state_text_printerstatus; ?></span></td>
                                <td class="al-center"><span class="statustext <?php echo $class_printererstatus_datetime; ?>"><?php echo $state_text_printerstatus_datetime; ?></span></td>

                            </tr>
                            <tr>
                                <td><img src="/img/icons/paper.png" height="20"></td>
                                <td>paper left</td>

                                <td class="al-center"><strong><?php echo $print_counter; ?></strong></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><img src="/img/icons/paper.png" height="20"></td>
                                <td>max at <?php echo $dagnaam; ?></td>

                                <td class="al-center"><strong><?php echo $max_aantal_prints_op_deze_dayofweek; ?></strong></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><img src="/img/icons/paper.png" height="20"></td>
                                <td>change paper</td>

                                <td class="al-center"><span class="statustext <?php echo $class_printer_vervang_rol; ?>"><?php echo $state_text_vervang_rol; ?></span></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><img src="/img/icons/paper.png" height="20"></td>
                                <td>rolls in stock</td>

                                <td class="al-center"><strong><?php echo $rolls_in_stock; ?></strong></td>
                                <td></td>
                            </tr>
                            <?php } // end check if minimal setup ?>
                            <?php if ( $_SESSION['client_role'] == 'admin' && !$is_minimal_setup ) {?>
                             <tr>
                                <td><img src="/img/icons/cog-wheel.png" height="20"></td>
                                <td>testmode</td>
                                <td class="al-center"><span class="statustext <?php echo $class_test_mode; ?>"><?php echo $state_text_test_mode; ?></span></td>

                                <td></td>
                            </tr><? } ?>
                            <?php } ?>
                            <?php if ( !$is_minimal_setup ) { ?>
                            <tr>
                                <td><img src="/img/icons/powerplanner.png" height="20"></td>
                                <td nowrap="nowrap">setplanner</td>
                                <td class="al-center"><span class="statustext <?php echo $class_setplanner; ?>"><?php echo $state_text_setplanner; ?></span></td>
                                <td class="al-center"><a href="#" class="info_circle" data-toggle="modal" data-target="#setplannerInfoModal" title="Meer info">i</a></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                   

                   <?php
                    if ( $_SESSION['client_id'] <> '5' ) {

                         if ( $shelly_switch_only == 0 && !$is_minimal_setup ) { ?>
                        
                     
                        <table class="table  table-bordered table-striped table-sm devicetable">
                            <thead>
                                <tr>
                                    <td colspan="4">LAST WORKDAY</td>
                                </tr>
                            </thead>    
                            <tbody>
                                <tr>
                                    <td><strong>
                                        <?php
                                        if ( isset ($revenue_data['werkdag'])) {
                                        echo $revenue_data['werkdag']; 
                                        } ?></strong>
                                    </td>
                                    
                                    <td class="al-right">paid</td>
                                    <td class="al-right">free</td>
                                    <td class="al-right">amount</td>

                                <tr>
                                <tr>
                                    <td>Prints</td>    
                                    <td class="al-right"><strong>
                                        <?php
                                        if ( isset ($revenue_data['aantal_print_betaald'])) {
                                        echo $revenue_data['aantal_print_betaald']; 
                                        } ?>
                                    </strong></td>
                                    <td class="al-right"><strong>
                                        <?php
                                        if ( isset ($revenue_data['aantal_print_gratis'])) {
                                        echo $revenue_data['aantal_print_gratis']; 
                                        } ?>
                                    </strong></td>
                                    <td class="al-right"><strong>&euro; <?php 
                                    if ( isset ($revenue_data['bedrag_print'])) {
                                        echo number_format( $revenue_data['bedrag_print'],2 ,",","." );
                                    } ?></strong></td>
                                </tr>        
                                <tr>
                                    <td>QR-only</td>    
                                    <td class="al-right"><strong>
                                        <?php
                                        if ( isset ($revenue_data['aantal_qr_betaald'])) {
                                        echo $revenue_data['aantal_qr_betaald']; 
                                        } ?>
                                    </strong></td>
                                    <td class="al-right"><strong>
                                        <?php
                                        if ( isset ($revenue_data['aantal_qr_gratis'])) {
                                        echo $revenue_data['aantal_qr_gratis']; 
                                        } ?>
                                    </strong></td>
                                    <td class="al-right"><strong>&euro; <?php 
                                    if ( isset ($revenue_data['bedrag_qr'])) {
                                        echo number_format( $revenue_data['bedrag_qr'],2 ,",","." );
                                    } ?></strong>
                                    </td>
                                </tr>        
                                <tr>
                                    <td>Total</td>
                                    <td class="al-right" colspan="3">
                                    <strong>&euro; <?php 
                                    if ( isset ($revenue_data['bedrag_totaal'])) {
                                        echo number_format( $revenue_data['bedrag_totaal'],2 ,",","." );
                                    } ?></strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>  
                           
                        <table class="table  table-bordered table-striped table-sm devicetable">
                            <thead>
                                <tr>
                                    <td colspan="2">CURRENT SET</td>
                                </tr>
                            </thead>    
                            <tbody>
                                <tr>
                                    <td>Name:</td>
                                    <td><strong><?php echo $set_data->set_name;?></strong></td>
                                </tr>    
                                <tr>
                                    <td colspan="2" class="al-center">
                                    <img class="set-strook-img" style="border:1px solid #000;" src='https://www.magicshoot.nl/stats/thumbnail.php?path=<?php echo $resource_path; ?>&img=strook.png&event_id=<?php echo $event_id; ?>&width=200&height=200&maxw=200&maxh=200'>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                      

                        <?php } // end shelly switch only == 0 

                        // ANYDESK LINK
                           if ( $_SESSION['client_role'] == 'admin' && isset ( $anydesk_id ) ) { ?>
                            
                            <table class="table  table-bordered table-striped table-sm devicetable">
                                <thead>
                                    <tr>
                                        <td colspan="2">ANYDESK</td>
                                    </tr>
                                </thead>    
                                <tbody>
                                    <tr>
                                        <td>Open Anydesk:</td>
                                        <td><a href="anydesk:<?php echo $anydesk_id; ?>"><?php echo $anydesk_id; ?></a></td>
                                    </tr>   
                                </tbody>
                            </table>
                            <?php } 
                        } // end client_id <> 5?>
                    </div>
                 </div><!-- // end card-->  
                  
                <?php
                } // end foreach event
                ?>

                <!-- gedeelde info-popup voor de setplanner-regel (1x op de pagina, niet per kaart) -->
                <div class="modal fade" id="setplannerInfoModal" tabindex="-1" role="dialog" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">Setplanner</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Sluiten"><span aria-hidden="true">&times;</span></button>
                      </div>
                      <div class="modal-body">
                        <p>Met de <strong>setplanner</strong> plan je een afwijkende strookset voor een bepaalde periode.</p>
                        <p><strong>Let op:</strong> zolang zo'n geplande set actief is, wordt de <strong>powerplanner genegeerd</strong>.
                           De fotokast blijft dan onafgebroken aan tot de eindtijd van de geplande set &mdash; ook op dagen en tijden
                           waarop de powerplanner de kast normaal uit zou zetten.</p>
                        <p>Na de eindtijd van de set neemt de powerplanner het aan- en uitzetten weer over.</p>
                        <p>Als je de fotokast handmatig uit zet, en de set_planner staat aan, dan zal hij binnen 3 minuten weer aan gezet worden. Houd hier rekening mee.</p>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Sluiten</button>
                      </div>
                    </div>
                  </div>
                </div>
                
                
                </div>
                <!-- end row -->
              </div>
            


            <!-- / end col-->   

             <div class="col-sm-12 col-md-12 col-lg-12">
                 
                 <div class="row">
                <!--<h2>DEBUG</h2>
                    <div style="display: block;"> 
                    <?php 
                    
                    //echo "<pre>";
                    //print_r ( $shelly_device_status_array);
                    //echo "</pre>"; 
                 
                    ?>
                    </div>
                    <h2>TIMING</h2>
                    <div style="display: block;"></div>-->
                    <?php 
                    
                     //$timer['time_over_all'] = $time_over_all += microtime(true);

                  //  echo "<pre>";
                  //  print_r ( $timer); 
                    if ( isset($timer['time_over_all']) ) {
                      echo "Laadtijd pagina: " . round( $timer['time_over_all'], 3) . " seconden<br>";
                    } else {
                      echo "Laadtijd pagina: niet beschikbaar<br>";
                    }
                  //  echo "</pre>";
                    
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