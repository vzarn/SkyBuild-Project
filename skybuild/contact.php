<?php /* Contact section HTML — variables set by index.php */ ?>

<div class="contact-inner">

  <div class="contact-sidebar">
    <span class="small-label">Contact Us</span>
    <h2 class="section-title">Let's Talk<br>About Your Project</h2>
    <p>We are available for construction, renovation, roofing, and electrical projects.</p>

    <div class="hours-block">
      <strong>Operating Hours</strong><br>
      Open daily · 6:00 AM – 7:00 PM
    </div>

    <div class="service-tags">
      <span>Construction</span>
      <span>Renovation</span>
      <span>Roofing</span>
      <span>Electrical</span>
    </div>
  </div>

  <div class="contact-form-wrap">

    <?php if ($contact_success): ?>
      <div class="alert-success">
        ✓ &nbsp;Your consultation request has been received. We'll be in touch soon.
      </div>
    <?php endif; ?>

    <?php if (!empty($contact_errors)): ?>
      <div class="alert-error">
        <?php foreach ($contact_errors as $err): ?>
          <p>· <?php echo htmlspecialchars($err); ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" class="contact-form" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      <input type="hidden" name="contact_submit" value="1">

      <div class="form-row">
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" name="fullname" placeholder="Juan dela Cruz" required
            value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" placeholder="juan@email.com" required
            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" style="max-width:180px">
          <label>Country Code</label>
          <select name="country_code" required>
            <?php
            $codes = ['+63'=>'PH +63','+1'=>'US +1','+44'=>'UK +44','+61'=>'AU +61','+65'=>'SG +65'];
            foreach ($codes as $val => $label) {
                $sel = (($_POST['country_code'] ?? '+63') === $val) ? 'selected' : '';
                echo "<option value=\"$val\" $sel>$label</option>";
            }
            ?>
          </select>
        </div>
        <div class="form-group">
          <label>Phone Number</label>
          <input type="tel" name="phone" id="phone" placeholder="9171234567" required
            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
        </div>
      </div>

      <div class="form-group">
        <select name="project_type" id="project_type" required>
          <option value="">Select type</option>
          <?php
          $ptypes = ['full_construction'=>'Full Construction','renovation'=>'Renovation','roofing'=>'Roofing','electrical'=>'Electrical','others'=>'Others'];
          foreach ($ptypes as $val => $label) {
              $sel = (($_POST['project_type'] ?? '') === $val) ? 'selected' : '';
              echo "<option value=\"$val\" $sel>$label</option>";
          }
          ?>
        </select>
      </div>

      <div class="form-group" id="other_needs_wrap" style="display: <?php echo (($_POST['project_type'] ?? '') === 'others') ? 'block' : 'none'; ?>;">
        <label>Please Specify</label>
        <input type="text" name="other_needs" id="other_needs" placeholder="Describe your project..."
          value="<?php echo htmlspecialchars($_POST['other_needs'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label>Project Details</label>
        <textarea name="message" rows="4" placeholder="Briefly describe your project..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
      </div>

      <button type="submit" class="btn">Submit Consultation</button>

    </form>
  </div>

</div>

<script>
  const phoneInput = document.getElementById('phone');
  if (phoneInput) {
    phoneInput.addEventListener('input', function () {
      this.value = this.value.replace(/[^0-9]/g, '');
    });
  }

  const projectTypeSelect = document.getElementById('project_type');
  const otherNeedsWrap = document.getElementById('other_needs_wrap');
  if (projectTypeSelect && otherNeedsWrap) {
    projectTypeSelect.addEventListener('change', function () {
      otherNeedsWrap.style.display = (this.value === 'others') ? 'block' : 'none';
      if (this.value === 'others') {
        document.getElementById('other_needs').focus();
      }
    });
  }
</script>