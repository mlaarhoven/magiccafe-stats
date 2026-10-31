  <?php 
  include ("partial_header.php");
  include ("partial_nav.php"); 
  include ("inc/invul_functions.php");

  $event_id = $_SESSION['event_id'];
  # check role
  if ( ! ( $_SESSION['client_role'] == 'admin' || $_SESSION['client_role'] == 'owner' ) ) {
    header('Location: https://stats.magicshoot.nl/admin/index.php');
    exit;
  }
  $set_id   = mysqli_real_escape_string( $cn, $_REQUEST['set_id']);
  $mode     = mysqli_real_escape_string( $cn, $_REQUEST['mode']);

  // standaard UI-kleuren: gebruikt als een veld leeg is (nieuwe set, leeg opgeslagen, of geen photobooth_ui rij)
  $ui_rgb_defaults = array(
    'ui_circle_border_rgb'      => '0,162,232',
    'ui_circle_fill_rgb'        => '0,0,0',
    'ui_background_rgb'         => '0,0,0',
    'ui_pricecircle_fill_rgb'   => '147,112,219',
    'ui_pricecircle_border_rgb' => '255,255,255',
    'ui_price_text_rgb'         => '255,255,255',
  );

  if ( $mode == "save_set_name" )
  {
    $set_name             = mysqli_real_escape_string( $cn, $_REQUEST['set_name']);
    $set_price_print      = mysqli_real_escape_string( $cn, $_REQUEST['set_price_print']);
    $set_price_qr         = mysqli_real_escape_string( $cn, $_REQUEST['set_price_qr']);
    $strip_layout_id      = mysqli_real_escape_string( $cn, $_REQUEST['strip_layout_id']);

    // kleurvelden staan alleen in het formulier voor admins; niet meegestuurd = bestaande waarde laten staan
    $ui_set_sql = "";
    foreach ( $ui_rgb_defaults as $ui_field => $ui_default ) {
      if ( isset( $_REQUEST[$ui_field] ) ) {
        $ui_value    = trim( $_REQUEST[$ui_field] );
        $ui_set_sql .= ", " . $ui_field . " = '" . mysqli_real_escape_string( $cn, $ui_value !== '' ? $ui_value : $ui_default ) . "'";
      }
    }

    $sql = "UPDATE photobooth_set
               SET set_name                  = '" . $set_name                 . "',
                   set_price_print           = '" . $set_price_print          . "',
                   set_price_qr              = '" . $set_price_qr             . "',
                   strip_layout_id           = '" . $strip_layout_id          . "'"
                   . $ui_set_sql . "
             WHERE set_id = '" . $set_id . "'";
    $result      = mysqli_query( $cn , $sql );
    if ( $result ) {
      $set_name_opgeslagen = 1;
    }
  }

  if ( $mode == "new" )
  {
    // maak nieuwe set aan in de database
    $datetime = date ( "Y-m-d H:i:s" );
    $set_color = generate_color();
    


    // Fetch UI colours for this event to copy into the new set
    $ui_result = mysqli_query($cn, "SELECT circle_border_rgb, circle_fill_rgb, background_rgb, pricecircle_fill_rgb, pricecircle_border_rgb, price_text_rgb FROM photobooth_ui WHERE event_id = '" . $event_id . "' LIMIT 1");
    $ui_row    = $ui_result ? mysqli_fetch_assoc($ui_result) : [];
    $ui_circle_border_rgb      = mysqli_real_escape_string($cn, ($ui_row['circle_border_rgb']      ?? '') ?: $ui_rgb_defaults['ui_circle_border_rgb']);
    $ui_circle_fill_rgb        = mysqli_real_escape_string($cn, ($ui_row['circle_fill_rgb']        ?? '') ?: $ui_rgb_defaults['ui_circle_fill_rgb']);
    $ui_background_rgb         = mysqli_real_escape_string($cn, ($ui_row['background_rgb']         ?? '') ?: $ui_rgb_defaults['ui_background_rgb']);
    $ui_pricecircle_fill_rgb   = mysqli_real_escape_string($cn, ($ui_row['pricecircle_fill_rgb']   ?? '') ?: $ui_rgb_defaults['ui_pricecircle_fill_rgb']);
    $ui_pricecircle_border_rgb = mysqli_real_escape_string($cn, ($ui_row['pricecircle_border_rgb'] ?? '') ?: $ui_rgb_defaults['ui_pricecircle_border_rgb']);
    $ui_price_text_rgb         = mysqli_real_escape_string($cn, ($ui_row['price_text_rgb']         ?? '') ?: $ui_rgb_defaults['ui_price_text_rgb']);

    $sql	  = "INSERT INTO photobooth_set
        			    SET set_name              = 'Nieuwe set',
                      set_price_print         = '500',
                      set_price_qr            = '250',
                      strip_layout_id         = '1',
                      set_color               = '$set_color',
                      date_created            = '$datetime',
                      event_id                = '" . $event_id . "',
                      ui_circle_border_rgb      = '$ui_circle_border_rgb',
                      ui_circle_fill_rgb        = '$ui_circle_fill_rgb',
                      ui_background_rgb         = '$ui_background_rgb',
                      ui_pricecircle_fill_rgb   = '$ui_pricecircle_fill_rgb',
                      ui_pricecircle_border_rgb = '$ui_pricecircle_border_rgb',
                      ui_price_text_rgb         = '$ui_price_text_rgb'";
		$result = mysqli_query( $cn , $sql );
    $set_id = mysqli_insert_id ( $cn );
 
    // daarna worden de paden aangemaakt  en daarna gaan we de basis set kopieren hiernaartoe
    
  }

  #paden
	$basepath 		 		      = "/home/magicshoot/domains/magicshoot.nl/public_html/api/stack";
	$path 	  		 		      = $basepath . '/event/' . $event_id . '/sets/' . $set_id . "/";
	$background_path 		    = $path 	. 'backgrounds';
	$foreground_path 		    = $path 	. 'foregrounds';
	$resource_path 		      = $path 	. 'resources';
	$background_thumbs_path = $path 	. 'backgrounds/thumbs';
	$foreground_thumbs_path = $path 	. 'foregrounds/thumbs';
	$resource_thumbs_path   = $path 	. 'resources/thumbs';

