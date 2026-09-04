  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  //$event_id = 418;
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
        <li class="breadcrumb-item">Marketing - conversion</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>


  <?php
  # haal aantallen clicks per source op uit: click
  $aantal_per_source_array  = array();
  $sql = "SELECT COUNT(*) AS aantal, source
             FROM click
         GROUP BY source";
  
  $result = mysqli_query( $cn , $sql );
  while ( $return_clicks_per_source = mysqli_fetch_object ( $result ) ) 
  {
    
    $source = $return_clicks_per_source->source;
    $aantal = $return_clicks_per_source->aantal;
    $aantal_per_source_array[$source] = $aantal;
  }


  # haal visits op uit: click_conversion 
  $aantal_visits_per_source  = array();
  $aantal_contactform_send_per_source  = array();
  $sql = "SELECT COUNT(*) AS aantal_visits, click.source
            FROM click_conversion 
       LEFT JOIN click USING ( click_id ) 
       WHERE visit_datetime <> '0000-00-00 00:00:00'
        GROUP BY click.source;";
  
  $result = mysqli_query( $cn , $sql );
  while ( $return_clicks_per_source = mysqli_fetch_object ( $result ) ) 
  {
    
    $source = $return_clicks_per_source->source;
    $aantal_visits = $return_clicks_per_source->aantal_visits;
    $aantal_visits_per_source[$source] = $aantal_visits;

    # haal nu met de source op hoe vaak het contactform is ingevuld
    $sql2 = "SELECT COUNT(*) AS aantal_ingevuld
            FROM click_conversion 
       LEFT JOIN click USING ( click_id ) 
       WHERE click.source = '". $source ."'
       AND contactformsend_datetime <> '0000-00-00 00:00:00'";
  
  $result2 = mysqli_query( $cn , $sql2 );
  while ( $return_ingevuld = mysqli_fetch_object ( $result2 ) ) {

    $aantal_ingevuld = $return_ingevuld->aantal_ingevuld;
    $aantal_contactform_send_per_source[$source] = $aantal_ingevuld;
  }
  }

  
  
  ?>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">


          
            <!-- /.row-->
            <!-- opzetje overzicht tabel alle ingezette advertising -->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card">
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Marketing - conversion</h2>
                  </div>
                  <div class="card-body"> 
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                      <thead>
                        <tr>
                          <th>Source</th>
                          <th>Redirect URL</th>
                          <th>Total cost</th>
                          <th>Click to website</th>
                          <th>Website visits</th>
                          <th>Contact forms fill ins</th>
                          
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td>Instagram Ad Owner</td>
                          <td>https://api.magicshoot.nl/marketing/redirect_insta_owner_click.php</td>
                          <td>&euro; 0.00</td>
                          <td><?php echo $aantal_per_source_array['insta_ad_owner']; ?></td>
                          <td><?php echo $aantal_visits_per_source['insta_ad_owner']; ?></td>
                          <td><?php echo $aantal_contactform_send_per_source['insta_ad_owner']; ?></td>
                          
                        </tr>
                        <tr>
                          <td>Instagram Ad Visitor</td>
                          <td>https://api.magicshoot.nl/marketing/redirect_insta_visitor.php</td>
                          <td>&euro; 0.00</td>
                          <td><?php echo $aantal_per_source_array['insta_ad_visitor']; ?></td>
                          <td><?php echo $aantal_visits_per_source['insta_ad_visitor']; ?></td>
                          <td><?php echo $aantal_contactform_send_per_source['insta_ad_visitor']; ?></td>
                          
                        </tr>
                        <tr>
                          <td>A5 Flyer bar</td>
                          <td>https://api.magicshoot.nl/marketing/redirect_flyer_A5_qr.php</td>
                          <td>&euro; 0.00</td>
                          <td><?php echo $aantal_per_source_array['flyer_A5']; ?></td>
                          <td><?php echo $aantal_visits_per_source['flyer_A5']; ?></td>
                          <td><?php echo $aantal_contactform_send_per_source['flyer_A5']; ?></td>
                          
                        </tr>
                        <tr>
                          <td>A4 PDF email</td>
                          <td>https://api.magicshoot.nl/marketing/redirect_flyer_A4_qr.php</td>
                          <td>&euro; 0.00</td>
                          <td><?php echo $aantal_per_source_array['flyer_A4']; ?></td>
                          <td><?php echo $aantal_visits_per_source['flyer_A4']; ?></td>
                          <td><?php echo $aantal_contactform_send_per_source['flyer_A4']; ?></td>
                          
                        </tr>
                        <tr>
                          <td>Strook QR</td>
                          <td>https://api.magicshoot.nl/marketing/redirect_strook_qr.php</td>
                          <td>&euro; 0.00</td>
                          <td><?php echo $aantal_per_source_array['strook_qr']; ?></td>
                          <td><?php echo $aantal_visits_per_source['strook_qr']; ?></td>
                          <td><?php echo $aantal_contactform_send_per_source['strook_qr']; ?></td>
                          
                        </tr>
                        <tr>
                          <td>Button Displaypage</td>
                          <td>varieert per event. <br>Link staat in magicshoot/api/event/[event_id]/magicstrook.php</td>
                          <td>&euro; 0.00</td>
                          <td><?php echo $aantal_per_source_array['displaypage']; ?></td>
                          <td><?php echo $aantal_visits_per_source['displaypage']; ?></td>
                          <td><?php echo $aantal_contactform_send_per_source['displaypage']; ?></td>
                          
                        </tr>
                        <tr>
                          <td>Direct traffic (SEO)</td>
                          <td>direct / search</td>
                          <td>&euro; 0.00</td>
                          <td>url ingetypt / via search / crawlers</td>
                          <td><?php echo $aantal_visits_per_source['direct']; ?></td>
                          <td><?php echo $aantal_contactform_send_per_source['direct']; ?></td>
                          
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>  
                  



       
          <?php      
          
        

           # haal de event_ids op
            $event_array      = array();
            $event_name_array = array();
            $sql = "SELECT event.event_id,
                           event.event_name
                      FROM photobooth_event_ids 
                      LEFT JOIN event USING ( event_id)
                      WHERE dashboard_show ='1'
                        ORDER BY 	dashboard_order_nr	 ASC";
                       
            $result             = mysqli_query( $cn , $sql );
            while ( $return     = mysqli_fetch_object ( $result ) ) {
                  $event_id   = $return->event_id;
                  $event_name   = $return->event_name;
                  array_push ( $event_array, $event_id );
                  $event_name_array[$event_id]=$event_name ;
            }


        
           
     
            ?> 
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card">
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Ruwe data</h2>
                  </div>
                  <div class="card-body"> 
                    <h2>Doorkliks vanaf displaypage waarop de foto getoond wordt, naar Magicstrook.nl</h2>
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                        <thead>
                          <tr>
                            <th>Event ID</th>
                            <th>Locatie fotokast</th>
                            <th>Log session ID</th>
                            <th>Click date time</th>
                            <th>IP</th>
                            <th>URL From</th>
                            <th>URL To</th>
                          </tr>
                        </thead>
                        <tbody>
                       <?php
                      # loop nu de event_id's af en toon aantallen
                      foreach ($event_array as $event_id) { 
                          $sql = "SELECT *
                                    FROM click
                                    WHERE event_id       = '" . $event_id       . "'";
                          
                          $result     = mysqli_query( $cn , $sql );
                          while ( $return     = mysqli_fetch_object ( $result ) )
                            {
                            ?>
                            <tr>
                              <td><?php echo $return->event_id; ?></td>
                              <td><?php echo $event_name_array[$event_id]; ?></td>
                              <td><a href="page_session_detail.php?log_session_id=<?php echo $return->log_session_id; ?>&event_id=<?php echo $event_id; ?>"><?php echo $return->log_session_id; ?></a></td>
                              <td><?php echo $return->datetime; ?></td>
                              <td><?php echo $return->ip; ?></td>
                              <td><?php echo $return->url_from; ?></td>
                              <td><?php echo $return->url_to; ?></td>
                              
                            </tr>
                            <?php 
                            } 
                          } // end for each?>
                        </tbody>
                      </table>
                   <?php
                 
                  ?>
                </div>
              </div>
            </div>
            </div>
            


            <!-- /.row-->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card">
                  <div class="card-body"> 
                    <h2>Doorkliks vanaf Instagram campagne, naar Magicstrook.nl</h2>
                    
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                        <thead>
                          <tr>
                          
                            <th>Click date time</th>
                            <th>IP</th>
                            <th>URL From</th>
                            <th>URL To</th>
                          </tr>
                        </thead>
                        <tbody>
                       <?php
                    
                          $sql = "SELECT *
                                    FROM click
                                    WHERE event_id        = '0'";
                          
                          $result     = mysqli_query( $cn , $sql );
                          while ( $return     = mysqli_fetch_object ( $result ) )
                            {
                            ?>
                            <tr>
                              <td><?php echo $return->datetime; ?></td>
                              <td><?php echo $return->ip; ?></td>
                              <td><?php echo $return->url_from; ?></td>
                              <td><?php echo $return->url_to; ?></td>
                              
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
        </div>
      </main>
      <footer class="c-footer">
        <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> © <?php echo date('Y'); ?> MagicShoot.</div>
        <div class="ml-auto">Powered by&nbsp;<a href="https://www.MagicShoot.nl/">MagicShoot</a></div>
        </footer>
      </div>
    </div>  
<?php include("partial_footer.php"); ?>
