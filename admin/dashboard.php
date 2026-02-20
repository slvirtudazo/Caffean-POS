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

$row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM products WHERE status = 1"));
$stats['products'] = $row['total'];

$row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM orders"));
$stats['orders'] = $row['total'];

$row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM users WHERE role = 'customer'"));
$stats['customers'] = $row['total'];

$row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(total_amount), 0) AS revenue FROM orders WHERE status = 'completed'"));
$stats['revenue'] = $row['revenue'];

$row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total FROM orders WHERE status = 'pending'"));
$stats['pending'] = $row['total'];

include 'includes/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1>Dashboard</h1>
    <p>View an overview of key metrics, sales, and recent store activity</p>
  </div>
  <?php if ($stats['pending'] > 0): ?>
    <a href="orders.php" class="btn-primary">
      <i class="fas fa-bell"></i>
      <?= $stats['pending'] ?> Pending Order<?= $stats['pending'] > 1 ? 's' : '' ?>
    </a>
  <?php endif; ?>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <h4>Active Products</h4>
    <div class="number"><?= number_format($stats['products']) ?></div>
    <div class="icon"><i class="fas fa-mug-hot"></i></div>
  </div>
  <div class="stat-card">
    <h4>Total Orders</h4>
    <div class="number"><?= number_format($stats['orders']) ?></div>
    <div class="icon"><i class="fas fa-receipt"></i></div>
  </div>
  <div class="stat-card">
    <h4>Total Customers</h4>
    <div class="number"><?= number_format($stats['customers']) ?></div>
    <div class="icon"><i class="fas fa-users"></i></div>
  </div>
  <div class="stat-card">
    <h4>Total Revenue</h4>
    <div class="number">&#8369;<?= number_format($stats['revenue'], 2) ?></div>
    <div class="icon"><i class="fas fa-coins"></i></div>
  </div>
</div>

<?php
$recent_orders = mysqli_query($conn,
    "SELECT o.order_id, o.total_amount, o.status, o.order_date, u.full_name
     FROM orders o
     JOIN users u ON o.user_id = u.user_id
     ORDER BY o.order_date DESC LIMIT 5");
$order_count = mysqli_num_rows($recent_orders);
?>

<div class="card">
  <div class="card-header">
    <h2>Recent Orders</h2>
    <a href="orders.php" class="btn-outline">View All <i class="fas fa-arrow-right"></i></a>
  </div>

  <table class="admin-table" id="dashboardOrdersTable">
    <thead>
      <tr>
        <th data-sort="number">Order ID</th>
        <th data-sort="text">Customer</th>
        <th data-sort="number">Amount</th>
        <th data-sort="status">Status</th>
        <th data-sort="date">Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($order_count === 0): ?>
        <tr>
          <td colspan="5">
            <div class="empty-state">
              <i class="fas fa-inbox"></i>
              <p>No orders found</p>
            </div>
          </td>
        </tr>
      <?php else: ?>
        <?php while ($o = mysqli_fetch_assoc($recent_orders)): ?>
          <tr>
            <td class="td-id">#<?= $o['order_id'] ?></td>
            <td><?= htmlspecialchars($o['full_name']) ?></td>
            <td>&#8369;<?= number_format($o['total_amount'], 2) ?></td>
            <td>
              <span class="badge badge-<?= strtolower($o['status']) ?>">
                <?= ucfirst($o['status']) ?>
              </span>
            </td>
            <td><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
  initSortableTable('dashboardOrdersTable');
</script>

<?php include 'includes/footer.php'; ?>