# paden checken en indien nodig aanmaken
if ( !is_dir( $path ))
{
  mkdir ( $path );
  chmod ( $path, 0777 );	
}	
# foregrounds paths
if ( !is_dir( $foreground_path ))
{
  mkdir ( $foreground_path  );
  chmod ( $foreground_path, 0777 );	
}
if ( !is_dir( $foreground_thumbs_path ))
{
  mkdir ( $foreground_thumbs_path  );
  chmod ( $foreground_thumbs_path, 0777 );	
}
# background paths
if ( !is_dir( $background_path ))
{
  mkdir ( $background_path  );
  chmod ( $background_path, 0777 );	
}
if ( !is_dir( $background_thumbs_path ))
{
  mkdir ( $background_thumbs_path  );
  chmod ( $background_thumbs_path, 0777 );	
}
# resource paths
if ( !is_dir( $resource_path ))
{
  mkdir ( $resource_path  );
  chmod ( $resource_path, 0777 );	
}
if ( !is_dir( $resource_thumbs_path ))
{
  mkdir ( $resource_thumbs_path  );
  chmod ( $resource_thumbs_path, 0777 );	
}

if ( $mode == "new" )
{
  // haal de basis_set_id op
  $sql = "SELECT set_id
            FROM photobooth_set
           WHERE event_id = '" . $_SESSION['event_id'] . "'
             AND is_base_set ='1'";
  //echo $sql . "<br>";           
  $result      = mysqli_query( $cn , $sql );
  $return	     = mysqli_fetch_object ( $result );
  $base_set_id = $return->set_id;
 

  // maak FROM en TO basis paths
  $from_path 	  		 		        = $basepath . '/event/' . $event_id . '/sets/' . $base_set_id . "/";
	$from_background_path 		    = $from_path 	. 'backgrounds';
	$from_foreground_path 		    = $from_path 	. 'foregrounds';
	$from_resource_path 		      = $from_path 	. 'resources';

  $to_path 	  		 		        = $basepath . '/event/' . $event_id . '/sets/' . $set_id . "/";
	$to_background_path 		    = $to_path 	. 'backgrounds';
	$to_foreground_path 		    = $to_path 	. 'foregrounds';
	$to_resource_path 		      = $to_path 	. 'resources';
	
  // ACHTERGROND PLAATJE
  $from_url = $from_background_path . "/1600x1200.jpg";
  $to_url   = $to_background_path . "/1600x1200.jpg";
  $result_copy = copy( $from_url, $to_url);
  echo "result copieren achtergrond plaatje = " . $result_copy . "<br>"; 
  
  // STROKEN
  $from_url_2 = $from_resource_path . "/strook.png";
  $to_url_2   = $to_resource_path . "/strook.png";
  $result_copy_2 = copy( $from_url_2, $to_url_2);
  //echo "result copieren strook plaatje = " . $result_copy . "<br>";

  $from_url_3 = $from_resource_path . "/strook_foreground.png";
  $to_url_3   = $to_resource_path . "/strook_foreground.png";
  $result_copy_3 = copy( $from_url_3, $to_url_3);
  //echo "result copieren strook_foreground plaatje = " . $result_copy . "<br>";

  // ANIMATIES --> deze copieren we niet, we gebruiken in principe de animaties uit de Basis set map, tenzij iemand zelf andere upload
  

}
function generate_color()
{
  $baseColors = [
    1 => 'r',
    2 => 'g',
    3 => 'b'
    ];
    $colorMap = [];
    $minValue = 155;
    $maxValue = 200;

    $primaryColorIndex = rand(1, 3);

    $primaryColor = $baseColors[$primaryColorIndex];
    unset($baseColors[$primaryColorIndex]);

    $colorMap[$primaryColor] = 255;

    foreach($baseColors as $baseColor) {
        $colorMap[$baseColor] = rand($minValue, $maxValue);
    }

    krsort($colorMap);

    $rgbColor = [];
    foreach($colorMap as $key => $value) {
        $rgbColor[$key] = $value;
    }

    $color = sprintf('#%02x%02x%02x', $rgbColor['r'], $rgbColor['g'], $rgbColor['b']);
return $color;
}

