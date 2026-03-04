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

/* Formats an integer ID into a prefixed display string */
function fmt_id($prefix, $id, $date_str = null) {
    $year = $date_str ? date('Y', strtotime($date_str)) : date('Y');
    return $prefix . '-' . $year . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
}

/* Fetch user with all profile columns */
$stmt = mysqli_prepare($conn,
    "SELECT user_id, full_name, email, mobile_number, profile_image,
     house_unit, street_name, barangay, city_municipality,
     province, zip_code, created_at
     FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

/* Fetch order stats */
$stmt = mysqli_prepare($conn,
    "SELECT
     COUNT(*)                                          AS total_orders,
     COALESCE(SUM(total_amount), 0)                    AS total_spent,
     COUNT(CASE WHEN status = 'pending'   THEN 1 END)  AS pending_orders,
     COUNT(CASE WHEN status = 'completed' THEN 1 END)  AS completed_orders,
     COUNT(CASE WHEN status = 'cancelled' THEN 1 END)  AS cancelled_orders
     FROM orders WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

/* Fetch full order history — DESC by date (most recent first) for default load */
$stmt = mysqli_prepare($conn,
    "SELECT o.order_id, o.total_amount, o.status, o.order_date,
     o.payment_method, o.order_type,
     COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON o.order_id = oi.order_id
     WHERE o.user_id = ?
     GROUP BY o.order_id
     ORDER BY o.order_date DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
$orders_arr = [];
while ($row = mysqli_fetch_assoc($orders_result)) $orders_arr[] = $row;

/* Helpers */
$initials    = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
$avatar_src  = !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '';
$member_date = !empty($user['created_at'])
    ? strtoupper(date('F Y', strtotime($user['created_at'])))
    : '—';
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

    <!-- ── NAVBAR ─────────────────────────────────────────────── -->
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

    <!-- ── FAVORITES DELETE MODAL ─────────────────────────────── -->
    <div class="acct-modal-overlay" id="favDeleteModal" style="display:none;">
        <div class="acct-modal">
            <div class="acct-modal-header">
                <span><i class="bi bi-trash3"></i> Remove Favorite</span>
                <button class="acct-modal-close" onclick="closeFavDeleteModal()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="acct-modal-body">
                <p>Are you sure you want to remove <strong id="favDeleteName"></strong> from your favorites? This action cannot be undone.</p>
            </div>
            <div class="acct-modal-footer">
                <button class="acct-modal-btn-cancel" onclick="closeFavDeleteModal()">Cancel</button>
                <button class="acct-modal-btn-delete" id="favDeleteConfirmBtn">
                    <i class="bi bi-trash3"></i> Remove
                </button>
            </div>
        </div>
    </div>

    <!-- ── TOAST ───────────────────────────────────────────────── -->
    <div id="fav-toast" class="fav-toast"></div>

    <!-- ── PAGE LAYOUT ────────────────────────────────────────── -->
    <div class="acct-page">
        <div class="acct-dashboard">

            <aside class="acct-sidebar">
                <nav class="acct-nav">
                    <a href="#" class="acct-nav-item active" onclick="openTab('orders', this)">
                        <i class="far fa-clock acct-ic-out"></i>
                        <i class="fas fa-clock acct-ic-fill"></i>
                        <span class="acct-nav-text">Order History</span>
                    </a>
                    <a href="#" class="acct-nav-item" onclick="openTab('favorites', this)">
                        <i class="far fa-heart acct-ic-out"></i>
                        <i class="fas fa-heart acct-ic-fill"></i>
                        <span class="acct-nav-text">Favorites</span>
                    </a>
                    <a href="#" class="acct-nav-item" onclick="alert('Insights coming soon!')">
                        <i class="fas fa-chart-line acct-ic-out"></i>
                        <i class="fas fa-chart-line acct-ic-fill"></i>
                        <span class="acct-nav-text">Insights</span>
                    </a>
                    <a href="#" class="acct-nav-item" onclick="openTab('profile', this)">
                        <i class="fas fa-gear acct-ic-out"></i>
                        <i class="fas fa-gear acct-ic-fill"></i>
                        <span class="acct-nav-text">Profile Settings</span>
                    </a>
                </nav>
                <div class="acct-sidebar-logout">
                    <a href="php/logout.php" class="acct-logout-link">
                        <i class="fas fa-right-from-bracket acct-ic-out"></i>
                        <span class="acct-nav-text">Log Out</span>
                    </a>
                </div>
            </aside>

            <main class="acct-main">
                <div class="acct-main-card">

                    <!-- ── PROFILE SUMMARY ──────────────────────────────── -->
                    <div class="acct-profile-summary">
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
                                    <i class="bi bi-calendar3"></i> MEMBER SINCE <?= $member_date ?>
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

                    <!-- ── ORDERS TAB ───────────────────────────────────── -->
                    <div class="acct-tab-panel" id="panel-orders">
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
                                <!-- ordersTable: PHP outputs rows already sorted by order_date DESC -->
                                <table class="acct-orders-table" id="ordersTable">
                                    <thead>
                                        <tr>
                                            <!-- CSS ::after adds ⇅/↑/↓ indicators via sort-asc/sort-desc classes -->
                                            <th data-sort="text">ORDER ID</th>
                                            <th data-sort="date">DATE</th>
                                            <th data-sort="number">ITEMS</th>
                                            <th data-sort="text">TYPE</th>
                                            <th data-sort="text">PAYMENT</th>
                                            <th data-sort="number">AMOUNT</th>
                                            <th data-sort="text">STATUS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders_arr as $o):
                                            $isDelivery = strtolower($o['order_type'] ?? '') === 'delivery';
                                            $typeIcon   = $isDelivery ? 'bi-truck' : 'bi-shop';
                                            $orderId    = fmt_id('OR', $o['order_id'], $o['order_date']);
                                        ?>
                                            <tr>
                                                <!-- td-id: bold 700 via CSS -->
                                                <td class="td-id" data-value="<?= htmlspecialchars($orderId) ?>"><?= $orderId ?></td>
                                                <td data-value="<?= $o['order_date'] ?>"><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
                                                <td data-value="<?= (int)$o['item_count'] ?>"><?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                                                <td data-value="<?= htmlspecialchars($o['order_type'] ?? 'Pickup') ?>"><i class="bi <?= $typeIcon ?>"></i> <?= ucfirst(htmlspecialchars($o['order_type'] ?? 'Pickup')) ?></td>
                                                <td data-value="<?= htmlspecialchars($o['payment_method']) ?>"><?= htmlspecialchars($o['payment_method']) ?></td>
                                                <td class="td-amount" data-value="<?= $o['total_amount'] ?>">&#8369;<?= number_format($o['total_amount'], 2) ?></td>
                                                <td data-value="<?= strtolower($o['status']) ?>">
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

                    <!-- ── FAVORITES TAB ────────────────────────────────── -->
                    <div class="acct-tab-panel hidden" id="panel-favorites">
                        <div class="acct-card-header">
                            <div>
                                <h3>Favorites</h3>
                                <p id="fav-subtitle">Loading your wishlist…</p>
                            </div>
                            <a href="menu.php" class="acct-view-all">View All <i class="bi bi-arrow-right"></i></a>
                        </div>

                        <!-- Table renders here via JS -->
                        <div id="fav-body">
                            <div class="acct-empty-state">
                                <i class="bi bi-heart"></i>
                                <p>Loading favorites…</p>
                            </div>
                        </div>

                        <!-- Pagination renders here via JS -->
                        <div id="fav-pagination" class="acct-pagination" style="display:none;">
                            <span class="page-info" id="fav-page-info"></span>
                            <div class="fav-page-controls" id="fav-page-controls"></div>
                        </div>
                    </div>

                    <!-- ── PROFILE SETTINGS TAB ──────────────────────────── -->
                    <div class="acct-tab-panel hidden" id="panel-profile">
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

                </div><!-- /acct-main-card -->
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
    <script>
        /* ── TAB NAVIGATION ────────────────────────────────────── */
        function openTab(name, element) {
            document.querySelectorAll('.acct-nav-item').forEach(el => el.classList.remove('active'));
            if (element) element.classList.add('active');
            document.querySelectorAll('.acct-tab-panel').forEach(p => p.classList.add('hidden'));
            document.getElementById('panel-' + name).classList.remove('hidden');
            if (name === 'favorites') loadFavorites(favPage);
        }

        /* ── SORTABLE TABLE — mirrors admin initSortableTable ───── */
        /* Uses data-value on each td for accurate sort comparison   */
        function initSortableTable(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return;
            let currentCol = -1;
            let currentDir = 'asc';

            table.querySelectorAll('thead th[data-sort]').forEach(th => {
                th.addEventListener('click', () => {
                    const col  = th.cellIndex;
                    const type = th.dataset.sort;
                    currentDir = (currentCol === col && currentDir === 'asc') ? 'desc' : 'asc';
                    currentCol = col;

                    /* CSS ::after handles ⇅ / ↑ / ↓ via these classes */
                    table.querySelectorAll('thead th[data-sort]').forEach(h => {
                        h.classList.remove('sort-asc', 'sort-desc');
                    });
                    th.classList.add(currentDir === 'asc' ? 'sort-asc' : 'sort-desc');

                    const tbody = table.querySelector('tbody');
                    const rows  = Array.from(tbody.querySelectorAll('tr'));

                    rows.sort((a, b) => {
                        const av = a.cells[col]?.dataset.value ?? '';
                        const bv = b.cells[col]?.dataset.value ?? '';
                        let cmp = 0;
                        if (type === 'number') {
                            cmp = parseFloat(av) - parseFloat(bv);
                        } else if (type === 'date') {
                            cmp = new Date(av) - new Date(bv);
                        } else {
                            cmp = av.localeCompare(bv, undefined, { sensitivity: 'base' });
                        }
                        return currentDir === 'asc' ? cmp : -cmp;
                    });

                    rows.forEach(r => tbody.appendChild(r));
                });
            });
        }

        /* Init orders table — PHP already outputs rows DESC by date */
        initSortableTable('ordersTable');

        /* ── FAVORITES ─────────────────────────────────────────── */
        let favPage = 1;
        let favItems = [];      /* current page items for client sort */
        let favSortCol = -1;
        let favSortDir = 'asc';

        /* Loads a page; resets sort state — favorites.php returns DESC by created_at */
        function loadFavorites(page) {
            favPage    = page;
            favSortCol = -1;
            favSortDir = 'asc';
            fetch(`favorites.php?action=get&page=${page}`)
                .then(r => r.json())
                .then(d => {
                    if (!d.success) return;
                    favItems = d.items;
                    renderFavTable(favItems);
                    renderFavPagination(d);
                    document.getElementById('fav-subtitle').textContent =
                        `You have ${d.total} item${d.total !== 1 ? 's' : ''} in your wishlist`;
                });
        }

        /* Client-side column sort on current page — mirrors initSortableTable */
        function sortFavBy(colIdx, type) {
            favSortDir = (favSortCol === colIdx && favSortDir === 'asc') ? 'desc' : 'asc';
            favSortCol = colIdx;

            const sorted = [...favItems].sort((a, b) => {
                let av, bv;
                switch (colIdx) {
                    case 1: av = a.name;              bv = b.name;              break;
                    case 2: av = a.category ?? '';     bv = b.category ?? '';    break;
                    case 3: av = parseFloat(a.price);  bv = parseFloat(b.price); break;
                    default: return 0;
                }
                const cmp = type === 'number'
                    ? av - bv
                    : String(av).localeCompare(String(bv), undefined, { sensitivity: 'base' });
                return favSortDir === 'asc' ? cmp : -cmp;
            });

            /* CSS ::after handles indicators via sort-asc / sort-desc classes */
            document.querySelectorAll('#fav-body thead th[data-sort]').forEach((th, i) => {
                th.classList.remove('sort-asc', 'sort-desc');
                if (i === colIdx) {
                    th.classList.add(favSortDir === 'asc' ? 'sort-asc' : 'sort-desc');
                }
            });

            const tbody = document.querySelector('#fav-body tbody');
            if (tbody) tbody.innerHTML = buildFavRows(sorted);
        }

        /* Builds HTML rows for the favorites table */
        function buildFavRows(items) {
            return items.map(item => {
                const name = item.name.replace(/'/g, "\\'");
                return `
                <tr>
                    <td><img src="${item.image_path || 'images/placeholder.png'}"
                             alt="${item.name}" class="fav-product-img" /></td>
                    <td class="td-fav-name">${item.name}</td>
                    <td>${item.category ?? '—'}</td>
                    <td class="td-fav-price">&#8369;${parseFloat(item.price).toFixed(2)}</td>
                    <td>
                        <div class="fav-td-action">
                            <button class="fav-btn-cart"
                                onclick="favAddToCart(${item.product_id}, '${name}')"
                                title="Add to cart">
                                <i class="bi bi-cart-plus"></i>
                            </button>
                            <button class="fav-btn-remove"
                                onclick="openFavDeleteModal(${item.product_id}, '${name}')"
                                title="Remove">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        }

        /* Renders the full favorites table with sortable headers */
        function renderFavTable(items) {
            const body = document.getElementById('fav-body');
            if (!items.length) {
                body.innerHTML = `
                    <div class="fav-empty-state">
                        <i class="bi bi-heart"></i>
                        <h2>No favorites yet</h2>
                        <p>Looks like you haven't saved any items yet.<br>Browse our menu to find your favorites!</p>
                        <a href="menu.php" class="btn-browse-menu">Browse Menu</a>
                    </div>`;
                document.getElementById('fav-pagination').style.display = 'none';
                return;
            }

            /* No sortable on PRODUCT IMAGE or ACTIONS columns */
            body.innerHTML = `
                <div class="table-responsive">
                    <table class="acct-fav-table">
                        <thead>
                            <tr>
                                <th>PRODUCT IMAGE</th>
                                <th data-sort="text" onclick="sortFavBy(1,'text')">PRODUCT NAME</th>
                                <th data-sort="text" onclick="sortFavBy(2,'text')">CATEGORY</th>
                                <th data-sort="number" onclick="sortFavBy(3,'number')">PRICE</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>${buildFavRows(items)}</tbody>
                    </table>
                </div>`;

            document.getElementById('fav-pagination').style.display = 'flex';
        }

        function renderFavPagination(d) {
            const total = d.total_pages || 1;
            document.getElementById('fav-page-info').textContent = `Page ${d.page} of ${total}`;
            document.getElementById('fav-page-controls').innerHTML = `
                <button class="btn-page" onclick="loadFavorites(${d.page - 1})"
                    ${d.page <= 1 ? 'disabled' : ''}>
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn-page" onclick="loadFavorites(${d.page + 1})"
                    ${d.page >= total ? 'disabled' : ''}>
                    <i class="bi bi-chevron-right"></i>
                </button>`;
        }

        /* ── ADD TO CART ────────────────────────────────────────── */
        function favAddToCart(productId, productName) {
            const fd = new FormData();
            fd.append('product_id', productId);
            fd.append('quantity', 1);
            fd.append('ajax', 1);
            fetch('add_to_cart.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) showFavToast(`${productName} added to cart!`, 'success');
                    else showFavToast(d.message || 'Failed to add.', 'error');
                });
        }

        /* ── DELETE MODAL ───────────────────────────────────────── */
        let pendingDeleteId = null;

        function openFavDeleteModal(productId, productName) {
            pendingDeleteId = productId;
            document.getElementById('favDeleteName').textContent = productName;
            document.getElementById('favDeleteModal').style.display = 'flex';
        }

        function closeFavDeleteModal() {
            pendingDeleteId = null;
            document.getElementById('favDeleteModal').style.display = 'none';
        }

        document.getElementById('favDeleteConfirmBtn').addEventListener('click', () => {
            if (!pendingDeleteId) return;
            const fd = new FormData();
            fd.append('action', 'remove');
            fd.append('product_id', pendingDeleteId);
            fetch('favorites.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    closeFavDeleteModal();
                    if (d.success) {
                        showFavToast('Removed from favorites.', 'success');
                        loadFavorites(favPage);
                    }
                });
        });

        /* Close modal on overlay click */
        document.getElementById('favDeleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeFavDeleteModal();
        });

        /* ── TOAST ──────────────────────────────────────────────── */
        function showFavToast(msg, type) {
            const toast = document.getElementById('fav-toast');
            toast.textContent = msg;
            toast.className   = `fav-toast fav-toast-${type} show`;
            clearTimeout(toast._t);
            toast._t = setTimeout(() => toast.classList.remove('show'), 3000);
        }

        /* ── AVATAR ─────────────────────────────────────────────── */
        function openAvatarEdit() {
            document.getElementById('avatarFileInput').click();
        }

        function previewAvatar(input) {
            if (!input.files || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                const wrap    = document.querySelector('.acct-avatar-wrap');
                let img       = document.getElementById('avatarPreview');
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

        /* ── PROFILE SAVE ───────────────────────────────────────── */
        function saveProfile(e) {
            e.preventDefault();
            const btn  = document.getElementById('saveBtn');
            const zone = document.getElementById('profile-alert-zone');
            const newPw  = document.getElementById('f-pw-new').value;
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

            fetch('php/update_profile.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    zone.innerHTML = d.success
                        ? '<div class="alert alert-success">Profile updated successfully.</div>'
                        : '<div class="alert alert-danger">' + (d.message || 'Update failed.') + '</div>';
                    if (d.success) {
                        document.querySelector('.profile-details h2').textContent = fd.get('full_name');
                        document.querySelector('.profile-email').textContent      = fd.get('email');
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