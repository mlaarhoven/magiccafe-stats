<?php

# in: complete_image_filename bijv: /home/magicshoot/domains/magicshoot.nl/public_html/api/stack/event/360/backgrounds/[img_name.jpg]
# check of thumb bestaat, zo niet maak aan
# geef thumb terug als plaatje

if (isset($_GET['event_id']) ) {
    $event_id = $_GET['event_id'];
}
if (isset($_GET['img']) ) {
    $img = $_GET['img'];
}
if (isset($_GET['path']) ) {
    $path = $_GET['path'];
}
$img_source = $path . '/' . $img;
$img_thumb =  $path . "/thumbs/" . $img;


// check of de thumbnail al bestaat
if ( file_exists( $img_thumb ) )
	{
	// zo ja: check of de datum van de thumb ouder is dan de datum van het origineel
	if ( filemtime( $img_thumb ) > filemtime( $img_source ) )
		{
		// zo ja: toon de bestaande thumb 
		$fp = fopen( $img_thumb, 'rb' );
		header( "Content-type: image/jpeg" );
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
		header( "Content-Length: " . filesize( $img_thumb ));
		fpassthru( $fp );
		}
	else
		{
		// zo nee: maak de thumb opnieuw 	
		create_thumbnail ( $img_source, $img_thumb );
		$fp = fopen( $img_thumb, 'rb' );
		header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );
		header( "Content-type: image/jpeg" );
		header( "Content-Length: " . filesize($img_thumb) );
		fpassthru( $fp );
		}
	}
else
	{
	// thumb bestaat niet, maak hem en sla hem op
	create_thumbnail ( $img_source, $img_thumb );
	$fp = fopen( $img_thumb, 'rb' );
	header( 'Expires: Sat, 26 Jul 1997 05:00:00 GMT' );
	header( 'Cache-Control: no-store, no-cache, must-revalidate' );
	header( 'Cache-Control: post-check=0, pre-check=0', false );
	header( 'Pragma: no-cache' );
	header( "Content-type: image/jpeg" );
	header( "Content-Length: " . filesize($img_thumb) );
	fpassthru( $fp );
	}
 

function create_thumbnail ( $sImagePath, $thumbPath ) 
	{
	$iThumbnailWidth 	= (int)$_GET['width'];
	$iThumbnailHeight 	= (int)$_GET['height'];
	$iMaxWidth 			= (int)$_GET["maxw"];
	$iMaxHeight 		= (int)$_GET["maxh"];
	 
	if ($iMaxWidth && $iMaxHeight) $sType = 'scale';
	else if ($iThumbnailWidth && $iThumbnailHeight) $sType = 'exact';
	 
	$img = NULL;
	 
	$sExtension = @strtolower(end(explode('.', $sImagePath)));

	if ($sExtension == 'jpg' || $sExtension == 'jpeg') {
	 
		$img = @imagecreatefromjpeg($sImagePath)
			or die("Cannot create new JPEG image");
	 
	} else if ($sExtension == 'png') {
	 
	   $img = @imagecreatefrompng($sImagePath)
			or die("Cannot create new PNG image");
	 
	} else if ($sExtension == 'gif') {
	 
		$img = @imagecreatefromgif($sImagePath)
			or die("Cannot create new GIF image");
	}
	 
	if ($img) {
	 
		$iOrigWidth = imagesx($img);
		$iOrigHeight = imagesy($img);
	 
		if ($sType == 'scale') {
	 
			// Get scale ratio
	 
			$fScale = min($iMaxWidth/$iOrigWidth,
				  $iMaxHeight/$iOrigHeight);
	 
			if ($fScale < 1) {
	 
				$iNewWidth = floor($fScale*$iOrigWidth);
				$iNewHeight = floor($fScale*$iOrigHeight);
	 
				$tmpimg = imagecreatetruecolor($iNewWidth,
								   $iNewHeight);
	 
				imagecopyresampled($tmpimg, $img, 0, 0, 0, 0,
				$iNewWidth, $iNewHeight, $iOrigWidth, $iOrigHeight);
	 
				imagedestroy($img);
				$img = $tmpimg;
			}     
	 
		} else if ($sType == "exact") {
	 
			$fScale = max($iThumbnailWidth/$iOrigWidth,
				  $iThumbnailHeight/$iOrigHeight);
	 
			if ($fScale < 1) {
	 
				$iNewWidth = floor($fScale*$iOrigWidth);
				$iNewHeight = floor($fScale*$iOrigHeight);
	 
				$tmpimg = imagecreatetruecolor($iNewWidth,
								$iNewHeight);
				$tmp2img = imagecreatetruecolor($iThumbnailWidth,
								$iThumbnailHeight);
	 
				imagecopyresampled($tmpimg, $img, 0, 0, 0, 0,
				$iNewWidth, $iNewHeight, $iOrigWidth, $iOrigHeight);
	 
				if ($iNewWidth == $iThumbnailWidth) {
	 
					$yAxis = ($iNewHeight/2)-
						($iThumbnailHeight/2);
					$xAxis = 0;
	 
				} else if ($iNewHeight == $iThumbnailHeight)  {
	 
					$yAxis = 0;
					$xAxis = ($iNewWidth/2)-
						($iThumbnailWidth/2);
	 
				} 
	 
				imagecopyresampled($tmp2img, $tmpimg, 0, 0,
						   $xAxis, $yAxis,
						   $iThumbnailWidth,
						   $iThumbnailHeight,
						   $iThumbnailWidth,
						   $iThumbnailHeight);
	 
				imagedestroy($img);
				imagedestroy($tmpimg);
				$img = $tmp2img;
			}    
	 
		}
	 	
		imagejpeg($img, $thumbPath);
		
	}
}
 
?>