$background_array = array_diff( scandir ( $background_path, 0 ) , array('..', '.', 'thumbs'));
$foreground_array = array_diff( scandir ( $foreground_path, 0 ) , array('..', '.', 'thumbs'));
$resource_array   = array_diff( scandir ( $resource_path, 0 ) , array('..', '.', 'thumbs'));
?>
<script
  src="https://code.jquery.com/jquery-3.7.1.min.js"
  integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
  crossorigin="anonymous"></script>
			
<script src="dropzone/dropzone.js"></script>

<link rel="stylesheet" type="text/css" href="dropzone/dropzone.css"/>

<style>
		.image_icon{
			width: 64px;
			margin-top: 5px;
		}
		.image_icon_small{
			width: 24px;
			margin-top: 10px;
			margin-right: 20px;
		}
		.last{
			margin-right:0 !important;	
		}
		.image_manager {
			display: block;
			float: none;
			clear:both;
			overflow:hidden;
		}	
		.two_image_container{
			display: block;
			border: 1px solid #999;
			border-radius: 4px;
			padding: 5px;
			float: left;
			background-color: #eee;
			margin-left: 10px;
			margin-bottom: 20px;
		}
		.img_filename {
			font-size: 9px;
			margin-top: 5px;
			margin-bottom: 5px;
			display: block;
			width: 180px;
			overflow: none;
		}
		.missing {
			color: #ff0000;
			font-weight: bold;
			font-size: 12px;
			margin-top:60px;
			line-height: 26px;
		}
		.drop_text_fore {
			
			color: #dd44ee;
		}
		.drop_text_back {
			
			color: #3344ff;
		}
		.image_container{
			display:block;
			border:1px solid #aaa;
			border-radius: 4px;
			padding: 5px;
			width: 200px;
			height: 230px;
			background-color: #ddd;
			
			text-align: center;
			margin: 5px;
			margin-bottom: 10px;
		} 	


    .image_container_high{
			display:block;
			border:1px solid #aaa;
			border-radius: 4px;
			padding: 5px;
			width: 200px;
			height: 280px;
			background-color: #ddd;
			
			text-align: center;
			margin: 5px;
			margin-bottom: 10px;
		} 	

		.image_container_imagemanager_high{
			display:block;
			padding: 10px;
      padding-top:75px;
			width: 124px;
			height: 285px;
			text-align: center;
			margin-bottom: 10px;
			font-size:10px;
  	} 	

    .image_container_imagemanager{
			display:block;
			padding: 10px;
      padding-top:55px;    
			width: 124px;
			height: 233px;
			text-align: center;
			margin-bottom: 10px;
			font-size:10px;

		} 	
		.image_icon{
			width:32px;
		}
		.imagemanager_title
		{
			display: block;
			margin-top: 10px;
			margin-bottom: 10px;
			font-weight: bold;
		}
	</style>
  <script>
   function updateSwatch(inputId, swatchId) {
       var val = document.getElementById(inputId).value.trim();
       var parts = val.split(',');
       if (parts.length === 3) {
           var r = parseInt(parts[0]), g = parseInt(parts[1]), b = parseInt(parts[2]);
           if (!isNaN(r) && !isNaN(g) && !isNaN(b)) {
               document.getElementById(swatchId).style.backgroundColor = 'rgb(' + r + ',' + g + ',' + b + ')';
           }
       }
   }

   function deleteImageFile(  folder, bestandsnaam, event_id, set_id ) 
        {
        $.post( "event_delete_image.php",  { folder:folder , bestandsnaam: bestandsnaam, event_id:event_id, set_id:set_id }, function( data ) 
          {
          var obj = jQuery.parseJSON( data );
          });
        //refresh page
        window.open("page_set_detail.php?set_id=" + set_id,"_top")
        }

  </script>   		
 <?php 
            $sql = "SELECT *
			                FROM photobooth_set
		                 WHERE set_id = '" . $set_id . "'";
			      $result           = mysqli_query( $cn , $sql );
	          $return	          = mysqli_fetch_object ( $result );
	          $set_name              = $return->set_name;
            $set_price_print       = $return->set_price_print;
            $set_price_qr          = $return->set_price_qr;
            $strip_layout_id       = $return->strip_layout_id;
            $is_base_set           = $return->is_base_set;
            $ui_circle_border_rgb      = $return->ui_circle_border_rgb      ?: $ui_rgb_defaults['ui_circle_border_rgb'];
            $ui_circle_fill_rgb        = $return->ui_circle_fill_rgb        ?: $ui_rgb_defaults['ui_circle_fill_rgb'];
            $ui_background_rgb         = $return->ui_background_rgb         ?: $ui_rgb_defaults['ui_background_rgb'];
            $ui_pricecircle_fill_rgb   = $return->ui_pricecircle_fill_rgb   ?: $ui_rgb_defaults['ui_pricecircle_fill_rgb'];
            $ui_pricecircle_border_rgb = $return->ui_pricecircle_border_rgb ?: $ui_rgb_defaults['ui_pricecircle_border_rgb'];
            $ui_price_text_rgb         = $return->ui_price_text_rgb         ?: $ui_rgb_defaults['ui_price_text_rgb'];

            ?>

    <div class="c-subheader px-3">
      <!-- Breadcrumb-->
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo get_event_name ( $_SESSION['event_id']); ?></strong></li>
        <li class="breadcrumb-item">Set detail - <?php echo $set_name; ?></li>
        
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
                <div class="card"> 
                  <div class="card-header bg-facebook content-center">
                      <h2 class="text-white text-uppercase">Set detail  - <?php echo $set_name; ?></h2>
                    </div>

  
                  <div class="card-body">
                      <div class="card-title"><h3>SET NAAM</h3></div>
                      <?php if ( $set_name_opgeslagen == 1 ) {
                        ?>
                        <div class="alert alert-success" role="alert">
                          De set naam is opgeslagen.
                        </div>
                        <?php
                      } ?>

                      <?php if ( $is_base_set == 1 )
                        {
                        ?>
                        Naam van de set: <strong><?php echo $set_name; ?></strong><br><br>
                        Dit is de basis set, deze kan niet worden aangepast.<br>
                        <?php    
                         echo "Prijs print: " . $set_price_print . " eurocent<br>";
                         echo "Prijs QR: " . $set_price_qr . " eurocent<br>";
                         echo "Layout ID: <a href='https://stats.magicshoot.nl/page_strip_layout_overview.php'>" . $strip_layout_id . "</a><br>"; 
                        } else { ?>
                     
                      <form action="page_set_detail.php" action="post">
                        <input type="hidden" name="set_id" value="<?php echo $set_id; ?>">
                        <input type="hidden" name="mode" value="save_set_name">
                        <input type="hidden" name="strip_layout_id" value="1"><!-- tijdelijk! -->
                        <table>
                          <tr>
                            <td>Naam van de set</td><td><input type="text" name="set_name" id="set_name" value="<?php echo $set_name; ?>"></td>
                          </tr>
                          <tr>
                            <td>Prijs van een print</td><td><input type="text" name="set_price_print" id="set_price_print" value="<?php echo $set_price_print; ?>"> eurocent</td>
                          </tr>
                          <tr>
                            <td>Prijs van QR-only</td><td><input type="text" name="set_price_qr" id="set_price_qr" value="<?php echo $set_price_qr; ?>"> eurocent</td>
                          </tr>  
                          <tr>
                            <td>Strip layout ID</td><td>
                            <?php echo $strip_layout_id; ?> (vooralsnog hebben we maar 1 striplayout, nummer:1)
                              <!--<input type="text" name="strip_layout_id" id="strip_layout_id" value="<?php echo $strip_layout_id; ?>">-->
                            </td>
                        </tr>
                        <tr>
                          <td colspan="2"><hr><strong>Photobooth user interface (UI) kleuren</strong> (formaat: R,G,B - in principe NIET aanpassen!)</td>
                        </tr>
                        <tr>
                          <td>Cirkel rand kleur</td>
                          <td>
                             <?php if ( $_SESSION['client_role'] == 'admin' ) { ?><input type="text" name="ui_circle_border_rgb" id="ui_circle_border_rgb" value="<?php echo htmlspecialchars($ui_circle_border_rgb); ?>" placeholder="bijv. 0,162,232" oninput="updateSwatch('ui_circle_border_rgb','swatch_circle_border')"><?php } ?>
                            <span id="swatch_circle_border" style="display:inline-block;width:24px;height:24px;border:1px solid #aaa;vertical-align:middle;margin-left:8px;background-color:rgb(<?php echo htmlspecialchars($ui_circle_border_rgb ?: '0,162,232'); ?>)"></span>
                          </td>
                        </tr>
                        <tr>
                          <td>Cirkel vulling kleur</td>
                          <td>
                             <?php if ( $_SESSION['client_role'] == 'admin' ) { ?><input type="text" name="ui_circle_fill_rgb" id="ui_circle_fill_rgb" value="<?php echo htmlspecialchars($ui_circle_fill_rgb); ?>" placeholder="bijv. 0,0,0" oninput="updateSwatch('ui_circle_fill_rgb','swatch_circle_fill')"><?php } ?>
                            <span id="swatch_circle_fill" style="display:inline-block;width:24px;height:24px;border:1px solid #aaa;vertical-align:middle;margin-left:8px;background-color:rgb(<?php echo htmlspecialchars($ui_circle_fill_rgb ?: '0,0,0'); ?>)"></span>
                          </td>
                        </tr>
                        <tr>
                          <td>Achtergrond kleur</td>
                          <td>
                             <?php if ( $_SESSION['client_role'] == 'admin' ) { ?><input type="text" name="ui_background_rgb" id="ui_background_rgb" value="<?php echo htmlspecialchars($ui_background_rgb); ?>" placeholder="bijv. 0,0,0" oninput="updateSwatch('ui_background_rgb','swatch_background')"><?php } ?>     
                            <span id="swatch_background" style="display:inline-block;width:24px;height:24px;border:1px solid #aaa;vertical-align:middle;margin-left:8px;background-color:rgb(<?php echo htmlspecialchars($ui_background_rgb ?: '0,0,0'); ?>)"></span>
                          </td>
                        </tr>
                        <tr>
                          <td>Prijscirkel vulling kleur</td>
                          <td>
                             <?php if ( $_SESSION['client_role'] == 'admin' ) { ?><input type="text" name="ui_pricecircle_fill_rgb" id="ui_pricecircle_fill_rgb" value="<?php echo htmlspecialchars($ui_pricecircle_fill_rgb); ?>" placeholder="bijv. 147,112,219" oninput="updateSwatch('ui_pricecircle_fill_rgb','swatch_pricecircle_fill')"><?php } ?> 
                            <span id="swatch_pricecircle_fill" style="display:inline-block;width:24px;height:24px;border:1px solid #aaa;vertical-align:middle;margin-left:8px;background-color:rgb(<?php echo htmlspecialchars($ui_pricecircle_fill_rgb ?: '147,112,219'); ?>)"></span>
                          </td>
                        </tr>
                        <tr>
                          <td>Prijscirkel rand kleur</td>
                          <td>
                            <?php if ( $_SESSION['client_role'] == 'admin' ) { ?><input type="text" name="ui_pricecircle_border_rgb" id="ui_pricecircle_border_rgb" value="<?php echo htmlspecialchars($ui_pricecircle_border_rgb); ?>" placeholder="bijv. 255,255,255" oninput="updateSwatch('ui_pricecircle_border_rgb','swatch_pricecircle_border')"><?php } ?>  
                            <span id="swatch_pricecircle_border" style="display:inline-block;width:24px;height:24px;border:1px solid #aaa;vertical-align:middle;margin-left:8px;background-color:rgb(<?php echo htmlspecialchars($ui_pricecircle_border_rgb ?: '255,255,255'); ?>)"></span>
                          </td>
                        </tr>
                        <tr>
                          <td>Prijs tekst kleur</td>
                          <td>
                            <?php if ( $_SESSION['client_role'] == 'admin' ) { ?><input type="text" name="ui_price_text_rgb" id="ui_price_text_rgb" value="<?php echo htmlspecialchars($ui_price_text_rgb); ?>" placeholder="bijv. 255,255,255" oninput="updateSwatch('ui_price_text_rgb','swatch_price_text')"><?php } ?>  
                            <span id="swatch_price_text" style="display:inline-block;width:24px;height:24px;border:1px solid #aaa;vertical-align:middle;margin-left:8px;background-color:rgb(<?php echo htmlspecialchars($ui_price_text_rgb ?: '255,255,255'); ?>)"></span>
                          </td>
                        </tr>
                        <tr>
                          <td colspan="2"><button class="btn btn-info" >Opslaan</button></td>
                        </tr>
                        </table>
                      </form><br>
                      <p><strong>Sla de set eerst op voordat je stroken toevoegd!</strong></p>
                    <p>Opmerking: als je iets aan een set wijzigd, worden de animaties (opnieuw) gegenereerd, die op de fotokast getoond worden. <br>Dat genereren duurt ongeveer 7-20 minuten. <br>Deze nieuwe animaties zijn zichtbaar nadat je eenmaal op START geklikt hebt op de fotokast.</p>
                      <?php } ?>
                  </div>



                  <div class="card-body">
                    <div class="card-title"><h3>STROOK</h3></div>
                    <p>Lees eerst de <a href="page_set_uitleg.php">uitleg</a> voor je begint.</p>  
                    <p>Let op: strook_foreground.png wordt niet op het scherm getoond tijdens het maken van de foto's!<br>

                        Indien er iets in de strook_foreground staat, op de plek waar de foto komt, dan kan het zijn dat een persoon verdwijnt achter je vormgeving.</p>
                    <div class="image_manager">
                    <div class="two_image_container">
                      <div class="image_container_imagemanager_high">
                        <form action="event_image_uploader.php" class="dropzone" id="backgroundForm">
                          <input type="hidden" name="foldername" value="resources">
                          <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                          <input type="hidden" name="set_id" value="<?php echo $set_id; ?>">
                          <?php echo '<img src="assets/icons/image1.svg" class="image_icon">';?>	
                          <?php echo '<div class="drop_text_fore">Stroken</div>';?>		
                        </form>	
                      </div>
                    </div>
                   <?php
                    
                    foreach ( $resource_array as $key => $resource_img_with_ext )
                      {
                        $resource_zonder_ext 		= substr($resource_img_with_ext, 0, strrpos($resource_img_with_ext, '.'));
                        $resource_extension 		= substr($resource_img_with_ext, -3 );                        
                        if ( $resource_extension == 'png')
                          {
                          ?>
                          <div class="two_image_container">
                              <div class="image_container_high"><?php
                              echo '<div class="img_filename">' . $resource_img_with_ext . '</div>';
                              echo "<img src='https://www.magicshoot.nl/stats/thumbnail.php?path=" . $resource_path . "&img="  . $resource_img_with_ext . "&event_id=". $event_id . "&width=200&height=200&maxw=200&maxh=200'><br>";
                              echo '<a onClick="deleteImageFile(' . "'resources','" . $resource_img_with_ext ."'," .  $event_id . "," . $set_id . ')"><img src="assets/icons/trash.svg" class="image_icon_small last"></a>'; 
                              ?></div>	
                          </div><?php
                          } 
                      }
                    ?>  
                  </div>
                </div>
                
                <div class="card-body">
                  <div class="card-title"><h3>PLAATJES</h3></div>
                    <p>De voorgrond plaatjes zie je wel direct bij het maken van de foto's.</p>
                   <div class="image_manager">
                    <div class="two_image_container">

                      <div class="image_container_imagemanager">
                        <form action="event_image_uploader.php" class="dropzone" id="backgroundForm">
                          <input type="hidden" name="foldername" value="backgrounds">
                          <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                          <input type="hidden" name="set_id" value="<?php echo $set_id; ?>">
                          <?php echo '<img src="assets/icons/image1.svg" class="image_icon">';?>
                          <?php echo '<div class="drop_text_back">Achtergrond plaatjes</div>';?>
                        </form>		
                      </div>

                      <div class="image_container_imagemanager">
                        <form action="event_image_uploader.php" class="dropzone" id="foregroundForm">
                          <input type="hidden" name="foldername" value="foregrounds">
                          <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                          <input type="hidden" name="set_id" value="<?php echo $set_id; ?>">
                          <?php echo '<img src="assets/icons/image1.svg" class="image_icon">';?>	
                          <?php echo '<div class="drop_text_fore">Voorgrond plaatjes</div>';?>		
                        </form>	
                      </div>
                    </div>
                    <?php
                  foreach ( $background_array as $key => $background_img_with_ext )
                        {
                          $background_zonder_ext 		= substr($background_img_with_ext, 0, strrpos($background_img_with_ext, '.'));
                          $foreground_img 			    = $foreground_path . '/' . $background_zonder_ext . '.png';
                          $foreground_img_with_ext	= $background_zonder_ext . '.png';

                          ?>
                          <div class="two_image_container">
                            
                            <div class="image_container"><php
                              echo '<div class="img_filename">' . $background_img_with_ext . '</div>';
                              echo "<img src='https://www.magicshoot.nl/stats/thumbnail.php?path=" . $background_path . "&img="  . $background_img_with_ext . "&event_id=". $event_id . "&width=180&height=180&maxw=180&maxh=180'><br>";
                              echo '<a onClick="deleteImageFile(' . "'backgrounds','" . $background_img_with_ext ."'," .  $event_id . "," . $set_id . ')"><img src="assets/icons/trash.svg" class="image_icon_small last"></a>'; 
                            ?></div>	
                            <div class="image_container"><?php
                            if ( is_file ( $foreground_img ) )
                              {
                              echo "<img src='https://www.magicshoot.nl/stats/thumbnail.php?path=" . $foreground_path . "&img="  . $foreground_img_with_ext . "&event_id=". $event_id . "&width=180&height=180&maxw=180&maxh=180'>";
                              echo '<a onClick="deleteImageFile(' . "'foregrounds','" . $foreground_img_with_ext ."'," .  $event_id . "," . $set_id . ')"><img src="assets/icons/trash.svg" class="image_icon_small last"></a>'; 
                              }
                            else
                              {
                              echo '<div class="img_filename">Geen voorgrond geupload. </div>';	
                              
                              }
                            ?></div>
                            
                          </div><?php
                        }
                    ?>
                    </div>
                </div>
