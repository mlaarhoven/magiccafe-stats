<?php
/**
 * Image proxy – fetches a photostrip from www.magicshoot.nl and serves it
 * from the same origin as the stats site, bypassing the browser CORS block
 * that prevents face-api.js from reading cross-origin canvas pixels.
 *
 * Access restricted to logged-in users (session must exist).
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Must be logged in
if (!isset($_SESSION['client_id'])) {
    http_response_code(403);
    exit;
}

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id'])  : 0;
$hash     = isset($_GET['hash'])     ? $_GET['hash']              : '';

// Validate: event_id must be positive; hash must be alphanumeric only
if ($event_id <= 0 || !preg_match('/^[a-zA-Z0-9]+$/', $hash)) {
    http_response_code(400);
    exit;
}

$file = "/home/magicshoot/domains/magicshoot.nl/public_html/photos/{$event_id}/{$hash}.jpg";

$data = @file_get_contents($file);

if ($data === false) {
    http_response_code(502);
    exit;
}

header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=3600');
echo $data;
