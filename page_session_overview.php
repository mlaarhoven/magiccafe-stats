  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
 
  $event_id = $_SESSION['event_id'];
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }
 
  ?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Sessie overzicht</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>
  <div class="c-body">
      <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">


          <?php             
          $aantal = mysqli_real_escape_string( $cn, $_REQUEST['aantal']);
          
          $sql = "SELECT * 
                    FROM log_session
                   WHERE event_id = '" . $event_id . "'
                ORDER BY log_session_id DESC
                 LIMIT 0, $aantal";
          $result   = mysqli_query( $cn , $sql );
          if ( !$result ){echo "ERROR bij ophalen sessies";}
          $counter = 0;
     
            ?> 
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card">
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Sessie overzicht</h2>
                    </div>
                  <div class="card-body">
                  <a href="page_invoice_overview.php" class="btn btn-success">Maandafrekening</a>
                  <a href="page_monthly_overview.php" class="btn btn-info">Omzet per maand</a>
                  <a href="page_revenue_per_evening.php" class="btn btn-info">Omzet per werkdag/avond</a>
                  <a href="page_revenue_per_workweek.php" class="btn btn-info">Omzet per werkweek</a>
                  <a href="page_session_overview.php?aantal=40" class="btn btn-warning">Sessie overzicht</a>
                  <a href="page_transaction_overview.php" class="btn btn-warning">Transactie overzicht</a><br><br>
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                        <thead>
                          <tr>
                          
                            <th>Sessie ID</th>
                            <th>Sessie start</th>
                            <th>Print of QR-only</th>
                            <th>Betaling start</th>
                            <th>Payter timestamp</th>
                            <th>Betaling status</th>
                            <th>Button foto eerste</th>
                            <th>Aantal keer opnieuw</th>
                            <th>Button foto laatste</th>
                           <!-- <th>Button print</th> -->
                            <th>Printen start</th>
                            <th>Printen klaar</th>
                            <th>Upload - filehash</th>
                            <th>QR gescand</th>
                            <th>Foto gedownload</th>
                            <th>Sessie einde</th>


                            
                          </tr>
                        </thead>
                        <tbody>
                        <?php
                        while ( $return = $result->fetch_object() )
                          {
                          $log_session_id        = $return->log_session_id;
                          $sessie_start          = $return->sessie_start;
                          $sessie_einde          = $return->sessie_einde;
                          $soort_sessie          = $return->sessie_soort;
                          
                          $soort_sessie          = $return->sessie_soort;
                          
                          $start_timestamp       = $return->start_timestamp;
                          $payter_timestamp      = $return->payter_timestamp;
                          $betaling_start        = $return->txnTimestampLocal;
                          $betaling_response     = $return->disposition ; 
                          $auth                  = $return->auth ; 
                          $upload_result         = $return->upload_start;
                          $button_foto_start     = $return->foto_start;
                          $button_foto_eind      = $return->foto_einde;
                          $aantal_keer_opnieuw   = $return->foto_aantal; 
                          $print_start           = $return->print_start;
                          $print_klaar           = $return->print_einde;
                          $upload_ok             = $return->upload_einde;
                          $filehash              = $return->filehash;
                          
                           // QR gescand?
                          $qr_scan_result = "";
                          $sql4           = "SELECT COUNT(stat_id) as scan_aantal
                                               FROM stat
                                              WHERE event_id      = '" . $event_id  . "' 
                                                AND filename      = '" . $filehash  . "' 
                                                AND via_qr        = '1'";
                                    
                          $result4        = mysqli_query( $cn , $sql4 );
                          if ( $result4 ) {
                            $return4        = $result4->fetch_object();
                            if (is_object($return4)) {
                              $qr_scan_result = $return4->scan_aantal;
                            }  
                          }  

                           // foto gedownload?
                          $download_aantal = "";
                          if ( $filehash <> '') {
                            $sql8           = "SELECT COUNT(*) as download_aantal
                                                 FROM stat_display_button
                                                WHERE event_id      = '" . $event_id . "' 
                                                  AND filename_md5  = '" . $filehash . "' 
                                                  AND button_name   = 'download'";
                            //echo $sql8; 
                            $result8        = mysqli_query( $cn , $sql8 );
                            if ( $result8 ) {
                              $return8        = $result8->fetch_object();
                              if (is_object($return8)) {
                                $download_aantal = $return8->download_aantal;
                              }  
                            }
                          } else 
                          { 
                            $download_aantal = 0; 
                          }
                            
                          ?>
                          <tr>
                            <td><a href="page_session_detail.php?log_session_id=<?php echo $log_session_id; ?>"><?php echo $log_session_id; ?></a></td>
                            <td><?php echo $sessie_start; ?></td>
                            <td><?php echo $soort_sessie; ?></td>
                            <td><?php echo $betaling_start; ?></td>
                            <td><?php echo $payter_timestamp; ?></td>
                            <td><?php echo $betaling_response; ?> <br> <strong><?php echo $auth;?></strong></td>
                            <td><?php echo $button_foto_start; ?></td>
                            <td valign="center"><?php echo $aantal_keer_opnieuw; ?></td>
                            <td><?php echo $button_foto_eind; ?></td>
                            <td><?php echo $print_start; ?></td>
                            <td><?php echo $print_klaar; ?></td>
                            <td><?php echo $upload_ok; ?><br><strong><? echo $filehash; ?></strong></td>
                            <td><?php echo $qr_scan_result; ?></td>
                            <td><?php echo $download_aantal; ?></td>
                            <td><?php echo $sessie_einde; ?></td>
                          </tr>
                    <?php } ?>
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