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

    // ── Queue GIF regeneration when a design asset is uploaded ────────────────
    // Triggers on: strook.png, strook_foreground.png, or any foreground image
    $uploaded_filename = $_FILES['file']['name'];
    $is_design_asset   = in_array($uploaded_filename, ['strook.png', 'strook_foreground.png'])
                      || stripos($uploaded_filename, 'foreground') !== false;

    if ($is_design_asset) {
        $server_root = "/home/magicshoot/domains/magicshoot.nl/public_html";
        require_once "{$server_root}/admin/inc/connection.inc.php";

        $eid = (int) $event_id;
        $sid = (int) $set_id;

        // Remove pending (not yet picked up) jobs for this event+set to avoid pile-up
        $cn->query("DELETE FROM photobooth_gif_generator
                    WHERE event_id = {$eid} AND set_id = {$sid} AND status = 'created'");

        // Insert one job per animation
        $animations = ['printen', 'startscherm', 'startscherm_qr', 'betalen', 'betalen_qr', 'pak_de_strookjes'];
        $stmt = $cn->prepare(
            "INSERT INTO photobooth_gif_generator (event_id, set_id, animation_name, status, created)
             VALUES (?, ?, ?, 'created', NOW())"
        );
        foreach ($animations as $anim) {
            $stmt->bind_param('iis', $eid, $sid, $anim);
            $stmt->execute();
        }
        $stmt->close();
    }
    }

?>     