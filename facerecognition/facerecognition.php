<?php
/**
 * Face Detection – walks through the first 10 photostrips of an event
 * and draws bounding boxes around detected faces using face-api.js.
 *
 * Access restricted to admin role only.
 */

// Change the PHP working directory to stats/ so all partial includes
// (partial_header, partial_nav, partial_footer) resolve their own relative
// sub-includes correctly (e.g. partial_header → ../inc/connection.inc.php).
chdir(dirname(__DIR__));

// Start session before any output so we can do the role check first.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['client_id'])) {
    header('Location: https://stats.magicshoot.nl/index.php');
    exit;
}

// Admin-only access
if ($_SESSION['client_role'] !== 'admin') {
    header('Location: https://stats.magicshoot.nl/page_dashboard.php');
    exit;
}

// Connection is included by partial_header; include it here too for the query.
global $cn;
include '../inc/connection.inc.php';

$event_id = isset($_SESSION['event_id']) ? intval($_SESSION['event_id']) : 0;

$photostrips = [];
if ($event_id > 0) {
    $sql = "SELECT filehash, sessie_timestamp
              FROM log_sessionOK
             WHERE event_id = $event_id
             AND filehash <> ''
             ORDER BY sessie_timestamp DESC
             LIMIT 5";
    $result = mysqli_query($cn, $sql);
    while ($row = $result->fetch_object()) {
        $photostrips[] = [
            'filehash'  => $row->filehash,
            'timestamp' => $row->sessie_timestamp,
            'url'       => "/facerecognition/proxy.php?event_id={$event_id}&hash=" . urlencode($row->filehash),
        ];
    }
}

$strips_json = json_encode($photostrips);

// Capture partial_header output and fix <base href> for this subdirectory.
// partial_header.php sets <base href="./"> which would point to facerecognition/;
// we need it to point to stats/ so all vendor/asset paths resolve correctly.
ob_start();
include 'partial_header.php';
echo str_replace('<base href="./">', '<base href="../">', ob_get_clean());

