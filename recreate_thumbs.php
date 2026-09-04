<?php
include ("partial_header.php");
include ("partial_nav.php");
include ("inc/photo_thumb_functions.php");

if ( $_SESSION['client_role'] <> 'admin' ) {
    echo "You must be logged in as admin to view this page!";
    exit;
}

set_time_limit ( 0 );
?>
    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item">Thumbs opnieuw aanmaken</li>
      </ol>
    </div>
  </header>
  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">
          <div class="card">
            <div class="card-header bg-facebook content-center">
              <h2 class="text-white text-uppercase">Alle thumbs opnieuw aanmaken</h2>
            </div>
            <div class="card-body">
              <pre><?php

$base_path = "/home/magicshoot/domains/magicshoot.nl/public_html/api/event/media/photos/";

$sql_events = "SELECT DISTINCT p.event_id
                 FROM photo p
                 JOIN photobooth_event_ids pei
                   ON pei.event_id = p.event_id
                WHERE p.filename_md5 <> ''
                  AND p.datetime >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                  AND p.event_id NOT IN (83, 98, 115)
                ORDER BY p.event_id ASC";
$result_events = mysqli_query ( $cn , $sql_events );

$event_ids = array();
while ( $event_row = $result_events->fetch_object() )
    {
    $event_ids[] = $event_row->event_id;
    }

$event_lijst_tekst = ( count ( $event_ids ) > 0 ) ? implode ( ", ", $event_ids ) : "geen events gevonden";
echo "Events die verwerkt worden (laatste maand): " . $event_lijst_tekst . "\n";

if ( $_SERVER['REQUEST_METHOD'] <> 'POST' || !isset ( $_POST['confirm_run'] ) )
    {
    ?></pre>
              <script>alert(<?php echo json_encode ( "Events die verwerkt worden (laatste maand): " . $event_lijst_tekst ); ?>);</script>
              <form method="post">
                <label><input type="checkbox" name="force_overwrite" value="1"> Ook bestaande thumbs overschrijven (nodig na aanpassing thumb-formaat)</label><br><br>
                <button type="submit" name="confirm_run" value="1" class="btn btn-sm btn-danger" <?php echo ( count ( $event_ids ) == 0 ) ? "disabled" : ""; ?>>Start: thumbs opnieuw aanmaken voor deze events</button>
              </form>
              <a class="btn btn-sm btn-info" href="page_dashboard_fast.php">Terug naar dashboard</a>
            </div>
          </div>
        </div>
      </div>
    </main>
    <footer class="c-footer">
      <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> &copy; <?php echo date('Y'); ?> MagicShoot.</div>
      <div class="ml-auto">Powered by&nbsp;<a href="https://www.magicshoot.nl/">MagicShoot</a></div>
    </footer>
  </div>
</div>
<?php
    include ("partial_footer.php");
    exit;
    }

$force_overwrite     = isset ( $_POST['force_overwrite'] );

$aantal_ok           = 0;
$aantal_overgeslagen = 0;
$aantal_bestond_al   = 0;

$sql    = "SELECT p.event_id, p.filename_md5
             FROM photo p
             JOIN photobooth_event_ids pei
               ON pei.event_id = p.event_id
            WHERE p.filename_md5 <> ''
              AND p.datetime >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
              AND p.event_id NOT IN (83, 98, 115)
            ORDER BY p.event_id ASC";
$result = mysqli_query ( $cn , $sql );

echo "Bezig...\n";
ob_flush();
flush();

while ( $return = $result->fetch_object() )
    {
    $event_id     = $return->event_id;
    $filename_md5 = $return->filename_md5;

    $source_path = $base_path . $event_id . "/" . $filename_md5 . ".jpg";
    $thumb_dir   = $base_path . $event_id . "/thumbs";
    $thumb_path  = $thumb_dir  . "/" . $filename_md5 . ".jpg";

    if ( is_file ( $thumb_path ) && !$force_overwrite )
      {
      $aantal_bestond_al++;
      }
    elseif ( is_file ( $source_path ) )
        {
        if ( is_dir ( $thumb_dir ) == false )
            {
            mkdir ( $thumb_dir );
            chmod ( $thumb_dir, 0777 );
            }
        create_photo_thumb ( $event_id, $source_path, $thumb_path );
        $aantal_ok++;
        }
    else
        {
        # geen origineel bestand gevonden (bv. event 83, die een andere opslaglocatie gebruikt)
        $aantal_overgeslagen++;
        }

    if ( ( $aantal_ok + $aantal_overgeslagen + $aantal_bestond_al ) % 100 == 0 )
        {
      echo ( $aantal_ok + $aantal_overgeslagen + $aantal_bestond_al ) . " foto's verwerkt...\n";
        ob_flush();
        flush();
        }
    }

echo "\nKlaar!\n";
echo "Thumbs opnieuw aangemaakt: " . $aantal_ok . "\n";
echo "Overgeslagen (thumb bestond al): " . $aantal_bestond_al . "\n";
echo "Overgeslagen (geen bronbestand gevonden): " . $aantal_overgeslagen . "\n";

?></pre>
              <a class="btn btn-sm btn-info" href="page_dashboard_fast.php">Terug naar dashboard</a>
            </div>
          </div>
        </div>
      </div>
    </main>
    <footer class="c-footer">
      <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> &copy; <?php echo date('Y'); ?> MagicShoot.</div>
      <div class="ml-auto">Powered by&nbsp;<a href="https://www.magicshoot.nl/">MagicShoot</a></div>
    </footer>
  </div>
</div>
<?php include("partial_footer.php"); ?>
