<?php
/**
 * Purge Coffee Shop — Admin Customers Management
 * View all registered customers, their order history and stats.
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// ── Customers with order stats ────────────────────────────────
$customers_result = mysqli_query($conn,
    "SELECT u.*,
     COUNT(DISTINCT o.order_id)          AS total_orders,
     COALESCE(SUM(o.total_amount), 0)    AS total_spent
     FROM users u
     LEFT JOIN orders o ON u.user_id = o.user_id
     WHERE u.role = 'customer'
     GROUP BY u.user_id
     ORDER BY u.created_at DESC");

$total_customers = mysqli_num_rows($customers_result);

// ── Summary stats ─────────────────────────────────────────────
$summary = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
     COUNT(DISTINCT CASE WHEN o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                         THEN u.user_id END)                                    AS active_customers,
     COUNT(DISTINCT CASE WHEN o.order_id IS NULL THEN u.user_id END)           AS no_orders
     FROM users u
     LEFT JOIN orders o ON u.user_id = o.user_id
     WHERE u.role = 'customer'"));

include 'includes/header.php';
?>

<div class="page-header">
  <h1>Customers</h1>
  <p>Manage registered accounts and view activity</p>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <h4>Total Customers</h4>
    <div class="number"><?= number_format($total_customers) ?></div>
    <div class="icon"><i class="fas fa-users"></i></div>
  </div>
  <div class="stat-card">
    <h4>Active (30 Days)</h4>
    <div class="number"><?= number_format($summary['active_customers']) ?></div>
    <div class="icon"><i class="fas fa-user-check"></i></div>
  </div>
  <div class="stat-card">
    <h4>No Orders Yet</h4>
    <div class="number"><?= number_format($summary['no_orders']) ?></div>
    <div class="icon"><i class="fas fa-user-clock"></i></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2>All Customers</h2>
    <span style="color:var(--text-muted);font-size:.9rem;"><?= $total_customers ?> account(s)</span>
  </div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Registered</th>
          <th>Orders</th>
          <th>Total Spent</th>
          <th>Tier</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total_customers > 0): ?>
          <?php while ($c = mysqli_fetch_assoc($customers_result)): ?>
            <tr>
              <td>#<?= $c['user_id'] ?></td>
              <td><strong><?= htmlspecialchars($c['full_name']) ?></strong></td>
              <td><?= htmlspecialchars($c['email']) ?></td>
              <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
              <td><?= $c['total_orders'] ?></td>
              <td><strong>&#8369;<?= number_format($c['total_spent'], 2) ?></strong></td>
              <td>
                <?php if ($c['total_spent'] > 5000): ?>
                  <span class="badge badge-completed">VIP</span>
                <?php elseif ($c['total_orders'] > 0): ?>
                  <span class="badge badge-processing">Active</span>
                <?php else: ?>
                  <span class="badge badge-pending">New</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:40px; color:var(--text-muted);">
              No customers found.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>