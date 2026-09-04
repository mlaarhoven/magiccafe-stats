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

 @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');
*{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}
html,body{
 
}
.wrapper{
  width: 280px;
  text-align: center;
  margin: auto;
  background: #fff;
  border-radius: 10px;
  box-shadow: 5px 5px 30px rgba(0,0,0,0.2);
}
.wrapper .title{
  color: #fff;
  line-height: 65px;
  text-align: center;
  background: #333;
  font-size: 25px;
  font-weight: 500;
  border-radius: 10px 10px 0 0;
}
.wrapper .box{
  padding: 20px 30px;
  background: #fff;
  border-radius: 10px;
}
.wrapper .box label{
  display: flex;
  height: 53px;
  width: 100%;
  align-items: center;
  border: 1px solid lightgrey;
  border-radius: 50px;
  margin: 10px 0;
  padding-left: 20px;
  cursor: default;
  transition: all 0.3s ease;
}
#option-1:checked ~ .option-1,
#option-2:checked ~ .option-2,
#option-3:checked ~ .option-3,
#option-4:checked ~ .option-4{
  background: #333;
  border-color: #333;
}
.wrapper .box label .dot{
  height: 20px;
  width: 20px;
  background: #d9d9d9;
  border-radius: 50%;
  position: relative;
  transition: all 0.3s ease;
}
#option-1:checked ~ .option-1 .dot,
#option-2:checked ~ .option-2 .dot,
#option-3:checked ~ .option-3 .dot,
#option-4:checked ~ .option-4 .dot{
  background: #fff;
}
.box label .dot::before{
  position: absolute;
  content: "";
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) scale(2);
  width: 9px;
  height: 9px;
  border-radius: 50%;
  transition: all 0.3s ease;
}
#option-1:checked ~ .option-1 .dot::before,
#option-2:checked ~ .option-2 .dot::before,
#option-3:checked ~ .option-3 .dot::before,
#option-4:checked ~ .option-4 .dot::before{
  background: #333;
  transform: translate(-50%, -50%) scale(1);
}
.wrapper .box label .text{
  color: #333;
  font-size: 18px;
  font-weight: 400;
  padding-left: 10px;
  transition: color 0.3s ease;
}
#option-1:checked ~ .option-1 .text,
#option-2:checked ~ .option-2 .text,
#option-3:checked ~ .option-3 .text,
#option-4:checked ~ .option-4 .text{
  color: #fff;
}
.wrapper .box input[type="radio"]{
  display: none;
}
table.smalltable tr td {
  font-size: 11px;
}
  </style>


    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Testmode</li>
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
          function save_testmode_log ( $new_status )
          {
            global $cn;  
            $sql = "INSERT INTO photobooth_testmode_log
                            SET datetime                = NOW(),
                                client_id               = '" . $_SESSION['client_id']   . "',
                                changed_status_to       = '" . $new_status              . "',
                               
                                event_id                = '" .  $_SESSION['event_id']   . "'";
  
            $result   = mysqli_query( $cn , $sql );
          }



          # save acties
          if ( isset( $_REQUEST['mode'] ) ) {
            if ( $_REQUEST['mode']  == 'save' ) {

              
              if ( isset ( $_REQUEST['status'] )) { 
                $new_status = $_REQUEST['status'];
                $new_status_text = "Test modus staat nu op: " . $_REQUEST['status'];
                } 
             
             

            
        
          
              $sql = "UPDATE photobooth_event_ids
                        SET test_mode                    = '" . $new_status    . "',
                          
                            test_mode_last_modified_client_id    = '" . $_SESSION['client_id']   . "',
                            test_mode_lastmodified_datetime      = NOW()
                      WHERE event_id                             = '" . $_SESSION['event_id']  . "'";
    
              $result   = mysqli_query( $cn , $sql );
              if ( !$result ) {
                  ?><span class="alert rounded-pill  alert-danger">Fout bij opslaan gegevens!</span><br><br><br><?php
              } else {
                  ?><span class="alert rounded-pill  alert-success">De gegevens zijn succesvol opgeslagen. <?php echo $new_status_text; ?></span><br><br><br><?php
              }

              save_testmode_log ( $new_status );
          }
        }



        # display acties
        $event_id = $_SESSION['event_id'];

        # get current state
         
          $sql = "SELECT *
			                FROM photobooth_event_ids
		                 WHERE event_id = '" . $_SESSION['event_id'] . "' 
                  ";
          $result                             = mysqli_query( $cn , $sql );
          $return	                            = mysqli_fetch_object ( $result );
          $test_mode                   = $return->test_mode;
         
        
        ?> 
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-3 col-md-3 col-lg-3">
               
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Test mode</h2></div>
                  <div class="card-body">
                 
                   
                    <form action ="page_test_mode.php">
                      <input type ="hidden" name="mode" value="save">
         
                             
                                 <div class="wrapper">
                                    <div class="title">Kies een mode</div>
                                    <div class="box">
                                      <input type="radio" name="status" id="option-1" value="0">
                                      <input type="radio" name="status" id="option-2" value="1">
                                      <input type="radio" name="status" id="option-3" value="2">
                                   
                                      <label for="option-1" class="option-1">
                                        <div class="dot"></div>
                                        <div class="text">0. UIT</div>
                                      </label>
                                      <label for="option-2" class="option-2">
                                        <div class="dot"></div>
                                        <div class="text">1.  gratis testronde</div>
                                      </label>
                                      <label for="option-3" class="option-3">
                                        <div class="dot"></div>
                                        <div class="text">2. print 4 stroken</div>
                                      </label>
                                       <br><br>
                             <button type="submit" class="btn btn-large btn-info">Save</button>
                                    </div>
                                  </div>
                           
                             <br><br>
                    </form>
                    
                     <p>Zet hier de testmode op 1 of 2 en klik op Save. Llik daarna op de fotokast op START.</p>
                     <p>Nadat de test mode ronde voltooid is schakelt de testmode automatisch weer naar 0.</p>
                      <p>Klik nogmaals op START op de fotokast om van QR-only weer in Print mode te komen.</p>
                    <script>
                      <?php if ( $test_mode == 0 ) { ?>
                        var option_1 = document.getElementById('option-1');
                        option_1.checked = true;
                        <?php
                      }?>
                      <?php if ( $test_mode == 1 ) { ?>
                        var option_2 = document.getElementById('option-2');
                        option_2.checked = true;
                        <?php
                      }?>
                       <?php if ( $test_mode == 2 ) { ?>
                        var option_3 = document.getElementById('option-3');
                        option_3.checked = true;
                        <?php
                      }?>

                      </script>
                    
                   <strong>Logregels</strong>
                   <table class="table table-responsive-sm table-bordered table-striped table-sm smalltable">
                          <thead>
                               <tr>
                                  <!--<td>Event_id</td>
                                  <td>Fotokast</td>-->
                                  <td><strong>Wanneer</strong></td>
                                  <td><strong>Wie</strong></td>
                                  <td><strong>Wat</strong></td>
                                 
                              </tr>
                        </thead>
                        <tbody>
                   <?php
                  $sql = "SELECT photobooth_testmode_log.datetime,
                                 photobooth_testmode_log.changed_status_to,
                                 event.event_name,
                                 photobooth_stat_client.client_name
                            FROM photobooth_testmode_log 
                            LEFT JOIN event USING(event_id)
                            LEFT JOIN photobooth_stat_client ON photobooth_testmode_log.client_id = photobooth_stat_client.client_id
                          WHERE photobooth_testmode_log.event_id = '" . $_SESSION['event_id'] . "'
                         
                          ORDER BY  photobooth_testmode_log.datetime DESC
                           LIMIT 0,30";
                  
                  //echo $sql;
                  $result = mysqli_query( $cn , $sql );
                    
                  while ( $return	= mysqli_fetch_object ( $result ) )
                     {
                    $datetime                  = $return->datetime;
                    $changed_status_to         = $return->changed_status_to;
                    $event_name                = $return->event_name;
                    $client_name               = $return->client_name;
                    ?>
                    <tr>
                      
                      
                        <td nowrap="nowrap"><?php echo $datetime; ?></td>
                        <td nowrap="nowrap"> <?php echo $client_name; ?></td>
                        <td><?php echo $changed_status_to; ?></td>
                        
                                     
                      
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