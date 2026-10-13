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
                    $photo_table_events = array ( 83, 115, 519, 520, 529 );   # 529 = Lufthansa AI era-swap (free, no sessions)

                    # Geeft de reden terug waarom een print-sessie NIET goed geprint is, of '' als het goed ging
                    # (of als er nog geen loggegevens zijn). Sessies met sessie_soort 'qr' printen bewust niet.
                    # Velden komen uit log_sessionOK: printen = print_start gezet, EchtPrintenKlaar = print_einde
                    # gezet, NrPrinted = print_counter - print_counter_voor. Bij een printerfout staat de melding
                    # van de printer in print_status (bv. 'Ribbon End', 'ERROR').
                    function print_probleem ( $rij ) {
                      if ( $rij->log_session_id === null || $rij->sessie_soort !== 'print' ) { return ''; }
                      if ( $rij->print_start === null )                                       { return 'print niet gestart'; }
                      if ( $rij->print_einde === null )                                       { return 'print niet afgerond'; }
                      if ( $rij->NrPrinted === null || (int) $rij->NrPrinted <= 0 ) {
                        return 'printer: ' . ( $rij->print_status ?: 'telde geen print' );
                      }
                      return '';
                    }
                    # Bron is de tabel photo (gevuld bij de upload, dus altijd actueel), niet de logfiles:
                    # die komen soms dagen later binnen. De sessie-info (auth, gratis, sessie-link) komt via
                    # een LEFT JOIN op log_sessionOK zodra de logfile er is; tot die tijd is die NULL.
                    # thumb_hide niet in de WHERE: ook een verborgen foto telt als "geupload" bij de
                    # controle op betaalde sessies zonder foto hieronder.
                    $sql = "SELECT p.filename_md5,
                                   p.datetime,
                                   p.photo_email_address,
                                   p.thumb_hide,
                                   s.log_session_id,
                                   s.sessie_soort,
                                   s.auth,
                                   s.gratis,
                                   s.print_start,
                                   s.print_einde,
                                   s.print_status,
                                   s.NrPrinted
                              FROM photo p
                         LEFT JOIN log_sessionOK s ON s.event_id = p.event_id AND s.filehash = p.filename_md5
                             WHERE p.event_id   = " . (int) $event_id;

                    if ( $show_all <> '1' ) {
                      $sql = $sql . " AND p.datetime >= CURDATE() - INTERVAL 2 MONTH";
                    }

                    $sql = $sql . " ORDER BY p.datetime DESC LIMIT 1500";
                    $result          = mysqli_query( $cn , $sql );

                    $rijen                = array();
                    $sessies_met_foto     = array();
                    $oudste_foto          = date('Y-m-d H:i:s');
                    $nr_niet_geprint      = 0;
                    while ( $return = $result->fetch_object() ) {
                      if ( $return->log_session_id !== null ) { $sessies_met_foto[ $return->log_session_id ] = true; }
                      $oudste_foto = $return->datetime;
                      $return->print_probleem = print_probleem ( $return );
                      if ( $return->print_probleem <> '' ) { $nr_niet_geprint++; }
                      if ( $return->thumb_hide == '0' ) {
                        $return->geen_foto    = false;
                        $return->upload_start = null;
                        $rijen[] = $return;
                      }
                    }

                    # BETAALDE SESSIES ZONDER FOTO - wel betaald (log_sessionOK), maar er is nooit een foto
                    # in photo beland: upload mislukt of niet gestart. Bewust in PHP vergeleken en niet met een
                    # NOT EXISTS op photo: filename_md5 heeft geen index, dat kostte ~10 s per kast.
                    # Een foto komt altijd na de start van zijn sessie binnen, dus een sessie binnen de periode
                    # heeft zijn foto (als die er is) ook binnen de periode.
                    if ( $show_all <> '1' ) {
                      $sessie_vanaf = "CURDATE() - INTERVAL 2 MONTH";
                    }
                    else {
                      $sessie_vanaf = "'" . mysqli_real_escape_string ( $cn, $oudste_foto ) . "'";
                    }
                    $sql = "SELECT log_session_id,
                                   filehash,
                                   sessie_timestamp,
                                   sessie_soort,
                                   auth,
                                   gratis,
                                   upload_start,
                                   print_start,
                                   print_einde,
                                   print_status,
                                   NrPrinted
                              FROM log_sessionOK
                             WHERE event_id = " . (int) $event_id . "
                               AND sessie_timestamp >= " . $sessie_vanaf;
                    $result = mysqli_query( $cn , $sql );
                    $nr_geen_foto = 0;
                    while ( $return = $result->fetch_object() ) {
                      if ( isset ( $sessies_met_foto[ $return->log_session_id ] ) ) { continue; }
                      $nr_geen_foto++;
                      $return->print_probleem = print_probleem ( $return );
                      if ( $return->print_probleem <> '' ) { $nr_niet_geprint++; }
                      $rijen[] = (object) array (
                        'print_probleem'      => $return->print_probleem,
                        'filename_md5'        => $return->filehash,
                        'datetime'            => $return->sessie_timestamp,
                        'photo_email_address' => '',
                        'log_session_id'      => $return->log_session_id,
                        'sessie_soort'        => $return->sessie_soort,
                        'auth'                => $return->auth,
                        'gratis'              => $return->gratis,
                        'geen_foto'           => true,
                        'upload_start'        => $return->upload_start,
                      );
                    }

                    usort ( $rijen, function ( $a, $b ) { return strcmp ( $b->datetime, $a->datetime ); } );
                    ?>
                    <p>
                      <?php if ( $nr_niet_geprint > 0 ) { ?>
                        <span style="background:#e55353;color:#fff;padding:4px 10px;border-radius:4px;font-weight:600"><?php echo $nr_niet_geprint; ?> sessie(s) niet (goed) geprint</span>
                      <?php } else { ?>
                        <span style="background:#2eb85c;color:#fff;padding:4px 10px;border-radius:4px;font-weight:600">Alle print-sessies geprint</span>
                      <?php } ?>
                      &nbsp;
                      <?php if ( $nr_geen_foto > 0 ) { ?>
                        <span style="background:#e55353;color:#fff;padding:4px 10px;border-radius:4px;font-weight:600"><?php echo $nr_geen_foto; ?> betaalde sessie(s) zonder foto</span>
                        in deze periode: rood gemarkeerd hieronder.
                      <?php } else { ?>
                        <span style="background:#2eb85c;color:#fff;padding:4px 10px;border-radius:4px;font-weight:600">Geen betaalde sessies zonder foto</span>
                        in deze periode.
                      <?php } ?>
                      <span class="text-muted small">Sessies waarvan de logfile nog niet binnen is, worden pas meegeteld als die er is.</span>
                    </p>
                    <!-- CONTACTSHEET (2026-10-13): kies de eerste en de laatste foto van een reeks → contactsheet.php maakt
                         er één grote JPG van, oudste eerst, 6 per rij (Lufthansa: 6 looks per tijdperk = 1 tijdperk per rij) -->
                    <p><button type="button" class="btn btn-primary" id="cs_start">Contactsheet maken</button></p>
                    <style>
                      #cs_bar { display:none; position:fixed; left:0; right:0; bottom:0; z-index:1050; background:#2f353a; color:#fff;
                                padding:12px 20px; box-shadow:0 -4px 16px rgba(0,0,0,.3); align-items:center; gap:18px; flex-wrap:wrap; }
                      #cs_bar label { margin:0; }
                      body.cs_mode .foto_session[data-hash] { cursor:pointer; }
                      body.cs_mode .foto_session[data-hash]:hover { outline:3px dashed #43bcf0; }
                      .foto_session.cs_end   { outline:5px solid #fa7d17 !important; }
                      .foto_session.cs_range { outline:3px solid #43bcf0; }
                      .cs_tag { background:#fa7d17; color:#fff; font-weight:bold; border-radius:6px; padding:2px 8px; margin-bottom:6px; display:inline-block; }
                    </style>
                    <div id="cs_bar">
                      <strong>Contactsheet</strong>
                      <span id="cs_info">Klik op de eerste foto van de reeks.</span>
                      <label>Per rij <input type="number" id="cs_cols" value="6" min="1" max="12" style="width:60px"></label>
                      <label><input type="radio" name="cs_crop" value="square" <?php echo ( (int) $event_id == 529 ) ? 'checked' : ''; ?>> alleen de foto (zonder kader)</label>
                      <label><input type="radio" name="cs_crop" value="full" <?php echo ( (int) $event_id == 529 ) ? '' : 'checked'; ?>> hele afbeelding</label>
                      <button type="button" class="btn btn-warning" id="cs_make" disabled>Maak contactsheet</button>
                      <button type="button" class="btn btn-secondary" id="cs_cancel">Annuleren</button>
                    </div>
                    <script>
                    (function () {
                      var picks = [];   // up to two hashes
                      function cards() { return $('.foto_session[data-hash]'); }
                      function refresh() {
                        cards().removeClass('cs_end cs_range').find('.cs_tag').remove();
                        var all = cards(), idx = picks.map(function (h) { return all.index(all.filter('[data-hash="' + h + '"]')); });
                        if (picks.length === 2) {
                          var a = Math.min(idx[0], idx[1]), b = Math.max(idx[0], idx[1]);
                          all.slice(a, b + 1).addClass('cs_range');
                          // gallery is newest first: the lower index is the LAST photo of the series
                          $(all[b]).prepend('<div class="cs_tag">EERSTE</div>');
                          $(all[a]).prepend('<div class="cs_tag">LAATSTE</div>');
                          $('#cs_info').text((b - a + 1) + " foto's: van " +$(all[b]).data('time') + ' t/m ' + $(all[a]).data('time') +
                                             ' (rijen: ' + Math.ceil((b - a + 1) / Math.max(1, +$('#cs_cols').val() || 6)) + ')');
                          $('#cs_make').prop('disabled', false);
                        } else if (picks.length === 1) {
                          $(all[idx[0]]).prepend('<div class="cs_tag">GEKOZEN</div>');
                          $('#cs_info').text('Klik nu op de laatste foto van de reeks.');
                          $('#cs_make').prop('disabled', true);
                        } else {
                          $('#cs_info').text('Klik op de eerste foto van de reeks.');
                          $('#cs_make').prop('disabled', true);
                        }
                        picks.forEach(function (h) { all.filter('[data-hash="' + h + '"]').addClass('cs_end'); });
                      }
                      $('#cs_start').on('click', function () { $('body').addClass('cs_mode'); $('#cs_bar').css('display', 'flex'); picks = []; refresh(); });
                      $('#cs_cancel').on('click', function () { $('body').removeClass('cs_mode'); $('#cs_bar').hide(); picks = []; refresh(); });
                      $('#cs_cols').on('input', refresh);
                      // in contactsheet mode a click on a card picks it (links inside the card don't navigate)
                      $(document).on('click', '.foto_session[data-hash]', function (e) {
                        if (!$('body').hasClass('cs_mode')) return;
                        e.preventDefault();
                        var h = $(this).data('hash');
                        if (picks.length === 2) picks = [];
                        if (picks[0] !== h) picks.push(h);
                        refresh();
                      });
                      $('#cs_make').on('click', function () {
                        if (picks.length !== 2) return;
                        var q = $.param({ from: picks[0], to: picks[1], cols: $('#cs_cols').val(), crop: $('input[name=cs_crop]:checked').val() });
                        $('#cs_info').text('Contactsheet wordt gemaakt … dit kan ongeveer een halve minuut duren; de download start vanzelf.');
                        window.location.href = 'contactsheet.php?' + q;
                      });
                    })();
                    </script>
                    <?php
                    foreach ( $rijen as $return )
                      {

                      $nrqr                 = 0;
                      $nrpage               = 0;
                      $nrdownload           = 0;
                      $qr_only              = false;
                      $verhuur_label        = 'Auth code:';
                      $verhuur_tekst        = '-';
                      $show_session_link    = true;

                      $file_hash        = $return->filename_md5;
                      $log_created      = $return->datetime;
                      $geen_foto        = $return->geen_foto;
                      $file_url         = $geen_foto ? '' : get_photo_thumb_url ( $event_id, $file_hash );
                      $geen_foto_reden  = ( $return->upload_start === null ) ? 'upload niet gestart' : 'upload mislukt';
                      $print_probleem   = $return->print_probleem;

                      if ( in_array ( (int) $event_id, $photo_table_events ) && ! $geen_foto ) {
                        $log_session_id   = 0;
                        $show_session_link = false;
                        $verhuur_label    = 'E-mail:';
                        $verhuur_tekst    = $return->photo_email_address ?: '-';
                      }
                      else {
                        $log_session_id  = $return->log_session_id;
                        $sessie_soort    = $return->sessie_soort;
                        $auth_code       = $return->auth;
                        $gratis          = $return->gratis;

                        # logfile nog niet binnen: geen sessie-info, wel de foto
                        if ( $log_session_id === null ) {
                          $show_session_link = false;
                          $verhuur_label     = 'Sessie:';
                          $verhuur_tekst     = 'nog niet in logs';
                        }

                        # zonder foto zijn er ook geen QR-/view-/download-stats (en een lege hash zou verkeerd matchen)
                        if ( ! $geen_foto ) {
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
                        } // end ! $geen_foto

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
                      <div class="foto_session" <?php if ( ! $geen_foto ) { echo 'data-hash="' . htmlspecialchars ( $file_hash ) . '" data-time="' . htmlspecialchars ( $log_created ) . '"'; } ?> <?php if ( $geen_foto || $print_probleem <> '' ) { echo 'style="border:2px solid #e55353;"'; } ?>>
                        <?php if ( $print_probleem <> '' ) { ?>
                          <div style="background:#e55353;color:#fff;font-weight:bold;text-align:center;border-radius:6px;padding:4px;margin-bottom:6px;width:280px;">
                            NIET GEPRINT
                          </div>
                        <?php } ?>
                        <?php if ( $geen_foto ) { ?>
                          <div style="width:280px;height:200px;background:#fbe9e9;border-radius:6px;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#e55353;font-weight:bold;">
                            Betaald, geen foto<br>
                            <span style="font-weight:normal;font-size:13px;"><?php echo $geen_foto_reden; ?></span>
                          </div>
                        <?php } else { ?>
                          <img src="<?php echo $file_url; ?>" width="280" >
                        <?php } ?><br>
                        <table  class="table-timestamp">
                            <tr>
                              <td>Timestamp:</td>
                              <td><strong><?php echo $log_created; ?></strong></td>
                            </tr>
                            <tr>
                              <td><?php echo $verhuur_label; ?></td>
                              <td><strong><?php echo $verhuur_tekst; ?></strong></td>
                           </tr>
                           <?php if ( $print_probleem <> '' ) { ?>
                           <tr>
                              <td>Print:</td>
                              <td><strong style="color:#e55353;"><?php echo htmlspecialchars ( $print_probleem ); ?></strong></td>
                           </tr>
                           <?php } ?>
                           
                         <tr>
                            <td>
                              <?php if ( ! $geen_foto ) { ?>
                                <a class="blue-button" href="https://www.magicshoot.nl/foto/<?php echo $event_id; ?>/<?php echo $file_hash; ?>">Displaypage</a>
                                <?php
                                # laugh clip of the AI era-swap booth (api/post/upload_clip.php) — only shown when it exists
                                if ( is_file ( '/home/magicshoot/domains/magicshoot.nl/public_html/api/event/media/photos/' . (int) $event_id . '/' . basename ( $file_hash ) . '.mp4' ) ) { ?>
                                  <a class="blue-button" target="_blank" href="https://api.magicshoot.nl/event/media/photos/<?php echo (int) $event_id; ?>/<?php echo htmlspecialchars ( $file_hash ); ?>.mp4">Video</a>
                                <?php } ?>
                              <?php } ?>
                            </td>

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
