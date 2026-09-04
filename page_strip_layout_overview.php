  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  $event_id         = $_SESSION['event_id'];
    # check role
    if ( ! ( $_SESSION['client_role'] == 'admin'  ) ) {
      header('Location: https://stats.magicshoot.nl/admin/index.php');
      exit;
    }
  $basepath         = "/home/magicshoot/domains/magicshoot.nl/public_html/api/stack";
	$path 	          = $basepath . '/event/' . $event_id .'/';
  $strip_layout_id  = mysqli_real_escape_string( $cn, $_REQUEST['strip_layout_id']);
  $mode             = mysqli_real_escape_string( $cn, $_REQUEST['mode']);
 
  

if ( $mode =="delete_jpeg" ) 
{
  $path            = "/home/magicshoot/domains/magicshoot.nl/public_html/stats/strip_layouts/generated";
  $img             = "strip_layout_" . $strip_layout_id . ".jpg";
  $img_source      = $path . '/' . $img;
  $result_remove = unlink( $img_source );
 
 
}
  ?>
  <style>
  .layout_number {
    float: left;
    font-size: 38px;
    color: #99f;
    padding-left: 20px;
    padding-top: 0px;
  }
  .layout-container {
    display:  block;
    border-width: 1px;
    border-color: #303c54;
    border-style: solid;
    border-radius: 15px;
    background-color: #fff;
    margin-bottom:  10px;
    padding:  0;
    text-align: center;
    width:  320px;
    float:  left;
    margin-right:  20px;
    margin-bottom:  20px;
    padding-bottom: 20px;
  }


  .layout-header {
    display:  block;
    font-family: sans-serif;
    font-weight: bold;
    font-size: 16px;
    color: #fff;
    text-align: center;
    width: 320px;
    background-color: #303c54;
    border-radius: 15px 15px 0 0;
    border-width: 1px;
    border-color: #303c54;
    border-style: solid;
    padding-top:  10px;
    padding-bottom:  10px;
    height: 115px;
    text-transform: uppercase;
  }

  .layout-container-landscape {
    display:  block;
    border-width: 1px;
    border-color: #303c54;
    border-style: solid;
    border-radius: 15px;
    background-color: #fff;
    margin-bottom:  10px;
    padding:  0;
    text-align: center;
    width:  465px;
    float:  left;
    margin-right:  20px;
    margin-bottom:  20px;
    padding-bottom: 20px;
  }


  .layout-header-landscape {
    display:  block;
    font-family: sans-serif;
    font-weight: bold;
    font-size: 16px;
    color: #fff;
    text-align: center;
    width: 465px;
    background-color: #303c54;
    border-radius: 15px 15px 0 0;
    border-width: 1px;
    border-color: #303c54;
    border-style: solid;
    padding-top:  10px;
    padding-bottom:  10px;
    height: 115px;
    text-transform: uppercase;
  }
 
  .layout-name {
    display:  block;
    width: 190px;
    float: left;
    margin-left: 20px;
    text-align: left;
    border: 0px solid white;

  }

  .strip_header {
    color:#fff;
    font-size: 9px;
    display: block;
    height: 32px;
    font-weight: normal;

  }

  .layout-current {
    color:#f7d30f;
    font-size: 11px;
    display: block;
    height: 12px;
    font-weight: bold;
  }
  .layout-image{
    display: block;
    margin-top: 20px;
    font-weight:  bold;
    margin-bottom: 0px;
    font-size: 18px;
    margin-bottom: 10px;
  }

  .layout-strook-img {
    border: 1px solid #111;
  }
  .layout-object {
    margin-bottom: 20px;
    margin-top: 0;
    padding-top: 0;
  }
  .button-container {
    display: block;
    height:  45px;
  }
