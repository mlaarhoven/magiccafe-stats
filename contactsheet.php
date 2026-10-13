<?php
# ---------------------------------------------------------------------------
#  contactsheet.php — one large JPG with all photos between a first and a last photo of the gallery
#  (page_gallery.php, button "Contactsheet maken"), oldest first, N per row. Made for handing a client
#  every look of the Lufthansa AI era-swap booth (2026-10-13): 6 looks per era → one era per row.
#
#  GET: from, to   = filename_md5 of the first and last photo (order doesn't matter, the range is by datetime)
#       cols       = photos per row (default 6)
#       crop       = 'square' → only the top W×W of each photo (event 529: the AI image without the frame),
#                    'full'   → the whole image
#  Only for the event selected in the session (same as the gallery), admin/owner only.
# ---------------------------------------------------------------------------

global $cn;
include '../inc/connection.inc.php';
if ( session_status() === PHP_SESSION_NONE ) session_start();

if ( !isset ( $_SESSION['client_id'] ) || !in_array ( $_SESSION['client_role'], array ( 'admin', 'owner' ) ) )
	{ http_response_code ( 403 ); echo 'Geen toegang'; exit; }

$event_id = (int) $_SESSION['event_id'];
$from     = isset ( $_GET['from'] ) ? $_GET['from'] : '';
$to       = isset ( $_GET['to'] )   ? $_GET['to']   : '';
$cols     = isset ( $_GET['cols'] ) ? max ( 1, min ( 12, (int) $_GET['cols'] ) ) : 6;
$crop     = ( isset ( $_GET['crop'] ) && $_GET['crop'] == 'full' ) ? 'full' : 'square';
if ( !preg_match ( '/^[A-Za-z0-9]{6,40}$/', $from ) || !preg_match ( '/^[A-Za-z0-9]{6,40}$/', $to ) )
	{ http_response_code ( 400 ); echo 'Kies een eerste en een laatste foto.'; exit; }

# the two end points → their timestamps → every visible photo in between
$times = array();
$r = mysqli_query ( $cn, "SELECT filename_md5, datetime FROM photo
                           WHERE event_id = " . $event_id . "
                             AND filename_md5 IN ('" . mysqli_real_escape_string ( $cn, $from ) . "', '" . mysqli_real_escape_string ( $cn, $to ) . "')" );
while ( $row = mysqli_fetch_object ( $r ) ) { $times[] = $row->datetime; }
if ( count ( $times ) < ( $from == $to ? 1 : 2 ) )
	{ http_response_code ( 404 ); echo 'Foto niet gevonden bij deze photobooth.'; exit; }
sort ( $times );
$t0 = $times[0];
$t1 = $times[count ( $times ) - 1];

$photos = array();
$r = mysqli_query ( $cn, "SELECT filename_md5 FROM photo
                           WHERE event_id = " . $event_id . "
                             AND thumb_hide = '0'
                             AND datetime BETWEEN '" . mysqli_real_escape_string ( $cn, $t0 ) . "' AND '" . mysqli_real_escape_string ( $cn, $t1 ) . "'
                           ORDER BY datetime ASC, photo_id ASC
                           LIMIT 300" );
$dir = '/home/magicshoot/domains/magicshoot.nl/public_html/api/event/media/photos/' . $event_id;
while ( $row = mysqli_fetch_object ( $r ) )
	{
	$f = $dir . '/' . basename ( $row->filename_md5 ) . '.jpg';
	if ( is_file ( $f ) ) { $photos[] = $f; }
	}
if ( count ( $photos ) == 0 ) { http_response_code ( 404 ); echo 'Geen foto\'s in deze reeks.'; exit; }

set_time_limit ( 300 );
ini_set ( 'memory_limit', '512M' );

# tile size from the first photo's aspect; 600 px wide keeps a 9-row sheet of 2480x3688 photos around 25 MP
$info  = getimagesize ( $photos[0] );
$TW    = 600;
$TH    = $crop == 'square' ? $TW : (int) round ( $TW * $info[1] / $info[0] );
$GAP   = 16;
$rows  = (int) ceil ( count ( $photos ) / $cols );
$W     = $cols * $TW + ( $cols + 1 ) * $GAP;
$H     = $rows * $TH + ( $rows + 1 ) * $GAP;

$sheet = imagecreatetruecolor ( $W, $H );
imagefill ( $sheet, 0, 0, imagecolorallocate ( $sheet, 255, 255, 255 ) );
foreach ( $photos as $i => $f )
	{
	$src = @imagecreatefromjpeg ( $f );
	if ( !$src ) continue;
	$sw = imagesx ( $src ); $sh = imagesy ( $src );
	$ch = $crop == 'square' ? min ( $sw, $sh ) : $sh;   # square = top W×W (the photo above the frame)
	$x = $GAP + ( $i % $cols ) * ( $TW + $GAP );
	$y = $GAP + (int) floor ( $i / $cols ) * ( $TH + $GAP );
	imagecopyresampled ( $sheet, $src, $x, $y, 0, 0, $TW, $TH, $sw, $ch );
	imagedestroy ( $src );
	}

$name = 'contactsheet_' . $event_id . '_' . date ( 'Y-m-d_Hi' ) . '_' . count ( $photos ) . 'fotos.jpg';
header ( 'Content-Type: image/jpeg' );
header ( 'Content-Disposition: attachment; filename="' . $name . '"' );
imagejpeg ( $sheet, null, 90 );
imagedestroy ( $sheet );
?>
