<?php
include("partial_header.php");
include("partial_nav.php");
include("inc/invul_functions.php");

$client_id   = (int)$_SESSION['client_id'];
$client_role = $_SESSION['client_role'];

$status_labels = [
    'new'              => ['label' => 'Nieuw',                 'color' => '#e55353'],
    'assigned'         => ['label' => 'Toegewezen',            'color' => '#f9b115'],
    'in_progress'      => ['label' => 'In behandeling',        'color' => '#321fdb'],
    'planned_remote'   => ['label' => 'Ingepland remote',      'color' => '#9b59b6'],
    'pending_purchase' => ['label' => 'Wacht op hardware',     'color' => '#e67e22'],
    'pending_client'   => ['label' => 'Wacht op klant',        'color' => '#17a2b8'],
    'on_hold'          => ['label' => 'On hold',               'color' => '#6c757d'],
    'closed'           => ['label' => 'Gesloten',              'color' => '#2eb85c'],
    'cancelled'        => ['label' => 'Geannuleerd',           'color' => '#343a40'],
    'reopened'         => ['label' => 'Opnieuw geopend',       'color' => '#e67e22'],
];
$priority_labels = [
    'low'    => ['label' => 'Laag',    'color' => '#6c757d'],
    'normal' => ['label' => 'Normaal', 'color' => '#321fdb'],
    'high'   => ['label' => 'Hoog',    'color' => '#f9b115'],
    'urgent' => ['label' => 'Urgent',  'color' => '#e55353'],
];

if ($client_role === 'admin') {
    $sql = "SELECT t.ticket_id, t.ticket_subject, t.ticket_status, t.ticket_priority,
                   t.ticket_created, t.ticket_updated,
                   c.client_name AS creator_name,
                   e.name AS event_name
              FROM support_ticket t
         LEFT JOIN photobooth_stat_client c ON c.client_id = t.created_by_client_id
         LEFT JOIN photobooth_event_ids e ON e.event_id = t.event_id
             ORDER BY t.ticket_created DESC";
} else {
    $sql = "SELECT t.ticket_id, t.ticket_subject, t.ticket_status, t.ticket_priority,
                   t.ticket_created, t.ticket_updated,
                   e.name AS event_name
              FROM support_ticket t
         LEFT JOIN photobooth_event_ids e ON e.event_id = t.event_id
             WHERE t.created_by_client_id = '$client_id'
             ORDER BY t.ticket_created DESC";
}

