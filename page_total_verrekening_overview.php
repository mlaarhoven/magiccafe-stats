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

  $event_array = array();
  $sql = "SELECT event_id 
            FROM photobooth_event_ids ";
       
          
  //echo $sql;
  $result             = mysqli_query( $cn , $sql );
  while ( $return     = mysqli_fetch_object ( $result ) ) {
       $event_id   = $return->event_id;
       array_push ( $event_array, $event_id );
   }



  if ( isset ( $_REQUEST['mode'] )) { $mode     = mysqli_real_escape_string( $cn, $_REQUEST['mode']);}
 
  


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

  

    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
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
                      <h2 class="text-white text-uppercase">Totaal overzicht alle fotokasten per maand - <?php echo $year; ?> en maand: <?php echo $month; ?></h2>
                    </div>
                  <div class="card-body">
               
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
                    <form action="page_total_verrekening_overview.php" action="post">
                        <input type="hidden" name="mode" value="save_year">
                        
                        <select name="year" onchange="this.form.submit()">
                            <?php 
                            for ( $y = 2023; $y < 2060; $y ++ )
                            {
                              ?><option value="<?php echo $y; ?>"
                              <?php if ( $y == $year ) { echo " selected"; } ?>
                              ><?php echo $y; ?></option><?php
                            }
                            ?>
                        </select>
                      </form>

                      Kies een andere maand:
                    <form action="page_total_verrekening_overview.php" action="post">
                        <input type="hidden" name="mode" value="save_month">
                        
                        <select name="month" onchange="this.form.submit()">
                            <?php 
                           for ( $m = 1; $m <= 12; $m ++ )
                            {
                              ?><option value="<?php echo $m; ?>"
                              <?php if ( $m == $month ) { echo " selected"; } ?>
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
              <p>Verhuur verrekenen: kies eerst de fotokast en bekijk dan de aantallen en financieel pagina en klik op Verhuur.</p>
              <p>
                        
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                        
                            <tr>
                            <td  align="right"><strong>event ID</strong></td>
                            
                            <td  align="right"><strong>totaal opbrengst</strong></td>
                            <td align="right"><strong>percentage dat bar ontvangt</strong></td>
                            <td align="right"><strong>percentage opbrengst bar</strong></td>
                            <td align="right"><strong>totaal verhuur ( to do )</strong></td>
                            <td align="right"><strong>verrekenen met bar (IN BTW)</strong></td>
                        </tr>
                    <?php
                   $totaal_opbrengst = 0; // init
                   $totaal_verdelen  = 0; // init
                    foreach ($event_array as $event_id) { 
                        if ( $event_id > 400 ) {

                        # haal gegevens op voor deze klant
                        $sql             = "SELECT * 
                                             FROM photobooth_percentage_pricing 
                                             WHERE event_id = $event_id AND '" . $year . "-" . $month . "-01' BETWEEN start_date AND end_date";
                        //echo $sql . "<br>";
                        $result          = mysqli_query( $cn , $sql );
                        $percentage_bar = 0; // init
                        $rental_price   = 0; // init
                         if ( $result->num_rows > 0 ) {
                          $return	         = mysqli_fetch_object ( $result );
                          $percentage_bar  = $return->percentage_bar;
                          $rental_price    = $return->rental_price;
                         }
                        //echo "percentage_bar = $percentage_bar<br>";
                        //echo "rental_price = $rental_price<br>";

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
                          
                    
                            $deel_voor_bar               = $percentage_bar * $amount_payter;
                            // totaal_verhuur wordt nu nog niet gevuld. 
                            // bij het overzicht per kast wordt dit gevuld 
                            $opbrengst_bar_minus_verhuur = $deel_voor_bar + $totaal_verhuur; 
                            
                            $totaal_verdelen            += $opbrengst_bar_minus_verhuur;
                            $totaal_opbrengst           += $amount_payter;
                            ?>
                           
                                
                                    <tr>
                                      <td><?php echo $event_id; ?></td>
                                      <td align="right">&euro; <?php echo number_format( $amount_payter  ,2 ,",","." ); ?></td>
                                      <td align="right"><?php echo $percentage_bar * 100;?> %</td>
                                      <td align="right">&euro; <?php echo number_format( $deel_voor_bar ,2 ,",","." ); ?></td>
                                      <td align="right">&euro; <?php echo number_format( $totaal_verhuur,2 ,",","." ); ?></td>
                                      <td align="right"><strong>&euro; <?php echo number_format( $opbrengst_bar_minus_verhuur,2 ,",","." ); ?></strong></td>
                                    </tr>  

                                
                                
                            
                        <?php 
                        } // end transacties ophalen Payter 
                        ?>
                        
                        <?php
                        } // end if event_id > 400
                    } // end for each 

                    $vaste_kosten                                   = 300; //aangepast van 200 naar 300 op 11-10-2025
                    $opbrengst_minus_verrekening_bar                = $totaal_opbrengst - $totaal_verdelen;
                    $opbrengst_minus_verrekening_bar_minus_kosten   = $opbrengst_minus_verrekening_bar -  $vaste_kosten;
                    $deel_martijn                                   = $opbrengst_minus_verrekening_bar_minus_kosten * 0.4;
                    $deel_sitan                                     = $opbrengst_minus_verrekening_bar_minus_kosten * 0.6;
                    $reserveren                                     = $totaal_verdelen + $deel_martijn;
                   
                    
                    ?>
                        <tr>
                            <td></td>
                            <td align="right"><strong>&euro; <?php echo number_format( $totaal_opbrengst,2 ,",","." ); ?></strong></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td align="right"><strong>&euro; <?php echo number_format( $totaal_verdelen,2 ,",","." ); ?></strong></td>
                        </tr>  
                        <tr>
                            <td>Vaste kosten (schatting) en restant te verdelen</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td align="right"><strong>&euro; <?php echo "-" . number_format( $vaste_kosten,2 ,",","." ); ?></strong></td>
                            
                        </tr> 
                        <tr>
                            <td>Te verdelen tussen Sitan en Martijn</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td align="right"><strong>&euro; <?php echo number_format( $opbrengst_minus_verrekening_bar_minus_kosten,2 ,",","." ); ?></strong></td>
                        </tr>      
                        
                        <tr>
                            <td>Deel Martijn</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td align="right"><strong>&euro; <?php echo number_format( $deel_martijn,2 ,",","." ); ?></strong></td>
                        </tr>      
                        

                        <tr>
                            <td>Deel Sitan</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td align="right"><strong>&euro; <?php echo number_format( $deel_sitan,2 ,",","." ); ?></strong></td>
                        </tr>  
                        <tr>
                            <td>Reserveren: deel cafes en deel Martijn opgeteld</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td align="right"><strong>&euro; <?php echo number_format( $reserveren,2 ,",","." ); ?></strong></td>
                        </tr>         
                  </table>
                  </div>
                </div>
              </div>
            </div>
          </div>          
        </div>
      </div>
    </main>
    <footer class="c-footer">
      <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> © <?php echo date('Y'); ?> MagicShoot.</div>
      <div class="ml-auto">Powered by&nbsp;<a href="https://www.MagicShoot.nl/">MagicShoot</a></div>
    </footer>
  </div>
</div>  
<?php include("partial_footer.php"); ?>
