<?php
session_start();
include 'db.php';

// Handle AJAX pre-quotation save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ajax_save_pre_quote'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid input data']);
        exit;
    }

    $cname   = trim($input['customer_name'] ?? '');
    $cemail  = trim($input['customer_email'] ?? '');
    $cphone  = trim($input['customer_phone'] ?? '');
    $ctype   = trim($input['project_type'] ?? 'full_construction');
    $cbuild  = trim($input['building_type'] ?? 'residential');
    $carea   = floatval($input['area'] ?? 0);
    $cfloors = intval($input['storeys'] ?? 1);
    $cmat    = trim($input['material'] ?? 'standard');
    $ctotal  = floatval($input['estimated_total'] ?? 0);
    $items   = $input['items'] ?? [];

    if (strlen($cname) < 2 || !filter_var($cemail, FILTER_VALIDATE_EMAIL) || empty($cphone) || $carea <= 0) {
        echo json_encode(['success' => false, 'error' => 'Missing or invalid customer information']);
        exit;
    }

    $items_json = json_encode($items);

    $stmt = $conn->prepare("INSERT INTO pre_quotations (customer_name, customer_email, customer_phone, project_type, building_type, sqm, floors, material_level, estimated_total, items_json, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')");
    $stmt->bind_param("sssssddiss", $cname, $cemail, $cphone, $ctype, $cbuild, $carea, $cfloors, $cmat, $ctotal, $items_json);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database save failed']);
    }
    exit;
}

