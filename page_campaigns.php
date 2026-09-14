<?php
# ---------------------------------------------------------------------------
#  page_campaigns.php — campagnes beheren voor "Campagne reacties". Admin only.
#  Een campagne met reacties kan niet verwijderd worden, alleen op inactief:
#  dan verdwijnt hij uit de keuzelijst bij een nieuwe reactie, maar blijft hij
#  filterbaar in page_leads.php.
# ---------------------------------------------------------------------------

# rolcheck vóór partial_header, zodat header() nog kan redirecten
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if ( !isset($_SESSION['client_id']) || $_SESSION['client_role'] != 'admin' ) {
    header('Location: https://stats.magicshoot.nl/index.php');
    exit;
}

global $cn;
include '../inc/connection.inc.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';
    $id   = (int)($_POST['campaign_id'] ?? 0);

    if ($mode === 'add_campaign' || $mode === 'edit_campaign') {
        $name_raw  = trim($_POST['campaign_name'] ?? '');
        $start_raw = trim($_POST['campaign_start'] ?? '');
        $note_raw  = trim($_POST['campaign_note'] ?? '');
        $name  = mysqli_real_escape_string($cn, $name_raw);
        $note  = mysqli_real_escape_string($cn, $note_raw);
        $start = preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_raw) ? "'$start_raw'" : 'NULL';

        if ($name === '') {
            $error = 'Vul een naam in voor de campagne.';
        } elseif ($mode === 'add_campaign') {
            mysqli_query($cn, "INSERT INTO campaign SET
                campaign_name    = '$name',
                campaign_start   = $start,
                campaign_note    = '$note',
                campaign_active  = 1,
                campaign_created = NOW()");
            header("Location: page_campaigns.php?ok=toegevoegd"); exit;
        } elseif ($id) {
            mysqli_query($cn, "UPDATE campaign SET
                campaign_name  = '$name',
                campaign_start = $start,
                campaign_note  = '$note'
                WHERE campaign_id = '$id'");
            header("Location: page_campaigns.php?ok=bijgewerkt"); exit;
        }

    } elseif ($mode === 'toggle_campaign' && $id) {
        mysqli_query($cn, "UPDATE campaign SET campaign_active = 1 - campaign_active WHERE campaign_id = '$id'");
        header("Location: page_campaigns.php"); exit;

    } elseif ($mode === 'delete_campaign' && $id) {
        // Alleen een lege campagne mag weg; anders zouden reacties aan niets meer hangen
        $cnt = (int)mysqli_fetch_row(mysqli_query($cn, "SELECT COUNT(*) FROM campaign_lead WHERE campaign_id = '$id'"))[0];
        if ($cnt > 0) {
            $error = 'Deze campagne heeft reacties en kan niet verwijderd worden. Zet hem op inactief.';
        } else {
            mysqli_query($cn, "DELETE FROM campaign WHERE campaign_id = '$id'");
            header("Location: page_campaigns.php?ok=verwijderd"); exit;
        }
    }
}

$ok_msgs = [
    'toegevoegd' => 'Campagne toegevoegd.',
    'bijgewerkt' => 'Campagne bijgewerkt.',
    'verwijderd' => 'Campagne verwijderd.',
];
$msg = $ok_msgs[$_GET['ok'] ?? ''] ?? '';

$campaigns = [];
$res = mysqli_query($cn, "SELECT c.*,
                                 COUNT(l.lead_id) AS lead_count,
                                 COALESCE(SUM(l.lead_status = 'closed_placed'), 0) AS placed_count,
                                 COALESCE(SUM(l.lead_status NOT IN ('closed_placed','closed_not_placed')), 0) AS open_count
                            FROM campaign c
                       LEFT JOIN campaign_lead l ON l.campaign_id = c.campaign_id
                        GROUP BY c.campaign_id
                        ORDER BY c.campaign_active DESC, c.campaign_start DESC, c.campaign_id DESC");
if ($res) while ($row = $res->fetch_object()) $campaigns[] = $row;

$edit_campaign = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $er  = mysqli_query($cn, "SELECT * FROM campaign WHERE campaign_id = '$eid'");
    if ($er && mysqli_num_rows($er) > 0) $edit_campaign = $er->fetch_object();
}

