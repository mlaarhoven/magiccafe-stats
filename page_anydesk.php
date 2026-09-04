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

  
  global $cn;

  # haal anydesknummer op uit database
  $sql = "SELECT event_name,
                 anydesk_id
            FROM event
           WHERE event_id = '" . $_SESSION['event_id']  . "'";
  $result             = mysqli_query( $cn , $sql );
  $return	             = mysqli_fetch_object ( $result );
  $event_name                 = $return->event_name;
  $anydesk_id                 = $return->anydesk_id;
    
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


  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">
                 <!-- /.col-->
                 <div class="col-sm-6 col-lg-12">
                  
                <div class="row">


                 <!-- /.card-->
                 <div class="card" style="width: 350px; margin-right: 10px;">
                    <div class="card-header bg-facebook content-center">
                   
                      <h2 class="text-white text-uppercase">Anydesk</h2>
                    </div>
                    <div class="card-body">
                       <table class="table  table-bordered table-striped table-sm devicetable">
                               
                                <tbody>
                                    <tr>
                                        <td>Open Anydesk:</td>
                                        <td><a href="anydesk:<?php echo $anydesk_id; ?>"><?php echo $anydesk_id; ?></a></td>
                                    </tr>   
                                </tbody>
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