// Handle AJAX pre-quotation status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ajax_update_pre_quote_status'])) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = intval($input['id'] ?? 0);
    $status = trim($input['status'] ?? 'submitted');
    
    if ($id <= 0 || !in_array($status, ['draft', 'submitted'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE pre_quotations SET status = ?, is_viewed = 0 WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database update failed']);
    }
    exit;
}

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
$consult_success = false;
$consult_error = '';
$consult_details = null;
$gen_quote_id = null;

// Read session messages for Post/Redirect/Get pattern
if (isset($_SESSION['consult_success'])) {
  $consult_success = true;
  $consult_details = $_SESSION['consult_details'] ?? null;
  unset($_SESSION['consult_success']);
  unset($_SESSION['consult_details']);
}
if (isset($_SESSION['consult_error'])) {
  $consult_error = $_SESSION['consult_error'];
  unset($_SESSION['consult_error']);
}

function fetch_estimator_inventory_items($conn) {
    $items = [];
    $res_inv = $conn->query("SELECT id, item_name, size, unit, unit_price, updated_at FROM inventory WHERE deleted_at IS NULL ORDER BY item_name ASC, unit_price ASC, id ASC");
    if ($res_inv) {
        while ($row = $res_inv->fetch_assoc()) {
            $items[] = [
                'id' => intval($row['id']),
                'item_name' => $row['item_name'],
                'size' => $row['size'] ?? '',
                'unit' => $row['unit'] ? $row['unit'] : 'pc',
                'unit_price' => floatval($row['unit_price']),
                'updated_at' => $row['updated_at'] ?? null
            ];
        }
    }
    return $items;
}

// Fetch active inventory items and prices for JS calculations
$inventory_data = fetch_estimator_inventory_items($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_inventory_prices'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'items' => fetch_estimator_inventory_items($conn)]);
    exit;
}

// Handle consultation inquiry from estimator
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['est_consult_submit'])) {
  $cname = trim($_POST['contact_name'] ?? '');
  $cemail = trim($_POST['contact_email'] ?? '');
  $cphone = trim($_POST['contact_phone'] ?? '');
  $ctype  = trim($_POST['project_type_hidden'] ?? 'full_construction');
  $cmsg   = trim($_POST['consult_message'] ?? '');

  if (strlen($cname) < 2) $consult_error = "Please enter your name.";
  elseif (!filter_var($cemail, FILTER_VALIDATE_EMAIL)) $consult_error = "Please enter a valid email.";
  elseif (!preg_match('/^[0-9+\-\s]{6,20}$/', $cphone)) $consult_error = "Please enter a valid phone number.";
  elseif (strlen($cmsg) < 10) $consult_error = "Please describe your project (at least 10 characters).";
  else {
    if (!in_array($ctype, $allowed_types)) $ctype = 'others';
    $source = 'estimator';
    $stmt = $conn->prepare("INSERT INTO inquiries (fullname, email, phone, project_type, message, contact_name, contact_number, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $cname, $cemail, $cphone, $ctype, $cmsg, $cname, $cphone, $source);
    if ($stmt->execute()) {
      $_SESSION['consult_success'] = true;
      $_SESSION['consult_details'] = [
        'fullname' => $cname,
        'email' => $cemail,
        'phone' => $cphone,
        'project_type' => $ctype,
        'message' => $cmsg
      ];
      header("Location: estimator.php");
      exit;
    } else {
      $consult_error = "Submission failed. Please try again.";
    }
  }
  if ($consult_error) {
    $_SESSION['consult_error'] = $consult_error;
    header("Location: estimator.php");
    exit;
  }
}

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
  <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
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
          <p>Enter your project details for an instant estimate. Results update live.</p>
        </div>
        <div class="est-header-badges">
          <div class="est-badge">
            <span class="est-badge-icon">⚡</span>
            <span>Live results</span>
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

            <!-- Form hint (no save button) -->
            <div class="form-footer" style="background: transparent; border-top: none; padding-top: 0;">
              <p class="form-hint" style="text-align: left; margin: 0;">Results update live as you type above.</p>
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

            <!-- Basis of Materials -->
            <div class="material-basis-block" id="materialBasisBlock" style="display: none; margin-top: 24px;">
              <span class="compare-label" style="display: block; margin-bottom: 8px;">Estimated Materials Basis</span>
              <div style="background: #fff7e8; border: 1px solid #ead8b8; border-radius: var(--radius); overflow: hidden; max-height: 200px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; margin: 0;">
                  <thead>
                    <tr style="background: #f3e3c6; border-bottom: 1px solid #ead8b8;">
                      <th style="padding: 8px 10px; font-weight: 700; font-size: 10px; text-transform: uppercase; color: #4d3926;">Material</th>
                      <th style="padding: 8px 10px; font-weight: 700; font-size: 10px; text-transform: uppercase; color: #4d3926; text-align: right; width: 75px;">Qty</th>
                      <th style="padding: 8px 10px; font-weight: 700; font-size: 10px; text-transform: uppercase; color: #4d3926; text-align: right; width: 95px;">Unit Price</th>
                      <th style="padding: 8px 10px; font-weight: 700; font-size: 10px; text-transform: uppercase; color: #4d3926; text-align: right; width: 95px;">Cost</th>
                    </tr>
                  </thead>
                  <tbody id="materialBasisRows">
                    <!-- Dynamic rows go here -->
                  </tbody>
                </table>
              </div>
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

      <!-- ── Consultation Request Panel ───────────────────────────────────── -->
      <div class="est-consult-panel" id="consultPanel">
        <div class="est-consult-toggle" onclick="toggleConsult()" id="consultToggle">
          <span>📩 Submit a Consultation Request</span>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="consultChevron"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="est-consult-body" id="consultBody" style="display:none;">
          <?php if ($consult_success): ?>
            <div class="alert-success" style="margin-bottom: 16px;">
              ✓ Your consultation request has been sent! We'll be in touch soon.
              <?php if ($consult_details): ?>
                <div style="margin-top: 10px; padding: 10px; background: rgba(0,0,0,0.03); border-radius: 4px; font-size: 13px; text-align: left; color: var(--text); line-height: 1.5;">
                  <strong>Submitted Details:</strong><br>
                  Name: <?php echo htmlspecialchars($consult_details['fullname']); ?><br>
                  Email: <?php echo htmlspecialchars($consult_details['email']); ?><br>
                  Phone: <?php echo htmlspecialchars($consult_details['phone']); ?><br>
                  Project Type: <?php echo htmlspecialchars($typeLabels[$consult_details['project_type']] ?? $consult_details['project_type']); ?><br>
                  Message: <?php echo nl2br(htmlspecialchars($consult_details['message'])); ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ($consult_error): ?>
            <div class="alert-error" style="margin-bottom: 16px;"><?php echo htmlspecialchars($consult_error); ?></div>
          <?php endif; ?>

          <p style="margin: 0 0 20px 0; color: var(--muted); font-size: 14px;">Use this estimate as a starting point and send a consultation request directly to our team.</p>

          <form method="POST" action="estimator.php" id="consultForm">
            <input type="hidden" name="est_consult_submit" value="1">
            <input type="hidden" name="project_type_hidden" id="consultProjectType" value="full_construction">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
              <div class="form-group" style="margin: 0;">
                <label>Contact Name <span style="color:#cc3333;">*</span></label>
                <input type="text" name="contact_name" id="consultName" class="form-input" placeholder="Juan dela Cruz" required>
              </div>
              <div class="form-group" style="margin: 0;">
                <label>Email Address <span style="color:#cc3333;">*</span></label>
                <input type="email" name="contact_email" id="consultEmail" class="form-input" placeholder="juan@email.com" required>
              </div>
            </div>
            <div class="form-group" style="margin-bottom: 16px;">
              <label>Contact Number <span style="color:#cc3333;">*</span></label>
              <input type="tel" name="contact_phone" id="consultPhone" class="form-input" placeholder="+63 917 123 4567" required>
            </div>
            <div class="form-group" style="margin-bottom: 20px;">
              <label>Message / Project Details <span style="color:#cc3333;">*</span></label>
              <textarea name="consult_message" id="consultMsg" class="form-input" rows="4" placeholder="Describe your project — we'll use your estimate as a reference..." required></textarea>
              <div id="consultSuggestedMsg" style="margin-top: 8px; padding: 10px 12px; background: #fff7e8; border: 1px solid #ead8b8; border-radius: var(--radius); color: #5f4730; font-size: 12px; line-height: 1.5;">
                <strong>Suggested message:</strong> Project Type: Full Construction · Estimated Area: sqm · Please provide a detailed quotation for my project.
              </div>
            </div>
            <button type="submit" class="btn" style="width: 100%; padding: 13px;">Send Consultation Request</button>
          </form>

          <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
            <p style="font-size: 13px; color: var(--muted); margin: 0 0 12px 0;">Or let us auto-generate a detailed material quotation from this estimate:</p>
            <button class="btn-ghost btn" style="border: 1px solid var(--border); width: 100%;" onclick="openQuoteModal()">⚡ Generate Detailed Quotation</button>
          </div>
        </div>
      </div>

    </div><!-- /.estimator-wrap -->
  </main>

  <!-- ── Customer-facing Generate Quotation Modal ────────────────────────── -->
  <div id="quoteModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.55); z-index:2000; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
    <div style="background:#fff; border-radius: var(--radius); padding: 24px; width: 100%; max-width: 400px; max-height: 82vh; overflow-y: auto; margin: 16px; box-shadow: 0 18px 30px -14px rgba(0,0,0,0.25);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px;">
        <h2 style="margin:0; font-size: 19px; font-weight: 600; letter-spacing: 0; color: var(--text);">⚡ Generate Quotation</h2>
        <button onclick="closeQuoteModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:var(--muted); line-height:1;">×</button>
      </div>
      <p style="font-size: 13px; color: var(--muted); margin: 0 0 18px 0; line-height: 1.5;">Enter your details to save a detailed material quotation draft for your project.</p>
      
      <form id="customerQuoteForm" onsubmit="generateCustomerQuote(event)">
        <div class="form-group" style="margin-bottom: 14px;">
          <label style="font-weight: 600; font-size: 12px; color: var(--muted); text-transform: uppercase; display: block; margin-bottom: 6px;">Your Full Name <span style="color:#cc3333;">*</span></label>
          <input type="text" id="custName" class="form-input" required placeholder="e.g. Juan dela Cruz">
        </div>
        <div class="form-group" style="margin-bottom: 14px;">
          <label style="font-weight: 600; font-size: 12px; color: var(--muted); text-transform: uppercase; display: block; margin-bottom: 6px;">Email Address <span style="color:#cc3333;">*</span></label>
          <input type="email" id="custEmail" class="form-input" required placeholder="e.g. juan@email.com">
        </div>
        <div class="form-group" style="margin-bottom: 20px;">
          <label style="font-weight: 600; font-size: 12px; color: var(--muted); text-transform: uppercase; display: block; margin-bottom: 6px;">Phone Number <span style="color:#cc3333;">*</span></label>
          <input type="tel" id="custPhone" class="form-input" required placeholder="e.g. 0917 123 4567">
        </div>
        
        <p style="font-size: 12px; color: var(--muted); margin: -8px 0 16px 0; font-style: italic; line-height: 1.4;">* Note: A copy of this generated quotation will be saved to our system for your reference and follow-up.</p>
        
        <button type="submit" class="btn" style="width:100%; padding:12px; font-size: 14px; font-weight: 600; border-radius: var(--radius);">Generate</button>
      </form>
    </div>
  </div>

  <!-- ── Full-Page Print-Ready Quotation Preview Modal ────────────────────── -->
  <div id="quotePreviewModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:2100; overflow-y:auto; padding: 40px 20px; box-sizing: border-box;" onclick="handlePreviewOverlayClick(event)">
    <div style="background:#fff; width:100%; max-width: 850px; margin: 0 auto; border-radius: var(--radius); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); display: flex; flex-direction: column;">
      
      <!-- Preview Header Controls (Not visible during print) -->
      <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; padding: 16px 24px; border-bottom: 1px solid var(--border); background: #fdfdfd; border-top-left-radius: var(--radius); border-top-right-radius: var(--radius);">
        <span style="font-weight: 600; font-size: 14px; color: var(--muted);">Quotation Preview</span>
        <div style="display: flex; gap: 10px; align-items: center;">
          <button id="sendPreQuoteBtn" onclick="submitPendingPreQuote()" class="btn" style="padding: 8px 16px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; background: #c2410c; color: #fff; border: none; transition: background 0.15s ease;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="22" y1="2" x2="11" y2="13"></line>
              <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
            <span>Send to Pre-Quotation</span>
          </button>
          <button onclick="printQuotation()" class="btn" style="padding: 8px 16px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; background: var(--text); color: #fff;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 6 2 18 2 18 9" />
              <path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
              <rect x="6" y="14" width="12" height="8" />
            </svg>
            Print
          </button>
          <button onclick="closePreviewModal()" class="btn-ghost btn" style="border: 1px solid var(--border); padding: 8px 16px; font-size: 13px; font-weight: 600;">Close</button>
        </div>
      </div>

      <!-- Alert Banner (Not visible during print) -->
      <div id="preQuoteSentAlert" class="no-print" style="display:none; padding: 12px 24px; background: #ecfdf5; border-bottom: 1px solid #a7f3d0; color: #065f46; font-size: 14px; font-weight: 500; text-align: center;">
        ✓ Pre-quotation sent successfully! Your estimate is now saved under Quote Number <span id="alertQuoteNum" style="font-weight: 700;"></span>.
      </div>

      <!-- Print-Ready Printable Area -->
      <div id="printArea" style="padding: 48px; color: #1a1a1a; font-family: 'Inter', system-ui, sans-serif; line-height: 1.5; font-size: 14px; background: #fff;">
        
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items: flex-start; margin-bottom: 40px; border-bottom: 2px solid #1a1a1a; padding-bottom: 20px;">
          <div>
            <img src="image.png" alt="NATH Hardware Logo" style="height: 90px; width: auto; margin-bottom: 12px; display: block;">
            <div style="font-weight: 700; font-size: 18px; letter-spacing: -0.5px;">NATH Hardware & Construction Supplies</div>
            <div style="font-size: 12px; color: #555; margin-top: 4px;">Purok 4, Brgy. San Jose, Santo Tomas, Batangas</div>
            <div style="font-size: 12px; color: #555;">Email: nathhardware@gmail.com | Tel: +63 917 123 4567</div>
          </div>
          <div style="text-align: right;">
            <div style="font-size: 28px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #1a1a1a; margin-bottom: 8px;">QUOTATION</div>
            <div style="font-size: 13px; color: #555;"><strong>Quote No:</strong> <span id="qNumber">PQ-2026-0001</span></div>
            <div style="font-size: 13px; color: #555;"><strong>Date:</strong> <span id="qDate">May 23, 2026</span></div>
            <div style="font-size: 13px; color: #555;"><strong>Valid Until:</strong> <span id="qExpiry">June 23, 2026</span></div>
          </div>
        </div>

        <!-- Info Grid -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px;">
          <div>
            <h4 style="margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; color: #777; letter-spacing: 0.5px;">Client Information</h4>
            <div style="font-size: 15px; font-weight: 700; color: #1a1a1a;" id="qClientName">Juan dela Cruz</div>
            <div style="color: #444; margin-top: 4px;" id="qClientEmail">juan@email.com</div>
            <div style="color: #444;" id="qClientPhone">+63 917 123 4567</div>
          </div>
          <div>
            <h4 style="margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; color: #777; letter-spacing: 0.5px;">Project Configuration</h4>
            <div style="display: grid; grid-template-columns: 100px 1fr; gap: 4px; font-size: 13px;">
              <span style="color:#666;">Project Name:</span> <strong id="qProjName">Dela Cruz Residence</strong>
              <span style="color:#666;">Project Type:</span> <strong id="qProjType">Full Construction</strong>
              <span style="color:#666;">Building Type:</span> <strong id="qBuildingType">Residential</strong>
              <span style="color:#666;">Dimensions:</span> <strong id="qDimensions">120.00 sqm / 2 floors</strong>
              <span style="color:#666;">Material Tier:</span> <strong id="qMaterialTier">Premium</strong>
            </div>
          </div>
        </div>

        <!-- Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 13px;">
          <thead>
            <tr style="border-bottom: 2px solid #1a1a1a; border-top: 1px solid #ddd; background: #fafafa;">
              <th style="padding: 10px 12px; text-align: left; font-weight: 700;">Estimated Material Item Description</th>
              <th style="padding: 10px 12px; text-align: right; font-weight: 700; width: 100px;">Qty</th>
              <th style="padding: 10px 12px; text-align: right; font-weight: 700; width: 60px;">Unit</th>
              <th style="padding: 10px 12px; text-align: right; font-weight: 700; width: 120px;">Unit Price</th>
              <th style="padding: 10px 12px; text-align: right; font-weight: 700; width: 140px;">Total Price</th>
            </tr>
          </thead>
          <tbody id="qItemsRows">
            <!-- Dynamic rows -->
          </tbody>
        </table>

        <!-- Totals & Notes -->
        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 40px; border-top: 1px solid #eee; padding-top: 30px;">
          <div>
            <h4 style="margin: 0 0 8px 0; font-size: 12px; text-transform: uppercase; color: #777; letter-spacing: 0.5px;">Terms & Conditions</h4>
            <ul style="margin: 0; padding-left: 16px; font-size: 11px; color: #555; line-height: 1.6;">
              <li>This is an initial pre-quotation based on cost estimation templates.</li>
              <li>Actual project billing is subject to detailed structural plans and site surveys.</li>
              <li>Item prices reflect current NATH Hardware inventory and are subject to change.</li>
              <li>Labor and construction services estimates are approximate and for reference only.</li>
            </ul>
          </div>
          <div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: #444;">
              <span>Estimated Materials Subtotal:</span>
              <strong id="qMatSubtotal">₱0.00</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: #444;">
              <span>Labor & Construction Services:</span>
              <strong id="qLaborSubtotal">₱0.00</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: #444; border-top: 1px solid #ddd; margin-top: 4px; padding-top: 10px;">
              <span>Subtotal (Excl. VAT):</span>
              <strong id="qSubtotal">₱0.00</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: #444;">
              <span>VAT (12%):</span>
              <strong id="qVat">₱0.00</strong>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 12px 0; border-top: 2px solid #1a1a1a; margin-top: 8px; font-size: 16px;">
              <span style="font-weight: 700; text-transform: uppercase;">Grand Total Estimate:</span>
              <strong style="font-size: 18px; color: #1a1a1a;" id="qGrandTotal">₱0.00</strong>
            </div>
          </div>
        </div>

        <!-- Signature Block -->
        <div style="margin-top: 60px; display: flex; justify-content: space-between; padding-top: 40px; border-top: 1px dashed #ddd;">
          <div>
            <div style="font-size: 11px; color: #777; margin-bottom: 40px;">Prepared By:</div>
            <div style="font-weight: 700; border-bottom: 1px solid #1a1a1a; padding-bottom: 4px; width: 220px; text-align: center;">NATH Hardware & Construction</div>
            <div style="font-size: 11px; color: #555; margin-top: 4px; text-align: center;">System Estimator Agent</div>
          </div>
          <div>
            <div style="font-size: 11px; color: #777; margin-bottom: 40px;">Client Acknowledgment:</div>
            <div style="border-bottom: 1px solid #1a1a1a; padding-bottom: 4px; width: 220px; text-align: center; height: 16px;" id="qClientSign"></div>
            <div style="font-size: 11px; color: #555; margin-top: 4px; text-align: center;">Authorized Signature / Date</div>
          </div>
        </div>

      </div><!-- /#printArea -->
      
    </div>
  </div>

  <style>
    .est-consult-panel {
      max-width: 860px;
      margin: 32px auto 0;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }
    .est-consult-toggle {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 18px 24px;
      cursor: pointer;
      font-weight: 600;
      font-size: 15px;
      transition: background 0.15s;
    }
    .est-consult-toggle:hover { background: rgba(0,0,0,0.03); }
    .est-consult-body {
      padding: 24px;
      border-top: 1px solid var(--border);
    }
    .form-input {
      width: 100%;
      box-sizing: border-box;
      padding: 10px 13px;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      font-family: var(--font);
      font-size: 14px;
      background: #fff;
      color: var(--text);
      outline: none;
      transition: border-color 0.15s;
    }
    .form-input:focus { border-color: var(--text); box-shadow: 0 0 0 3px rgba(28,24,20,0.08); }
    .alert-success { padding: 12px 16px; background: #f4fbf4; border: 1px solid #c3e6c3; color: #2a5e2a; border-radius: var(--radius); font-size: 14px; }
    .alert-error   { padding: 12px 16px; background: #fdf5f5; border: 1px solid #f5c6c6; color: #7a2020; border-radius: var(--radius); font-size: 14px; }

    @media print {
      body.printing-quotation * {
        visibility: hidden;
      }
      body.printing-quotation #quotePreviewModal,
      body.printing-quotation #quotePreviewModal *,
      body.printing-quotation #printArea,
      body.printing-quotation #printArea * {
        visibility: visible;
      }
      body.printing-quotation #quotePreviewModal {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: auto;
        background: none !important;
        padding: 0;
        overflow: visible;
      }
      body.printing-quotation #printArea {
        padding: 0;
        margin: 0;
        border: none;
        box-shadow: none;
        width: 100%;
      }
      body.printing-quotation .no-print {
        display: none !important;
      }
      body.printing-quotation nav,
      body.printing-quotation footer,
      body.printing-quotation .estimator-wrap {
        display: none !important;
      }
    }
  </style>


  <?php include 'components/footer.php'; ?>

  <script>
    let INVENTORY_ITEMS = <?php echo json_encode($inventory_data); ?>;
    let inventoryRefreshPromise = null;
    let materialBasisVisible = false;

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

    async function refreshInventoryItems() {
      if (inventoryRefreshPromise) return inventoryRefreshPromise;
      inventoryRefreshPromise = fetch('estimator.php?ajax_inventory_prices=1', { cache: 'no-store' })
        .then(response => response.json())
        .then(result => {
          if (result.success && Array.isArray(result.items)) {
            INVENTORY_ITEMS = result.items;
            updateDisplay();
          }
        })
        .catch(() => {})
        .finally(() => {
          inventoryRefreshPromise = null;
        });
      return inventoryRefreshPromise;
    }

    function cleanInventoryText(value) {
      return String(value || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
    }

    function getInventoryMatch(name, materialLevel = 'standard') {
      const needle = cleanInventoryText(name);
      const candidates = INVENTORY_ITEMS.filter(item => {
        const itemName = cleanInventoryText(item.item_name);
        return itemName === needle || itemName.includes(needle) || needle.includes(itemName);
      }).sort((a, b) => (parseFloat(a.unit_price) || 0) - (parseFloat(b.unit_price) || 0));

      if (!candidates.length) {
        return { itemName: name, displayName: name, price: 0, unit: 'pc', inventoryId: null, inInventory: false };
      }

      let index = 0;
      if (materialLevel === 'premium') {
        index = Math.floor((candidates.length - 1) / 2);
      } else if (materialLevel === 'luxury') {
        index = candidates.length - 1;
      }

      const match = candidates[index];
      const size = match.size ? ` (${match.size})` : '';
      return {
        itemName: match.item_name,
        displayName: `${match.item_name}${size}`,
        price: parseFloat(match.unit_price) || 0,
        unit: match.unit || 'pc',
        inventoryId: match.id || null,
        inInventory: true
      };
    }

    function getMaterialsBasis(type, building, materialLevel, area, storeys) {
      const items = [];
      
      if (type === 'electrical') {
        let wireQty = Math.max(1, Math.ceil(area * 0.50));
        let conduitQty = Math.max(1, Math.ceil(area * 0.35));
        let outletQty = Math.max(1, Math.ceil(area * 0.12));
        let breakerQty = Math.max(1, Math.ceil(area * 0.03));
        let panelQty = Math.max(1, Math.ceil(area * 0.005));

        if (building === 'commercial') {
          outletQty = Math.ceil(outletQty * 1.5);
          panelQty = Math.ceil(panelQty * 1.2);
        } else if (building === 'industrial') {
          conduitQty = Math.ceil(conduitQty * 1.3);
          breakerQty = Math.ceil(breakerQty * 1.4);
        }

        items.push({ name: 'THHN/THWN Wire', qty: wireQty });
        items.push({ name: 'PVC Electrical Conduit', qty: conduitQty });
        items.push({ name: 'Convenience Outlet (Universal)', qty: outletQty });
        items.push({ name: 'Circuit Breaker', qty: breakerQty });
        items.push({ name: 'Lighting Panel Board (8-branch)', qty: panelQty });

      } else if (type === 'roofing') {
        let sheetQty = Math.max(1, Math.ceil(area * 0.13));
        let purlinQty = Math.max(1, Math.ceil(area * 0.06));
        let ridgeQty = Math.max(1, Math.ceil(area * 0.04));
        let screwQty = Math.max(1, Math.ceil(area * 0.30));

        if (building === 'industrial' || building === 'commercial') {
          sheetQty = Math.ceil(sheetQty * 1.1);
          purlinQty = Math.ceil(purlinQty * 1.2);
        }

        items.push({ name: 'G.I. Corrugated Roofing Sheet', qty: sheetQty });
        items.push({ name: 'G.I. C-Purlin', qty: purlinQty });
        items.push({ name: 'G.I. Ridgecap', qty: ridgeQty });
        items.push({ name: 'Tekscrew (Self-drilling)', qty: screwQty });

      } else if (type === 'renovation') {
        let cementQty = Math.max(1, Math.ceil(area * 0.30));
        let tileQty = Math.max(1, Math.ceil(area * 1.10));
        let plywoodQty = Math.max(1, Math.ceil(area * 0.04));
        let lumberQty = Math.max(1, Math.ceil(area * 0.15));
        let paintQty = Math.max(1, Math.ceil(area * 0.05));
        let pvcQty = Math.max(1, Math.ceil(area * 0.08));

        if (materialLevel === 'premium') {
          cementQty = Math.ceil(cementQty * 1.1);
          paintQty = Math.ceil(paintQty * 1.2);
        } else if (materialLevel === 'luxury') {
          cementQty = Math.ceil(cementQty * 1.2);
          paintQty = Math.ceil(paintQty * 1.4);
          tileQty = Math.ceil(tileQty * 1.1);
        }

        items.push({ name: 'Portland Cement (Type I)', qty: cementQty });
        items.push({ name: 'Ceramic Floor Tile', qty: tileQty });
        items.push({ name: 'Marine Plywood', qty: plywoodQty });
        items.push({ name: 'Good Lumber', qty: lumberQty });
        items.push({ name: 'Flat Latex Paint', qty: paintQty });
        items.push({ name: 'PVC Pipe (Orange, Pressure)', qty: pvcQty });

      } else if (type === 'full_construction') {
        let cementQty = Math.max(1, Math.ceil(area * 0.40));
        let chbQty = Math.max(1, Math.ceil(area * 12.00));
        let rebarQty = Math.max(1, Math.ceil(area * 0.06));
        let sandQty = Math.max(1, Math.ceil(area * 0.08));
        let gravelQty = Math.max(1, Math.ceil(area * 0.06));
        let plywoodQty = Math.max(1, Math.ceil(area * 0.02));
        let lumberQty = Math.max(1, Math.ceil(area * 0.08));
        let sheetQty = Math.max(1, Math.ceil(area * 0.05));
        let wireQty = Math.max(1, Math.ceil(area * 0.10));
        let pvcQty = Math.max(1, Math.ceil(area * 0.06));
        let tileQty = Math.max(1, Math.ceil(area * 1.00));

        if (storeys > 1) {
          let scale = 1 + 0.12 * (storeys - 1);
          cementQty = Math.ceil(cementQty * scale);
          rebarQty = Math.ceil(rebarQty * (1 + 0.18 * (storeys - 1)));
          sandQty = Math.ceil(sandQty * scale);
          gravelQty = Math.ceil(gravelQty * scale);
          chbQty = Math.ceil(chbQty * (1 + 0.08 * (storeys - 1)));
        }

        let chbName = 'CHB / Hollow Block';
        let rebarName = 'Deformed Bar (Rebar)';
        if (materialLevel === 'standard') {
          // standard
        } else if (materialLevel === 'premium') {
          cementQty = Math.ceil(cementQty * 1.15);
          rebarQty = Math.ceil(rebarQty * 1.1);
        } else if (materialLevel === 'luxury') {
          cementQty = Math.ceil(cementQty * 1.3);
          rebarQty = Math.ceil(rebarQty * 1.25);
        }

        items.push({ name: 'Portland Cement (Type I)', qty: cementQty });
        items.push({ name: chbName, qty: chbQty });
        items.push({ name: rebarName, qty: rebarQty });
        items.push({ name: 'Washed Sand', qty: sandQty });
        items.push({ name: 'Crushed Gravel (3/4 inch)', qty: gravelQty });
        items.push({ name: 'Marine Plywood', qty: plywoodQty });
        items.push({ name: 'Good Lumber', qty: lumberQty });
        items.push({ name: 'G.I. Corrugated Roofing Sheet', qty: sheetQty });
        items.push({ name: 'THHN/THWN Wire', qty: wireQty });
        items.push({ name: 'PVC Pipe (Orange, Pressure)', qty: pvcQty });
        items.push({ name: 'Ceramic Floor Tile', qty: tileQty });
      }

      let totalMaterialsCost = 0;
      const processedItems = items.map(it => {
        const lookup = getInventoryMatch(it.name, materialLevel);
        const price = lookup.price;
        const total = it.qty * price;
        totalMaterialsCost += total;
        return {
          name: lookup.displayName,
          inventory_name: lookup.itemName,
          inventory_id: lookup.inventoryId,
          in_inventory: lookup.inInventory,
          qty: it.qty,
          unit: lookup.unit,
          price: price,
          total: total
        };
      });

      return { items: processedItems, totalMaterialsCost };
    }

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
        const materialBasisBlock = $('materialBasisBlock');
        if (materialBasisBlock) materialBasisBlock.style.display = 'none';
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

      const matBasis = getMaterialsBasis(type, building, material, area, storeys);
      const materialBasisBlock = $('materialBasisBlock');
      if (materialBasisBlock) materialBasisBlock.style.display = materialBasisVisible ? 'block' : 'none';
      const rowsContainer = $('materialBasisRows');
      if (rowsContainer) {
        rowsContainer.innerHTML = '';
        matBasis.items.forEach(it => {
          const tr = document.createElement('tr');
          tr.style.borderBottom = '1px solid rgba(0,0,0,0.05)';
          const materialCell = document.createElement('td');
          materialCell.style.cssText = 'padding: 8px 10px; font-weight: 600; color: #3f3022;';
          materialCell.textContent = it.name;

          const qtyCell = document.createElement('td');
          qtyCell.style.cssText = 'padding: 8px 10px; text-align: right; color: #6b5337;';
          qtyCell.textContent = `${it.qty} ${it.unit}`;

          const priceCell = document.createElement('td');
          priceCell.style.cssText = 'padding: 8px 10px; text-align: right; color: #4f3a25;';
          priceCell.textContent = peso(it.price);

          const totalCell = document.createElement('td');
          totalCell.style.cssText = 'padding: 8px 10px; text-align: right; font-weight: 700; color: #2f2115;';
          totalCell.textContent = peso(it.total);

          tr.append(materialCell, qtyCell, priceCell, totalCell);
          rowsContainer.appendChild(tr);
        });
      }

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

    ['projectType', 'buildingType', 'area', 'storeys', 'projectName'].forEach(id => {
      const el = $(id);
      if (el) { el.addEventListener('input', updateDisplay); el.addEventListener('change', updateDisplay); }
    });
    document.querySelectorAll('input[name="material"]').forEach(r => r.addEventListener('change', updateDisplay));
    window.addEventListener('focus', refreshInventoryItems);

    function toggleStoreys() {
      const hide = ['roofing', 'electrical'].includes($('projectType').value);
      $('storeyField').style.display = hide ? 'none' : '';
      $('dimRow').classList.toggle('no-storeys', hide);
    }
    $('projectType').addEventListener('change', toggleStoreys);
    toggleStoreys();

    const nav = document.querySelector('nav');
    window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 10));

    function printEstimate() {
      document.body.classList.remove('printing-quotation');
      window.print();
    }

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

    updateDisplay();

    <?php if ($consult_success || $consult_error): ?>
    document.getElementById('consultBody').style.display = 'block';
    document.getElementById('consultChevron').style.transform = 'rotate(180deg)';
    <?php endif; ?>

    function toggleConsult() {
      const body = document.getElementById('consultBody');
      const chev = document.getElementById('consultChevron');
      const isOpen = body.style.display !== 'none';
      body.style.display = isOpen ? 'none' : 'block';
      chev.style.transform = isOpen ? '' : 'rotate(180deg)';
      if (!isOpen) {
        const pt = document.getElementById('projectType');
        if (pt) document.getElementById('consultProjectType').value = pt.value;
        const area = document.getElementById('area')?.value || '';
        const type = pt?.options[pt.selectedIndex]?.text || '';
        const suggestionEl = document.getElementById('consultSuggestedMsg');
        if (suggestionEl) {
          suggestionEl.textContent = `Suggested message: Project Type: ${type} · Estimated Area: ${area} sqm · Please provide a detailed quotation for my project.`;
        }
      }
    }

    async function openQuoteModal() {
      await refreshInventoryItems();
      materialBasisVisible = true;
      updateDisplay();
      window.scrollTo({ top: 0, behavior: 'smooth' });
      document.getElementById('quoteModal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    }
    function closeQuoteModal() {
      document.getElementById('quoteModal').style.display = 'none';
      document.body.style.overflow = '';
    }
    
    // Global variables for quotation tracking
    window.currentPreQuoteId = null;

    async function generateCustomerQuote(event) {
      event.preventDefault();
      await refreshInventoryItems();
      
      const cname = document.getElementById('custName').value.trim();
      const cemail = document.getElementById('custEmail').value.trim();
      const cphone = document.getElementById('custPhone').value.trim();
      
      const type = document.getElementById('projectType').value;
      const building = document.getElementById('buildingType').value;
      const material = document.querySelector('input[name="material"]:checked')?.value || 'standard';
      const area = parseFloat(document.getElementById('area').value) || 0;
      const storeys = parseInt(document.getElementById('storeys').value) || 1;
      const pname = document.getElementById('projectName').value.trim();

      if (area <= 0) {
        alert("Please enter a valid project area first.");
        return;
      }

      const r = calc(type, building, material, area);
      const matBasis = getMaterialsBasis(type, building, material, area, storeys);
      
      const estimatedTotal = r.total;
      const laborSubtotal = Math.max(0, r.direct - matBasis.totalMaterialsCost);
      
      const saveData = {
        customer_name: cname,
        customer_email: cemail,
        customer_phone: cphone,
        project_type: type,
        building_type: building,
        area: area,
        storeys: storeys,
        material: material,
        estimated_total: estimatedTotal,
        items: matBasis.items
      };

      try {
        // Save as draft immediately
        const response = await fetch('estimator.php?ajax_save_pre_quote=1', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(saveData)
        });
        
        const result = await response.json();
        if (result.success) {
          window.currentPreQuoteId = result.id;
          
          document.getElementById('qNumber').textContent = `PQ-${new Date().getFullYear()}-${String(result.id).padStart(4, '0')} (Draft)`;
          document.getElementById('qDate').textContent = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
          
          const expiryDate = new Date();
          expiryDate.setMonth(expiryDate.getMonth() + 1);
          document.getElementById('qExpiry').textContent = expiryDate.toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
          
          document.getElementById('qClientName').textContent = cname;
          document.getElementById('qClientEmail').textContent = cemail;
          document.getElementById('qClientPhone').textContent = cphone;
          
          document.getElementById('qProjName').textContent = pname || 'Not Specified';
          document.getElementById('qProjType').textContent = LABELS.type[type];
          document.getElementById('qBuildingType').textContent = LABELS.building[building];
          document.getElementById('qDimensions').textContent = `${area.toLocaleString('en-PH', { minimumFractionDigits: 2 })} sqm / ${storeys} floor${storeys > 1 ? 's' : ''}`;
          document.getElementById('qMaterialTier').textContent = LABELS.material[material];
          
          const rowsContainer = document.getElementById('qItemsRows');
          rowsContainer.innerHTML = '';
          
          matBasis.items.forEach(it => {
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid #eee';
            const materialCell = document.createElement('td');
            materialCell.style.cssText = 'padding: 10px 12px; text-align: left;';
            materialCell.textContent = it.name;

            const qtyCell = document.createElement('td');
            qtyCell.style.cssText = 'padding: 10px 12px; text-align: right;';
            qtyCell.textContent = it.qty.toLocaleString('en-PH');

            const unitCell = document.createElement('td');
            unitCell.style.cssText = 'padding: 10px 12px; text-align: right; color: #555;';
            unitCell.textContent = it.unit;

            const priceCell = document.createElement('td');
            priceCell.style.cssText = 'padding: 10px 12px; text-align: right;';
            priceCell.textContent = peso(it.price);

            const totalCell = document.createElement('td');
            totalCell.style.cssText = 'padding: 10px 12px; text-align: right; font-weight: 600;';
            totalCell.textContent = peso(it.total);

            tr.append(materialCell, qtyCell, unitCell, priceCell, totalCell);
            rowsContainer.appendChild(tr);
          });
          
          const trLabor = document.createElement('tr');
          trLabor.style.borderBottom = '1px solid #eee';
          trLabor.innerHTML = `
            <td style="padding: 10px 12px; text-align: left; font-style: italic; color: #555;">Labor, Equipment & Contractor Services (Estimated)</td>
            <td style="padding: 10px 12px; text-align: right;">1</td>
            <td style="padding: 10px 12px; text-align: right; color: #555;">lot</td>
            <td style="padding: 10px 12px; text-align: right;">${peso(laborSubtotal)}</td>
            <td style="padding: 10px 12px; text-align: right; font-weight: 600;">${peso(laborSubtotal)}</td>
          `;
          rowsContainer.appendChild(trLabor);
          
          document.getElementById('qMatSubtotal').textContent = peso(matBasis.totalMaterialsCost);
          document.getElementById('qLaborSubtotal').textContent = peso(laborSubtotal);
          document.getElementById('qSubtotal').textContent = peso(r.direct);
          document.getElementById('qVat').textContent = peso(r.vat);
          document.getElementById('qGrandTotal').textContent = peso(r.total);
          document.getElementById('qClientSign').textContent = cname;
          
          // Reset button and alert states
          const sendBtn = document.getElementById('sendPreQuoteBtn');
          if (sendBtn) {
            sendBtn.disabled = false;
            sendBtn.style.background = '#c2410c';
            sendBtn.style.opacity = '1';
            sendBtn.innerHTML = `
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
              <span>Send to Pre-Quotation</span>
            `;
          }
          document.getElementById('preQuoteSentAlert').style.display = 'none';
          
          closeQuoteModal();
          document.getElementById('materialBasisBlock')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
          
        } else {
          alert("Failed to save quotation: " + result.error);
        }
      } catch (err) {
        console.error(err);
        alert("An error occurred while communicating with the server.");
      }
    }
    
    async function submitPendingPreQuote() {
      if (!window.currentPreQuoteId) {
        alert("No active quotation draft found.");
        return;
      }
      
      const btn = document.getElementById('sendPreQuoteBtn');
      if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.innerHTML = '<span>Sending...</span>';
      }

      try {
        const response = await fetch('estimator.php?ajax_update_pre_quote_status=1', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            id: window.currentPreQuoteId,
            status: 'submitted'
          })
        });
        
        const result = await response.json();
        if (result.success) {
          const finalQuoteNum = `PQ-${new Date().getFullYear()}-${String(window.currentPreQuoteId).padStart(4, '0')}`;
          document.getElementById('qNumber').textContent = finalQuoteNum;
          
          const alertEl = document.getElementById('preQuoteSentAlert');
          const alertNumEl = document.getElementById('alertQuoteNum');
          if (alertNumEl) alertNumEl.textContent = finalQuoteNum;
          if (alertEl) alertEl.style.display = 'block';
          
          if (btn) {
            btn.style.background = '#15803d'; // Green
            btn.style.opacity = '1';
            btn.innerHTML = `
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12" />
              </svg>
              <span>Sent to Pre-Quotation</span>
            `;
          }
        } else {
          alert("Failed to submit pre-quotation: " + result.error);
          if (btn) {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.style.background = '#c2410c';
            btn.innerHTML = `
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
              </svg>
              <span>Send to Pre-Quotation</span>
            `;
          }
        }
      } catch (err) {
        console.error(err);
        alert("An error occurred while submitting.");
        if (btn) {
          btn.disabled = false;
          btn.style.opacity = '1';
          btn.style.background = '#c2410c';
          btn.innerHTML = `
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="22" y1="2" x2="11" y2="13"></line>
              <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
            <span>Send to Pre-Quotation</span>
          `;
        }
      }
    }
    
    function closePreviewModal() {
      document.getElementById('quotePreviewModal').style.display = 'none';
      document.body.style.overflow = '';
    }

    function handlePreviewOverlayClick(e) {
      if (e.target === document.getElementById('quotePreviewModal')) {
        closePreviewModal();
      }
    }
    
    function printQuotation() {
      document.body.classList.add('printing-quotation');
      const cleanup = () => document.body.classList.remove('printing-quotation');
      window.addEventListener('afterprint', cleanup, { once: true });
      window.print();
      setTimeout(cleanup, 1000);
    }

    document.getElementById('quoteModal').addEventListener('click', function(e) {
      if (e.target === this) closeQuoteModal();
    });

    // Also lock body scroll when preview modal opens
    const _origGenerateClose = closePreviewModal;
    function openPreviewModal() {
      document.getElementById('quotePreviewModal').style.display = 'block';
      document.getElementById('quotePreviewModal').scrollTop = 0;
      document.body.style.overflow = 'hidden';
    }
  </script>

</body>

</html>