$result  = mysqli_query($cn, $sql);
$tickets = [];
$open_count = 0;
while ($row = $result->fetch_object()) {
    if (!in_array($row->ticket_status, ['closed', 'cancelled'])) $open_count++;
    $tickets[] = $row;
}
?>
    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item active">Support Tickets</li>
      </ol>
      <div class="ml-auto pr-3">
        <a href="page_ticket_new.php" class="btn btn-primary btn-sm">
          <svg class="c-icon mr-1"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-plus"></use></svg>
          Nieuw ticket
        </a>
      </div>
    </div>
  </header>

  <style>
    .ticket-row:hover { background-color: #f0f4ff !important; }
    .badge-status { padding: 4px 10px; border-radius: 4px; font-size: 0.78rem; font-weight: 600; color: #fff; display: inline-block; }
    .filter-btn.active { font-weight: bold; }
    #ticketTable thead th { background-color: #3c4b64; color: #fff; font-size: 0.82rem; text-transform: uppercase; letter-spacing: .04em; }
    #ticketTable tbody td { vertical-align: middle; font-size: 0.9rem; }
    .ticket-id { font-weight: 700; color: #768192; }
  </style>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">

          <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center">
                <h4 class="mb-0 mr-3">Support Tickets</h4>
                <?php if ($open_count > 0) { ?>
                  <span class="badge badge-danger"><?php echo $open_count; ?> open</span>
                <?php } ?>
              </div>
              <?php if ($client_role === 'admin') { ?>
              <div>
                <button class="btn btn-sm btn-outline-secondary filter-btn active" data-filter="all">Alles (<?php echo count($tickets); ?>)</button>
                <button class="btn btn-sm btn-outline-danger filter-btn" data-filter="new">Nieuw</button>
                <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="open">Nieuw &amp; Open</button>
                <button class="btn btn-sm btn-outline-success filter-btn" data-filter="closed">Gesloten</button>
              </div>
              <?php } ?>
            </div>
            <div class="card-body p-0">
              <table class="table table-hover mb-0" id="ticketTable">
                <thead>
                  <tr>
                    <th style="width:65px">#ID</th>
                    <th>Onderwerp</th>
                    <th style="width:160px">Fotokast</th>
                    <?php if ($client_role === 'admin') { ?><th style="width:140px">Indiener</th><?php } ?>
                    <th style="width:160px">Status</th>
                    <th style="width:110px">Prioriteit</th>
                    <th style="width:145px">Aangemaakt</th>
                    <th style="width:90px"></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($tickets)) { ?>
                    <tr>
                      <td colspan="<?php echo $client_role === 'admin' ? 8 : 7; ?>" class="text-center text-muted py-5">
                        <svg class="c-icon c-icon-2xl text-muted mb-2 d-block mx-auto"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-task"></use></svg>
                        Geen tickets gevonden. <a href="page_ticket_new.php">Maak een nieuw ticket aan.</a>
                      </td>
                    </tr>
                  <?php } ?>
                  <?php foreach ($tickets as $t) {
                    $sl = $status_labels[$t->ticket_status] ?? ['label' => $t->ticket_status, 'color' => '#999'];
                    $pl = $priority_labels[$t->ticket_priority] ?? ['label' => $t->ticket_priority, 'color' => '#999'];
                    $is_closed = in_array($t->ticket_status, ['closed', 'cancelled']);
                    $filter_cls = $is_closed ? 'filter-closed' : ($t->ticket_status === 'new' ? 'filter-new' : 'filter-open');
                  ?>
                  <tr class="ticket-row <?php echo $filter_cls; ?>" style="cursor:pointer" onclick="window.location='page_ticket_detail.php?ticket_id=<?php echo $t->ticket_id; ?>'">
                    <td class="ticket-id">#<?php echo $t->ticket_id; ?></td>
                    <td><?php echo htmlspecialchars($t->ticket_subject); ?></td>
                    <td class="text-muted"><?php echo htmlspecialchars($t->event_name ?? '—'); ?></td>
                    <?php if ($client_role === 'admin') { ?>
                    <td class="text-muted"><?php echo htmlspecialchars($t->creator_name ?? '—'); ?></td>
                    <?php } ?>
                    <td><span class="badge-status" style="background-color:<?php echo $sl['color']; ?>"><?php echo $sl['label']; ?></span></td>
                    <td><span class="badge-status" style="background-color:<?php echo $pl['color']; ?>"><?php echo $pl['label']; ?></span></td>
                    <td class="text-muted" style="font-size:0.82rem"><?php echo date('d M Y H:i', strtotime($t->ticket_created)); ?></td>
                    <td onclick="event.stopPropagation()">
                      <a href="page_ticket_detail.php?ticket_id=<?php echo $t->ticket_id; ?>" class="btn btn-sm btn-primary">Openen</a>
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
        document.querySelectorAll('.ticket-row').forEach(function(row) {
            if (filter === 'all') {
                row.style.display = '';
            } else if (filter === 'new') {
                row.style.display = row.classList.contains('filter-new') ? '' : 'none';
            } else if (filter === 'open') {
                row.style.display = (row.classList.contains('filter-open') || row.classList.contains('filter-new')) ? '' : 'none';
            } else if (filter === 'closed') {
                row.style.display = row.classList.contains('filter-closed') ? '' : 'none';
            }
        });
    });
});
</script>

<?php include("partial_footer.php"); ?>
