<?php
/* Purge Coffee Shop Customer Account Page */
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
$member_year = !empty($user['created_at']) ? date('Y', strtotime($user['created_at'])) : '—';
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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
                <i class="fas fa-search nav-icon" onclick="showSearchOverlay()"></i>
                <a href="cart.php" class="text-decoration-none">
                    <i class="fas fa-shopping-cart nav-icon"></i>
                </a>
                <a href="account.php" class="text-decoration-none">
                    <i class="fas fa-user nav-icon active-icon"></i>
                </a>
            </div>
        </div>
    </nav>

    <div class="acct-page">
        <div class="acct-dashboard">

            <aside class="acct-sidebar">
                <nav class="acct-nav">
                    <a href="#" class="acct-nav-item active" onclick="openTab('orders', this)">
                        <i class="bi bi-clock-history"></i> Order History
                    </a>
                    <a href="#" class="acct-nav-item" onclick="alert('Favorites coming soon!')">
                        <i class="bi bi-heart"></i> Favorites
                    </a>
                    <a href="#" class="acct-nav-item" onclick="alert('Insights coming soon!')">
                        <i class="bi bi-graph-up-arrow"></i> Insights
                    </a>
                    <a href="#" class="acct-nav-item" onclick="openTab('profile', this)">
                        <i class="bi bi-gear"></i> Profile Settings
                    </a>
                </nav>

                <a href="php/logout.php" class="acct-logout-btn">
                    <i class="bi bi-box-arrow-left"></i> Log Out
                </a>
            </aside>

            <main class="acct-main">

                <div class="acct-card acct-profile-summary">
                    <div class="profile-left">
                        <div class="acct-avatar-wrap" onclick="openAvatarEdit()" title="Change photo">
                            <?php if ($avatar_src): ?>
                                <img src="<?= $avatar_src ?>" alt="Profile" class="acct-avatar-img" id="avatarPreview" />
                            <?php else: ?>
                                <div class="acct-avatar-initial" id="avatarInitial"><?= $initials ?></div>
                            <?php endif; ?>
                            <div class="avatar-edit-icon"><i class="bi bi-pencil-fill"></i></div>
                            <input type="file" id="avatarFileInput" accept="image/*" style="display:none" onchange="previewAvatar(this)" />
                        </div>
                        <div class="profile-details">
                            <h2><?= htmlspecialchars($user['full_name'] ?? '—') ?></h2>
                            <p class="profile-email"><?= htmlspecialchars($user['email'] ?? '—') ?></p>
                            <span class="member-badge">
                                <i class="bi bi-calendar3"></i> MEMBER SINCE <?= $member_year ?>
                            </span>
                        </div>
                    </div>

                    <div class="profile-stats">
                        <div class="stat-col">
                            <span class="stat-lbl">TOTAL ORDERS</span>
                            <span class="stat-val"><?= number_format($stats['total_orders']) ?></span>
                        </div>
                        <div class="stat-col">
                            <span class="stat-lbl">TOTAL SPENT</span>
                            <span class="stat-val">&#8369;<?= number_format($stats['total_spent'], 0) ?></span>
                        </div>
                        <div class="stat-col">
                            <span class="stat-lbl">COMPLETED</span>
                            <span class="stat-val"><?= number_format($stats['completed_orders']) ?></span>
                        </div>
                        <div class="stat-col">
                            <span class="stat-lbl">PENDING</span>
                            <span class="stat-val"><?= number_format($stats['pending_orders']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="acct-card acct-tab-panel" id="panel-orders">
                    <div class="acct-card-header">
                        <div>
                            <h3>Recent Orders</h3>
                            <p>Showing <?= count($orders_arr) ?> total orders from your history</p>
                        </div>
                        <a href="menu.php" class="acct-view-all">View All <i class="bi bi-arrow-right"></i></a>
                    </div>

                    <?php if (empty($orders_arr)): ?>
                        <div class="acct-empty-state">
                            <i class="bi bi-bag"></i>
                            <p>No orders yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="acct-orders-table">
                                <thead>
                                    <tr>
                                        <th>ORDER ID</th>
                                        <th>DATE</th>
                                        <th>ITEMS</th>
                                        <th>TYPE</th>
                                        <th>PAYMENT</th>
                                        <th>AMOUNT</th>
                                        <th>STATUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders_arr as $o):
                                        $isDelivery = strtolower($o['order_type'] ?? '') === 'delivery';
                                        $typeIcon = $isDelivery ? 'bi-truck' : 'bi-shop';
                                    ?>
                                        <tr>
                                            <td class="td-id"><?= fmt_id('OR', $o['order_id'], $o['order_date']) ?></td>
                                            <td class="td-date"><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
                                            <td class="td-items"><?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                                            <td class="td-type"><i class="bi <?= $typeIcon ?>"></i> <?= ucfirst(htmlspecialchars($o['order_type'] ?? 'Pickup')) ?></td>
                                            <td class="td-payment"><?= htmlspecialchars($o['payment_method']) ?></td>
                                            <td class="td-amount">&#8369;<?= number_format($o['total_amount'], 2) ?></td>
                                            <td class="td-status">
                                                <span class="status-badge status-<?= strtolower($o['status']) ?>">
                                                    <?= strtoupper($o['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="acct-pagination">
                            <span class="page-info">Page 1 of 1</span>
                            <div class="page-controls">
                                <button class="btn-page"><i class="bi bi-chevron-left"></i></button>
                                <button class="btn-page"><i class="bi bi-chevron-right"></i></button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="acct-card acct-tab-panel hidden" id="panel-profile">
                    <div class="acct-card-header">
                        <div>
                            <h3>Profile Settings</h3>
                            <p>Manage your account details and default addresses</p>
                        </div>
                    </div>

                    <div id="profile-alert-zone" class="p-4 pb-0"></div>

                    <form id="profileForm" onsubmit="saveProfile(event)" class="p-4">
                        <div class="acct-section-hd">Personal Information</div>
                        <div class="acct-form-grid">
                            <div class="acct-field">
                                <label>Full Name *</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required />
                            </div>
                            <div class="acct-field">
                                <label>Email Address *</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required />
                            </div>
                            <div class="acct-field">
                                <label>Mobile Number</label>
                                <input type="tel" name="mobile_number" value="<?= htmlspecialchars($user['mobile_number'] ?? '') ?>" maxlength="11" />
                            </div>
                        </div>

                        <div class="acct-section-hd mt-4">Change Password</div>
                        <div class="acct-form-grid">
                            <div class="acct-field">
                                <label>New Password</label>
                                <input type="password" name="new_password" id="f-pw-new" placeholder="Min. 8 characters" autocomplete="new-password" />
                            </div>
                            <div class="acct-field">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" id="f-pw-confirm" placeholder="Repeat new password" autocomplete="new-password" />
                            </div>
                        </div>

                        <div class="acct-section-hd mt-4">Default Delivery Address</div>
                        <div class="acct-form-grid">
                            <div class="acct-field">
                                <label>House / Unit No.</label>
                                <input type="text" name="house_unit" value="<?= htmlspecialchars($user['house_unit'] ?? '') ?>" />
                            </div>
                            <div class="acct-field">
                                <label>Street</label>
                                <input type="text" name="street_name" value="<?= htmlspecialchars($user['street_name'] ?? '') ?>" />
                            </div>
                            <div class="acct-field">
                                <label>Barangay</label>
                                <input type="text" name="barangay" value="<?= htmlspecialchars($user['barangay'] ?? '') ?>" />
                            </div>
                            <div class="acct-field">
                                <label>City / Municipality</label>
                                <input type="text" name="city_municipality" value="<?= htmlspecialchars($user['city_municipality'] ?? '') ?>" />
                            </div>
                        </div>

                        <div class="acct-form-actions mt-4">
                            <button type="submit" class="acct-btn-save" id="saveBtn">Save Changes</button>
                        </div>
                    </form>
                </div>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
    <script>
        function openTab(name, element) {
            // Manage Active States
            document.querySelectorAll('.acct-nav-item').forEach(el => el.classList.remove('active'));
            if (element) element.classList.add('active');

            // Manage Panels
            document.querySelectorAll('.acct-tab-panel').forEach(p => p.classList.add('hidden'));
            document.getElementById('panel-' + name).classList.remove('hidden');
        }

        function openAvatarEdit() {
            document.getElementById('avatarFileInput').click();
        }

        function previewAvatar(input) {
            if (!input.files || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                const wrap = document.querySelector('.acct-avatar-wrap');
                let img = document.getElementById('avatarPreview');
                const initial = document.getElementById('avatarInitial');
                if (!img) {
                    img = document.createElement('img');
                    img.id = 'avatarPreview';
                    img.className = 'acct-avatar-img';
                    if (initial) initial.replaceWith(img);
                    else wrap.insertBefore(img, wrap.firstChild);
                }
                img.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }

        function saveProfile(e) {
            e.preventDefault();
            const btn = document.getElementById('saveBtn');
            const zone = document.getElementById('profile-alert-zone');

            const newPw = document.getElementById('f-pw-new').value;
            const confPw = document.getElementById('f-pw-confirm').value;

            if (newPw && newPw !== confPw) {
                zone.innerHTML = '<div class="alert alert-danger">Passwords do not match.</div>';
                return;
            }
            if (newPw && newPw.length < 8) {
                zone.innerHTML = '<div class="alert alert-danger">New password must be at least 8 characters.</div>';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Saving...';

            const fd = new FormData(document.getElementById('profileForm'));
            const avatarFile = document.getElementById('avatarFileInput').files[0];
            if (avatarFile) fd.append('avatar', avatarFile);

            fetch('php/update_profile.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(d => {
                    zone.innerHTML = d.success ?
                        '<div class="alert alert-success">Profile updated successfully.</div>' :
                        '<div class="alert alert-danger">' + (d.message || 'Update failed.') + '</div>';
                    if (d.success) {
                        const fd2 = new FormData(document.getElementById('profileForm'));
                        document.querySelector('.profile-details h2').textContent = fd2.get('full_name');
                        document.querySelector('.profile-email').textContent = fd2.get('email');
                    }
                    setTimeout(() => zone.innerHTML = '', 5000);
                })
                .catch(() => zone.innerHTML = '<div class="alert alert-danger">Network error.</div>')
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Save Changes';
                });
        }
    </script>
</body>

</html>