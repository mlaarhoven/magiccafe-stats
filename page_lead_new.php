<?php
# ---------------------------------------------------------------------------
#  page_lead_new.php — nieuwe campagne-reactie vastleggen. Admin only.
#  Opzet gelijk aan page_ticket_new.php: POST wordt afgehandeld vóór er HTML
#  is, zodat de redirect naar de detailpagina werkt.
#  Verplicht: campagne, naam, venue, beschrijving, en telefoon ÓF e-mail.
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

$client_id   = (int)$_SESSION['client_id'];
$client_name = $_SESSION['client_name'];

$channel_labels = lead_channel_labels();
$error = '';

// Actieve campagnes, nieuwste eerst (die staat dan standaard geselecteerd)
$campaigns = [];
$cres = mysqli_query($cn, "SELECT campaign_id, campaign_name FROM campaign WHERE campaign_active = 1
                            ORDER BY campaign_start DESC, campaign_id DESC");
if ($cres) while ($row = $cres->fetch_object()) $campaigns[] = $row;
$campaign_ids = array_map(fn($c) => (int)$c->campaign_id, $campaigns);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'new_lead') {
    $campaign_id = (int)($_POST['campaign_id'] ?? 0);
    $channel     = (string)($_POST['lead_channel'] ?? '');
    $name_raw    = trim($_POST['contact_name']  ?? '');
    $phone_raw   = trim($_POST['contact_phone'] ?? '');
    $email_raw   = trim($_POST['contact_email'] ?? '');
    $venue_raw   = trim($_POST['venue_name']    ?? '');
    $city_raw    = trim($_POST['venue_city']    ?? '');
    $desc_raw    = lead_clean_text($_POST['lead_description'] ?? '');

    if (!in_array($campaign_id, $campaign_ids, true)) {
        $error = 'Kies een (actieve) campagne.';
    } elseif (!isset($channel_labels[$channel])) {
        $error = 'Kies hoe de reactie binnenkwam.';
    } elseif ($name_raw === '' || $venue_raw === '' || $desc_raw === '') {
        $error = 'Vul naam, venue en beschrijving in.';
    } elseif ($phone_raw === '' && $email_raw === '') {
        $error = 'Vul een telefoonnummer of e-mailadres in (minstens één van de twee).';
    } elseif ($email_raw !== '' && !filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
        $error = 'Het e-mailadres lijkt niet te kloppen.';
    } else {
        $name  = mysqli_real_escape_string($cn, $name_raw);
        $phone = $phone_raw === '' ? 'NULL' : "'" . mysqli_real_escape_string($cn, $phone_raw) . "'";
        $email = $email_raw === '' ? 'NULL' : "'" . mysqli_real_escape_string($cn, $email_raw) . "'";
        $venue = mysqli_real_escape_string($cn, $venue_raw);
        $city  = $city_raw === '' ? 'NULL' : "'" . mysqli_real_escape_string($cn, $city_raw) . "'";
        $desc  = mysqli_real_escape_string($cn, $desc_raw);

        $ok = mysqli_query($cn, "INSERT INTO campaign_lead SET
                campaign_id          = '$campaign_id',
                contact_name         = '$name',
                contact_phone        = $phone,
                contact_email        = $email,
                lead_channel         = '$channel',
                venue_name           = '$venue',
                venue_city           = $city,
                lead_description     = '$desc',
                lead_status          = 'new',
                created_by_client_id = '$client_id',
                lead_created         = NOW(),
                lead_updated         = NOW()");

        if ($ok) {
            $lead_id  = (int)mysqli_insert_id($cn);
            $sys_text = mysqli_real_escape_string($cn, "Reactie vastgelegd door $client_name (binnengekomen via " . $channel_labels[$channel]['label'] . ")");
            mysqli_query($cn, "INSERT INTO campaign_lead_response SET
                lead_id          = '$lead_id',
                client_id        = '$client_id',
                response_type    = 'system',
                response_text    = '$sys_text',
                response_created = NOW()");
            header("Location: page_lead_detail.php?lead_id=$lead_id&new=1");
            exit;
        }
        $error = 'Database fout bij opslaan.';
    }
}

// Voorselectie: POST (bij een fout) > ?campaign_id= vanaf het overzicht > nieuwste actieve
$sel_campaign = (int)($_POST['campaign_id'] ?? $_GET['campaign_id'] ?? ($campaign_ids[0] ?? 0));
$sel_channel  = $_POST['lead_channel'] ?? 'instagram';

include("partial_header.php");
include("partial_nav.php");

function lead_old($key) { return htmlspecialchars($_POST[$key] ?? ''); }
?>
    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="page_leads.php">Campagne reacties</a></li>
        <li class="breadcrumb-item active">Nieuwe reactie</li>
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
                  <h4 class="mb-0 text-white">Nieuwe campagne-reactie vastleggen</h4>
                </div>
                <div class="card-body">
                  <?php if (empty($campaigns)) { ?>
                    <div class="alert alert-warning mb-0">
                      Er is geen actieve campagne. <a href="page_campaigns.php">Maak eerst een campagne aan</a>.
                    </div>
                  <?php } else { ?>
                  <?php if ($error) { ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                  <?php } ?>

                  <form method="POST" autocomplete="off">
                    <input type="hidden" name="mode" value="new_lead">

                    <div class="form-row">
                      <div class="form-group col-md-7">
                        <label for="campaign_id"><strong>Campagne</strong> <span class="text-danger">*</span></label>
                        <select id="campaign_id" name="campaign_id" class="form-control" required>
                          <?php foreach ($campaigns as $c) { ?>
                            <option value="<?php echo $c->campaign_id; ?>" <?php echo $sel_campaign === (int)$c->campaign_id ? 'selected' : ''; ?>>
                              <?php echo htmlspecialchars($c->campaign_name); ?>
                            </option>
                          <?php } ?>
                        </select>
                      </div>
                      <div class="form-group col-md-5">
                        <label for="lead_channel"><strong>Binnengekomen via</strong> <span class="text-danger">*</span></label>
                        <select id="lead_channel" name="lead_channel" class="form-control" required>
                          <?php foreach ($channel_labels as $key => $info) { ?>
                            <option value="<?php echo $key; ?>" <?php echo $sel_channel === $key ? 'selected' : ''; ?>><?php echo $info['label']; ?></option>
                          <?php } ?>
                        </select>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="contact_name"><strong>Naam contactpersoon</strong> <span class="text-danger">*</span></label>
                      <input type="text" id="contact_name" name="contact_name" class="form-control" required maxlength="150"
                             value="<?php echo lead_old('contact_name'); ?>">
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-5">
                        <label for="contact_phone"><strong>Telefoon</strong></label>
                        <input type="tel" id="contact_phone" name="contact_phone" class="form-control" maxlength="50"
                               placeholder="06-12345678" value="<?php echo lead_old('contact_phone'); ?>">
                      </div>
                      <div class="form-group col-md-7">
                        <label for="contact_email"><strong>E-mail</strong></label>
                        <input type="email" id="contact_email" name="contact_email" class="form-control" maxlength="150"
                               value="<?php echo lead_old('contact_email'); ?>">
                      </div>
                    </div>
                    <small class="form-text text-muted mt-n2 mb-3">Minstens één van de twee (telefoon of e-mail) is verplicht.</small>

                    <div class="form-row">
                      <div class="form-group col-md-7">
                        <label for="venue_name"><strong>Venue</strong> <span class="text-danger">*</span></label>
                        <input type="text" id="venue_name" name="venue_name" class="form-control" required maxlength="150"
                               placeholder="Naam van de zaak / locatie" value="<?php echo lead_old('venue_name'); ?>">
                      </div>
                      <div class="form-group col-md-5">
                        <label for="venue_city"><strong>Plaats</strong></label>
                        <input type="text" id="venue_city" name="venue_city" class="form-control" maxlength="100"
                               value="<?php echo lead_old('venue_city'); ?>">
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="lead_description"><strong>Reactie / beschrijving</strong> <span class="text-danger">*</span></label>
                      <textarea id="lead_description" name="lead_description" class="form-control" rows="6" required
                                placeholder="Wat schreef of zei de persoon? Wat voor zaak is het, hoeveel bezoekers, waarom interessant?"><?php echo lead_old('lead_description'); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                      <a href="page_leads.php" class="btn btn-secondary">Annuleren</a>
                      <button type="submit" class="btn btn-primary px-4">
                        <svg class="c-icon mr-1"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-plus"></use></svg>
                        Reactie vastleggen
                      </button>
                    </div>
                  </form>
                  <?php } ?>
                </div>
              </div>
            </div>

            <div class="col-lg-4 col-md-3 d-none d-md-block">
              <div class="card border-0" style="background:#f0f4ff">
                <div class="card-body">
                  <h6 class="font-weight-bold">Werkwijze</h6>
                  <ol class="small text-muted pl-3 mt-2 mb-0">
                    <li>Leg alleen serieuze reacties vast</li>
                    <li>Onderzoek de venue en geef een score (1–5)</li>
                    <li>Laat de persoon weten of het positief of negatief is, en leg vast via welk kanaal</li>
                    <li>Plaatsing ingepland? Zet de status om</li>
                    <li>Sluit af met "geplaatst" of "geen plaatsing"</li>
                  </ol>
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

<?php include("partial_footer.php"); ?>
