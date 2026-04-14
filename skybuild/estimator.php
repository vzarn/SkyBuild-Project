<section id="estimator" class="glass card">
  <h3>Project Estimator</h3>
  <form method="post" action="#estimator">
    <label>Project Type</label>
    <select name="type">
      <option>Renovation</option>
      <option>Roofing</option>
      <option>Electrical</option>
    </select>

    <label>Area (sqm)</label>
    <input type="number" name="area" required>

    <label>Material Level</label>
    <select name="material">
      <option value="1">Standard</option>
      <option value="1.25">Premium</option>
    </select>

    <button class="btn" type="submit">Calculate</button>
  </form>

  <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['area'])) {
      $base = 1200;
      $area = (float)$_POST['area'];
      $material = (float)$_POST['material'];
      
      $cost = $area * $base * $material;
      $weeks = max(2, round($area / 20));
      
      echo "<div class='result'><strong>Estimated Cost:</strong> ₱ " . number_format($cost) . "<br><strong>Timeline:</strong> $weeks weeks</div>";
    }
  ?>
</section>