include 'partial_nav.php';
?>

    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo htmlspecialchars(get_event_name($_SESSION['event_id'])); ?></strong></li>
        <li class="breadcrumb-item active">Face Detection</li>
      </ol>
    </div>
  </header>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">

          <div class="row">
            <div class="col-sm-12">
              <div class="card">

                <div class="card-header bg-dark">
                  <h2 class="text-white text-uppercase mb-0">
                    Face Detection &mdash; eerste 10 photostrips
                  </h2>
                </div>

                <div class="card-body">

                  <p id="global-status" class="text-muted">
                    <?php if ($event_id === 0): ?>
                      Geen event geselecteerd.
                    <?php elseif (empty($photostrips)): ?>
                      Geen photostrips gevonden voor event <?php echo $event_id; ?>.
                    <?php else: ?>
                      Face detection modellen laden&hellip;
                    <?php endif; ?>
                  </p>

                  <style>
                    .fd-grid {
                      display: flex;
                      flex-wrap: wrap;
                      gap: 20px;
                    }
                    .fd-card {
                      width: 320px;
                      border: 1px solid #ccc;
                      border-radius: 10px;
                      padding: 10px;
                      background: #f8f9fa;
                    }
                    .fd-card canvas {
                      width: 100%;
                      height: auto;
                      border-radius: 6px;
                      display: block;
                    }
                    .fd-card .fd-meta {
                      margin-top: 6px;
                      font-size: 12px;
                      color: #666;
                    }
                    .fd-card .fd-count {
                      margin-top: 4px;
                      font-size: 13px;
                      font-weight: bold;
                    }
                    .fd-count.fd-found   { color: #28a745; }
                    .fd-count.fd-none    { color: #fd7e14; }
                    .fd-count.fd-loading { color: #6c757d; }
                    .fd-count.fd-error   { color: #dc3545; }
                  </style>

                  <div class="fd-grid">
                    <?php foreach ($photostrips as $i => $strip): ?>
                    <div class="fd-card">
                      <!-- Hidden source image served from same origin to avoid CORS -->
                      <img id="img-<?php echo $i; ?>"
                           src="<?php echo htmlspecialchars($strip['url']); ?>"
                           style="display:none"
                           crossorigin="anonymous">

                      <canvas id="canvas-<?php echo $i; ?>"></canvas>

                      <div class="fd-meta"><?php echo htmlspecialchars($strip['timestamp']); ?></div>
                      <div class="fd-meta"><?php echo htmlspecialchars($strip['url']); ?></div>
                      <div class="fd-count fd-loading" id="status-<?php echo $i; ?>">Verwerken&hellip;</div>
                    </div>
                    <?php endforeach; ?>
                  </div>

                </div><!-- /.card-body -->
              </div><!-- /.card -->
            </div><!-- /.col -->
          </div><!-- /.row -->

        </div>
      </div>
    </main>

    <footer class="c-footer">
      <div><a href="https://stats.magicshoot.nl">Photobooth Beheer</a> &copy; <?php echo date('Y'); ?> MagicShoot.</div>
      <div class="ml-auto">Powered by&nbsp;<a href="https://www.MagicShoot.nl/">MagicShoot</a></div>
    </footer>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
<script>
(function () {
  const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
  const strips    = <?php echo $strips_json; ?>;
  const statusEl  = document.getElementById('global-status');

  // Distinct high-contrast colours for up to 8 people per strip
  const PALETTE = ['#FF4444','#44FF88','#FFE44D','#FF44FF','#44DDFF','#FF8C44','#AAFF44','#FF6699'];

  // Euclidean distance threshold for "same person" (0–~1.2 range; lower = stricter)
  const MATCH_THRESHOLD = 0.50;

  if (strips.length === 0) return;

  function waitForImage(img) {
    return new Promise(function (resolve, reject) {
      if (img.complete && img.naturalHeight !== 0) { resolve(); return; }
      img.onload  = resolve;
      img.onerror = function () { reject(new Error('Kan afbeelding niet laden')); };
    });
  }

  // Reject boxes that are too tall & thin (false positives like border lines)
  function isValidFaceBox(box) {
    var ratio = box.width / box.height;
    return ratio > 0.3 && ratio < 2.5;
  }

  // Return the colour of the closest known identity, or null if none close enough
  function findMatch(descriptor, identities) {
    var bestDist  = Infinity;
    var bestColor = null;
    identities.forEach(function (id) {
      var dist = faceapi.euclideanDistance(descriptor, id.descriptor);
      if (dist < bestDist) {
        bestDist  = dist;
        bestColor = id.color;
      }
    });
    return bestDist < MATCH_THRESHOLD ? bestColor : null;
  }

  async function processStrip(index, identities) {
    var img     = document.getElementById('img-' + index);
    var canvas  = document.getElementById('canvas-' + index);
    var countEl = document.getElementById('status-' + index);

    try {
      await waitForImage(img);

      canvas.width  = img.naturalWidth;
      canvas.height = img.naturalHeight;
      var ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0);

      // Detect + landmarks + 128-D descriptors (needed for person matching)
      var options    = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 });
      var detections = await faceapi
        .detectAllFaces(canvas, options)
        .withFaceLandmarks()
        .withFaceDescriptors();

      // Filter aspect-ratio false positives
      detections = detections.filter(function (d) {
        return isValidFaceBox(d.detection.box);
      });

      if (detections.length === 0) {
        countEl.textContent = 'Geen gezichten gevonden';
        countEl.className   = 'fd-count fd-none';
        return;
      }

      // Sort top-to-bottom so we always process photo 1 before 2 before 3
      detections.sort(function (a, b) {
        return a.detection.box.y - b.detection.box.y;
      });

      // Assign colours: match against global identities (across all strips), or add new one
      var colorMap = []; // parallel array, same index as detections

      detections.forEach(function (det) {
        var matched = findMatch(det.descriptor, identities);
        if (matched) {
          colorMap.push(matched);
        } else {
          var color = PALETTE[identities.length % PALETTE.length];
          identities.push({ descriptor: det.descriptor, color: color });
          colorMap.push(color);
        }
      });

      // Draw boxes and badges
      var lineW = Math.max(2, Math.round(canvas.width / 150));
      var fs    = Math.max(14, Math.round(canvas.width / 28));

      detections.forEach(function (det, i) {
        var color = colorMap[i];
        var b     = det.detection.box;
        var score = Math.round(det.detection.score * 100);

        ctx.strokeStyle = color;
        ctx.lineWidth   = lineW;
        ctx.strokeRect(b.x, b.y, b.width, b.height);

        ctx.font = 'bold ' + fs + 'px Arial';
        var label  = score + '%';
        var badgeW = ctx.measureText(label).width + 10;
        ctx.fillStyle = color;
        ctx.fillRect(b.x, b.y - fs - 6, badgeW, fs + 6);
        ctx.fillStyle = '#000';
        ctx.fillText(label, b.x + 5, b.y - 4);
      });

      var word = detections.length === 1 ? 'gezicht' : 'gezichten';
      countEl.textContent = detections.length + ' ' + word + ' gevonden';
      countEl.className   = 'fd-count fd-found';

    } catch (err) {
      countEl.textContent = 'Fout: ' + err.message;
      countEl.className   = 'fd-count fd-error';
      console.error('Strip ' + index + ':', err);
    }
  }

  async function run() {
    statusEl.textContent = 'Face detection modellen laden\u2026';
    try {
      // Load all three models in parallel
      await Promise.all([
        faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
      ]);
    } catch (e) {
      statusEl.textContent = 'Fout bij laden van modellen: ' + e.message;
      return;
    }

    // Shared identity list — persists across all strips so the same person
    // gets the same colour even when they appear on multiple strips
    var identities = [];

    for (var i = 0; i < strips.length; i++) {
      statusEl.textContent = 'Verwerken ' + (i + 1) + ' / ' + strips.length + '\u2026';
      await processStrip(i, identities);
    }

    statusEl.textContent = 'Klaar \u2014 ' + strips.length + ' strip(s) geanalyseerd, ' + identities.length + ' unieke personen herkend.';
  }

  run();
})();
</script>

<?php include 'partial_footer.php'; ?>
