<?php
// ── Must be first — before any output ──────────────────────────────────────
session_start();
include 'db.php';

// ── CSRF token ──────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Contact form processing (moved here so session_start is already done) ──
$contact_success = false;
$contact_errors  = [];
$allowed_project_types = ['full_construction', 'renovation', 'roofing', 'electrical', 'others'];
$allowed_country_codes = ['+63', '+1', '+44', '+61', '+65'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $contact_errors[] = "Invalid submission. Please refresh and try again.";
    }

    if (empty($contact_errors)) {
        $fullname     = trim($_POST['fullname']     ?? '');
        $email        = trim($_POST['email']        ?? '');
        $country_code = trim($_POST['country_code'] ?? '');
        $phone        = trim($_POST['phone']        ?? '');
        $project_type = trim($_POST['project_type'] ?? '');
        $other_needs  = trim($_POST['other_needs']  ?? '');
        $message      = trim($_POST['message']      ?? '');

        if (strlen($fullname) < 2)                          $contact_errors[] = "Please enter your full name.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $contact_errors[] = "Please enter a valid email address.";
        if (!in_array($country_code, $allowed_country_codes)) $contact_errors[] = "Invalid country code selected.";
        if (!preg_match('/^[0-9]{6,15}$/', $phone))         $contact_errors[] = "Phone number must be 6–15 digits.";
        if (!in_array($project_type, $allowed_project_types)) $contact_errors[] = "Please select a valid project type.";
        
        if ($project_type === 'others' && empty($other_needs)) {
            $contact_errors[] = "Please specify your project type.";
        }
        
        if (strlen($message) < 10)                          $contact_errors[] = "Project details must be at least 10 characters.";

        // If 'others', prepend the specific type to the message
        if ($project_type === 'others') {
            $message = "OTHER PROJECT TYPE: " . $other_needs . "\n\n" . $message;
        }
    }

    if (empty($contact_errors)) {
        $full_phone = $country_code . $phone;
        $stmt = $conn->prepare("INSERT INTO inquiries (fullname, email, phone, project_type, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $fullname, $email, $full_phone, $project_type, $message);
        if ($stmt->execute()) {
            $contact_success = true;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            $contact_errors[] = "Submission failed. Please try again.";
        }
    }
}

// ── Active tab on load (from POST or hash handled by JS) ────────────────────
$open_tab = ($contact_success || !empty($contact_errors)) ? 'contact' : 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="NATH Hardware and Construction Supplies — Modern contractor services focused on clarity, quality, and trusted project delivery." />
  <title>NATH Hardware & Construction Supplies</title>
  <link rel="stylesheet" href="style.css">
</head>

<body class="spa">

<!-- ── Navigation ─────────────────────────────────────────────────────────── -->
<nav>
  <img src="image.png" alt="NATH Hardware Logo">
  <ul>
    <li><a href="#" data-tab="home"     class="tab-link <?php echo $open_tab==='home'?'active':''; ?>">Home</a></li>
    <li><a href="#" data-tab="about"    class="tab-link">About</a></li>
    <li><a href="#" data-tab="services" class="tab-link">Services</a></li>
    <li><a href="#" data-tab="showcase" class="tab-link">Showcase</a></li>
    <li><a href="#" data-tab="contact"  class="tab-link <?php echo $open_tab==='contact'?'active':''; ?>">Contact</a></li>
    <li><a href="estimator.php" class="nav-cta">Estimator</a></li>
  </ul>
</nav>

<!-- ── Panels ─────────────────────────────────────────────────────────────── -->
<main>

  <!-- Home -->
  <div class="panel <?php echo $open_tab==='home'?'active':''; ?>" id="tab-home">
    <div class="home-hero">
      <span class="small-label">NATH Hardware and Construction Supplies</span>
      <h1>Simple.<br>Reliable.<br>Built Smart.</h1>
      <p>Modern contractor services focused on clarity, quality, and trusted project delivery.</p>
      <div class="hero-actions">
        <a href="estimator.php" class="btn">Estimate Your Project</a>
        <button class="btn-ghost tab-link" data-tab="contact">Get in Touch</button>
      </div>
    </div>
  </div>

  <!-- About -->
  <div class="panel" id="tab-about">
    <?php include 'about.php'; ?>
  </div>

  <!-- Services -->
  <div class="panel" id="tab-services">
    <?php include 'services.php'; ?>
  </div>

  <!-- Showcase -->
  <div class="panel" id="tab-showcase">
    <?php include 'showcase.php'; ?>
  </div>

  <!-- Contact -->
  <div class="panel <?php echo $open_tab==='contact'?'active':''; ?>" id="tab-contact">
    <?php include 'contact.php'; ?>
  </div>

</main>

<script>
  const allPanels = document.querySelectorAll('.panel');
  const allLinks  = document.querySelectorAll('.tab-link');

  function activate(name) {
    if (!name || !document.getElementById('tab-' + name)) return;
    
    allPanels.forEach(p => {
      p.classList.toggle('active', p.id === 'tab-' + name);
      // Reset scroll position when switching tabs
      if (p.id === 'tab-' + name) p.scrollTop = 0;
    });
    
    allLinks.forEach(l => l.classList.toggle('active', l.dataset.tab === name));
    
    if (name !== 'home') {
      history.replaceState(null, '', '#' + name);
    } else {
      history.replaceState(null, '', window.location.pathname);
    }
  }

  allLinks.forEach(link => {
    link.addEventListener('click', function (e) {
      if (this.dataset.tab) {
        e.preventDefault();
        activate(this.dataset.tab);
      }
    });
  });

  // Handle deep-link hash on load
  const hash = window.location.hash.slice(1);
  if (hash && document.getElementById('tab-' + hash)) {
    activate(hash);
  }

  // Page exit transition — for real navigation (e.g. clicking Estimator)
  document.querySelectorAll('a[href]').forEach(function (link) {
    const href = link.getAttribute('href');
    if (href && !href.startsWith('#') && !link.dataset.tab) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        document.body.classList.add('page-exit');
        setTimeout(function () { window.location.href = href; }, 280);
      });
    }
  });

  // Auto-hide notifications after 3 seconds
  document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-success, .alert-error');
    if (alerts.length > 0) {
      setTimeout(function() {
        alerts.forEach(function(alert) {
          alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
          alert.style.opacity = '0';
          alert.style.transform = 'translateY(-10px)';
          setTimeout(function() {
            alert.style.display = 'none';
          }, 500);
        });
      }, 3000);
    }
  });
</script>

<div style="position: fixed; bottom: 0; left: 0; width: 100%; z-index: 2000; background: var(--bg);">
  <?php include 'components/footer.php'; ?>
</div>

</body>
</html>