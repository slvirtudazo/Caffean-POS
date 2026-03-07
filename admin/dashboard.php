<?php

/**
 * Purge Coffee Shop — Admin Dashboard (admin/dashboard.php)
 * Sales analytics, trending products, and recent orders overview.
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// ── Stat cards ────────────────────────────────────────────────
$stats = [];

$stats['products_active']   = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE status = 1"))['c'];
$stats['products_inactive'] = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE status = 0"))['c'];
$stats['orders']            = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders"))['c'];

// Active customers = have at least one order
$stats['customers_active'] = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(DISTINCT u.user_id) AS c FROM users u
     JOIN orders o ON u.user_id = o.user_id WHERE u.role = 'customer'"
))['c'];

// Inactive customers = registered but no orders
$stats['customers_inactive'] = (int)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM users u
     LEFT JOIN orders o ON u.user_id = o.user_id
     WHERE u.role = 'customer' AND o.order_id IS NULL"
))['c'];

$stats['customers_total'] = $stats['customers_active'] + $stats['customers_inactive'];

// Total revenue: kiosk (all) → processing/completed; online COD → completed; online card → processing/completed
$stats['revenue'] = (float)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(total_amount), 0) AS r FROM orders WHERE
     (is_kiosk = 1 AND status IN ('processing','completed'))
     OR (COALESCE(is_kiosk,0) = 0 AND payment_method = 'Cash on Delivery' AND status = 'completed')
     OR (COALESCE(is_kiosk,0) = 0 AND payment_method != 'Cash on Delivery' AND status IN ('processing','completed'))"
))['r'];

// ── Revenue chart: last 7 days ────────────────────────────────
$chart_labels = [];
$chart_data   = [];
for ($i = 6; $i >= 0; $i--) {
    $date           = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('M j', strtotime("-$i days"));
    $row            = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(total_amount), 0) AS rev FROM orders
         WHERE DATE(order_date) = '$date'
         AND ((is_kiosk = 1 AND status IN ('processing','completed'))
           OR (COALESCE(is_kiosk,0) = 0 AND payment_method = 'Cash on Delivery' AND status = 'completed')
           OR (COALESCE(is_kiosk,0) = 0 AND payment_method != 'Cash on Delivery' AND status IN ('processing','completed')))"
    ));
    $chart_data[] = (float)$row['rev'];
}

// ── Trending products: top 5 by units sold ────────────────────
$trending_res = mysqli_query($conn,
    "SELECT p.name, p.price, p.image_path, SUM(oi.quantity) AS total_qty
     FROM order_items oi
     JOIN products p ON oi.product_id = p.product_id
     GROUP BY oi.product_id
     ORDER BY total_qty DESC
     LIMIT 5"
);
$trending = [];
while ($t = mysqli_fetch_assoc($trending_res)) $trending[] = $t;

// Fallback: show newest active products if no order data yet
if (empty($trending)) {
    $fb = mysqli_query($conn, "SELECT name, price, image_path, 0 AS total_qty FROM products WHERE status = 1 ORDER BY product_id DESC LIMIT 5");
    while ($t = mysqli_fetch_assoc($fb)) $trending[] = $t;
}

// ── Recent orders: last 6 (includes kiosk + online) ──────────
// Check if order_number column exists (may be missing on older installs)
$has_order_num = (bool)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'order_number'"
));

$order_num_select = $has_order_num
    ? "COALESCE(NULLIF(o.order_number,''), CONCAT('PC-', YEAR(o.order_date), '-', LPAD(o.order_id,5,'0')))"
    : "CONCAT('PC-', YEAR(o.order_date), '-', LPAD(o.order_id,5,'0'))";

$recent_res = mysqli_query($conn,
    "SELECT o.order_id,
            $order_num_select AS order_number,
            o.total_amount, o.status, o.payment_method,
            o.order_date, o.order_type, o.is_kiosk,
            COALESCE(u.full_name, o.customer_name, 'Guest') AS customer_name
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.user_id
     ORDER BY o.order_date DESC
     LIMIT 6"
);
$recent_orders = [];
while ($ro = mysqli_fetch_assoc($recent_res)) $recent_orders[] = $ro;

// ── Month-over-month deltas ───────────────────────────────────
$this_month = date('Y-m');
$last_month = date('Y-m', strtotime('first day of last month'));

$tm_orders = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE DATE_FORMAT(order_date,'%Y-%m')='$this_month'"))['c'];
$lm_orders = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE DATE_FORMAT(order_date,'%Y-%m')='$last_month'"))['c'];
$orders_delta = $lm_orders > 0 ? round((($tm_orders - $lm_orders) / $lm_orders) * 100, 1) : ($tm_orders > 0 ? 100 : 0);

$tm_revenue = (float)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(total_amount),0) AS r FROM orders
     WHERE DATE_FORMAT(order_date,'%Y-%m')='$this_month'
     AND ((is_kiosk = 1 AND status IN ('processing','completed'))
       OR (COALESCE(is_kiosk,0) = 0 AND payment_method = 'Cash on Delivery' AND status = 'completed')
       OR (COALESCE(is_kiosk,0) = 0 AND payment_method != 'Cash on Delivery' AND status IN ('processing','completed')))"))['r'];
$lm_revenue = (float)mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(total_amount),0) AS r FROM orders
     WHERE DATE_FORMAT(order_date,'%Y-%m')='$last_month'
     AND ((is_kiosk = 1 AND status IN ('processing','completed'))
       OR (COALESCE(is_kiosk,0) = 0 AND payment_method = 'Cash on Delivery' AND status = 'completed')
       OR (COALESCE(is_kiosk,0) = 0 AND payment_method != 'Cash on Delivery' AND status IN ('processing','completed')))"))['r'];
$revenue_delta = $lm_revenue > 0 ? round((($tm_revenue - $lm_revenue) / $lm_revenue) * 100, 1) : ($tm_revenue > 0 ? 100 : 0);

$tm_customers = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='customer' AND DATE_FORMAT(created_at,'%Y-%m')='$this_month'"))['c'];
$lm_customers = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='customer' AND DATE_FORMAT(created_at,'%Y-%m')='$last_month'"))['c'];
$customers_delta = $lm_customers > 0 ? round((($tm_customers - $lm_customers) / $lm_customers) * 100, 1) : ($tm_customers > 0 ? 100 : 0);

include 'includes/header.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="page-header">
  <div class="page-header-text">
    <h1>Dashboard</h1>
    <p>Overview of key metrics, sales analytics, and recent activity</p>
  </div>
</div>

<!-- All dashboard content in one outer frame -->
<div class="ins-outer-frame">

<!-- Stat cards -->
<div class="stat-grid">

  <div class="stat-card stat-card--revenue">
    <div class="stat-card-top">
      <div class="stat-card-icon"><i class="fas fa-coins"></i></div>
      <span class="stat-card-label">Total Revenue</span>
    </div>
    <div class="stat-card-body">
      <span class="stat-card-value">&#8369;<?= number_format($stats['revenue'], 2) ?></span>
    </div>
  </div>

  <div class="stat-card stat-card--orders">
    <div class="stat-card-top">
      <div class="stat-card-icon"><i class="fas fa-receipt"></i></div>
      <span class="stat-card-label">Total Orders</span>
    </div>
    <div class="stat-card-body">
      <span class="stat-card-value"><?= number_format($stats['orders']) ?></span>
    </div>
  </div>

  <div class="stat-card stat-card--customers">
    <div class="stat-card-top">
      <div class="stat-card-icon"><i class="fas fa-users"></i></div>
      <span class="stat-card-label">Registered Customers</span>
    </div>
    <div class="stat-card-body">
      <div class="stat-active-inactive">
        <div class="stat-ai-row">
          <span class="stat-ai-num"><?= number_format($stats['customers_active']) ?></span>
          <span class="stat-ai-label">Active</span>
        </div>
        <div class="stat-ai-row">
          <span class="stat-ai-num inactive"><?= number_format($stats['customers_inactive']) ?></span>
          <span class="stat-ai-label">Inactive</span>
        </div>
      </div>
    </div>
  </div>

  <div class="stat-card stat-card--inventory">
    <div class="stat-card-top">
      <div class="stat-card-icon"><i class="fas fa-shopping-cart"></i></div>
      <span class="stat-card-label">Inventory Status</span>
    </div>
    <div class="stat-card-body">
      <div class="stat-active-inactive">
        <div class="stat-ai-row">
          <span class="stat-ai-num"><?= number_format($stats['products_active']) ?></span>
          <span class="stat-ai-label">Active</span>
        </div>
        <div class="stat-ai-row">
          <span class="stat-ai-num inactive"><?= number_format($stats['products_inactive']) ?></span>
          <span class="stat-ai-label">Hidden</span>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Analytics + Trending grid, Recent orders -->
<div class="dash-analytics-grid">

  <div class="dash-panel dash-panel--chart">
    <div class="dash-panel-header">
      <h3 class="dash-panel-title">Sales Analytics</h3>
      <span class="dash-panel-sub">Last 7 days revenue</span>
      <a href="insights.php" class="dash-see-all">View All &rarr;</a>
    </div>
    <div class="dash-chart-wrap">
      <canvas id="salesChart"></canvas>
    </div>
  </div>

  <div class="dash-panel dash-panel--trending">
    <div class="dash-panel-header">
      <h3 class="dash-panel-title">Trending Products</h3>
      <a href="products.php" class="dash-see-all">View All &rarr;</a>
    </div>
    <ul class="trending-list">
      <?php if (empty($trending)): ?>
        <li class="trending-empty">No product data yet.</li>
      <?php else: ?>
        <?php foreach ($trending as $i => $item): ?>
          <li class="trending-item">
            <div class="trending-rank"><?= $i + 1 ?></div>
            <div class="trending-info">
              <span class="trending-name"><?= htmlspecialchars($item['name']) ?></span>
              <span class="trending-price">&#8369;<?= number_format($item['price'], 2) ?></span>
            </div>
            <div class="trending-qty">
              <?= $item['total_qty'] > 0 ? number_format($item['total_qty']) . ' sold' : 'In stock' ?>
            </div>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
  </div>

</div>

<!-- Recent orders (online + in-store) -->
<div class="dash-panel dash-panel--orders">
  <div class="dash-panel-header">
    <h3 class="dash-panel-title">Recent Orders</h3>
    <a href="orders.php" class="dash-see-all">View All &rarr;</a>
  </div>
  <?php if (empty($recent_orders)): ?>
    <div class="dash-empty-state">
      <i class="fas fa-receipt"></i>
      <p>No orders yet. They'll appear here once customers start ordering.</p>
    </div>
  <?php else: ?>
    <div class="dash-table-wrap">
      <table class="dash-orders-table">
        <thead>
          <tr>
            <th class="th-order-no">Order No.</th>
            <th>Customer</th>
            <th>Date &amp; Time</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_orders as $ro): ?>
            <tr>
              <td class="td-id"><?= htmlspecialchars($ro['order_number'] ?? '#' . $ro['order_id']) ?></td>
              <td><?= htmlspecialchars($ro['customer_name']) ?></td>
              <td><?= date('M j, Y · g:i A', strtotime($ro['order_date'])) ?></td>
              <td>
                <?php if ($ro['is_kiosk']): ?>
                  <span class="badge-type badge-pickup">Kiosk</span>
                <?php else: ?>
                  <span class="badge-type badge-<?= $ro['order_type'] ?>">
                    <?= ucfirst($ro['order_type']) ?>
                  </span>
                <?php endif; ?>
              </td>
              <td class="td-amount">&#8369;<?= number_format($ro['total_amount'], 2) ?></td>
              <td><?= htmlspecialchars($ro['payment_method']) ?></td>
              <td>
                <span class="status-pill status-<?= $ro['status'] ?>">
                  <?= ucfirst($ro['status']) ?>
                </span>
              </td>
              <td>
                <a href="<?= $ro['is_kiosk'] ? 'instore_orders.php' : 'orders.php' ?>"
                   class="btn-icon-sm" title="View Order">
                  <i class="fas fa-arrow-right"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

</div><!-- /.ins-outer-frame -->

<script>
  (function () {
    const labels = <?= json_encode($chart_labels) ?>;
    const data   = <?= json_encode($chart_data) ?>;
    const ctx    = document.getElementById('salesChart').getContext('2d');

    const grad = ctx.createLinearGradient(0, 0, 0, 280);
    grad.addColorStop(0, 'rgba(91,19,18,0.18)');
    grad.addColorStop(1, 'rgba(91,19,18,0.0)');

    new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Revenue (₱)',
          data,
          borderColor: '#5B1312',
          borderWidth: 2.5,
          pointBackgroundColor: '#5B1312',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 7,
          fill: true,
          backgroundColor: grad,
          tension: 0.42,
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
            ticks: { color: '#7a6a5a', font: { family: 'Outfit', size: 12 } }
          },
          y: {
            grid: { color: 'rgba(42,0,0,0.06)' },
            border: { display: false, dash: [4, 4] },
            ticks: {
              color: '#7a6a5a',
              font: { family: 'Outfit', size: 12 },
              callback: v => '₱' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v)
            }
          }
        }
      }
    });
  })();
</script>

<?php include 'includes/footer.php'; ?>