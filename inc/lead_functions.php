<?php
# ---------------------------------------------------------------------------
#  inc/lead_functions.php — gedeelde labels en helpers voor "Campagne reacties"
#  (page_leads.php, page_lead_new.php, page_lead_detail.php, page_campaigns.php,
#  ajax_lead_action.php).
#
#  De statussen en kanalen staan HIER en nergens anders, plus in de ENUMs van
#  de tabellen (stats/database structuur/campaign_lead.sql). Voeg je een status
#  of kanaal toe, pas dan beide aan — een waarde die alleen hier staat wordt
#  door MariaDB bij het opslaan geweigerd of leeg gemaakt.
# ---------------------------------------------------------------------------

function lead_status_labels() {
    return [
        'new'               => ['label' => 'Nieuw',                    'color' => '#e55353'],
        'investigating'     => ['label' => 'Venue onderzoeken',        'color' => '#321fdb'],
        'replied_positive'  => ['label' => 'Positief gereageerd',      'color' => '#2eb85c'],
        'replied_negative'  => ['label' => 'Negatief gereageerd',      'color' => '#e67e22'],
        'placement_planned' => ['label' => 'Plaatsing ingepland',      'color' => '#9b59b6'],
        'closed_placed'     => ['label' => 'Gesloten – geplaatst',     'color' => '#1b8a45'],
        'closed_not_placed' => ['label' => 'Gesloten – geen plaatsing','color' => '#343a40'],
    ];
}

// Hoe de reactie binnenkwam
function lead_channel_labels() {
    return [
        'instagram' => ['label' => 'Instagram', 'color' => '#c13584'],
        'facebook'  => ['label' => 'Facebook',  'color' => '#3b5998'],
        'email'     => ['label' => 'E-mail',    'color' => '#17a2b8'],
        'phone'     => ['label' => 'Telefoon',  'color' => '#f9b115'],
        'other'     => ['label' => 'Anders',    'color' => '#6c757d'],
    ];
}

// Hoe wij het contact ons antwoord hebben gegeven (alleen vastgelegd, niet verstuurd)
function lead_reply_channel_labels() {
    return [
        'phone'     => 'Telefoon',
        'whatsapp'  => 'WhatsApp',
        'instagram' => 'Instagram',
        'facebook'  => 'Facebook',
        'email'     => 'E-mail',
        'in_person' => 'Persoonlijk',
    ];
}

// Gesloten = status begint met closed_. Alles daarvoor telt als open.
function lead_is_closed($status) {
    return strpos((string)$status, 'closed_') === 0;
}

// Het "reageren"-stadium: alleen hier zet een vastgelegde reactie ook de status om.
// Staat de lead al verder (ingepland / gesloten), dan wordt de reactie alleen gelogd.
function lead_reply_moves_status($status) {
    return in_array($status, ['new', 'investigating', 'replied_positive', 'replied_negative'], true);
}

function lead_score_labels() {
    return [1 => 'Slecht', 2 => 'Matig', 3 => 'Redelijk', 4 => 'Goed', 5 => 'Uitstekend'];
}

function lead_score_stars($score) {
    $score = (int)$score;
    if ($score < 1) return '<span class="text-muted">—</span>';
    return '<span class="lead-stars" title="Score ' . $score . '/5">'
         . str_repeat('★', $score) . '<span class="lead-stars-off">' . str_repeat('★', 5 - $score) . '</span></span>';
}

// Telefoonnummer terugbrengen tot alleen cijfers met landcode, om dubbele
// contacten te herkennen: "06-12 34 56 78", "+31612345678" en "0031612345678"
// worden allemaal "31612345678".
function lead_phone_key($phone) {
    $digits = preg_replace('/[^0-9]/', '', (string)$phone);
    if ($digits === '') return '';
    if (substr($digits, 0, 2) === '00') return substr($digits, 2);
    if (substr($digits, 0, 1) === '0')  return '31' . substr($digits, 1);
    return $digits;
}

function lead_initials($name) {
    $parts = explode(' ', trim($name));
    $ini = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $ini .= strtoupper(substr(end($parts), 0, 1));
    return $ini;
}

function lead_avatar_color($name) {
    $colors = ['#321fdb','#e55353','#2eb85c','#f9b115','#9b59b6','#17a2b8','#e67e22','#e91e63'];
    return $colors[crc32($name) % count($colors)];
}

function lead_badge($label, $color) {
    return '<span class="badge-status" style="background-color:' . $color . '">' . htmlspecialchars($label) . '</span>';
}

// Normaliseert regeleinden zoals fix_line_endings() in ajax_ticket_action.php
function lead_clean_text($text) {
    $text = str_replace("\\r\\n", "\n", (string)$text);
    $text = str_replace(["\\r", "\\n"], "\n", $text);
    return trim(str_replace(["\r\n", "\r"], "\n", $text));
}
