<?php

/**
 * Purge Coffee Shop — Customer Account Page  (account.php)
 * Displays profile card, stat cards, and order history table.
 * Mirrors admin dashboard UI/UX design language.
 */

require_once 'php/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Admins always go to the admin panel
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ── Fetch user ────────────────────────────────────────────────
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// ── Fetch stats ───────────────────────────────────────────────
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        COUNT(*)                                          AS total_orders,
        COALESCE(SUM(total_amount), 0)                   AS total_spent,
        COUNT(CASE WHEN status = 'pending'    THEN 1 END) AS pending_orders,
        COUNT(CASE WHEN status = 'completed'  THEN 1 END) AS completed_orders
     FROM orders WHERE user_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// ── Fetch order history ───────────────────────────────────────
$stmt = mysqli_prepare(
    $conn,
    "SELECT o.order_id, o.total_amount, o.status, o.order_date,
            o.payment_method, COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON o.order_id = oi.order_id
     WHERE o.user_id = ?
     GROUP BY o.order_id
     ORDER BY o.order_date DESC
     LIMIT 10"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
$orders_arr = [];
while ($row = mysqli_fetch_assoc($orders_result)) $orders_arr[] = $row;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Account — Purge Coffee</title>

    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/footer-section.css" />
    <link rel="stylesheet" href="css/search.css" />
    <link rel="stylesheet" href="css/account-page.css" />
</head>

<body>

    <!-- ── Navbar ────────────────────────────────────────────── -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Purge Coffee Logo" />
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
                <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                    <a href="cart.php" class="text-decoration-none">
                        <i class="fas fa-shopping-cart nav-icon"></i>
                    </a>
                <?php endif; ?>
                <a href="account.php" class="text-decoration-none">
                    <i class="fas fa-user nav-icon active-icon"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- ── Account Page Body ─────────────────────────────────── -->
    <div class="acct-page">
        <div class="acct-container">

            <!-- ── Page Header ─────────────────────────────────────── -->
            <div class="acct-page-header">
                <div>
                    <h1>My Account</h1>
                    <p>View your profile, order stats, and order history</p>
                </div>

            </div>

            <!-- ── Profile Card ─────────────────────────────────────── -->
            <div class="acct-profile-card">
                <div class="acct-profile-left">
                    <div class="acct-avatar">
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    </div>
                    <div class="acct-profile-info">
                        <h2><?= htmlspecialchars($user['full_name']) ?></h2>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                        <span class="acct-member-since">
                            <i class="fas fa-calendar-alt"></i>
                            Member since <?= date('F Y', strtotime($user['created_at'])) ?>
                        </span>
                    </div>
                </div>
                <div class="acct-profile-actions">
                    <a href="php/logout.php" class="acct-btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Log Out
                    </a>
                </div>
            </div>

            <!-- ── Stat Cards ────────────────────────────────────────── -->
            <div class="acct-stat-grid">
                <div class="acct-stat-card">
                    <h4>Total Orders</h4>
                    <div class="number"><?= number_format($stats['total_orders']) ?></div>
                    <div class="icon"><i class="fas fa-receipt"></i></div>
                </div>
                <div class="acct-stat-card">
                    <h4>Total Spent</h4>
                    <div class="number">&#8369;<?= number_format($stats['total_spent'], 2) ?></div>
                    <div class="icon"><i class="fas fa-coins"></i></div>
                </div>
                <div class="acct-stat-card">
                    <h4>Completed Orders</h4>
                    <div class="number"><?= number_format($stats['completed_orders']) ?></div>
                    <div class="icon"><i class="fas fa-circle-check"></i></div>
                </div>
                <div class="acct-stat-card">
                    <h4>Pending Orders</h4>
                    <div class="number"><?= number_format($stats['pending_orders']) ?></div>
                    <div class="icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>

            <!-- ── Order History Table ────────────────────────────────── -->
            <div class="acct-card" id="order-history">
                <div class="acct-card-header">
                    <h2>Order History</h2>
                    <span class="acct-card-count">
                        <?= count($orders_arr) ?> order<?= count($orders_arr) !== 1 ? 's' : '' ?>
                    </span>
                </div>

                <table class="acct-table" id="orderHistoryTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Payment</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders_arr)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="acct-empty-state">
                                        <i class="fas fa-shopping-bag"></i>
                                        <p>No orders yet</p>
                                        <a href="menu.php" class="btn-primary-site">
                                            Browse Menu
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders_arr as $o): ?>
                                <tr>
                                    <td class="acct-td-id">#<?= $o['order_id'] ?></td>
                                    <td><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
                                    <td><?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                                    <td><?= htmlspecialchars($o['payment_method']) ?></td>
                                    <td>&#8369;<?= number_format($o['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="acct-badge acct-badge-<?= strtolower($o['status']) ?>">
                                            <?= ucfirst($o['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div><!-- /.acct-container -->
    </div><!-- /.acct-page -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/search.js"></script>
</body>

</html>