<?php

$event_id 	 =  $_REQUEST['event_id'];
$set_id 	 =  $_REQUEST['set_id'];
$foldername  =  $_REQUEST['foldername'];

$path = '/home/magicshoot/domains/magicshoot.nl/public_html/api/stack/event/' . $event_id . '/sets/' . $set_id . '/' . $foldername ;

if (!empty($_FILES))
	{
	$tempFile = $_FILES['file']['tmp_name'];
    $targetFile =  $path .'/'. $_FILES['file']['name'];
    move_uploaded_file( $tempFile, $targetFile );
    //header('Location: https://stats.magicshoot.nl/page_set_detail.php?set_id=' . $set_id );
    }

?>     