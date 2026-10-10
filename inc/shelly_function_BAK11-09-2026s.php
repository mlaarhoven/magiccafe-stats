  <?php 
    global $cn; 
    include ( '/home/magicshoot/domains/magicshoot.nl/public_html/inc/connection.inc.php' );

function get_all_shelly_status ()
    {   
    # https://shelly-api-docs.shelly.cloud/cloud-control-api/communication-v2
    # 1. haal alle event_id en shelly_device_id op uit de event tabel
    # 2. plaats deze per 10 in een array
    # 3. roep 1 of meer keer de Shelly v2 api aan met max 10 device_id's
    # 4. verzamel alle statussen in een array met:
    # [event_id][online]   // is het device online
    # [event_id][switch:0] // wat is de stand van  relais 1
    # [event_id][switch:1] // wat is de stand van relais 2
    # 5. return array
    # example :
    /*
    Array
    (
        [35] => Array
            (
                [online] => 1
                [switch:0] => 0
                [switch:1] => 0
            )

    )
    */


    global $cn;
    global $timer;
    if ( !is_array ( $timer ) ) { $timer = array(); }
    $debug_time_start   = -microtime(true);
    $debug_curl_time    = 0;
    $debug_sleep_time   = 0;
    $debug_curl_calls   = 0;
    $shelly_device_status_array = array();

    # haal de shelly auth_key en de url op
    $sql =    "SELECT auth_key, url
                 FROM photobooth_shelly_auth";
    $result             = mysqli_query( $cn , $sql );
    $return	            = mysqli_fetch_object ( $result );
    $shelly_url         = $return->url;
    $shelly_auth_key    = $return->auth_key;

    # haal shelly_device_ids en de event_ids op
     $sql =   "SELECT event_id, shelly_device_id
                FROM event
           LEFT JOIN photobooth_event_ids USING(event_id)
               WHERE event.shelly_device_id <> ''
                 AND photobooth_event_ids.dashboard_show ='1'";


    $result             = mysqli_query( $cn , $sql );
    $device_counter     = 0;
    $string_counter     = 0;
    while ( $return     = mysqli_fetch_object ( $result ) ) {
        $event_id                                           = $return->event_id;
        $shelly_device_id                                   = $return->shelly_device_id;
        $values[$string_counter][ $device_counter]          = $shelly_device_id;
        $values_event[$string_counter][ $device_counter]    = $event_id;
        $device_counter ++;
        if ( $device_counter == 10 ) {
            $string_counter++;
            $device_counter = 0;
        } 
    }
  
    for ( $i =0; $i <= $string_counter; $i++ ) {
        
        $params=['ids'=>$values[$i], 
                'select'=>["status"],
                'pick'=>['status'=>['switch:0','switch:1']]
                ];           
    

        $url =  $shelly_url . '/v2/devices/api/get?auth_key='. $shelly_auth_key;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $debug_curl_start = -microtime(true);
        $data = curl_exec($curl);
        $debug_curl_time += $debug_curl_start += microtime(true);
        $debug_curl_calls++;
        $myData = json_decode($data, true);


        for ( $r = 0 ; $r < count( $myData ); $r ++) {
            if ( isset( $values_event[$i][$r] )) { $event_id = $values_event[$i][$r];}
            if ( $event_id > 0 ) {
                    // online status
                    if ( isset ( $myData[$r]['online'] )) { $shelly_device_status_array[ $event_id ]['online'] = $myData[$r]['online']; } else { $shelly_device_status_array[ $event_id ]['online'] = ''; }
                    // switch status
                    $switch0value = 0; //default
                    $switch1value = 0; //default
                    if ( isset($myData[$r]['status']['switch:0'])) { if ( $myData[$r]['status']['switch:0']['output'] == 1) {  $switch0value = 1; }}
                    if ( isset($myData[$r]['status']['switch:1'])) { if ( $myData[$r]['status']['switch:1']['output'] == 1) {  $switch1value = 1; }}
                    
                    $shelly_device_status_array[ $event_id ]['switch:0'] = $switch0value;
                    $shelly_device_status_array[ $event_id ]['switch:1'] = $switch1value;
                    //mail ( "info@sitan.nl", 'DEBUG /inc/shelly_functions.php', serialize($shelly_device_status_array ) . "event_id=" . $event_id . " device_id=" . $shelly_device_id);
          
            }
        }
            
      //  sleep ( 2 ); //let op belangrijk, meerdere requests te snel achter elkaar geeft een error terug!
      # aangepast door sitan op 17-10-2025 - 1 keer sleep aanroepen ipv 2 keer en 1 seconden wachten ipv 2 gaat in totaal 4 x sneller
         if ( $i <> $string_counter )   {
            $debug_sleep_start = -microtime(true);
            usleep(3000000); //let op belangrijk, meerdere requests te snel achter elkaar geeft een error terug!
            $debug_sleep_time += $debug_sleep_start += microtime(true);
        }


    }

    $timer['get_all_shelly_status_detail']['total']       = $debug_time_start += microtime(true);
    $timer['get_all_shelly_status_detail']['curl_calls']   = $debug_curl_calls;
    $timer['get_all_shelly_status_detail']['curl_time']    = $debug_curl_time;
    $timer['get_all_shelly_status_detail']['sleep_time']   = $debug_sleep_time;

    if ( isset ( $myData['error'])){
        $error_message = $myData['data']['messages']['0'];
        $error_code = $myData['error'];
        # log error in log tabel
        $datetime 	 = date ("Y-m-d H:i:s");
        $ip 		 = $_SERVER['REMOTE_ADDR'];
        $log_id_apps = random_int(1,100000);

        $sql =   "INSERT INTO log
                            SET log_level       = '30', 
                                log_created     = '". $datetime. "', 
                                log_source      = 'magiccafe', 
                                log_action      = 'get_all_shelly_status', 
                                log_ip          = '".$ip."',
                                log_id_apps     = '" . $log_id_apps . "',
                                log_details     = '/stats/inc/shelly_functions.php error: " . $error_message . "'";
        $result   = mysqli_query( $cn , $sql );

        return $myData;   
    } else {
            
        return $shelly_device_status_array;    
    }
}

