<?php
/**
 * Face Detection – walks through the first 5 photostrips of an event
 * and draws bounding boxes around detected faces using MediaPipe Tasks Vision.
 *
 * Comparison version of index.php (which uses face-api.js / SsdMobilenetv1).
 * Key differences vs face-api.js version:
 *  - Uses MediaPipe BlazeFace (short-range) model via @mediapipe/tasks-vision
 *  - No 128-D face descriptors → no cross-strip person identity tracking
 *  - Faces within a single strip are coloured by position only
 *  - Typically faster inference; bounding boxes may differ in position/size
 *
 * Access restricted to admin role only.
 */

// Change the PHP working directory to stats/ so all partial includes resolve correctly.
chdir(dirname(__DIR__));

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['client_id'])) {
    header('Location: https://stats.magicshoot.nl/index.php');
    exit;
}

if ($_SESSION['client_role'] !== 'admin') {
    header('Location: https://stats.magicshoot.nl/page_dashboard.php');
    exit;
}

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

ob_start();
include 'partial_header.php';
echo str_replace('<base href="./">', '<base href="../">', ob_get_clean());

include 'partial_nav.php';
?>

    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo htmlspecialchars(get_event_name($_SESSION['event_id'])); ?></strong></li>
        <li class="breadcrumb-item active">Face Detection (MediaPipe)</li>
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
                    Face Detection (MediaPipe) &mdash; eerste 5 photostrips
                  </h2>
                </div>

                <div class="card-body">

                  <div class="alert alert-info mb-3">
                    <strong>MediaPipe variant</strong> &mdash; gebruikt BlazeFace (short-range model) via
                    <code>@mediapipe/tasks-vision</code>. Geen 128-D face descriptors beschikbaar, dus
                    dezelfde persoon krijgt <em>geen</em> consistente kleur over meerdere strips.
                    Vergelijk met de <a href="index.php">face-api.js versie</a>.
                  </div>

                  <p id="global-status" class="text-muted">
                    <?php if ($event_id === 0): ?>
                      Geen event geselecteerd.
                    <?php elseif (empty($photostrips)): ?>
                      Geen photostrips gevonden voor event <?php echo $event_id; ?>.
                    <?php else: ?>
                      MediaPipe model laden&hellip;
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

<!-- MediaPipe Tasks Vision (includes WASM runtime + BlazeFace model support) -->
<script type="module">
import { FaceDetector, FilesetResolver } from 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest/+esm';

