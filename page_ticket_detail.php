<?php
include("partial_header.php");
include("partial_nav.php");
include("inc/invul_functions.php");

$client_id   = (int)$_SESSION['client_id'];
$client_role = $_SESSION['client_role'];
$client_name = $_SESSION['client_name'];

$ticket_id = (int)($_GET['ticket_id'] ?? 0);
if (!$ticket_id) { header("Location: page_tickets.php"); exit; }

$ticket_id_safe = $ticket_id;

// Fetch ticket
$sql = "SELECT t.*, c.client_name AS creator_name, c.client_email AS creator_email,
               e.name AS event_name
          FROM support_ticket t
     LEFT JOIN photobooth_stat_client c ON c.client_id = t.created_by_client_id
     LEFT JOIN photobooth_event_ids e ON e.event_id = t.event_id
         WHERE t.ticket_id = '$ticket_id_safe'";
$res = mysqli_query($cn, $sql);
if (!$res || mysqli_num_rows($res) === 0) { header("Location: page_tickets.php"); exit; }
$ticket = $res->fetch_object();

// Access control: non-admin can only view own tickets
if ($client_role !== 'admin' && (int)$ticket->created_by_client_id !== $client_id) {
    header("Location: page_tickets.php"); exit;
}

$status_labels = [
    'new'              => ['label' => 'Nieuw',             'color' => '#e55353'],
    'assigned'         => ['label' => 'Toegewezen',        'color' => '#f9b115'],
    'in_progress'      => ['label' => 'In behandeling',    'color' => '#321fdb'],
    'planned_remote'   => ['label' => 'Ingepland remote',  'color' => '#9b59b6'],
    'pending_purchase' => ['label' => 'Wacht op hardware', 'color' => '#e67e22'],
    'pending_client'   => ['label' => 'Wacht op klant',    'color' => '#17a2b8'],
    'on_hold'          => ['label' => 'On hold',           'color' => '#6c757d'],
    'closed'           => ['label' => 'Gesloten',          'color' => '#2eb85c'],
    'cancelled'        => ['label' => 'Geannuleerd',       'color' => '#343a40'],
    'reopened'         => ['label' => 'Opnieuw geopend',   'color' => '#e67e22'],
];
$priority_labels = [
    'low'    => ['label' => 'Laag',    'color' => '#6c757d'],
    'normal' => ['label' => 'Normaal', 'color' => '#321fdb'],
    'high'   => ['label' => 'Hoog',    'color' => '#f9b115'],
    'urgent' => ['label' => 'Urgent',  'color' => '#e55353'],
];

$sl = $status_labels[$ticket->ticket_status]   ?? ['label' => $ticket->ticket_status,   'color' => '#999'];
$pl = $priority_labels[$ticket->ticket_priority] ?? ['label' => $ticket->ticket_priority, 'color' => '#999'];

