<?php
include("partial_header.php");

if (!isset($_SESSION['client_id']) || $_SESSION['client_role'] !== 'admin') {
    header("Location: page_tickets.php"); exit;
}

include("partial_nav.php");

$msg = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';

    if ($mode === 'add_topic') {
        $label   = trim(mysqli_real_escape_string($cn, $_POST['topic_label']   ?? ''));
        $subject = trim(mysqli_real_escape_string($cn, $_POST['topic_subject'] ?? ''));
        if ($label === '' || $subject === '') {
            $error = 'Vul zowel het label als het onderwerp in.';
        } else {
            $max_res = mysqli_query($cn, "SELECT MAX(topic_order) AS mo FROM support_ticket_topic");
            $max_row = $max_res ? $max_res->fetch_object() : null;
            $new_order = (int)($max_row->mo ?? 0) + 10;
            mysqli_query($cn, "INSERT INTO support_ticket_topic SET topic_label='$label', topic_subject='$subject', topic_order='$new_order', topic_active=1");
            $msg = 'Onderwerp toegevoegd.';
        }

    } elseif ($mode === 'edit_topic') {
        $id      = (int)($_POST['topic_id'] ?? 0);
        $label   = trim(mysqli_real_escape_string($cn, $_POST['topic_label']   ?? ''));
        $subject = trim(mysqli_real_escape_string($cn, $_POST['topic_subject'] ?? ''));
        if ($id && $label !== '' && $subject !== '') {
            mysqli_query($cn, "UPDATE support_ticket_topic SET topic_label='$label', topic_subject='$subject' WHERE topic_id='$id'");
            $msg = 'Onderwerp bijgewerkt.';
        }

    } elseif ($mode === 'move_up' || $mode === 'move_down') {
        $id = (int)($_POST['topic_id'] ?? 0);
        if ($id) {
            // Load sorted list
            $all = [];
            $r = mysqli_query($cn, "SELECT topic_id, topic_order FROM support_ticket_topic ORDER BY topic_order ASC, topic_id ASC");
            while ($row = $r->fetch_object()) $all[] = $row;
            $pos = -1;
            foreach ($all as $i => $t) { if ((int)$t->topic_id === $id) { $pos = $i; break; } }
            $swap_pos = ($mode === 'move_up') ? $pos - 1 : $pos + 1;
            if ($pos >= 0 && isset($all[$swap_pos])) {
                $a = $all[$pos];
                $b = $all[$swap_pos];
                // Swap order values; if equal, force a difference
                $oa = (int)$a->topic_order;
                $ob = (int)$b->topic_order;
                if ($oa === $ob) { $oa = $pos * 10; $ob = $swap_pos * 10; }
                mysqli_query($cn, "UPDATE support_ticket_topic SET topic_order='$ob' WHERE topic_id='" . (int)$a->topic_id . "'");
                mysqli_query($cn, "UPDATE support_ticket_topic SET topic_order='$oa' WHERE topic_id='" . (int)$b->topic_id . "'");
            }
        }

    } elseif ($mode === 'toggle_topic') {
        $id = (int)($_POST['topic_id'] ?? 0);
        if ($id) mysqli_query($cn, "UPDATE support_ticket_topic SET topic_active = 1 - topic_active WHERE topic_id='$id'");

    } elseif ($mode === 'delete_topic') {
        $id = (int)($_POST['topic_id'] ?? 0);
        if ($id) mysqli_query($cn, "DELETE FROM support_ticket_topic WHERE topic_id='$id'");
        $msg = 'Onderwerp verwijderd.';
    }
}

// Fetch all topics
$topics = [];
$res = mysqli_query($cn, "SELECT * FROM support_ticket_topic ORDER BY topic_order ASC, topic_id ASC");
if ($res) while ($row = $res->fetch_object()) $topics[] = $row;
$total = count($topics);

