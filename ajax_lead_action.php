<?php
# ---------------------------------------------------------------------------
#  ajax_lead_action.php — acties op een campagne-reactie. Admin only.
#  Opzet gelijk aan ajax_ticket_action.php. Acties:
#    add_note, change_status, assign, set_score, record_reply, edit_lead
#
#  Er gaat NOOIT mail naar het contact (besluit 14-09-2026: alleen vastleggen).
#  Interne mail gaat naar toegewezen beheerders, nooit naar degene die de
#  actie zelf uitvoert. Zelfde mail() + afzender als send_ticket_email():
#  lokale mail() valt niet onder de anti-spoof ACL (zie root CLAUDE.md, pad B).
# ---------------------------------------------------------------------------

header('Content-Type: application/json');

global $cn;
include '../inc/connection.inc.php';
include_once __DIR__ . '/inc/lead_functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'error' => 'Niet ingelogd']); exit;
}
if ($_SESSION['client_role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Geen toegang']); exit;
}

$client_id   = (int)$_SESSION['client_id'];
$client_name = $_SESSION['client_name'];

$action  = (string)($_POST['action'] ?? '');
$lead_id = (int)($_POST['lead_id'] ?? 0);
if (!$lead_id || $action === '') {
    echo json_encode(['success' => false, 'error' => 'Ongeldige aanvraag']); exit;
}

$res = mysqli_query($cn, "SELECT * FROM campaign_lead WHERE lead_id = '$lead_id'");
if (!$res || mysqli_num_rows($res) === 0) {
    echo json_encode(['success' => false, 'error' => 'Reactie niet gevonden']); exit;
}
$lead = $res->fetch_object();

$status_labels  = lead_status_labels();
$channel_labels = lead_channel_labels();
$reply_channels = lead_reply_channel_labels();
$score_labels   = lead_score_labels();

$msg_clean = lead_clean_text($_POST['message'] ?? '');
$msg_sql   = mysqli_real_escape_string($cn, $msg_clean);

