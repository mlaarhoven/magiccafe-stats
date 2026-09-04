<?php
require_once ( "/home/magicshoot/domains/magicshoot.nl/public_html/inc/photo_thumb_functions.php" );

# geeft de thumb-url voor een foto terug.
# de thumb wordt normaal al aangemaakt bij upload (api/post/upload.php),
# maar voor oudere foto's die dat nog niet hebben wordt hij hier alsnog on-the-fly aangemaakt.
function get_photo_thumb_url ( $event_id, $file_hash )
	{
	global $timer;
	if ( !is_array ( $timer ) ) { $timer = array(); }
	if ( !isset ( $timer['photo_thumb_on_the_fly_count'] ) ) { $timer['photo_thumb_on_the_fly_count'] = 0; }
	if ( !isset ( $timer['photo_thumb_on_the_fly_time']  ) ) { $timer['photo_thumb_on_the_fly_time']  = 0; }

	$base_dir    = "/home/magicshoot/domains/magicshoot.nl/public_html/api/event/media/photos/" . $event_id;
	$source_path = $base_dir . "/" . $file_hash . ".jpg";
	$thumb_dir   = $base_dir . "/thumbs";
	$thumb_path  = $thumb_dir . "/" . $file_hash . ".jpg";

	if ( is_file ( $thumb_path ) == false && is_file ( $source_path ) )
		{
		$time = -microtime(true);
		if ( is_dir ( $thumb_dir ) == false )
			{
			mkdir ( $thumb_dir );
			chmod ( $thumb_dir, 0777 );
			}
		create_photo_thumb ( $event_id, $source_path, $thumb_path );
		$timer['photo_thumb_on_the_fly_count']++;
		$timer['photo_thumb_on_the_fly_time'] += $time += microtime(true);
		}

	# cache-buster: voorkomt dat browsers/CDN een verouderde (bv. verkeerd geformateerde) thumb blijven tonen na regeneratie
	$cache_buster = is_file ( $thumb_path ) ? filemtime ( $thumb_path ) : time();

	return "https://api.magicshoot.nl/event/media/photos/" . $event_id . "/thumbs/" . $file_hash . ".jpg?v=" . $cache_buster;
	}
?>
