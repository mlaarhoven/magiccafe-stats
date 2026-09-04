  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  $event_id  = $_SESSION['event_id'];
    # check role
    if ( ! ( $_SESSION['client_role'] == 'admin'  ) ) {
      header('Location: https://stats.magicshoot.nl/admin/index.php');
      exit;
    }
  $basepath  = "/home/magicshoot/domains/magicshoot.nl/public_html/api/stack";
	$path 	   = $basepath . '/event/' . $event_id .'/';
  $mode      = mysqli_real_escape_string( $cn, $_REQUEST['mode']);
  $set_id    = mysqli_real_escape_string( $cn, $_REQUEST['set_id']);
  
 
  if ( $mode == 'delete_set')
    {
    // check eerst of deze set niet in gebruik is, in toekomstige datums!
    

    // verwijder nu de bestanden
    $dir_to_delete = '/home/magicshoot/domains/magicshoot.nl/public_html/api/stack/event/' . $event_id . '/sets/'. $set_id ;
    exec('rm -rf ' . $dir_to_delete);

    // verwijder uit de databse
    $sql	  = "DELETE FROM photobooth_set
        			    WHERE set_id = '" . $set_id . "' AND
                      event_id = '" . $event_id . "'";
		$result = mysqli_query( $cn , $sql );	
    }



  ?>
  <style>
  
  .set-container {
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


  .set-header {
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
    height: 85px;
    text-transform: uppercase;
  }
 
  .set-name {
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
    height: 22px;
    font-weight: normal;

  }

  .set-current {
    color:#f7d30f;
    font-size: 11px;
    display: block;
    height: 12px;
    font-weight: bold;
  }
  .set-image{
    display: block;
    margin-top: 20px;
    font-weight:  bold;
    margin-bottom: 0px;
    font-size: 18px;
    margin-bottom: 10px;
  }

  .set-strook-img {
    border: 1px solid #111;
  }
  .set-object {
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
        <li class="breadcrumb-item">Strip  Editor</li>
        
      <!-- Breadcrumb Menu-->
      </ol>
    </div>
  </header>
  <div class="c-body">
        <main class="c-main">
          <div class="container-fluid">
            <div class="fade-in">

          <?php 
          // get the current_id with this event_id 
          
          $sql = "SELECT set_id
                    FROM photobooth_set_current
                    WHERE event_id = '" . $_SESSION['event_id']. "'";
          $result = mysqli_query( $cn , $sql );
          $return	= mysqli_fetch_object ( $result );
          $set_id_current  = $return->set_id;
          
          
          ?>
       
         
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase"> Strip  Editor</h2>
                    </div>
                  <div class="card-body">
                    <a href="page_strip_editor.php?mode=new" class="btn btn-info">Nieuwe layout</a>
                    <!--<a href="https://stats.magicshoot.nl/strook_templates/<?php echo $_SESSION['event_id']; ?>.psd" class="btn btn-success">Download strook template (.psd)</a>-->
                  </div>
                  <div class="card-body">
                    <?php 
                    # twee tabellen die samen 1 layout met fotootjes vormen
                    # tabel 1.: strip_paper --> hier wordt het formaat beschreven, of het wel of niet door midden gesneden wordt en een staande of liggende layout is en hoeveel fotootjes erop staan)
                    # tabel 2.: strip_photo --> in deze tabel staat per opvolgende foto waar deze op het papier komt

                    $sql = "SELECT *
                              FROM strip_paper
                              
                          ORDER BY strip_paper_id";
                    
                    $result = mysqli_query( $cn , $sql );
                    while ( $return	= mysqli_fetch_object ( $result ) )
                      {
                      $strip_paper_id               = $return->strip_paper_id;
                      $strip_paper_name             = $return->strip_paper_name;
                      $strip_is_base_set             = $return->strip_is_base_set;
                      $strip_paper_width            = $return->strip_paper_width;
                      $strip_paper_height           = $return->strip_paper_height;
                      $strip_paper_second_cut       = $return->strip_paper_second_cut;
                      $strip_paper_number_of_photos = $return->strip_paper_number_of_photos;
                      $strip_background_filename    = $return->strip_background_filename;
                      $strip_foreground_filename    = $return->strip_foreground_filename;

                      
                      ?>
                      <div class="set-container"  style="background-color:<?php echo $set_color; ?>;">
                        <div class="set-header" >
                         
                            <?php echo $return->strip_paper_name; ?><br>
                            <div class="strip_header">
                                width x height: <?php echo $return->strip_paper_width . " x " . $return->strip_paper_height; ?><br>
                                number of photos: <?php echo $return->strip_paper_number_of_photos; ?></br>
                                second cut: <?php echo $return->strip_paper_second_cut; ?>
                            </div>
                         
                        </div>
                    
                        <div class="set-image">
                          <img class="set-strook-img" src='https://www.magicshoot.nl/stats/thumbnail.php?path=<?php echo $strip_background_filename; ?>&img=strook.png&width=200&height=200&maxw=200&maxh=200'>
                        </div>
                          <div class="button-container">
                            <?php 
                            if ( $strip_is_base_set == 0 ) {
                              ?><a href="page_strip_editor.php?mode=delete_set&set_id=<?php echo $set_id; ?>" class="btn btn-danger">Verwijder layout</a><?php
                            } else { 
                              echo "Basis strip, deze kan niet worden verwijdert."; 
                            } ?>
                          </div>
                        </div>
                    <?php } ?>

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