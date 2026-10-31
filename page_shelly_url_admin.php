  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");

  $event_id = $_SESSION['event_id'];
    # check role
    if ( ! ( $_SESSION['client_role'] == 'admin' ) ) {
      header('Location: https://stats.magicshoot.nl/admin/index.php');
      exit;
    }
  ?>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item">Shelly URL</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">


       
          <?php 
          
          
          if ( $_REQUEST['mode'] == 'save' ) {
            $url = mysqli_real_escape_string ( $cn, $_REQUEST['url'] );
            $auth_key = mysqli_real_escape_string ( $cn, $_REQUEST['auth_key'] );
            $sql = "UPDATE photobooth_shelly_auth
                       SET url      = '" . $url      . "',
                           auth_key = '" . $auth_key . "'";
  
            $result   = mysqli_query( $cn , $sql );
            if ( !$result ) {
                ?><span class="alert rounded-pill  alert-danger">Fout bij opslaan gegevens!</span><br><br><br><?php
            } else {
                ?><span class="alert rounded-pill  alert-success">De gegevens zijn succesvol opgeslagen.</span><br><br><br><?php
            }
          }
          
          # haal shelly auth gegevens op        
          $sql = "SELECT *
                    FROM photobooth_shelly_auth";
          
          $result   = mysqli_query( $cn , $sql );
          if ( !$result ){echo "ERROR bij ophalen shelly auth gegevens";}
         
          $return               = $result->fetch_object();
          $shelly_url           = $return->url;
          $shelly_auth_key      = $return->auth_key;
          ?> 
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Shelly gegevens</h2>
                    </div>
                  <div class="card-body">
                    <p>Let op: alleen editten als de url veranderd is door Shelly (dit vind je terug in de mobiel shelly app), of als de auth code veranderd is doordat er een nieuw password gekozen is. <br>Dus niet zo maar editten, de mogelijkheid bestaat namelijk dat dan geen enkele fotokast meer aan of uitgezet wordt!</p>
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                      
                        <tbody>
                          
                            
                                <?php 
                                if ( isset ( $_REQUEST['mode'] ) && $_REQUEST['mode'] == 'edit' ) {
                                    ?>
                                    
                                        <form action ="page_shelly_url_admin.php">
                                            <input type ="hidden" name="mode" value="save">
                                            <tr><td>URL:</td><td><input size="50" type="text" name="url" value="<?php echo $shelly_url; ?>"></td></tr>
                                            <tr><td>Auth Key:</td><td><input size="150" type="text" name="auth_key" value="<?php echo $shelly_auth_key; ?>"></td></tr>
                                            <tr><td><button type="submit" class="btn-sm btn-info">Opslaan</button></td></tr>
                                        </form>
                                    
                                    <?php
                                    }
                                else { 
                                    ?>
                                    <tr><td>URL: </td><td><?php echo $shelly_url; ?></td></tr>
                                    <tr><td>Auth Key:</td><td><?php echo $shelly_auth_key; ?></td></tr>
                                    <tr><td><a style="text-decoration:none; " href='page_shelly_url_admin.php?mode=edit'><span class="btn-sm btn-info"> Edit </span></a></td></tr>
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