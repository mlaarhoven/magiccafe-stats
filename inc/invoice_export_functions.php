<?php
# Gedeelde export van de maandafrekening naar het facturatiesysteem.
# Gebruikt door page_invoice_overview.php (per kast) en page_invoice_all.php (alle kasten).
# Verwacht $cn (magicshoot_db) en $cn_info (facturatiesysteem, ../inc/connection_invoicesystem.inc.php).
#
# LET OP: de charge-regels worden altijd ge-INSERT, dus een tweede export van dezelfde maand
# levert dubbele factuurregels op. Check daarom altijd eerst invoice_export_status().

# kasten die nooit een creditfactuur krijgen: test event + flora foto/video + 115 + oude kasten 450/451
function invoice_excluded_event_ids() {
  return array( 35, 83, 98, 115, 450, 451 );
}

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

# true als er voor deze kast + maand al een order is aangemaakt (= al geexporteerd)
function invoice_export_status( $event_id, $month, $year ) {
  global $cn;
  $sql    = "SELECT order_id FROM photobooth_orders
              WHERE event_id = '" . (int)$event_id . "'
                AND order_maand = '" . (int)$month . "'
                AND order_jaar = '" . (int)$year . "'";
  $result = mysqli_query( $cn , $sql );
  return ( $result && mysqli_num_rows( $result ) > 0 );
}

# exporteer een maand van een kast; geeft de statusregels terug
function invoice_export_month( $event_id, $month, $year ) {
  global $cn, $cn_info;
  $event_id = (int)$event_id;
  $month    = (int)$month;
  $year     = (int)$year;
  $regels   = array();

  # haal de afgesproken rental price en percentage op
  $sql = "SELECT *
            FROM photobooth_percentage_pricing
           WHERE event_id = $event_id AND '" . $year . "-" . $month . "-01' BETWEEN start_date AND end_date";

  $result          = mysqli_query( $cn , $sql );
  $return	         = mysqli_fetch_object ( $result );
  if ( !$return ) {
    $regels[] = "Geen prijsafspraak gevonden voor event $event_id in $month-$year, niets geexporteerd.";
    return $regels;
  }
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
    $regels[] = "Update order result = " . $result . " Order_id = $order_id";
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
    $result   = mysqli_query( $cn , $sql );
    $order_id =  mysqli_insert_id($cn);
    $regels[] = "Opslaan order result = " . $result . " Order_id = $order_id";
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
    $regels[] = "Update order IN FACTUURSYSTEEM result = " . $result_info . " Order_id = $order_id_info";
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
    $regels[] = "Opslaan order IN FACTUURSYSTEEM result = " . $result_info . " Order_id = $order_id_info";
  }

  # --- insert charge regels in photobooth_charge, with the order_id --- #

  # zorg dat de log_session tabel goed gevuld is, met de laatste logs
  mysqli_query( $cn, 'CALL FillSequence()');

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
    $charge_year      = $return->jaar;
    $charge_month     = $return->maand;
    $set_sequence     = $return->set_sequence;
    $set_id           = $return->set_id;
    $set_name         = $return->set_name;
    $van              = $return->van;
    $sessie_soort     = $return->sessie_soort;
    $prijs            = $return->prijs;
    $bedrag           = $return->totaal_log;
    $aantal           = $return->aantal;

    # haal met de set_sequence op of deze regel is aangevinkt als verhuur
    $rental_check     = get_checked( $set_sequence, $event_id );

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
    $regels[] = "Opslaan charge regel result = " . $result2;

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
    $regels[] = "Opslaan charge regel in FACTUURSYSTEEM result = " . $result2_info;
    }

  return $regels;
}