// Fetch topic for editing (if edit mode via GET)
$edit_topic = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $er = mysqli_query($cn, "SELECT * FROM support_ticket_topic WHERE topic_id='$eid'");
    if ($er && mysqli_num_rows($er) > 0) $edit_topic = $er->fetch_object();
}
?>
    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="page_tickets.php">Support Tickets</a></li>
        <li class="breadcrumb-item active">Beheer onderwerpen</li>
      </ol>
    </div>
  </header>

  <style>
    .move-btn { padding: 1px 6px; font-size: 0.75rem; line-height: 1.4; border-radius: 3px; }
  </style>

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
            <!-- Topic list -->
            <div class="col-lg-8">
              <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between" style="background:#3c4b64;color:#fff">
                  <h4 class="mb-0">Vooraf ingestelde onderwerpen</h4>
                  <span class="badge badge-light"><?php echo $total; ?> onderwerpen</span>
                </div>
                <div class="card-body p-0">
                  <table class="table table-hover mb-0">
                    <thead>
                      <tr>
                        <th style="width:70px"></th>
                        <th>Label (dropdown)</th>
                        <th>Onderwerp (ingevuld)</th>
                        <th style="width:80px">Actief</th>
                        <th style="width:120px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($topics)) { ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Nog geen onderwerpen aangemaakt.</td></tr>
                      <?php } ?>
                      <?php foreach ($topics as $idx => $tp) { ?>
                        <tr style="<?php echo $tp->topic_active ? '' : 'opacity:0.5'; ?>">
                          <td style="white-space:nowrap">
                            <form method="POST" style="display:inline">
                              <input type="hidden" name="mode" value="move_up">
                              <input type="hidden" name="topic_id" value="<?php echo $tp->topic_id; ?>">
                              <button type="submit" class="btn btn-outline-secondary move-btn" <?php echo $idx === 0 ? 'disabled' : ''; ?>>&#9650;</button>
                            </form>
                            <form method="POST" style="display:inline">
                              <input type="hidden" name="mode" value="move_down">
                              <input type="hidden" name="topic_id" value="<?php echo $tp->topic_id; ?>">
                              <button type="submit" class="btn btn-outline-secondary move-btn" <?php echo $idx === $total - 1 ? 'disabled' : ''; ?>>&#9660;</button>
                            </form>
                          </td>
                          <td><strong><?php echo htmlspecialchars($tp->topic_label); ?></strong></td>
                          <td class="text-muted"><?php echo htmlspecialchars($tp->topic_subject); ?></td>
                          <td>
                            <form method="POST" style="display:inline">
                              <input type="hidden" name="mode" value="toggle_topic">
                              <input type="hidden" name="topic_id" value="<?php echo $tp->topic_id; ?>">
                              <button type="submit" class="btn btn-sm <?php echo $tp->topic_active ? 'btn-success' : 'btn-outline-secondary'; ?>">
                                <?php echo $tp->topic_active ? 'Ja' : 'Nee'; ?>
                              </button>
                            </form>
                          </td>
                          <td>
                            <a href="page_ticket_topics.php?edit=<?php echo $tp->topic_id; ?>" class="btn btn-sm btn-outline-primary mr-1">Bewerken</a>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Onderwerp verwijderen?')">
                              <input type="hidden" name="mode" value="delete_topic">
                              <input type="hidden" name="topic_id" value="<?php echo $tp->topic_id; ?>">
                              <button type="submit" class="btn btn-sm btn-outline-danger">&#10005;</button>
                            </form>
                          </td>
                        </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Add / Edit form -->
            <div class="col-lg-4">
              <div class="card">
                <div class="card-header" style="background:#321fdb;color:#fff">
                  <strong><?php echo $edit_topic ? 'Onderwerp bewerken' : 'Nieuw onderwerp toevoegen'; ?></strong>
                </div>
                <div class="card-body">
                  <form method="POST" action="page_ticket_topics.php">
                    <input type="hidden" name="mode" value="<?php echo $edit_topic ? 'edit_topic' : 'add_topic'; ?>">
                    <?php if ($edit_topic) { ?>
                      <input type="hidden" name="topic_id" value="<?php echo $edit_topic->topic_id; ?>">
                    <?php } ?>

                    <div class="form-group">
                      <label><strong>Label</strong> <small class="text-muted">(getoond in dropdown)</small></label>
                      <input type="text" name="topic_label" class="form-control" required
                             placeholder="bijv. Printer jaamt papier"
                             value="<?php echo htmlspecialchars($edit_topic->topic_label ?? ''); ?>">
                    </div>

                    <div class="form-group">
                      <label><strong>Onderwerp</strong> <small class="text-muted">(ingevuld in veld)</small></label>
                      <input type="text" name="topic_subject" class="form-control" required
                             placeholder="bijv. Printer pakt papier niet goed"
                             value="<?php echo htmlspecialchars($edit_topic->topic_subject ?? ''); ?>">
                    </div>

                    <div class="d-flex justify-content-between">
                      <?php if ($edit_topic) { ?>
                        <a href="page_ticket_topics.php" class="btn btn-secondary">Annuleren</a>
                      <?php } else { ?>
                        <span></span>
                      <?php } ?>
                      <button type="submit" class="btn btn-primary">
                        <?php echo $edit_topic ? 'Opslaan' : 'Toevoegen'; ?>
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