include("partial_header.php");
include("partial_nav.php");
?>
    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="page_leads.php">Campagne reacties</a></li>
        <li class="breadcrumb-item active">Campagnes beheren</li>
      </ol>
    </div>
  </header>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">

          <?php if ($msg) { ?>
            <div class="alert alert-success alert-dismissible fade show">
              <?php echo htmlspecialchars($msg); ?>
              <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
          <?php } ?>
          <?php if ($error) { ?>
            <div class="alert alert-danger alert-dismissible fade show">
              <?php echo htmlspecialchars($error); ?>
              <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
          <?php } ?>

          <div class="row">
            <!-- Campagnelijst -->
            <div class="col-lg-8">
              <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between" style="background:#3c4b64;color:#fff">
                  <h4 class="mb-0">Campagnes</h4>
                  <span class="badge badge-light"><?php echo count($campaigns); ?> campagnes</span>
                </div>
                <div class="card-body p-0">
                  <table class="table table-hover mb-0">
                    <thead>
                      <tr>
                        <th>Naam</th>
                        <th style="width:110px">Start</th>
                        <th style="width:90px" class="text-right">Reacties</th>
                        <th style="width:70px" class="text-right">Open</th>
                        <th style="width:90px" class="text-right">Geplaatst</th>
                        <th style="width:80px">Actief</th>
                        <th style="width:130px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($campaigns)) { ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Nog geen campagnes. Maak er rechts een aan.</td></tr>
                      <?php } ?>
                      <?php foreach ($campaigns as $c) { ?>
                        <tr style="<?php echo $c->campaign_active ? '' : 'opacity:0.5'; ?>">
                          <td>
                            <a href="page_leads.php?campagne=<?php echo $c->campaign_id; ?>"><strong><?php echo htmlspecialchars($c->campaign_name); ?></strong></a>
                            <?php if ($c->campaign_note !== null && $c->campaign_note !== '') { ?>
                              <div class="small text-muted"><?php echo htmlspecialchars($c->campaign_note); ?></div>
                            <?php } ?>
                          </td>
                          <td class="text-muted"><?php echo $c->campaign_start ? date('d-m-Y', strtotime($c->campaign_start)) : '—'; ?></td>
                          <td class="text-right"><?php echo (int)$c->lead_count; ?></td>
                          <td class="text-right"><?php echo (int)$c->open_count; ?></td>
                          <td class="text-right"><?php echo (int)$c->placed_count; ?></td>
                          <td>
                            <form method="POST" style="display:inline">
                              <input type="hidden" name="mode" value="toggle_campaign">
                              <input type="hidden" name="campaign_id" value="<?php echo $c->campaign_id; ?>">
                              <button type="submit" class="btn btn-sm <?php echo $c->campaign_active ? 'btn-success' : 'btn-outline-secondary'; ?>">
                                <?php echo $c->campaign_active ? 'Ja' : 'Nee'; ?>
                              </button>
                            </form>
                          </td>
                          <td>
                            <a href="page_campaigns.php?edit=<?php echo $c->campaign_id; ?>" class="btn btn-sm btn-outline-primary mr-1">Bewerken</a>
                            <?php if ((int)$c->lead_count === 0) { ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Campagne verwijderen?')">
                              <input type="hidden" name="mode" value="delete_campaign">
                              <input type="hidden" name="campaign_id" value="<?php echo $c->campaign_id; ?>">
                              <button type="submit" class="btn btn-sm btn-outline-danger">&#10005;</button>
                            </form>
                            <?php } ?>
                          </td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Toevoegen / bewerken -->
            <div class="col-lg-4">
              <div class="card">
                <div class="card-header" style="background:#321fdb;color:#fff">
                  <strong><?php echo $edit_campaign ? 'Campagne bewerken' : 'Nieuwe campagne'; ?></strong>
                </div>
                <div class="card-body">
                  <form method="POST" action="page_campaigns.php">
                    <input type="hidden" name="mode" value="<?php echo $edit_campaign ? 'edit_campaign' : 'add_campaign'; ?>">
                    <?php if ($edit_campaign) { ?>
                      <input type="hidden" name="campaign_id" value="<?php echo $edit_campaign->campaign_id; ?>">
                    <?php } ?>

                    <div class="form-group">
                      <label><strong>Naam</strong> <span class="text-danger">*</span></label>
                      <input type="text" name="campaign_name" class="form-control" required maxlength="150"
                             placeholder="bijv. Instagram najaar 2026"
                             value="<?php echo htmlspecialchars($edit_campaign->campaign_name ?? ''); ?>">
                    </div>

                    <div class="form-group">
                      <label><strong>Startdatum</strong></label>
                      <input type="date" name="campaign_start" class="form-control"
                             value="<?php echo htmlspecialchars($edit_campaign->campaign_start ?? date('Y-m-d')); ?>">
                    </div>

                    <div class="form-group">
                      <label><strong>Notitie</strong> <small class="text-muted">(optioneel)</small></label>
                      <textarea name="campaign_note" class="form-control" rows="3"
                                placeholder="bijv. doelgroep, budget, welke posts"><?php echo htmlspecialchars($edit_campaign->campaign_note ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                      <?php if ($edit_campaign) { ?>
                        <a href="page_campaigns.php" class="btn btn-secondary">Annuleren</a>
                      <?php } else { ?>
                        <span></span>
                      <?php } ?>
                      <button type="submit" class="btn btn-primary">
                        <?php echo $edit_campaign ? 'Opslaan' : 'Toevoegen'; ?>
                      </button>
                    </div>
                  </form>
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
