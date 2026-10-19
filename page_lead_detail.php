<?php
# ---------------------------------------------------------------------------
#  page_lead_detail.php — één campagne-reactie: gegevens, tijdlijn, beheer.
#  Admin only. Opzet gelijk aan page_ticket_detail.php; alle acties gaan via
#  ajax_lead_action.php.
#
#  "Reactie aan contact vastleggen" VERSTUURT NIETS naar het contact (besluit
#  gebruiker 14-09-2026): je antwoordt via je eigen kanaal en legt hier vast
#  wat je zei. Alleen beheerders die aan de lead hangen krijgen interne mail.
# ---------------------------------------------------------------------------

# rolcheck vóór partial_header, zodat header() nog kan redirecten
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if ( !isset($_SESSION['client_id']) || $_SESSION['client_role'] != 'admin' ) {
    header('Location: https://stats.magicshoot.nl/index.php');
    exit;
}

global $cn;
include '../inc/connection.inc.php';
include_once __DIR__ . '/inc/lead_functions.php';

$client_id = (int)$_SESSION['client_id'];
$lead_id   = (int)($_GET['lead_id'] ?? 0);
if (!$lead_id) { header("Location: page_leads.php"); exit; }

$res = mysqli_query($cn, "SELECT l.*, c.campaign_name, cr.client_name AS creator_name
                            FROM campaign_lead l
                       LEFT JOIN campaign c ON c.campaign_id = l.campaign_id
                       LEFT JOIN photobooth_stat_client cr ON cr.client_id = l.created_by_client_id
                           WHERE l.lead_id = '$lead_id'");
if (!$res || mysqli_num_rows($res) === 0) { header("Location: page_leads.php"); exit; }
$lead = $res->fetch_object();

$status_labels   = lead_status_labels();
$channel_labels  = lead_channel_labels();
$reply_channels  = lead_reply_channel_labels();
$score_labels    = lead_score_labels();

$sl = $status_labels[$lead->lead_status]   ?? ['label' => $lead->lead_status,  'color' => '#999'];
$kl = $channel_labels[$lead->lead_channel] ?? ['label' => $lead->lead_channel, 'color' => '#999'];

// Tijdlijn
$responses = [];
$rres = mysqli_query($cn, "SELECT r.*, c.client_name
                             FROM campaign_lead_response r
                        LEFT JOIN photobooth_stat_client c ON c.client_id = r.client_id
                            WHERE r.lead_id = '$lead_id'
                         ORDER BY r.response_created ASC, r.response_id ASC");
if ($rres) while ($row = $rres->fetch_object()) $responses[] = $row;

// Toegewezen beheerders
$assignee_ids   = [];
$assignee_names = [];
$ares = mysqli_query($cn, "SELECT c.client_id, c.client_name
                             FROM campaign_lead_assignee a
                             JOIN photobooth_stat_client c ON c.client_id = a.client_id
                            WHERE a.lead_id = '$lead_id'");
if ($ares) while ($row = $ares->fetch_object()) {
    $assignee_ids[]   = (int)$row->client_id;
    $assignee_names[] = $row->client_name;
}

$all_admins = [];
$adm_res = mysqli_query($cn, "SELECT client_id, client_name FROM photobooth_stat_client
                               WHERE client_role = 'admin' AND client_active = 1 ORDER BY client_name ASC");
if ($adm_res) while ($row = $adm_res->fetch_object()) $all_admins[] = $row;

// Alle campagnes (ook inactieve: de lead kan in een inmiddels inactieve campagne zitten)
$campaigns = [];
$cres = mysqli_query($cn, "SELECT campaign_id, campaign_name, campaign_active FROM campaign
                            ORDER BY campaign_active DESC, campaign_start DESC, campaign_id DESC");
if ($cres) while ($row = $cres->fetch_object()) $campaigns[] = $row;

// Dubbele contacten: zelfde e-mail, of zelfde telefoonnummer na normaliseren
$duplicates = [];
$phone_key  = lead_phone_key($lead->contact_phone);
$email_cond = $lead->contact_email ? " OR contact_email = '" . mysqli_real_escape_string($cn, $lead->contact_email) . "'" : '';
$dres = mysqli_query($cn, "SELECT lead_id, contact_name, contact_phone, contact_email, venue_name, lead_status
                             FROM campaign_lead
                            WHERE lead_id <> '$lead_id'
                              AND ((contact_phone IS NOT NULL AND contact_phone <> '')$email_cond)
                         ORDER BY lead_id DESC");
if ($dres) while ($row = $dres->fetch_object()) {
    $same_mail  = $lead->contact_email && strcasecmp((string)$row->contact_email, $lead->contact_email) === 0;
    $same_phone = $phone_key !== '' && lead_phone_key($row->contact_phone) === $phone_key;
    if ($same_mail || $same_phone) $duplicates[] = $row;
}

// Standaard antwoordkanaal = het kanaal waarlangs de reactie binnenkwam
$default_reply_channel = isset($reply_channels[$lead->lead_channel]) ? $lead->lead_channel : 'phone';
$tel_href = preg_replace('/[^0-9+]/', '', (string)$lead->contact_phone);

include("partial_header.php");
include("partial_nav.php");
?>
    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="page_leads.php">Campagne reacties</a></li>
        <li class="breadcrumb-item active">Reactie #<?php echo $lead_id; ?></li>
      </ol>
    </div>
  </header>

  <style>
    .badge-status { padding: 5px 12px; border-radius: 4px; font-size: 0.82rem; font-weight: 600; color: #fff; display: inline-block; }
    .lead-header-card .card-header { background: #3c4b64; color: #fff; }
    .lead-meta span { font-size: 0.83rem; color: #768192; margin-right: 20px; display: inline-block; margin-bottom: 4px; }
    .lead-meta svg { width: 14px; height: 14px; vertical-align: middle; margin-right: 4px; }
    .lead-stars { color: #f9b115; letter-spacing: 1px; white-space: nowrap; font-size: 1.05rem; }
    .lead-stars-off { color: #d8dbe0; }
    .lead-header-card .card-header .lead-stars-off { color: #8a93a2; }

    /* Tijdlijn (zelfde als page_ticket_detail.php) */
    .timeline { position: relative; padding: 0; list-style: none; }
    .timeline::before { content:''; position:absolute; left:28px; top:0; bottom:0; width:2px; background:#e4e7ea; }
    .timeline-item { display: flex; gap: 16px; margin-bottom: 20px; position: relative; }
    .tl-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                 font-weight: 700; font-size: 0.8rem; color: #fff; flex-shrink: 0; z-index: 1; }
    .tl-body { flex: 1; background: #fff; border: 1px solid #e4e7ea; border-radius: 6px; padding: 12px 16px; min-width: 0; }
    .tl-body.system-msg { background: #f8f9fa; border-style: dashed; }
    .tl-body.status-msg { background: #fffbe6; border-color: #f9b115; }
    .tl-body.assign-msg { background: #f0f4ff; border-color: #321fdb55; }
    .tl-body.score-msg  { background: #fdf0ff; border-color: #9b59b655; }
    .tl-body.reply-pos  { background: #eafaf1; border-color: #2eb85c; }
    .tl-body.reply-neg  { background: #fdf1ea; border-color: #e67e22; }
    .tl-meta { font-size: 0.78rem; color: #768192; margin-bottom: 4px; }
    .tl-meta strong { color: #3c4b64; }
    .tl-text { font-size: 0.9rem; color: #3c4b64; white-space: pre-wrap; }
    .tl-type-badge { font-size: 0.68rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 600;
                     padding: 2px 6px; border-radius: 3px; margin-left: 8px; vertical-align: middle; color: #fff; }

    /* Beheerpaneel */
    .action-card .card-header { background: #321fdb; color: #fff; }
    .section-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #768192; letter-spacing: .06em; margin-bottom: 8px; margin-top: 16px; }
    .assign-checkbox label { font-size: 0.88rem; }
    .info-block { background: #f0f4ff; border: 1px solid #321fdb33; border-radius: 6px; padding: 8px 12px; font-size: 0.8rem; color: #3c4b64; }
    .reply-choice .btn { flex: 1; }
  </style>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">

          <?php if (isset($_GET['new'])) { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <strong>Reactie vastgelegd.</strong> Volgende stap: de venue onderzoeken en een score geven.
              <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
          <?php } ?>

          <?php if (!empty($duplicates)) { ?>
            <div class="alert alert-warning">
              <strong>Let op:</strong> dit contact komt vaker voor —
              <?php foreach ($duplicates as $i => $d) {
                $dsl = $status_labels[$d->lead_status]['label'] ?? $d->lead_status; ?>
                <?php echo $i ? ', ' : ''; ?><a href="page_lead_detail.php?lead_id=<?php echo $d->lead_id; ?>">#<?php echo $d->lead_id; ?></a>
                (<?php echo htmlspecialchars($d->venue_name); ?>, <?php echo htmlspecialchars($dsl); ?>)
              <?php } ?>
            </div>
          <?php } ?>

          <!-- Kop -->
          <div class="card lead-header-card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:8px">
              <h4 class="mb-0">#<?php echo $lead_id; ?> — <?php echo htmlspecialchars($lead->venue_name); ?><?php echo $lead->venue_city ? ', ' . htmlspecialchars($lead->venue_city) : ''; ?></h4>
              <div class="d-flex align-items-center" style="gap:8px">
                <?php echo lead_score_stars($lead->venue_score); ?>
                <?php echo lead_badge($sl['label'], $sl['color']); ?>
              </div>
            </div>
            <div class="card-body">
              <div class="lead-meta d-flex flex-wrap mb-3">
                <span>
                  <svg class="c-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-user"></use></svg>
                  <strong>Contact:</strong> <?php echo htmlspecialchars($lead->contact_name); ?>
                </span>
                <?php if ($lead->contact_phone) { ?>
                <span>
                  <svg class="c-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-phone"></use></svg>
                  <a href="tel:<?php echo htmlspecialchars($tel_href); ?>"><?php echo htmlspecialchars($lead->contact_phone); ?></a>
                  <?php if ($phone_key !== '') { ?>
                    · <a href="https://wa.me/<?php echo $phone_key; ?>" target="_blank" rel="noopener">WhatsApp</a>
                  <?php } ?>
                </span>
                <?php } ?>
                <?php if ($lead->contact_email) { ?>
                <span>
                  <svg class="c-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-envelope-closed"></use></svg>
                  <a href="mailto:<?php echo htmlspecialchars($lead->contact_email); ?>"><?php echo htmlspecialchars($lead->contact_email); ?></a>
                </span>
                <?php } ?>
                <?php if ($lead->venue_url) { ?>
                <span>
                  <svg class="c-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-globe-alt"></use></svg>
                  <a href="<?php echo htmlspecialchars($lead->venue_url); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(lead_url_label($lead->venue_url)); ?></a>
                </span>
                <?php } ?>
                <span>
                  <svg class="c-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-bullhorn"></use></svg>
                  <strong>Campagne:</strong> <?php echo htmlspecialchars($lead->campaign_name ?? '—'); ?>
                  via <?php echo lead_badge($kl['label'], $kl['color']); ?>
                </span>
                <span>
                  <svg class="c-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-calendar"></use></svg>
                  <strong>Aangemaakt:</strong> <?php echo date('d M Y H:i', strtotime($lead->lead_created)); ?>
                  door <?php echo htmlspecialchars($lead->creator_name ?? '—'); ?>
                </span>
                <?php if (!empty($assignee_names)) { ?>
                <span>
                  <svg class="c-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-people"></use></svg>
                  <strong>Toegewezen:</strong> <?php echo htmlspecialchars(implode(', ', $assignee_names)); ?>
                </span>
                <?php } ?>
              </div>
              <div style="background:#f8f9fa;border-radius:5px;padding:14px;font-size:0.92rem;white-space:pre-wrap;border:1px solid #e4e7ea;text-align:left"><?php echo htmlspecialchars($lead->lead_description); ?></div>
            </div>
          </div>

          <div class="row">
            <!-- Tijdlijn -->
            <div class="col-lg-8">
              <div class="card mb-4">
                <div class="card-header"><strong>Tijdlijn</strong></div>
                <div class="card-body">
                  <ul class="timeline">
                    <?php foreach ($responses as $r) {
                      $rname      = $r->client_name ?: 'Systeem';
                      $body_class = 'tl-body';
                      $type_badge = '';
                      $sub_line   = '';
                      if ($r->response_type === 'system') {
                          $body_class .= ' system-msg';
                          $type_badge  = '<span class="tl-type-badge" style="background:#6c757d">Systeem</span>';
                      } elseif ($r->response_type === 'status_change') {
                          $body_class .= ' status-msg';
                          $new_info    = $status_labels[$r->new_status] ?? ['label' => $r->new_status, 'color' => '#999'];
                          $old_lbl     = $status_labels[$r->old_status]['label'] ?? $r->old_status;
                          $type_badge  = '<span class="tl-type-badge" style="background:' . $new_info['color'] . '">' . htmlspecialchars($new_info['label']) . '</span>';
                          $sub_line    = 'Status: ' . htmlspecialchars($old_lbl) . ' → ' . htmlspecialchars($new_info['label']);
                      } elseif ($r->response_type === 'assignment') {
                          $body_class .= ' assign-msg';
                          $type_badge  = '<span class="tl-type-badge" style="background:#321fdb">Toewijzing</span>';
                      } elseif ($r->response_type === 'score_change') {
                          $body_class .= ' score-msg';
                          $type_badge  = '<span class="tl-type-badge" style="background:#9b59b6">Venue score</span>';
                      } elseif ($r->response_type === 'contact_reply') {
                          $pos         = ($r->new_status === 'replied_positive');
                          $body_class .= $pos ? ' reply-pos' : ' reply-neg';
                          $via         = $reply_channels[$r->reply_channel] ?? $r->reply_channel;
                          $type_badge  = '<span class="tl-type-badge" style="background:' . ($pos ? '#2eb85c' : '#e67e22') . '">'
                                       . ($pos ? 'Positief' : 'Negatief') . ' gereageerd via ' . htmlspecialchars($via) . '</span>';
                      }
                    ?>
                    <li class="timeline-item">
                      <div class="tl-avatar" style="background-color:<?php echo lead_avatar_color($rname); ?>; min-width:40px"><?php echo lead_initials($rname); ?></div>
                      <div class="<?php echo $body_class; ?>">
                        <div class="tl-meta">
                          <strong><?php echo htmlspecialchars($rname); ?></strong>
                          <?php echo $type_badge; ?>
                          <span class="ml-2"><?php echo date('d M Y H:i', strtotime($r->response_created)); ?></span>
                        </div>
                        <?php if ($sub_line) { ?>
                          <div class="small text-muted mb-1"><?php echo $sub_line; ?></div>
                        <?php } ?>
                        <?php if ($r->response_text !== null && $r->response_text !== '') { ?>
                          <div class="tl-text<?php echo in_array($r->response_type, ['assignment', 'system'], true) ? ' text-muted' : ''; ?>"><?php echo htmlspecialchars($r->response_text); ?></div>
                        <?php } ?>
                      </div>
                    </li>
                    <?php } ?>
                    <?php if (empty($responses)) { ?>
                    <li class="text-muted text-center py-3">Nog geen activiteit.</li>
                    <?php } ?>
                  </ul>
                </div>
              </div>

              <!-- Notitie -->
              <div class="card mb-4">
                <div class="card-header"><strong>Notitie toevoegen</strong> <small class="text-muted">— bijv. bevindingen uit het venue-onderzoek</small></div>
                <div class="card-body">
                  <textarea id="note-text" class="form-control mb-3" rows="4" placeholder="Interne notitie..."></textarea>
                  <button class="btn btn-primary" onclick="submitAction('add_note')">
                    <svg class="c-icon mr-1"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-send"></use></svg>
                    Notitie opslaan
                  </button>
                  <span id="note-feedback" class="ml-3 text-muted small"></span>
                </div>
              </div>
            </div>

            <!-- Beheerpaneel -->
            <div class="col-lg-4">
              <div class="card action-card mb-3">
                <div class="card-header"><strong>Beheer</strong></div>
                <div class="card-body">

                  <!-- 1. Toewijzen -->
                  <div class="section-label mt-0">Toewijzen aan</div>
                  <div class="assign-checkbox mb-2">
                    <?php foreach ($all_admins as $adm) { ?>
                    <div class="form-check">
                      <input class="form-check-input assign-check" type="checkbox"
                             id="adm_<?php echo $adm->client_id; ?>"
                             value="<?php echo $adm->client_id; ?>"
                             <?php echo in_array((int)$adm->client_id, $assignee_ids, true) ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="adm_<?php echo $adm->client_id; ?>">
                        <?php echo htmlspecialchars($adm->client_name); ?>
                        <?php echo ((int)$adm->client_id === $client_id) ? ' <span class="text-muted">(jij)</span>' : ''; ?>
                      </label>
                    </div>
                    <?php } ?>
                  </div>
                  <button class="btn btn-sm btn-info btn-block" onclick="submitAction('assign')">Toewijzing opslaan</button>

                  <hr>

                  <!-- 2. Venue score -->
                  <div class="section-label">Venue score</div>
                  <select id="new-score" class="form-control form-control-sm mb-2">
                    <option value="0" <?php echo !$lead->venue_score ? 'selected' : ''; ?>>— Nog geen score —</option>
                    <?php foreach ($score_labels as $val => $lbl) { ?>
                    <option value="<?php echo $val; ?>" <?php echo (int)$lead->venue_score === $val ? 'selected' : ''; ?>>
                      <?php echo str_repeat('★', $val) . str_repeat('☆', 5 - $val) . '  ' . $val . ' – ' . $lbl; ?>
                    </option>
                    <?php } ?>
                  </select>
                  <textarea id="score-note" class="form-control form-control-sm mb-2" rows="2" placeholder="Optioneel: waarom deze score?"></textarea>
                  <button class="btn btn-sm btn-secondary btn-block" onclick="submitAction('set_score')">Score opslaan</button>

                  <hr>

                  <!-- 3. Reactie aan contact -->
                  <div class="section-label">Reactie aan contact vastleggen</div>
                  <div class="btn-group btn-group-toggle d-flex reply-choice mb-2">
                    <label class="btn btn-sm btn-outline-success active">
                      <input type="radio" name="reply-outcome" value="positive" checked> Positief
                    </label>
                    <label class="btn btn-sm btn-outline-warning">
                      <input type="radio" name="reply-outcome" value="negative"> Negatief
                    </label>
                  </div>
                  <select id="reply-channel" class="form-control form-control-sm mb-2">
                    <?php foreach ($reply_channels as $val => $lbl) { ?>
                    <option value="<?php echo $val; ?>" <?php echo $default_reply_channel === $val ? 'selected' : ''; ?>>via <?php echo $lbl; ?></option>
                    <?php } ?>
                  </select>
                  <textarea id="reply-text" class="form-control form-control-sm mb-2" rows="3" placeholder="Wat heb je gezegd / afgesproken?"></textarea>
                  <div class="info-block mb-2">
                    Wordt <strong>niet</strong> naar het contact verstuurd — alleen vastgelegd.
                    <?php if (lead_reply_moves_status($lead->lead_status)) { ?>
                      De status gaat mee naar <em>Positief</em> of <em>Negatief gereageerd</em>.
                    <?php } else { ?>
                      De status blijft <em><?php echo htmlspecialchars($sl['label']); ?></em>; deze lead is al verder.
                    <?php } ?>
                  </div>
                  <button class="btn btn-sm btn-success btn-block" onclick="submitAction('record_reply')">Reactie vastleggen</button>

                  <hr>

                  <!-- 4. Status -->
                  <div class="section-label">Status wijzigen</div>
                  <select id="new-status" class="form-control form-control-sm mb-2">
                    <?php foreach ($status_labels as $val => $info) { ?>
                    <option value="<?php echo $val; ?>" <?php echo $lead->lead_status === $val ? 'selected' : ''; ?>><?php echo $info['label']; ?></option>
                    <?php } ?>
                  </select>
                  <textarea id="status-message" class="form-control form-control-sm mb-2" rows="2" placeholder="Optioneel: toelichting bij deze statuswijziging..."></textarea>
                  <button class="btn btn-sm btn-warning btn-block" onclick="submitAction('change_status')">Status opslaan</button>

                  <div id="admin-feedback" class="mt-2 text-center small text-danger"></div>
                </div>
              </div>

              <!-- 5. Gegevens bewerken -->
              <div class="card mb-3">
                <div class="card-header py-2">
                  <a href="#" onclick="document.getElementById('edit-body').hidden = !document.getElementById('edit-body').hidden; return false;">
                    <svg class="c-icon mr-1"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-pencil"></use></svg>
                    Gegevens bewerken
                  </a>
                </div>
                <div class="card-body" id="edit-body" hidden>
                  <div class="form-group mb-2">
                    <label class="small mb-0"><strong>Campagne</strong></label>
                    <select id="edit-campaign_id" class="form-control form-control-sm">
                      <?php foreach ($campaigns as $c) { ?>
                        <option value="<?php echo $c->campaign_id; ?>" <?php echo (int)$lead->campaign_id === (int)$c->campaign_id ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($c->campaign_name) . ($c->campaign_active ? '' : ' (inactief)'); ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>
                  <div class="form-group mb-2">
                    <label class="small mb-0"><strong>Binnengekomen via</strong></label>
                    <select id="edit-lead_channel" class="form-control form-control-sm">
                      <?php foreach ($channel_labels as $val => $info) { ?>
                        <option value="<?php echo $val; ?>" <?php echo $lead->lead_channel === $val ? 'selected' : ''; ?>><?php echo $info['label']; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                  <?php
                  $edit_fields = [
                      'contact_name'  => 'Naam contactpersoon',
                      'contact_phone' => 'Telefoon',
                      'contact_email' => 'E-mail',
                      'venue_name'    => 'Venue',
                      'venue_city'    => 'Plaats',
                      'venue_url'     => 'Website venue',
                  ];
                  foreach ($edit_fields as $f => $lbl) { ?>
                  <div class="form-group mb-2">
                    <label class="small mb-0"><strong><?php echo $lbl; ?></strong></label>
                    <input type="text" id="edit-<?php echo $f; ?>" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)$lead->$f); ?>">
                  </div>
                  <?php } ?>
                  <div class="form-group mb-2">
                    <label class="small mb-0"><strong>Beschrijving</strong></label>
                    <textarea id="edit-lead_description" class="form-control form-control-sm" rows="4"><?php echo htmlspecialchars($lead->lead_description); ?></textarea>
                  </div>
                  <button class="btn btn-sm btn-outline-primary btn-block" onclick="submitAction('edit_lead')">Gegevens opslaan</button>
                  <div id="edit-feedback" class="mt-2 text-center small text-danger"></div>
                </div>
              </div>

              <div class="card border-0" style="background:#f8f9fa;font-size:0.82rem">
                <div class="card-body py-2">
                  <div><strong>Laatste update:</strong> <?php echo date('d M Y H:i', strtotime($lead->lead_updated)); ?></div>
                  <div><strong>Reactie ID:</strong> #<?php echo $lead_id; ?></div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </main>
    <footer class="c-footer">
      <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> &copy; <?php echo date('Y'); ?> MagicShoot.</div>
      <div class="ml-auto">Powered by&nbsp;<a href="https://www.MagicShoot.nl/">MagicShoot</a></div>
    </footer>
  </div>
</div>

<script>
var LEAD_ID = <?php echo $lead_id; ?>;

// Positief/Negatief-knoppen: 'active' zelf bijhouden, niet afhankelijk van de bootstrap button-plugin
document.querySelectorAll('input[name="reply-outcome"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.reply-choice .btn').forEach(function(lbl) {
            lbl.classList.toggle('active', lbl.querySelector('input').checked);
        });
    });
});

function submitAction(action) {
    var data = { action: action, lead_id: LEAD_ID };
    var feedbackEl = document.getElementById(
        action === 'add_note' ? 'note-feedback' : (action === 'edit_lead' ? 'edit-feedback' : 'admin-feedback'));
    feedbackEl.textContent = '';

    if (action === 'add_note') {
        data.message = document.getElementById('note-text').value.trim();
        if (!data.message) { feedbackEl.textContent = 'Vul een notitie in.'; return; }
    } else if (action === 'assign') {
        var checked = [];
        document.querySelectorAll('.assign-check:checked').forEach(function(el) { checked.push(el.value); });
        data.assignees = checked.join(',');
    } else if (action === 'set_score') {
        data.score   = document.getElementById('new-score').value;
        data.message = document.getElementById('score-note').value.trim();
    } else if (action === 'record_reply') {
        data.outcome       = document.querySelector('input[name="reply-outcome"]:checked').value;
        data.reply_channel = document.getElementById('reply-channel').value;
        data.message       = document.getElementById('reply-text').value.trim();
    } else if (action === 'change_status') {
        data.new_status = document.getElementById('new-status').value;
        data.message    = document.getElementById('status-message').value.trim();
    } else if (action === 'edit_lead') {
        ['campaign_id', 'lead_channel', 'contact_name', 'contact_phone', 'contact_email',
         'venue_name', 'venue_city', 'venue_url', 'lead_description'].forEach(function(f) {
            data[f] = document.getElementById('edit-' + f).value.trim();
        });
    }

    var formData = new FormData();
    for (var k in data) formData.append(k, data[k]);

    fetch('ajax_lead_action.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            if (json.success) {
                setTimeout(function() { window.location.reload(); }, 300);
            } else {
                feedbackEl.textContent = 'Fout: ' + (json.error || 'onbekend');
            }
        })
        .catch(function() {
            feedbackEl.textContent = 'Verbindingsfout, probeer opnieuw.';
        });
}
</script>

<?php include("partial_footer.php"); ?>
