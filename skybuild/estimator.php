<?php /* SkyBuild by Cloud – Estimator Page */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Project Estimator | SkyBuild by Cloud</title>
  <link rel="stylesheet" href="style.css">
</head>

<body class="estimator-page">

<nav>
  <img src="image.png" alt="SkyBuild Logo">
  <ul>
    <li><a href="index.php">Home</a></li>
    <li><a href="index.php#about">About</a></li>
    <li><a href="index.php#services">Services</a></li>
    <li><a href="estimator.php">Estimator</a></li>
  </ul>
</nav>

<main>
  <section id="estimator" class="glass card">
    <h3>Project Estimator</h3>

    <form method="post" action="estimator.php">
      <label>Project Type</label>
      <select name="type" required>
        <option value="full_construction" <?php echo (($_POST['type'] ?? '') === 'full_construction') ? 'selected' : ''; ?>>Full Construction</option>
        <option value="renovation" <?php echo (($_POST['type'] ?? '') === 'renovation') ? 'selected' : ''; ?>>Renovation</option>
        <option value="roofing" <?php echo (($_POST['type'] ?? '') === 'roofing') ? 'selected' : ''; ?>>Roofing</option>
        <option value="electrical" <?php echo (($_POST['type'] ?? '') === 'electrical') ? 'selected' : ''; ?>>Electrical</option>
      </select>

      <label>Building Type</label>
      <select name="building_type" required>
        <option value="residential" <?php echo (($_POST['building_type'] ?? '') === 'residential') ? 'selected' : ''; ?>>Residential</option>
        <option value="commercial" <?php echo (($_POST['building_type'] ?? '') === 'commercial') ? 'selected' : ''; ?>>Commercial / Office</option>
        <option value="institutional" <?php echo (($_POST['building_type'] ?? '') === 'institutional') ? 'selected' : ''; ?>>Institutional</option>
        <option value="industrial" <?php echo (($_POST['building_type'] ?? '') === 'industrial') ? 'selected' : ''; ?>>Industrial</option>
        <option value="agricultural" <?php echo (($_POST['building_type'] ?? '') === 'agricultural') ? 'selected' : ''; ?>>Agricultural</option>
      </select>

      <label>Area (sqm)</label>
      <input type="number" name="area" min="1" step="0.01" value="<?php echo htmlspecialchars($_POST['area'] ?? ''); ?>" required>

      <label>Material Level</label>
      <select name="material" required>
        <option value="standard" <?php echo (($_POST['material'] ?? '') === 'standard') ? 'selected' : ''; ?>>Standard</option>
        <option value="premium" <?php echo (($_POST['material'] ?? '') === 'premium') ? 'selected' : ''; ?>>Premium</option>
        <option value="luxury" <?php echo (($_POST['material'] ?? '') === 'luxury') ? 'selected' : ''; ?>>Luxury</option>
      </select>

      <button class="btn" type="submit">Calculate</button>
    </form>

    <?php
      if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['area']) && $_POST['area'] > 0) {

        $projectType  = $_POST['type'] ?? '';
        $buildingType = $_POST['building_type'] ?? '';
        $material     = $_POST['material'] ?? '';
        $area         = (float) ($_POST['area'] ?? 0);

        // Base rates per sqm
        $buildingRates = [
          'residential'   => 12590.96,
          'commercial'    => 12059.11,
          'institutional' => 13924.23,
          'industrial'    => 11117.36,
          'agricultural'  => 6057.38
        ];

        // Project multipliers
        $projectMultipliers = [
          'full_construction' => 1.00,
          'renovation'        => 0.85,
          'roofing'           => 0.30,
          'electrical'        => 0.18
        ];

        // Material multipliers
        $materialMultipliers = [
          'standard' => 1.00,
          'premium'  => 1.18,
          'luxury'   => 1.35
        ];

        // Weekly output
        $weeklyOutput = [
          'full_construction' => 8,
          'renovation'        => 18,
          'roofing'           => 35,
          'electrical'        => 45
        ];

        // Building timeline factor
        $buildingTimeFactor = [
          'residential'   => 1.00,
          'commercial'    => 1.10,
          'institutional' => 1.15,
          'industrial'    => 1.15,
          'agricultural'  => 0.90
        ];

        // Material timeline factor
        $materialTimeFactor = [
          'standard' => 1.00,
          'premium'  => 1.10,
          'luxury'   => 1.20
        ];

        // Labels
        $projectLabels = [
          'full_construction' => 'Full Construction',
          'renovation'        => 'Renovation',
          'roofing'           => 'Roofing',
          'electrical'        => 'Electrical'
        ];

        $buildingLabels = [
          'residential'   => 'Residential',
          'commercial'    => 'Commercial / Office',
          'institutional' => 'Institutional',
          'industrial'    => 'Industrial',
          'agricultural'  => 'Agricultural'
        ];

        $materialLabels = [
          'standard' => 'Standard',
          'premium'  => 'Premium',
          'luxury'   => 'Luxury'
        ];

        $baseRate = $buildingRates[$buildingType] ?? 12590.96;
        $projectMultiplier = $projectMultipliers[$projectType] ?? 1.00;
        $materialMultiplier = $materialMultipliers[$material] ?? 1.00;

        // Direct cost
        $directCost = $area * $baseRate * $projectMultiplier * $materialMultiplier;

        // Add-ons
        $overheadRate = 0.10;
        $profitRate = 0.10;
        $contingencyRate = 0.05;
        $vatRate = 0.12;

        $overhead = $directCost * $overheadRate;
        $profit = $directCost * $profitRate;
        $contingency = $directCost * $contingencyRate;

        $subtotal = $directCost + $overhead + $profit + $contingency;
        $vat = $subtotal * $vatRate;
        $totalCost = $subtotal + $vat;

        // Timeline
        $baseWeeks = $area / ($weeklyOutput[$projectType] ?? 18);
        $timelineFactor =
          ($buildingTimeFactor[$buildingType] ?? 1.00) *
          ($materialTimeFactor[$material] ?? 1.00);

        $weeks = max(1, ceil($baseWeeks * $timelineFactor));

        echo "<div class='result'>";
        echo "<strong>Project Type:</strong> " . htmlspecialchars($projectLabels[$projectType] ?? '-') . "<br>";
        echo "<strong>Building Type:</strong> " . htmlspecialchars($buildingLabels[$buildingType] ?? '-') . "<br>";
        echo "<strong>Area:</strong> " . number_format($area, 2) . " sqm<br>";
        echo "<strong>Material Level:</strong> " . htmlspecialchars($materialLabels[$material] ?? '-') . "<br><br>";

        echo "<strong>Direct Cost:</strong> ₱ " . number_format($directCost, 2) . "<br>";
        echo "<strong>Overhead (10%):</strong> ₱ " . number_format($overhead, 2) . "<br>";
        echo "<strong>Profit (10%):</strong> ₱ " . number_format($profit, 2) . "<br>";
        echo "<strong>Contingency (5%):</strong> ₱ " . number_format($contingency, 2) . "<br>";
        echo "<strong>VAT (12%):</strong> ₱ " . number_format($vat, 2) . "<br><br>";

        echo "<strong>Total Estimated Cost:</strong> ₱ " . number_format($totalCost, 2) . "<br>";
        echo "<strong>Estimated Timeline:</strong> " . $weeks . " week" . ($weeks > 1 ? "s" : "");

        echo "<p class='estimate-note'>
                Note: This is only an initial estimate. Actual project cost and completion time may vary depending on permit requirements, changes in material prices, site conditions, labor availability, design changes, scope of work, electrical load requirements, structural condition, delivery delays, and other client-specific needs.
              </p>";
        echo "</div>";
      }
    ?>
  </section>
</main>

<footer>
  © <?php echo date('Y'); ?> SkyBuild by Cloud
</footer>

</body>
</html>