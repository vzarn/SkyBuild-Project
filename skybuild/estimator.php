<?php
include 'db.php';

$allowed_types = ['full_construction', 'renovation', 'roofing', 'electrical'];
$allowed_buildings = ['residential', 'commercial', 'institutional', 'industrial', 'agricultural'];
$allowed_materials = ['standard', 'premium', 'luxury'];

$typeLabels = ['full_construction' => 'Full Construction', 'renovation' => 'Renovation', 'roofing' => 'Roofing', 'electrical' => 'Electrical'];
$buildingLabels = ['residential' => 'Residential', 'commercial' => 'Commercial / Office', 'institutional' => 'Institutional', 'industrial' => 'Industrial', 'agricultural' => 'Agricultural'];
$materialLabels = ['standard' => 'Standard', 'premium' => 'Premium', 'luxury' => 'Luxury'];

$buildingRates = ['residential' => 12590.96, 'commercial' => 12059.11, 'institutional' => 13924.23, 'industrial' => 11117.36, 'agricultural' => 6057.38];
$projectMultipliers = ['full_construction' => 1.00, 'renovation' => 0.85, 'roofing' => 0.30, 'electrical' => 0.18];
$materialMultipliers = ['standard' => 1.00, 'premium' => 1.18, 'luxury' => 1.35];
$weeklyOutput = ['full_construction' => 8, 'renovation' => 18, 'roofing' => 35, 'electrical' => 45];

$saved = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['area'])) {
  $projectType = $_POST['type'] ?? '';
  $buildingType = $_POST['building_type'] ?? '';
  $material = $_POST['material'] ?? '';
  $area = (float) ($_POST['area'] ?? 0);
  $storeys = (int) ($_POST['storeys'] ?? 1);

  if (!in_array($projectType, $allowed_types))
    $errors[] = "Invalid project type.";
  if (!in_array($buildingType, $allowed_buildings))
    $errors[] = "Invalid building type.";
  if (!in_array($material, $allowed_materials))
    $errors[] = "Invalid material level.";
  if ($area <= 0 || $area > 999999)
    $errors[] = "Area must be between 1 and 999,999 sqm.";
  if ($storeys < 1 || $storeys > 200)
    $errors[] = "Storeys must be between 1 and 200.";

  if (empty($errors)) {
    $directCost = $area * $buildingRates[$buildingType] * $projectMultipliers[$projectType] * $materialMultipliers[$material];
    $totalCost = $directCost * 1.12;
    try {
      $stmt = $conn->prepare("INSERT INTO estimates (project_type, area, estimated_cost) VALUES (?, ?, ?)");
      $stmt->bind_param("sdd", $projectType, $area, $totalCost);
      $stmt->execute();
      $saved = true;
    } catch (mysqli_sql_exception $e) {
      error_log("Estimator DB error: " . $e->getMessage());
      $errors[] = "Could not save. Please try again.";
    }
  }
}

