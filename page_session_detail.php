  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  //$event_id = 418;
  if ( isset ( $_REQUEST['event_id'])) {
    $event_id = $_REQUEST['event_id'];
  }
  else {
  $event_id = $_SESSION['event_id'];
  }
  
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }
  
  $log_session_id = mysqli_real_escape_string( $cn, $_REQUEST['log_session_id']);
  ?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Sessie detail</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">


            <div class="row">
               <!-- /. col-->
               <div class="col-sm-12 col-md-12 col-lg-12">
                  <!-- /.card-->
                  <div class="card">
                    <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Sessie detail <?php echo $log_session_id; ?></h2>
                    </div>
                    <div class="card-body">
                    <a href="page_invoice_overview.php" class="btn btn-success">Maandafrekening</a>
                  <a href="page_monthly_overview.php" class="btn btn-info">Omzet per maand</a>
                  <a href="page_revenue_per_evening.php" class="btn btn-info">Omzet per werkdag/avond</a>
                  <a href="page_revenue_per_workweek.php" class="btn btn-info">Omzet per werkweek</a>
                  <a href="page_session_overview.php?aantal=40" class="btn btn-warning">Sessie overzicht</a>
                  <a href="page_transaction_overview.php" class="btn btn-warning">Transactie overzicht</a><br><br>
                      
                    </div>  
                  </div>
                </div>
            </div>      



       
          <?php             
          $sql = "SELECT * 
                    FROM log
                    WHERE event_id       = '" . $event_id       . "' AND 
                          log_session_id = '" . $log_session_id . "'
                    -- ORDER BY log_id ASC
                    ORDER BY log_created, log_id_apps ASC";
          //echo $sql;
          $result   = mysqli_query( $cn , $sql );
          if ( !$result ){echo "ERROR bij ophalen sessies";}
          $counter = 0;
     
            ?> 
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card">
                  <div class="card-body">
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                        <thead>
                          <tr>
                          
                            <th>Log ID</th>
                            <th>Log Sessie ID</th>
                            <th>Log ID apps</th>
                            <th>Log created</th>
                            <th>Log level</th>
                            <th>Log source</th>
                            <th>Log action</th>
                            <th>Log ip</th>
                            <th>Log details</th>
                            
                          </tr>
                        </thead>
                        <tbody>
                        <?php
                        while ( $return = $result->fetch_object() )
                          {
                          ?>
                          <tr>
                            <td><?php echo $return->log_id; ?></td>
                            <td><?php echo $return->log_session_id; ?></td>
                            <td><?php echo $return->log_id_apps; ?></td>
                            <td><?php echo $return->log_created; ?></td>
                            <td><?php echo $return->log_level; ?></td>
                            <td><?php echo $return->log_source; ?></td>
                            <td><?php echo $return->log_action; ?></td>
                            <td><?php echo $return->log_ip; ?></td>
                            <td><?php echo $return->log_details; ?></td>
                            
                          </tr>
                        <?php } ?>
                        </tbody>
                      </table>
                   

                   <!-- toon strookje-->
                   <?php 
                    $sql = "SELECT log_details 
                              FROM log
                             WHERE event_id       = '" . $event_id       . "' AND 
                                   log_session_id = '" . $log_session_id . "' AND 
                                   log_action     = 'UPLOAD OK'";
          
                    $result    = mysqli_query( $cn , $sql );
                    $return    = $result->fetch_object();
                    $file_hash = $return->log_details;
                    if ( $file_hash <> '') {
                      $file_url  = "https://api.magicshoot.nl/event/media/photos/" . $event_id . "/" . $file_hash . ".jpg";
                      ?>
                      <a href="https://www.magicshoot.nl/foto/<?php echo $event_id; ?>/<?php echo $file_hash; ?>" target="_blank">Display page link</a><br>
                      Fotostrook:<br>
                    <img src="<?php echo $file_url; ?>" width="300">
                  <?php } ?>
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
