<?php
/**
 * Caffean — Admin Insights (admin/insights.php)
 * Revenue trends, top products, order type and payment breakdowns.
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// ── Revenue by month — last 6 months ──────────────────────────
$spend_labels = [];
$spend_data   = [];
for ($i = 5; $i >= 0; $i--) {
    $y = date('Y', strtotime("-$i months"));
    $m = date('m', strtotime("-$i months"));
    $spend_labels[] = date('M Y', strtotime("-$i months"));
    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(total_amount),0) AS s FROM orders
         WHERE YEAR(order_date)=$y AND MONTH(order_date)=$m
         AND ((is_kiosk = 1 AND status IN ('processing','completed'))
           OR (COALESCE(is_kiosk,0) = 0 AND payment_method = 'Cash on Delivery' AND status = 'completed')
           OR (COALESCE(is_kiosk,0) = 0 AND payment_method != 'Cash on Delivery' AND status IN ('processing','completed')))"
    ));
    $spend_data[] = (float)($row['s'] ?? 0);
}

// ── Top 5 products by quantity sold ───────────────────────────
$top_res = mysqli_query($conn,
    "SELECT p.name, SUM(oi.quantity) AS qty
     FROM order_items oi
     JOIN products p ON oi.product_id = p.product_id
     GROUP BY oi.product_id ORDER BY qty DESC LIMIT 5"
);
$top_items = mysqli_fetch_all($top_res, MYSQLI_ASSOC);

// ── Order type breakdown ───────────────────────────────────────
$type_rows = mysqli_fetch_all(
    mysqli_query($conn, "SELECT order_type, COUNT(*) AS cnt FROM orders GROUP BY order_type"),
    MYSQLI_ASSOC
);
$type_data = [];
foreach ($type_rows as $r) $type_data[ucfirst($r['order_type'])] = (int)$r['cnt'];

// ── Payment method breakdown ───────────────────────────────────
$pay_rows = mysqli_fetch_all(
    mysqli_query($conn, "SELECT payment_method, COUNT(*) AS cnt FROM orders GROUP BY payment_method"),
    MYSQLI_ASSOC
);
$pay_data = [];
foreach ($pay_rows as $r) $pay_data[$r['payment_method']] = (int)$r['cnt'];

// ── Summary stats ──────────────────────────────────────────────
// Summary: kiosk (all) → processing/completed; online COD → completed; online card → processing/completed
$summary = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total_orders,
            COALESCE(SUM(CASE
              WHEN is_kiosk = 1 AND status IN ('processing','completed') THEN total_amount
              WHEN COALESCE(is_kiosk,0) = 0 AND payment_method = 'Cash on Delivery' AND status = 'completed' THEN total_amount
              WHEN COALESCE(is_kiosk,0) = 0 AND payment_method != 'Cash on Delivery' AND status IN ('processing','completed') THEN total_amount
              ELSE 0 END), 0) AS total_revenue,
            COUNT(CASE
              WHEN is_kiosk = 1 AND status IN ('processing','completed') THEN 1
              WHEN COALESCE(is_kiosk,0) = 0 AND payment_method = 'Cash on Delivery' AND status = 'completed' THEN 1
              WHEN COALESCE(is_kiosk,0) = 0 AND payment_method != 'Cash on Delivery' AND status IN ('processing','completed') THEN 1
              END) AS qualifying_orders,
            COUNT(DISTINCT user_id) AS unique_customers
     FROM orders"
));
$avg_order = $summary['qualifying_orders'] > 0
    ? round($summary['total_revenue'] / $summary['qualifying_orders'], 2)
    : 0;

require 'includes/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1>Insights</h1>
    <p>Store analytics and performance overview</p>
  </div>
</div>

<!-- Insights body -->
<div class="ins-outer-frame">
<div class="ins-body">

  <!-- Row 1: Revenue chart + Summary -->
  <div class="ins-row ins-row--chart">

    <div class="ins-panel ins-panel--chart">
      <div class="ins-panel-header">
        <span class="ins-panel-title">Monthly Revenue</span>
        <span class="ins-panel-sub">Last 6 months</span>
      </div>
      <div class="ins-chart-wrap">
        <canvas id="revenueChart"></canvas>
      </div>
    </div>

    <div class="ins-panel ins-panel--summary">
      <div class="ins-panel-header">
        <span class="ins-panel-title">Summary</span>
      </div>
      <div class="ins-summary-list">
        <div class="ins-summary-item">
          <span class="ins-summary-lbl">Total Orders</span>
          <span class="ins-summary-val"><?= number_format($summary['total_orders']) ?></span>
        </div>
        <div class="ins-summary-item">
          <span class="ins-summary-lbl">Total Revenue</span>
          <span class="ins-summary-val">&#8369;<?= number_format($summary['total_revenue'], 0) ?></span>
        </div>
        <div class="ins-summary-item">
          <span class="ins-summary-lbl">Avg. Order Value</span>
          <span class="ins-summary-val">&#8369;<?= number_format($avg_order, 0) ?></span>
        </div>
        <div class="ins-summary-item">
          <span class="ins-summary-lbl">Unique Customers</span>
          <span class="ins-summary-val"><?= number_format($summary['unique_customers']) ?></span>
        </div>
      </div>
    </div>

  </div>

  <!-- Row 2: Top items + Order type donut + Payment donut -->
  <div class="ins-row ins-row--bottom">

    <div class="ins-panel ins-panel--top-items">
      <div class="ins-panel-header">
        <span class="ins-panel-title">Top Ordered Items</span>
        <span class="ins-panel-sub">By quantity</span>
      </div>
      <?php if (empty($top_items)): ?>
        <p class="ins-summary-lbl" style="padding:16px 0;">No order data yet.</p>
      <?php else: ?>
        <ul class="ins-top-list">
          <?php foreach ($top_items as $i => $item): ?>
            <li class="ins-top-item">
              <span class="ins-top-rank"><?= $i + 1 ?></span>
              <span class="ins-top-name"><?= htmlspecialchars($item['name']) ?></span>
              <span class="ins-top-qty"><?= number_format($item['qty']) ?> sold</span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="ins-panel ins-panel--donut">
      <div class="ins-panel-header">
        <span class="ins-panel-title">Order Types</span>
      </div>
      <div class="ins-donut-wrap">
        <canvas id="orderTypeChart"></canvas>
      </div>
      <ul class="ins-legend">
        <?php
        $type_color_map = ['Delivery'=>'#5B1312','Pickup'=>'#b07830','Dine In'=>'#3a7a5b','Take Out'=>'#b07830','Kiosk'=>'#3a7a5b'];
        $fallback = ['#5B1312','#b07830','#3a7a5b','#1a6ea8'];
        $ci = 0;
        foreach ($type_data as $lbl => $val): ?>
          <li class="ins-legend-item">
            <span class="ins-legend-dot" style="background:<?= $type_color_map[$lbl] ?? $fallback[$ci % 4] ?>"></span>
            <span class="ins-legend-lbl"><?= htmlspecialchars($lbl) ?></span>
            <span class="ins-legend-val"><?= $val ?></span>
          </li>
        <?php $ci++; endforeach; ?>
      </ul>
    </div>

    <div class="ins-panel ins-panel--donut">
      <div class="ins-panel-header">
        <span class="ins-panel-title">Payment Methods</span>
      </div>
      <div class="ins-donut-wrap">
        <canvas id="paymentChart"></canvas>
      </div>
      <ul class="ins-legend">
        <?php
        $pay_color_map = ['Cash'=>'#5B1312','Cash on Delivery'=>'#b07830','GCash'=>'#0057DA','Gcash'=>'#0057DA','Maya'=>'#44B655','PayMaya'=>'#44B655','Card'=>'#1a6ea8'];
        $fallback2 = ['#5B1312','#b07830','#3a7a5b','#1a6ea8','#7a3a7a'];
        $ci = 0;
        foreach ($pay_data as $lbl => $val): ?>
          <li class="ins-legend-item">
            <span class="ins-legend-dot" style="background:<?= $pay_color_map[$lbl] ?? $fallback2[$ci % 5] ?>"></span>
            <span class="ins-legend-lbl"><?= htmlspecialchars($lbl) ?></span>
            <span class="ins-legend-val"><?= $val ?></span>
          </li>
        <?php $ci++; endforeach; ?>
      </ul>
    </div>

  </div>
</div><!-- /.ins-body -->
</div><!-- /.ins-outer-frame -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
  const brandColor  = '#5B1312';
  const chartFont   = { family: "'Outfit', sans-serif", size: 11 };

  /* Revenue line chart */
  const revCtx = document.getElementById('revenueChart')?.getContext('2d');
  if (revCtx) {
    new Chart(revCtx, {
      type: 'line',
      data: {
        labels:   <?= json_encode($spend_labels) ?>,
        datasets: [{
          data:            <?= json_encode($spend_data) ?>,
          borderColor:     brandColor,
          backgroundColor: 'rgba(91,19,18,0.08)',
          borderWidth: 2,
          pointRadius: 4,
          pointBackgroundColor: brandColor,
          fill: true,
          tension: 0.35
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: {
          label: ctx => '₱' + ctx.parsed.y.toLocaleString()
        }}},
        scales: {
          x: { ticks: { font: chartFont, color: '#7a6a5a' }, grid: { display: false } },
          y: { ticks: { font: chartFont, color: '#7a6a5a', callback: v => '₱' + v.toLocaleString() },
               grid: { color: 'rgba(42,0,0,0.05)' } }
        }
      }
    });
  }

  /* Brand-accurate color map for payment methods and order types */
  const colorMap = {
    'Cash':             '#5B1312',
    'Cash on Delivery': '#b07830',
    'Gcash':            '#0057DA',
    'GCash':            '#0057DA',
    'Maya':             '#44B655',
    'PayMaya':          '#44B655',
    'Card':             '#1a6ea8',
    'Delivery':         '#5B1312',
    'Pickup':           '#b07830',
    'Dine In':          '#3a7a5b',
    'Take Out':         '#b07830',
    'Kiosk':            '#3a7a5b',
  };
  const fallbackColors = ['#5B1312','#b07830','#3a7a5b','#1a6ea8','#7a3a7a'];

  function getColors(labels) {
    return labels.map((lbl, i) => colorMap[lbl] || fallbackColors[i % fallbackColors.length]);
  }

  /* Doughnut helper */
  function makeDoughnut(id, labels, data) {
    const ctx = document.getElementById(id)?.getContext('2d');
    if (!ctx || !labels.length) return;
    new Chart(ctx, {
      type: 'doughnut',
      data: { labels, datasets: [{ data, backgroundColor: getColors(labels), borderWidth: 0, hoverOffset: 4 }] },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        cutout: '65%'
      }
    });
  }

  makeDoughnut('orderTypeChart',
    <?= json_encode(array_keys($type_data)) ?>,
    <?= json_encode(array_values($type_data)) ?>
  );

  makeDoughnut('paymentChart',
    <?= json_encode(array_keys($pay_data)) ?>,
    <?= json_encode(array_values($pay_data)) ?>
  );
</script>

<?php require 'includes/footer.php'; ?>