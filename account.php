<?php
/* Caffean Shop Customer Account Page */
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

/* Fetch order stats — includes kiosk orders matched by mobile number */
$stmt = mysqli_prepare($conn,
    "SELECT
     COUNT(*)                                                     AS total_orders,
     COALESCE(SUM(CASE
       WHEN is_kiosk = 1 AND payment_method = 'Pay at the counter (Cash)' AND status IN ('processing','completed') THEN total_amount
       WHEN is_kiosk = 1 AND payment_method != 'Pay at the counter (Cash)' AND status IN ('processing','completed') THEN total_amount
       WHEN COALESCE(is_kiosk,0) = 0 AND payment_method = 'Cash on Delivery' AND status = 'completed' THEN total_amount
       WHEN COALESCE(is_kiosk,0) = 0 AND payment_method != 'Cash on Delivery' AND status IN ('processing','completed') THEN total_amount
       ELSE 0 END), 0)                                           AS total_spent,
     COUNT(CASE
       WHEN is_kiosk = 1 AND payment_method = 'Pay at the counter (Cash)' AND status IN ('processing','completed') THEN 1
       WHEN is_kiosk = 1 AND payment_method != 'Pay at the counter (Cash)' AND status IN ('processing','completed') THEN 1
       WHEN COALESCE(is_kiosk,0) = 0 AND payment_method = 'Cash on Delivery' AND status = 'completed' THEN 1
       WHEN COALESCE(is_kiosk,0) = 0 AND payment_method != 'Cash on Delivery' AND status IN ('processing','completed') THEN 1
       END)                                                      AS qualifying_orders,
     COUNT(CASE WHEN status = 'pending'    THEN 1 END)           AS pending_orders,
     COUNT(CASE WHEN status = 'processing' THEN 1 END)           AS processing_orders,
     COUNT(CASE WHEN status = 'completed'  THEN 1 END)           AS completed_orders,
     COUNT(CASE WHEN status = 'cancelled'  THEN 1 END)           AS cancelled_orders
     FROM orders
     WHERE user_id = ? OR (is_kiosk = 1 AND mobile_number = (SELECT mobile_number FROM users WHERE user_id = ?))");
mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

/* Fetch favorites count */
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS fav_count FROM favorites WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$fav_count_res = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$fav_count = $fav_count_res['fav_count'] ?? 0;
mysqli_stmt_close($stmt);

/* Fetch full order history — includes kiosk orders matched by mobile */
$stmt = mysqli_prepare($conn,
    "SELECT o.order_id, o.order_number, o.total_amount, o.status, o.order_date,
     o.payment_method, o.order_type, o.is_kiosk, o.kiosk_order_type,
     COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON o.order_id = oi.order_id
     WHERE o.user_id = ?
        OR (o.is_kiosk = 1 AND o.mobile_number = (SELECT mobile_number FROM users WHERE user_id = ?))
     GROUP BY o.order_id
     ORDER BY o.order_date DESC");
mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
$orders_arr = [];
while ($row = mysqli_fetch_assoc($orders_result)) $orders_arr[] = $row;

/* Separate online vs kiosk for sub-tabs */
$online_orders = array_values(array_filter($orders_arr, fn($o) => empty($o['is_kiosk'])));
$kiosk_orders  = array_values(array_filter($orders_arr, fn($o) => !empty($o['is_kiosk'])));

/* ── INSIGHTS DATA ───────────────────────────────────────────── */

/* Spending by month — last 6 months */
$spend_labels = [];
$spend_data   = [];
for ($i = 5; $i >= 0; $i--) {
    $y = date('Y', strtotime("-$i months"));
    $m = date('m', strtotime("-$i months"));
    $spend_labels[] = date('M Y', strtotime("-$i months"));
    $row = mysqli_fetch_assoc(mysqli_prepare_and_execute($conn,
        "SELECT COALESCE(SUM(CASE
           WHEN is_kiosk = 1 AND payment_method = 'Pay at the counter (Cash)' AND status IN ('processing','completed') THEN total_amount
           WHEN is_kiosk = 1 AND payment_method != 'Pay at the counter (Cash)' AND status IN ('processing','completed') THEN total_amount
           WHEN COALESCE(is_kiosk,0) = 0 AND payment_method = 'Cash on Delivery' AND status = 'completed' THEN total_amount
           WHEN COALESCE(is_kiosk,0) = 0 AND payment_method != 'Cash on Delivery' AND status IN ('processing','completed') THEN total_amount
           ELSE 0 END),0) AS s FROM orders
         WHERE (user_id = ? OR (is_kiosk = 1 AND mobile_number = (SELECT mobile_number FROM users WHERE user_id = ?)))
           AND YEAR(order_date)=? AND MONTH(order_date)=?",
        "iiii", [$user_id, $user_id, $y, $m]));
    $spend_data[] = (float)($row['s'] ?? 0);
}

