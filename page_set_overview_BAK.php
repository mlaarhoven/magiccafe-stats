  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");
  $event_id  = $_SESSION['event_id'];
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }
  $basepath  = "/home/magicshoot/domains/magicshoot.nl/public_html/api/stack";
	$path 	   = $basepath . '/event/' . $event_id .'/';
  
  if ( isset ( $_REQUEST['mode'] ) )   { $mode = mysqli_real_escape_string( $cn, $_REQUEST['mode']);} else { $mode = ""; }
  if ( isset ( $_REQUEST['set_id'] ) ) { $set_id = mysqli_real_escape_string( $cn, $_REQUEST['set_id']);} else { $set_id = ""; }
 
  if ( $mode == "set_as_base"){
    # haal de prijzen op van de huidige basis set
    $sql = "SELECT set_price_print, set_price_qr
              FROM photobooth_set
             WHERE event_id = '" . $_SESSION['event_id'] . "'";
    $result             = mysqli_query( $cn , $sql );
    $return             = mysqli_fetch_object ( $result );
    $set_price_print    = $return->set_price_print;
    $set_price_qr       = $return->set_price_qr;

    # set de oude base set op 0
    $sql	  = "UPDATE photobooth_set
                  SET is_base_set   = '0'
                WHERE is_base_set   = '1' 
                  AND event_id      = '" . $_SESSION['event_id'] . "'";
    $result = mysqli_query( $cn , $sql );	

    # set de nieuwe set is_base_set op 1 en zet de prijs op de basis prijs van de vorige basisset
    $sql	  = "UPDATE photobooth_set
                  SET is_base_set     = '1',
                      set_price_print = '" . $set_price_print       . "',
                      set_price_qr    = '" . $set_price_qr          . "'
                WHERE set_id          = '" . $set_id                . "' 
                  AND event_id        = '" . $_SESSION['event_id']  . "'";
    $result = mysqli_query( $cn , $sql );	
  
  }
  
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

    if ( $mode == 'disable_set')
    {
      $sql	  = "UPDATE photobooth_set
                    SET set_enabled = '0'
                  WHERE set_id   = '" . $set_id   . "'
                    AND event_id = '" . $_SESSION['event_id'] . "'";
      $result = mysqli_query( $cn , $sql );
      //echo $sql;
    }

  if ( $mode == 'generate_gifs' && $set_id != '' )
    {
      $eid = (int) $event_id;
      $sid = (int) $set_id;
      mysqli_query($cn, "DELETE FROM photobooth_gif_generator
                          WHERE event_id = {$eid} AND set_id = {$sid} AND status = 'created'");
      $animations = ['printen', 'startscherm', 'startscherm_qr', 'betalen', 'betalen_qr', 'pak_de_strookjes'];
      $stmt = mysqli_prepare($cn,
          "INSERT INTO photobooth_gif_generator (event_id, set_id, animation_name, status, created)
           VALUES (?, ?, ?, 'created', NOW())");
      foreach ($animations as $anim) {
          mysqli_stmt_bind_param($stmt, 'iis', $eid, $sid, $anim);
          mysqli_stmt_execute($stmt);
      }
      mysqli_stmt_close($stmt);
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
    margin-bottom: 10px;
    padding-bottom: 120px;
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
    height: 65px;
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

  .set-created-date {
    color:#fff;
    font-size: 9px;
    display: block;
    height: 12px;
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
    height:  175px;
    font-size: 11px;
  }
</style>
    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Set overzicht</li>
        
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
          $set_id_current = get_planner_set_id ( $event_id );

          if ( $set_id_current  == '')
                {
                 $set_id_current = get_base_set_id ( $event_id );
                }

          #---------functions ----------#
          function get_planner_set_id ( $event_id ) 
            {
              global $cn;   
              $current_date   = date("Y-m-d");
              $current_time   = date("H") . ":00:00";
              $sql = "SELECT * FROM `photobooth_set_planner` WHERE NOW() BETWEEN start_date_time AND end_date_time AND event_id = $event_id;";
              
              $result          = mysqli_query( $cn , $sql );
              $return	        = mysqli_fetch_object ( $result );
              if ( $return ) {
                $planner_set_id  = $return->set_id;
                return $planner_set_id;
              } else { 
                return $planner_set_id = "";
              }
            }

          function get_base_set_id ( $event_id ) 
            {
              global $cn;
              $sql         ="SELECT set_id
                                FROM photobooth_set
                              WHERE event_id = '" . $event_id . "'
                                AND is_base_set ='1'";
              $result      = mysqli_query( $cn , $sql );
              $return	    = mysqli_fetch_object ( $result );
              $base_set_id = $return->set_id;
              return $base_set_id;
            }

            function is_set_used ( $set_id )
            {
              global $cn;
              $sql        = "SELECT COUNT(*) AS aantal
                               FROM log_session
                              WHERE event_id = '" . $_SESSION['event_id'] . "'
                                AND set_id   = '" . $set_id   . "'";
              $result     = mysqli_query( $cn , $sql );
              $return	    = mysqli_fetch_object ( $result );
              //echo $sql . "<br>";
              $aantal     = $return->aantal;
              if ($aantal > 0 ) { 
                $return = 1;
              } 
              else { 
                $return = 0; 
              }
              return $return;
            }
          #-----------------------------#
          ?>
       
         
            <!-- /.row-->
            <div class="row">
              <div class="col-sm-12 col-md-12 col-lg-12">
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase"> Set overzicht</h2>
                    </div>
                  <div class="card-body">
                    <p>Let op: als je op de knop drukt: "Gebruik deze set als basisset" dan wordt de prijs overgenomen van de huidige basisset.</p>
                    <a href="page_set_detail.php?mode=new" class="btn btn-info">Nieuwe set aanmaken</a>
                    <a href="https://stats.magicshoot.nl/strook_templates/<?php echo $_SESSION['event_id']; ?>.psd" class="btn btn-info">Download strook template (.psd)</a>
                    <a href="page_set_uitleg.php" class="btn btn-info">Uitleg</a>
                    
                  </div>
                  <div class="card-body">
                    <?php 
                    $sql = "SELECT *
                              FROM photobooth_set
                              WHERE event_id = '" . $_SESSION['event_id']. "'
                              AND set_enabled = '1'
                          ORDER BY set_id ASC";
                    
                    $result = mysqli_query( $cn , $sql );
                    while ( $return	= mysqli_fetch_object ( $result ) )
                      {
                      $set_id             = $return->set_id;
                      $set_name           = $return->set_name;
                      $set_price_print    = $return->set_price_print;
                      $set_price_qr       = $return->set_price_qr;
                      $strip_layout_id    = $return->strip_layout_id;
                      $set_color          = $return->set_color;
                      $is_base_set        = $return->is_base_set;
                      $date_created       = $return->date_created;
                      $set_path           = $path . 'sets/' . $set_id . '/'; 
                      $resource_path      = $set_path . 'resources/'; 
                      $strook_background  = $resource_path . 'strook.png';
                      ?>
                      <div class="set-container"  style="background-color:<?php echo $set_color; ?>;">
                        <div class="set-header" >
                          <div>
                            <?php echo $return->set_name; ?> - ID: <?php echo $set_id; ?><br>
                            <div class="set-created-date">Created: <?php echo $return->date_created; ?></div>
                            <?php
                            if ( $set_id == $set_id_current )
                            {
                             ?><div class="set-current">DEZE SET IS NU ACTIEF</div><?php 
                            }
                            
                            ?>
                          </div>
                        </div>
                    
                        <div class="set-image">
                          <a href="page_set_detail.php?set_id=<?php echo $set_id; ?>">
                          <img class="set-strook-img" src='https://www.magicshoot.nl/stats/thumbnail.php?path=<?php echo $resource_path; ?>&img=strook.png&event_id=<?php echo $event_id; ?>&width=200&height=200&maxw=200&maxh=200'>
                        </a>
                        <br><br><a href="page_set_detail.php?set_id=<?php echo $set_id; ?>" class="btn btn-info">Edit set</a><br>
                          
                        </div>
                          <div class="button-container">
                            <?php
                            echo "<br>Prijs print: &euro; " . $set_price_print / 100 . " <br>";
                            echo "Prijs QR: &euro; " . $set_price_qr / 100 . " <br>";
                            echo "<a href='https://stats.magicshoot.nl/page_strip_layout_overview.php'>Layout ID:" . $strip_layout_id . "</a><br>";

                            
                            if ( $is_base_set == 1 ) {
                              echo "<br>Basisset, deze kan niet worden verwijdert.<br>"; 
                            } else  {
                              # als set in gebruik is of in gebruik is geweest kan deez niet verwijdert, alleen disabled
                              if ( $set_id == $set_id_current )
                                {
                                  echo "Set is in gebruik, kan niet worden verwijderd.";
                                }
                                if ( is_set_used ($set_id ) == 0 )  
                                 { ?>
                                 <br><br><a href="page_set_overview.php?mode=delete_set&set_id=<?php echo $set_id; ?>" class="btn btn-danger">Verwijder set</a><br><?php
                                 } else {
                                  echo "<br>Set is gebruikt, kan niet worden verwijderd, maar wel uitgezet worden, zodat hij uit het overzicht verdwijnt.";
                                  ?><br><br><a href="page_set_overview.php?mode=disable_set&set_id=<?php echo $set_id; ?>" class="btn btn-danger">Verberg set in overzicht</a><br><?php
                                 }
                            }

                            # toon set as base set knop tonen, indien dit geen base set is
                            if ( $is_base_set == 0 ) {
                              ?><br><a href="page_set_overview.php?mode=set_as_base&set_id=<?php echo $set_id; ?>" class="btn btn-danger">Gebruik deze set als basis set</a><br><?php
                            }
                            ?><br><a href="page_set_overview.php?mode=generate_gifs&set_id=<?php echo $set_id; ?>" class="btn btn-warning">Genereer gif animaties</a><br><?php

                            ?>
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