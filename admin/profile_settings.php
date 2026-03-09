<?php
/**
 * Caffean — Admin Profile Settings (admin/profile_settings.php)
 * Split into two independent cards: Account Information and Change Password.
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

/* Fetch current admin record */
$stmt = mysqli_prepare($conn,
    "SELECT full_name, email, mobile_number, profile_image FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

/* Build avatar src for display */
if (!defined('BASE_URL')) define('BASE_URL', '..');
$avatar_src = !empty($admin['profile_image'])
    ? BASE_URL . '/' . htmlspecialchars($admin['profile_image'])
    : '';

require 'includes/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1>Profile Settings</h1>
    <p>Update your account and password information</p>
  </div>
</div>

<div class="ins-outer-frame">
  <div class="ps-settings-row">

    <div>
      <div id="ps-info-alert" class="ps-alert-zone"></div>
      <div class="ps-card">
        <p class="ps-section-hd">Account Information</p>

        <div class="ps-avatar-row">
          <div class="ps-avatar-wrap" id="psAvatarWrap"
               onclick="document.getElementById('psAvatarInput').click()" title="Edit profile photo">
            <?php if ($avatar_src): ?>
              <img src="<?= $avatar_src ?>" alt="Profile" class="ps-avatar-img" id="psAvatarPreview" />
            <?php else: ?>
              <div class="ps-avatar-initial" id="psAvatarInitial">
                <?= strtoupper(substr($admin['full_name'] ?? 'A', 0, 1)) ?>
              </div>
            <?php endif; ?>
            <div class="ps-avatar-pencil"><i class="bi bi-pencil-fill"></i></div>
          </div>
          <div class="ps-avatar-meta">
            <span class="ps-avatar-hint">Edit Profile Photo</span>
            <span class="ps-avatar-hint-sub">Accepted formats: JPG, PNG, WEBP (Max 5MB)</span>
          </div>
          <input type="file" id="psAvatarInput" accept="image/jpeg,image/png,image/webp"
                 style="display:none" onchange="previewPsAvatar(this)" />
        </div>

        <form id="psInfoForm" onsubmit="saveInfoSection(event)">
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
            <div class="ps-field full-width">
              <label>MOBILE NUMBER</label>
              <input type="tel" name="mobile_number" id="ps-mobile"
                     value="<?= htmlspecialchars($admin['mobile_number'] ?? '') ?>"
                     placeholder="+63 9XX XXX XXXX"
                     maxlength="16"
                     pattern="(\+63|0)[0-9]{10}"
                     title="Format: +63 9XX XXX XXXX or 09XXXXXXXXX" />
            </div>
          </div>
          <div class="ps-form-actions">
            <button type="button" class="ps-btn-discard" onclick="discardInfo()">Discard</button>
            <button type="submit" class="ps-btn-save" id="psInfoSaveBtn">Save Changes</button>
          </div>
        </form>
      </div>
    </div>

    <div>
      <div id="ps-pw-alert" class="ps-alert-zone"></div>
      <div class="ps-card">
        <p class="ps-section-hd">Change Password</p>

        <form id="psPwForm" onsubmit="savePasswordSection(event)">
          <div class="ps-form-grid" style="margin-top: 8px;">
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
            <div class="ps-field full-width">
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
            <button type="button" class="ps-btn-discard" onclick="discardPassword()">Discard</button>
            <button type="submit" class="ps-btn-save" id="psPwSaveBtn">Save Changes</button>
          </div>
        </form>
      </div>
    </div>

  </div></div><script>
  /* Stored originals for discard */
  var _origInfo = {
    full_name:     <?php echo json_encode($admin['full_name']     ?? ''); ?>,
    email:         <?php echo json_encode($admin['email']         ?? ''); ?>,
    mobile_number: <?php echo json_encode($admin['mobile_number'] ?? ''); ?>
  };
  var _avatarChanged = false;

  /* Avatar preview in card */
  function previewPsAvatar(input) {
    if (!input.files || !input.files[0]) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      var wrap    = document.getElementById('psAvatarWrap');
      var preview = document.getElementById('psAvatarPreview');
      var initial = document.getElementById('psAvatarInitial');
      if (!preview) {
        preview    = document.createElement('img');
        preview.id = 'psAvatarPreview';
        preview.className = 'ps-avatar-img';
        if (initial) initial.replaceWith(preview);
        else wrap.insertBefore(preview, wrap.querySelector('.ps-avatar-pencil'));
      }
      preview.src    = e.target.result;
      _avatarChanged = true;
      /* Also sync sidebar avatar */
      var sidebarImg = document.getElementById('adminAvatarPreview');
      if (sidebarImg) sidebarImg.src = e.target.result;
    };
    reader.readAsDataURL(input.files[0]);
  }

  /* Toggle password visibility */
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

  /* Discard account info changes */
  function discardInfo() {
    document.getElementById('ps-full-name').value = _origInfo.full_name;
    document.getElementById('ps-email').value     = _origInfo.email;
    document.getElementById('ps-mobile').value    = _origInfo.mobile_number;
    if (_avatarChanged) {
      document.getElementById('psAvatarInput').value = '';
      var preview = document.getElementById('psAvatarPreview');
      var initial = document.getElementById('psAvatarInitial');
      if (initial && preview) {
        preview.style.display = 'none';
        initial.style.display = '';
      }
      _avatarChanged = false;
    }
    document.getElementById('ps-info-alert').innerHTML = '';
  }

  /* Discard password changes */
  function discardPassword() {
    document.getElementById('ps-pw-cur').value     = '';
    document.getElementById('ps-pw-new').value     = '';
    document.getElementById('ps-pw-confirm').value = '';
    document.querySelectorAll('#psPwForm .ps-pw-toggle i').forEach(function (ic) {
      ic.classList.remove('fa-eye');
      ic.classList.add('fa-eye-slash');
    });
    document.querySelectorAll('#psPwForm input').forEach(function (inp) {
      if (inp.type === 'text') inp.type = 'password';
    });
    document.getElementById('ps-pw-alert').innerHTML = '';
  }

  /* Save account information section */
  function saveInfoSection(e) {
    e.preventDefault();
    var zone = document.getElementById('ps-info-alert');
    var btn  = document.getElementById('psInfoSaveBtn');
    btn.disabled    = true;
    btn.textContent = 'Saving\u2026';

    var fd = new FormData(document.getElementById('psInfoForm'));
    fd.append('action', 'info');
    var avatarInput = document.getElementById('psAvatarInput');
    if (avatarInput && avatarInput.files[0]) fd.append('avatar', avatarInput.files[0]);

    fetch('<?= BASE_URL ?>/php/update_profile.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.success) {
          zone.innerHTML = '<div class="flash-success"><i class="bi bi-check-circle"></i> Account information updated.</div>';
          _origInfo.full_name     = document.getElementById('ps-full-name').value;
          _origInfo.email         = document.getElementById('ps-email').value;
          _origInfo.mobile_number = document.getElementById('ps-mobile').value;
          _avatarChanged = false;
          var nameEl = document.querySelector('.admin-sidebar-info h2');
          if (nameEl) nameEl.textContent = _origInfo.full_name;
          if (d.profile_image) {
            var sidebarImg = document.getElementById('adminAvatarPreview');
            if (sidebarImg) sidebarImg.src = '<?= BASE_URL ?>/' + d.profile_image;
          }
        } else {
          zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> ' + (d.message || 'Update failed.') + '</div>';
        }
        setTimeout(function () { zone.innerHTML = ''; }, 5000);
      })
      .catch(function () {
        zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> Network error.</div>';
      })
      .finally(function () {
        btn.disabled    = false;
        btn.textContent = 'Save Changes';
      });
  }

  /* Save password section */
  function savePasswordSection(e) {
    e.preventDefault();
    var zone   = document.getElementById('ps-pw-alert');
    var btn    = document.getElementById('psPwSaveBtn');
    var curPw  = document.getElementById('ps-pw-cur').value;
    var newPw  = document.getElementById('ps-pw-new').value;
    var confPw = document.getElementById('ps-pw-confirm').value;

    if (!curPw) {
      zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> Enter your current password.</div>';
      return;
    }
    if (!newPw) {
      zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> Enter a new password.</div>';
      return;
    }
    if (newPw.length < 8) {
      zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> Password must be at least 8 characters.</div>';
      return;
    }
    if (newPw !== confPw) {
      zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> Passwords do not match.</div>';
      return;
    }

    btn.disabled    = true;
    btn.textContent = 'Saving\u2026';

    var fd = new FormData(document.getElementById('psPwForm'));
    fd.append('action', 'password');

    fetch('<?= BASE_URL ?>/php/update_profile.php', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.success) {
          zone.innerHTML = '<div class="flash-success"><i class="bi bi-check-circle"></i> Password updated successfully.</div>';
          discardPassword();
        } else {
          zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> ' + (d.message || 'Update failed.') + '</div>';
        }
        setTimeout(function () { zone.innerHTML = ''; }, 5000);
      })
      .catch(function () {
        zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> Network error.</div>';
      })
      .finally(function () {
        btn.disabled    = false;
        btn.textContent = 'Save Changes';
      });
  }
</script>

<?php require 'includes/footer.php'; ?>