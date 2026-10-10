<?php
/*
 * download.php — serves a photo/video from the event media folders as a download.
 *
 * SECURITY FIX 2026-10-10: this script used to readfile() ANY path given in ?file=, so anyone
 * could download e.g. inc/connection.inc.php. The interface is unchanged (display pages keep
 * passing the absolute media path), but the file must now:
 *   - resolve (realpath, so ../ and symlinks are followed) to inside the event media folders;
 *   - have a photo/video extension.
 * Note: api/event/media is a symlink to /mnt/bigstorage/media — the allowed folders are
 * resolved with realpath() too, so the comparison is like for like.
 *
 * Identical copies live at stats/download.php and screen/admin/download.php.
 */

$allowed_dirs = array(
	'/home/magicshoot/domains/magicshoot.nl/public_html/api/event/media/photos',
	'/home/magicshoot/domains/magicshoot.nl/public_html/api/event/media/video',
);
$content_types = array(
	'jpg'  => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'png'  => 'image/png',
	'gif'  => 'image/gif',
	'mp4'  => 'video/mp4',
	'mov'  => 'video/quicktime',
);

$requested = isset($_GET['file']) ? (string)$_GET['file'] : '';
$path      = resolve_media_file($requested, $allowed_dirs, $content_types);

if ( $path === false )
	{
	http_response_code(404);
	die('File Not Found');
	}

download_file($path, $content_types);


/** Returns the real path when it is an allowed media file, otherwise false. */
function resolve_media_file ( $requested, $allowed_dirs, $content_types )
	{
	if ( $requested === '' || strpos($requested, "\0") !== false ) return false;

	$real = realpath($requested);
	if ( $real === false || !is_file($real) ) return false;

	$ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
	if ( !isset($content_types[$ext]) ) return false;

	foreach ( $allowed_dirs as $dir )
		{
		$real_dir = realpath($dir);
		if ( $real_dir !== false && strpos($real, rtrim($real_dir, '/') . '/') === 0 ) return $real;
		}
	return false;
	}

function download_file ( $fullPath, $content_types )
	{
	// Must be fresh start
	if ( headers_sent() ) die('Headers Sent');

	// Required for some browsers
	if ( ini_get('zlib.output_compression') ) ini_set('zlib.output_compression', 'Off');

	$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

	header("Pragma: public"); // required
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-Control: private", false); // required for certain browsers
	header("Content-Type: " . $content_types[$ext]);
	header("Content-Disposition: attachment; filename=\"" . basename($fullPath) . "\";");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: " . filesize($fullPath));
	while ( ob_get_level() > 0 ) ob_end_clean();
	flush();
	readfile($fullPath);
	}
?>
