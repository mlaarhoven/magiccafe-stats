<?php
# ---------------------------------------------------------------------------
#  page_leads.php — overzicht "Campagne reacties". Admin only.
#  Opzet gelijk aan page_tickets.php. Filter op campagne via ?campagne=
#  (leeg = alle actieve campagnes, 'alle' = ook inactieve, getal = één campagne).
#  De samenvattingskaart telt over dezelfde selectie als de tabel.
# ---------------------------------------------------------------------------

# rolcheck vóór partial_header, zodat header() nog kan redirecten
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if ( !isset($_SESSION['client_id']) || $_SESSION['client_role'] != 'admin' ) {
    header('Location: https://stats.magicshoot.nl/index.php');
    exit;
}

include("partial_header.php");
include("partial_nav.php");
include_once __DIR__ . '/inc/lead_functions.php';

$status_labels  = lead_status_labels();
$channel_labels = lead_channel_labels();

// Campagnes voor de keuzelijst
$campaigns = [];
$cres = mysqli_query($cn, "SELECT campaign_id, campaign_name, campaign_active FROM campaign
                            ORDER BY campaign_active DESC, campaign_start DESC, campaign_id DESC");
if ($cres) while ($row = $cres->fetch_object()) $campaigns[] = $row;

$campagne_param = $_GET['campagne'] ?? '';
$gekozen_id     = 0;
if ($campagne_param === 'alle') {
    $where = "1=1";
} elseif ((int)$campagne_param > 0) {
    $gekozen_id = (int)$campagne_param;
    $where = "l.campaign_id = '$gekozen_id'";
} else {
    $campagne_param = '';
    $where = "c.campaign_active = 1";
}

$leads = [];
$res = mysqli_query($cn, "SELECT l.lead_id, l.contact_name, l.contact_phone, l.contact_email, l.lead_channel,
                                 l.venue_name, l.venue_city, l.lead_status, l.venue_score,
                                 l.lead_created, c.campaign_name
                            FROM campaign_lead l
                       LEFT JOIN campaign c ON c.campaign_id = l.campaign_id
                           WHERE $where
                        ORDER BY l.lead_created DESC");
if ($res) while ($row = $res->fetch_object()) $leads[] = $row;

// Leads die ooit een positieve reactie van ons kregen (ook als ze later toch niet geplaatst zijn)
$positief_ids = [];
$pres = mysqli_query($cn, "SELECT DISTINCT r.lead_id
                             FROM campaign_lead_response r
                             JOIN campaign_lead l ON l.lead_id = r.lead_id
                        LEFT JOIN campaign c ON c.campaign_id = l.campaign_id
                            WHERE r.response_type = 'contact_reply' AND r.new_status = 'replied_positive'
                              AND $where");
if ($pres) while ($row = $pres->fetch_row()) $positief_ids[(int)$row[0]] = true;

// Samenvatting
$totaal       = count($leads);
$open_count   = 0;
$positief     = 0;
$geplaatst    = 0;
$per_kanaal   = [];
foreach ($leads as $l) {
    if (!lead_is_closed($l->lead_status)) $open_count++;
    if ($l->lead_status === 'closed_placed') $geplaatst++;
    // Een lead die zonder vastgelegde reactie naar ingepland/geplaatst is gezet was ook positief
    if (isset($positief_ids[(int)$l->lead_id]) || in_array($l->lead_status, ['placement_planned', 'closed_placed'], true)) $positief++;
    $per_kanaal[$l->lead_channel] = ($per_kanaal[$l->lead_channel] ?? 0) + 1;
}
$conversie = $totaal > 0 ? round($geplaatst / $totaal * 100) : 0;
?>
    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item active">Campagne reacties</li>
      </ol>
      <div class="ml-auto pr-3">
        <a href="page_lead_new.php<?php echo $gekozen_id ? '?campaign_id=' . $gekozen_id : ''; ?>" class="btn btn-primary btn-sm">
          <svg class="c-icon mr-1"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-plus"></use></svg>
          Nieuwe reactie
        </a>
        <a href="page_campaigns.php" class="btn btn-secondary btn-sm ml-2">
          <svg class="c-icon mr-1"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-bullhorn"></use></svg>
          Campagnes beheren
        </a>
      </div>
    </div>
  </header>

  <style>
    .lead-row:hover { background-color: #f0f4ff !important; }
    .badge-status { padding: 4px 10px; border-radius: 4px; font-size: 0.78rem; font-weight: 600; color: #fff; display: inline-block; }
    .filter-btn.active { font-weight: bold; }
    #leadTable thead th { background-color: #3c4b64; color: #fff; font-size: 0.82rem; text-transform: uppercase; letter-spacing: .04em; }
    #leadTable tbody td { vertical-align: middle; font-size: 0.9rem; }
    .lead-id { font-weight: 700; color: #768192; }
    .lead-stars { color: #f9b115; letter-spacing: 1px; white-space: nowrap; }
    .lead-stars-off { color: #d8dbe0; }
    .kpi { text-align: center; padding: 6px 10px; }
    .kpi-value { font-size: 1.6rem; font-weight: 700; color: #3c4b64; line-height: 1.1; }
    .kpi-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .05em; color: #768192; }
  </style>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">

          <?php if (empty($campaigns)) { ?>
            <div class="alert alert-info">
              Er is nog geen campagne. <a href="page_campaigns.php">Maak eerst een campagne aan</a>, daarna kun je reacties vastleggen.
            </div>
          <?php } ?>

          <!-- Campagnekeuze + samenvatting -->
          <div class="card">
            <div class="card-body">
              <form method="GET" class="form-inline mb-3">
                <label for="campagne" class="mr-2"><strong>Campagne</strong></label>
                <select id="campagne" name="campagne" class="form-control form-control-sm" onchange="this.form.submit()">
                  <option value="" <?php echo $campagne_param === '' ? 'selected' : ''; ?>>Alle actieve campagnes</option>
                  <option value="alle" <?php echo $campagne_param === 'alle' ? 'selected' : ''; ?>>Alle campagnes (ook inactief)</option>
                  <?php foreach ($campaigns as $c) { ?>
                    <option value="<?php echo $c->campaign_id; ?>" <?php echo $gekozen_id === (int)$c->campaign_id ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($c->campaign_name) . ($c->campaign_active ? '' : ' (inactief)'); ?>
                    </option>
                  <?php } ?>
                </select>
              </form>

              <div class="d-flex flex-wrap justify-content-between">
                <div class="kpi"><div class="kpi-value"><?php echo $totaal; ?></div><div class="kpi-label">Reacties</div></div>
                <div class="kpi"><div class="kpi-value"><?php echo $open_count; ?></div><div class="kpi-label">Open</div></div>
                <div class="kpi"><div class="kpi-value"><?php echo $positief; ?></div><div class="kpi-label">Positief gereageerd</div></div>
                <div class="kpi"><div class="kpi-value"><?php echo $geplaatst; ?></div><div class="kpi-label">Geplaatst</div></div>
                <div class="kpi"><div class="kpi-value"><?php echo $conversie; ?>%</div><div class="kpi-label">Conversie</div></div>
              </div>
              <?php if ($totaal > 0) { ?>
              <div class="mt-3 small text-muted">
                Binnengekomen via:
                <?php foreach ($channel_labels as $key => $info) {
                    if (empty($per_kanaal[$key])) continue; ?>
                  <span class="badge-status ml-1" style="background-color:<?php echo $info['color']; ?>"><?php echo $info['label']; ?> <?php echo $per_kanaal[$key]; ?></span>
                <?php } ?>
              </div>
              <?php } ?>
            </div>
          </div>

          <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
              <div class="d-flex align-items-center">
                <h4 class="mb-0 mr-3">Campagne reacties</h4>
                <?php if ($open_count > 0) { ?>
                  <span class="badge badge-danger"><?php echo $open_count; ?> open</span>
                <?php } ?>
              </div>
              <div>
                <button class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all">Alles (<?php echo $totaal; ?>)</button>
                <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="open">Open</button>
                <button class="btn btn-sm btn-outline-success filter-btn" data-filter="placed">Geplaatst</button>
                <button class="btn btn-sm btn-outline-dark filter-btn" data-filter="closed">Gesloten</button>
              </div>
            </div>
            <div class="card-body p-0" style="overflow-x:auto">
              <table class="table table-hover mb-0" id="leadTable">
                <thead>
                  <tr>
                    <th style="width:65px">#ID</th>
                    <th>Contact</th>
                    <th>Venue</th>
                    <th style="width:110px">Kanaal</th>
                    <th style="width:160px">Campagne</th>
                    <th style="width:170px">Status</th>
                    <th style="width:95px">Score</th>
                    <th style="width:145px">Aangemaakt</th>
                    <th style="width:90px"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($leads)) { ?>
                    <tr>
                      <td colspan="9" class="text-center text-muted py-5">
                        <svg class="c-icon c-icon-2xl text-muted mb-2 d-block mx-auto"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-bullhorn"></use></svg>
                        Geen reacties gevonden.<?php if (!empty($campaigns)) { ?> <a href="page_lead_new.php">Leg een nieuwe reactie vast.</a><?php } ?>
                      </td>
                    </tr>
                  <?php } ?>
                  <?php foreach ($leads as $l) {
                    $sl = $status_labels[$l->lead_status]  ?? ['label' => $l->lead_status,  'color' => '#999'];
                    $kl = $channel_labels[$l->lead_channel] ?? ['label' => $l->lead_channel, 'color' => '#999'];
                    $filter_cls = lead_is_closed($l->lead_status) ? 'filter-closed' : 'filter-open';
                    if ($l->lead_status === 'closed_placed') $filter_cls .= ' filter-placed';
                  ?>
                  <tr class="lead-row <?php echo $filter_cls; ?>" style="cursor:pointer" onclick="window.location='page_lead_detail.php?lead_id=<?php echo $l->lead_id; ?>'">
                    <td class="lead-id">#<?php echo $l->lead_id; ?></td>
                    <td>
                      <?php echo htmlspecialchars($l->contact_name); ?>
                      <div class="small text-muted"><?php echo htmlspecialchars($l->contact_phone ?: $l->contact_email); ?></div>
                    </td>
                    <td>
                      <?php echo htmlspecialchars($l->venue_name); ?>
                      <?php if ($l->venue_city) { ?><div class="small text-muted"><?php echo htmlspecialchars($l->venue_city); ?></div><?php } ?>
                    </td>
                    <td><?php echo lead_badge($kl['label'], $kl['color']); ?></td>
                    <td class="text-muted"><?php echo htmlspecialchars($l->campaign_name ?? '—'); ?></td>
                    <td><?php echo lead_badge($sl['label'], $sl['color']); ?></td>
                    <td><?php echo lead_score_stars($l->venue_score); ?></td>
                    <td class="text-muted" style="font-size:0.82rem"><?php echo date('d M Y H:i', strtotime($l->lead_created)); ?></td>
                    <td onclick="event.stopPropagation()">
                      <a href="page_lead_detail.php?lead_id=<?php echo $l->lead_id; ?>" class="btn btn-sm btn-primary">Openen</a>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
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
document.querySelectorAll('.filter-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');
        var filter = this.dataset.filter;
        document.querySelectorAll('.lead-row').forEach(function(row) {
            row.style.display = (filter === 'all' || row.classList.contains('filter-' + filter)) ? '' : 'none';
        });
    });
});
</script>

<?php include("partial_footer.php"); ?>
