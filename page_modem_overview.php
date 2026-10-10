  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");

  $event_id = $_SESSION['event_id'];
  
  # check role
   if ( !$_SESSION['client_role'] == 'admin'  ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }


  ?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Modem acties</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">


       
          <?php     
          # haal modem gegevens op        
          $sql = "SELECT modem_phonenumber, modem_serial, sim_pin, sim_puk, sim_imei
                    FROM event
                   WHERE event_id   = '" . $event_id . "'";
          
          $result   = mysqli_query( $cn , $sql );
          if ( !$result ){echo "ERROR bij ophalen modem gegevens";}
          $counter          = 0;
          $return           = $result->fetch_object();
          $modem_serial     = $return->modem_serial;
          $sim_phonenumber  = $return->modem_phonenumber;
          $sim_pin          = $return->sim_pin;
          $sim_puk          = $return->sim_puk;
          $sim_imei         = $return->sim_imei;
          ?> 
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Modem gegevens</h2>
                    </div>
                  <div class="card-body">
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                        <thead>
                          <tr>
                                                   
                            <th>Event ID</th>
                            <th>Modem serial</th>
                            <th>SIM phonenumber</th> 
                            <th>SIM pin</th> 
                            <th>SIM puk</th> 
                            <th>SIM IMEI</th> 
                            
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td><?php echo $event_id; ?></td>
                            <td><?php echo $modem_serial; ?></td>
                            <td><?php echo $sim_phonenumber; ?></td>
                            <td><?php echo $sim_pin; ?></td>
                            <td><?php echo $sim_puk; ?></td>
                            <td><?php echo $sim_imei; ?></td>
                          </tr>
                        
                        </tbody>
                      </table>


                      <h2> Modem Remote beheer via RMS Teltonika</h2>
                      <p>Ga naar: <a href="https://rms.teltonika-networks.com/management/devices">RMS Teltonika</a></p>
                      <h2>Reboot modem op afstand</h2>
                      <p>Alleen een medewerker van MagicShoot kan het modem rebooten (vanaf nummer +31621220846).</p>
                      <p>SMS:<br>
                      <strong><?php echo $modem_serial; ?> status</strong><br>
                      <strong><?php echo $modem_serial; ?> reboot</strong><br>
                      naar nummer:<br>
                      <strong><?php echo $sim_phonenumber; ?></strong></p>
                    <br>
                    <h2>Modem settings</h2>
                    <strong>Heartbeat instellen bij Auto Reboot functie:</strong>
                    <p>https://api.magicshoot.nl/post/heartbeat_modem_add.php?event_id=<?php echo $event_id; ?></p>
                    <br>

                    <strong>RUT901 Modem op IPv4 zetten (anders werkt Payter module niet!):</strong>
                    <ul>
                      <li>Ga naar Netwerk -> WAN</li>
                      <li>Klik op Edit bij de LAN connectie</li>
                      <li>Kies IPv4 Only</li>
                      <li>Kies bij DHCP voor alleen IPv4</li>
                      <li>Zorg dat er bij DNS de servers: 8.8.8.8 en 8.8.4.4 ingevuld staan (evt Add).</p>
                    </ul>
                    <br>
                    
                    <strong>SMS services instellen:</strong>
                    <p>Zet bij de SMS services reboot' en 'status' aan en zet Authorization op Serialnumber ipv username.</p>

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