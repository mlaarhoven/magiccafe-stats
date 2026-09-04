  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  global $event_array, $cn;
  setlocale(LC_MONETARY, "nl_NL");    

  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }

  get_event_data ();
  
  
  ?>
   <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
    <style>
    #images{ display: block;  }
    .voorbeeld { display:  block; border: solid 2px #eee; padding:  10px; margin:  5px; float: left; width:  250px; height:  auto;}
    .card {
        width: 400px;
        margin-right: 10px;
    }
    </style>
  </header>

<?php
# --- FUNCTIONS --- #

function   get_event_data () {
    global $cn;
    global $event_array; 
    global $event_data_array;
    $event_id = $_SESSION['event_id'];
    $sql = "SELECT payter_terminal_id, event_name
              FROM event
             WHERE event_id = '" . $event_id . "'";
    $result             = mysqli_query( $cn , $sql );
    $return     = mysqli_fetch_object ( $result );
    $payter_terminal_id         = $return->payter_terminal_id;
    $event_name                 = $return->event_name;

    $event_data_array[$event_id]['payter_terminal_id']  = $payter_terminal_id;
    $event_data_array[$event_id]['event_name']  = $event_name;
    
}



function get_payter_terminal_info ( $event_id )
{
    global $event_data_array; 
    global $cn;

    # haal de Payter CPS api_key en de url op
    $sql =    "SELECT cps_api_url, 
                      cps_api_key_prod
                 FROM photobooth_payter_auth";
    $result              = mysqli_query( $cn , $sql );
    $return	             = mysqli_fetch_object ( $result );
    $cps_api_url         = $return->cps_api_url;
    $cps_api_key_prod    = $return->cps_api_key_prod;
    
    # terminal id komt uit tabel: event
    $payter_terminal_id   =  $event_data_array[$event_id]['payter_terminal_id'];
    
    $url  = $cps_api_url . "/terminals/" . $payter_terminal_id;
    
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $headr = array();
    $headr[] = 'Content-length: 0';
    $headr[] = 'Content-type: application/json';
    $headr[] = 'Authorization: CPS apikey="'. $cps_api_key_prod . '"';
    curl_setopt($curl, CURLOPT_HTTPHEADER,$headr);
    $data = curl_exec($curl);
    curl_close($curl);
    $myData                 = json_decode($data, true);
    $online = $myData['online'];
    if ( $online == 1) { return 1;} else { return 0;}

}


  # --- END FUNCTIONS --- #
  ?>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">
                 <!-- /.col-->
                 <div class="col-sm-6 col-lg-12">
                  
                <div class="row">


                <?php 
                $event_id = $_SESSION['event_id'];
                
                $payter_terminal_online = get_payter_terminal_info ( $event_id   );
                
                ?>
                 <!-- /.card-->
                 <div class="card" style="width: 350px; margin-right: 10px;">
                    <div class="card-header bg-facebook content-center">
                   
                      <h2 class="text-white text-uppercase">Betaalterminal</h2>
                    </div>
                    <div class="card-body">
                        <table class="table table-responsive-sm table-bordered table-striped table-sm">
                           
                          
                            <tr>
                                <td>Betaal terminal online</td>
                                <td><?php 
                                    if ( $payter_terminal_online == 1 ) 
                                        { 
                                        ?><span class="badge rounded-pill btn-success">Staat aan</span><?php
                                    } else { 
                                        ?><span class="badge rounded-pill btn-info">Staat uit</span><?php
                                    } ?></td>
                            </tr>
                            <tr>
                                <td>Betaal terminal ID</td>
                                <td><strong><?php 
                                    echo $event_data_array[$event_id]['payter_terminal_id']; 
                                    ?></strong></td>
                            </tr>

                            
                        </table>
                    </div>
                 </div>
                 <!-- / end card-->   
                
                </div>
                <!-- end row -->
              </div>
            


            <!-- / end col-->   
          </div>
        </div>
      </main>
      <footer class="c-footer">
          <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> © <?php echo date('Y'); ?> MagicShoot.</div>
          <div class="ml-auto">Powered by&nbsp;<a href="https://www.magicshoot.nl/">MagicShoot</a></div>
        </footer>
      </div>
    </div>  
<?php include("partial_footer.php"); ?>