<?php
  include ("inc/shelly_functions_debug.php"); 
 $time = -microtime(true); 
  $shelly_device_status_array = get_all_shelly_status ();
   $time += microtime(true);
    echo "timer: " . $time;
      echo "<pre>";
                    print_r ( $shelly_device_status_array);
                    echo "</pre>"; 
 ?>