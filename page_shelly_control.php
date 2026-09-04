  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  include ("inc/shelly_functions.php"); 

  $event_id = $_SESSION['event_id'];
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner'|| $_SESSION['client_role'] == 'employee' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }
  $mode = ""; //init
  if ( isset ( $_REQUEST['mode'] )){   $mode = mysqli_real_escape_string( $cn, $_REQUEST['mode']); }
  if ( $mode == "" ) { $mode = "get_shelly_status";}
   

  
  ?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Fotokast aan- en uitzetten</li>
        
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
              <div class="col-sm-6 col-md-6 col-lg-6">
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Fotokast aan- en uitzetten</h2>
                    </div>
                  <div class="card-body">
                    <?php

                     # haal de shelly device_id op die bij deze kast (event) hoort
                      $sql =  "SELECT shelly_device_id,
                                      shelly_switch_only
                                 FROM event
                                WHERE event_id = '" . $_SESSION['event_id'] . "'";
                      $result             = mysqli_query( $cn , $sql );
                      $return	            = mysqli_fetch_object ( $result );
                      $shelly_device_id   = $return->shelly_device_id;
                      $shelly_switch_only = (int) $return->shelly_switch_only;
                      $show_switch_1      = ( $shelly_switch_only != 0 );


                      # haal de shelly auth_key en de url op
                      $sql =  "SELECT auth_key, url
                                 FROM photobooth_shelly_auth";
                      $result             = mysqli_query( $cn , $sql );
                      $return	            = mysqli_fetch_object ( $result );
                      $shelly_auth_key    = $return->auth_key;
                      $shelly_url         = $return->url;

      
                   
                   
                      /*
                        UITLEG: https://shelly-api-docs.shelly.cloud/cloud-control-api/communication-v2
                        */
                      if ( $mode == "get_shelly_status" )
                      {
                      // status Shelly device
                      $shelly_device_status_array = get_one_shelly_status ( $event_id );
                        sleep(1)
                     

                        ?>
                        <h2>Status fotokast</h2>
                        <table  class="table-responsive-sm table-bordered table-striped table-sm">
                          <tr>
                            <td>Online:</td>
                            <td><?php if ( $shelly_device_status_array[$event_id]['online'] > 0 ) { 
                              ?><span class="badge rounded-pill  btn-success">Online</span><?php
                              } else 
                              { 
                                ?><span class="badge rounded-pill btn-danger">Offline</span><?php
                                } ?></td>
                        </tr>
                        <tr>  
                            <td>Ingeschakeld - switch 0:</td>
                            <td><?php if ( $shelly_device_status_array[ $event_id]['switch:0']== 1 ) 
                            { 
                              ?><span class="badge rounded-pill btn-success">Staat aan</span><?php
                              } else 
                              { 
                                ?><span class="badge rounded-pill btn-danger">Staat uit</span><?php
                                } ?></td>
                        </tr>
                        <?php if ( $show_switch_1 ) { ?>
                        <tr>  
                            <td>Ingeschakeld - switch 1 (PC):</td>
                            <td><?php if ( $shelly_device_status_array[ $event_id]['switch:1']== 1 ) 
                            { 
                              ?><span class="badge rounded-pill btn-success">Staat aan</span><?php
                              } else 
                              { 
                                ?><span class="badge rounded-pill btn-danger">Staat uit</span><?php
                                } ?></td>
                        </tr>
                        <?php } ?>
                          
                        </table> 
                        <br>
                        <?php 
                        if ( $shelly_device_status_array[ $event_id]['switch:0'] == 1 ) 
                          { 
                            ?><a href="page_shelly_control.php?mode=turn_off_photobooth" class="btn btn-danger">Zet fotokast uit</a><?php 
                          
                          } else  { 
                            if ( $shelly_device_status_array[ $event_id]['online'] == 1 ) {
                            ?><a href="page_shelly_control.php?mode=turn_on_photobooth" class="btn btn-success">Zet fotokast aan</a><?php
                            } else { 
                              echo "De fotokast is niet online en kan dus niet remote worden aangezet. <br>
                              Vraag een lokale medewerker eventueel de stekker eruit te halen en opnieuw in het stopcontact te doen.<br> 
                              Ververs daarna deze pagina en kijk of de fotokast online is.<br>";
                            }
                          }

                          
                          if ( $show_switch_1 ) {
                            if ( $shelly_device_status_array[ $event_id]['switch:1'] == 1 ) 
                              { 
                                ?><br><br><a href="page_shelly_control.php?mode=turn_off_switch_1" class="btn btn-danger">Zet PC uit</a><?php 
                              
                              } else  { 
                                if ( $shelly_device_status_array[ $event_id]['online'] == 1 ) {
                                ?><br><br><a href="page_shelly_control.php?mode=turn_on_switch_1" class="btn btn-success">Zet PC aan</a><?php
                                } else { 
                                  echo "De shelly is niet online en kan dus niet remote worden aangezet. <br>
                                  Vraag een lokale medewerker eventueel de stekker eruit te halen en opnieuw in het stopcontact te doen.<br> 
                                  Ververs daarna deze pagina en kijk of de fotokast online is.<br>";
                                }
                              }
                          }
                      }   
                         
                       ?><br><br><br>Als je de kast handmatig aan/uit zet, dan wordt de powerplanner 8 uur uitgeschakeld.</p>
                    <?php
                      
                      

                      if ( $mode == "turn_off_photobooth" )
                      {
                      # je schakelt handmatig, zet dan de powerplanner uit, teneinde dat de kast niet weer aan of uit gaat terwijl je dat niet wilt
                      $sql = "UPDATE photobooth_power_planner_used
                                  SET use_power_planner = '0',
                                      deactivated_by = '" . $_SESSION['client_name'] . "',
                                      deactivated_at = NOW()
                                WHERE event_id    = '" . $_SESSION['event_id'] . "'";
                      $result = mysqli_query( $cn , $sql );


                      # switch:0 off
                     $switch_0_result = set_switch ( $_SESSION['event_id'], 0, 'off');
                     if ( $switch_0_result == 1)
                        { 
                          echo "De fotokast is uitgeschakeld. De powerplanner staat nu UIT!";
                        }
                        else {
                          echo " Error: er is iets fout gegaan bij het uitzetten van de fotokast. Error:" . $switch_0_result;
                          ?><a href="page_shelly_control.php?mode=get_shelly_status" class="btn btn-info">Check status fotokast</a><?php
                        } 
                      }

                      if ( $mode == "turn_on_photobooth" )
                      {
                      # je schakelt handmatig, zet dan de powerplanner uit, teneinde dat de kast niet weer aan of uit gaat terwijl je dat niet wilt
                      $sql = "UPDATE photobooth_power_planner_used
                                 SET use_power_planner = '0',
                                     deactivated_by = '" . $_SESSION['client_name'] . "',
                                     deactivated_at = NOW()
                               WHERE event_id    = '" . $_SESSION['event_id'] . "'";
                      $result = mysqli_query( $cn , $sql );


                      $switch_0_result = set_switch ( $_SESSION['event_id'], 0, 'on' );
                      if ( $switch_0_result == 1)
                        { 
                          echo "De fotokast is aan het opstarten, momentje geduld! De powerplanner staat nu UIT!";
                        }
                       else {
                         echo " Error: er is iets fout gegaan bij het aanzetten van de fotokast. Error:" . $switch_0_result;
                         ?><a href="page_shelly_control.php?mode=get_shelly_status" class="btn btn-info">Check status fotokast</a><?php
                       } 
                      }
                    
                    

                    if ( $mode == "turn_off_switch_1" )
                      {
                      if ( ! $show_switch_1 ) {
                        echo "Switch 1 is uitgeschakeld voor deze fotokast.";
                      } else {
                      # je schakelt handmatig, zet dan de powerplanner uit, teneinde dat de kast niet weer aan of uit gaat terwijl je dat niet wilt
                      $sql = "UPDATE photobooth_power_planner_used
                                  SET use_power_planner = '0',
                                      deactivated_by = '" . $_SESSION['client_name'] . "',
                                      deactivated_at = NOW()
                                WHERE event_id    = '" . $_SESSION['event_id'] . "'";
                      $result = mysqli_query( $cn , $sql );


                      # switch:1 off
                      $switch_1_result = set_switch ( $_SESSION['event_id'], 1, 'off' );
                      if ( $switch_1_result == 1)
                        { 
                          echo "De PC is uitgeschakeld. De powerplanner staat nu UIT!";
                        }
                        else {
                          echo " Error: er is iets fout gegaan bij het uitzetten van de fotokast. Error:" . $switch_1_result;
                          ?><a href="page_shelly_control.php?mode=get_shelly_status" class="btn btn-info">Check status fotokast</a><?php
                        } 
                      }
                      }

                      if ( $mode == "turn_on_switch_1" )
                      {
                      if ( ! $show_switch_1 ) {
                        echo "Switch 1 is uitgeschakeld voor deze fotokast.";
                      } else {
                      # je schakelt handmatig, zet dan de powerplanner uit, teneinde dat de kast niet weer aan of uit gaat terwijl je dat niet wilt
                      $sql = "UPDATE photobooth_power_planner_used
                                 SET use_power_planner = '0',
                                     deactivated_by = '" . $_SESSION['client_name'] . "',
                                     deactivated_at = NOW()
                               WHERE event_id    = '" . $_SESSION['event_id'] . "'";
                      $result = mysqli_query( $cn , $sql );


                      # switch:1 on
                      $switch_1_result = set_switch ( $_SESSION['event_id'], 1, 'on' );
                      if ( $switch_1_result == 1)
                        { 
                          echo "De PC is aan het opstarten, momentje geduld! De powerplanner staat nu UIT!";
                        }
                       else {
                         echo " Error: er is iets fout gegaan bij het aanzetten van de PC.  Error:" . $switch_1_result;
                         ?><a href="page_shelly_control.php?mode=get_shelly_status" class="btn btn-info">Check status fotokast</a><?php
                       } 
                      }
                      }
                    ?>
                   
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