// Fetch timeline
$responses = [];
$resp_res = mysqli_query($cn, "SELECT r.*, c.client_name, c.client_role AS resp_role
                                 FROM support_ticket_response r
                            LEFT JOIN photobooth_stat_client c ON c.client_id = r.client_id
                                WHERE r.ticket_id = '$ticket_id_safe'
                                ORDER BY r.response_created ASC");
while ($row = $resp_res->fetch_object()) $responses[] = $row;

// Fetch current assignees
$assignee_ids = [];
$assignees_names = [];
$asgn_res = mysqli_query($cn, "SELECT c.client_id, c.client_name
                                  FROM support_ticket_assignee a
                                  JOIN photobooth_stat_client c ON c.client_id = a.client_id
                                 WHERE a.ticket_id = '$ticket_id_safe'");
while ($row = $asgn_res->fetch_object()) {
    $assignee_ids[]    = (int)$row->client_id;
    $assignees_names[] = $row->client_name;
}

// Fetch all admins for admin assignment panel
$all_admins = [];
if ($client_role === 'admin') {
    $adm_res = mysqli_query($cn, "SELECT client_id, client_name FROM photobooth_stat_client WHERE client_role='admin' AND client_active=1 ORDER BY client_name ASC");
    while ($row = $adm_res->fetch_object()) $all_admins[] = $row;
}

function initials($name) {
    $parts = explode(' ', trim($name));
    $ini = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) $ini .= strtoupper(substr(end($parts), 0, 1));
    return $ini;
}

function avatar_color($name) {
    $colors = ['#321fdb','#e55353','#2eb85c','#f9b115','#9b59b6','#17a2b8','#e67e22','#e91e63'];
    return $colors[crc32($name) % count($colors)];
}
?>
    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="page_tickets.php">Support Tickets</a></li>
        <li class="breadcrumb-item active">Ticket #<?php echo $ticket_id; ?></li>
      </ol>
    </div>
  </header>

  <style>
    .badge-status { padding: 5px 12px; border-radius: 4px; font-size: 0.82rem; font-weight: 600; color: #fff; display: inline-block; }
    .ticket-header-card .card-header { background: #3c4b64; color: #fff; }
    .ticket-meta span { font-size: 0.83rem; color: #768192; margin-right: 20px; }
    .ticket-meta svg { width: 14px; height: 14px; vertical-align: middle; margin-right: 4px; }

    /* Timeline */
    .timeline { position: relative; padding: 0; list-style: none; }
    .timeline::before { content:''; position:absolute; left:28px; top:0; bottom:0; width:2px; background:#e4e7ea; }
    .timeline-item { display: flex; gap: 16px; margin-bottom: 20px; position: relative; }
    .tl-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                 font-weight: 700; font-size: 0.8rem; color: #fff; flex-shrink: 0; z-index: 1; }
    .tl-body { flex: 1; background: #fff; border: 1px solid #e4e7ea; border-radius: 6px; padding: 12px 16px; }
    .tl-body.system-msg { background: #f8f9fa; border-style: dashed; }
    .tl-body.status-msg { background: #fffbe6; border-color: #f9b115; }
    .tl-body.assign-msg { background: #f0f4ff; border-color: #321fdb55; }
    .tl-body.priority-msg { background: #fdf0ff; border-color: #9b59b655; }
    .tl-meta { font-size: 0.78rem; color: #768192; margin-bottom: 4px; }
    .tl-meta strong { color: #3c4b64; }
    .tl-text { font-size: 0.9rem; color: #3c4b64; white-space: pre-wrap; }
    .tl-type-badge { font-size: 0.68rem; text-transform: uppercase; letter-spacing: .05em; font-weight: 600;
                     padding: 2px 6px; border-radius: 3px; margin-left: 8px; vertical-align: middle; }

    /* Notification recipient row (shown below message body) */
    .tl-notify-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
                     margin-top: 8px; padding-top: 8px; border-top: 1px solid #e4e7ea; }
    .tl-notify-pill { display: inline-flex; align-items: center; gap: 3px; }
    .tl-notify-circle { display: inline-flex; align-items: center; justify-content: center;
                        width: 22px; height: 22px; border-radius: 50%; font-size: 0.6rem; font-weight: 800;
                        color: #fff; cursor: default; flex-shrink: 0; }
    .tl-notify-icon { font-size: 0.8rem; line-height: 1; color: #768192; }

    /* Action panel */
    .action-card .card-header { background: #321fdb; color: #fff; }
    .section-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #768192; letter-spacing: .06em; margin-bottom: 8px; margin-top: 16px; }
    .assign-checkbox label { font-size: 0.88rem; }
    .notify-block { background: #f0f4ff; border: 1px solid #321fdb33; border-radius: 6px; padding: 12px 16px; margin-top: 14px; }
    .notify-block .form-check-label { font-size: 0.88rem; }
  </style>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">

          <?php if (isset($_GET['new'])) { ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <strong>Ticket aangemaakt!</strong> We hebben de beheerders op de hoogte gesteld. U ontvangt updates per e-mail.
              <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
          <?php } ?>

          <!-- Ticket header -->
          <div class="card ticket-header-card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h4 class="mb-0">#<?php echo $ticket_id; ?> — <?php echo htmlspecialchars($ticket->ticket_subject); ?></h4>
              <div class="d-flex gap-2" style="gap:8px">
                <span class="badge-status" style="background-color:<?php echo $sl['color']; ?>"><?php echo $sl['label']; ?></span>
                <span class="badge-status" style="background-color:<?php echo $pl['color']; ?>"><?php echo $pl['label']; ?></span>
              </div>
            </div>
            <div class="card-body">
              <div class="ticket-meta d-flex flex-wrap mb-3">
                <span>
                  <svg class="c-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-user"></use></svg>
                  <strong>Ingediend door:</strong> <?php echo htmlspecialchars($ticket->creator_name); ?>
                </span>
                <?php if ($ticket->event_name) { ?>
                <span>
                  <svg class="c-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-camera"></use></svg>
                  <strong>Fotokast:</strong> <?php echo htmlspecialchars($ticket->event_name); ?>
                </span>
                <?php } ?>
                <span>
                  <svg class="c-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-calendar"></use></svg>
                  <strong>Aangemaakt:</strong> <?php echo date('d M Y H:i', strtotime($ticket->ticket_created)); ?>
                </span>
                <?php if (!empty($assignees_names)) { ?>
                <span>
                  <svg class="c-icon"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-people"></use></svg>
                  <strong>Toegewezen:</strong> <?php echo htmlspecialchars(implode(', ', $assignees_names)); ?>
                </span>
                <?php } ?>
              </div>
              <div style="background:#f8f9fa;border-radius:5px;padding:14px;font-size:0.92rem;white-space:pre-wrap;border:1px solid #e4e7ea;text-align:left;font-weight:700"><?php echo nl2br(htmlspecialchars($ticket->ticket_description)); ?></div>
            </div>
          </div>

          <div class="row">
            <!-- Timeline -->
            <div class="col-lg-8">
              <div class="card mb-4">
                <div class="card-header"><strong>Tijdlijn</strong></div>
                <div class="card-body">
                  <ul class="timeline">
                    <?php
                    // Load notification recipients from DB, grouped by response_id
                    $resp_ids = [];
                    foreach ($responses as $r) $resp_ids[] = (int)$r->response_id;
                    $notify_map = [];
                    if (!empty($resp_ids)) {
                        $ids_str = implode(',', $resp_ids);
                        $nres = mysqli_query($cn, "SELECT n.response_id, n.notify_type, c.client_id, c.client_name
                                                     FROM support_ticket_response_notify n
                                                     JOIN photobooth_stat_client c ON c.client_id = n.client_id
                                                    WHERE n.response_id IN ($ids_str)
                                                    ORDER BY n.sent_at ASC");
                        while ($nrow = $nres->fetch_object()) $notify_map[(int)$nrow->response_id][] = $nrow;
                    }

                    foreach ($responses as $r) {
                      $rname  = $r->client_name ?: 'Systeem';
                      $ini    = initials($rname);
                      $acolor = avatar_color($rname);
                      $body_class = 'tl-body';
                      $type_badge = '';
                      if ($r->response_type === 'system') {
                          $body_class .= ' system-msg';
                          $type_badge  = '<span class="tl-type-badge" style="background:#6c757d;color:#fff">Systeem</span>';
                      } elseif ($r->response_type === 'status_change') {
                          $body_class .= ' status-msg';
                          $sc_new_lbl = $status_labels[$r->new_status]['label'] ?? $r->new_status;
                          $sc_new_clr = $status_labels[$r->new_status]['color'] ?? '#999';
                          $type_badge  = '<span class="tl-type-badge" style="background:' . $sc_new_clr . ';color:#fff">' . htmlspecialchars($sc_new_lbl) . '</span>';
                      } elseif ($r->response_type === 'assignment') {
                          $body_class .= ' assign-msg';
                          $type_badge  = '<span class="tl-type-badge" style="background:#321fdb;color:#fff">Toewijzing</span>';
                      } elseif ($r->response_type === 'priority_change') {
                          $body_class .= ' priority-msg';
                          $type_badge  = '<span class="tl-type-badge" style="background:#9b59b6;color:#fff">Prioriteit</span>';
                      }
                    ?>
                    <li class="timeline-item">
                      <div class="tl-avatar" style="background-color:<?php echo $acolor; ?>; min-width:40px"><?php echo $ini; ?></div>
                      <div class="<?php echo $body_class; ?>">
                        <div class="tl-meta">
                          <strong><?php echo htmlspecialchars($rname); ?></strong>
                          <?php echo $type_badge; ?>
                          <span class="ml-2"><?php echo date('d M Y H:i', strtotime($r->response_created)); ?></span>
                        </div>
                        <?php if ($r->response_type === 'status_change') { ?>
                          <?php if (!empty($r->response_text)) { ?>
                          <div class="tl-text" style="white-space:normal"><span style="font-size:0.88rem"><strong>Opmerking</strong>: <?php echo htmlspecialchars($r->response_text); ?></span></div>
                          <?php } ?>
                        <?php } elseif ($r->response_type === 'assignment' || $r->response_type === 'priority_change') { ?>
                          <div class="tl-text text-muted"><?php echo htmlspecialchars($r->response_text); ?></div>
                        <?php } else { ?>
                          <div class="tl-text"><?php echo nl2br(htmlspecialchars($r->response_text)); ?></div>
                        <?php } ?>
                        <?php
                        $rid_key      = (int)$r->response_id;
                        $email_recips = [];
                        $sms_recips   = [];
                        if (!empty($notify_map[$rid_key])) {
                            foreach ($notify_map[$rid_key] as $n) {
                                if ($n->notify_type === 'email') $email_recips[] = $n;
                                else $sms_recips[] = $n;
                            }
                        }
                        if (!empty($email_recips) || !empty($sms_recips)) { ?>
                        <div class="tl-notify-row">
                          <?php if (!empty($email_recips)) {
                            $names = implode(', ', array_map(fn($n) => htmlspecialchars($n->client_name),$email_recips)); ?>
                            <span class="tl-notify-pill" title="E-mail verstuurd naar: <?php echo $names; ?>">
                              <span class="tl-notify-icon">✉</span>
                              <?php foreach ($email_recips as $n) { ?>
                              <span class="tl-notify-circle" style="background:<?php echo avatar_color($n->client_name); ?>"><?php echo initials($n->client_name); ?></span>
                              <?php } ?>
                            </span>
                          <?php } ?>
                          <?php if (!empty($sms_recips)) {
                            $names = implode(', ', array_map(fn($n) => htmlspecialchars($n->client_name),$sms_recips)); ?>
                            <span class="tl-notify-pill" title="SMS verstuurd naar: <?php echo $names; ?>">
                              <span class="tl-notify-icon">📱</span>
                              <?php foreach ($sms_recips as $n) { ?>
                              <span class="tl-notify-circle" style="background:#2eb85c"><?php echo initials($n->client_name); ?></span>
                              <?php } ?>
                            </span>
                          <?php } ?>
                        </div>
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

              <!-- Client reply panel (all roles) -->
              <?php if ($ticket->ticket_status !== 'closed' && $ticket->ticket_status !== 'cancelled') { ?>
              <div class="card mb-4" id="reply-card">
                <div class="card-header"><strong>Reageren</strong></div>
                <div class="card-body">
                  <textarea id="reply-text" class="form-control mb-3" rows="4" placeholder="Typ uw reactie..."></textarea>
                  <button class="btn btn-primary" onclick="submitAction('add_response')">
                    <svg class="c-icon mr-1"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-send"></use></svg>
                    Reactie plaatsen
                  </button>
                  <span id="reply-feedback" class="ml-3 text-muted small"></span>
                </div>
              </div>
              <?php } ?>
            </div>

            <!-- Admin action panel -->
            <?php if ($client_role === 'admin') { ?>
            <div class="col-lg-4">
              <div class="card action-card mb-3">
                <div class="card-header"><strong>Beheer</strong></div>
                <div class="card-body">

                  <!-- 1. Toewijzen -->
                  <div class="section-label">Toewijzen aan</div>
                  <div class="assign-checkbox mb-2">
                    <?php foreach ($all_admins as $adm) { ?>
                    <div class="form-check">
                      <input class="form-check-input assign-check" type="checkbox"
                             id="adm_<?php echo $adm->client_id; ?>"
                             value="<?php echo $adm->client_id; ?>"
                             <?php echo in_array((int)$adm->client_id, $assignee_ids) ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="adm_<?php echo $adm->client_id; ?>">
                        <?php echo htmlspecialchars($adm->client_name); ?>
                        <?php echo ($adm->client_id == $client_id) ? ' <span class="text-muted">(jij)</span>' : ''; ?>
                      </label>
                    </div>
                    <?php } ?>
                  </div>
                  <button class="btn btn-sm btn-info btn-block" onclick="submitAction('assign')">Toewijzing opslaan</button>

                  <hr>

                  <!-- 2. Status wijzigen -->
                  <div class="section-label">Status wijzigen</div>
                  <select id="new-status" class="form-control form-control-sm mb-2">
                    <?php foreach ($status_labels as $val => $info) { ?>
                    <option value="<?php echo $val; ?>" <?php echo $ticket->ticket_status === $val ? 'selected' : ''; ?>>
                      <?php echo $info['label']; ?>
                    </option>
                    <?php } ?>
                  </select>
                  <textarea id="status-message" class="form-control form-control-sm mb-2" rows="3" placeholder="Optioneel: voeg een bericht toe bij deze statuswijziging..."></textarea>
                  <div class="notify-block mb-2">
                    <div class="form-check mb-1">
                      <input class="form-check-input" type="checkbox" id="status-notify-email" checked>
                      <label class="form-check-label" for="status-notify-email">
                        <svg class="c-icon mr-1" style="width:14px;height:14px"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-envelope-closed"></use></svg>
                        Klant informeren per e-mail
                        <small class="text-muted d-block ml-4"><?php echo htmlspecialchars($ticket->creator_email); ?></small>
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="status-notify-sms">
                      <label class="form-check-label" for="status-notify-sms">
                        <svg class="c-icon mr-1" style="width:14px;height:14px"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-phone"></use></svg>
                        Klant informeren per SMS
                      </label>
                    </div>
                  </div>
                  <button class="btn btn-sm btn-warning btn-block" onclick="submitAction('change_status')">Status opslaan</button>

                  <hr>

                  <!-- 3. Prioriteit wijzigen -->
                  <div class="section-label">Prioriteit wijzigen</div>
                  <select id="new-priority" class="form-control form-control-sm mb-2">
                    <?php foreach ($priority_labels as $val => $info) { ?>
                    <option value="<?php echo $val; ?>" <?php echo $ticket->ticket_priority === $val ? 'selected' : ''; ?>>
                      <?php echo $info['label']; ?>
                    </option>
                    <?php } ?>
                  </select>
                  <button class="btn btn-sm btn-secondary btn-block" onclick="submitAction('change_priority')">Prioriteit opslaan</button>

                  <hr>

                  <!-- 4. Bericht toevoegen -->
                  <div class="section-label">Bericht toevoegen</div>
                  <textarea id="admin-message" class="form-control form-control-sm mb-2" rows="4" placeholder="Interne notitie of reactie naar klant..."></textarea>
                  <div class="notify-block mb-2">
                    <div class="form-check mb-1">
                      <input class="form-check-input" type="checkbox" id="admin-notify-email" checked>
                      <label class="form-check-label" for="admin-notify-email">
                        <svg class="c-icon mr-1" style="width:14px;height:14px"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-envelope-closed"></use></svg>
                        Klant informeren per e-mail
                        <small class="text-muted d-block ml-4"><?php echo htmlspecialchars($ticket->creator_email); ?></small>
                      </label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="notify-sms">
                      <label class="form-check-label" for="notify-sms">
                        <svg class="c-icon mr-1" style="width:14px;height:14px"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-phone"></use></svg>
                        SMS sturen via CM.com
                      </label>
                    </div>
                  </div>
                  <button class="btn btn-primary btn-block" onclick="submitAction('add_admin_response')">
                    <svg class="c-icon mr-1"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-send"></use></svg>
                    Bericht opslaan
                  </button>

                  <div id="admin-feedback" class="mt-2 text-center small"></div>
                </div>
              </div>

              <!-- Quick info -->
              <div class="card border-0" style="background:#f8f9fa;font-size:0.82rem">
                <div class="card-body py-2">
                  <div><strong>Laatste update:</strong> <?php echo date('d M Y H:i', strtotime($ticket->ticket_updated)); ?></div>
                  <div><strong>Ticket ID:</strong> #<?php echo $ticket_id; ?></div>
                  <div><strong>Prioriteit:</strong> <span style="color:<?php echo $pl['color'];?>;font-weight:600"><?php echo $pl['label']; ?></span></div>
                </div>
              </div>
            </div>
            <?php } ?>
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
var TICKET_ID = <?php echo $ticket_id; ?>;

function submitAction(action) {
    var data = { action: action, ticket_id: TICKET_ID };

    if (action === 'add_response') {
        var msg = document.getElementById('reply-text').value.trim();
        if (!msg) { document.getElementById('reply-feedback').textContent = 'Vul een reactie in.'; return; }
        data.message = msg;
        data.notify_email = 0;
        data.notify_sms   = 0;
    } else if (action === 'add_admin_response') {
        var msg = document.getElementById('admin-message').value.trim();
        if (!msg) { document.getElementById('admin-feedback').textContent = 'Vul een bericht in.'; return; }
        data.message      = msg;
        data.notify_email = document.getElementById('admin-notify-email').checked ? 1 : 0;
        data.notify_sms   = document.getElementById('notify-sms').checked ? 1 : 0;
        action = 'add_response';
        data.action = 'add_response';
    } else if (action === 'change_status') {
        data.new_status   = document.getElementById('new-status').value;
        data.message      = document.getElementById('status-message').value.trim();
        data.notify_email = document.getElementById('status-notify-email').checked ? 1 : 0;
        data.notify_sms   = document.getElementById('status-notify-sms').checked ? 1 : 0;
    } else if (action === 'change_priority') {
        data.new_priority = document.getElementById('new-priority').value;
    } else if (action === 'assign') {
        var checked = [];
        document.querySelectorAll('.assign-check:checked').forEach(function(el) { checked.push(el.value); });
        data.assignees = checked.join(',');
    }

    // Build form data
    var formData = new FormData();
    for (var k in data) formData.append(k, data[k]);

    var feedbackEl = document.getElementById(action === 'add_response' ? 'reply-feedback' : 'admin-feedback') || document.getElementById('admin-feedback');

    fetch('ajax_ticket_action.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            if (json.success) {
                setTimeout(function() { window.location.reload(); }, 300);
            } else {
                if (feedbackEl) feedbackEl.textContent = 'Fout: ' + (json.error || 'onbekend');
            }
        })
        .catch(function() {
            if (feedbackEl) feedbackEl.textContent = 'Verbindingsfout, probeer opnieuw.';
        });
}
</script>

<?php include("partial_footer.php"); ?>
