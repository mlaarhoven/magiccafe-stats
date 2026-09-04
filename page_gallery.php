  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  include ("inc/photo_thumb_functions.php");
 
  $event_id = $_SESSION['event_id'];
  $show_all = $_REQUEST['show_all'] ?? '';
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
        <li class="breadcrumb-item">Gallery laaste maand</li>
        
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
                      <h2 class="text-white text-uppercase">Gallery laaste en huidige maand</h2>
                    </div>
                    <div class="card-body">
                      <p>Let op: dit zijn kleiner gemaakte thumbnails om de fotos snel te laten zien. Als je de werkelijk kwaliteit wilt zien klik dan op Displaypage.</p>
                     <style>
                       
                       .foto_session {
                        display:  block;
                        float: left;
                        width:  300px;
                        border: 1px solid #bbb;
                        border-radius:  10px;
                        padding:  10px;
                        margin:  5px;
                       }
                     </style>
                   
       
                    <?php
                    $photo_table_events = array ( 83, 115, 519, 520 );
                    if ( in_array ( (int) $event_id, $photo_table_events ) ) {
                      $sql = "SELECT photo_id,
                                     filename_md5,
                                     datetime,
                                     photo_email_address,
                                     customer_id
                                FROM photo
                               WHERE thumb_hide = '0'
                                 AND event_id  = '" . $event_id . "'";

                      if ( $show_all <> '1' ) {
                        $sql = $sql . " AND DATE(datetime) BETWEEN DATE_SUB( CURRENT_DATE( ) , INTERVAL 4 WEEK ) AND CURRENT_DATE( ) ";
                      }

                      $sql = $sql . " ORDER BY datetime DESC LIMIT 1500";
                    }
                    else {
                      $sql = "SELECT `filehash`,
                                      `sessie_soort`, 
                                      `sessie_timestamp`,
                                      `log_session_id` 
                                      , auth,
                                      txnsn, gratis
                                FROM log_sessionOK 
                                WHERE sessie_timestamp >= CURDATE() - INTERVAL 2 MONTH 
                                  AND event_id = $event_id
                                ORDER BY sessie_timestamp DESC";
                    }
                    $result          = mysqli_query( $cn , $sql );
                    //echo $sql;
                    while ( $return = $result->fetch_object() )
                      {

                      $nrqr                 = 0;
                      $nrpage               = 0;
                      $nrdownload           = 0;
                      $qr_only              = false;
                      $verhuur_label        = 'Auth code:';
                      $verhuur_tekst        = '-';
                      $show_session_link    = true;

                      if ( in_array ( (int) $event_id, $photo_table_events ) ) {
                        $file_hash        = $return->filename_md5;
                        $log_created      = $return->datetime;
                        $log_session_id   = 0;
                        $show_session_link = false;
                        $verhuur_label    = 'E-mail:';
                        $verhuur_tekst    = $return->photo_email_address ?: '-';
                        $photo_filename   = "/home/magicshoot/domains/magicshoot.nl/public_html/photos/" . $event_id . "/" . $file_hash . ".jpg";
                        $file_url         = get_photo_thumb_url ( $event_id, $file_hash );
                      }
                      else {
                        $file_hash       = $return->filehash;
                        $log_created     = $return->sessie_timestamp;
                        $log_session_id  = $return->log_session_id;
                        $sessie_soort    = $return->sessie_soort;
                        $auth            = $return->auth;
                        $txnsn           = $return->txnsn;
                        $auth_code       = $auth;
                        $gratis          = $return->gratis;

                        $file_url  = get_photo_thumb_url ( $event_id, $file_hash );

                        // QR STATS - hoe vaak is de qr code gescand en hoe vaak is de display page bekeken
                        $sql2 = "SELECT event_id, 
                                        filename AS filehash, 
                                        SUM(via_qr) AS nrqr,
                                        SUM(via_mail) AS nrmail,
                                        SUM(via_magicshootpage) AS nrpage
                                  FROM stat
                                 WHERE event_id='".$event_id ."' AND filename = '".$file_hash ."'
                                  GROUP BY event_id, filename;";

                        $result2  = mysqli_query( $cn , $sql2 );
                        if ( $result2->num_rows > 0 ) {
                          $return2    = $result2->fetch_object();
                          $nrqr       = $return2->nrqr;
                          $nrpage     = $return2->nrpage;
                          if ( $nrqr == "") { $nrqr = 0;}
                          if ( $nrpage == "") { $nrpage = 0;}
                        }

                        // DOWNLOAD STATS - hoe vaak is er op de download knop gedrukt
                        $sql3 = "SELECT event_id, filename_md5 AS filehash, COUNT(*) as download_aantal
                                   FROM stat_display_button
                                  WHERE 
                                  event_id      = '".$event_id ."' AND filename_md5 = '".$file_hash ."' AND 
                                  button_name   = 'download'
                                  GROUP BY event_id,filename_md5;";

                        $result3  = mysqli_query( $cn , $sql3 );
                        if ( $result3->num_rows > 0 ) {
                          $return3        = $result3->fetch_object();
                          $nrdownload     = $return3->download_aantal;
                          if ( $nrdownload == "") { $nrdownload = 0;}
                        }

                        if ( $sessie_soort == 'qr') { $qr_only = true;}
                        if ( $sessie_soort == 'print') { $qr_only = false;}

                        if ( $gratis == '0') { $verhuur_tekst = $auth_code;}
                        if ( $gratis == '1') { $verhuur_tekst = 'gratis - verhuur';}
                      }

                      // set the values
                      $count_qr_gescand        = $nrqr;
                      $count_foto_gedownload   = $nrdownload;
                      $count_foto_bekeken      = $nrpage;

                      if ( $qr_only == true ) {
                        $url_printer = "/img/icons/stats/multiply.png"; // rood kruis
                      }
                      else {
                        $url_printer = "/img/icons/stats/check.png";    // groen vinkje
                      }
                     ?>
                    <style>
                      .blue-circle {
                          height: 25px;
                          width: 25px;
                          background-color: #009FE3;
                          border-radius: 50%;
                          display: inline-block;
                          color: #fff;
                          font-weight: bold;
                          font-size: 16px;
                          
                      }
                      .center {
                        text-align:center;
                      }
                      .small {
                        font-size: 12px;
                      }
                      .table-stats {
                        border: 0px solid #bbb;
                        background-color: #eee;
                        border-radius: 10px;
                        width: 280px;
                        border:5px solid transparent; /* 5px is the "padding" */
                        margin-top: 10px;
                      }

                      .table-timestamp {
                        border: 0px solid #bbb;
                        background-color: #eee;
                        border-radius: 10px;
                        width: 280px;
                        
                        border:5px solid transparent; /* 5px is the "padding" */
                        margin-top: 10px;
                      }
                      
                      .blue-button {
                          margin-top: 10px;
                          height: 25px;
                          width: 120px;
                          background-color:rgb(67, 188, 240);
                          border-radius: 12px;
                          display: block;
                          color: #fff;
                          font-weight: bold;
                          font-size: 14px;
                          text-align: center;
                          text-decoration: none;
                      }

                      .orange-button {
                          margin-left: 10px;
                          margin-top: 10px;
                          height: 25px;
                          width: 120px;
                          background-color:rgb(250, 125, 23);
                          border-radius: 12px;
                          display: block;
                          color: #fff;
                          font-weight: bold;
                          font-size: 14px;
                          text-align: center;
                          float: right;
                          text-decoration: none;
                      }

                      a:hover {
                        text-decoration: none;
                        color: #000;
                      }
                    </style>
                      <div class="foto_session">
                        <img src="<?php echo $file_url; ?>" width="280" ><br>
                        <table  class="table-timestamp">
                            <tr>
                              <td>Timestamp:</td>
                              <td><strong><?php echo $log_created; ?></strong></td>
                            </tr>
                            <tr>
                              <td><?php echo $verhuur_label; ?></td>
                              <td><strong><?php echo $verhuur_tekst; ?></strong></td>
                           </tr>
                           
                         <tr>
                            <td><a class="blue-button" href="https://www.magicshoot.nl/foto/<?php echo $event_id; ?>/<?php echo $file_hash; ?>">Displaypage</a></td>

                            <td>
                              <?php if ( $show_session_link ) { ?>
                                <a  class="orange-button" href="https://stats.magicshoot.nl/page_session_detail.php?log_session_id=<?php echo $log_session_id; ?>">Sessie</a>
                              <?php } ?>
                            </td>
                         </tr>
                       </table>
                      
                       <table class="table-stats">
                          <tr>
                             
                              <td class="center"></td>
                              <td class="center small">QRonly?</td>
                              <td></td>
                              <td class="center small">QRscans</td>
                              <td></td>
                              <td class="center small">Views</td>
                              <td></td>
                              <td class="center small">Downloads</td>
                               
                          </tr>
                          <tr>
                              
                              <td class="center"><img src="/img/icons/stats/check.png" height="20" width="20"></td>
                              <td class="center"><img src="/img/icons/stats/qr-code.png" height="20" width="20"></td>
                              <td class="center"><img src="/img/icons/stats/right-arrow.png" height="20" width="20"></td>
                              <td class="center"><img src="/img/icons/stats/qr-code.png" height="20" width="20"></td>
                              <td class="center"><img src="/img/icons/stats/right-arrow.png" height="20" width="20"></td>
                              <td class="center"><img src="/img/icons/stats/view.png" height="20" width="20"></td>
                              <td class="center"><img src="/img/icons/stats/right-arrow.png" height="20" width="20"></td>
                               <td class="center"><img src="/img/icons/stats/downloading.png" height="20" width="20"></td>
                          </tr>
                          <tr>
                            
                              <td class="center"><img src="<?php echo $url_printer; ?>" height="20" width="20"></td>
                              <td class="center"><img src="/img/icons/stats/printer.png" height="20" width="20"></td>
                              <td></td>
                              <td class="center"><div class="blue-circle"><?php echo $count_qr_gescand; ?></div></td>
                              <td></td>
                              <td class="center"><div class="blue-circle"><?php echo $count_foto_bekeken; ?></div></td>
                              <td></td>
                              <td class="center"><div class="blue-circle"><?php echo $count_foto_gedownload; ?></div></td>
                              
                          </tr>

                        </table>

                      </div>  
                <?php } ?>

                  
               
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
