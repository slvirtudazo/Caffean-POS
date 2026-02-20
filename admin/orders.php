<?php
/**
 * Purge Coffee Shop — Admin Orders Management
 * View all orders, filter by status, update order statuses.
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error   = '';

// ── Handle status update ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id   = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    $allowed    = ['pending', 'processing', 'completed', 'cancelled'];

    if (in_array($new_status, $allowed)) {
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status=? WHERE order_id=?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Order #$order_id status updated to " . ucfirst($new_status) . ".";
        } else {
            $error = "Error updating order status.";
        }
        mysqli_stmt_close($stmt);
    }
}

// ── Filter ────────────────────────────────────────────────────
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$where_clause  = "1=1";
if (in_array($status_filter, ['pending', 'processing', 'completed', 'cancelled'])) {
    $where_clause = "o.status = '$status_filter'";
}

// Get counts for badges
$counts = [ 'all' => 0, 'pending' => 0, 'processing' => 0, 'completed' => 0, 'cancelled' => 0 ];
$c_res  = mysqli_query($conn, "SELECT status, COUNT(*) as count FROM orders GROUP BY status");
while ($r = mysqli_fetch_assoc($c_res)) {
    $counts[$r['status']] = $r['count'];
    $counts['all']       += $r['count'];
}

// ── Fetch orders ──────────────────────────────────────────────
$orders_query = "SELECT o.*, u.full_name,
                 (SELECT SUM(quantity) FROM order_items WHERE order_id = o.order_id) as total_items
                 FROM orders o
                 JOIN users u ON o.user_id = u.user_id
                 WHERE $where_clause
                 ORDER BY o.order_date DESC";
$orders_result = mysqli_query($conn, $orders_query);

include 'includes/header.php';
?>

<div class="page-header">
  <h1>Orders</h1>
  <p>Track and manage customer orders</p>
</div>

<?php if ($message): ?>
  <div style="background:#e6f4ea;color:#1e8e3e;padding:15px;border-radius:8px;margin-bottom:20px;"><?= $message ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div style="background:#fce8e6;color:#d93025;padding:15px;border-radius:8px;margin-bottom:20px;"><?= $error ?></div>
<?php endif; ?>

<div class="admin-tabs">
  <a href="?status=all" class="tab <?= $status_filter === 'all' ? 'active' : '' ?>">All Orders <span class="badge"><?= $counts['all'] ?></span></a>
  <a href="?status=pending" class="tab <?= $status_filter === 'pending' ? 'active' : '' ?>">Pending <span class="badge"><?= $counts['pending'] ?></span></a>
  <a href="?status=processing" class="tab <?= $status_filter === 'processing' ? 'active' : '' ?>">Processing <span class="badge"><?= $counts['processing'] ?></span></a>
  <a href="?status=completed" class="tab <?= $status_filter === 'completed' ? 'active' : '' ?>">Completed <span class="badge"><?= $counts['completed'] ?></span></a>
  <a href="?status=cancelled" class="tab <?= $status_filter === 'cancelled' ? 'active' : '' ?>">Cancelled <span class="badge"><?= $counts['cancelled'] ?></span></a>
</div>

<div class="card">
  <div class="card-header">
    <h2><?= ucfirst($status_filter) ?> Orders</h2>
    <span style="color:var(--text-muted);font-size:.9rem;"><?= mysqli_num_rows($orders_result) ?> results</span>
  </div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Customer</th>
          <th>Date</th>
          <th>Items</th>
          <th>Amount</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (mysqli_num_rows($orders_result) > 0): ?>
          <?php while ($o = mysqli_fetch_assoc($orders_result)): ?>
            <tr>
              <td><strong>#<?= $o['order_id'] ?></strong></td>
              <td><?= htmlspecialchars($o['full_name']) ?></td>
              <td><?= date('M d, Y h:i A', strtotime($o['order_date'])) ?></td>
              <td><?= $o['total_items'] ?? 0 ?></td>
              <td><strong>&#8369;<?= number_format($o['total_amount'], 2) ?></strong></td>
              <td><?= htmlspecialchars($o['payment_method']) ?></td>
              <td>
                <span class="badge badge-<?= strtolower($o['status']) ?>">
                  <?= ucfirst($o['status']) ?>
                </span>
              </td>
              <td>
                <button class="btn-icon" title="Update Status" onclick="openUpdateModal(<?= $o['order_id'] ?>, '<?= $o['status'] ?>')">
                  <i class="fas fa-edit"></i>
                </button>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" style="text-align:center; padding:50px; color:var(--text-muted);">
              <i class="fas fa-inbox" style="font-size:3rem; opacity:0.2; display:block; margin-bottom:10px;"></i>
              No orders found.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div id="updateStatusModal" class="modal-overlay">
  <div class="modal-content">
    <h3>Update Order Status</h3>
    <p style="color:var(--text-muted); margin-bottom:20px;">Change the status of <strong id="display_order_id"></strong></p>
    <form method="POST">
      <input type="hidden" name="order_id" id="modal_order_id">
      <div class="form-group">
        <label>Status</label>
        <select name="status" id="modal_status" class="form-control">
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:30px;">
        <button type="button" class="btn-outline" onclick="closeModal('updateStatusModal')">Cancel</button>
        <button type="submit" name="update_status" class="btn-primary">Update Status</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
  function closeModal(id) { document.getElementById(id).style.display = 'none'; }
  function openUpdateModal(orderId, currentStatus) {
    document.getElementById('modal_order_id').value    = orderId;
    document.getElementById('display_order_id').innerText = "#" + orderId;
    document.getElementById('modal_status').value      = currentStatus;
    openModal('updateStatusModal');
  }
</script>

</main>
<?php include 'includes/footer.php'; ?>