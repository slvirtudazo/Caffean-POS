<?php
/**
 * Purge Coffee Shop — Admin Orders Management  (orders.php)
 * Operations: View (eye) + Update Status (pen).
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// ── Handle status update (PRG) ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = (int)$_POST['order_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $allowed    = ['pending', 'processing', 'completed', 'cancelled'];

    if (in_array($new_status, $allowed)) {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status=? WHERE order_id=?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
        $_SESSION['flash'] = mysqli_stmt_execute($stmt)
            ? ['type' => 'success', 'msg' => "Order #$order_id status updated to " . ucfirst($new_status) . "."]
            : ['type' => 'error',   'msg' => 'Error updating order status.'];
        mysqli_stmt_close($stmt);
    }

    $qs = isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '';
    header('Location: orders.php' . $qs);
    exit();
}

// ── Session Flash ─────────────────────────────────────────────
$flash   = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$message = ($flash && $flash['type'] === 'success') ? $flash['msg'] : '';
$error   = ($flash && $flash['type'] === 'error')   ? $flash['msg'] : '';

// ── Filter ────────────────────────────────────────────────────
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$where_clause  = "1=1";
if (in_array($status_filter, ['pending', 'processing', 'completed', 'cancelled'])) {
    $where_clause = "o.status = '$status_filter'";
}

$counts = ['all' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'cancelled' => 0];
$c_res  = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($r = mysqli_fetch_assoc($c_res)) {
    $counts[$r['status']] = $r['count'];
    $counts['all']       += $r['count'];
}

// ── Fetch orders into array ────────────────────────────────────
$orders_raw = mysqli_query($conn,
    "SELECT o.*, u.full_name, u.email,
     (SELECT SUM(quantity) FROM order_items WHERE order_id = o.order_id) AS total_items
     FROM orders o
     JOIN users u ON o.user_id = u.user_id
     WHERE $where_clause
     ORDER BY o.order_date DESC");

$total_orders = mysqli_num_rows($orders_raw);

$orders_map = [];
while ($o = mysqli_fetch_assoc($orders_raw)) {
    $o['items'] = [];
    $orders_map[$o['order_id']] = $o;
}

if (!empty($orders_map)) {
    $ids       = implode(',', array_map('intval', array_keys($orders_map)));
    $items_res = mysqli_query($conn,
        "SELECT oi.order_id, oi.quantity, oi.price, p.name AS product_name
         FROM order_items oi
         JOIN products p ON oi.product_id = p.product_id
         WHERE oi.order_id IN ($ids)");
    while ($item = mysqli_fetch_assoc($items_res)) {
        $orders_map[$item['order_id']]['items'][] = $item;
    }
}

include 'includes/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1>Orders</h1>
    <p>Track, process, and update the status of customer orders</p>
  </div>
</div>

<?php if ($message): ?>
  <div class="flash-success"><i class="fas fa-check-circle"></i><?= $message ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="flash-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div>
<?php endif; ?>

<div class="admin-tabs">
  <a href="?status=all"        class="tab <?= $status_filter === 'all'        ? 'active' : '' ?>">All Orders   <span class="tab-count"><?= $counts['all']        ?></span></a>
  <a href="?status=pending"    class="tab <?= $status_filter === 'pending'    ? 'active' : '' ?>">Pending      <span class="tab-count"><?= $counts['pending']    ?></span></a>
  <a href="?status=processing" class="tab <?= $status_filter === 'processing' ? 'active' : '' ?>">Processing   <span class="tab-count"><?= $counts['processing'] ?></span></a>
  <a href="?status=completed"  class="tab <?= $status_filter === 'completed'  ? 'active' : '' ?>">Completed    <span class="tab-count"><?= $counts['completed']  ?></span></a>
  <a href="?status=cancelled"  class="tab <?= $status_filter === 'cancelled'  ? 'active' : '' ?>">Cancelled    <span class="tab-count"><?= $counts['cancelled']  ?></span></a>
</div>

<div class="card">
  <div class="card-header">
    <h2><?= ucfirst($status_filter) ?> Orders</h2>
    <span class="card-count"><?= $total_orders ?> result<?= $total_orders !== 1 ? 's' : '' ?></span>
  </div>

  <table class="admin-table" id="ordersTable">
    <thead>
      <tr>
        <th data-sort="number">Order ID</th>
        <th data-sort="text">Customer</th>
        <th data-sort="date">Date</th>
        <th data-sort="number">Items</th>
        <th data-sort="number">Amount</th>
        <th data-sort="text">Payment</th>
        <th data-sort="status">Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($total_orders === 0): ?>
        <tr>
          <td colspan="8">
            <div class="empty-state">
              <i class="fas fa-inbox"></i>
              <p>No orders found</p>
            </div>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($orders_map as $o): ?>
          <tr>
            <td class="td-id">#<?= $o['order_id'] ?></td>
            <td><?= htmlspecialchars($o['full_name']) ?></td>
            <td><?= date('M d, Y h:i A', strtotime($o['order_date'])) ?></td>
            <td><?= $o['total_items'] ?? 0 ?></td>
            <td>&#8369;<?= number_format($o['total_amount'], 2) ?></td>
            <td><?= htmlspecialchars($o['payment_method']) ?></td>
            <td>
              <span class="badge badge-<?= strtolower($o['status']) ?>">
                <?= ucfirst($o['status']) ?>
              </span>
            </td>
            <td class="td-actions">
              <button class="btn-icon btn-icon-view" title="View Order Details"
                      onclick="viewOrder(<?= $o['order_id'] ?>)">
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn-icon btn-icon-update" title="Update Status"
                      onclick="openUpdateModal(<?= $o['order_id'] ?>, '<?= $o['status'] ?>')">
                <i class="fas fa-pen"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- ── View Order Modal ───────────────────────────────────── -->
<div class="modal-overlay" id="viewOrderModal" style="display:none;">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3><i class="fas fa-eye modal-icon"></i>Order Details</h3>
      <button class="modal-close" onclick="closeModal('viewOrderModal')">&#x2715;</button>
    </div>
    <div class="modal-body">
      <div class="view-detail-group">
        <span class="view-label">Order ID</span>
        <span class="view-value" id="vw_order_id"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Customer</span>
        <span class="view-value" id="vw_customer"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Email</span>
        <span class="view-value" id="vw_email"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Order Date</span>
        <span class="view-value" id="vw_date"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Payment</span>
        <span class="view-value" id="vw_payment"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Status</span>
        <span class="view-value" id="vw_status"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Total Amount</span>
        <span class="view-value" id="vw_total"></span>
      </div>

      <!-- Items section — margin classes replace former inline styles -->
      <div class="view-label view-modal-section-label">Ordered Items</div>
      <table class="admin-table" id="vw_items_table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody id="vw_items_body"></tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-cancel" onclick="closeModal('viewOrderModal')">Close</button>
    </div>
  </div>
</div>

<!-- ── Update Status Modal ───────────────────────────────── -->
<div class="modal-overlay" id="updateStatusModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-pen modal-icon"></i>Update Order Status</h3>
      <button class="modal-close" onclick="closeModal('updateStatusModal')">&#x2715;</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <p class="modal-subtitle">Change the status of <strong id="display_order_id"></strong></p>
        <input type="hidden" name="order_id" id="modal_order_id">
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" id="modal_status" class="form-control">
            <option value="pending">Pending</option>
            <option value="processing">Processing</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('updateStatusModal')">Cancel</button>
        <button type="submit" name="update_status" class="btn-update">
          <i class="fas fa-check"></i> Update Status
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  /* ── Orders data map from PHP ──────────────────────────────── */
  var ordersData = <?= json_encode(array_values($orders_map)) ?>;
  var ordersMap  = {};
  ordersData.forEach(function(o) { ordersMap[o.order_id] = o; });

  function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
  function closeModal(id) { document.getElementById(id).style.display = 'none'; }

  /* ── View Order ──────────────────────────────────────────────── */
  function viewOrder(orderId) {
    var o = ordersMap[orderId];
    if (!o) return;

    document.getElementById('vw_order_id').textContent = '#' + o.order_id;
    document.getElementById('vw_customer').textContent = o.full_name;
    document.getElementById('vw_email').textContent    = o.email;
    document.getElementById('vw_date').textContent     = o.order_date;
    document.getElementById('vw_payment').textContent  = o.payment_method;
    document.getElementById('vw_total').textContent    = '\u20B1' + parseFloat(o.total_amount).toFixed(2);
    document.getElementById('vw_status').innerHTML =
      '<span class="badge badge-' + o.status.toLowerCase() + '">' +
      o.status.charAt(0).toUpperCase() + o.status.slice(1) + '</span>';

    var tbody = document.getElementById('vw_items_body');
    tbody.innerHTML = '';
    if (o.items && o.items.length > 0) {
      o.items.forEach(function(item) {
        var sub = (parseFloat(item.price) * parseInt(item.quantity)).toFixed(2);
        tbody.innerHTML +=
          '<tr>' +
          '<td>' + item.product_name + '</td>' +
          '<td>' + item.quantity + '</td>' +
          '<td>\u20B1' + parseFloat(item.price).toFixed(2) + '</td>' +
          '<td>\u20B1' + sub + '</td>' +
          '</tr>';
      });
    } else {
      tbody.innerHTML = '<tr><td colspan="4" class="vw-no-items-cell">No items found</td></tr>';
    }

    openModal('viewOrderModal');
  }

  /* ── Update Status ───────────────────────────────────────────── */
  function openUpdateModal(orderId, currentStatus) {
    document.getElementById('modal_order_id').value       = orderId;
    document.getElementById('display_order_id').innerText = '#' + orderId;
    document.getElementById('modal_status').value         = currentStatus;
    openModal('updateStatusModal');
  }

  document.querySelectorAll('.modal-overlay').forEach(function(o) {
    o.addEventListener('click', function(e) { if (e.target === o) o.style.display = 'none'; });
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape')
      document.querySelectorAll('.modal-overlay').forEach(function(o) { o.style.display = 'none'; });
  });

  /* ── Sorting ─────────────────────────────────────────────────── */
  initSortableTable('ordersTable');
</script>

<?php include 'includes/footer.php'; ?>