/* Top 5 ordered items — includes kiosk orders */
$stmt = mysqli_prepare($conn,
    "SELECT p.name, SUM(oi.quantity) AS qty, COALESCE(p.image_path,'') AS img
     FROM order_items oi
     JOIN orders o ON oi.order_id = o.order_id
     JOIN products p ON oi.product_id = p.product_id
     WHERE o.user_id = ?
        OR (o.is_kiosk = 1 AND o.mobile_number = (SELECT mobile_number FROM users WHERE user_id = ?))
     GROUP BY oi.product_id
     ORDER BY qty DESC LIMIT 5");
mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
mysqli_stmt_execute($stmt);
$top_items = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

/* Order type breakdown — includes kiosk orders */
$stmt = mysqli_prepare($conn,
    "SELECT order_type, COUNT(*) AS cnt FROM orders
     WHERE user_id = ? OR (is_kiosk = 1 AND mobile_number = (SELECT mobile_number FROM users WHERE user_id = ?))
     GROUP BY order_type");
mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
mysqli_stmt_execute($stmt);
$type_rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
$type_data = [];
foreach ($type_rows as $r) $type_data[ucfirst($r['order_type'])] = (int)$r['cnt'];

/* Payment method breakdown — includes kiosk orders */
$stmt = mysqli_prepare($conn,
    "SELECT payment_method, COUNT(*) AS cnt FROM orders
     WHERE user_id = ? OR (is_kiosk = 1 AND mobile_number = (SELECT mobile_number FROM users WHERE user_id = ?))
     GROUP BY payment_method");
mysqli_stmt_bind_param($stmt, "ii", $user_id, $user_id);
mysqli_stmt_execute($stmt);
$pay_rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);
$pay_data = [];
foreach ($pay_rows as $r) $pay_data[$r['payment_method']] = (int)$r['cnt'];

/* Average order value — based on qualifying (reflected) orders only */
$avg_order = $stats['qualifying_orders'] > 0
    ? round($stats['total_spent'] / $stats['qualifying_orders'], 2)
    : 0;

/* Helper: prepare + bind + execute in one call */
function mysqli_prepare_and_execute($conn, $sql, $types, $params) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($types && $params) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $res;
}

/* Helpers */
$initials   = strtoupper(substr($user['full_name'] ?? 'U', 0, 1));
$avatar_src = !empty($user['profile_image']) ? htmlspecialchars($user['profile_image']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Account — Caffean</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/search.css" />
    <link rel="stylesheet" href="css/account-page.css?v=<?php echo time(); ?>" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="page-account">

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Caffean" />
                <span>caffean</span>
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

    <div class="cart-modal-overlay" id="favDeleteModal">
        <div class="cart-modal">
            <div class="cart-modal-header">
                <h3>Remove Item</h3>
                <button class="cart-modal-close" onclick="closeFavDeleteModal()">&#x2715;</button>
            </div>
            <div class="cart-modal-body">
                <p class="cart-modal-subtitle">Are you sure you want to remove <strong id="favDeleteName"></strong> from your favorites? This cannot be undone.</p>
            </div>
            <div class="cart-modal-footer">
                <button class="cart-modal-btn-cancel" onclick="closeFavDeleteModal()">Cancel</button>
                <button class="cart-modal-btn-delete" id="favDeleteConfirmBtn">Remove Item</button>
            </div>
        </div>
    </div>

    <div class="acct-page">
        <div class="acct-dashboard">

            <aside class="acct-sidebar">

                <div class="acct-sidebar-profile">
                    <div class="acct-avatar-wrap">
                        <?php if ($avatar_src): ?>
                            <img src="<?= $avatar_src ?>" alt="Profile" class="acct-avatar-img" id="avatarPreview" />
                        <?php else: ?>
                            <div class="acct-avatar-initial" id="avatarInitial"><?= $initials ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-details">
                        <h2><?= htmlspecialchars($user['full_name'] ?? '—') ?></h2>
                        <p class="profile-email"><?= htmlspecialchars($user['email'] ?? '—') ?></p>
                    </div>
                </div>

                <nav class="acct-nav">
                    <a href="#" class="acct-nav-item active" onclick="openTab('orders', this); return false;">
                        <i class="far fa-clock acct-ic-out"></i>
                        <i class="fas fa-clock acct-ic-fill"></i>
                        <span class="acct-nav-text">Order History</span>
                    </a>
                    <a href="#" class="acct-nav-item" onclick="openTab('favorites', this); return false;">
                        <i class="far fa-heart acct-ic-out"></i>
                        <i class="fas fa-heart acct-ic-fill"></i>
                        <span class="acct-nav-text">Favorites</span>
                        <span class="acct-nav-badge" id="fav-nav-badge" style="<?php echo $fav_count > 0 ? '' : 'display:none;'; ?>"><?php echo $fav_count; ?></span>
                    </a>
                    <a href="#" class="acct-nav-item" onclick="openTab('insights', this); return false;">
                        <i class="fas fa-chart-line acct-ic-out"></i>
                        <i class="fas fa-chart-line acct-ic-fill"></i>
                        <span class="acct-nav-text">Insights</span>
                    </a>
                    <a href="#" class="acct-nav-item" onclick="openTab('profile', this); return false;">
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

                <div class="acct-stats-bar">
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
                        <span class="stat-val"><?= number_format($stats['pending_orders'] + $stats['processing_orders']) ?></span>
                    </div>
                </div>

                <div class="acct-main-card">

                    <div class="acct-tab-panel" id="panel-orders">
                        <div class="acct-card-header">
                            <div>
                                <h3>Order History</h3>
                                <p>Showing <?= count($orders_arr) ?> total orders from your history</p>
                            </div>
                        </div>

                        <div class="acct-subtabs">
                            <button class="acct-subtab active" onclick="openOrderSubTab('online', this)">
                                Order Online <span class="acct-subtab-count"><?= count($online_orders) ?></span>
                            </button>
                            <button class="acct-subtab" onclick="openOrderSubTab('kiosk', this)">
                                Self-Order Kiosk <span class="acct-subtab-count"><?= count($kiosk_orders) ?></span>
                            </button>
                        </div>

                        <div class="acct-subtab-panel" id="subtab-online">
                            <?php if (empty($online_orders)): ?>
                                <div class="acct-empty-state">
                                    <i class="bi bi-bag"></i>
                                    <p>No online orders yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="acct-subtab-toolbar">
                                    <div class="acct-subtab-search">
                                        <span class="srch-icon"><i class="fas fa-search"></i></span>
                                        <input type="text" id="onlineSearch" placeholder="Search orders..." oninput="filterOrderTable('onlineOrdersTable', this.value, 'onlinePageInfo', 'onlinePrev', 'onlineNext')" />
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="acct-orders-table" id="onlineOrdersTable">
                                        <thead>
                                            <tr>
                                                <th data-sort="text">ORDER ID</th>
                                                <th data-sort="date">DATE &amp; TIME</th>
                                                <th data-sort="number">ITEMS</th>
                                                <th data-sort="text">TYPE</th>
                                                <th data-sort="text">PAYMENT</th>
                                                <th data-sort="number">AMOUNT</th>
                                                <th data-sort="text">STATUS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($online_orders as $o):
                                                $orderId = !empty($o['order_number']) ? $o['order_number'] : fmt_id($o['is_kiosk'] ? 'SO' : 'ON', $o['order_id'], $o['order_date']);
                                                $status  = strtolower($o['status']);
                                            ?>
                                                <tr>
                                                    <td class="td-id" data-value="<?= htmlspecialchars($orderId) ?>"><?= htmlspecialchars($orderId) ?></td>
                                                    <td data-value="<?= $o['order_date'] ?>"><?= date('M d, Y · g:i A', strtotime($o['order_date'])) ?></td>
                                                    <td data-value="<?= (int)$o['item_count'] ?>"><?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                                                    <td data-value="<?= htmlspecialchars(ucfirst($o['order_type'] ?? 'Pickup')) ?>"><?= htmlspecialchars(ucfirst($o['order_type'] ?? 'Pickup')) ?></td>
                                                    <td data-value="<?= htmlspecialchars($o['payment_method']) ?>"><?= htmlspecialchars($o['payment_method']) ?></td>
                                                    <td class="td-amount" data-value="<?= $o['total_amount'] ?>">
                                                        &#8369;<?= number_format($o['total_amount'], 2) ?>
                                                    </td>
                                                    <td data-value="<?= $status ?>">
                                                        <span class="status-badge status-<?= $status ?>">
                                                            <?= strtoupper($status) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="acct-pagination" id="onlinePagination">
                                    <span class="page-info" id="onlinePageInfo">Page 1 of 1</span>
                                    <div class="page-controls">
                                        <button class="btn-page" id="onlinePrev"><i class="bi bi-chevron-left"></i></button>
                                        <button class="btn-page" id="onlineNext"><i class="bi bi-chevron-right"></i></button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="acct-subtab-panel hidden" id="subtab-kiosk">
                            <?php if (empty($kiosk_orders)): ?>
                                <div class="acct-empty-state">
                                    <i class="bi bi-display"></i>
                                    <p>No kiosk orders yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="acct-subtab-toolbar">
                                    <div class="acct-subtab-search">
                                        <span class="srch-icon"><i class="fas fa-search"></i></span>
                                        <input type="text" id="kioskSearch" placeholder="Search orders..." oninput="filterOrderTable('kioskOrdersTable', this.value, 'kioskPageInfo', 'kioskPrev', 'kioskNext')" />
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="acct-orders-table" id="kioskOrdersTable">
                                        <thead>
                                            <tr>
                                                <th data-sort="text">ORDER ID</th>
                                                <th data-sort="date">DATE &amp; TIME</th>
                                                <th data-sort="number">ITEMS</th>
                                                <th data-sort="text">TYPE</th>
                                                <th data-sort="text">PAYMENT</th>
                                                <th data-sort="number">AMOUNT</th>
                                                <th data-sort="text">STATUS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($kiosk_orders as $o):
                                                $orderId   = !empty($o['order_number']) ? $o['order_number'] : fmt_id($o['is_kiosk'] ? 'SO' : 'ON', $o['order_id'], $o['order_date']);
                                                $status    = strtolower($o['status']);
                                                $kioskType = $o['kiosk_order_type'] === 'dine_in' ? 'Dine In' : 'Take Out';
                                            ?>
                                                <tr>
                                                    <td class="td-id" data-value="<?= htmlspecialchars($orderId) ?>"><?= htmlspecialchars($orderId) ?></td>
                                                    <td data-value="<?= $o['order_date'] ?>"><?= date('M d, Y · g:i A', strtotime($o['order_date'])) ?></td>
                                                    <td data-value="<?= (int)$o['item_count'] ?>"><?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                                                    <td data-value="<?= $kioskType ?>"><?= $kioskType ?></td>
                                                    <td data-value="<?= htmlspecialchars($o['payment_method']) ?>"><?= htmlspecialchars($o['payment_method']) ?></td>
                                                    <td class="td-amount" data-value="<?= $o['total_amount'] ?>">
                                                        &#8369;<?= number_format($o['total_amount'], 2) ?>
                                                    </td>
                                                    <td data-value="<?= $status ?>">
                                                        <span class="status-badge status-<?= $status ?>">
                                                            <?= strtoupper($status) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="acct-pagination" id="kioskPagination">
                                    <span class="page-info" id="kioskPageInfo">Page 1 of 1</span>
                                    <div class="page-controls">
                                        <button class="btn-page" id="kioskPrev"><i class="bi bi-chevron-left"></i></button>
                                        <button class="btn-page" id="kioskNext"><i class="bi bi-chevron-right"></i></button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="acct-tab-panel hidden" id="panel-favorites">
                        <div class="acct-card-header">
                            <div>
                                <h3>Favorites</h3>
                                <p id="fav-subtitle">Loading your wishlist…</p>
                            </div>
                        </div>

                        <div id="fav-body">
                            <div class="fav-loading">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Loading favorites…</p>
                            </div>
                        </div>

                        <div id="fav-pagination" class="acct-pagination" style="display:none;">
                            <span class="page-info" id="fav-page-info"></span>
                            <div class="fav-page-controls" id="fav-page-controls"></div>
                        </div>
                    </div>

                    <div class="acct-tab-panel hidden" id="panel-insights">
                        <div class="acct-card-header">
                            <div>
                                <h3>Insights</h3>
                                <p>A summary of your spending and ordering activity</p>
                            </div>
                        </div>

                        <div class="ins-body">

                            <div class="ins-row ins-row--chart">

                                <div class="ins-panel ins-panel--chart">
                                    <div class="ins-panel-header">
                                        <span class="ins-panel-title">Spending Over Time</span>
                                        <span class="ins-panel-sub">Last 6 months</span>
                                    </div>
                                    <div class="ins-chart-wrap">
                                        <canvas id="insSpendChart"></canvas>
                                    </div>
                                </div>

                                <div class="ins-panel ins-panel--summary">
                                    <div class="ins-panel-header">
                                        <span class="ins-panel-title">Summary</span>
                                    </div>
                                    <div class="ins-summary-list">
                                        <div class="ins-summary-item">
                                            <span class="ins-summary-lbl">Average Order Value</span>
                                            <span class="ins-summary-val">&#8369;<?= number_format($avg_order, 2) ?></span>
                                        </div>
                                        <div class="ins-summary-item">
                                            <span class="ins-summary-lbl">Total Orders</span>
                                            <span class="ins-summary-val"><?= number_format($stats['total_orders']) ?></span>
                                        </div>
                                        <div class="ins-summary-item">
                                            <span class="ins-summary-lbl">Total Spent</span>
                                            <span class="ins-summary-val">&#8369;<?= number_format($stats['total_spent'], 2) ?></span>
                                        </div>
                                        <div class="ins-summary-item">
                                            <span class="ins-summary-lbl">Cancelled Orders</span>
                                            <span class="ins-summary-val"><?= number_format($stats['cancelled_orders']) ?></span>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <div class="ins-row ins-row--bottom">

                                <div class="ins-panel ins-panel--top-items">
                                    <div class="ins-panel-header">
                                        <span class="ins-panel-title">Top Ordered Items</span>
                                    </div>
                                    <?php if (empty($top_items)): ?>
                                        <div class="acct-empty-state"><i class="bi bi-cup-hot"></i><p>No orders yet.</p></div>
                                    <?php else: ?>
                                        <ul class="ins-top-list">
                                            <?php foreach ($top_items as $i => $item): ?>
                                                <li class="ins-top-item">
                                                    <span class="ins-top-rank"><?= $i + 1 ?></span>
                                                    <span class="ins-top-name"><?= htmlspecialchars($item['name']) ?></span>
                                                    <span class="ins-top-qty"><?= number_format($item['qty']) ?> ordered</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>

                                <div class="ins-panel ins-panel--donut">
                                    <div class="ins-panel-header">
                                        <span class="ins-panel-title">Order Type</span>
                                    </div>
                                    <?php if (empty($type_data)): ?>
                                        <div class="acct-empty-state"><i class="bi bi-truck"></i><p>No data yet.</p></div>
                                    <?php else: ?>
                                        <div class="ins-donut-wrap">
                                            <canvas id="insTypeChart"></canvas>
                                        </div>
                                        <ul class="ins-legend">
                                            <?php
                                            $type_colors = ['Delivery' => '#5B1312', 'Pickup' => '#4a8a6f'];
                                            $total_types = array_sum($type_data);
                                            foreach ($type_data as $label => $cnt):
                                                $color = $type_colors[$label] ?? '#c4a882';
                                                $pct = $total_types > 0 ? round($cnt / $total_types * 100) : 0;
                                            ?>
                                                <li class="ins-legend-item">
                                                    <span class="ins-legend-dot" style="background:<?= $color ?>"></span>
                                                    <span class="ins-legend-lbl"><?= htmlspecialchars($label) ?></span>
                                                    <span class="ins-legend-val"><?= $pct ?>%</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>

                                <div class="ins-panel ins-panel--donut">
                                    <div class="ins-panel-header">
                                        <span class="ins-panel-title">Payment Methods</span>
                                    </div>
                                    <?php if (empty($pay_data)): ?>
                                        <div class="acct-empty-state"><i class="bi bi-credit-card"></i><p>No data yet.</p></div>
                                    <?php else: ?>
                                        <div class="ins-donut-wrap">
                                            <canvas id="insPayChart"></canvas>
                                        </div>
                                        <ul class="ins-legend">
                                            <?php
                                            $pay_palette = ['#5B1312','#c49a3c','#1a6ea8','#2d8a5e','#8b4b9e'];
                                            $total_pays  = array_sum($pay_data);
                                            $pi = 0;
                                            foreach ($pay_data as $label => $cnt):
                                                $color = $pay_palette[$pi++ % count($pay_palette)];
                                                $pct = $total_pays > 0 ? round($cnt / $total_pays * 100) : 0;
                                            ?>
                                                <li class="ins-legend-item">
                                                    <span class="ins-legend-dot" style="background:<?= $color ?>"></span>
                                                    <span class="ins-legend-lbl"><?= htmlspecialchars($label) ?></span>
                                                    <span class="ins-legend-val"><?= $pct ?>%</span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div></div>

                    <div class="acct-tab-panel hidden" id="panel-profile">
                        <div class="acct-card-header">
                            <div>
                                <h3>Profile Settings</h3>
                                <p>Manage your account details and default addresses</p>
                            </div>
                        </div>

                        <div id="profile-info-alert-zone" class="acct-ps-alert-zone"></div>

                        <div class="acct-ps-row">

                            <div class="acct-ps-col">
                                <div class="acct-ps-card">
                                <p class="acct-ps-section-hd">Account Information</p>

                                <!-- Avatar upload row — matches admin profile settings -->
                                <div class="acct-ps-avatar-row">
                                    <div class="acct-ps-avatar-wrap" id="acctPsAvatarWrap"
                                         onclick="document.getElementById('avatarFileInput').click()" title="Edit profile photo">
                                        <?php if ($avatar_src): ?>
                                            <img src="<?= $avatar_src ?>" alt="Profile" class="acct-ps-avatar-img" id="avatarPreview" />
                                        <?php else: ?>
                                            <div class="acct-ps-avatar-initial" id="avatarInitial"><?= $initials ?></div>
                                        <?php endif; ?>
                                        <div class="acct-ps-avatar-pencil"><i class="bi bi-pencil-fill"></i></div>
                                    </div>
                                    <div class="acct-ps-avatar-meta">
                                        <span class="acct-ps-avatar-hint">Edit Profile Photo</span>
                                        <span class="acct-ps-avatar-hint-sub">Accepted formats: JPG, PNG, WEBP (Max 5MB)</span>
                                    </div>
                                    <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/webp"
                                           style="display:none" onchange="previewAvatar(this)" />
                                </div>

                                <form id="profileInfoForm" onsubmit="saveProfileInfo(event)">
                                    <div class="acct-ps-form-grid">
                                        <div class="acct-ps-field">
                                            <label>FULL NAME</label>
                                            <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required />
                                        </div>
                                        <div class="acct-ps-field">
                                            <label>EMAIL ADDRESS</label>
                                            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required />
                                        </div>
                                        <div class="acct-ps-field full-width">
                                            <label>MOBILE NUMBER</label>
                                            <input type="tel" name="mobile_number" value="<?= htmlspecialchars($user['mobile_number'] ?? '') ?>" placeholder="(+63 9XX XXX XXXX)" maxlength="16" pattern="(\+63|0)[0-9]{10}" />
                                        </div>
                                        <div class="acct-ps-field full-width acct-ps-sub-hd">
                                            Default Delivery Address
                                        </div>
                                        <div class="acct-ps-field">
                                            <label>HOUSE / UNIT NO.</label>
                                            <input type="text" name="house_unit" value="<?= htmlspecialchars($user['house_unit'] ?? '') ?>" />
                                        </div>
                                        <div class="acct-ps-field">
                                            <label>STREET</label>
                                            <input type="text" name="street_name" value="<?= htmlspecialchars($user['street_name'] ?? '') ?>" />
                                        </div>
                                        <div class="acct-ps-field">
                                            <label>BARANGAY</label>
                                            <input type="text" name="barangay" value="<?= htmlspecialchars($user['barangay'] ?? '') ?>" />
                                        </div>
                                        <div class="acct-ps-field">
                                            <label>CITY / MUNICIPALITY</label>
                                            <input type="text" name="city_municipality" value="<?= htmlspecialchars($user['city_municipality'] ?? '') ?>" />
                                        </div>
                                        <div class="acct-ps-field">
                                            <label>PROVINCE</label>
                                            <input type="text" name="province" value="<?= htmlspecialchars($user['province'] ?? '') ?>" placeholder="e.g., Davao del Sur" />
                                        </div>
                                        <div class="acct-ps-field">
                                            <label>ZIP CODE</label>
                                            <input type="text" name="zip_code" value="<?= htmlspecialchars($user['zip_code'] ?? '') ?>" placeholder="e.g., 8000" maxlength="4" inputmode="numeric" />
                                        </div>
                                    </div>
                                    <div class="acct-ps-form-actions">
                                        <button type="button" class="acct-ps-btn-discard" onclick="discardProfileInfo()">Discard</button>
                                        <button type="submit" class="acct-ps-btn-save" id="saveInfoBtn">Save Changes</button>
                                    </div>
                                </form>
                            </div><!-- /.acct-ps-card -->
                            </div><!-- /.acct-ps-col -->

                            <div class="acct-ps-col">
                                <div id="profile-pw-alert-zone" class="acct-ps-alert-zone"></div>
                                <div class="acct-ps-card">
                                    <p class="acct-ps-section-hd">Change Password</p>
                                    <form id="profilePwForm" onsubmit="saveProfilePw(event)">
                                        <div class="acct-ps-form-grid" style="margin-top:8px;">
                                            <div class="acct-ps-field full-width">
                                                <label>CURRENT PASSWORD</label>
                                                <div class="acct-ps-pw-wrap">
                                                    <input type="password" name="current_password" id="f-pw-cur" autocomplete="current-password" />
                                                    <button type="button" class="acct-ps-pw-toggle" onclick="toggleAcctPw('f-pw-cur', this)" aria-label="Toggle visibility">
                                                        <i class="bi bi-eye-slash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="acct-ps-field full-width">
                                                <label>NEW PASSWORD</label>
                                                <div class="acct-ps-pw-wrap">
                                                    <input type="password" name="new_password" id="f-pw-new" autocomplete="new-password" />
                                                    <button type="button" class="acct-ps-pw-toggle" onclick="toggleAcctPw('f-pw-new', this)" aria-label="Toggle visibility">
                                                        <i class="bi bi-eye-slash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="acct-ps-field full-width">
                                                <label>CONFIRM NEW PASSWORD</label>
                                                <div class="acct-ps-pw-wrap">
                                                    <input type="password" name="confirm_password" id="f-pw-confirm" autocomplete="new-password" />
                                                    <button type="button" class="acct-ps-pw-toggle" onclick="toggleAcctPw('f-pw-confirm', this)" aria-label="Toggle visibility">
                                                        <i class="bi bi-eye-slash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="acct-ps-form-actions">
                                            <button type="button" class="acct-ps-btn-discard" onclick="discardProfilePw()">Discard</button>
                                            <button type="submit" class="acct-ps-btn-save" id="savePwBtn">Save Changes</button>
                                        </div>
                                    </form>
                                </div><!-- /.acct-ps-card -->
                            </div><!-- /.acct-ps-col -->

                        </div>
                    </div>

                </div></main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
    <script>
        /* ── INSIGHTS DATA from PHP ─────────────────────────────── */
        const insSpendLabels = <?= json_encode($spend_labels) ?>;
        const insSpendData   = <?= json_encode($spend_data) ?>;
        const insTypeLabels  = <?= json_encode(array_keys($type_data)) ?>;
        const insTypeData    = <?= json_encode(array_values($type_data)) ?>;
        const insPayLabels   = <?= json_encode(array_keys($pay_data)) ?>;
        const insPayData     = <?= json_encode(array_values($pay_data)) ?>;

        /* ── TAB NAVIGATION ─────────────────────────────────────── */
        let insightsInitialized = false;

        function openTab(name, element) {
            document.querySelectorAll('.acct-nav-item').forEach(el => el.classList.remove('active'));
            if (element) element.classList.add('active');
            document.querySelectorAll('.acct-tab-panel').forEach(p => p.classList.add('hidden'));
            document.getElementById('panel-' + name).classList.remove('hidden');
            if (name === 'favorites') loadFavorites(favPage);
            if (name === 'insights' && !insightsInitialized) {
                initInsightsCharts();
                insightsInitialized = true;
            }
        }

        /* ── INSIGHTS CHARTS ────────────────────────────────────── */
        function initInsightsCharts() {
            /* Shared chart defaults matching admin dashboard style */
            const FONT   = 'Outfit';
            const MAROON = '#5B1312';
            const MUTED  = '#7a6a5a';

            /* Spending line chart */
            const spendCtx = document.getElementById('insSpendChart');
            if (spendCtx && insSpendLabels.length) {
                const grad = spendCtx.getContext('2d').createLinearGradient(0, 0, 0, 220);
                grad.addColorStop(0, 'rgba(91,19,18,0.18)');
                grad.addColorStop(1, 'rgba(91,19,18,0.0)');
                new Chart(spendCtx, {
                    type: 'line',
                    data: {
                        labels: insSpendLabels,
                        datasets: [{
                            label: 'Spent (₱)',
                            data: insSpendData,
                            borderColor: MAROON,
                            borderWidth: 2.5,
                            pointBackgroundColor: MAROON,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            fill: true,
                            backgroundColor: grad,
                            tension: 0.42
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#2A0000',
                                titleColor: '#e8d5b0',
                                bodyColor: '#fff',
                                borderColor: 'rgba(255,255,255,0.1)',
                                borderWidth: 1,
                                padding: 12,
                                callbacks: { label: c => '  ₱' + c.parsed.y.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: { color: MUTED, font: { family: FONT, size: 11 } }
                            },
                            y: {
                                grid: { color: 'rgba(42,0,0,0.06)' },
                                border: { display: false, dash: [4, 4] },
                                ticks: {
                                    color: MUTED,
                                    font: { family: FONT, size: 11 },
                                    callback: v => '₱' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v)
                                }
                            }
                        }
                    }
                });
            }

            /* Shared doughnut options */
            const doughnutOpts = (labels) => ({
                type: 'doughnut',
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#2A0000',
                            titleColor: '#e8d5b0',
                            bodyColor: '#fff',
                            padding: 10,
                            callbacks: { label: c => `  ${labels[c.dataIndex]}: ${c.parsed}` }
                        }
                    }
                }
            });

            /* Order type doughnut */
            const typeCtx = document.getElementById('insTypeChart');
            if (typeCtx && insTypeLabels.length) {
                const opts = doughnutOpts(insTypeLabels);
                opts.data = {
                    labels: insTypeLabels,
                    datasets: [{ data: insTypeData, backgroundColor: ['#5B1312','#4a8a6f','#c49a3c'], borderWidth: 0, hoverOffset: 4 }]
                };
                new Chart(typeCtx, opts);
            }

            /* Payment method doughnut */
            const payCtx = document.getElementById('insPayChart');
            if (payCtx && insPayLabels.length) {
                const opts = doughnutOpts(insPayLabels);
                opts.data = {
                    labels: insPayLabels,
                    datasets: [{ data: insPayData, backgroundColor: ['#5B1312','#c49a3c','#1a6ea8','#2d8a5e','#8b4b9e'], borderWidth: 0, hoverOffset: 4 }]
                };
                new Chart(payCtx, opts);
            }
        }

        /* ── SORTABLE TABLE ─────────────────────────────────────── */
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
                    table.querySelectorAll('thead th[data-sort]').forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
                    th.classList.add(currentDir === 'asc' ? 'sort-asc' : 'sort-desc');

                    const tbody = table.querySelector('tbody');
                    const rows  = Array.from(tbody.querySelectorAll('tr'));
                    rows.sort((a, b) => {
                        const av = a.cells[col]?.dataset.value ?? '';
                        const bv = b.cells[col]?.dataset.value ?? '';
                        let cmp = 0;
                        if (type === 'number') {
                            cmp = parseFloat(av.replace(/[^0-9.-]/g, '') || 0) - parseFloat(bv.replace(/[^0-9.-]/g, '') || 0);
                        } else if (type === 'date') {
                            cmp = new Date(av) - new Date(bv);
                        } else {
                            cmp = av.toLowerCase().localeCompare(bv.toLowerCase());
                        }
                        return currentDir === 'asc' ? cmp : -cmp;
                    });
                    rows.forEach(r => tbody.appendChild(r));
                });
            });
        }

        initSortableTable('onlineOrdersTable');
        initSortableTable('kioskOrdersTable');

        /* ── ORDER SUB-TAB SWITCHER ─────────────────────────────── */
        function openOrderSubTab(name, el) {
            document.querySelectorAll('.acct-subtab').forEach(b => b.classList.remove('active'));
            el.classList.add('active');
            document.querySelectorAll('.acct-subtab-panel').forEach(p => p.classList.add('hidden'));
            document.getElementById('subtab-' + name).classList.remove('hidden');
        }

        /* ── TABLE SEARCH FILTER ────────────────────────────────── */
        function filterOrderTable(tableId, query, infoId, prevId, nextId) {
            const table = document.getElementById(tableId);
            if (!table) return;
            const q = query.trim().toLowerCase();
            table.querySelectorAll('tbody tr').forEach(row => {
                row.dataset.filtered = q === '' || row.textContent.toLowerCase().includes(q) ? '1' : '0';
            });
            renderPaginatedTable(tableId, 1, infoId, prevId, nextId);
        }

        /* ── PAGINATED TABLE RENDERER ───────────────────────────── */
        const _pgState = {};

        function renderPaginatedTable(tableId, page, infoId, prevId, nextId) {
            const table = document.getElementById(tableId);
            if (!table) return;
            const perPage = 10;
            const rows    = Array.from(table.querySelectorAll('tbody tr'))
                .filter(r => r.dataset.filtered !== '0');
            const total   = Math.max(1, Math.ceil(rows.length / perPage));
            page = Math.min(Math.max(1, page), total);
            _pgState[tableId] = { page, infoId, prevId, nextId };

            Array.from(table.querySelectorAll('tbody tr')).forEach(r => r.style.display = 'none');
            rows.slice((page - 1) * perPage, page * perPage).forEach(r => r.style.display = '');

            document.getElementById(infoId).textContent = `Page ${page} of ${total}`;
            document.getElementById(prevId).disabled = page <= 1;
            document.getElementById(nextId).disabled = page >= total;
        }

        /* ── SIMPLE TABLE PAGINATION ────────────────────────────── */
        function initTablePagination(tableId, infoId, prevId, nextId) {
            Array.from(document.getElementById(tableId)?.querySelectorAll('tbody tr') ?? [])
                .forEach(r => r.dataset.filtered = '1');
            renderPaginatedTable(tableId, 1, infoId, prevId, nextId);
            document.getElementById(prevId).addEventListener('click', () => {
                const s = _pgState[tableId];
                if (s) renderPaginatedTable(tableId, s.page - 1, s.infoId, s.prevId, s.nextId);
            });
            document.getElementById(nextId).addEventListener('click', () => {
                const s = _pgState[tableId];
                if (s) renderPaginatedTable(tableId, s.page + 1, s.infoId, s.prevId, s.nextId);
            });
        }

        initTablePagination('onlineOrdersTable', 'onlinePageInfo', 'onlinePrev', 'onlineNext');
        initTablePagination('kioskOrdersTable',  'kioskPageInfo',  'kioskPrev',  'kioskNext');

        /* ── FAVORITES ──────────────────────────────────────────── */
        let favPage = 1;
        let favSortCol = -1;
        let favSortDir = 'asc';
        let favAllItems = [];

        function loadFavorites(page) {
            favPage = page || 1;
            fetch(`favorites.php?action=get&page=${favPage}`)
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(d => {
                    if (!d.success) throw new Error(d.message || 'Failed');
                    const total = d.total || 0;
                    document.getElementById('fav-subtitle').textContent =
                        total === 0
                            ? 'You have no items in your wishlist'
                            : `You have ${total} item${total !== 1 ? 's' : ''} in your wishlist`;
                    favAllItems = d.items || [];
                    renderFavTable(favAllItems);
                    renderFavPagination(d);
                    updateFavBadge(total);
                })
                .catch(() => {
                    /* Show empty state — favorites.php unreachable or returned error */
                    renderFavTable([]);
                    document.getElementById('fav-subtitle').textContent = 'You have no items in your wishlist';
                    document.getElementById('fav-pagination').style.display = 'none';
                });
        }

        function sortFavBy(col, type) {
            if (favSortCol === col) {
                favSortDir = favSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                favSortCol = col;
                favSortDir = 'asc';
            }
            const sorted = [...favAllItems].sort((a, b) => {
                const keys = ['', 'name', 'price'];
                const av = String(a[keys[col]] ?? '');
                const bv = String(b[keys[col]] ?? '');
                const cmp = type === 'number'
                    ? parseFloat(av) - parseFloat(bv)
                    : av.toLowerCase().localeCompare(bv.toLowerCase());
                return favSortDir === 'asc' ? cmp : -cmp;
            });
            const tbody = document.querySelector('#fav-body tbody');
            if (tbody) tbody.innerHTML = buildFavRows(sorted);
        }

        function buildFavRows(items) {
            return items.map(item => {
                const img = item.image_path
                    ? `<img src="${item.image_path}" class="fav-product-img" alt="${item.name}">`
                    : `<div class="fav-product-img" style="background:rgba(42,0,0,0.06);display:flex;align-items:center;justify-content:center;"><i class="bi bi-cup-hot" style="color:var(--dark-brown);opacity:0.4;font-size:1.4rem;"></i></div>`;
                return `<tr>
                    <td>${img}</td>
                    <td class="td-fav-name">${item.name}</td>
                    <td class="td-fav-price">&#8369;${parseFloat(item.price).toFixed(2)}</td>
                    <td><div class="fav-td-action">
                        <button class="fav-btn-cart" onclick="favAddToCart(${item.product_id},'${item.name.replace(/'/g,"\\'")}')"><i class="bi bi-cart-plus"></i></button>
                        <button class="fav-btn-remove" onclick="openFavDeleteModal(${item.product_id},'${item.name.replace(/'/g,"\\'")}')"><i class="bi bi-trash3"></i></button>
                    </div></td>
                </tr>`;
            }).join('');
        }

        function renderFavTable(items) {
            const body = document.getElementById('fav-body');
            if (!items || !items.length) {
                body.innerHTML = `
                    <div class="fav-empty-state">
                        <i class="fas fa-heart"></i>
                        <h2>No favorites yet</h2>
                        <p>Looks like you haven't saved any items yet.<br>Browse our menu to find your favorites!</p>
                        <a href="menu.php" class="btn-browse-menu">Browse Menu</a>
                    </div>`;
                document.getElementById('fav-pagination').style.display = 'none';
                updateFavBadge(0);
                return;
            }
            body.innerHTML = `
                <div class="table-responsive">
                    <table class="acct-fav-table">
                        <thead>
                            <tr>
                                <th>PRODUCT IMAGE</th>
                                <th data-sort="text" onclick="sortFavBy(1,'text')">PRODUCT NAME</th>
                                <th data-sort="number" onclick="sortFavBy(2,'number')">PRICE</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>${buildFavRows(items)}</tbody>
                    </table>
                </div>`;
        }

        function renderFavPagination(d) {
            const total = d.total_pages || 1;
            document.getElementById('fav-page-info').textContent = `Page ${d.page} of ${total}`;
            document.getElementById('fav-page-controls').innerHTML = `
                <button class="btn-page" onclick="loadFavorites(${d.page - 1})" ${d.page <= 1 ? 'disabled' : ''}>
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn-page" onclick="loadFavorites(${d.page + 1})" ${d.page >= total ? 'disabled' : ''}>
                    <i class="bi bi-chevron-right"></i>
                </button>`;
            if (total > 1) document.getElementById('fav-pagination').style.display = 'flex';
        }

        /* Update Favorites nav badge count */
        function updateFavBadge(count) {
            const badge = document.getElementById('fav-nav-badge');
            if (!badge) return;
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = '';
            } else {
                badge.style.display = 'none';
            }
        }

        /* ── ADD TO CART — matches menu/supplies.php addToProductCart ── */
        function favAddToCart(productId, productName) {
            const fd = new FormData();
            fd.append('product_id', productId);
            fd.append('quantity', 1);
            fd.append('ajax', 1);
            fetch('php/add_to_cart.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        // Detect whether item was newly added or quantity increased
                        const isUpdate = d.message && d.message.includes('quantity updated');
                        const msg = isUpdate
                            ? 'Product quantity increased.'
                            : productName + ' added to your cart.';
                        showNotification(msg, 'success');
                        // Update cart badge count in real-time
                        if (typeof updateCartCountDisplay === 'function' && d.cart_count != null)
                            updateCartCountDisplay(d.cart_count);
                        // Animate cart icon
                        if (typeof animateCartIcon === 'function') animateCartIcon();
                    } else {
                        showNotification(d.message || 'Could not add to cart.', 'error');
                    }
                })
                .catch(() => showNotification('Error adding to cart.', 'error'));
        }

        /* ── DELETE MODAL — matches cart.php remove item modal ──────── */
        let pendingDeleteId   = null;
        let pendingDeleteName = null;

        function openFavDeleteModal(productId, productName) {
            pendingDeleteId   = productId;
            pendingDeleteName = productName;
            document.getElementById('favDeleteName').textContent = productName;
            document.getElementById('favDeleteModal').classList.add('open');
        }

        function closeFavDeleteModal() {
            pendingDeleteId   = null;
            pendingDeleteName = null;
            document.getElementById('favDeleteModal').classList.remove('open');
        }

        document.getElementById('favDeleteConfirmBtn').addEventListener('click', () => {
            if (!pendingDeleteId) return;
            const name = pendingDeleteName || 'Product';
            const fd = new FormData();
            fd.append('action', 'remove');
            fd.append('product_id', pendingDeleteId);
            fetch('favorites.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    closeFavDeleteModal();
                    if (d.success) {
                        showNotification(name + ' removed from favorites.', 'info');
                        const newPage = favAllItems.length <= 1 && favPage > 1 ? favPage - 1 : favPage;
                        loadFavorites(newPage);
                    }
                })
                .catch(() => showNotification('Could not remove from favorites.', 'error'));
        });

        document.getElementById('favDeleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeFavDeleteModal();
        });

        function previewAvatar(input) {
            if (!input.files || !input.files[0]) return;
            const reader = new FileReader();
            reader.onload = e => {
                const wrap    = document.getElementById('acctPsAvatarWrap');
                let img       = document.getElementById('avatarPreview');
                const initial = document.getElementById('avatarInitial');
                if (!img) {
                    img    = document.createElement('img');
                    img.id = 'avatarPreview';
                    img.className = 'acct-ps-avatar-img';
                    if (initial) initial.replaceWith(img);
                    else wrap.insertBefore(img, wrap.querySelector('.acct-ps-avatar-pencil'));
                }
                img.src = e.target.result;
                /* Sync sidebar avatar */
                const sidebarImg     = document.querySelector('.acct-avatar-img');
                const sidebarInitial = document.getElementById('avatarInitial');
                if (sidebarImg) sidebarImg.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }

        /* ── PROFILE SAVE ───────────────────────────────────────── */
        /* ── PASSWORD TOGGLE ────────────────────────────────────── */
        function toggleAcctPw(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon  = btn.querySelector('i');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'bi bi-eye' : 'bi bi-eye-slash';
        }

        /* Stored originals for discard */
        const _origInfo = {
            full_name:        <?= json_encode($user['full_name'] ?? '') ?>,
            email:            <?= json_encode($user['email'] ?? '') ?>,
            mobile_number:    <?= json_encode($user['mobile_number'] ?? '') ?>,
            house_unit:       <?= json_encode($user['house_unit'] ?? '') ?>,
            street_name:      <?= json_encode($user['street_name'] ?? '') ?>,
            barangay:         <?= json_encode($user['barangay'] ?? '') ?>,
            city_municipality:<?= json_encode($user['city_municipality'] ?? '') ?>,
            province:         <?= json_encode($user['province'] ?? '') ?>,
            zip_code:         <?= json_encode($user['zip_code'] ?? '') ?>
        };

        function discardProfileInfo() {
            const f = document.getElementById('profileInfoForm');
            Object.keys(_origInfo).forEach(k => { if (f[k]) f[k].value = _origInfo[k]; });
        }

        function discardProfilePw() {
            ['f-pw-cur', 'f-pw-new', 'f-pw-confirm'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                el.value = '';
                if (el.type === 'text') el.type = 'password';
            });
            document.querySelectorAll('#profilePwForm .acct-ps-pw-toggle i').forEach(ic => {
                ic.className = 'bi bi-eye-slash';
            });
            document.getElementById('profile-pw-alert-zone').innerHTML = '';
        }

        /* ── SAVE PASSWORD ──────────────────────────────────────── */
        function saveProfilePw(e) {
            e.preventDefault();
            const btn    = document.getElementById('savePwBtn');
            const zone   = document.getElementById('profile-pw-alert-zone');
            const curPw  = document.getElementById('f-pw-cur').value;
            const newPw  = document.getElementById('f-pw-new').value;
            const confPw = document.getElementById('f-pw-confirm').value;

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

            btn.disabled = true;
            btn.textContent = 'Saving...';

            const fd = new FormData(document.getElementById('profilePwForm'));
            fd.append('action', 'password');

            fetch('php/update_profile.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    zone.innerHTML = d.success
                        ? '<div class="flash-success"><i class="bi bi-check-circle"></i> Password updated successfully.</div>'
                        : '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> ' + (d.message || 'Update failed.') + '</div>';
                    if (d.success) discardProfilePw();
                    setTimeout(() => zone.innerHTML = '', 5000);
                })
                .catch(() => zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> Network error.</div>')
                .finally(() => { btn.disabled = false; btn.textContent = 'Save Changes'; });
        }
        /* ── SAVE ACCOUNT INFO ──────────────────────────────────── */
        function saveProfileInfo(e) {
            e.preventDefault();
            const btn  = document.getElementById('saveInfoBtn');
            const zone = document.getElementById('profile-info-alert-zone');

            btn.disabled = true;
            btn.textContent = 'Saving...';

            const fd = new FormData(document.getElementById('profileInfoForm'));
            fd.append('action', 'info');
            const avatarFile = document.getElementById('avatarFileInput').files[0];
            if (avatarFile) fd.append('avatar', avatarFile);

            fetch('php/update_profile.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    zone.innerHTML = d.success
                        ? '<div class="flash-success"><i class="bi bi-check-circle"></i> Profile updated successfully.</div>'
                        : '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> ' + (d.message || 'Update failed.') + '</div>';
                    if (d.success) {
                        document.querySelector('.profile-details h2').textContent = fd.get('full_name');
                        document.querySelector('.profile-email').textContent      = fd.get('email');
                    }
                    setTimeout(() => zone.innerHTML = '', 5000);
                })
                .catch(() => zone.innerHTML = '<div class="flash-error"><i class="bi bi-exclamation-circle"></i> Network error.</div>')
                .finally(() => { btn.disabled = false; btn.textContent = 'Save Changes'; });
        }
    </script>
</body>
</html>