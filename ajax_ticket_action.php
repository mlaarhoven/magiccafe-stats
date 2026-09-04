<?php
define('CM_PRODUCT_TOKEN', 'FILL_IN_CM_PRODUCT_TOKEN_HERE');
define('CM_SENDER', 'MagicShoot');

header('Content-Type: application/json');

global $cn;
include '../inc/connection.inc.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'error' => 'Niet ingelogd']);
    exit;
}

$client_id   = (int)$_SESSION['client_id'];
$client_role = $_SESSION['client_role'];
$client_name = $_SESSION['client_name'];

$action    = $_POST['action']    ?? '';
$ticket_id = (int)($_POST['ticket_id'] ?? 0);

if (!$ticket_id || !$action) {
    echo json_encode(['success' => false, 'error' => 'Ongeldige aanvraag']);
    exit;
}

// Fetch ticket + creator info
$res = mysqli_query($cn, "SELECT t.*, c.client_email AS creator_email, c.client_phone AS creator_phone,
                                  c.client_name AS creator_name, e.name AS event_name
                             FROM support_ticket t
                        LEFT JOIN photobooth_stat_client c ON c.client_id = t.created_by_client_id
                        LEFT JOIN photobooth_event_ids e ON e.event_id = t.event_id
                            WHERE t.ticket_id = '$ticket_id'");
if (!$res || mysqli_num_rows($res) === 0) {
    echo json_encode(['success' => false, 'error' => 'Ticket niet gevonden']);
    exit;
}
$ticket = $res->fetch_object();

// Non-admin can only interact with own tickets
if ($client_role !== 'admin' && (int)$ticket->created_by_client_id !== $client_id) {
    echo json_encode(['success' => false, 'error' => 'Geen toegang']);
    exit;
}

// Non-admin can only add messages
if ($client_role !== 'admin' && in_array($action, ['change_status', 'assign', 'change_priority'])) {
    echo json_encode(['success' => false, 'error' => 'Onvoldoende rechten']);
    exit;
}

$priority_nl = [
    'low'    => 'Laag',
    'normal' => 'Normaal',
    'high'   => 'Hoog',
    'urgent' => 'Urgent',
];
$status_nl = [
    'new'              => 'Nieuw',
    'assigned'         => 'Toegewezen',
    'in_progress'      => 'In behandeling',
    'planned_remote'   => 'Ingepland remote werk',
    'pending_purchase' => 'Wacht op hardware aankoop',
    'pending_client'   => 'Wacht op klant reactie',
    'on_hold'          => 'On hold',
    'closed'           => 'Gesloten',
    'cancelled'        => 'Geannuleerd',
    'reopened'         => 'Opnieuw geopend',
];

function fix_line_endings($text) {
    // Handle literal \r\n escape sequences (from pasted email text in some clients)
    $text = str_replace("\\r\\n", "\n", $text);
    $text = str_replace(["\\r", "\\n"], "\n", $text);
    // Normalize actual CRLF and lone CR to LF
    return str_replace(["\r\n", "\r"], "\n", $text);
}

switch ($action) {

    // ---------------------------------------------------------------
    case 'add_response':
        $msg_clean    = fix_line_endings($_POST['message'] ?? '');
        $message      = trim(mysqli_real_escape_string($cn, $msg_clean));
        $notify_email = (int)($_POST['notify_email'] ?? 0);
        $notify_sms   = (int)($_POST['notify_sms']   ?? 0);

        if ($message === '') {
            echo json_encode(['success' => false, 'error' => 'Leeg bericht']); exit;
        }

        // Insert response first to get the response_id
        mysqli_query($cn, "INSERT INTO support_ticket_response SET
                    ticket_id         = '$ticket_id',
                    client_id         = '$client_id',
                    response_type     = 'message',
                    response_text     = '$message',
                    notify_email_sent = 0,
                    notify_sms_sent   = 0,
                    response_created  = NOW()");
        $response_id = (int)mysqli_insert_id($cn);
        mysqli_query($cn, "UPDATE support_ticket SET ticket_updated = NOW() WHERE ticket_id = '$ticket_id'");

        // Auto-notify all assignees (skip the person who just responded)
        $assignees = get_ticket_assignees($cn, $ticket_id);
        foreach ($assignees as $a) {
            if ((int)$a->client_id === $client_id) continue;
            if (!$a->client_email) continue;
            $sender = ($client_role === 'admin') ? 'het MagicShoot team' : $ticket->creator_name;
            $body   = "Er is een nieuwe reactie geplaatst op ticket #{$ticket->ticket_id} door {$sender}.\n\nBericht:\n{$msg_clean}";
            notify_email($response_id, (int)$a->client_id, $a->client_email, $a->client_name,
                $ticket->ticket_id, $ticket->ticket_subject,
                "Nieuwe reactie op ticket #{$ticket->ticket_id}", $body);
        }

        // Notify creator only when checkbox was checked (skip if they are the one who just responded)
        if ($notify_email && $ticket->creator_email && (int)$ticket->created_by_client_id !== $client_id) {
            $body = "Er is een reactie geplaatst op uw ticket.\n\nBericht:\n{$msg_clean}";
            notify_email($response_id, (int)$ticket->created_by_client_id,
                $ticket->creator_email, $ticket->creator_name,
                $ticket->ticket_id, $ticket->ticket_subject,
                "Nieuw bericht op uw support ticket #{$ticket->ticket_id}", $body);
        }
        if ($notify_sms && $ticket->creator_phone && $client_role === 'admin') {
            $sms_text = "MagicShoot ticket #{$ticket->ticket_id}: " . mb_substr($msg_clean, 0, 100);
            notify_sms($response_id, (int)$ticket->created_by_client_id, $ticket->creator_phone, $sms_text);
        }

        update_notify_flags($response_id);
        echo json_encode(['success' => true]);
        break;

    // ---------------------------------------------------------------
    case 'change_status':
        $allowed = ['new','assigned','in_progress','planned_remote','pending_purchase','pending_client','on_hold','closed','cancelled','reopened'];
        $new_status = mysqli_real_escape_string($cn, $_POST['new_status'] ?? '');
        if (!in_array($new_status, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Ongeldige status']); exit;
        }
        $old_status    = mysqli_real_escape_string($cn, $ticket->ticket_status);
        $notify_email  = (int)($_POST['notify_email'] ?? 0);
        $notify_sms    = (int)($_POST['notify_sms']   ?? 0);
        $extra_clean   = fix_line_endings($_POST['message'] ?? '');
        $extra_message = trim(mysqli_real_escape_string($cn, $extra_clean));
        $old_label     = $status_nl[$ticket->ticket_status] ?? $ticket->ticket_status;
        $new_label     = $status_nl[$new_status] ?? $new_status;

        mysqli_query($cn, "UPDATE support_ticket SET ticket_status='$new_status', ticket_updated=NOW() WHERE ticket_id='$ticket_id'");
        mysqli_query($cn, "INSERT INTO support_ticket_response SET
                ticket_id         = '$ticket_id',
                client_id         = '$client_id',
                response_type     = 'status_change',
                response_text     = '$extra_message',
                old_status        = '$old_status',
                new_status        = '$new_status',
                notify_email_sent = 0,
                notify_sms_sent   = 0,
                response_created  = NOW()");
        $response_id = (int)mysqli_insert_id($cn);

        // Auto-notify all assignees
        $assignees = get_ticket_assignees($cn, $ticket_id);
        foreach ($assignees as $a) {
            if ((int)$a->client_id === $client_id) continue;
            if (!$a->client_email) continue;
            $body = "De status van ticket #{$ticket->ticket_id} is gewijzigd.\n\nVan: {$old_label}\nNaar: {$new_label}";
            if ($extra_clean !== '') $body .= "\n\nBericht:\n{$extra_clean}";
            notify_email($response_id, (int)$a->client_id, $a->client_email, $a->client_name,
                $ticket->ticket_id, $ticket->ticket_subject,
                "Status gewijzigd: ticket #{$ticket->ticket_id}", $body);
        }

        // Notify creator only when checkbox was checked
        if ($notify_email && $ticket->creator_email && (int)$ticket->created_by_client_id !== $client_id) {
            $body = "De status van uw support ticket is gewijzigd.\n\nVan: {$old_label}\nNaar: {$new_label}";
            if ($extra_clean !== '') $body .= "\n\nBericht van MagicShoot:\n{$extra_clean}";
            notify_email($response_id, (int)$ticket->created_by_client_id,
                $ticket->creator_email, $ticket->creator_name,
                $ticket->ticket_id, $ticket->ticket_subject,
                "Status update ticket #{$ticket->ticket_id}: {$new_label}", $body);
        }
        if ($notify_sms && $ticket->creator_phone) {
            $sms_text = "MagicShoot ticket #{$ticket->ticket_id} - Status: {$new_label}";
            if ($extra_clean !== '') $sms_text .= '. ' . mb_substr($extra_clean, 0, 80);
            notify_sms($response_id, (int)$ticket->created_by_client_id, $ticket->creator_phone, $sms_text);
        }

        update_notify_flags($response_id);
        echo json_encode(['success' => true]);
        break;

    // ---------------------------------------------------------------
    case 'assign':
        $raw_ids  = $_POST['assignees'] ?? '';
        $new_ids  = [];
        if ($raw_ids !== '') {
            foreach (explode(',', $raw_ids) as $id) {
                $id = (int)trim($id);
                if ($id > 0) $new_ids[] = $id;
            }
        }

        // Verify all are admins
        $valid_ids     = [];
        $assignee_info = [];
        if (!empty($new_ids)) {
            $in_list = implode(',', $new_ids);
            $chk = mysqli_query($cn, "SELECT client_id, client_name, client_email
                                        FROM photobooth_stat_client
                                       WHERE client_id IN ($in_list) AND client_role='admin'");
            while ($row = $chk->fetch_object()) {
                $valid_ids[]     = (int)$row->client_id;
                $assignee_info[] = $row;
            }
        }

        $names_arr = [];
        foreach ($assignee_info as $a) $names_arr[] = $a->client_name;
        $names_str = empty($names_arr) ? 'niemand' : implode(', ', $names_arr);

        // Replace assignees
        mysqli_query($cn, "DELETE FROM support_ticket_assignee WHERE ticket_id='$ticket_id'");
        foreach ($valid_ids as $aid) {
            mysqli_query($cn, "INSERT INTO support_ticket_assignee SET
                ticket_id             = '$ticket_id',
                client_id             = '$aid',
                assigned_by_client_id = '$client_id',
                assigned_at           = NOW()");
        }

        $resp_text = mysqli_real_escape_string($cn, "Ticket toegewezen aan: $names_str (door $client_name)");
        mysqli_query($cn, "INSERT INTO support_ticket_response SET
                ticket_id         = '$ticket_id',
                client_id         = '$client_id',
                response_type     = 'assignment',
                response_text     = '$resp_text',
                notify_email_sent = 0,
                response_created  = NOW()");
        $response_id = (int)mysqli_insert_id($cn);

        // Email all newly assigned admins (skip the person doing the assigning)
        foreach ($assignee_info as $a) {
            if ((int)$a->client_id === $client_id) continue;
            if (!$a->client_email) continue;
            $body = "U bent toegewezen aan support ticket #{$ticket->ticket_id} door {$client_name}.\n\nOnderwerp: {$ticket->ticket_subject}";
            notify_email($response_id, (int)$a->client_id, $a->client_email, $a->client_name,
                $ticket->ticket_id, $ticket->ticket_subject,
                "Toewijzing: ticket #{$ticket->ticket_id}", $body);
        }

        // Auto-notify creator
        if ($ticket->creator_email && (int)$ticket->created_by_client_id !== $client_id) {
            $body = "De toewijzing van uw ticket #{$ticket->ticket_id} is bijgewerkt door {$client_name}.\n\nToegewezen aan: {$names_str}";
            notify_email($response_id, (int)$ticket->created_by_client_id,
                $ticket->creator_email, $ticket->creator_name,
                $ticket->ticket_id, $ticket->ticket_subject,
                "Toewijzing bijgewerkt: ticket #{$ticket->ticket_id}", $body);
        }

        update_notify_flags($response_id);

        // Auto-advance status from 'new' to 'assigned'
        if ($ticket->ticket_status === 'new' && !empty($valid_ids)) {
            mysqli_query($cn, "UPDATE support_ticket SET ticket_status='assigned', ticket_updated=NOW() WHERE ticket_id='$ticket_id'");
        } else {
            mysqli_query($cn, "UPDATE support_ticket SET ticket_updated=NOW() WHERE ticket_id='$ticket_id'");
        }

        echo json_encode(['success' => true]);
        break;

    // ---------------------------------------------------------------
    case 'change_priority':
        $allowed_priority = ['low', 'normal', 'high', 'urgent'];
        $new_priority = mysqli_real_escape_string($cn, $_POST['new_priority'] ?? '');
        if (!in_array($new_priority, $allowed_priority)) {
            echo json_encode(['success' => false, 'error' => 'Ongeldige prioriteit']); exit;
        }
        $old_p_label = $priority_nl[$ticket->ticket_priority] ?? $ticket->ticket_priority;
        $new_p_label = $priority_nl[$new_priority] ?? $new_priority;

        mysqli_query($cn, "UPDATE support_ticket SET ticket_priority='$new_priority', ticket_updated=NOW() WHERE ticket_id='$ticket_id'");

        // Add timeline entry
        $resp_text = mysqli_real_escape_string($cn, "Prioriteit gewijzigd van {$old_p_label} naar {$new_p_label}");
        mysqli_query($cn, "INSERT INTO support_ticket_response SET
                ticket_id         = '$ticket_id',
                client_id         = '$client_id',
                response_type     = 'priority_change',
                response_text     = '$resp_text',
                notify_email_sent = 0,
                response_created  = NOW()");
        $response_id = (int)mysqli_insert_id($cn);

        // Auto-notify all assignees
        $assignees = get_ticket_assignees($cn, $ticket_id);
        foreach ($assignees as $a) {
            if ((int)$a->client_id === $client_id) continue;
            if (!$a->client_email) continue;
            $body = "De prioriteit van ticket #{$ticket->ticket_id} is gewijzigd van {$old_p_label} naar {$new_p_label}.";
            notify_email($response_id, (int)$a->client_id, $a->client_email, $a->client_name,
                $ticket->ticket_id, $ticket->ticket_subject,
                "Prioriteit gewijzigd: ticket #{$ticket->ticket_id}", $body);
        }

        // Auto-notify creator
        if ($ticket->creator_email && (int)$ticket->created_by_client_id !== $client_id) {
            $body = "De prioriteit van uw ticket #{$ticket->ticket_id} is gewijzigd van {$old_p_label} naar {$new_p_label}.";
            notify_email($response_id, (int)$ticket->created_by_client_id,
                $ticket->creator_email, $ticket->creator_name,
                $ticket->ticket_id, $ticket->ticket_subject,
                "Prioriteit gewijzigd: ticket #{$ticket->ticket_id}", $body);
        }

        update_notify_flags($response_id);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Onbekende actie']);
}

// -------------------------------------------------------------------
function get_ticket_assignees($cn, $ticket_id) {
    $assignees = [];
    $res = mysqli_query($cn, "SELECT c.client_id, c.client_name, c.client_email, c.client_phone
                                FROM support_ticket_assignee a
                                JOIN photobooth_stat_client c ON c.client_id = a.client_id
                               WHERE a.ticket_id = '$ticket_id' AND c.client_active = 1");
    if ($res) while ($row = $res->fetch_object()) $assignees[] = $row;
    return $assignees;
}

function notify_email($response_id, $recipient_client_id, $to_email, $to_name, $ticket_id, $subject, $email_subject, $body_extra) {
    global $cn;
    if (!$to_email) return false;
    $ok = send_ticket_email($to_email, $to_name, $ticket_id, $subject, $email_subject, $body_extra);
    if ($ok) {
        $rid = (int)$response_id;
        $cid = (int)$recipient_client_id;
        mysqli_query($cn, "INSERT INTO support_ticket_response_notify SET
            response_id = '$rid', client_id = '$cid', notify_type = 'email', sent_at = NOW()");
    }
    return $ok;
}

function notify_sms($response_id, $recipient_client_id, $phone, $message) {
    global $cn;
    if (!$phone) return false;
    $ok = send_cm_sms($phone, $message);
    if ($ok) {
        $rid = (int)$response_id;
        $cid = (int)$recipient_client_id;
        mysqli_query($cn, "INSERT INTO support_ticket_response_notify SET
            response_id = '$rid', client_id = '$cid', notify_type = 'sms', sent_at = NOW()");
    }
    return $ok;
}

function update_notify_flags($response_id) {
    global $cn;
    $rid = (int)$response_id;
    $ec  = (int)mysqli_fetch_row(mysqli_query($cn, "SELECT COUNT(*) FROM support_ticket_response_notify WHERE response_id='$rid' AND notify_type='email'"))[0];
    $sc  = (int)mysqli_fetch_row(mysqli_query($cn, "SELECT COUNT(*) FROM support_ticket_response_notify WHERE response_id='$rid' AND notify_type='sms'"))[0];
    mysqli_query($cn, "UPDATE support_ticket_response SET
        notify_email_sent = " . ($ec > 0 ? 1 : 0) . ",
        notify_sms_sent   = " . ($sc > 0 ? 1 : 0) . "
        WHERE response_id = '$rid'");
}

function send_ticket_email($to_email, $to_name, $ticket_id, $subject, $email_subject, $body_extra) {
    $ticket_url = "https://stats.magicshoot.nl/page_ticket_detail.php?ticket_id=$ticket_id";
    $body  = "Beste $to_name,\n\n";
    $body .= $body_extra . "\n\n";
    $body .= "Onderwerp: $subject\n";
    $body .= "Bekijk ticket: $ticket_url\n\n";
    $body .= "Met vriendelijke groet,\nHet MagicShoot team\nsupport@magicshoot.nl";

    $headers  = "From: support@magicshoot.nl\r\n";
    $headers .= "Reply-To: support@magicshoot.nl\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    return mail($to_email, $email_subject, $body, $headers);
}

function send_cm_sms($phone, $message) {
    if (CM_PRODUCT_TOKEN === 'FILL_IN_CM_PRODUCT_TOKEN_HERE') return false;

    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (substr($phone, 0, 1) === '0') $phone = '+31' . substr($phone, 1);
    if (substr($phone, 0, 1) !== '+') $phone = '+' . $phone;

    $payload = json_encode([
        'messages' => [
            'authentication' => ['producttoken' => CM_PRODUCT_TOKEN],
            'msg' => [[
                'from' => CM_SENDER,
                'to'   => [['number' => $phone]],
                'body' => ['content' => $message],
            ]]
        ]
    ]);

    $ch = curl_init('https://gw.cmtelecom.com/v1.0/message');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    return $http_code >= 200 && $http_code < 300;
}