switch ($action) {

    // ---------------------------------------------------------------
    case 'add_note':
        if ($msg_clean === '') {
            echo json_encode(['success' => false, 'error' => 'Lege notitie']); exit;
        }
        add_lead_response($lead_id, $client_id, "response_type = 'message', response_text = '$msg_sql'");
        notify_lead_assignees($lead, $client_id, "Nieuwe notitie",
            "$client_name heeft een notitie toegevoegd:\n\n$msg_clean");
        echo json_encode(['success' => true]);
        break;

    // ---------------------------------------------------------------
    case 'change_status':
        $new_status = (string)($_POST['new_status'] ?? '');
        if (!isset($status_labels[$new_status])) {
            echo json_encode(['success' => false, 'error' => 'Ongeldige status']); exit;
        }
        if ($new_status === $lead->lead_status && $msg_clean === '') {
            echo json_encode(['success' => false, 'error' => 'Status is niet gewijzigd']); exit;
        }
        $old_status = $lead->lead_status;
        mysqli_query($cn, "UPDATE campaign_lead SET lead_status = '$new_status', lead_updated = NOW() WHERE lead_id = '$lead_id'");
        add_lead_response($lead_id, $client_id, "response_type = 'status_change', response_text = '$msg_sql',
                                                 old_status = '$old_status', new_status = '$new_status'");
        $body = "$client_name heeft de status gewijzigd.\n\nVan: " . $status_labels[$old_status]['label']
              . "\nNaar: " . $status_labels[$new_status]['label'];
        if ($msg_clean !== '') $body .= "\n\nToelichting:\n$msg_clean";
        notify_lead_assignees($lead, $client_id, "Status: " . $status_labels[$new_status]['label'], $body);
        echo json_encode(['success' => true]);
        break;

    // ---------------------------------------------------------------
    case 'assign':
        $new_ids = [];
        foreach (explode(',', (string)($_POST['assignees'] ?? '')) as $id) {
            $id = (int)trim($id);
            if ($id > 0) $new_ids[] = $id;
        }

        // Alleen actieve beheerders
        $assignee_info = [];
        if (!empty($new_ids)) {
            $in_list = implode(',', $new_ids);
            $chk = mysqli_query($cn, "SELECT client_id, client_name, client_email FROM photobooth_stat_client
                                       WHERE client_id IN ($in_list) AND client_role = 'admin' AND client_active = 1");
            if ($chk) while ($row = $chk->fetch_object()) $assignee_info[] = $row;
        }

        // Wie was er al? Die krijgen geen nieuwe toewijzingsmail.
        $old_ids = [];
        $ores = mysqli_query($cn, "SELECT client_id FROM campaign_lead_assignee WHERE lead_id = '$lead_id'");
        if ($ores) while ($row = $ores->fetch_row()) $old_ids[] = (int)$row[0];

        mysqli_query($cn, "DELETE FROM campaign_lead_assignee WHERE lead_id = '$lead_id'");
        foreach ($assignee_info as $a) {
            $aid = (int)$a->client_id;
            mysqli_query($cn, "INSERT INTO campaign_lead_assignee SET
                lead_id               = '$lead_id',
                client_id             = '$aid',
                assigned_by_client_id = '$client_id',
                assigned_at           = NOW()");
        }

        $names_str = empty($assignee_info) ? 'niemand' : implode(', ', array_map(fn($a) => $a->client_name, $assignee_info));
        $resp_text = mysqli_real_escape_string($cn, "Toegewezen aan: $names_str");
        add_lead_response($lead_id, $client_id, "response_type = 'assignment', response_text = '$resp_text'");

        foreach ($assignee_info as $a) {
            if ((int)$a->client_id === $client_id) continue;
            if (in_array((int)$a->client_id, $old_ids, true)) continue;
            send_lead_email($a->client_email, $a->client_name, $lead, "Toegewezen",
                "$client_name heeft je toegewezen aan deze campagne-reactie.");
        }
        echo json_encode(['success' => true]);
        break;

    // ---------------------------------------------------------------
    case 'set_score':
        $score = (int)($_POST['score'] ?? -1);
        if ($score < 0 || $score > 5) {
            echo json_encode(['success' => false, 'error' => 'Ongeldige score']); exit;
        }
        if ($score === (int)$lead->venue_score && $msg_clean === '') {
            echo json_encode(['success' => false, 'error' => 'Score is niet gewijzigd']); exit;
        }
        $score_sql = $score === 0 ? 'NULL' : "'$score'";
        mysqli_query($cn, "UPDATE campaign_lead SET venue_score = $score_sql, lead_updated = NOW() WHERE lead_id = '$lead_id'");

        $txt = $score === 0 ? 'Score gewist' : "Score: $score/5 (" . $score_labels[$score] . ")";
        if ($msg_clean !== '') $txt .= "\n$msg_clean";
        $txt_sql = mysqli_real_escape_string($cn, $txt);
        add_lead_response($lead_id, $client_id, "response_type = 'score_change', response_text = '$txt_sql'");
        echo json_encode(['success' => true]);
        break;

    // ---------------------------------------------------------------
    case 'record_reply':
        // Legt vast wat wij het contact hebben laten weten. Verstuurt niets.
        $outcome = (string)($_POST['outcome'] ?? '');
        $channel = (string)($_POST['reply_channel'] ?? '');
        if (!in_array($outcome, ['positive', 'negative'], true)) {
            echo json_encode(['success' => false, 'error' => 'Kies positief of negatief']); exit;
        }
        if (!isset($reply_channels[$channel])) {
            echo json_encode(['success' => false, 'error' => 'Ongeldig kanaal']); exit;
        }
        $reply_status = $outcome === 'positive' ? 'replied_positive' : 'replied_negative';
        $old_status   = $lead->lead_status;

        // new_status op de tijdlijnregel = de uitkomst (daar telt page_leads.php "positief gereageerd" op).
        // De lead zelf gaat alleen mee als hij nog in de reageer-fase zit; ingepland/gesloten blijft staan.
        add_lead_response($lead_id, $client_id, "response_type = 'contact_reply', response_text = '$msg_sql',
                                                 reply_channel = '$channel',
                                                 old_status = '$old_status', new_status = '$reply_status'");
        if (lead_reply_moves_status($old_status)) {
            mysqli_query($cn, "UPDATE campaign_lead SET lead_status = '$reply_status' WHERE lead_id = '$lead_id'");
        }

        $body = "$client_name heeft het contact " . ($outcome === 'positive' ? 'POSITIEF' : 'NEGATIEF')
              . " geantwoord via " . $reply_channels[$channel] . ".";
        if ($msg_clean !== '') $body .= "\n\nWat er gezegd is:\n$msg_clean";
        notify_lead_assignees($lead, $client_id, ($outcome === 'positive' ? 'Positief' : 'Negatief') . " gereageerd", $body);
        echo json_encode(['success' => true]);
        break;

    // ---------------------------------------------------------------
    case 'edit_lead':
        $new = [
            'campaign_id'      => (int)($_POST['campaign_id'] ?? 0),
            'lead_channel'     => (string)($_POST['lead_channel'] ?? ''),
            'contact_name'     => trim((string)($_POST['contact_name']  ?? '')),
            'contact_phone'    => trim((string)($_POST['contact_phone'] ?? '')),
            'contact_email'    => trim((string)($_POST['contact_email'] ?? '')),
            'venue_name'       => trim((string)($_POST['venue_name']    ?? '')),
            'venue_city'       => trim((string)($_POST['venue_city']    ?? '')),
            'lead_description' => lead_clean_text($_POST['lead_description'] ?? ''),
        ];
        $chk = mysqli_query($cn, "SELECT campaign_id FROM campaign WHERE campaign_id = '{$new['campaign_id']}'");
        if (!$chk || mysqli_num_rows($chk) === 0) {
            echo json_encode(['success' => false, 'error' => 'Onbekende campagne']); exit;
        }
        if (!isset($channel_labels[$new['lead_channel']])) {
            echo json_encode(['success' => false, 'error' => 'Ongeldig kanaal']); exit;
        }
        if ($new['contact_name'] === '' || $new['venue_name'] === '' || $new['lead_description'] === '') {
            echo json_encode(['success' => false, 'error' => 'Naam, venue en beschrijving zijn verplicht']); exit;
        }
        if ($new['contact_phone'] === '' && $new['contact_email'] === '') {
            echo json_encode(['success' => false, 'error' => 'Telefoon of e-mail is verplicht']); exit;
        }
        if ($new['contact_email'] !== '' && !filter_var($new['contact_email'], FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Het e-mailadres lijkt niet te kloppen']); exit;
        }

        $field_labels = [
            'campaign_id' => 'campagne', 'lead_channel' => 'kanaal', 'contact_name' => 'naam',
            'contact_phone' => 'telefoon', 'contact_email' => 'e-mail', 'venue_name' => 'venue',
            'venue_city' => 'plaats', 'lead_description' => 'beschrijving',
        ];
        $nullable = ['contact_phone', 'contact_email', 'venue_city'];
        $sets     = [];
        $changed  = [];
        foreach ($new as $f => $val) {
            if ((string)$val === (string)($lead->$f ?? '')) continue;
            $changed[] = $field_labels[$f];
            $sets[] = ($val === '' && in_array($f, $nullable, true))
                    ? "$f = NULL"
                    : "$f = '" . mysqli_real_escape_string($cn, (string)$val) . "'";
        }
        if (empty($sets)) {
            echo json_encode(['success' => false, 'error' => 'Niets gewijzigd']); exit;
        }
        mysqli_query($cn, "UPDATE campaign_lead SET " . implode(', ', $sets) . ", lead_updated = NOW() WHERE lead_id = '$lead_id'");
        $txt_sql = mysqli_real_escape_string($cn, "Gegevens bijgewerkt: " . implode(', ', $changed));
        add_lead_response($lead_id, $client_id, "response_type = 'system', response_text = '$txt_sql'");
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Onbekende actie']);
}

// -------------------------------------------------------------------
// Tijdlijnregel toevoegen + lead_updated bijwerken. $sets is al ge-escapete SQL.
function add_lead_response($lead_id, $client_id, $sets) {
    global $cn;
    $lid = (int)$lead_id;
    mysqli_query($cn, "INSERT INTO campaign_lead_response SET
        lead_id          = '$lid',
        client_id        = '" . (int)$client_id . "',
        $sets,
        response_created = NOW()");
    mysqli_query($cn, "UPDATE campaign_lead SET lead_updated = NOW() WHERE lead_id = '$lid'");
}

// Mail aan alle toegewezen beheerders behalve degene die de actie deed
function notify_lead_assignees($lead, $actor_client_id, $subject_suffix, $body) {
    global $cn;
    $lid = (int)$lead->lead_id;
    $res = mysqli_query($cn, "SELECT c.client_id, c.client_name, c.client_email
                                FROM campaign_lead_assignee a
                                JOIN photobooth_stat_client c ON c.client_id = a.client_id
                               WHERE a.lead_id = '$lid' AND c.client_active = 1");
    if (!$res) return;
    while ($a = $res->fetch_object()) {
        if ((int)$a->client_id === (int)$actor_client_id) continue;
        send_lead_email($a->client_email, $a->client_name, $lead, $subject_suffix, $body);
    }
}

function send_lead_email($to_email, $to_name, $lead, $subject_suffix, $body_extra) {
    if (!$to_email) return false;
    $url   = "https://stats.magicshoot.nl/page_lead_detail.php?lead_id=" . (int)$lead->lead_id;
    $venue = $lead->venue_name . ($lead->venue_city ? ", " . $lead->venue_city : "");

    $body  = "Hallo $to_name,\n\n";
    $body .= $body_extra . "\n\n";
    $body .= "Campagne-reactie #{$lead->lead_id}: $venue\n";
    $body .= "Contact: {$lead->contact_name}\n";
    $body .= "Bekijk: $url\n\n";
    $body .= "— Photobooth Beheer";

    $headers  = "From: support@magicshoot.nl\r\n";
    $headers .= "Reply-To: support@magicshoot.nl\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    $subject = "Campagne-reactie #{$lead->lead_id} ($venue): $subject_suffix";
    return mail($to_email, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}
