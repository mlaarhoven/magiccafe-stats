  <?php 
  error_reporting (E_ALL ^ E_NOTICE); 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  
  $event_id = $_SESSION['event_id'];
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }


  $mode = ""; //init
  $year_aangepast = 0 ; //init
  $set_planning_opgeslagen = "";
  if ( isset($_REQUEST['mode']) ) {
    $mode     = mysqli_real_escape_string( $cn, $_REQUEST['mode']);
  }

  if ( $mode == "save_set_with_date_time" )
    {
    $start_date   = mysqli_real_escape_string( $cn, $_REQUEST['start_date']); 
    $end_date     = mysqli_real_escape_string( $cn, $_REQUEST['end_date']); 
    $start_time   = mysqli_real_escape_string( $cn, $_REQUEST['start_time']); 
    $end_time     = mysqli_real_escape_string( $cn, $_REQUEST['end_time']); 
    $set_id       = mysqli_real_escape_string( $cn, $_REQUEST['set_id']);  
    $base_set_id  = get_base_set_id();
    
    // check: start_dat_time < end_date_time  
    $start_date_time = $start_date . " " . $start_time;
    $end_date_time   = $end_date . " " . $end_time;

    //echo "start date time = " . $start_date_time . " " . "end date time=" . $end_date_time . "<br>";
    if ( strtotime( $start_date_time ) < strtotime ( $end_date_time ) )
      {
        $sql = "REPLACE INTO photobooth_set_planner
                        SET event_id        = '" . $event_id        . "',
                            start_date      = '" . $start_date      . "',
                            end_date        = '" . $end_date        . "',
                            start_time      = '" . $start_time      . "',
                            end_time        = '" . $end_time        . "',
                            start_date_time = '" . $start_date_time . "',
                            end_date_time   = '" . $end_date_time   . "',
                            set_id          = '" . $set_id          . "'";
        $result      = mysqli_query( $cn , $sql );
        //echo $sql;
        if ( $result ) { $set_planning_opgeslagen = 1; } else {
          echo "Error mysql " . $sql ." " . mysqli_error(( $cn));

        }
        $sql2 = "DELETE FROM photobooth_set_planner
                      WHERE event_id   = '" . $event_id  . "' AND
                            set_id = '" . $base_set_id   . "'";
        $result2      = mysqli_query( $cn , $sql2 );
       
        }
    else  
    {
      $set_planning_opgeslagen =  "FOUTMELDING: de eind datum en tijd moet na de start datum en tijd liggen!";
    }  
  }

  if ( $mode == "save_year" )
    {
    $year   = mysqli_real_escape_string( $cn, $_REQUEST['year']);
    $_SESSION['year'] = $year;
    $year_aangepast = 1; 
    }

  if ( $_SESSION['year'] == "" )
    { 
    $year = date("Y");
    $_SESSION['year'] = $year;
    }
  else 
    {
    $year = $_SESSION['year'];
    }  
    if ( $mode == "save_month" )
    {
    $month   = mysqli_real_escape_string( $cn, $_REQUEST['month']);
    $_SESSION['month'] = $month;
    $month_aangepast = 1; 
    }
  

  if ( $_SESSION['month'] == "" )
    { 
    $month = date("m");
    $_SESSION['month'] = $month;
    }
  else 
    {
    $month = $_SESSION['month'];
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

  $dateStart = $year . "-01-01";
  $dateEnd   = $year . "-12-31";

  // create array with the dates and hours that are already in the set_planner table
  $array_planner = array();
  $array_color   = array();

  $sql = "SELECT *
            FROM photobooth_set_planner
           WHERE event_id = '" . $_SESSION['event_id']. "'
          AND YEAR(start_date)= '" . $year . "' AND MONTH(start_date) = '" . $month . "'
          ORDER BY start_date_time ASC";
  
  $result = mysqli_query( $cn , $sql );
  while ( $return	= mysqli_fetch_object ( $result ) )
    {
    $set_id             = $return->set_id;
    $start_date         = $return->start_date;
    $end_date           = $return->end_date;
    $start_time         = $return->start_time;
    $end_time           = $return->end_time;
    $start_date_time    = $start_date . " " . $start_time;
    $end_date_time      = $end_date   . " " . $end_time;

    //echo "start_date_time=" . $start_date_time ." " . "end_date_time=" . $end_date_time ." set_id = " . $set_id . "<br>";

    $dates = returnBetweenDates ( $start_date_time, $end_date_time );
   // echo "<pre>";
  //  print_r ( $dates );   
   // echo "</pre>";
    foreach ($dates as $date) {
      $array_planner[$date] = $set_id;
      }
    $color = get_set_color_from_db ( $set_id );
    $array_color[ $set_id ] = $color;
    }

function isLeapYear($year) 
    {
    if (($year/4)  != floor($year/4))  return false;
    if (($year/100) != floor($year/100)) return true;
    if (($year/400) != floor($year/400)) return false;
    return true;
    }

function returnBetweenDates( $startDate, $endDate )
  {
    $startStamp = strtotime(  $startDate );
    $endStamp   = strtotime(  $endDate );
    if( $endStamp > $startStamp ){
        while( $endStamp >= $startStamp ){
            $dateArr[] = date( 'Y-m-d H:i:s', $startStamp );
            $startStamp = strtotime( '+1 hour', $startStamp );
        }
        return $dateArr;    
    }else{
        return $startDate;
    }
  }

function get_set_name ( $set_id ) 
    {
    global $cn;
    $sql = "SELECT set_name
              FROM photobooth_set
              WHERE event_id = '" . $_SESSION['event_id'] . "'
               AND set_id ='" . $set_id . "'";
    //echo $sql . "<br>";           
    $result      = mysqli_query( $cn , $sql );
    $return	     = mysqli_fetch_object ( $result );
    $set_name    = $return->set_name;
    return $set_name;
  }

 function get_set_color_from_db ( $set_id ) 
    {
    global $cn;
    $set_color = "#f00"; //init
    $sql = "SELECT set_color
              FROM photobooth_set
             WHERE event_id = '" . $_SESSION['event_id'] . "'
               AND set_id   = '" . $set_id . "'";
    //echo $sql . "<br>";           
    $result      = mysqli_query( $cn , $sql );
    if ( $result->num_rows > 0 ) {
     $return	     = mysqli_fetch_object ( $result );
      $set_color   = $return->set_color;
    }
    return $set_color;
  }

  function get_base_set_id () 
    {
    global $cn;
    $sql = "SELECT set_id
              FROM photobooth_set
             WHERE event_id = '" . $_SESSION['event_id'] . "'
               AND is_base_set ='1'";
    //echo $sql . "<br>";  
    $result      = mysqli_query( $cn , $sql );
    $return	     = mysqli_fetch_object ( $result );
    $base_set_id = $return->set_id;
    //echo "base_set_id = " . $base_set_id;
    return $base_set_id;
    }
  ?> <style>
  .row_1 {
    display: block;
    height: 5px;
    border:0;
    border-bottom: 1px solid #ddd;
    margin:0;
    padding:0;
  }

  
  td .row_1 {
    border: 0;
  }
  .table_planner td {
    border:0;
    padding:0;
    width:40px
  }
  .table_planner {
    margin:0;
    padding:0;
    border:0;

  }
</style>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Set planner</li>
        
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
                      <h2 class="text-white text-uppercase">Set planner - <?php echo $year; ?></h2>
                  </div>
                  <div class="card-body">
                   <p>Als je een set plant, dan wordt de fotokast aangezet op de tijden die je ingeeft bij de set. <br>De powerplanner wordt dan tijdelijk genegeerd. <br>Na de eindtijd van de set neemt de powerplanner het aan- en uitzetten weer over.</p>
                    <?php if ( $year_aangepast == 1 ) {
                        ?>
                        <div class="alert alert-success" role="alert">
                          Het getoonde jaar is aangepast naar: <?php echo $year; ?>
                        </div>
                        <?php
                      } ?>

                    <?php
                     if ( $set_planning_opgeslagen == 1 ) {
                        ?>
                        <div class="alert alert-success" role="alert">
                          De set is aangepast.
                        </div>
                        <?php
                      } else { 
                        if ( $set_planning_opgeslagen <> "" )
                          {
                          ?>
                          <div class="alert alert-warning" role="alert">
                          <?php echo $set_planning_opgeslagen; ?>
                          </div>
                          <?php
                          }

                      } ?>                     
                    Kies een ander jaar:
                    <form action="page_set_planner.php" action="post">
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

                      <br>
                             Kies een andere maand:
                    <form action="page_set_planner.php" action="post">
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
                    </div>
                         

                  <div class="card-body">
                  <?php 
                   
                    $base_set_id = get_base_set_id();
                    $base_set_color = get_set_color_from_db ( $base_set_id );

                    ?><table class="table_planner"><?php
                      
                   // for ( $maand =0 ; $maand < 12; $maand ++ )
                   //   { 
                   // $maand = date ( "n" ) -1;
                   $maand = $month -1;
                      if ( $maand < 9 ) {  $dateStart = $year . "-0". ( $maand + 1) . "-01"; }
                      if ( $maand > 8 ) {  $dateStart = $year . "-" . ( $maand + 1) . "-01"; }

                      if ( $maand < 9 ) {  $dateEnd = $year . "-0". ( $maand + 1) . "-" . $daysOfMonth[ $maand ]; }
                      if ( $maand > 8  ) {  $dateEnd = $year . "-". ( $maand + 1) . "-" . $daysOfMonth[ $maand ]; }
                      ?>
                            <tr>
                              <?php 
                                $current_date = $dateStart;
                                while(strtotime($current_date) <= strtotime($dateEnd))
                                  {
                                  ?><td style="font-size:10px; text-align:center; width: 48px; "><?php echo date ("D" , strtotime($current_date)) ."<br>"; echo date ("d M" , strtotime($current_date)); ?></td><?
                                  $current_date= date("Y-m-d",strtotime("+1 day",strtotime($current_date)));
                                  }
                              ?>
                             </tr>
                             <tr>
                              <?php 
                                $border_current_time=0;

                                $current_date = $dateStart;
                                while( strtotime($current_date) <= strtotime($dateEnd) )
                                  {
                                  // loop door de dagen van de maand  
                                  ?>
                                  <td>

                                    <table class="table_planner">

                                      <?php 
                                      for ( $hour=0; $hour < 24; $hour ++ )
                                        {
                                        // loop door de uren van de dag  
                                        if ( $hour < 10 ) 
                                          { 
                                            $h = "0" . $hour; 
                                          } else {
                                            $h = $hour;
                                          }
                                        $current_date_time = $current_date . " " . $h . ":00:00";
                                        // bepaal of we een randje op het vakje laten zien ten teken dat dit NOW is
                                        if ( $current_date_time == date ( "Y-m-d H:00:00") )
                                        {
                                          $border_current_time = 2;
                                        } else {
                                          $border_current_time = 0;
                                        }



                                        // check of dit uur in de array_planner zit en een set_id heeft dat NIET de basisset is
                                        

                                        $set_id_from_planner  = ""; //init

                                        # test op hele uur
                                        $current_date_time = $current_date . " " . $h . ":00:00";
                                        if ( isset ($array_planner[ $current_date_time ])) {
                                           $set_id_from_planner = $array_planner[ $current_date_time ];
                                        } 
                                        # test op halve uur
                                        if ( $set_id_from_planner  == "" ) 
                                          {
                                          $current_date_time = $current_date . " " . $h . ":30:00";
                                          if ( isset ($array_planner[ $current_date_time ])) {
                                            $set_id_from_planner = $array_planner[ $current_date_time ];
                                          }
                                        }
                                     
                                        if ( $set_id_from_planner  == "" ) 
                                          {
                                          // toon basis set
                                          ?>
                                          <tr class="row_1"  style="background-color: <?php echo $base_set_color; ?>; border: <?php echo $border_current_time; ?>px solid #000;" onClick="window.location='page_set_choose.php?date_time=<?php echo $current_date_time; ?>'">
                                            <td ></td>
                                          </tr><?
                                          } 

                                        else 
                                          
                                          {
                                          // toon afwijkende set
                                          ?>
                                          <tr class="row_1" style="background-color: <?php echo $array_color[  $set_id_from_planner ]; ?>; border: <?php echo $border_current_time; ?>px solid #000;"" onClick="window.location='page_set_choose.php?date_time=<?php echo $current_date_time; ?>'">
                                            <td></td>
                                          </tr><?
                                          }

                                        } //end uren 
                                      
                                        ?>
                                      </table>  
                                    
                                  </td>
                                <?php 
                                // zet de current_date naar de volgende dag
                                $current_date= date("Y-m-d",strtotime("+1 day",strtotime($current_date)));

                                } //end dagen vd maand ?>

                          </tr>  
                          <?php 
                        //} //end maanden ?> 
                    </table>
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