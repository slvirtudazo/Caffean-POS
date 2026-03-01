<?php
/* Purge Coffee Shop Customer Account Page */
/* Single-frame layout with profile header, stats, and tabbed profile or order history */
/* Includes editable personal info, contact, address, and sortable order table */
require_once 'php/db_connection.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Formats an integer ID into a prefixed display string
function fmt_id($prefix, $id, $date_str = null)
{
    $year = $date_str ? date('Y', strtotime($date_str)) : date('Y');
    return $prefix . '-' . $year . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
}

/* Fetch user includes extended profile columns */
$stmt = mysqli_prepare(
    $conn,
    "SELECT user_id, full_name, email, mobile_number, profile_image,
    house_unit, street_name, barangay, city_municipality,
    province, zip_code, created_at
    FROM users WHERE user_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
/* Fetch order stats */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
    COUNT(*)                                          AS total_orders,
    COALESCE(SUM(total_amount), 0)                    AS total_spent,
    COUNT(CASE WHEN status = 'pending'    THEN 1 END) AS pending_orders,
    COUNT(CASE WHEN status = 'completed'  THEN 1 END) AS completed_orders,
    COUNT(CASE WHEN status = 'cancelled'  THEN 1 END) AS cancelled_orders
    FROM orders WHERE user_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
/* Fetch full order history all for JS-side sorting */
$stmt = mysqli_prepare(
    $conn,
    "SELECT o.order_id, o.total_amount, o.status, o.order_date,
    o.payment_method, o.order_type, o.delivery_notes,
    COUNT(oi.id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
$orders_arr = [];
while ($row = mysqli_fetch_assoc($orders_result)) $orders_arr[] = $row;
/* Helpers */
$initials    = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
$avatar_src  = !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '';
$member_date = !empty($user['created_at']) ? date('F Y', strtotime($user['created_at'])) : '—';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Account — Purge Coffee</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/search.css" />
    <link rel="stylesheet" href="css/account-page.css?v=<?php echo time(); ?>" />
</head>

<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Purge Coffee" />
                <span>purge coffee</span>
            </a>
            <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="supplies-page.php">Supplies</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                </ul>
            </div>
            <div class="nav-icons">
                <i class="bi bi-search nav-icon" onclick="showSearchOverlay()"></i>
                <a href="cart.php" class="text-decoration-none">
                    <i class="bi bi-cart nav-icon"></i>
                </a>
                <a href="account.php" class="text-decoration-none">
                    <i class="bi bi-person nav-icon active-icon"></i>
                </a>
            </div>
        </div>
    </nav>
    <div class="acct-page">
        <div class="acct-container">
            <div class="acct-page-hd">
                <div>
                    <h1>My Account</h1>
                    <p>Manage your profile, view order stats, and track your order history.</p>
                </div>
            </div>
            <div class="acct-frame">
                <div class="acct-profile-row">
                    <div class="acct-profile-left">
                        <div class="acct-avatar-wrap" onclick="openAvatarEdit()" title="Change photo">
                            <?php if ($avatar_src): ?>
                                <img src="<?= $avatar_src ?>" alt="Profile" class="acct-avatar-img" id="avatarPreview" />
                            <?php else: ?>
                                <div class="acct-avatar-initial" id="avatarInitial"><?= $initials ?></div>
                            <?php endif; ?>
                            <div class="acct-avatar-overlay"><i class="bi bi-camera"></i></div>
                            <input type="file" id="avatarFileInput" accept="image/*" style="display:none"
                                onchange="previewAvatar(this)" />
                        </div>
                        <div class="acct-profile-info">
                            <h2><?= htmlspecialchars($user['full_name'] ?? '—') ?></h2>
                            <p><?= htmlspecialchars($user['email'] ?? '—') ?></p>
                            <span class="acct-member-badge">
                                <i class="bi bi-calendar"></i>
                                Member since <?= $member_date ?>
                            </span>
                        </div>
                    </div>
                    <div class="acct-profile-actions">
                        <a href="php/logout.php" class="acct-btn-logout">
                            <i class="fas fa-sign-out-alt"></i> Log Out
                        </a>
                    </div>
                </div>
                <div class="acct-stats-row">
                    <div class="acct-stat">
                        <span class="stat-label">Total Orders</span>
                        <span class="stat-value"><?= number_format($stats['total_orders']) ?></span>
                    </div>
                    <div class="acct-stat">
                        <span class="stat-label">Total Spent</span>
                        <span class="stat-value">&#8369;<?= number_format($stats['total_spent'], 2) ?></span>
                    </div>
                    <div class="acct-stat">
                        <span class="stat-label">Completed</span>
                        <span class="stat-value"><?= number_format($stats['completed_orders']) ?></span>
                    </div>
                    <div class="acct-stat">
                        <span class="stat-label">Pending</span>
                        <span class="stat-value"><?= number_format($stats['pending_orders']) ?></span>
                    </div>
                    <div class="acct-stat">
                        <span class="stat-label">Cancelled</span>
                        <span class="stat-value"><?= number_format($stats['cancelled_orders']) ?></span>
                    </div>
                </div>
                <div class="acct-tab-nav">
                    <button class="acct-tab-btn active" onclick="openTab('orders')" id="tab-orders">
                        Order History
                    </button>
                    <button class="acct-tab-btn" onclick="openTab('profile')" id="tab-profile">
                        Profile Settings
                    </button>
                </div>
                <div class="acct-tab-panel" id="panel-orders">
                    <div class="acct-table-hd">
                        <span class="acct-table-title">All Orders</span>
                        <span class="acct-table-count">
                            <?= count($orders_arr) ?> order<?= count($orders_arr) !== 1 ? 's' : '' ?>
                        </span>
                    </div>
                    <?php if (empty($orders_arr)): ?>
                        <div class="acct-empty-state">
                            <i class="bi bi-bag"></i>
                            <p>No orders yet.</p>
                            <a href="menu.php" class="acct-btn-browse">Browse Menu</a>
                        </div>
                    <?php else: ?>
                        <div class="acct-table-wrap">
                            <table class="acct-table" id="orderTable">
                                <thead>
                                    <tr>
                                        <th class="sortable" data-col="0" data-type="num">
                                            Order ID <span class="sort-icon">↕</span>
                                        </th>
                                        <th class="sortable" data-col="1" data-type="date">
                                            Date <span class="sort-icon">↕</span>
                                        </th>
                                        <th class="sortable" data-col="2" data-type="num">
                                            Items <span class="sort-icon">↕</span>
                                        </th>
                                        <th class="sortable" data-col="3" data-type="str">
                                            Type <span class="sort-icon">↕</span>
                                        </th>
                                        <th class="sortable" data-col="4" data-type="str">
                                            Payment <span class="sort-icon">↕</span>
                                        </th>
                                        <th class="sortable" data-col="5" data-type="num">
                                            Amount <span class="sort-icon">↕</span>
                                        </th>
                                        <th class="sortable" data-col="6" data-type="str">
                                            Status <span class="sort-icon">↕</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders_arr as $o): ?>
                                        <tr>
                                            <td class="acct-td-id" data-raw="<?= $o['order_id'] ?>"><?= fmt_id('OR', $o['order_id'], $o['order_date']) ?></td>
                                            <td data-raw="<?= strtotime($o['order_date']) ?>">
                                                <?= date('M d, Y', strtotime($o['order_date'])) ?>
                                            </td>
                                            <td data-raw="<?= $o['item_count'] ?>">
                                                <?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?>
                                            </td>
                                            <td><?= ucfirst(htmlspecialchars($o['order_type'] ?? '—')) ?></td>
                                            <td><?= htmlspecialchars($o['payment_method']) ?></td>
                                            <td data-raw="<?= $o['total_amount'] ?>">
                                                &#8369;<?= number_format($o['total_amount'], 2) ?>
                                            </td>
                                            <td data-raw="<?= htmlspecialchars($o['status']) ?>">
                                                <span class="acct-badge acct-badge-<?= strtolower($o['status']) ?>">
                                                    <?= ucfirst($o['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="acct-tab-panel hidden" id="panel-profile">
                    <div id="profile-alert-zone"></div>
                    <form id="profileForm" onsubmit="saveProfile(event)">
                        <div class="acct-section-hd">Personal Information</div>
                        <div class="acct-form-grid">
                            <div class="acct-field">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" id="f-name"
                                    value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                                    placeholder="Your full name" required />
                            </div>
                            <div class="acct-field">
                                <label>Email Address *</label>
                                <input type="email" name="email" id="f-email"
                                    value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                    placeholder="your@email.com" required />
                            </div>
                            <div class="acct-field">
                                <label>Mobile Number</label>
                                <input type="tel" name="mobile_number" id="f-mobile"
                                    value="<?= htmlspecialchars($user['mobile_number'] ?? '') ?>"
                                    placeholder="09XXXXXXXXX" maxlength="11" inputmode="numeric" />
                            </div>
                        </div>
                        <div class="acct-form-divider"></div>
                        <div class="acct-section-hd">Change Password <span class="acct-section-note">(leave blank to keep current)</span></div>
                        <div class="acct-form-grid">
                            <div class="acct-field">
                                <label>Current Password</label>
                                <div class="acct-pw-wrap">
                                    <input type="password" name="current_password" id="f-pw-current"
                                        placeholder="Enter current password" autocomplete="current-password" />
                                    <button type="button" class="acct-pw-toggle" onclick="togglePw('f-pw-current')">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="acct-field">
                                <label>New Password</label>
                                <div class="acct-pw-wrap">
                                    <input type="password" name="new_password" id="f-pw-new"
                                        placeholder="Min. 8 characters" autocomplete="new-password" />
                                    <button type="button" class="acct-pw-toggle" onclick="togglePw('f-pw-new')">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="acct-field">
                                <label>Confirm New Password</label>
                                <div class="acct-pw-wrap">
                                    <input type="password" name="confirm_password" id="f-pw-confirm"
                                        placeholder="Repeat new password" autocomplete="new-password" />
                                    <button type="button" class="acct-pw-toggle" onclick="togglePw('f-pw-confirm')">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="acct-form-divider"></div>
                        <div class="acct-section-hd">Default Delivery Address</div>
                        <div class="acct-form-grid">
                            <div class="acct-field">
                                <label>House / Unit No.</label>
                                <input type="text" name="house_unit" id="f-house"
                                    value="<?= htmlspecialchars($user['house_unit'] ?? '') ?>"
                                    placeholder="e.g., Blk 6 Lot 17" />
                            </div>
                            <div class="acct-field">
                                <label>Street</label>
                                <input type="text" name="street_name" id="f-street"
                                    value="<?= htmlspecialchars($user['street_name'] ?? '') ?>"
                                    placeholder="e.g., Crestview Avenue" />
                            </div>
                            <div class="acct-field">
                                <label>Barangay</label>
                                <input type="text" name="barangay" id="f-barangay"
                                    value="<?= htmlspecialchars($user['barangay'] ?? '') ?>"
                                    placeholder="e.g., Brgy. Matina Aplaya" />
                            </div>
                            <div class="acct-field">
                                <label>City / Municipality</label>
                                <input type="text" name="city_municipality" id="f-city"
                                    value="<?= htmlspecialchars($user['city_municipality'] ?? '') ?>"
                                    placeholder="e.g., Davao City" />
                            </div>
                            <div class="acct-field">
                                <label>Province</label>
                                <input type="text" name="province" id="f-province"
                                    value="<?= htmlspecialchars($user['province'] ?? '') ?>"
                                    placeholder="e.g., Davao del Sur" />
                            </div>
                            <div class="acct-field">
                                <label>ZIP Code</label>
                                <input type="text" name="zip_code" id="f-zip"
                                    value="<?= htmlspecialchars($user['zip_code'] ?? '') ?>"
                                    placeholder="e.g., 8000" maxlength="4" inputmode="numeric" />
                            </div>
                        </div>
                        <div class="acct-form-actions">
                            <button type="submit" class="acct-btn-save" id="saveBtn">
                                Save Changes
                            </button>
                            <button type="button" class="acct-btn-cancel" onclick="openTab('orders')">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
    <script>
        /* Tab switching */
        function openTab(name) {
            document.querySelectorAll('.acct-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.acct-tab-panel').forEach(p => p.classList.add('hidden'));
            document.getElementById('tab-' + name).classList.add('active');
            document.getElementById('panel-' + name).classList.remove('hidden');
        }
        /* Avatar edit */
        function openAvatarEdit() {
            document.getElementById('avatarFileInput').click();
        }

        function previewAvatar(input) {
            if (!input.files || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                const wrap = document.querySelector('.acct-avatar-wrap');
                /* Replace initial div with img if needed */
                let img = document.getElementById('avatarPreview');
                const initial = document.getElementById('avatarInitial');
                if (!img) {
                    img = document.createElement('img');
                    img.id = 'avatarPreview';
                    img.className = 'acct-avatar-img';
                    img.alt = 'Profile';
                    if (initial) initial.replaceWith(img);
                    else wrap.insertBefore(img, wrap.querySelector('.acct-avatar-overlay'));
                }
                img.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
        /* Password visibility toggle */
        function togglePw(id) {
            const inp = document.getElementById(id);
            const icon = inp.nextElementSibling.querySelector('i');
            if (inp.type === 'password') {
                inp.type = 'text';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            } else {
                inp.type = 'password';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            }
        }
        /* Save profile via fetch */
        function saveProfile(e) {
            e.preventDefault();
            const btn = document.getElementById('saveBtn');
            const zone = document.getElementById('profile-alert-zone');
            /* Validate passwords if entered */
            const newPw = document.getElementById('f-pw-new').value;
            const confPw = document.getElementById('f-pw-confirm').value;
            if (newPw && newPw !== confPw) {
                zone.innerHTML = '<div class="acct-alert acct-alert-err"><i class="bi bi-exclamation-circle"></i> Passwords do not match.</div>';
                return;
            }
            if (newPw && newPw.length < 8) {
                zone.innerHTML = '<div class="acct-alert acct-alert-err"><i class="bi bi-exclamation-circle"></i> New password must be at least 8 characters.</div>';
                return;
            }
            btn.disabled = true;
            btn.textContent = 'Saving…';
            const fd = new FormData(document.getElementById('profileForm'));
            /* Attach avatar file if selected */
            const avatarFile = document.getElementById('avatarFileInput').files[0];
            if (avatarFile) fd.append('avatar', avatarFile);
            fetch('php/update_profile.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(d => {
                    zone.innerHTML = d.success ?
                        '<div class="acct-alert acct-alert-ok"><i class="bi bi-check-circle"></i> Profile updated successfully.</div>' :
                        '<div class="acct-alert acct-alert-err"><i class="bi bi-exclamation-circle"></i> ' + (d.message || 'Update failed.') + '</div>';
                    if (d.success) {
                        /* Refresh displayed name/email in header */
                        const fd2 = new FormData(document.getElementById('profileForm'));
                        document.querySelector('.acct-profile-info h2').textContent = fd2.get('full_name');
                        document.querySelector('.acct-profile-info p').textContent = fd2.get('email');
                    }
                    zone.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest'
                    });
                    setTimeout(() => zone.innerHTML = '', 5000);
                })
                .catch(() => {
                    zone.innerHTML = '<div class="acct-alert acct-alert-err"><i class="bi bi-exclamation-circle"></i> Network error. Please try again.</div>';
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Save Changes';
                });
        }
        /* Sortable table */
        (function initSort() {
            const table = document.getElementById('orderTable');
            if (!table) return;
            let sortCol = null;
            let sortAsc = true;
            table.querySelectorAll('th.sortable').forEach(th => {
                th.addEventListener('click', () => {
                    const col = parseInt(th.dataset.col);
                    const type = th.dataset.type;
                    if (sortCol === col) {
                        sortAsc = !sortAsc;
                    } else {
                        sortCol = col;
                        sortAsc = true;
                    }
                    /* Update icons */
                    table.querySelectorAll('th.sortable').forEach(h => {
                        h.classList.remove('sort-asc', 'sort-desc');
                        h.querySelector('.sort-icon').textContent = '↕';
                    });
                    th.classList.add(sortAsc ? 'sort-asc' : 'sort-desc');
                    th.querySelector('.sort-icon').textContent = sortAsc ? '↑' : '↓';
                    /* Sort rows */
                    const tbody = table.querySelector('tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    rows.sort((a, b) => {
                        const aCell = a.cells[col];
                        const bCell = b.cells[col];
                        const aRaw = aCell.dataset.raw ?? aCell.textContent.trim();
                        const bRaw = bCell.dataset.raw ?? bCell.textContent.trim();
                        let cmp = 0;
                        if (type === 'num' || type === 'date') {
                            cmp = parseFloat(aRaw) - parseFloat(bRaw);
                        } else {
                            cmp = aRaw.localeCompare(bRaw);
                        }
                        return sortAsc ? cmp : -cmp;
                    });
                    rows.forEach(r => tbody.appendChild(r));
                });
            });
        })();
    </script>
</body>

</html>