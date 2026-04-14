<?php /* SkyBuild by Cloud – Minimalist Contractor Website (PHP) */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SkyBuild by Cloud</title>
  
  <link rel="stylesheet" href="style.css">
</head>

<body>

<nav>
  <img src="image.png" alt="SkyBuild Logo">
  <ul>
    <li><a href="#about">About</a></li>
    <li><a href="#services">Services</a></li>
    <li><a href="#estimator">Estimator</a></li>
  </ul>
</nav>

<main>

<section class="hero">
    <h1>Simple. Reliable. Built Smart.</h1>
    <p>Modern contractor services focused on clarity, quality, and trusted project delivery.</p>
    <button class="btn">Estimate Your Project</button>
  </section>

  <?php include 'about.php'; ?>
  <?php include 'services.php'; ?>
  <?php include 'estimator.php'; ?>

</main>

<footer>
  © <?php echo date('Y'); ?> SkyBuild by Cloud
</footer>

</body>
</html>