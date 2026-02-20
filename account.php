<?php
/**
 * Purge Coffee Shop — User Account Page
 * Displays user info, order history, and stats.
 * Admin users see a role-switcher info banner only (no duplicate button).
 */

require_once 'php/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Admin users go directly to the admin dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$is_admin = false; // always false here since admins are redirected above

// Fetch user
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Fetch recent orders
$stmt = mysqli_prepare($conn,
    "SELECT o.*, COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON o.order_id = oi.order_id
     WHERE o.user_id = ?
     GROUP BY o.order_id
     ORDER BY o.order_date DESC
     LIMIT 10");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Stats
$stmt = mysqli_prepare($conn,
    "SELECT
        COUNT(*)                                        AS total_orders,
        COALESCE(SUM(total_amount), 0)                 AS total_spent,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending_orders
     FROM orders WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account — Purge Coffee</title>

    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/footer-section.css">
    <link rel="stylesheet" href="css/search.css">
    <link rel="stylesheet" href="css/account-page.css">
</head>
<body>

    <!-- ── Announcement Bar ── -->
    <div class="top-banner">Shipping Nationwide</div>

    <!-- ── Navbar ── -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Purge Coffee Logo">
                <span>purge coffee</span>
            </a>

            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="supplies-page.php">Offers</a></li>
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

    <!-- ── Account Section ── -->
    <section class="account-section">
        <div class="container account-container">

            <!-- Profile Card -->
            <div class="account-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <h2><?= htmlspecialchars($user['full_name']) ?></h2>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                        <p class="member-since">Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
                    </div>
                </div>

                <div class="account-actions">
                    <?php if ($is_admin): ?>
                    <!-- Single Admin Dashboard button — role-switcher bar no longer duplicates this -->
                    <a href="/purge-coffee/admin/dashboard.php" class="btn-dashboard">
                        <i class="fas fa-gauge-high"></i> Admin Dashboard
                    </a>
                    <?php endif; ?>
                    <a href="php/logout.php" class="btn-logout">
                        <i class="fas fa-right-from-bracket"></i> Log out
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-value"><?= $stats['total_orders'] ?? 0 ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                    <div class="stat-value">&#8369;<?= number_format($stats['total_spent'] ?? 0, 2) ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="stat-value"><?= $stats['pending_orders'] ?? 0 ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>

            <!-- Order History -->
            <div class="orders-section">
                <h3 class="section-title">Order History</h3>

                <?php if (mysqli_num_rows($orders_result) > 0): ?>
                    <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">Order #<?= $order['order_id'] ?></span>
                            <span class="order-status status-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                        <div class="order-details">
                            <div class="order-detail-item">
                                <span class="detail-label">Date</span>
                                <span class="detail-value"><?= date('M d, Y', strtotime($order['order_date'])) ?></span>
                            </div>
                            <div class="order-detail-item">
                                <span class="detail-label">Items</span>
                                <span class="detail-value"><?= $order['item_count'] ?> item<?= $order['item_count'] != 1 ? 's' : '' ?></span>
                            </div>
                            <div class="order-detail-item">
                                <span class="detail-label">Total Amount</span>
                                <span class="detail-value">&#8369;<?= number_format($order['total_amount'], 2) ?></span>
                            </div>
                            <div class="order-detail-item">
                                <span class="detail-label">Payment</span>
                                <span class="detail-value"><?= htmlspecialchars($order['payment_method']) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No Orders Yet</h3>
                        <p>Start exploring our menu and place your first order!</p>
                        <a href="menu.php" class="btn-browse">Browse Menu</a>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <!-- ── Footer (matches Image 4 exactly) ── -->
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

    <!-- Search Overlay -->
    <div id="searchOverlay" class="search-overlay" style="display:none;">
        <div class="search-overlay-content">
            <button class="search-close-btn" onclick="hideSearchOverlay()">
                <i class="fas fa-times"></i>
            </button>
            <input type="text" id="searchInput" class="search-input" placeholder="Search products...">
            <div id="searchResults" class="search-results"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/search.js"></script>
</body>
</html>