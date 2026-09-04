<?php
global $cn; 

include '../inc/connection.inc.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ( !isset ( $_SESSION['client_id'] ) )
    {
    header('Location: https://stats.magicshoot.nl/index.php');
    exit;
    }
# check role
if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }    
$event_id       = $_SESSION['event_id'];
$day_of_week 	  = $_REQUEST['day_of_week'];
$start_time 	  = $_REQUEST['start_time'] ;	
$end_time 	    = $_REQUEST['end_time'];	
$power 	        = $_REQUEST['power'];	

$sql = "UPDATE photobooth_power_planner
           SET power       = '" . $power         . "'
         WHERE event_id    = '" . $event_id      . "'
           AND day_of_week = '" . $day_of_week   . "'
           AND start_time  = '" . $start_time    . "'
           AND end_time    = '" . $end_time      . "'";
$result = mysqli_query( $cn , $sql ); 

//echo $sql;
echo  $result;
?>