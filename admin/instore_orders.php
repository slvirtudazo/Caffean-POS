<?php

/**
 * Caffean Shop — Admin In-Store Orders (admin/instore_orders.php)
 * Manages walk-in kiosk orders (is_kiosk = 1).
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle status update (PRG pattern)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = (int)$_POST['order_id'];
    $new_status = trim($_POST['status'] ?? '');
    $allowed    = ['pending', 'processing', 'completed', 'cancelled'];

    if (in_array($new_status, $allowed)) {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status=? WHERE order_id=? AND is_kiosk=1");
        mysqli_stmt_bind_param($stmt, 'si', $new_status, $order_id);
        $_SESSION['flash'] = mysqli_stmt_execute($stmt)
            ? ['type' => 'success', 'msg' => "Order #$order_id updated to " . ucfirst($new_status) . "."]
            : ['type' => 'error',   'msg' => 'Error updating order status.'];
        mysqli_stmt_close($stmt);
    }

    $qs = isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : '';
    header('Location: instore_orders.php' . $qs);
    exit();
}

// Session flash
$flash   = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$message = ($flash && $flash['type'] === 'success') ? $flash['msg'] : '';
$error   = ($flash && $flash['type'] === 'error')   ? $flash['msg'] : '';

// Status filter
$status_filter = $_GET['status'] ?? 'all';
$base_where    = "o.is_kiosk = 1";
$where_clause  = $base_where;
if (in_array($status_filter, ['pending', 'processing', 'completed', 'cancelled'])) {
    $where_clause = "$base_where AND o.status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

// Status counts
$counts = ['all' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'cancelled' => 0];
$c_res  = mysqli_query($conn, "SELECT status, COUNT(*) AS count FROM orders WHERE is_kiosk = 1 GROUP BY status");
while ($r = mysqli_fetch_assoc($c_res)) {
    $counts[$r['status']] = $r['count'];
    $counts['all']       += $r['count'];
}

// Fetch kiosk orders
$orders_raw = mysqli_query($conn,
    "SELECT o.order_id, o.order_number, o.order_date, o.total_amount, o.status,
            o.payment_method, o.kiosk_order_type, o.mobile_number,
            COALESCE(o.customer_name, 'Guest') AS customer_name,
            (SELECT SUM(quantity) FROM order_items WHERE order_id = o.order_id) AS total_items
     FROM orders o
     WHERE $where_clause
     ORDER BY o.order_date DESC"
);
$total_orders = mysqli_num_rows($orders_raw);

$orders_map = [];
while ($o = mysqli_fetch_assoc($orders_raw)) {
    $o['items']            = [];
    $orders_map[$o['order_id']] = $o;
}

// Attach order items
if (!empty($orders_map)) {
    $ids       = implode(',', array_map('intval', array_keys($orders_map)));
    $items_res = mysqli_query($conn,
        "SELECT oi.order_id, oi.quantity, oi.price_at_time AS price,
                p.name AS product_name, oi.size, oi.temperature, oi.sugar_level
         FROM order_items oi
         JOIN products p ON oi.product_id = p.product_id
         WHERE oi.order_id IN ($ids)"
    );
    while ($item = mysqli_fetch_assoc($items_res)) {
        $orders_map[$item['order_id']]['items'][] = $item;
    }
}

include 'includes/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1>In-Store Orders</h1>
    <p>Manage and process walk-in kiosk orders from the self-order terminal</p>
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

<div class="toolbar">
  <div class="search-box">
    <span class="search-icon"><i class="fas fa-search"></i></span>
    <input type="text" id="instoreSearch" placeholder="Search orders..." />
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2><?= $status_filter === 'all' ? 'All' : ucfirst($status_filter) ?> In-Store Orders</h2>
    <span class="card-count"><?= $total_orders ?> result<?= $total_orders !== 1 ? 's' : '' ?></span>
  </div>

  <table class="admin-table" id="instoreTable">
    <thead>
      <tr>
        <th data-sort="text">Order No.</th>
        <th data-sort="text">Customer</th>
        <th data-sort="text">Type</th>
        <th data-sort="date">Date &amp; Time</th>
        <th data-sort="number">Items</th>
        <th data-sort="number">Amount</th>
        <th data-sort="text">Payment</th>
        <th data-sort="status">Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($total_orders === 0): ?>
        <tr class="empty-row">
          <td colspan="9">
            <div class="empty-state">
              <i class="fas fa-cash-register"></i>
              <p>No in-store orders found</p>
            </div>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($orders_map as $o): ?>
          <tr>
            <td class="td-id"><?= htmlspecialchars($o['order_number'] ?? '#' . $o['order_id']) ?></td>
            <td><?= htmlspecialchars($o['customer_name']) ?></td>
            <td><?= $o['kiosk_order_type'] === 'dine_in' ? 'Dine In' : 'Take Out' ?></td>
            <td><?= date('M d, Y · H:i:s', strtotime($o['order_date'])) ?></td>
            <td><?= $o['total_items'] ?? 0 ?></td>
            <td class="td-amount">&#8369;<?= number_format($o['total_amount'], 2) ?></td>
            <td><?= htmlspecialchars($o['payment_method']) ?></td>
            <td>
              <span class="badge badge-<?= strtolower($o['status']) ?>">
                <?= ucfirst($o['status']) ?>
              </span>
            </td>
            <td class="td-actions">
              <button class="btn-icon btn-icon-view" title="View Order"
                onclick="viewInstoreOrder(<?= $o['order_id'] ?>)">
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
  <div id="instoreTable-pagination" class="admin-pagination">
    <span class="page-info">Page 1 of 1</span>
    <div class="page-btns">
      <button class="btn-page btn-prev"><i class="fas fa-chevron-left"></i></button>
      <button class="btn-page btn-next"><i class="fas fa-chevron-right"></i></button>
    </div>
  </div>
</div>

<!-- View In-Store Order Modal -->
<div class="modal-overlay" id="viewInstoreModal" style="display:none;">
  <div class="modal modal-lg">
    <div class="modal-header">
      <h3>In-Store Order Details</h3>
      <button class="modal-close" onclick="closeModal('viewInstoreModal')">&#x2715;</button>
    </div>
    <div class="modal-body">
      <div class="view-detail-group"><span class="view-label">Order No.</span>    <span class="view-value" id="iv_order_id"></span></div>
      <div class="view-detail-group"><span class="view-label">Customer</span>     <span class="view-value" id="iv_customer"></span></div>
      <div class="view-detail-group"><span class="view-label">Mobile</span>       <span class="view-value" id="iv_mobile"></span></div>
      <div class="view-detail-group"><span class="view-label">Order Type</span>   <span class="view-value" id="iv_order_type"></span></div>
      <div class="view-detail-group"><span class="view-label">Order Date</span>   <span class="view-value" id="iv_date"></span></div>
      <div class="view-detail-group"><span class="view-label">Payment</span>      <span class="view-value" id="iv_payment"></span></div>
      <div class="view-detail-group"><span class="view-label">Status</span>       <span class="view-value" id="iv_status"></span></div>
      <div class="view-detail-group"><span class="view-label">Total Amount</span> <span class="view-value" id="iv_total"></span></div>
      <div class="view-label view-modal-section-label">Ordered Items</div>
      <table class="admin-table" id="iv_items_table">
        <thead>
          <tr><th>Product</th><th>Size</th><th>Temp</th><th>Sugar</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr>
        </thead>
        <tbody id="iv_items_body"></tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-cancel" onclick="closeModal('viewInstoreModal')">Close</button>
    </div>
  </div>
</div>

<!-- Update Status Modal -->
<div class="modal-overlay" id="updateStatusModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h3>Update Order Status</h3>
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
        <button type="submit" name="update_status" class="btn-update">Update Status</button>
      </div>
    </form>
  </div>
</div>

<script>
  var instoreData = <?= json_encode(array_values($orders_map)) ?>;
  var instoreMap  = {};
  instoreData.forEach(function (o) { instoreMap[o.order_id] = o; });

  function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
  function closeModal(id) { document.getElementById(id).style.display = 'none'; }

  function viewInstoreOrder(orderId) {
    var o = instoreMap[orderId];
    if (!o) return;
    document.getElementById('iv_order_id').textContent  = o.order_number || '#' + o.order_id;
    document.getElementById('iv_customer').textContent  = o.customer_name;
    document.getElementById('iv_mobile').textContent    = o.mobile_number || '—';
    document.getElementById('iv_order_type').textContent  =
      o.kiosk_order_type === 'dine_in' ? 'Dine In' : 'Take Out';
    document.getElementById('iv_date').textContent      = o.order_date;
    document.getElementById('iv_payment').textContent   = o.payment_method;
    document.getElementById('iv_total').textContent     = '\u20B1' + parseFloat(o.total_amount).toFixed(2);
    document.getElementById('iv_status').innerHTML      =
      '<span class="badge badge-' + o.status + '">' +
      o.status.charAt(0).toUpperCase() + o.status.slice(1) + '</span>';

    var tbody = document.getElementById('iv_items_body');
    tbody.innerHTML = '';
    (o.items || []).forEach(function (item) {
      var sub = (parseFloat(item.price) * parseInt(item.quantity)).toFixed(2);
      tbody.innerHTML +=
        '<tr><td>' + item.product_name + '</td>' +
        '<td>' + (item.size        || '—') + '</td>' +
        '<td>' + (item.temperature || '—') + '</td>' +
        '<td>' + (item.sugar_level || '—') + '</td>' +
        '<td>' + item.quantity + '</td>' +
        '<td>\u20B1' + parseFloat(item.price).toFixed(2) + '</td>' +
        '<td>\u20B1' + sub + '</td></tr>';
    });
    if (!o.items || !o.items.length)
      tbody.innerHTML = '<tr><td colspan="7" class="vw-no-items-cell">No items found</td></tr>';

    openModal('viewInstoreModal');
  }

  function openUpdateModal(orderId, currentStatus) {
    document.getElementById('modal_order_id').value       = orderId;
    document.getElementById('display_order_id').innerText = '#' + orderId;
    document.getElementById('modal_status').value         = currentStatus;
    openModal('updateStatusModal');
  }

  document.querySelectorAll('.modal-overlay').forEach(function (o) {
    o.addEventListener('click', function (e) { if (e.target === o) o.style.display = 'none'; });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape')
      document.querySelectorAll('.modal-overlay').forEach(function (o) { o.style.display = 'none'; });
  });

  initSortableTable('instoreTable', 3);
  initTableSearch('instoreSearch', 'instoreTable');
  initTablePagination('instoreTable', 10);
</script>

<?php include 'includes/footer.php'; ?>