<?php
    require_once '/home/magicshoot/public_html/inc/connection.inc.php';
    global $cn;
    $strip_layout_id = mysqli_real_escape_string( $cn, $_REQUEST['strip_layout_id']);
    $path            = "/home/magicshoot/domains/magicshoot.nl/public_html/stats/strip_layouts/generated";
    $img             = "strip_layout_" . $strip_layout_id . ".jpg";
    $img_source      = $path . '/' . $img;
   
        // check of de img al bestaat
        if ( file_exists( $img_source ) )
            {
            // zo ja: toon de bestaande img
            $fp = fopen( $img_source, 'rb' );
            header( "Content-type: image/jpeg" );
            header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
            header( 'Cache-Control: no-store, no-cache, must-revalidate' );
            header( 'Cache-Control: post-check=0, pre-check=0', false );
            header( 'Pragma: no-cache' );
            header( "Content-Length: " . filesize( $img_source ));
            fpassthru( $fp );
            }
        else
            {
            // zo nee: maak de img opnieuw 	
            create_strip ( $strip_layout_id, $path );
            $fp = fopen( $img_source, 'rb' );
            header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
            header( 'Cache-Control: no-store, no-cache, must-revalidate' );
            header( 'Cache-Control: post-check=0, pre-check=0', false );
            header( 'Pragma: no-cache' );
            header( "Content-type: image/jpeg" );
            header( "Content-Length: " . filesize($img_source) );
            fpassthru( $fp );
            }
    
    


    function create_strip ( $strip_layout_id, $path )
        {
        global $cn; 
       
        $sql = "SELECT *
                FROM strip_layout
                WHERE strip_layout_id = '" . $strip_layout_id . "'";
        $result = mysqli_query( $cn , $sql );
        $return	= mysqli_fetch_object ( $result );
            
        
        $strip_layout_width            = $return->strip_layout_width;
        $strip_layout_height           = $return->strip_layout_height;
        $strip_layout_second_cut       = $return->strip_layout_second_cut;
        $strip_number_of_photos        = $return->strip_number_of_photos;
        //$strip_background_filename     = $return->strip_background_filename;
        //$strip_foreground_filename     = $return->strip_foreground_filename;
        $strip_margin                  = $return->strip_margin;
            
        if ( $strip_layout_width > $strip_layout_height ) { 
            # landscape
            $background_file = 'strip_layouts/base_strip_background_landscape.png';
            $foreground_file = 'strip_layouts/base_strip_foreground_landscape.png';
            }
        else {
            # portrait
            $background_file = 'strip_layouts/base_strip_background.png';
            $foreground_file = 'strip_layouts/base_strip_foreground.png';
        }    
        
        $background_img = imagecreatefrompng( $background_file );
        # zorg dat er transparante plaatjes op de background geplakt kunnen worden
        ImageAlphaBlending ( $background_img , true);

        $foreground_img = imagecreatefrompng( $foreground_file );
        ImageAlphaBlending ( $foreground_img , true);
        
        # maak voorbeeldplaatjes aan
        for ( $p = 1; $p <= $strip_number_of_photos; $p ++) {
            $example_file[ $p ]  = 'strip_layouts/example_images/voorbeeld_foto_' . $p . '.jpg';
            ImageAlphaBlending ( $foreground_img, true );
        }

        # maak photo_number plaatjes aan
        for ( $p = 1; $p <= $strip_number_of_photos; $p ++) {
            $photo_number_file[ $p ]  = 'strip_layouts/photo_number_' . $p . '.png';
            $photo_number_img[$p]   = imagecreatefrompng( $photo_number_file[$p] );
            ImageAlphaBlending ( $photo_number_img[ $p ], true );
        }
    
        # haal uit de database op waar de photos in de layout komen te staan
        $sql = "SELECT *
                FROM strip_photo
                WHERE strip_layout_id = '" . $strip_layout_id . "'
                ORDER BY strip_photo_number";

        $result = mysqli_query( $cn , $sql );
        $photo_counter = 1;
        while ( $return	= mysqli_fetch_object ( $result ) )
        {
            $strip_photo_number            = $return->strip_photo_number;
            $example_img[$photo_counter]   = imagecreatefromjpeg( $example_file[$strip_photo_number] );
            ImageAlphaBlending ( $example_img[$photo_counter], true );   
            $strip_photo_width             = $return->strip_photo_width;
            $strip_photo_height            = $return->strip_photo_height;
            $strip_photo_top_left_x        = $return->strip_photo_top_left_x;
            $strip_photo_top_left_y        = $return->strip_photo_top_left_y;
            $strip_photo_center_rotation   = $return->strip_photo_center_rotation;

            # voorbeeldje erop  plakken
            ImageCopy($background_img, $example_img[$photo_counter], $strip_photo_top_left_x, $strip_photo_top_left_y, 0,0, $strip_photo_width, $strip_photo_height);

            # nummer erop plakken
            ImageCopy($background_img, $photo_number_img[ $strip_photo_number ], $strip_photo_top_left_x + 40, $strip_photo_top_left_y + 40, 0,0, 200, 200);

            $photo_counter++;
        }
        
        

        # plak de voorgrond op de achtergrond  
        ImageCopy($background_img, $foreground_img, 0, 0, 0, 0, $strip_layout_width, $strip_layout_height);

        
        # als er een second cut is, plak dan de stippellijn file eroverheen
        if ( $strip_layout_second_cut == 1 )
        {
        if ( $strip_layout_width > $strip_layout_height ) {
            # landscape
            $stippellijn_file = 'strip_layouts/base_strip_stippellijn_landscape.png';
        } else {
            # portrait
            $stippellijn_file = 'strip_layouts/base_strip_stippellijn.png';
        }
        $stippellijn_img = imagecreatefrompng( $stippellijn_file );
        ImageCopy($background_img, $stippellijn_img, 0, 0, 0, 0, $strip_layout_width, $strip_layout_height);
        ImageAlphaBlending ( $stippellijn_img   , true );
        }

        # margins
        if ( $strip_layout_width > $strip_layout_height ) {
            # landscape
            $margins_file = 'strip_layouts/base_strip_margins_landscape.png';
        } else {
            # portrait
            $margins_file = 'strip_layouts/base_strip_margins.png';
        }
        $margins_img = imagecreatefrompng( $margins_file );
        ImageCopy($background_img, $margins_img, 0, 0, 0, 0, $strip_layout_width, $strip_layout_height);
        
        # save the image to the server
        $img_filename = $path . '/strip_layout_' . $strip_layout_id . '.jpg';
        imagejpeg( $background_img , $img_filename);
        chmod( $img_filename, 0775);
        # destroy
       // imagedestroy( $foreground_img );  
       // imagedestroy( $margins_img );  
       // if ( isset($stippellijn_img)) { imagedestroy( $stippellijn_img ); }  
      //  for ( $p = 1; $p <= $strip_number_of_photos; $p ++) {
       //     imagedestroy( $example_img[ $p ] );  
       //     }
       // for ( $p = 1; $p <= $strip_number_of_photos; $p ++) {
       //     imagedestroy( $photo_number_img[ $p ] );  
       //     }  
       // imagedestroy( $background_img );  
      }
     
?>  