<?php
// Handle POST before any HTML output so header() redirect works
global $cn;
include '../inc/connection.inc.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['client_id'])) {
    header("Location: https://stats.magicshoot.nl/index.php"); exit;
}

$client_id   = (int)$_SESSION['client_id'];
$client_role = $_SESSION['client_role'];
$client_name = $_SESSION['client_name'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'new_ticket') {
    $subject_raw     = trim($_POST['subject'] ?? '');
    $description_raw = trim($_POST['description'] ?? '');
    $description_raw = str_replace("\\r\\n", "\n", $description_raw);
    $description_raw = str_replace(["\\r", "\\n", "\r\n", "\r"], "\n", $description_raw);
    $subject         = mysqli_real_escape_string($cn, $subject_raw);
    $description     = mysqli_real_escape_string($cn, $description_raw);
    $event_id    = (int)($_POST['event_id'] ?? 0);
    $priority    = mysqli_real_escape_string($cn, $_POST['priority'] ?? 'normal');
    $allowed_priorities = ['low', 'normal', 'high', 'urgent'];
    if (!in_array($priority, $allowed_priorities)) $priority = 'normal';

    if ($subject === '' || $description === '') {
        $error = 'Vul een onderwerp en beschrijving in.';
    } else {
        if ($client_role !== 'admin' && $event_id > 0) {
            $chk = mysqli_query($cn, "SELECT event_id FROM photobooth_stat_client_event WHERE client_id='$client_id' AND event_id='$event_id'");
            if (!$chk || mysqli_num_rows($chk) === 0) $event_id = 0;
        }

        $event_val = $event_id > 0 ? "'$event_id'" : 'NULL';
        $sql = "INSERT INTO support_ticket SET
                  ticket_subject        = '$subject',
                  ticket_description    = '$description',
                  created_by_client_id  = '$client_id',
                  event_id              = $event_val,
                  ticket_priority       = '$priority',
                  ticket_status         = 'new',
                  ticket_created        = NOW(),
                  ticket_updated        = NOW()";

        if (mysqli_query($cn, $sql)) {
            $ticket_id = mysqli_insert_id($cn);

            $sys_text = mysqli_real_escape_string($cn, "Ticket aangemaakt door $client_name");
            mysqli_query($cn, "INSERT INTO support_ticket_response SET
                ticket_id        = '$ticket_id',
                client_id        = '$client_id',
                response_type    = 'system',
                response_text    = '$sys_text',
                response_created = NOW()");

            $admin_res = mysqli_query($cn, "SELECT client_email, client_name FROM photobooth_stat_client WHERE client_role='admin' AND client_active=1 AND notify_new_tickets=1");
            if ($admin_res && mysqli_num_rows($admin_res) > 0) {
                $ticket_url    = "https://stats.magicshoot.nl/page_ticket_detail.php?ticket_id=$ticket_id";
                $email_subject = "Nieuw support ticket #$ticket_id: $subject_raw";
                $body  = "Er is een nieuw support ticket ingediend.\n\n";
                $body .= "Ticket #$ticket_id\n";
                $body .= "Onderwerp: $subject_raw\n";
                $body .= "Ingediend door: $client_name\n\n";
                $body .= "Beschrijving:\n$description_raw\n\n";
                $body .= "Bekijk ticket: $ticket_url";
                $headers = "From: support@magicshoot.nl\r\nReply-To: support@magicshoot.nl\r\nContent-Type: text/plain; charset=UTF-8\r\n";
                while ($admin = $admin_res->fetch_object()) {
                    mail($admin->client_email, $email_subject, $body, $headers);
                }
            }

            header("Location: page_tickets.php");
            exit;
        } else {
            $error = 'Database fout bij aanmaken ticket.';
        }
    }
}

// Fetch predefined topics
$topics = [];
$topic_res = mysqli_query($cn, "SELECT topic_id, topic_label, topic_subject FROM support_ticket_topic WHERE topic_active=1 ORDER BY topic_order ASC, topic_label ASC");
if ($topic_res) while ($row = $topic_res->fetch_object()) $topics[] = $row;

// Fetch events for dropdown
if ($client_role === 'admin') {
    $ev_res = mysqli_query($cn, "SELECT event_id, name FROM photobooth_event_ids WHERE dashboard_show=1 ORDER BY name ASC");
} else {
    $ev_res = mysqli_query($cn, "SELECT e.event_id, e.name FROM photobooth_event_ids e
                                   JOIN photobooth_stat_client_event ce ON ce.event_id = e.event_id
                                  WHERE ce.client_id = '$client_id'
                                  ORDER BY e.name ASC");
}
$events = [];
while ($row = $ev_res->fetch_object()) $events[] = $row;

include("partial_header.php");
include("partial_nav.php");
include("inc/invul_functions.php");
?>
    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="page_tickets.php">Support Tickets</a></li>
        <li class="breadcrumb-item active">Nieuw ticket</li>
      </ol>
    </div>
  </header>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">
          <div class="row">
            <div class="col-lg-7 col-md-9">
              <div class="card">
                <div class="card-header" style="background-color:#321fdb">
                  <h4 class="mb-0 text-white">Nieuw support ticket indienen</h4>
                </div>
                <div class="card-body">
                  <?php if ($error) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                  <?php } ?>

                  <form method="POST" autocomplete="off">
                    <input type="hidden" name="mode" value="new_ticket">

                    <?php if (!empty($topics)) { ?>
                    <div class="form-group">
                      <label for="topic_preset"><strong>Kies een vooraf ingesteld onderwerp</strong> <span class="text-muted font-weight-normal">(optioneel)</span></label>
                      <select id="topic_preset" class="form-control" onchange="applyTopic(this)">
                        <option value="">— Kies een onderwerp (optioneel) —</option>
                        <?php foreach ($topics as $tp) { ?>
                          <option value="<?php echo htmlspecialchars($tp->topic_subject); ?>"><?php echo htmlspecialchars($tp->topic_label); ?></option>
                        <?php } ?>
                      </select>
                    </div>
                    <?php } ?>

                    <div class="form-group">
                      <label for="subject"><strong>Onderwerp</strong> <span class="text-danger">*</span></label>
                      <input type="text" id="subject" name="subject" class="form-control" required
                             placeholder="Korte omschrijving van het probleem"
                             value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                      <label for="description"><strong>Beschrijving</strong> <span class="text-danger">*</span></label>
                      <textarea id="description" name="description" class="form-control" rows="7" required
                                placeholder="Beschrijf het probleem zo uitgebreid mogelijk. Wat gebeurt er? Wanneer? Wat heeft u al geprobeerd?"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                      <label for="event_id"><strong>Fotokast</strong></label>
                      <select id="event_id" name="event_id" class="form-control">
                        <option value="0">— Niet specifiek —</option>
                        <?php foreach ($events as $ev) { ?>
                          <option value="<?php echo $ev->event_id; ?>" <?php echo (count($events) === 1) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ev->name); ?>
                          </option>
                        <?php } ?>
                      </select>
                    </div>

                    <div class="form-group">
                      <label for="priority"><strong>Prioriteit</strong></label>
                      <select id="priority" name="priority" class="form-control">
                        <option value="low">Laag – niet urgent</option>
                        <option value="normal" selected>Normaal</option>
                        <option value="high">Hoog – snel actie gewenst</option>
                        <option value="urgent">Urgent – fotokast staat stil</option>
                      </select>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                      <a href="page_tickets.php" class="btn btn-secondary">Annuleren</a>
                      <button type="submit" class="btn btn-primary px-4">
                        <svg class="c-icon mr-1"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-send"></use></svg>
                        Ticket indienen
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <div class="col-lg-4 col-md-3 d-none d-md-block">
              <div class="card border-0" style="background:#f0f4ff">
                <div class="card-body">
                  <h6 class="font-weight-bold">Tips voor een goed ticket</h6>
                  <ul class="small text-muted pl-3 mt-2">
                    <li>Beschrijf exact wat er mis gaat</li>
                    <li>Geef aan wanneer het probleem begon</li>
                    <li>Vermeld foutmeldingen letterlijk</li>
                    <li>Kies de juiste fotokast</li>
                    <li>Zet prioriteit op Urgent als de kast volledig stilstaat</li>
                  </ul>
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
function applyTopic(sel) {
    if (sel.value) {
        document.getElementById('subject').value = sel.value;
    }
}
</script>
<?php include("partial_footer.php"); ?>
