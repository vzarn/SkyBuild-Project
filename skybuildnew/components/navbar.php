<?php
/* SkyBuild – Reusable Navigation Component */
$current = basename($_SERVER['PHP_SELF']);
?>
<nav id="mainNav">
  <img src="<?php echo ($current === 'estimator.php') ? '' : ''; ?>image.png" alt="SkyBuild Logo">
  <ul>
    <li><a href="index.php" <?php echo ($current === 'index.php') ? 'class="nav-active"' : ''; ?>>Home</a></li>
    <li><a href="<?php echo ($current === 'estimator.php') ? 'index.php#about' : '#about'; ?>" >About</a></li>
    <li><a href="<?php echo ($current === 'estimator.php') ? 'index.php#services' : '#services'; ?>">Services</a></li>
    <li><a href="<?php echo ($current === 'estimator.php') ? 'index.php#contact' : '#contact'; ?>">Contact</a></li>
    <li><a href="estimator.php" <?php echo ($current === 'estimator.php') ? 'class="nav-active"' : ''; ?>>Estimator</a></li>
  </ul>
</nav>
