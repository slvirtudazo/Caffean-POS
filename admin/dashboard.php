<?php

/**
 * Purge Coffee Shop — Admin Dashboard  (dashboard.php)
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../login.php');
  exit();
}

// ── Statistics ────────────────────────────────────────────────
$stats = [];

// Products: active (status=1) and inactive (status=0)
$row = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT COUNT(*) AS total FROM products WHERE status = 1"
));
$stats['products_active'] = (int)$row['total'];

$row = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT COUNT(*) AS total FROM products WHERE status = 0"
));
$stats['products_inactive'] = (int)$row['total'];

// Orders total
$row = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT COUNT(*) AS total FROM orders"
));
$stats['orders'] = $row['total'];

// Customers: active (has placed ≥1 order) and inactive (no orders)
$row = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT COUNT(DISTINCT u.user_id) AS total
   FROM users u
   JOIN orders o ON u.user_id = o.user_id
   WHERE u.role = 'customer'"
));
$stats['customers_active'] = (int)$row['total'];

$row = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT COUNT(*) AS total
   FROM users u
   LEFT JOIN orders o ON u.user_id = o.user_id
   WHERE u.role = 'customer' AND o.order_id IS NULL"
));
$stats['customers_inactive'] = (int)$row['total'];

// Revenue
$row = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT COALESCE(SUM(total_amount), 0) AS revenue FROM orders WHERE status = 'completed'"
));
$stats['revenue'] = $row['revenue'];

// Unread messages
$msg_result = mysqli_query(
  $conn,
  "SELECT COUNT(*) AS total FROM contact_messages WHERE is_read = 0"
);
$stats['messages'] = $msg_result ? (int)mysqli_fetch_assoc($msg_result)['total'] : 0;

include 'includes/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1>Dashboard</h1>
    <p>View an overview of key metrics, sales, and recent store activity</p>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <?php if ($stats['messages'] > 0): ?>
      <a href="messages.php" class="btn-primary" style="background:var(--saddle-brown,#8B4513);">
        <i class="fas fa-envelope"></i>
        <?= $stats['messages'] ?> Unread Message<?= $stats['messages'] > 1 ? 's' : '' ?>
      </a>
    <?php endif; ?>
  </div>
</div>

<div class="stat-grid">

  <!-- Total Revenue Card -->
  <div class="stat-card">
    <div class="stat-header">
      <h4>Total Revenue</h4>
      <div class="icon"><i class="fas fa-coins"></i></div>
    </div>
    <div class="stat-body">
      <div class="stat-dual">
        <div class="stat-dual-row">
          <span class="stat-dual-num">&#8369;<?= number_format($stats['revenue'], 2) ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Total Orders Card -->
  <div class="stat-card">
    <div class="stat-header">
      <h4>Total Orders</h4>
      <div class="icon"><i class="fas fa-receipt"></i></div>
    </div>
    <div class="stat-body">
      <div class="stat-dual">
        <div class="stat-dual-row">
          <span class="stat-dual-num"><?= number_format($stats['orders']) ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Registered Customers Card -->
  <div class="stat-card">
    <div class="stat-header">
      <h4>Registered Customers</h4>
      <div class="icon"><i class="fas fa-users"></i></div>
    </div>
    <div class="stat-body">
      <div class="stat-dual">
        <div class="stat-dual-row">
          <span class="stat-dual-num"><?= number_format($stats['customers_active']) ?></span>
          <span class="stat-dual-label">Active</span>
        </div>
        <div class="stat-dual-row">
          <span class="stat-dual-num inactive"><?= number_format($stats['customers_inactive']) ?></span>
          <span class="stat-dual-label">Inactive</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Inventory Status Card -->
  <div class="stat-card">
    <div class="stat-header">
      <h4>Inventory Status</h4>
      <div class="icon"><i class="fas fa-shopping-cart"></i></div>
    </div>
    <div class="stat-body">
      <div class="stat-dual">
        <div class="stat-dual-row">
          <span class="stat-dual-num"><?= number_format($stats['products_active']) ?></span>
          <span class="stat-dual-label">Active</span>
        </div>
        <div class="stat-dual-row">
          <span class="stat-dual-num inactive"><?= number_format($stats['products_inactive']) ?></span>
          <span class="stat-dual-label">Inactive</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Unread Inquiries Card -->
  <div class="stat-card">
    <div class="stat-header">
      <h4>Unread Inquiries</h4>
      <div class="icon"><i class="fas fa-envelope"></i></div>
    </div>
    <div class="stat-body">
      <div class="stat-dual">
        <div class="stat-dual-row">
          <span class="stat-dual-num"><?= number_format($stats['messages']) ?></span>
        </div>
      </div>
    </div>
  </div>

</div>

<?php include 'includes/footer.php'; ?>