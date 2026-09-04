<?php
include "partial_header.php";
include "partial_nav.php";

$client_id   = (int)$_SESSION['client_id'];
$client_role = $_SESSION['client_role'];

if ($client_role !== 'admin') {
    header("Location: page_tickets.php"); exit;
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

$admins = [];
$res = mysqli_query($cn, "SELECT client_id, client_name, client_email, notify_new_tickets
                            FROM photobooth_stat_client
                           WHERE client_role='admin' AND client_active=1
                           ORDER BY client_name ASC");
while ($row = $res->fetch_object()) $admins[] = $row;

$notify_count = 0;
foreach ($admins as $a) { if ($a->notify_new_tickets) $notify_count++; }
?>
    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="page_tickets.php">Support Tickets</a></li>
        <li class="breadcrumb-item active">Notificaties</li>
      </ol>
    </div>
  </header>

  <style>
    .admin-avatar { width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center;
                    justify-content: center; font-weight: 700; font-size: 0.75rem; color: #fff; flex-shrink: 0; }
    .notify-row td { vertical-align: middle; }
    .notify-row.is-off td { opacity: 0.5; }
    .notify-row.is-off .admin-avatar { filter: grayscale(1); }
    .custom-control-input:checked ~ .custom-control-label::before { background-color: #2eb85c; border-color: #2eb85c; }
  </style>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">
          <div class="row">
            <div class="col-lg-7">

              <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between" style="background:#321fdb">
                  <h4 class="mb-0 text-white">
                    <svg class="c-icon mr-2" style="width:20px;height:20px"><use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-bell"></use></svg>
                    Notificaties — nieuwe tickets
                  </h4>
                  <span class="badge badge-light" style="font-size:0.85rem">
                    <?php echo $notify_count; ?> / <?php echo count($admins); ?> actief
                  </span>
                </div>
                <div class="card-body p-0">
                  <?php if (empty($admins)) { ?>
                    <p class="text-muted p-4 mb-0">Geen beheerders gevonden.</p>
                  <?php } else { ?>
                  <table class="table table-hover mb-0">
                    <thead class="thead-light">
                      <tr>
                        <th style="width:44px"></th>
                        <th>Naam</th>
                        <th>E-mail</th>
                        <th class="text-center" style="width:120px">Notificaties</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($admins as $a) {
                        $on  = (bool)$a->notify_new_tickets;
                        $ini = initials($a->client_name);
                        $clr = avatar_color($a->client_name);
                      ?>
                      <tr class="notify-row <?php echo $on ? '' : 'is-off'; ?>" id="row_<?php echo $a->client_id; ?>">
                        <td>
                          <div class="admin-avatar" style="background:<?php echo $clr; ?>"><?php echo $ini; ?></div>
                        </td>
                        <td>
                          <strong><?php echo htmlspecialchars($a->client_name); ?></strong>
                          <?php if ($a->client_id == $client_id) { ?>
                            <span class="text-muted small ml-1">(jij)</span>
                          <?php } ?>
                        </td>
                        <td class="text-muted small"><?php echo htmlspecialchars($a->client_email ?: '—'); ?></td>
                        <td class="text-center">
                          <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input"
                                   id="tog_<?php echo $a->client_id; ?>"
                                   <?php echo $on ? 'checked' : ''; ?>
                                   onchange="toggleNotify(this, <?php echo $a->client_id; ?>)">
                            <label class="custom-control-label" for="tog_<?php echo $a->client_id; ?>"></label>
                          </div>
                        </td>
                      </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                  <?php } ?>
                </div>
              </div>

            </div>
            <div class="col-lg-5 d-none d-lg-block">
              <div class="card border-0" style="background:#f0f4ff">
                <div class="card-body">
                  <h6 class="font-weight-bold mb-2">Wanneer wordt dit gebruikt?</h6>
                  <p class="small text-muted mb-2">
                    Wanneer een klant een nieuw support ticket indient, ontvangen alle beheerders met de schakelaar op <strong>aan</strong> direct een e-mail.
                  </p>
                  <p class="small text-muted mb-2">
                    Zet de schakelaar op <strong>uit</strong> als een beheerder tijdelijk geen notificaties moet ontvangen — bijvoorbeeld tijdens vakantie of testperiodes.
                  </p>
                  <p class="small text-muted mb-0">
                    Deze instelling heeft alleen invloed op de notificatie bij het <em>aanmaken</em> van een ticket. Reacties, statuswijzigingen en toewijzingen worden apart beheerd per ticket.
                  </p>
                </div>
              </div>
            </div>
          </div>

          <div id="save-feedback" class="mt-2" style="min-height:24px"></div>

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
function toggleNotify(el, clientId) {
    var val  = el.checked ? 1 : 0;
    var row  = document.getElementById('row_' + clientId);
    var fb   = document.getElementById('save-feedback');

    var fd = new FormData();
    fd.append('client_id', clientId);
    fd.append('value', val);

    fetch('ajax_ticket_notify_toggle.php', { method: 'POST', body: fd })
        .then(function(r) { return r.text(); })
        .then(function(text) {
            try {
                var json = JSON.parse(text);
                if (json.success) {
                    row.classList.toggle('is-off', val === 0);
                    fb.innerHTML = '<span class="text-success small">&#10003; Opgeslagen</span>';
                    setTimeout(function() { fb.innerHTML = ''; }, 2000);
                } else {
                    el.checked = !el.checked;
                    fb.innerHTML = '<span class="text-danger small">Fout: ' + (json.error || 'onbekend') + '</span>';
                }
            } catch (e) {
                console.error('Unexpected response from ajax_ticket_notify_toggle.php:', text);
                el.checked = !el.checked;
                fb.innerHTML = '<span class="text-danger small">Verbindingsfout, probeer opnieuw.</span>';
            }
        })
        .catch(function(e) {
            console.error('Fetch error:', e);
            el.checked = !el.checked;
            fb.innerHTML = '<span class="text-danger small">Verbindingsfout, probeer opnieuw.</span>';
        });
}
</script>

<?php include "partial_footer.php"; ?>
