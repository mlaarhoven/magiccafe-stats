<?php

$shelly_auth_key = "OTg3MDV1aWQC83167038D985D86B5A01C58185739EAFA8D81ACDD5B146A6526145CBCC15EC5E818A872CC6CCEA0";
$shelly_url = "https://shelly-92-eu.shelly.cloud";
 $time = -microtime(true); 
//MAX 10 ELEMENTS!
  $params=['ids'=>[ //"2cbcbb9e6f48", // flora video
                    "2cbcbb9e97cc", // flora foto
                    "ec6260907824", // studio boven
                    "ec62608d32f4", // bobbi bar
                    "30c6f7824458", // portiertje
                    "ec6260828398", // oude enghe
                    "30c6f78b2910", // studio beneden
                    "ec62608b0188", // hermanos
                    "ec6260816d14", // stiels
                    "ec62608aa608", // cube rotterdam
                    "30c6f78b2010",  // studio beneden
                    "80f3dac87260"  // cafe het feest
                  ], 
           'select'=>["status"],
           'pick'=>['status'=>['switch:0','switch:1']]
           ];  
        
  $query_of_params =  json_encode($params);
  echo ''.$query_of_params.'';

  $url =  $shelly_url . '/v2/devices/api/get?auth_key='. $shelly_auth_key;
  $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($curl);
    curl_close($curl);
    $myData = json_decode($data, true);
    echo "<pre>";  
    print_r ( $myData);
    echo "</pre>";
    echo " Online= " . $myData['0']['online'];
    echo " Status Switch 0 = " . $myData['0']['status']['switch:0']['output'];
    echo " Status Switch 1 = " . $myData['0']['status']['switch:1']['output'];

    $time += microtime(true);
    echo "timer: " . $time;
 ?>