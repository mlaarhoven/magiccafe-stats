<?php
/**
 * Face Search – upload a photo of a person and find all photostrips for the
 * current event in which that person appears.
 *
 * Workflow:
 *  1. face-api.js models are loaded as soon as the page opens.
 *  2. Admin uploads an image; faces are detected and highlighted.
 *  3. If one face is found the search starts automatically.
 *     If multiple faces are found the admin picks which person to search for.
 *  4. Every photostrip of the event is checked; matches are rendered live.
 *
 * Access restricted to admin role only.
 */

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

$photostrips  = [];
$total_in_db  = 0;

if ($event_id > 0) {
    // Count how many strips the event has in total (for the UI)
    $cnt_sql    = "SELECT COUNT(*) AS cnt FROM log_sessionOK WHERE event_id = $event_id AND filehash <> ''";
    $cnt_result = mysqli_query($cn, $cnt_sql);
    if ($cnt_row = $cnt_result->fetch_object()) {
        $total_in_db = intval($cnt_row->cnt);
    }

    // Load up to 200 strips newest-first
    $sql    = "SELECT filehash, sessie_timestamp
                 FROM log_sessionOK
                WHERE event_id = $event_id
                  AND filehash <> ''
                ORDER BY sessie_timestamp DESC
                LIMIT 3000";
    $result = mysqli_query($cn, $sql);
    while ($row = $result->fetch_object()) {
        $photostrips[] = [
            'filehash'  => $row->filehash,
            'timestamp' => $row->sessie_timestamp,
            'url'       => "/facerecognition/proxy.php?event_id={$event_id}&hash=" . urlencode($row->filehash),
        ];
    }
}

$strips_json      = json_encode($photostrips);
$strips_loaded    = count($photostrips);

ob_start();
include 'partial_header.php';
echo str_replace('<base href="./">', '<base href="../">', ob_get_clean());

