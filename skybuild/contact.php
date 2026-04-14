<section id="contact">
  <h2 class="contact-title">CONTACT US</h2>

  <p class="contact-intro">
    Have a project in mind? Fill out the form below to request a consultation with
    NATH Hardware and Construction Supplies. Our team will review your inquiry and
    contact you as soon as possible.
  </p>

  <div class="contact-wrapper">
    <div class="contact-info">
      <h3>Consultation Details</h3>
      <p>
        We are available to discuss your construction, renovation, roofing, and
        electrical project needs.
      </p>

      <div class="contact-hours">
        <strong>Operating Hours:</strong><br>
        Open daily from 6:00 AM to 7:00 PM
      </div>
    </div>

    <div class="contact-form-box">
      <div class="contact-success" id="contactSuccess" style="display: none;">
        Thank you for contacting us. We have received your consultation request,
        and we will call you as soon as possible.
      </div>

      <form method="post" action="submit_consultation.php" class="contact-form" id="contactForm">
        <label for="fullname">Full Name</label>
        <input
          type="text"
          id="fullname"
          name="fullname"
          required
        >

        <label for="email">Email Address</label>
        <input
          type="email"
          id="email"
          name="email"
          placeholder="example@email.com"
          required
        >

        <label for="country_code">Country Code</label>
        <select id="country_code" name="country_code" required>
          <option value="+63">Philippines (+63)</option>
          <option value="+1">United States (+1)</option>
          <option value="+44">United Kingdom (+44)</option>
          <option value="+61">Australia (+61)</option>
          <option value="+65">Singapore (+65)</option>
          <option value="+971">UAE (+971)</option>
          <option value="+81">Japan (+81)</option>
        </select>

        <label for="phone">Phone Number</label>
        <input
          type="tel"
          id="phone"
          name="phone"
          placeholder="9123456789"
          pattern="[0-9]{7,12}"
          inputmode="numeric"
          maxlength="12"
          required
        >
        <small class="contact-help">Enter numbers only, without spaces or symbols.</small>

        <label for="project_type">Project Type</label>
        <select id="project_type" name="project_type" required>
          <option value="">Select project type</option>
          <option value="full_construction">Full Construction</option>
          <option value="renovation">Renovation</option>
          <option value="roofing">Roofing</option>
          <option value="electrical">Electrical</option>
        </select>

        <label for="message">Project Details</label>
        <textarea
          id="message"
          name="message"
          rows="5"
          placeholder="Tell us about your project..."
          required
        ></textarea>

        <button type="submit" class="btn">Submit Consultation</button>
      </form>
    </div>
  </div>
</section>

<script>
  const contactForm = document.getElementById('contactForm');
  const contactSuccess = document.getElementById('contactSuccess');
  const phoneInput = document.getElementById('phone');

  phoneInput.addEventListener('input', function () {
    this.value = this.value.replace(/[^0-9]/g, '');
  });

  contactForm.addEventListener('submit', function (e) {
    e.preventDefault();

    if (!contactForm.checkValidity()) {
      contactForm.reportValidity();
      return;
    }

    contactSuccess.style.display = 'block';
    contactForm.reset();

    const contactSection = document.getElementById('contact');
    if (contactSection) {
      contactSection.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  });
</script>