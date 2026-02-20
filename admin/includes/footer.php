<?php
/**
 * Admin Footer Include — admin/includes/footer.php
 * Closes the main content + sidebar wrappers opened in header.php,
 * then renders the full 3-column footer identical to the customer site.
 */
if (!defined('BASE_URL')) define('BASE_URL', '/purge-coffee');
?>

  </main><!-- /.admin-wrapper -->

</div><!-- /.admin-body -->

<!-- ── Footer — identical to customer site ────────────────── -->
<footer class="footer">
  <div class="container">
    <div class="footer-content">

      <!-- Brand & Contact -->
      <div class="footer-section">
        <div class="footer-brand">
          <span class="footer-brand-name">Purge Coffee</span>
        </div>
        <div class="footer-contact">
          <p><i class="fas fa-phone"></i> 0960 315 0070</p>
          <p><i class="fas fa-envelope"></i> purgecoffee@gmail.com</p>
        </div>
      </div>

      <!-- Policies -->
      <div class="footer-section">
        <h3>Our Policies</h3>
        <ul class="footer-links">
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms Of Use</a></li>
          <li><a href="#">Shipping &amp; Delivery</a></li>
        </ul>
      </div>

      <!-- Social -->
      <div class="footer-section">
        <h3>Social Media</h3>
        <div class="social-icons">
          <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
        </div>
      </div>

    </div>

    <hr>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> Purge Coffee | All Rights Reserved</p>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>