$fType = htmlspecialchars($_POST['type'] ?? 'full_construction');
$fBuilding = htmlspecialchars($_POST['building_type'] ?? 'residential');
$fMaterial = htmlspecialchars($_POST['material'] ?? 'standard');
$fArea = htmlspecialchars($_POST['area'] ?? '');
$fStoreys = htmlspecialchars($_POST['storeys'] ?? '1');
$fName = htmlspecialchars($_POST['project_name'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description"
    content="Get an instant construction cost estimate — NATH Hardware and Construction Supplies." />
  <title>Project Estimator — NATH Hardware & Construction</title>
  <link rel="stylesheet" href="style.css">
</head>

<body class="estimator-page">

  <?php include 'components/navbar.php'; ?>

  <main>
    <div class="estimator-wrap">

      <!-- ── Page Header ─────────────────────────────────────────────────────── -->
      <div class="estimator-header">
        <div class="est-header-text">
          <span class="small-label">Cost Estimation Tool</span>
          <h1>Project Estimator</h1>
          <p>Enter your project details for an instant estimate. Results update live — no need to submit.</p>
        </div>
        <div class="est-header-badges">
          <div class="est-badge">
            <span class="est-badge-icon">⚡</span>
            <span>Live results</span>
          </div>
          <div class="est-badge">
            <span class="est-badge-icon">₱</span>
            <span>BCAP-based rates</span>
          </div>
          <div class="est-badge">
            <span class="est-badge-icon">✓</span>
            <span>Free to use</span>
          </div>
        </div>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="estimator-errors">
          <?php foreach ($errors as $e): ?>
            <p>· <?php echo $e; ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- ── Two-column layout ────────────────────────────────────────────────── -->
      <div class="estimator-layout">

        <!-- ── Form Column ──────────────────────────────────────────────────── -->
        <div class="est-form-box">

          <?php if ($saved): ?>
            <div class="alert-success est-saved-alert">✓ &nbsp;Estimate saved to our records.</div>
          <?php endif; ?>

          <form method="post" action="estimator.php" id="estimatorForm">

            <!-- Project Details -->
            <div class="form-section">
              <div class="form-section-head">Project Details</div>

              <div class="form-group">
                <label>Project Name <span class="label-opt">optional</span></label>
                <input type="text" name="project_name" id="projectName" placeholder="e.g. Dela Cruz Residence"
                  value="<?php echo $fName; ?>">
              </div>

              <div class="form-group">
                <label>Project Type</label>
                <div class="select-wrap">
                  <select name="type" id="projectType" required>
                    <?php foreach ($typeLabels as $val => $label): ?>
                      <option value="<?php echo $val; ?>" <?php echo ($fType === $val) ? 'selected' : ''; ?>>
                        <?php echo $label; ?></option>
                    <?php endforeach; ?>
                  </select>
                  <svg class="select-arrow" viewBox="0 0 10 6">
                    <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"
                      stroke-linecap="round" />
                  </svg>
                </div>
              </div>
            </div>

            <!-- Dimensions -->
            <div class="form-section">
              <div class="form-section-head">Dimensions</div>

              <div class="form-group">
                <label>Building Type</label>
                <div class="select-wrap">
                  <select name="building_type" id="buildingType" required>
                    <?php foreach ($buildingLabels as $val => $label): ?>
                      <option value="<?php echo $val; ?>" <?php echo ($fBuilding === $val) ? 'selected' : ''; ?>>
                        <?php echo $label; ?></option>
                    <?php endforeach; ?>
                  </select>
                  <svg class="select-arrow" viewBox="0 0 10 6">
                    <path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.5" fill="none"
                      stroke-linecap="round" />
                  </svg>
                </div>
              </div>

              <div class="form-dim-row" id="dimRow">
                <div class="form-group" id="storeyField">
                  <label>Floors / Storeys</label>
                  <input type="number" name="storeys" id="storeys" min="1" max="200" value="<?php echo $fStoreys; ?>"
                    placeholder="2">
                </div>
                <div class="form-group">
                  <label>Total Area <span class="label-unit">sqm</span></label>
                  <input type="number" name="area" id="area" min="1" max="999999" step="0.01" required
                    placeholder="e.g. 120" value="<?php echo $fArea; ?>">
                </div>
              </div>
            </div>

            <!-- Material -->
            <div class="form-section form-section-last">
              <div class="form-section-head">Material Level</div>
              <div class="material-pills">
                <?php foreach ($materialLabels as $val => $label): ?>
                  <label class="pill-label">
                    <input type="radio" name="material" value="<?php echo $val; ?>" <?php echo ($fMaterial === $val) ? 'checked' : ''; ?>>
                    <span>
                      <?php echo $label; ?>
                      <em><?php echo $val === 'standard' ? 'Base rate' : ($val === 'premium' ? '+18%' : '+35%'); ?></em>
                    </span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Sticky footer -->
            <div class="form-footer">
              <button class="btn" type="submit">Save Estimate to Records</button>
              <p class="form-hint">Results update live as you type above.</p>
            </div>

          </form>
        </div><!-- /.est-form-box -->

        <!-- ── Result Column ─────────────────────────────────────────────────── -->
        <div class="est-result-box" id="resultPanel">

          <!-- Placeholder -->
          <div class="result-placeholder" id="resultPlaceholder">
            <div class="placeholder-icon">◻</div>
            <p>Enter your project area above to see an instant cost estimate.</p>
          </div>

          <!-- Live result content -->
          <div class="result-content" id="resultContent">

            <div class="result-header">
              <h3 id="rTitle">Estimate Summary</h3>
              <button class="print-btn" onclick="printEstimate()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="6 9 6 2 18 2 18 9" />
                  <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
                  <rect x="6" y="14" width="12" height="8" />
                </svg>
                Print
              </button>
            </div>

            <!-- Project summary rows -->
            <div class="result-rows">
              <div class="result-row"><span>Project</span><strong id="rType">—</strong></div>
              <div class="result-row"><span>Building</span><strong id="rBuilding">—</strong></div>
              <div class="result-row"><span>Storeys</span><strong id="rStoreys">—</strong></div>
              <div class="result-row"><span>Area</span><strong id="rArea">—</strong></div>
              <div class="result-row result-row-last"><span>Material</span><strong id="rMaterial">—</strong></div>
            </div>

            <!-- Ruler -->
            <hr class="result-ruler">

            <!-- Cost breakdown rows -->
            <div class="result-rows">
              <div class="result-row"><span>Base Cost</span><strong id="rBase">—</strong></div>
              <div class="result-row"><span>VAT (12%)</span><strong id="rVat">—</strong></div>
              <div class="result-row result-row-last"><span>Cost / sqm</span><strong id="rPerSqm">—</strong></div>
            </div>

            <!-- Total -->
            <div class="result-total-block">
              <span>Total Estimate</span>
              <strong id="rTotal">—</strong>
            </div>

            <!-- Timeline -->
            <div class="result-timeline-row">
              <span>Estimated Timeline</span>
              <strong id="rTimeline">—</strong>
            </div>

            <!-- Material comparison -->
            <div class="material-compare">
              <span class="compare-label">Material Tier Comparison</span>
              <div class="compare-item" id="cmpRowStandard">
                <span>Standard</span>
                <div class="compare-track">
                  <div class="compare-fill" id="barStandard"></div>
                </div>
                <strong id="cmpStandard">—</strong>
              </div>
              <div class="compare-item" id="cmpRowPremium">
                <span>Premium</span>
                <div class="compare-track">
                  <div class="compare-fill" id="barPremium"></div>
                </div>
                <strong id="cmpPremium">—</strong>
              </div>
              <div class="compare-item" id="cmpRowLuxury">
                <span>Luxury</span>
                <div class="compare-track">
                  <div class="compare-fill" id="barLuxury"></div>
                </div>
                <strong id="cmpLuxury">—</strong>
              </div>
            </div>

            <p class="estimate-note">
              Initial estimate only. Actual cost and timeline may vary based on site conditions,
              permits, material prices, design changes, labour, and other project-specific factors.
            </p>

          </div><!-- /#resultContent -->
        </div><!-- /.est-result-box -->

      </div><!-- /.estimator-layout -->
    </div><!-- /.estimator-wrap -->
  </main>

  <?php include 'components/footer.php'; ?>

  <script>
    const RATES = {
      building: { residential: 12590.96, commercial: 12059.11, institutional: 13924.23, industrial: 11117.36, agricultural: 6057.38 },
      project: { full_construction: 1.00, renovation: 0.85, roofing: 0.30, electrical: 0.18 },
      material: { standard: 1.00, premium: 1.18, luxury: 1.35 },
      weekly: { full_construction: 8, renovation: 18, roofing: 35, electrical: 45 }
    };
    const LABELS = {
      type: { full_construction: 'Full Construction', renovation: 'Renovation', roofing: 'Roofing', electrical: 'Electrical' },
      building: { residential: 'Residential', commercial: 'Commercial / Office', institutional: 'Institutional', industrial: 'Industrial', agricultural: 'Agricultural' },
      material: { standard: 'Standard', premium: 'Premium', luxury: 'Luxury' }
    };

    function calc(type, building, material, area) {
      const direct = area * RATES.building[building] * RATES.project[type] * RATES.material[material];
      const vat = direct * 0.12;
      const total = direct + vat;
      const weeks = Math.max(1, Math.ceil(area / (RATES.weekly[type] || 18)));
      const perSqm = area > 0 ? total / area : 0;
      return { direct, vat, total, weeks, perSqm };
    }

    function peso(n) {
      return '₱\u202F' + n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const $ = id => document.getElementById(id);
    const set = (id, val) => { const el = $(id); if (el) el.textContent = val; };

    function updateDisplay() {
      const type = $('projectType').value;
      const building = $('buildingType').value;
      const material = document.querySelector('input[name="material"]:checked')?.value || 'standard';
      const area = parseFloat($('area').value) || 0;
      const storeys = parseInt($('storeys').value) || 1;
      const name = $('projectName').value.trim();

      if (area <= 0 || area > 999999) {
        $('resultContent').style.display = 'none';
        $('resultPlaceholder').style.display = 'flex';
        return;
      }

      const r = calc(type, building, material, area);

      set('rTitle', name || 'Estimate Summary');
      set('rType', LABELS.type[type]);
      set('rBuilding', LABELS.building[building]);
      set('rStoreys', storeys + ' floor' + (storeys > 1 ? 's' : ''));
      set('rArea', area.toLocaleString('en-PH', { minimumFractionDigits: 2 }) + ' sqm');
      set('rMaterial', LABELS.material[material]);
      set('rBase', peso(r.direct));
      set('rVat', peso(r.vat));
      set('rTotal', peso(r.total));
      set('rPerSqm', peso(r.perSqm) + ' / sqm');
      set('rTimeline', r.weeks + ' week' + (r.weeks > 1 ? 's' : ''));

      const std = calc(type, building, 'standard', area).total;
      const prm = calc(type, building, 'premium', area).total;
      const lux = calc(type, building, 'luxury', area).total;

      set('cmpStandard', peso(std));
      set('cmpPremium', peso(prm));
      set('cmpLuxury', peso(lux));
      $('barStandard').style.width = (std / lux * 100).toFixed(1) + '%';
      $('barPremium').style.width = (prm / lux * 100).toFixed(1) + '%';
      $('barLuxury').style.width = '100%';

      ['standard', 'premium', 'luxury'].forEach(m => {
        const row = $('cmpRow' + m.charAt(0).toUpperCase() + m.slice(1));
        if (row) row.classList.toggle('compare-active', m === material);
      });

      $('resultContent').style.display = 'block';
      $('resultPlaceholder').style.display = 'none';
    }

    // Listen to all inputs
    ['projectType', 'buildingType', 'area', 'storeys', 'projectName'].forEach(id => {
      const el = $(id);
      if (el) { el.addEventListener('input', updateDisplay); el.addEventListener('change', updateDisplay); }
    });
    document.querySelectorAll('input[name="material"]').forEach(r => r.addEventListener('change', updateDisplay));

    // Storey toggling
    function toggleStoreys() {
      const hide = ['roofing', 'electrical'].includes($('projectType').value);
      $('storeyField').style.display = hide ? 'none' : '';
      $('dimRow').classList.toggle('no-storeys', hide);
    }
    $('projectType').addEventListener('change', toggleStoreys);
    toggleStoreys();

    // Nav scroll
    const nav = document.querySelector('nav');
    window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 10));

    // Print
    function printEstimate() { window.print(); }

    // Page exit transition
    document.querySelectorAll('a[href]').forEach(link => {
      const href = link.getAttribute('href');
      if (href && !href.startsWith('#')) {
        link.addEventListener('click', e => {
          e.preventDefault();
          document.body.classList.add('page-exit');
          setTimeout(() => { window.location.href = href; }, 280);
        });
      }
    });

    // Run on load (pre-filled values from POST)
    updateDisplay();
  </script>

</body>

</html>