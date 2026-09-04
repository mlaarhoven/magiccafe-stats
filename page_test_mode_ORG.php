  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");

  $event_id = $_SESSION['event_id'];

  # check role
  if ( !$_SESSION['client_role'] == 'admin' ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }

  ?>
  <style>

    /* The switch - the box around the slider */
.switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}

/* Hide default HTML checkbox */
.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

/* The slider */
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: .4s;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: .4s;
  transition: .4s;
}

input:checked + .slider {
  background-color: #2196F3;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
}

/* Rounded sliders */
.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}
  </style>


    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item">Test mode</li>
        <li class="breadcrumb-item"><?php echo get_event_name ( $_SESSION['event_id']); ?></li>
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">


       
          <?php 
          echo '<pre>';
           // print_r ( $_REQUEST);
           echo '</pre>';
          function save_testmode_log ( $new_status, $new_price_print, $new_price_qr )
          {
            global $cn;  
            $sql = "INSERT INTO photobooth_testmode_log
                            SET datetime                = NOW(),
                                client_id               = '" . $_SESSION['client_id']   . "',
                                changed_status_to       = '" . $new_status              . "',
                                changed_price_print_to  = '" . $new_price_print         . "',
                                changed_price_qr_to     = '" . $new_price_qr            . "',
                                event_id                = '" .  $_SESSION['event_id']   . "'";
  
            $result   = mysqli_query( $cn , $sql );
          }



          # save acties
          if ( isset( $_REQUEST['mode'] ) ) {
            if ( $_REQUEST['mode']  == 'save' ) {

              
              if ( isset ( $_REQUEST['status'] )) { 
                $new_status = 1; 
                $new_status_text = "Test modus staat nu AAN!";
                } 
              else { 
                $new_status = 0;
                $new_status_text = "Test modus staat nu uit."; 
              }
             

              $new_price_print        = mysqli_real_escape_string ( $cn, $_REQUEST['new_price_print'] );
              $new_price_qr           = mysqli_real_escape_string ( $cn, $_REQUEST['new_price_qr'] );
        
          
              $sql = "UPDATE photobooth_event_ids
                        SET test_mode_status                     = '" . $new_status    . "',
                            test_mode_price_print                = '" . $new_price_print         . "',
                            test_mode_price_qr                   = '" . $new_price_qr            . "',
                            test_mode_last_modified_client_id    = '" . $_SESSION['client_id']   . "',
                            test_mode_lastmodified_datetime      = NOW()
                      WHERE event_id                             = '" . $_SESSION['event_id']  . "'";
    
              $result   = mysqli_query( $cn , $sql );
              if ( !$result ) {
                  ?><span class="alert rounded-pill  alert-danger">Fout bij opslaan gegevens!</span><br><br><br><?php
              } else {
                  ?><span class="alert rounded-pill  alert-success">De gegevens zijn succesvol opgeslagen. <?php echo $new_status_text; ?></span><br><br><br><?php
              }

              save_testmode_log ( $new_status, $new_price_print, $new_price_qr );
          }
        }



        # display acties
        $event_id = $_SESSION['event_id'];

        # get current state
         
          $sql = "SELECT *
			                FROM photobooth_event_ids
		                 WHERE event_id = '" . $_SESSION['event_id'] . "'";
          $result                             = mysqli_query( $cn , $sql );
          $return	                            = mysqli_fetch_object ( $result );
          $test_mode_status                   = $return->test_mode_status;
          $test_mode_price_print              = $return->test_mode_price_print;
          $test_mode_price_qr                 = $return->test_mode_price_qr;
        
        ?> 
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-6 col-md-6 col-lg-6">
               
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Test modus</h2></div>
                  <div class="card-body">
                 
                   
                    <form action ="page_test_mode.php">
                      <input type ="hidden" name="mode" value="save">
                        <table class="table table-responsive-sm table-bordered table-striped table-sm">
                          <tbody>
                              <tr><td>Test modus status:</td><td>
                                <!-- Rounded switch -->
                              <label class="switch">
                                <input type="checkbox" name="status" id="status" value="1">
                                <span class="slider round"></span>
                              </label>
                              </td></tr>
                              <tr><td>Test modus prijs PRINT:</td><td>&euro; <input  type="text" name="new_price_print" value="<?php echo $test_mode_price_print; ?>"></td></tr>
                              <tr><td>Test modus prijs QR ONLY:</td><td>&euro; <input  type="text" name="new_price_qr" value="<?php echo $test_mode_price_qr; ?>"></td></tr>




                              <tr><td colspan="2"><button type="submit" class="btn btn-info">Save</button></td></tr>
                           </tbody>
                        </table>
                    </form>
                     
                    <script>
                      <?php if ( $test_mode_status == 1 ) { ?>
                        var cb1 = document.getElementById('status');
                        cb1.checked = true;
                        <?php
                      }?>
                      <?php if ( $test_mode_status == 0 ) { ?>
                        var cb1 = document.getElementById('status');
                        cb1.checked = false;
                        <?php
                      }?>

                      </script>
                    
                   <strong>Logregels</strong>
                   <table class="table table-responsive-sm table-bordered table-striped table-sm">
                          <thead>
                               <tr>
                                  <!--<td>Event_id</td>
                                  <td>Fotokast</td>-->
                                  <td><strong>Date time</strong></td>
                                  <td><strong>Door wie</strong></td>
                                  <td><strong>Status aangepast naar</strong></td>
                                  <td><strong>Prijs print aangepast naar</strong></td>
                                  <td><strong>Prijs qr aangepast naar</strong></td>
                              </tr>
                        </thead>
                        <tbody>
                   <?php
                  $sql = "SELECT photobooth_testmode_log.datetime,
                                 photobooth_testmode_log.changed_status_to,
                                 photobooth_testmode_log.changed_price_print_to,
                                 photobooth_testmode_log.changed_price_qr_to,
                                 event.event_name,
                                 photobooth_stat_client.client_name
                            FROM photobooth_testmode_log 
                            LEFT JOIN event USING(event_id)
                            LEFT JOIN photobooth_stat_client ON photobooth_testmode_log.client_id = photobooth_stat_client.client_id
                          WHERE photobooth_testmode_log.event_id = '" . $_SESSION['event_id'] . "'
                         
                          ORDER BY  photobooth_testmode_log.datetime DESC
                           LIMIT 0,20";
                  
                  //echo $sql;
                  $result = mysqli_query( $cn , $sql );
                    
                  while ( $return	= mysqli_fetch_object ( $result ) )
                     {
                    $datetime                  = $return->datetime;
                    $changed_status_to         = $return->changed_status_to;
                    $changed_price_print_to     = $return->changed_price_print_to;
                    $changed_price_qr_to        = $return->changed_price_qr_to;
                    $event_name                = $return->event_name;
                    $client_name               = $return->client_name;
                    ?>
                    <tr>
                        <!--<td><?php echo $event_id; ?></td>
                        <td><?php echo $event_name; ?></td>-->
                        <td><?php echo $datetime; ?></td>
                        <td><?php echo $client_name; ?></td>
                        <td><?php echo $changed_status_to; ?></td>
                        <td><?php echo $changed_price_print_to; ?></td>
                        <td><?php echo $changed_price_qr_to; ?></td>
                                     
                      
                    </tr>
                    <?php
                    }  
                    ?>
                  </tbody>
                  </table>
                   
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