# slaat het resultaat van get_all_shelly_status() / get_one_shelly_status() op in photobooth_device_status,
# zodat het dashboard de status normaal uit de database kan lezen ipv steeds de Shelly api aan te roepen.
function save_shelly_status_to_db ( $shelly_device_status_array )
    {
    global $cn;
    foreach ( $shelly_device_status_array as $event_id => $status )
        {
        if ( !is_numeric ( $event_id ) ) { continue; } // bv. de 'error' key bij een Shelly api-fout overslaan

        $shelly_online = isset ( $status['online'] )   && $status['online']   <> "" ? (int) $status['online']   : 0;
        $shelly_out_0  = isset ( $status['switch:0'] ) && $status['switch:0'] <> "" ? (int) $status['switch:0'] : 0;
        $shelly_out_1  = isset ( $status['switch:1'] ) && $status['switch:1'] <> "" ? (int) $status['switch:1'] : 0;

        $sql = "UPDATE photobooth_device_status
                   SET shelly_online = '" . $shelly_online . "',
                       shelly_out_0  = '" . $shelly_out_0  . "',
                       shelly_out_1  = '" . $shelly_out_1  . "',
                       last_update   = NOW()
                 WHERE event_id      = '" . $event_id      . "'";
        mysqli_query ( $cn , $sql );
        }
    }

