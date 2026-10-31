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
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item">Payter APIs</li>
        
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
            $mypayter_url       = mysqli_real_escape_string ( $cn, $_REQUEST['mypayter_url'] );
            $mypayter_user      = mysqli_real_escape_string ( $cn, $_REQUEST['mypayter_user'] );
            $mypayter_password  = mysqli_real_escape_string ( $cn, $_REQUEST['mypayter_password'] );
            $mypayter_domain    = mysqli_real_escape_string ( $cn, $_REQUEST['mypayter_domain'] );
            $cps_api_url        = mysqli_real_escape_string ( $cn, $_REQUEST['cps_api_url'] );
            $cps_api_key_prod   = mysqli_real_escape_string ( $cn, $_REQUEST['cps_api_key_prod'] );
            $cps_api_key_test   = mysqli_real_escape_string ( $cn, $_REQUEST['cps_api_key_test'] );
            $sql = "UPDATE photobooth_payter_auth
                       SET mypayter_url         = '" . $mypayter_url  . "',
                           mypayter_user        = '" . $mypayter_user . "',
                           mypayter_password    = '" . $mypayter_password . "',
                           mypayter_domain      = '" . $mypayter_domain . "',
                           cps_api_url          = '" . $cps_api_url . "',
                           cps_api_key_prod     = '" . $cps_api_key_prod . "',
                           cps_api_key_test     = '" . $cps_api_key_test . "'";
  
            $result   = mysqli_query( $cn , $sql );
            if ( !$result ) {
                ?><span class="alert rounded-pill  alert-danger">Fout bij opslaan gegevens!</span><br><br><br><?php
            } else {
                ?><span class="alert rounded-pill  alert-success">De gegevens zijn succesvol opgeslagen.</span><br><br><br><?php
            }
          }
          
          # haal Payter auth gegevens op        
          $sql = "SELECT *
                    FROM photobooth_payter_auth";
          
          $result   = mysqli_query( $cn , $sql );
          if ( !$result ){echo "ERROR bij ophalen Payter auth gegevens";}
         
          $return               = $result->fetch_object();
          $mypayter_url         = $return->mypayter_url;
          $mypayter_user        = $return->mypayter_user;
          $mypayter_password    = $return->mypayter_password;
          $mypayter_domain      = $return->mypayter_domain;

          $cps_api_url          = $return->cps_api_url;
          $cps_api_key_prod     = $return->cps_api_key_prod;
          $cps_api_key_test     = $return->cps_api_key_test;
          ?> 
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Payter gegevens</h2>
                    </div>
                  <div class="card-body">
                      <a href="page_api_admin_shelly.php" class="btn btn-info">Shelly API</a>
                      <a href="page_api_admin_payter.php" class="btn btn-info">Payter API</a>
                      <br><br>
                    <p><strong>Let op: Niet zo maar editten, dan kan geen enkele fotokast meer betalingen verrichten!</strong></p>
                    <table class="table table-responsive-sm table-bordered table-striped table-sm">
                      
                        <tbody>
                          
                            
                                <?php 
                                if ( isset ( $_REQUEST['mode'] ) && $_REQUEST['mode'] == 'edit' ) {
                                    ?>
                                    
                                        <form action ="page_api_admin_payter.php">
                                       
                                            <input type ="hidden" name="mode" value="save">
                                            <tr><td>MyPayter URL:</td><td><input size="50" type="text" name="mypayter_url" value="<?php echo $mypayter_url; ?>"></td></tr>
                                            <tr><td>MyPayter Username:</td><td><input size="150" type="text" name="mypayter_user" value="<?php echo $mypayter_user; ?>"></td></tr>
                                            <tr><td>MyPayter Password:</td><td><input size="150" type="text" name="mypayter_password" value="<?php echo $mypayter_password; ?>"></td></tr>
                                            <tr><td>MyPayter Domain:</td><td><input size="150" type="text" name="mypayter_domain" value="<?php echo $mypayter_domain; ?>"></td></tr>
                                            <tr><td>CPS URL:</td><td><input size="150" type="text" name="cps_api_url" value="<?php echo $cps_api_url; ?>"></td></tr>
                                            <tr><td>CPS API key productie:</td><td><input size="150" type="text" name="cps_api_key_prod" value="<?php echo $cps_api_key_prod; ?>"></td></tr>
                                            <tr><td>CPS API key test:</td><td><input size="150" type="text" name="cps_api_key_test" value="<?php echo $cps_api_key_test; ?>"></td></tr>
                                            <tr><td><button type="submit" class="btn-sm btn-info">Opslaan</button></td></tr>
                                       
                                        </form>
                                    
                                    <?php
                                    }
                                else { 
                                    ?>
                                    <tr><td>MyPayter URL: </td><td><?php echo $mypayter_url; ?></td></tr>
                                    <tr><td>MyPayter Username:</td><td><?php echo $mypayter_user; ?></td></tr>
                                    <tr><td>MyPayter Password:</td><td><?php echo $mypayter_password; ?></td></tr>
                                    <tr><td>MyPayter Domain:</td><td><?php echo $mypayter_domain; ?></td></tr>
                                    <tr><td>CPS URL:</td><td><?php echo $cps_api_url; ?></td></tr>
                                    <tr><td>CPS API key productie:</td><td><?php echo $cps_api_key_prod; ?></td></tr>
                                    <tr><td>CPS API key test:</td><td><?php echo $cps_api_key_test; ?></td></tr>
                                    <tr><td><a style="text-decoration:none; " href='page_api_admin_payter.php?mode=edit'><span class="btn-sm btn-info"> Edit </span></a></td></tr>
                                    <?php
                                }
                                ?>
                                
                            
                        
                        
                        </tbody>
                      </table>

                      Uitleg Payter CPS api: <a href="https://cps.mypayter.com/swagger-ui/index.html?configUrl=/v3/api-docs/swagger-config#/">Uitleg</a>
                     
                   
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