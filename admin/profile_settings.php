<?php
/**
 * Purge Coffee — Admin Profile Settings (admin/profile_settings.php)
 * Allows the admin to update their name, email, password, and profile image.
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Fetch current admin record
$stmt = mysqli_prepare($conn,
    "SELECT full_name, email, mobile_number, profile_image FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

require 'includes/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1>Profile Settings</h1>
    <p>Update your account information and password</p>
  </div>
</div>

<div id="ps-alert-zone" style="max-width:680px;margin-bottom:12px;"></div>

<div class="ps-card">

  <!-- Account info section -->
  <p class="ps-section-hd">Account Information</p>
  <form id="psForm" onsubmit="saveAdminProfile(event)">
    <div class="ps-form-grid">
      <div class="ps-field">
        <label>FULL NAME</label>
        <input type="text" name="full_name" id="ps-full-name"
               value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>" required />
      </div>
      <div class="ps-field">
        <label>EMAIL ADDRESS</label>
        <input type="email" name="email" id="ps-email"
               value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required />
      </div>
      <div class="ps-field">
        <label>MOBILE NUMBER</label>
        <input type="tel" name="mobile_number"
               value="<?= htmlspecialchars($admin['mobile_number'] ?? '') ?>"
               placeholder="+63 9XX XXX XXXX"
               maxlength="16"
               pattern="(\+63|0)[0-9]{10}"
               title="Format: +63 9XX XXX XXXX or 09XXXXXXXXX" />
      </div>
    </div>

    <!-- Password change section -->
    <p class="ps-section-hd">Change Password <span style="font-weight:400;opacity:.6;">(leave blank to keep current)</span></p>
    <div class="ps-form-grid">
      <div class="ps-field">
        <label>CURRENT PASSWORD</label>
        <div class="ps-pw-wrap">
          <input type="password" name="current_password" id="ps-pw-cur" autocomplete="current-password" />
          <button type="button" class="ps-pw-toggle" onclick="togglePw('ps-pw-cur', this)" aria-label="Toggle visibility">
            <i class="fas fa-eye-slash"></i>
          </button>
        </div>
      </div>
      <div class="ps-field">
        <label>NEW PASSWORD</label>
        <div class="ps-pw-wrap">
          <input type="password" name="new_password" id="ps-pw-new" autocomplete="new-password" />
          <button type="button" class="ps-pw-toggle" onclick="togglePw('ps-pw-new', this)" aria-label="Toggle visibility">
            <i class="fas fa-eye-slash"></i>
          </button>
        </div>
      </div>
      <div class="ps-field">
        <label>CONFIRM NEW PASSWORD</label>
        <div class="ps-pw-wrap">
          <input type="password" name="confirm_password" id="ps-pw-confirm" autocomplete="new-password" />
          <button type="button" class="ps-pw-toggle" onclick="togglePw('ps-pw-confirm', this)" aria-label="Toggle visibility">
            <i class="fas fa-eye-slash"></i>
          </button>
        </div>
      </div>
    </div>

    <div class="ps-form-actions">
      <button type="submit" class="ps-btn-save" id="psSaveBtn">Save Changes</button>
    </div>
  </form>

</div>

<script>
  /* Toggle password visibility: eye-slash (hidden) ↔ eye (visible) */
  function togglePw(inputId, btn) {
    var input = document.getElementById(inputId);
    var icon  = btn.querySelector('i');
    if (input.type === 'password') {
      input.type = 'text';
      icon.classList.replace('fa-eye-slash', 'fa-eye');
    } else {
      input.type = 'password';
      icon.classList.replace('fa-eye', 'fa-eye-slash');
    }
  }

  /* Save admin profile via the shared update_profile endpoint */
  function saveAdminProfile(e) {
    e.preventDefault();
    const zone   = document.getElementById('ps-alert-zone');
    const btn    = document.getElementById('psSaveBtn');
    const newPw  = document.getElementById('ps-pw-new').value;
    const confPw = document.getElementById('ps-pw-confirm').value;

    if (newPw && newPw !== confPw) {
      zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> Passwords do not match.</div>';
      return;
    }
    if (newPw && newPw.length < 8) {
      zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> Password must be at least 8 characters.</div>';
      return;
    }

    btn.disabled    = true;
    btn.textContent = 'Saving…';

    const fd          = new FormData(document.getElementById('psForm'));
    const avatarInput = document.getElementById('adminAvatarFileInput');
    if (avatarInput && avatarInput.files[0]) fd.append('avatar', avatarInput.files[0]);

    fetch('<?= BASE_URL ?>/php/update_profile.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          zone.innerHTML = '<div class="flash-success"><i class="bi bi-check-circle"></i> Profile updated successfully.</div>';
          // Update sidebar display name
          const nameEl = document.querySelector('.admin-sidebar-info h2');
          if (nameEl) nameEl.textContent = fd.get('full_name');
        } else {
          zone.innerHTML = `<div class="flash-error"><i class="bi bi-exclamation-circle"></i> ${d.message || 'Update failed.'}</div>`;
        }
        setTimeout(() => zone.innerHTML = '', 5000);
      })
      .catch(() => zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> Network error.</div>')
      .finally(() => {
        btn.disabled    = false;
        btn.textContent = 'Save Changes';
      });
  }
</script>

<?php require 'includes/footer.php'; ?>