</style>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item">Layout overview</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">

         
       
         
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <p><strong>Dit is nog niet operationeel! (je kunt wel de Photoshop template downloaden van de Basis layout onder nummer 1. om strookjes mee te ontwerpen)</strong></p>
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Layout overview</h2>
                    </div>
                  <div class="card-body">
                    
                    <table class="table table-responsive-sm table-bordered table-striped ">
                    <thead>
                          
                           <td><strong>portrait (staand)</strong></td>
                            <td><strong>pixels</strong></td>
                            <td><strong>landscape (liggend)</strong></td>
                            <td><strong>pixels</strong></td>
                        
                     </thead>  
                      <tr>
                          <td>breedte</td>
                          <td>2480</td>
                          <td>breedte</td>
                          <td>3688</td>
                      </tr>
                      <tr>
                          <td>hoogte</td>
                          <td>3688</td>
                          <td>hoogte</td>
                          <td>2480</td>
                      </tr>
                      <tr>
                          <td>snijmarge left</td>
                          <td>40</td>
                          <td>snijmarge left</td>
                          <td>44</td>
                      </tr>
                      <tr>
                          <td>snijmarge right</td>
                          <td>40</td>
                          <td>snijmarge right</td>
                          <td>44</td>
                      </tr>
                      <tr>
                          <td>snijmarge top</td>
                          <td>44</td>
                          <td>snijmarge top</td>
                          <td>40</td>
                      </tr>
                      <tr>
                          <td>snijmarge bottom</td>
                          <td>44</td>
                          <td>snijmarge bottom</td>
                          <td>40</td>
                      </tr>
                      <tr>
                          <td>witruimte om 1 photo</td>
                          <td>60</td>
                          <td>witruimte om 1 photo</td>
                          <td>60</td>
                      </tr>
                    </table>

                  </div>
                  <div class="card-body">
                   <!-- <a href="page_strip_layout_overview.php?mode=new" class="btn btn-info">Nieuwe layout</a>-->
                    <!--<a href="https://stats.magicshoot.nl/strook_templates/<?php echo $_SESSION['event_id']; ?>.psd" class="btn btn-success">Download strook template (.psd)</a>-->
                  </div>
                  <div class="card-body">
                    <?php 
                    # twee tabellen die samen 1 layout met fotootjes vormen
                    # tabel 1.: strip_layout --> hier wordt het formaat beschreven, of het wel of niet door midden gesneden wordt en een staande of liggende layout is en hoeveel fotootjes erop staan)
                    # tabel 2.: strip_photo --> in deze tabel staat per opvolgende foto waar deze op het papier komt

                    $sql = "SELECT *
                              FROM strip_layout
                              
                          ORDER BY strip_layout_id";
                    
                    $result = mysqli_query( $cn , $sql );
                    while ( $return	= mysqli_fetch_object ( $result ) )
                      {
                      $strip_layout_id               = $return->strip_layout_id;
                      $strip_layout_name             = $return->strip_layout_name;
                      $strip_layout_papersize        = $return->strip_layout_papersize;
                      $strip_is_base_layout          = $return->strip_is_base_layout;
                      $strip_layout_width            = $return->strip_layout_width;
                      $strip_layout_height           = $return->strip_layout_height;
                      $strip_layout_second_cut       = $return->strip_layout_second_cut;
                      $strip_number_of_photos        = $return->strip_number_of_photos;

                      if ( $strip_layout_width > $strip_layout_height )  { 
                        $container_class = "layout-container-landscape"; 
                        $header_class = "layout-header-landscape";
                        $image_width = "445";
                      } else {
                        $container_class = "layout-container"; 
                        $header_class = "layout-header";
                        $image_width = "300";
                      }



                      if ( $strip_layout_second_cut == 1 )
                      { $aantal_strips = 2;} else { $aantal_strips = 1; }

                      

                     
                      ?>
                      <div class="<?php echo $container_class; ?>"  style="background-color:<?php //echo $layout_color; ?>;">
                     
                        <div class="<?php echo $header_class; ?>" >
                            <span class="layout_number"><?php echo $return->strip_layout_id; ?></span>
                            <?php echo $return->strip_layout_name; ?><br>
                            <div class="strip_header">
                                papierformaat: <?php echo $return->strip_layout_papersize; ?><br>
                                strook.png width x height: <?php echo $return->strip_layout_width . " x " . $return->strip_layout_height; ?><br>
                                aantal fotos: <?php echo $return->strip_number_of_photos; ?></br>
                                aantal stripjes: <?php echo $aantal_strips; ?>
                            </div>
                         
                        </div>
                    
                        <div class="layout-image">
                        <a href="page_strip_layout_overview.php?mode=delete_jpeg&strip_layout_id=<?php echo $strip_layout_id; ?>" class="btn btn-info">Genereer plaatje opnieuw</a><br><br>
                        <img class="layout-strook-img" src='https://www.magicshoot.nl/stats/strip_combine.php?strip_layout_id=<?php echo $strip_layout_id ;?>' width="<?php echo $image_width; ?>">
                          
                        </div>
                          <div class="button-container">
                            <?php 
                            if ( $strip_is_base_layout == 1 ) {
                               echo "<strong>Basis layout</strong><br>"; 
                            }  
                            
                            if ( file_exists( "/home/magicshoot/domains/magicshoot.nl/public_html/stats/strook_templates/strip_layout_".$strip_layout_id . ".psd"))
                            { 
                              ?><a href="https://stats.magicshoot.nl/strook_templates/strip_layout_<?php echo $strip_layout_id; ?>.psd">Photoshop template</a><br><?php 
                            } ?>
                          <!--  <a href="https://api.magicshoot.nl/get/get_layout.php?strip_layout_id=<?php echo $strip_layout_id; ?>" target="_blank">Layout als JSON</a>-->
                          </div>
                        </div>
                    <?php 
                   
                  } ?>

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