(async function () {
  // Full-range model handles varied distances (group shots, party photos)
  // Short-range is optimised for selfies only and misses small/distant faces
  const MODEL_URL = 'https://storage.googleapis.com/mediapipe-models/face_detector/blaze_face_full_range/float16/1/blaze_face_full_range.tflite';
  const WASM_PATH = 'https://cdn.jsdelivr.net/npm/@mediapipe/tasks-vision@latest/wasm';

  // Number of equal-height slices to cut each photostrip into before detection.
  // A 3-photo strip processed as 3 separate slices gives the model larger faces
  // relative to image size → much better recall in group/party shots.
  const PHOTO_SLICES = 3;

  const strips   = <?php echo $strips_json; ?>;
  const statusEl = document.getElementById('global-status');

  // Colours for up to 8 faces per strip (no cross-strip identity tracking with MediaPipe)
  const PALETTE = ['#FF4444','#44FF88','#FFE44D','#FF44FF','#44DDFF','#FF8C44','#AAFF44','#FF6699'];

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

  // Run detection on a sub-region of a canvas and return detections with
  // coordinates translated back to the full-canvas coordinate space.
  function detectInSlice(detector, srcCanvas, sliceY, sliceH) {
    var tmp = document.createElement('canvas');
    tmp.width  = srcCanvas.width;
    tmp.height = sliceH;
    tmp.getContext('2d').drawImage(srcCanvas, 0, sliceY, srcCanvas.width, sliceH, 0, 0, srcCanvas.width, sliceH);
    var result = detector.detect(tmp);
    // Translate originY back into full-strip coordinates
    return result.detections.map(function (d) {
      return Object.assign({}, d, {
        boundingBox: {
          originX: d.boundingBox.originX,
          originY: d.boundingBox.originY + sliceY,
          width:   d.boundingBox.width,
          height:  d.boundingBox.height,
        },
        // Keep keypoints (normalised to slice), translate Y back
        keypoints: (d.keypoints || []).map(function (kp) {
          return { x: kp.x, y: kp.y * (sliceH / srcCanvas.height) + sliceY / srcCanvas.height };
        }),
      });
    });
  }

  // Simple IoU-based deduplication: drop a box if it overlaps >50% with a higher-scored one
  function deduplicate(detections) {
    detections.sort(function (a, b) { return b.categories[0].score - a.categories[0].score; });
    var kept = [];
    detections.forEach(function (det) {
      var b = det.boundingBox;
      var overlap = kept.some(function (k) {
        var kb = k.boundingBox;
        var ix = Math.max(0, Math.min(b.originX + b.width,  kb.originX + kb.width)  - Math.max(b.originX, kb.originX));
        var iy = Math.max(0, Math.min(b.originY + b.height, kb.originY + kb.height) - Math.max(b.originY, kb.originY));
        var inter = ix * iy;
        var uni   = b.width * b.height + kb.width * kb.height - inter;
        return inter / uni > 0.5;
      });
      if (!overlap) kept.push(det);
    });
    return kept;
  }

  async function processStrip(detector, index) {
    var img     = document.getElementById('img-' + index);
    var canvas  = document.getElementById('canvas-' + index);
    var countEl = document.getElementById('status-' + index);

    try {
      await waitForImage(img);

      canvas.width  = img.naturalWidth;
      canvas.height = img.naturalHeight;
      var ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0);

      // Detect in each slice and merge results
      var sliceH = Math.floor(canvas.height / PHOTO_SLICES);
      var allDetections = [];
      for (var s = 0; s < PHOTO_SLICES; s++) {
        var y = s * sliceH;
        var h = (s === PHOTO_SLICES - 1) ? canvas.height - y : sliceH; // last slice takes remainder
        var sliceResults = detectInSlice(detector, canvas, y, h);
        allDetections = allDetections.concat(sliceResults);
      }

      // Remove aspect-ratio false positives and overlapping duplicates at slice boundaries
      var detections = allDetections.filter(function (d) {
        return isValidFaceBox(d.boundingBox);
      });
      detections = deduplicate(detections);

      if (detections.length === 0) {
        countEl.textContent = 'Geen gezichten gevonden';
        countEl.className   = 'fd-count fd-none';
        return;
      }

      // Sort top-to-bottom (by originY) so photo 1 is processed before 2, etc.
      detections.sort(function (a, b) {
        return a.boundingBox.originY - b.boundingBox.originY;
      });

      var lineW = Math.max(2, Math.round(canvas.width / 150));
      var fs    = Math.max(14, Math.round(canvas.width / 28));

      detections.forEach(function (det, i) {
        var color = PALETTE[i % PALETTE.length];
        var b     = det.boundingBox;                        // { originX, originY, width, height } in px
        var score = Math.round(det.categories[0].score * 100);

        ctx.strokeStyle = color;
        ctx.lineWidth   = lineW;
        ctx.strokeRect(b.originX, b.originY, b.width, b.height);

        ctx.font = 'bold ' + fs + 'px Arial';
        var label  = score + '%';
        var badgeW = ctx.measureText(label).width + 10;
        ctx.fillStyle = color;
        ctx.fillRect(b.originX, b.originY - fs - 6, badgeW, fs + 6);
        ctx.fillStyle = '#000';
        ctx.fillText(label, b.originX + 5, b.originY - 4);

        // Draw the 6 facial keypoints (eyes, nose, mouth corners, ears)
        if (det.keypoints && det.keypoints.length > 0) {
          ctx.fillStyle = color;
          det.keypoints.forEach(function (kp) {
            // keypoints are normalised 0-1; multiply by canvas dimensions
            var kx = kp.x * canvas.width;
            var ky = kp.y * canvas.height;
            ctx.beginPath();
            ctx.arc(kx, ky, Math.max(3, lineW * 2), 0, 2 * Math.PI);
            ctx.fill();
          });
        }
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

  statusEl.textContent = 'MediaPipe model laden\u2026';

  var detector;
  try {
    var vision = await FilesetResolver.forVisionTasks(WASM_PATH);
    detector = await FaceDetector.createFromOptions(vision, {
      baseOptions: {
        modelAssetPath: MODEL_URL,
        delegate: 'GPU',          // falls back to CPU automatically if GPU unavailable
      },
      runningMode: 'IMAGE',
      minDetectionConfidence: 0.2, // lower threshold catches faces in challenging party lighting
      minSuppressionThreshold: 0.3,
    });
  } catch (e) {
    statusEl.textContent = 'Fout bij laden van MediaPipe model: ' + e.message;
    console.error(e);
    return;
  }

  for (var i = 0; i < strips.length; i++) {
    statusEl.textContent = 'Verwerken ' + (i + 1) + ' / ' + strips.length + '\u2026';
    await processStrip(detector, i);
  }

  detector.close();
  statusEl.textContent = 'Klaar \u2014 ' + strips.length + ' strip(s) geanalyseerd met MediaPipe BlazeFace.';
})();
</script>

<?php include 'partial_footer.php'; ?>