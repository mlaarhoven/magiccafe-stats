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
if ( ! ( $_SESSION['client_role'] == 'admin') ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }    
# page_invoice_all.php stuurt het event_id mee (meerdere kasten op één pagina), anders de sessie
$event_id            = isset( $_REQUEST['event_id'] ) ? (int)$_REQUEST['event_id'] : (int)$_SESSION['event_id'];
$set_sequence 		 = (int)$_REQUEST['set_sequence'];
$rental_boolean 	 = $_REQUEST['rental_boolean'];	

# als er een record in de tabel: photobooth_sequence_rental staat, dan telt deze mee als verhuur en anders niet

if ( $rental_boolean == 'false' ) {
    $sql= "DELETE  
             FROM photobooth_sequence_rental
			WHERE set_sequence  = '" . $set_sequence  . "' 
              AND event_id 	    = '" . $event_id 	  . "'";
} 
if ( $rental_boolean == 'true' ) {
    $sql= "INSERT INTO photobooth_sequence_rental
             SET set_sequence   = '" . $set_sequence  . "', 
                 event_id 	    = '" . $event_id 	  . "'";

}
echo $sql;

$result = mysqli_query( $cn , $sql );	
echo  $result;
?>