function get_one_shelly_status ( $event_id )
    {   
   

    global $cn; 
    $shelly_device_status_array = array();

    # haal de shelly auth_key en de url op
    $sql =    "SELECT auth_key, url
                 FROM photobooth_shelly_auth";
    $result             = mysqli_query( $cn , $sql );
    $return	            = mysqli_fetch_object ( $result );
    $shelly_url         = $return->url;
    $shelly_auth_key    = $return->auth_key;

    # haal shelly_device_ids en de event_ids op
     $sql =   "SELECT shelly_device_id
                FROM event
               WHERE event_id = '" . $event_id . "'";
    $result             = mysqli_query( $cn , $sql );
    $return             = mysqli_fetch_object ( $result ) ;
    $shelly_device_id   = $return->shelly_device_id;
    //echo $shelly_device_id;

    $params=['ids'=>[$shelly_device_id], 
                'select'=>["status"],
                'pick'=>['status'=>['switch:0','switch:1']]
                ];           
    
       
        $url =  $shelly_url . '/v2/devices/api/get?auth_key='. $shelly_auth_key;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($curl);
 
        $myData = json_decode($data, true);

            
        
    
        if ( isset($myData['error']) && $myData['error']<>"" ){
            $error_message ="";
               if ( isset($myData['data']['messages']['0'])) { $error_message = $myData['data']['messages']['0'];}
                $error_code = $myData['error'];
                # log error in log tabel
                $datetime 	 = date ("Y-m-d H:i:s");
                $ip 		 = $_SERVER['REMOTE_ADDR'];
                $log_id_apps = random_int(1,100000);

                $sql =   "INSERT INTO log
                                    SET log_level       = '30', 
                                        log_created     = '". $datetime. "', 
                                        log_source      = 'magiccafe', 
                                        log_action      = 'get_all_shelly_status', 
                                        log_ip          = '".$ip."',
                                        log_id_apps     = '" . $log_id_apps . "',
                                        log_details     = '/stats/inc/shelly_functions.php error:" .$error_code. " " .  $error_message . "'";
                $result   = mysqli_query( $cn , $sql );

                return $myData;   
        } else {
            $shelly_device_status_array[ $event_id ]['online'] = $myData[0]['online'];
            // switch status
            $switch0value = 0; //default
            $switch1value = 0; //default
            if ( $myData[0]['status']['switch:0']['output'] == 1) {  $switch0value = 1; } ;
            if ( isset($myData[0]['status']['switch:1'])) {
                # niet alle shellies hebben 2 relais
                if ( $myData[0]['status']['switch:1']['output'] == 1) {  $switch1value = 1; }; 
            }
            $shelly_device_status_array[ $event_id ]['switch:0'] = $switch0value;
            $shelly_device_status_array[ $event_id ]['switch:1'] = $switch1value;
            return  $shelly_device_status_array;    
        }
}



function set_switch ( $event_id, $switch_nr, $switch_value )
{
    global $cn;
    # switch_value = 'on' of 'off'
    # switch_nr = 0 of 1
    //echo "binnen gekomen switch value = " . $switch_value ."<br>";
    # haal de shelly device_id op die bij deze kast (event) hoort
    $sql =  "SELECT shelly_device_id
                FROM event
            WHERE event_id = '" . $event_id . "'";
    $result             = mysqli_query( $cn , $sql );
    $return	            = mysqli_fetch_object ( $result );
    $shelly_device_id   = $return->shelly_device_id;

    # haal de shelly auth_key en de url op
    $sql =    "SELECT auth_key, url
                 FROM photobooth_shelly_auth";
    $result             = mysqli_query( $cn , $sql );
    $return	            = mysqli_fetch_object ( $result );
    $shelly_url         = $return->url;
    $shelly_auth_key    = $return->auth_key;
    $switch_value_boolean = false;
    if ( $switch_value == 'on')  { $switch_value_boolean = true;}
    if ( $switch_value == 'off') { $switch_value_boolean = false;}
    //$shelly_device_id = '"' . $shelly_device_id .'"';
    $params=['id'       =>  $shelly_device_id, 
             'channel'  =>  $switch_nr,
             'on'       =>  $switch_value_boolean
            ];           

    $url =  $shelly_url . '/v2/devices/api/set/switch?auth_key='. $shelly_auth_key;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);    
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($curl);
    curl_close($curl);
    
    
    $myData = json_decode($data, true);

   // echo "<pre>";
   // print_r ( $myData );
   // echo "</pre>";
    sleep(2);

    if ( isset ( $myData['error']) ) {
        
        $error_code = $myData['error'];
        # log error in log tabel
        $datetime 	 = date ("Y-m-d H:i:s");
        $ip 		 = $_SERVER['REMOTE_ADDR'];
        $log_id_apps = random_int(1,100000);
        $sql =   "INSERT INTO log
                            SET event_id = '" . $event_id . "',
                                log_level       = '30', 
                                log_created     = '". $datetime. "', 
                                log_source      = 'magiccafe', 
                                log_action      = 'set_switch', 
                                log_ip          = '".$ip."',
                                log_id_apps     = '" . $log_id_apps . "',
                                log_details     = '/stats/inc/shelly_functions.php error:" . $error_code . "'";
         //echo $sql;
        $result   = mysqli_query( $cn , $sql );
         return $myData['error'];
    }
    else { 
        return 1;
    }
}



?>