include 'partial_nav.php';
?>

    <div class="c-subheader px-3">
      <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item">Home</li>
        <li class="breadcrumb-item"><strong><?php echo htmlspecialchars(get_event_name($_SESSION['event_id'])); ?></strong></li>
        <li class="breadcrumb-item"><a href="index.php">Face Detection</a></li>
        <li class="breadcrumb-item active">Persoon zoeken</li>
      </ol>
    </div>
  </header>

  <div class="c-body">
    <main class="c-main">
      <div class="container-fluid">
        <div class="fade-in">

          <style>
            /* ── Upload zone ──────────────────────────────────────────────── */
            #upload-zone {
              border: 3px dashed #adb5bd;
              border-radius: 12px;
              padding: 40px 20px;
              text-align: center;
              cursor: pointer;
              transition: border-color .2s, background .2s;
              background: #f8f9fa;
            }
            #upload-zone:hover,
            #upload-zone.drag-over {
              border-color: #007bff;
              background: #e8f0fe;
            }
            #upload-zone .upload-icon {
              font-size: 48px;
              color: #6c757d;
              margin-bottom: 10px;
            }

            /* ── Query preview ────────────────────────────────────────────── */
            #query-canvas {
              max-width: 100%;
              height: auto;
              border-radius: 8px;
              display: block;
            }

            /* ── Results grid ─────────────────────────────────────────────── */
            .fd-grid {
              display: flex;
              flex-wrap: wrap;
              gap: 20px;
            }
            .fd-card {
              width: 320px;
              border: 2px solid #28a745;
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
            .fd-card .fd-match {
              margin-top: 4px;
              font-size: 13px;
              font-weight: bold;
              color: #28a745;
            }

            /* ── Misc ─────────────────────────────────────────────────────── */
            #face-buttons .btn { margin-right: 6px; margin-bottom: 6px; }
          </style>

          <!-- ── Row 1: Upload + query preview ─────────────────────────── -->
          <div class="row mb-4">
            <div class="col-12">
              <div class="card">
                <div class="card-header bg-dark d-flex align-items-center justify-content-between">
                  <h2 class="text-white text-uppercase mb-0">
                    Persoon zoeken in photostrips
                  </h2>
                  <a href="index.php" class="btn btn-sm btn-outline-light">
                    &larr; Terug naar Face Detection
                  </a>
                </div>

                <div class="card-body">

                  <?php if ($event_id === 0): ?>
                    <div class="alert alert-warning mb-0">Geen event geselecteerd.</div>

                  <?php elseif (empty($photostrips)): ?>
                    <div class="alert alert-warning mb-0">
                      Geen photostrips gevonden voor event <?php echo $event_id; ?>.
                    </div>

                  <?php else: ?>

                    <!-- Model status -->
                    <div id="model-status" class="alert alert-info mb-3">
                      Face recognition modellen laden&hellip;
                    </div>

                    <!-- Strip info -->
                    <p class="text-muted mb-3">
                      <?php if ($strips_loaded < $total_in_db): ?>
                        <?php echo $strips_loaded; ?> meest recente strips geladen
                        (event heeft er <?php echo $total_in_db; ?> in totaal).
                      <?php else: ?>
                        <?php echo $strips_loaded; ?> strip(s) beschikbaar voor dit event.
                      <?php endif; ?>
                    </p>

                    <!-- Upload zone -->
                    <div id="upload-zone">
                      <input type="file" id="file-input" accept="image/*" style="display:none">
                      <div class="upload-icon">&#128247;</div>
                      <h5>Sleep een foto hierheen of klik om te bladeren</h5>
                      <p class="text-muted mb-0">
                        Upload een foto van de persoon die je wilt zoeken.
                        De foto wordt alleen lokaal verwerkt en niet opgeslagen.
                      </p>
                    </div>

                    <!-- Query preview (hidden until upload) -->
                    <div id="query-section" style="display:none" class="mt-4">
                      <h5>Geüploade foto</h5>
                      <canvas id="query-canvas"></canvas>
                      <p id="query-status" class="mt-2 text-muted"></p>
                      <div id="face-buttons"></div>
                    </div>

                  <?php endif; ?>

                </div><!-- /.card-body -->
              </div><!-- /.card -->
            </div>
          </div>

          <!-- ── Row 2: Search progress (hidden until search starts) ─── -->
          <div id="search-section" style="display:none" class="row mb-4">
            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <strong>Zoekvoortgang</strong>
                </div>
                <div class="card-body">
                  <p id="progress-text" class="mb-2 text-muted">Bezig&hellip;</p>
                  <div class="progress" style="height:20px">
                    <div id="progress-bar"
                         class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                         role="progressbar"
                         style="width:0%"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ── Row 3: Results ──────────────────────────────────────── -->
          <div id="results-section" style="display:none" class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-header bg-success text-white">
                  <strong>Overeenkomsten</strong>
                </div>
                <div class="card-body">
                  <div id="summary-text"></div>
                  <div id="results-grid" class="fd-grid"></div>
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

<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>
<script>
(function () {
  'use strict';

  const MODEL_URL       = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
  const MATCH_THRESHOLD = 0.50;  // Euclidean distance; lower = stricter
  const PALETTE         = ['#FF4444','#44FF88','#FFE44D','#FF44FF','#44DDFF','#FF8C44','#AAFF44','#FF6699'];

  const strips   = <?php echo $strips_json; ?>;
  const EVENT_ID = <?php echo $event_id; ?>;

  // ── DOM refs ───────────────────────────────────────────────────────────────
  const uploadZone     = document.getElementById('upload-zone');
  const fileInput      = document.getElementById('file-input');
  const modelStatus    = document.getElementById('model-status');
  const querySection   = document.getElementById('query-section');
  const queryCanvas    = document.getElementById('query-canvas');
  const queryStatus    = document.getElementById('query-status');
  const faceButtons    = document.getElementById('face-buttons');
  const searchSection  = document.getElementById('search-section');
  const progressBar    = document.getElementById('progress-bar');
  const progressText   = document.getElementById('progress-text');
  const resultsSection = document.getElementById('results-section');
  const resultsGrid    = document.getElementById('results-grid');
  const summaryText    = document.getElementById('summary-text');

  if (!uploadZone) return; // no event selected – nothing to do

  // ── State ──────────────────────────────────────────────────────────────────
  let modelsReady      = false;
  let queryDescriptors = []; // one Float32Array per detected face in the upload

  // ── Model loading ──────────────────────────────────────────────────────────
  async function loadModels() {
    try {
      await Promise.all([
        faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
      ]);
      modelsReady = true;
      modelStatus.className   = 'alert alert-success mb-3';
      modelStatus.textContent = 'Modellen geladen \u2014 upload een foto om te beginnen.';
    } catch (e) {
      modelStatus.className   = 'alert alert-danger mb-3';
      modelStatus.textContent = 'Fout bij laden van modellen: ' + e.message;
    }
  }

  // ── Upload handling ────────────────────────────────────────────────────────
  function handleFile(file) {
    if (!file || !file.type.startsWith('image/')) return;
    var reader = new FileReader();
    reader.onload = function (e) { analyzeUpload(e.target.result); };
    reader.readAsDataURL(file);
  }

  async function analyzeUpload(dataUrl) {
    // Show query section immediately so the user sees something
    querySection.style.display = 'block';
    faceButtons.innerHTML      = '';
    queryStatus.className      = 'mt-2 text-muted';
    queryStatus.textContent    = 'Wachten op modellen\u2026';

    // Clear previous results
    resultsSection.style.display = 'none';
    searchSection.style.display  = 'none';
    resultsGrid.innerHTML        = '';
    summaryText.innerHTML        = '';

    // Wait for models if still loading
    if (!modelsReady) {
      await new Promise(function (resolve) {
        var id = setInterval(function () {
          if (modelsReady) { clearInterval(id); resolve(); }
        }, 200);
      });
    }

    queryStatus.textContent = 'Gezichten detecteren\u2026';

    // Decode image
    var img = new Image();
    img.src = dataUrl;
    await waitForImage(img);

    queryCanvas.width  = img.naturalWidth;
    queryCanvas.height = img.naturalHeight;
    var ctx = queryCanvas.getContext('2d');
    ctx.drawImage(img, 0, 0);

    // Detect faces + 128-D descriptors
    var options    = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 });
    var detections = await faceapi
      .detectAllFaces(queryCanvas, options)
      .withFaceLandmarks()
      .withFaceDescriptors();

    // Filter aspect-ratio noise (thin vertical lines etc.)
    detections = detections.filter(function (d) {
      var r = d.detection.box.width / d.detection.box.height;
      return r > 0.3 && r < 2.5;
    });

    if (detections.length === 0) {
      queryStatus.className   = 'mt-2 text-danger';
      queryStatus.textContent = 'Geen gezicht gevonden in de geüploade foto. Probeer een andere foto.';
      return;
    }

    // Sort top → bottom so numbering matches visual order on the strip
    detections.sort(function (a, b) { return a.detection.box.y - b.detection.box.y; });
    queryDescriptors = detections.map(function (d) { return d.descriptor; });

    // Draw bounding boxes with labels on the query canvas
    var lineW = Math.max(2, Math.round(queryCanvas.width / 150));
    var fs    = Math.max(14, Math.round(queryCanvas.width / 28));

    detections.forEach(function (det, i) {
      var color = PALETTE[i % PALETTE.length];
      var b     = det.detection.box;
      ctx.strokeStyle = color;
      ctx.lineWidth   = lineW;
      ctx.strokeRect(b.x, b.y, b.width, b.height);

      ctx.font = 'bold ' + fs + 'px Arial';
      var label  = detections.length === 1 ? 'Persoon' : 'Persoon ' + (i + 1);
      var badgeW = ctx.measureText(label).width + 10;
      ctx.fillStyle = color;
      ctx.fillRect(b.x, b.y - fs - 6, badgeW, fs + 6);
      ctx.fillStyle = '#000';
      ctx.fillText(label, b.x + 5, b.y - 4);
    });

    if (detections.length === 1) {
      queryStatus.className   = 'mt-2 text-success';
      queryStatus.textContent = '1 gezicht gevonden \u2014 zoeken gestart\u2026';
      startSearch(queryDescriptors[0]);
    } else {
      queryStatus.className   = 'mt-2 text-info';
      queryStatus.textContent = detections.length + ' gezichten gevonden. Kies een persoon om op te zoeken:';

      detections.forEach(function (det, i) {
        var btn = document.createElement('button');
        btn.className   = 'btn btn-sm';
        btn.style.backgroundColor = PALETTE[i % PALETTE.length];
        btn.style.color           = '#000';
        btn.textContent = 'Zoek Persoon ' + (i + 1);
        btn.onclick = function () {
          // Disable all buttons to prevent double-click
          faceButtons.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
          startSearch(queryDescriptors[i]);
        };
        faceButtons.appendChild(btn);
      });
    }
  }

  // ── Strip search ───────────────────────────────────────────────────────────
  async function startSearch(descriptor) {
    searchSection.style.display  = 'block';
    resultsSection.style.display = 'block';
    progressBar.style.width      = '0%';
    progressBar.classList.add('progress-bar-animated');
    resultsGrid.innerHTML  = '';
    summaryText.innerHTML  = '';

    var total      = strips.length;
    var matchCount = 0;

    for (var i = 0; i < strips.length; i++) {
      progressText.textContent = (i + 1) + ' / ' + total + ' strips doorzocht \u2014 '
                                + matchCount + ' overeenkomst(en)';
      progressBar.style.width  = ((i + 1) / total * 100) + '%';

      try {
        var match = await searchStrip(strips[i], descriptor);
        if (match) {
          matchCount++;
          renderResult(strips[i], match.canvas, match.similarity);
        }
      } catch (e) {
        console.warn('Strip fout (' + strips[i].filehash + '):', e.message);
      }
    }

    // Done
    progressBar.classList.remove('progress-bar-animated');
    progressBar.style.width = '100%';
    progressText.textContent = 'Klaar \u2014 ' + matchCount + ' overeenkomst(en) gevonden in ' + total + ' strip(s).';

    if (matchCount === 0) {
      summaryText.innerHTML = '<p class="text-warning font-weight-bold">Geen overeenkomsten gevonden in de doorzochte strips.</p>';
    }
  }

  async function searchStrip(strip, queryDescriptor) {
    var img = new Image();
    img.crossOrigin = 'anonymous';
    img.src = strip.url;
    await waitForImage(img);

    var canvas = document.createElement('canvas');
    canvas.width  = img.naturalWidth;
    canvas.height = img.naturalHeight;
    var ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0);

    var options    = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.3 });
    var detections = await faceapi
      .detectAllFaces(canvas, options)
      .withFaceLandmarks()
      .withFaceDescriptors();

    detections = detections.filter(function (d) {
      var r = d.detection.box.width / d.detection.box.height;
      return r > 0.3 && r < 2.5;
    });

    // Find the detection closest to the query descriptor
    var bestDist = Infinity;
    var bestDet  = null;
    detections.forEach(function (det) {
      var dist = faceapi.euclideanDistance(det.descriptor, queryDescriptor);
      if (dist < bestDist) { bestDist = dist; bestDet = det; }
    });

    if (bestDist > MATCH_THRESHOLD) return null;

    // Highlight the matched face on the canvas
    var lineW      = Math.max(2, Math.round(canvas.width / 150));
    var fs         = Math.max(14, Math.round(canvas.width / 28));
    var similarity = Math.round((1 - bestDist / MATCH_THRESHOLD) * 100);
    var b          = bestDet.detection.box;

    ctx.strokeStyle = '#00FF88';
    ctx.lineWidth   = lineW * 2;
    ctx.strokeRect(b.x, b.y, b.width, b.height);

    ctx.font = 'bold ' + fs + 'px Arial';
    var label  = 'Match ' + similarity + '%';
    var badgeW = ctx.measureText(label).width + 10;
    ctx.fillStyle = '#00FF88';
    ctx.fillRect(b.x, b.y - fs - 6, badgeW, fs + 6);
    ctx.fillStyle = '#000';
    ctx.fillText(label, b.x + 5, b.y - 4);

    return { canvas: canvas, similarity: similarity };
  }

  function renderResult(strip, canvas, similarity) {
    var card = document.createElement('div');
    card.className = 'fd-card';

    canvas.style.cssText = 'width:100%;height:auto;display:block;border-radius:6px;';
    card.appendChild(canvas);

    var meta = document.createElement('div');
    meta.className   = 'fd-meta';
    meta.textContent = strip.timestamp;
    card.appendChild(meta);

    var matchLabel = document.createElement('div');
    matchLabel.className   = 'fd-match';
    matchLabel.textContent = 'Overeenkomst \u2014 ' + similarity + '%';
    card.appendChild(matchLabel);

    var link = document.createElement('a');
    link.href      = 'https://api.magicshoot.nl/event/display.php?event_id=' + EVENT_ID + '&filehash=' + encodeURIComponent(strip.filehash);
    link.target    = '_blank';
    link.rel       = 'noopener noreferrer';
    link.className = 'btn btn-sm btn-outline-success mt-2';
    link.textContent = 'Bekijk photostrip \u2192';
    card.appendChild(link);

    resultsGrid.appendChild(card);
  }

  // ── Helpers ────────────────────────────────────────────────────────────────
  function waitForImage(img) {
    return new Promise(function (resolve, reject) {
      if (img.complete && img.naturalHeight !== 0) { resolve(); return; }
      img.onload  = resolve;
      img.onerror = function () { reject(new Error('Kan afbeelding niet laden: ' + img.src)); };
    });
  }

  // ── Wire up upload zone ────────────────────────────────────────────────────
  fileInput.addEventListener('change', function (e) {
    if (e.target.files[0]) handleFile(e.target.files[0]);
  });

  uploadZone.addEventListener('click', function () { fileInput.click(); });

  uploadZone.addEventListener('dragover', function (e) {
    e.preventDefault();
    uploadZone.classList.add('drag-over');
  });
  uploadZone.addEventListener('dragleave', function () {
    uploadZone.classList.remove('drag-over');
  });
  uploadZone.addEventListener('drop', function (e) {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    if (e.dataTransfer.files[0]) handleFile(e.dataTransfer.files[0]);
  });

  // ── Kick off model loading immediately ────────────────────────────────────
  if (strips.length > 0) loadModels();

})();
</script>

<?php include 'partial_footer.php'; ?>
