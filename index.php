<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if($_SERVER['HTTPS']!="on")  
  {     
  $redirect= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];     
  header("Location:$redirect");  
  }
global $cn; 
include '../inc/connection.inc.php';
include 'inc/inlog_functions.php';

$mode = "";
if ( isset ( $_REQUEST['mode']) ) { $mode = mysqli_real_escape_string ( $cn, $_REQUEST['mode'] ); }
if ( array_key_exists ( 'event_id', $_SESSION )) { 
  if ( $_SESSION['event_id'] == '' )
    {
    $_SESSION['event_id']= 418;
    }
}  

if ( $mode == 'logout' )
  {
  logout(); 
  }


if ( $mode == 'login' )
    {
    $login_result = inloggen ();
   
    if ( $login_result == true )
      {
        if ( $_SESSION['client_id'] == 1 || $_SESSION['client_id'] == 2 || $_SESSION['client_id'] == 3)
        {
          header('Location: https://stats.magicshoot.nl/page_dashboard_fast.php');
        }
      else {
        header('Location: https://stats.magicshoot.nl/page_dashboard.php');
      }
      } 
   
    }

   
  // indien andere fotokast is gekozen:
  $event_id = ""; // init
  if ( isset ( $_REQUEST['event_id']) ) { $event_id = mysqli_real_escape_string( $cn, $_REQUEST['event_id']); }
  if ( $event_id <> '')
      {
      // check of de ingelogde client_id recht heeft om dit event_id te kiezen:
      $sql = "SELECT event_id
                FROM photobooth_stat_client_event
               WHERE client_id = '" . $_SESSION['client_id']. "' AND
                     event_id  = '" . $event_id . "'";
      $result = mysqli_query( $cn , $sql );
      $num 		= mysqli_num_rows ( $result );
      if ( $num > 0 )
        {
          // save de nieuwe current event_id in de sessie
        $_SESSION['event_id'] = $event_id;
        header('Location: https://stats.magicshoot.nl/page_dashboard.php?mode=single');
        }
      else {
        echo " <strong>ERROR: de ingelogde user heeft geen recht om deze fotokast te bekijken/bewerken!</strong>. <br>Hier is een security melding van gedaan naar MagicShoot!<br><br>";
        mail ( "warnings@magicshoot.nl", "SECURITY WARNING verkeerde fotokast gekozen in photobooth admin!", "sql = $sql");
        }  
      }
   if ( isset ( $_REQUEST['mode']) ) { $mode = mysqli_real_escape_string( $cn, $_REQUEST['mode']); }
   if ( $mode == 'stroken' && $event_id <> '' )
   {
    # redirect naar de strookjes pagina
     header('Location: https://stats.magicshoot.nl/page_gallery.php');
   }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <base href="./">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="description" content="MagicShoot Screens">
    <meta name="author" content="Sitan van Sluis">
    <meta name="keyword" content="Admin">
    <title>MagicShoot Photobooth Statistieken</title>
    <link rel="apple-touch-icon" sizes="180x180" href="assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="assets/favicon/site.webmanifest">
    <link rel="mask-icon" href="assets/favicon/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="assets/favicon/favicon.ico">
    <meta name="msapplication-TileColor" content="#da532c">
    <meta name="msapplication-config" content="assets/favicon/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <meta name="theme-color" content="#ffffff">
    <!-- Main styles for this application-->
    <link href="css/style.css" rel="stylesheet">
   

     
    

    <link href="vendors/@coreui/chartjs/css/coreui-chartjs.css" rel="stylesheet">
  </head> 
  
<body class="c-app flex-row align-items-center">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-sm-8 md-5">
            <?
            // als je hier komt ben je dus niet geredirect en kan je het inlogscherm tonen
            login(); 

            if ( $mode == 'login' && inloggen() == false)
              {
              ?><div class="alert alert-danger" role="alert">Het inloggen is niet gelukt, controleer uw e-mailadres en wachtwoord.</div><?
              }

           if ( isset ( $_SESSION['client_id']) )
              {
              if ( $_SESSION['client_id'] > 0 ) 
                {
                ?><div class="alert alert-success" role="alert">U bent al ingelogd. Klik <a href="https://stats.magicshoot.nl/page_dashboard_fast.php">hier</a> om verder te gaan.</div><? 
                } 
              } 
            ?>
   </div>
      </div>
    </div>
    <!-- CoreUI and necessary plugins-->
    <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <!--[if IE]><!-->
    <script src="vendors/@coreui/icons/js/svgxuse.min.js"></script>
    <!--<![endif]-->

  </body>
</html>


