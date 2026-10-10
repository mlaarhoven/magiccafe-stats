<?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  include ('../inc/connection_invoicesystem.inc.php'); //maak connectie met de infosysteem_2011 database
  // error_reporting(E_ALL & ~E_NOTICE);

  # check role
  if ( !$_SESSION['client_role'] == 'admin'  ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }
  # --- INIT --- #
  $mode             = "";
  $year_aangepast   = 0; 
  $month_aangepast  = 0; 
  $year             = ""; 
  $month            = "";


  $event_id = $_SESSION['event_id'];
  if ( isset ( $_REQUEST['mode'] )) { $mode     = mysqli_real_escape_string( $cn, $_REQUEST['mode']);}
 
  if ( $mode == "save_invoice" )
    {
    if ( isset ($_REQUEST['month']) ) { $month = $_REQUEST['month']; }
    if ( isset ($_REQUEST['year']) ) { $year   = $_REQUEST['year']; }
    $event_id = $_SESSION['event_id'];

    # haal de afgesproken rental price en percentage op
    $sql = "SELECT * 
              FROM photobooth_percentage_pricing 
             WHERE event_id = $event_id AND '" . $year . "-" . $month . "-01' BETWEEN start_date AND end_date";
    
    $result          = mysqli_query( $cn , $sql );
    $return	         = mysqli_fetch_object ( $result );
    $percentage_bar  = $return->percentage_bar;
    $rental_price    = $return->rental_price;  

    $order_description = "Verrekening percentage opbrengst en verhuur fotokast($event_id) voor maand: $month en jaar: $year. ";
    $order_number_from_customer = "Photobooth verrekening";

    # haal met het event_id de client_id op en bepaal dan de customer_id die hoort bij het invoicesysteem
    # aangepast op 24-01-2025 door sitan
    $sql_cust     = "SELECT customer_id 
                       FROM client 
                  LEFT JOIN event USING(client_id) 
                      WHERE event.event_id ='" .  $event_id  . "'";
    $result_cust   = mysqli_query( $cn , $sql_cust );
    $return_cust   = mysqli_fetch_object ( $result_cust );
    $customer_id   = $return_cust->customer_id;
    
    /* 
    onderstaande komt nu uit database : zet de customer_id uit het Factuursysteem in de tabel client.

    if ( $event_id == 418 ) { 
      $customer_id = 421; //cafe studio
      $order_number_from_customer = "Photobooth verrekening";
    }
    if ( $event_id == 429 ) { 
      $customer_id = 427; //bobbi bar
      $order_number_from_customer = "Photobooth verrekening";
    }

    if ( $event_id == 450 ) { 
      $customer_id = 433; // baja beach bar
      $order_number_from_customer = "Photobooth verrekening";
    }

    if ( $event_id == 451 ) { 
      $customer_id = 439; //cafe XO
      $order_number_from_customer = "Photobooth verrekening";
    }
    */
    
    # insert order in photobooth_orders (or update if already exists)
    # Check if record already exists
    $sql_check = "SELECT order_id FROM photobooth_orders 
                  WHERE event_id = '" . $event_id . "' 
                  AND order_maand = '" . $month . "' 
                  AND order_jaar = '" . $year . "'";
    $result_check = mysqli_query( $cn , $sql_check );
    $existing_record = mysqli_fetch_object( $result_check );
    
    if ( $existing_record ) {
      # Update existing record
      $sql = "UPDATE photobooth_orders
              SET order_created               = NOW(),
                  customer_id                 = '" . $customer_id                 . "',
                  order_description           = '" . $order_description           . "',
                  order_number_from_customer  = '" . $order_number_from_customer  . "'
              WHERE event_id = '" . $event_id . "' 
              AND order_maand = '" . $month . "' 
              AND order_jaar = '" . $year . "'";
      $result   = mysqli_query( $cn , $sql );
      $order_id = $existing_record->order_id;
      echo "Update order result = " . $result . " Order_id = $order_id <br>";
    } else {
      # Insert new record
      $sql = "INSERT INTO photobooth_orders
                      SET event_id                    = '" . $event_id                    . "',
                          order_maand                 = '" . $month                       . "',
                          order_jaar                  = '" . $year                        . "',
                          order_created               = NOW(),
                          customer_id                 = '" . $customer_id                 . "',
                          order_description           = '" . $order_description           . "',
                          order_number_from_customer  = '" . $order_number_from_customer  . "'";
      //echo $sql . "<br>";
      $result   = mysqli_query( $cn , $sql );
      $order_id =  mysqli_insert_id($cn);
      echo "Opslaan order result = " . $result . " Order_id = $order_id <br>";
    }

    # opslaan in FACTUURSYSTEEM (or update if already exists)
    $sql_info_check = "SELECT order_id FROM orders 
                       WHERE event_id = '" . $event_id . "' 
                       AND order_maand = '" . $month . "' 
                       AND order_jaar = '" . $year . "'";
    $result_info_check = mysqli_query( $cn_info , $sql_info_check );
    $existing_record_info = mysqli_fetch_object( $result_info_check );
    
    if ( $existing_record_info ) {
      # Update existing record in invoice system
      $sql_info = "UPDATE orders
                   SET order_created               = NOW(),
                       customer_id                 = '" . $customer_id                 . "',
                       order_description           = '" . $order_description           . "',
                       order_number_from_customer  = '" . $order_number_from_customer  . "'
                   WHERE event_id = '" . $event_id . "' 
                   AND order_maand = '" . $month . "' 
                   AND order_jaar = '" . $year . "'";
      $result_info   = mysqli_query( $cn_info , $sql_info );
      $order_id_info = $existing_record_info->order_id;
      echo "Update order IN FACTUURSYSTEEM result = " . $result_info . " Order_id = $order_id_info <br>";
    } else {
      # Insert new record in invoice system
      $sql_info = "INSERT INTO orders
                      SET event_id                    = '" . $event_id                    . "',
                          order_maand                 = '" . $month                       . "',
                          order_jaar                  = '" . $year                        . "',
                          order_created               = NOW(),
                          customer_id                 = '" . $customer_id                 . "',
                          order_description           = '" . $order_description           . "',
                          order_number_from_customer  = '" . $order_number_from_customer  . "',
                          order_termination_date      = '0000-00-00'";
      $result_info   = mysqli_query( $cn_info , $sql_info );
      $order_id_info =  mysqli_insert_id( $cn_info );
      echo "Opslaan order IN FACTUURSYSTEEM result = " . $result_info . " Order_id = $order_id_info <br>";
    }

    # --- insert charge regels in photobooth_charge, with the order_id --- #

    # zorg dat de log_session tabel goed gevuld is, met de laatste logs
    mysqli_query( $cn, 'CALL FillSequence()');
     
		
    # Initialize totals before processing charges
    $totaal_aantal              = 0;
    $totaal_bedrag              = 0;
    $totaal_aantal_verhuur      = 0;
    $totaal_verhuur             = 0;
    
    # haal de set_sequenties op
    $sql = 'SELECT 
              event_id,
              YEAR(sessie_timestamp) as jaar, 
              MONTH(sessie_timestamp) AS maand, 
              set_sequence,
              set_id,set_name,
              sessie_soort,
              prijs, 
              MIN(sessie_timestamp) AS van, 
              MAX(sessie_timestamp) AS tot, 
              SUM(prijs) AS totaal_log,
              COUNT(*) AS aantal
				 FROM log_sessionOK
				WHERE (event_id='.$event_id.')
          AND (YEAR(sessie_timestamp) ='. $year .')
          AND (MONTH(sessie_timestamp)='. $month .')
				GROUP BY event_id, jaar, maand, set_sequence';

    $result = mysqli_query( $cn , $sql );
    while ( $return	= mysqli_fetch_object ( $result ) )
      {
      
      $verhuur_bedrag   = 0;  
      $charge_year      = $return->jaar;
      $charge_month     = $return->maand;
      $set_sequence     = $return->set_sequence;
      $set_id           = $return->set_id;
      $set_name         = $return->set_name;
      $van              = $return->van;
      $tot              = $return->tot;
      $sessie_soort     = $return->sessie_soort;
      $prijs            = $return->prijs;
      $bedrag           = $return->totaal_log;
      $aantal           = $return->aantal;

      # haal met de set_sequence op of deze regel is aangevinkt als verhuur
      $rental_check     = get_checked( $set_sequence, $event_id );

      if ( $bedrag == 0 ) { 
        $verhuur_bedrag = -$rental_price; 
      }
      
      # verhoog de totalen, indien regel geen verhuur is of als verhuur mee telt
      if ( $prijs > 0 ) {
        $totaal_aantal += $aantal;
        $totaal_bedrag += $bedrag;
      } else {
          
        if ( $rental_check == 'checked') {
            # verhoog verhuur totaal bedrag en aantal alleen als de verhuur regel is aangevinkt
            $totaal_aantal_verhuur += $aantal;
            $totaal_verhuur        += $verhuur_bedrag;
        }

      }
      
      $charge_start_date         = $van;
      $charge_end_date           = "0000-00-00"; // anders factureert hij deze regel niet...$tot;
      $charge_description_id     = 13;          # 13 = photobooth_verrekening

      if ( $prijs == 0 ) {
        # verhuur
        if ( $rental_check == 'checked') {
          # telt mee
          $charge_description_manual = "set_id: " .  $set_id . " - " . $set_name . " - "  . $sessie_soort . " ( $aantal stuks) - Verhuur - prijs ex BTW = " . round ( ($rental_price / 1.21 ),2);
          $charge_price              =  $rental_price / 1.21;
          $charge_subtotal           =  $rental_price / 1.21;
          $charge_qty                =  1;
        } else {
          # telt niet mee
          $charge_description_manual = "set_id: " . $set_id . " - " . $set_name . " - "  . $sessie_soort . " ( $aantal stuks) - Verhuur - niet in rekening gebracht";
          $charge_price              = 0;
          $charge_subtotal           = 0;
          $charge_qty                = 0;
        }
      } else {
        # geen verhuur
        $charge_description_manual = "CREDIT set_id: " . $set_id . " - " . $set_name . " - "  . $sessie_soort . " - Percentage voor klant: " . $percentage_bar * 100 . " %";
        $charge_price              = $percentage_bar * ( - ($prijs  / 1.21) / 100 );
        $charge_subtotal           = $percentage_bar * ( - ($bedrag / 1.21) / 100 );
        $charge_qty                = $aantal;
      }
      
      $charge_type               = 'direct';
     
      $charge_period             = 'stuk';
      
      
      if ( $charge_price > 0 ){
        $Gb_ID                     = '8340';      # Grootboek code in e-boekhouden voor Photobooth voor omzet
        }
      if ( $charge_price < 0 ){
        $Gb_ID                     = '4044';      # Grootboek code in e-boekhouden voor Photobooth voor verdeling opbrengst
      }
      
      # Escape strings for SQL
      $charge_description_manual_escaped = mysqli_real_escape_string( $cn, $charge_description_manual );
      
      $sql2      = "INSERT INTO photobooth_charge
                            SET order_id                        = '" . $order_id                    . "',
                                Gb_ID                           = '" . $Gb_ID                       . "',
                                event_id                        = '" . $event_id                    . "',
                                charge_year                     = '" . $charge_year                 . "',
                                charge_month                    = '" . $charge_month                . "',
                                set_sequence                    = '" . $set_sequence                . "',
                                set_id                          = '" . $set_id                      . "',
                                charge_start_date               = '" . $charge_start_date           . "',
                                charge_end_date                 = '" . $charge_end_date             . "',
                                charge_description_id           = '" . $charge_description_id       . "',
                                charge_description_manual       = '" . $charge_description_manual_escaped   . "',
                                charge_type                     = '" . $charge_type                 . "',
                                charge_qty                      = '" . $charge_qty                  . "',
                                charge_period                   = '" . $charge_period               . "',
                                charge_price                    = '" . $charge_price                . "',
                                charge_subtotal                 = '" . $charge_subtotal             . "'";
      $result2   = mysqli_query( $cn , $sql2 );
      echo "Opslaan charge regel result = " . $result2 . "<br>";

      # LET OP: deze query gebruikt een andere order_id, namelijk: order_id_info ipv order_id
      $sql2_info = "INSERT INTO charge
                            SET order_id                    = '" . $order_id_info               . "',
                                Gb_ID                       = '" . $Gb_ID                       . "',
                                event_id                    = '" . $event_id                    . "',
                                charge_year                 = '" . $charge_year                 . "',
                                charge_month                = '" . $charge_month                . "',
                                set_sequence                = '" . $set_sequence                . "',
                                set_id                      = '" . $set_id                      . "',
                                charge_start_date           = '" . $charge_start_date           . "',
                                charge_end_date             = '" . $charge_end_date             . "',
                                charge_description_id       = '" . $charge_description_id       . "',
                                charge_description_manual   = '" . $charge_description_manual_escaped   . "',
                                charge_type                 = '" . $charge_type                 . "',
                                charge_qty                  = '" . $charge_qty                  . "',
                                charge_period               = '" . $charge_period               . "',
                                charge_price                = '" . $charge_price                . "',
                                charge_subtotal             = '" . $charge_subtotal             . "',
                                charge_next_invoice_date	  = '" . date ( "Y-m-d")              . "'";
    $result2_info   = mysqli_query( $cn_info , $sql2_info );
    echo "Opslaan charge regel in FACTUURSYSTEEM result = " . $result2_info . "<br>";
    }
 
  }


  if ( $mode == "save_year" )
    {
    $year   = mysqli_real_escape_string( $cn, $_REQUEST['year']);
    $_SESSION['year'] = $year;
    $year_aangepast = 1; 
    }
  if ( $mode == "save_month" )
    {
    $month   = mysqli_real_escape_string( $cn, $_REQUEST['month']);
    $_SESSION['month'] = $month;
    $month_aangepast = 1; 
    }
  if ( !isset ( $_SESSION['year'] )) 
   
    { 
    $year = date("Y");
    $_SESSION['year'] = $year;
    }
  else 
    {
    $year = $_SESSION['year'];
    }  

  if ( !isset ($_SESSION['month'] ))
    { 
    $month = date("m");
    $_SESSION['month'] = $month;
    }
  else 
    {
    $month = $_SESSION['month'];
    }   
    

  if ( $month < 10 ) 
    { 
        $month_met_nul = "0" . $month;
    }   

  if ( $month >= 10 ) 
    { 
      $month_met_nul = $month;
    }   

  
  
  if ( isLeapYear ( $year ) == true ) 
    { 
    // leap year - schrikkeljaar
    $daysOfMonth      = array( 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    }
  else
    {
    $daysOfMonth      = array( 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    } 

 

  $dateStart = $year . "-" . ( $month_met_nul ) . "-01"; 
  $dateEnd   = $year . "-" . ( $month_met_nul ) . "-" . $daysOfMonth[ $month -1 ]; 
  
  function isLeapYear($year)  {
    if (($year/4)  != floor($year/4))  return false;
    if (($year/100) != floor($year/100)) return true;
    if (($year/400) != floor($year/400)) return false;
    return true;
  }
                          
?>

  <script>
    // update rental	
    function onChangeRental(  set_sequence ) 
        {
        var veld = 'rental#' + set_sequence;
        
       
        var rental_boolean = document.getElementById( veld ).checked;
        console.log ("rental_boolean = " + rental_boolean );

        $.post( "save_rental.php",  { rental_boolean: rental_boolean, set_sequence:set_sequence}, function( data ) 
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
        <li class="breadcrumb-item">(Credit) Factuur inzicht maand voor: <?php echo $year; ?> en maand: <?php echo $month; ?></li>
        
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
            <div class="col-sm-12 col-md-12 col-lg-12">
              <div class="card">
                <div class="card-header bg-facebook content-center">
                  <h2 class="text-white text-uppercase">(Credit) Facuur inzicht per maand - <?php echo $year; ?> en maand: <?php echo $month; ?></h2>
                </div>
                <div class="card-body">
                  <a href="page_invoice_overview.php" class="btn btn-success">Maandafrekening</a>
                  <a href="page_monthly_overview.php" class="btn btn-info">Omzet per maand</a>
                  <a href="page_revenue_per_evening.php" class="btn btn-info">Omzet per werkdag/avond</a>
                  <a href="page_revenue_per_workweek.php" class="btn btn-info">Omzet per werkweek</a>
                  <a href="page_session_overview.php?aantal=40" class="btn btn-warning">Sessie overzicht</a>
                  <a href="page_transaction_overview.php" class="btn btn-warning">Transactie overzicht</a><br><br>
                  <?php
                    if ( $year_aangepast == 1 ) {
                        ?>
                        <div class="alert alert-success" role="alert">
                          Het getoonde jaar is aangepast naar: <?php echo $year; ?>
                        </div>
                        <?php
                    } 

                    if ( $month_aangepast == 1 ) {
                        ?>
                        <div class="alert alert-success" role="alert">
                          De getoonde maand is aangepast naar: <?php echo $month; ?>
                        </div>
                        <?php
                    } 
                  ?>   
              
                  Kies een ander jaar:
                  <form action="page_invoice_overview.php" action="post">
                    <input type="hidden" name="mode" value="save_year">
                    
                    <select name="year" onchange="this.form.submit()">
                        <?php 
                        for ( $y = 2023; $y < 2060; $y ++ )
                        {
                          ?><option value="<?php echo $y; ?>"
                          <? if ( $y == $year ) { echo " selected"; } ?>
                          ><?php echo $y; ?></option><?php
                        }
                        ?>
                    </select>
                  </form>

                  Kies een andere maand:
                  <form action="page_invoice_overview.php" action="post">
                    <input type="hidden" name="mode" value="save_month">
                    
                    <select name="month" onchange="this.form.submit()">
                        <?php 
                        for ( $m = 1; $m <= 12; $m ++ )
                        {
                          ?><option value="<?php echo $m; ?>"
                          <? if ( $m == $month ) { echo " selected"; } ?>
                          ><?php echo $m; ?></option><?php
                        } 
                        ?>
                    </select>
                  </form> 
                  <br>
                  <br>
                  <p>Let op: dit overzicht is niet helemaal realtime: de cijfers die we uit Payter halen voor de controle op de transacties komen met maximaal 10 minuten vertraging binnen.</p>
                  <p>Verder worden onze eigen logs (die de photobooth genereerd) wel realtime verstuurd, tenzij er een internet hickup is. Dan wordt het uploaden van logs gestopt.<br>
                    In dat geval wordt de logfile pas de volgende dag geupload als de photobooth herstart is. Het kan dus zijn dat de cijfers pas helemaal kloppen na een dag + herstart.</p>
              
                  <?php
                        # haal gegevens op voor deze klant
                        $sql             = "SELECT * 
                                             FROM photobooth_percentage_pricing 
                                             WHERE event_id = $event_id AND '" . $year . "-" . $month . "-01' BETWEEN start_date AND end_date";
                        //echo $sql . "<br>";
                        $result          = mysqli_query( $cn , $sql );
                        $return	         = mysqli_fetch_object ( $result );
                        $percentage_bar  = $return->percentage_bar;
                        $rental_price    = $return->rental_price;
                        //echo "percentage_bar = $percentage_bar<br>";
                        //echo "rental_price = $rental_price<br>";
                  ?>
                  <h3>Prijsafspraken voor de gekozen jaar + maand</h3>
                  <br>
                        
                  <table class="table table-responsive-sm table-bordered table-striped table-sm">
                    <tr>
                      <td><strong>Percentage bar</strong></td>
                      <td><?php echo $percentage_bar * 100; ?> %</td>
                    </tr>
                    <tr>
                      <td><strong>Prijs verhuur</strong></td>
                      <td>&euro; <?php echo $rental_price; ?></td>
                    </tr>
                  </table>  

                        
                  <h3>Welke sets zijn er wanneer gebruikt?</h3>
                  <br>
                  <p>Bedragen zijn inclusief BTW.</p>
                  <table class="table table-responsive-sm table-bordered table-striped table-sm">
                    <thead>
                       
                      <td align="right"><strong>jaar</strong></td>
                      <td align="right"><strong>maand</strong></td>
                      <td align="right"><strong>set_sequence</strong></td>
                      <td align="right"><strong>set_id</strong></td>
                      <td align="right"><strong>set_name</strong></td>
                      <td align="right"><strong>van</strong></td>
                      <td align="right"><strong>tot</strong></td>
                      <td align="right"><strong>soort</strong></td>
                      <td align="right"><strong>prijs per stuk</strong></td>
                      <td align="right"><strong>aantal betaald</strong></td>
                      <td align="right"><strong>aantal verhuur</strong></td>
                      <td align="right"><strong>totaal opbrengst</strong></td>
                      <td align="right"><strong>totaal verhuur</strong></td>
                      <td align="right"><strong>telt mee als verhuur?</strong></td>
                      
                      <?php if ( $_SESSION['client_id'] == 1 || $_SESSION['client_id'] == 2 ) { ?>
                        <td align="right"><strong>verhuur ja/nee</strong></td>
                      <?php } ?>

                      <td align="right"><strong>is gefactureerd?</strong></td>
                    
                    </thead>
                    
                    <?php
                    setlocale(LC_MONETARY, "nl_NL");    

                    # functies
                    // TODO gewoon erbij joinen?
                    function get_checked( $set_sequence, $event_id) {
                      global $cn;
                      $sql      = "SELECT COUNT(*) as aantal FROM photobooth_sequence_rental WHERE event_id = $event_id AND set_sequence = $set_sequence";
                      $result   = mysqli_query( $cn , $sql );
                      $return	  = mysqli_fetch_object ( $result ); 
                      $aantal   = $return->aantal;
                      if ( $aantal > 0 ) { return "checked";} else { return "";}
                    }
                    // TODO gewoon erbij joinen?
                    function get_invoiced( $set_sequence, $event_id) {
                      global $cn;
                      $sql      = "SELECT COUNT(*) as aantal FROM photobooth_charge WHERE event_id = $event_id AND set_sequence = $set_sequence";
                      $result   = mysqli_query( $cn , $sql );
                      $return	  = mysqli_fetch_object ( $result ); 
                      $aantal   = $return->aantal;
                      if ( $aantal > 0 ) { return true; } else { return false;}
                    }
                  
                  
                    # werk de log_session tabel bij met de laatste logs
                    mysqli_query( $cn, 'CALL FillSequence()');

                    # haal de set_sequences op

                    # zet totalen op 0
                    $totaal_aantal         = 0;
                    $totaal_bedrag         = 0;
                    $totaal_aantal_verhuur = 0;
                    $totaal_verhuur        = 0;

                    $sql = 'SELECT event_id,
                                   YEAR(sessie_timestamp) as jaar, 
                                   MONTH(sessie_timestamp) AS maand, 
                                   set_sequence,
                                   set_id,
                                   set_name,
                                   sessie_soort,
                                   prijs, 
                                   MIN(sessie_timestamp) AS van, 
                                   MAX(sessie_timestamp) AS tot, 
                                   SUM(prijs) AS totaal_log,
                                   COUNT(*) AS aantal
                              FROM log_sessionOK
                             WHERE (event_id='.$event_id.')
                               AND (YEAR(sessie_timestamp) ='. $year  .')
                               AND (MONTH(sessie_timestamp)='. $month .')
                          GROUP BY event_id, jaar, maand, set_sequence';
                    
                    $result      = mysqli_query( $cn , $sql );
                    //echo $sql . "<br>";
                    while ( $return = mysqli_fetch_object ( $result ) ) {
                      $verhuur_bedrag = 0;  


                      $jaar           = $return->jaar;
                      $maand          = $return->maand;
                      $set_sequence   = $return->set_sequence;
                      $set_id         = $return->set_id;
                      $set_name       = $return->set_name;
                      $van            = $return->van;
                      $tot            = $return->tot;
                      $sessie_soort   = $return->sessie_soort;
                      $prijs          = $return->prijs;
                      $bedrag         = $return->totaal_log;
                      $aantal         = $return->aantal;

                      if ( $bedrag == 0 ) { 
                        $verhuur_bedrag = -$rental_price; 
                      }
                      
                      $rental_check     = get_checked( $set_sequence, $event_id );
                      $invoiced_check   = get_invoiced( $set_sequence, $event_id );
                      
                      # verhoog de totalen, indien regel geen verhuur is of als verhuur mee telt
                      if ( $prijs > 0 ) {
                        $totaal_aantal += $aantal;
                        $totaal_bedrag += $bedrag;

                      } else {  
                        if ( $rental_check == 'checked') {
                            # verhoog alleen als aangevinkt
                            $totaal_aantal_verhuur += $aantal;
                            $totaal_verhuur += $verhuur_bedrag;
                        }

                      }

                      ?>
                      <tr>
                          <td align="right"><?php echo $jaar; ?></td>
                          <td align="right"><?php echo $maand; ?></td>
                          <td align="right"><?php echo $set_sequence; ?></td>
                          <td align="right"><?php echo $set_id; ?></td>
                          <td align="right"><?php echo $set_name; ?></td>
                          <td align="right"><?php echo $van; ?></td>
                          <td align="right"><?php echo $tot; ?></td>
                          <td align="right"><?php echo $sessie_soort; ?></td>
                          <td align="right">&euro; <?php echo number_format( $prijs/100,2 ,",","." ); ?></td>
                          <?php 
                          if ( $prijs == 0  ) {
                            ?><td align="right">0</td>
                              <td align="right"><?php echo $aantal; ?></td>
                            <?php
                          } else {
                            ?><td align="right"><?php echo $aantal; ?></td>
                              <td align="right">0</td>
                            <?php
                          } ?>
                          

                          <td align="right">&euro; <?php echo number_format( $bedrag/100,2 ,",","." ); ?></td>
                          <td align="right">&euro; <?php echo number_format( $verhuur_bedrag,2 ,",","." ); ?></td>
                          <td align="center"><?php 
                            

                            if ( $rental_check == "checked" ) {
                              ?>&nbsp;&nbsp;<span class="badge rounded-pill  btn-success">ja</span><?php 
                            }
                            else {
                                ?>&nbsp;&nbsp;<span class="badge rounded-pill btn-danger">nee</span><?php
                            } ?>
                            </td><?php
                                                              
                          ?>
                          
                          
                            <?php 
                            if ( $_SESSION['client_id'] == 1 || $_SESSION['client_id'] == 2 ) {
                              ?><td><?php
                              if ( $invoiced_check == false ) {
                                ?><input type="checkbox" value="1" id="rental#<?php echo $set_sequence; ?>" onChange="onChangeRental(<?php echo $set_sequence; ?>)" 
                                  
                                <?php echo $rental_check ?> 
                                
                                ><?php
                              }
                              else {
                                 echo "al gefactureerd"; 
                              }
                              ?></td><?php
                            } 
                            ?>

                          
                          <td align="center">
                            <?php if ( $invoiced_check == true ) { ?>
                            <span class="badge rounded-pill  btn-success">ja</span>
                            <?php } else { ?>
                            <span class="badge rounded-pill btn-info">nee</span>
                            <?php } ?>
                          </td>
                      </tr>
                      <?php
                    } ?>
                    
                    <tr>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                      <td></td>
                      
                      <td align="right"><?php echo $totaal_aantal;?></td>
                      <td align="right"><?php echo $totaal_aantal_verhuur;?></td>
                      <td align="right"><strong>&euro; <?php echo number_format( $totaal_bedrag/100,2 ,",","." ); ?></strong></td>
                      <td align="right"><strong>&euro; <?php echo number_format( $totaal_verhuur,2 ,",","." ); ?></strong></td>
                    </tr>
                  </table>


                  <h3>Controle</h3>
                  <br>
                  <table class="table table-responsive-sm table-bordered table-striped table-sm">
                    <thead>
                      <tr>
                       
                        <td align="right"><strong>jaar</strong></td>
                        <td align="right"><strong>maand</strong></td>
                        <td align="right"><strong>aantal prints</strong></td>
                        <td align="right"><strong>bedrag prints</strong></td>
                        <td align="right"><strong>aantal qr</strong></td>
                        <td align="right"><strong>bedrag qr</strong></td>
                        <td align="right"><strong>totaal aantal transacties</strong></td>
                        <td align="right"><strong>bedrag totaal</strong></td>
                        <td align="right"><strong>controle aantal Payter</strong></td>
                        <td align="right"><strong>controle bedrag Payter</strong></td>
                      </tr>  
                    </thead>
                    
                      <?php
                          setlocale(LC_MONETARY, "nl_NL");    

                          $sql = 'SELECT 
                              -- payter
                              payterOK.event_id, 
                              YEAR(payterOK.txnTimestampLocal) as jaar, 
                              MONTH(payterOK.txnTimestampLocal) AS maand, 
                              COUNT(payterOK.transactionId) as nr_payter,
                              SUM(payterOK.committedAmount)/100 AS amount_payter,
                              
                              -- log_session
                              COUNT(log_sessionOK.txnid) as nr_total,
                              SUM(log_sessionOK.prijs)/100 AS amount_total,
                              SUM( if(log_sessionOK.sessie_soort="print",1,0)) AS nr_print, 
                              SUM( if(log_sessionOK.sessie_soort="print",prijs,0))/100 AS amount_print,
                              SUM( if(log_sessionOK.sessie_soort="qr",1,0)) AS nr_QR,	
                              SUM( if(log_sessionOK.sessie_soort="qr",prijs,0))/100 AS amount_QR
                      
                            FROM payterOK LEFT JOIN log_sessionOK ON 
                                    payterOK.serialNumber =log_sessionOK.txnsn AND
                                    payterOK.transactionId=log_sessionOK.txnid

                            WHERE	      payterOK.event_id='.$event_id.'
                              AND YEAR( payterOK.txnTimestampLocal) = '. $year  .'
                              AND MONTH(payterOK.txnTimestampLocal) ='. $month .'

                            GROUP BY payterOK.event_id, YEAR(payterOK.txnTimestampLocal), MONTH(payterOK.txnTimestampLocal)';
                              //echo $sql . "<br>";
                          
                         
                          $result      = mysqli_query( $cn , $sql );
                          $aantal_rows = mysqli_num_rows ( $result);
                          if ( $aantal_rows == 0 ) { echo "<tr><td colspan='9'>Er zijn geen transacties gevonden bij Payter, eventuele aantallen prints zijn waarschijnlijk of gratis (verhuur) of tests geweest.</td></tr>";}
                          while ( $return	     = mysqli_fetch_object ( $result ) )
                            {
                              $jaar                   = $return->jaar;
                              $maand                  = $return->maand;
                              $nr_payter              = $return->nr_payter;
                              $amount_payter          = $return->amount_payter;

                              $nr_total               = $return->nr_total;
                              $amount_total           = $return->amount_total;
                              $nr_print               = $return->nr_print;
                              $amount_print           = $return->amount_print;
                              $nr_QR                  = $return->nr_QR;
                              $amount_QR              = $return->amount_QR;

                              $maand_array[ $maand ]  = $amount_payter;
                              ?><tr>
                                  <td align="right"><?php echo $jaar; ?></td>
                                  <td align="right"><?php echo $maand; ?></td>

                                  <td align="right"><?php echo $nr_print; ?></td>
                                  <td align="right">&euro; <?php echo number_format( $amount_print,2 ,",","." ); ?></td>
                                  <td align="right"><?php echo $nr_QR; ?></td>
                                  <td align="right">&euro; <?php echo number_format( $amount_QR,2 ,",","." ); ?></td>
                                  <td align="right"<?php echo ($nr_total!=$nr_payter)?' style="color:red"':'';?>><?php echo $nr_total; ?></td>
                                  <td align="right"<?php echo ($amount_total!=$amount_payter)?' style="color:red"':'';?>>&euro; <?php echo number_format( $amount_total  ,2 ,",","." ); ?></td>

                                  <td align="right"><strong><?php echo $nr_payter; ?></strong></td>
                                  <td align="right"><strong>&euro; <?php echo number_format( $amount_payter  ,2 ,",","." ); ?></strong></td>
                                </tr><?php
                            }
                       ?>
                  </table>

                  <h3>Verdeling</h3>
                  <br>
                  <?php                   
                    $deel_voor_bar               = $percentage_bar * $amount_payter;
                    $opbrengst_bar_minus_verhuur = $deel_voor_bar + $totaal_verhuur;
                  ?>
                  <table class="table table-responsive-sm table-bordered table-striped table-sm">
                    
                        <tr>
                           <td  align="right"><strong>totaal opbrengst</strong></td>
                           <td align="right">&euro; <?php echo number_format( $amount_payter  ,2 ,",","." ); ?></td>
                        </tr>
                        
                        <tr>    
                          <td align="right"><strong>percentage dat bar ontvangt</strong></td>
                          <td align="right"><?php echo $percentage_bar * 100;?> %</td>
                        </tr>  
                        
                        <tr>
                          <td align="right"><strong>percentage opbrengst bar</strong></td>
                          <td align="right">&euro; <?php echo number_format( $deel_voor_bar ,2 ,",","." ); ?></td>
                        </tr>

                        <tr>
                          <td align="right"><strong>totaal verhuur</strong></td>
                          <td align="right">&euro; <?php echo number_format( $totaal_verhuur,2 ,",","." ); ?></td>
                        </tr>  

                        <tr>
                          <td align="right"><strong>verrekenen met bar (IN BTW)</strong></td>
                          <td align="right"><strong>&euro; <?php echo number_format( $opbrengst_bar_minus_verhuur,2 ,",","." ); ?></strong></td>
                        </tr>  

                        <tr>
                          <td align="right"><strong>verrekenen met bar (EX BTW)</strong></td>
                          <td align="right"><strong>&euro; <?php echo number_format( ($opbrengst_bar_minus_verhuur/1.21),2 ,",","." ); ?></strong></td>
                        </tr>  

                    
                    
                  </table>


                  <?php
                    $period = '90 DAY';
                    # mediacounter vd printer
                    
                    $sql = "SELECT log_created, log_details 
                             FROM log
                            WHERE event_id = '" . $_SESSION['event_id'] . "'
                              AND log_action      = 'counter'
                              AND log_details    != '-1'
                              AND MONTH(log_created) = $month AND YEAR(log_created) = $year
                              ORDER BY log_created ASC";

                    $result = mysqli_query( $cn , $sql );
                    while ( $return = mysqli_fetch_object ( $result ) ) {
                      $value_array[ $return->log_created ] = $return->log_details;
                    }
                  ?>

                  <div style="width:auto; height: 300px;">
                    <canvas id="myChart" ></canvas>
                  </div>

                  <script src="https://cdn.jsdelivr.net/npm/chart.js@^4"></script>
                  <script src="https://cdn.jsdelivr.net/npm/luxon@^2"></script>
                  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon@^1"></script>

                  <script>
                    const ctx = document.getElementById('myChart');

                    new Chart(ctx, {
                      type: 'line',
                      data: {
                        labels: [ 
                          <?php 
                            echo "luxon.DateTime.local($year, $month).toISO(),";
                            echo "luxon.DateTime.local($year, " . ($month+1) . ").toISO()";
                          ?>
                        ],
                        datasets: [
                          {
                            label: 'Aantal prints dat nog gemaakt kan worden met deze rol papier (media counter)',
                            fill: false,
                            borderWidth: 1.1,
                            pointStyle: false,
                            yAxisID: 'y',
                            data: [
                              <?php  
                              foreach (  $value_array as $date => $value ) {
                                $date_iso = date("Y-m-d\TH:i:s"  ,strtotime( $date )). ".000Z";
                                echo "{ x:'$date_iso', y:$value },";
                              }
                              ?>   
                             ],
                          },
                        ]
                      }, 
                      options: {
                        plugins: {
                          title: {
                            text: 'Papier verbruik (media counter)',
                            display: true
                          }
                        },
                        
                        scales: {
                          x: {
                              type: 'time',
                              time: {
                                  unit: 'day',
                                  unitStepSize: 1,
                                  displayFormats: {
                                      'day': ' DD'
                                  }, 
                              },
                              title: {
                              display: true,
                              text: 'Datum'
                              }
                          },
                          y: {
                              type: 'linear',
                              display: true,
                              position: 'left',
                              beginAtZero: true,
                          },
                        }
                      }
                    });
                  </script>
                 
                  <?php 
                    # toon gebruikte rollen vanuit de photobooth_media tabel
                    $sql = "SELECT COUNT(*) as aantal_rollen, SUM(photobooth_media.skip_verdeling) as aantal_skipped
                              FROM photobooth_media
                              WHERE event_id = '" . $_SESSION['event_id'] . "'
                              AND MONTH(date_modified) = $month
                              AND YEAR(date_modified) = $year
                              AND number_of_rolls = '-1'";
                    $result = mysqli_query( $cn , $sql );
                    $return = mysqli_fetch_object ( $result );
                    echo "Aantal gebruikte rollen deze maand = <strong>" . ($return->aantal_rollen - $return->aantal_skipped) . "</strong>";
                    echo (($return->aantal_skipped)?" (aantal verhuisd = $return->aantal_skipped)":'');
                  ?>

                  <?php 
                      if ( $_SESSION['client_id'] == 1  ) {
                        ?><br><br>LET OP: deze knop alleen indrukken als de maand helemaal klaar is (dus begin van de nieuwe maand).<br>
                        Er worden direct factuurregels aangemaakt in het facturatiesysteem en deze zijn lastig te verwijderen!<br>
                        <a href="page_invoice_overview.php?mode=save_invoice&month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn btn-info">Exporteer naar Facturatiesysteem</a><?php 

                      }
                  ?>
                </div>
              </div>
            </div>
          </div>
        </div>          
      </div>
    </main>
  </div>
  <footer class="c-footer">
    <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> © <?php echo date('Y'); ?> MagicShoot.</div>
    <div class="ml-auto">Powered by&nbsp;<a href="https://www.MagicShoot.nl/">MagicShoot</a></div>
  </footer>
</div>  
<?php include("partial_footer.php"); ?>