<!--
                <div class="card-body">
                  <div class="card-title"><h3>ANIMATIES</h3></div>
                  
                  <p>In principe gebruiken we de animaties uit de Basis set. Tenzij je echt de animaties wilt aanpassen aan de strookjes, dan kan je ze hier uploaden.</p> 
                  <div class="image_manager">
                    <div class="two_image_container">
                        <div class="image_container_imagemanager">
                          <form action="event_image_uploader.php" class="dropzone" id="animatieForm">
                            <input type="hidden" name="foldername" value="resources">
                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                            <input type="hidden" name="set_id" value="<?php echo $set_id; ?>">
                            <?php echo '<img src="assets/icons/image1.svg" class="image_icon">';?>	
                            <?php echo '<div class="drop_text_fore">Animaties</div>';?>		
                          </form>	
                        </div>
                      </div>
                   
                    <?php
                    foreach ( $resource_array as $key => $resource_img_with_ext )
                      {
                        $resource_zonder_ext 		= substr($resource_img_with_ext, 0, strrpos($resource_img_with_ext, '.'));
                        $resource_extension 		= substr($resource_img_with_ext, -3 );                        
                        if ( $resource_extension == 'gif')
                          {
                          ?>
                          <div class="two_image_container">
                            
                              <div class="image_container"><?php
                              echo '<div class="img_filename">' . $resource_img_with_ext . '</div>';
                              echo "<img src='https://www.magicshoot.nl/stats/thumbnail.php?path=" . $resource_path . "&img="  . $resource_img_with_ext . "&event_id=". $event_id . "&width=160&height=160&maxw=160&maxh=160'><br>";
                              echo '<a onClick="deleteImageFile(' . "'resources','" . $resource_img_with_ext ."'," .  $event_id . "," . $set_id . ')"><img src="assets/icons/trash.svg" class="image_icon_small last"></a>'; 
                              ?></div>	
                          </div><?php
                          } 
                      }
                    ?>
                    </div>
                  </div>
                    -->
            <script>
                Dropzone.options.strokenForm = {
                    maxFilesize: 40, // Mb
                    init: function () {
                        // Set up any event handlers
                        this.on('queuecomplete', function () {
                            location.reload();
                        });
                    }
                };

                Dropzone.options.backgroundForm = {
                    maxFilesize: 40, // Mb
                    init: function () {
                        // Set up any event handlers
                        this.on('queuecomplete', function () {
                            location.reload();
                        });
                    }
                };

                Dropzone.options.foregroundForm = {
                    maxFilesize: 40, // Mb
                    init: function () {
                        // Set up any event handlers
                        this.on('queuecomplete', function () {
                            location.reload();
                        });
                    }
                };

             /*   Dropzone.options.animatieForm = {
                    maxFilesize: 40, // Mb
                    init: function () {
                        // Set up any event handlers
                        this.on('queuecomplete', function () {
                            location.reload();
                        });
                    }
                };
                */
            </script>
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