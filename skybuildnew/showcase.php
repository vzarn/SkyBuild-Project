<div class="showcase-inner">
  <span class="small-label">Portfolio</span>
  <h2 class="section-title">Our Work</h2>
  <p>A selection of completed works highlighting residential and commercial projects delivered with quality workmanship and practical design.</p>

  <div class="showcase-grid">

    <?php
    include 'db.php';
    $res = $conn->query("SELECT * FROM showcase ORDER BY created_at DESC");
    if ($res && $res->num_rows > 0):
      while ($proj = $res->fetch_assoc()):
    ?>
      <div class="showcase-card">
        <div class="showcase-image">
          <img src="<?php echo htmlspecialchars($proj['image_path']); ?>" 
               alt="<?php echo htmlspecialchars($proj['title']); ?>"
               onclick="openLightbox(this.src)">
        </div>
        <div class="showcase-info">
          <h3><?php echo htmlspecialchars($proj['title']); ?></h3>
          <p><?php echo nl2br(htmlspecialchars($proj['description'])); ?></p>
        </div>
      </div>
    <?php 
      endwhile;
    else:
    ?>
      <p style="grid-column: 1/-1; text-align: center; color: var(--muted); padding: 40px;">No projects to show at the moment.</p>
    <?php endif; ?>

  </div>
</div>

<!-- Lightbox Modal -->
<div id="lightboxModal" onclick="if(event.target === this) closeLightbox()">
  <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
  <img id="lightboxImg" onclick="toggleZoom(this)">
</div>

<style>
.showcase-image img { cursor: zoom-in; transition: opacity 0.3s; }
.showcase-image img:hover { opacity: 0.8; }
#lightboxModal {
  display: none; position: fixed; z-index: 9999; left: 0; top: 0; 
  width: 100vw; height: 100vh; background-color: rgba(0,0,0,0.9);
  align-items: center; justify-content: center; overflow: auto;
}
#lightboxImg {
  max-width: 90%; max-height: 90vh; margin: auto; display: block;
  transition: transform 0.3s ease; cursor: zoom-in;
}
#lightboxImg.zoomed { transform: scale(2); cursor: zoom-out; }
.lightbox-close {
  position: absolute; top: 20px; right: 35px; color: #f1f1f1;
  font-size: 40px; font-weight: bold; cursor: pointer; z-index: 10000;
}
.lightbox-close:hover { color: #bbb; }
</style>

<script>
function openLightbox(src) {
  const modal = document.getElementById('lightboxModal');
  const img = document.getElementById('lightboxImg');
  img.src = src;
  img.className = ''; // reset zoom
  modal.style.display = 'flex';
}

function closeLightbox() {
  document.getElementById('lightboxModal').style.display = 'none';
}

function toggleZoom(img) {
  img.classList.toggle('zoomed');
}

// Add click events to images after DOM is fully loaded or immediately if already loaded
function attachLightboxEvents() {
  const images = document.querySelectorAll('.showcase-image img');
  images.forEach(img => {
    // Prevent duplicate event listeners if this runs multiple times
    img.removeEventListener('click', imgClickHandler);
    img.addEventListener('click', imgClickHandler);
  });
}

function imgClickHandler() {
    openLightbox(this.src);
}

if (document.readyState === 'loading') {
    document.addEventListener("DOMContentLoaded", attachLightboxEvents);
} else {
    attachLightboxEvents();
}
</script>