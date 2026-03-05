<?php

/**
 * Purge Coffee Shop — Admin Customers Management  (customers.php)
 * Operation: View only (eye icon).
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../login.php');
  exit();
}

// Formats an integer ID into a prefixed display string
function fmt_id($prefix, $id, $date_str = null)
{
  $year = $date_str ? date('Y', strtotime($date_str)) : date('Y');
  return $prefix . '-' . $year . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
}

// ── Customers with order stats ────────────────────────────────
$customers_result = mysqli_query(
  $conn,
  "SELECT u.*,
     COUNT(DISTINCT o.order_id)       AS total_orders,
     COALESCE(SUM(o.total_amount), 0) AS total_spent
     FROM users u
     LEFT JOIN orders o ON u.user_id = o.user_id
     WHERE u.role = 'customer'
     GROUP BY u.user_id
     ORDER BY u.created_at DESC"
);

$total_customers = mysqli_num_rows($customers_result);

// ── Summary stats ─────────────────────────────────────────────
$summary = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT
     COUNT(DISTINCT CASE WHEN o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                         THEN u.user_id END)               AS active_customers,
     COUNT(DISTINCT CASE WHEN o.order_id IS NULL
                         THEN u.user_id END)               AS no_orders
     FROM users u
     LEFT JOIN orders o ON u.user_id = o.user_id
     WHERE u.role = 'customer'"
));

// Load all customers into array for JS map
$customers_arr = [];
mysqli_data_seek($customers_result, 0);
while ($c = mysqli_fetch_assoc($customers_result)) {
  $c['fmt_id'] = fmt_id('CS', $c['user_id'], $c['created_at']);
  $customers_arr[] = $c;
}

include 'includes/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1>Customers</h1>
    <p>View and manage registered user accounts and details</p>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2>All Customers</h2>
    <span class="card-count"><?= $total_customers ?> account<?= $total_customers !== 1 ? 's' : '' ?></span>
  </div>

  <table class="admin-table" id="customersTable">
    <thead>
      <tr>
        <th data-sort="number">ID</th>
        <th data-sort="text">Name</th>
        <th data-sort="text">Email</th>
        <th data-sort="date">Registered</th>
        <th data-sort="number">Orders</th>
        <th data-sort="number">Total Spent</th>
        <th data-sort="status">Tier</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($total_customers === 0): ?>
        <tr>
          <td colspan="8">
            <div class="empty-state">
              <i class="fas fa-users"></i>
              <p>No customers found.</p>
            </div>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($customers_arr as $c): ?>
          <tr>
            <td class="td-id"><?= fmt_id('CS', $c['user_id'], $c['created_at']) ?></td>
            <td><?= htmlspecialchars($c['full_name']) ?></td>
            <td><?= htmlspecialchars($c['email']) ?></td>
            <td><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
            <td><?= $c['total_orders'] ?></td>
            <td>&#8369;<?= number_format($c['total_spent'], 2) ?></td>
            <td>
              <?php if ($c['total_spent'] > 5000): ?>
                <span class="badge badge-completed">VIP</span>
              <?php elseif ($c['total_orders'] > 0): ?>
                <span class="badge badge-processing">Active</span>
              <?php else: ?>
                <span class="badge badge-pending">New</span>
              <?php endif; ?>
            </td>
            <td class="td-actions">
              <button class="btn-icon btn-icon-view" title="View Customer"
                onclick="viewCustomer(<?= $c['user_id'] ?>)">
                <i class="fas fa-eye"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- ══ VIEW CUSTOMER MODAL ═══════════════════════════════════ -->
<div class="modal-overlay" id="viewCustomerModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h3>Customer Details</h3>
      <button class="modal-close" onclick="closeModal('viewCustomerModal')">&#x2715;</button>
    </div>
    <div class="modal-body">
      <div class="view-detail-group">
        <span class="view-label">Customer ID</span>
        <span class="view-value" id="vc_id"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Full Name</span>
        <span class="view-value" id="vc_name"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Email</span>
        <span class="view-value" id="vc_email"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Registered</span>
        <span class="view-value" id="vc_date"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Total Orders</span>
        <span class="view-value" id="vc_orders"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Total Spent</span>
        <span class="view-value" id="vc_spent"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Tier</span>
        <span class="view-value" id="vc_tier"></span>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-cancel" onclick="closeModal('viewCustomerModal')">Close</button>
    </div>
  </div>
</div>

<script>
  /* ── Customers data map from PHP ─────────────────────────── */
  var customersData = <?= json_encode($customers_arr) ?>;
  var customersMap = {};
  customersData.forEach(function(c) {
    customersMap[c.user_id] = c;
  });

  function openModal(id) {
    document.getElementById(id).style.display = 'flex';
  }

  function closeModal(id) {
    document.getElementById(id).style.display = 'none';
  }

  function viewCustomer(userId) {
    var c = customersMap[userId];
    if (!c) return;

    document.getElementById('vc_id').textContent = c.fmt_id;
    document.getElementById('vc_name').textContent = c.full_name;
    document.getElementById('vc_email').textContent = c.email;
    document.getElementById('vc_date').textContent = c.created_at ? c.created_at.split(' ')[0] : '—';
    document.getElementById('vc_orders').textContent = c.total_orders;
    document.getElementById('vc_spent').textContent = '\u20B1' + parseFloat(c.total_spent).toFixed(2);

    var tier = 'New',
      tierClass = 'badge-pending';
    if (parseFloat(c.total_spent) > 5000) {
      tier = 'VIP';
      tierClass = 'badge-completed';
    } else if (parseInt(c.total_orders) > 0) {
      tier = 'Active';
      tierClass = 'badge-processing';
    }
    document.getElementById('vc_tier').innerHTML =
      '<span class="badge ' + tierClass + '">' + tier + '</span>';

    openModal('viewCustomerModal');
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) overlay.style.display = 'none';
    });
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape')
      document.querySelectorAll('.modal-overlay').forEach(function(o) {
        o.style.display = 'none';
      });
  });

  /* ── Sorting ─────────────────────────────────────────────── */
  initSortableTable('customersTable');
</script>

<?php include 'includes/footer.php'; ?>