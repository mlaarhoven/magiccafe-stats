<?php

$bestandsnaam 	= $_REQUEST['bestandsnaam'];
$event_id 		= $_REQUEST['event_id'];
$set_id 		= $_REQUEST['set_id'];
$folder 		= $_REQUEST['folder'];

$result = unlink ( '/home/magicshoot/domains/magicshoot.nl/public_html/api/stack/event/' . $event_id . '/sets/'. $set_id . '/' . $folder . '/' .$bestandsnaam);
$result = unlink ( '/home/magicshoot/domains/magicshoot.nl/public_html/api/stack/event/' . $event_id . '/sets/'. $set_id . '/' . $folder . '/thumbs/' .$bestandsnaam);
echo  $result;
?>	