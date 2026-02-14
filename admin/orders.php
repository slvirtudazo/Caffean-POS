<?php
/**
 * Purge Coffee Shop - Admin Orders Management
 * This page allows administrators to view all orders, filter by status,
 * update order statuses, and view detailed order information.
 */

require_once '../php/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("location: ../login.php");
    exit();
}

$message = '';

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $update_query = "UPDATE orders SET status = ? WHERE order_id = ?";
    $stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt, "si", $new_status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Order status updated successfully!";
    }
    mysqli_stmt_close($stmt);
}

// Filter orders by status if requested
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$where_clause = $status_filter ? "WHERE o.status = '$status_filter'" : '';

// Fetch all orders with customer information
$orders_query = "SELECT o.*, u.full_name, u.email,
                 (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) as item_count
                 FROM orders o 
                 JOIN users u ON o.user_id = u.user_id 
                 $where_clause
                 ORDER BY o.order_date DESC";
$orders_result = mysqli_query($conn, $orders_query);

// Get order statistics for summary cards
$stats_query = "SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_count,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count
                FROM orders";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Purge Coffee Admin</title>
    
    <link rel="icon" type="image/png" href="../images/coffee_beans_logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Admin panel styling */
        :root {
            --ivory-cream: #F5F1E8;
            --deep-maroon: #2A0000;
            --burgundy-wine: #5B1312;
            --dark-brown: #3C1518;
        }
        
        body {
            background-color: var(--ivory-cream);
            font-family: 'Inter', sans-serif;
        }
        
        .admin-header {
            background-color: var(--deep-maroon);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        
        .btn-admin {
            background-color: var(--deep-maroon);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-admin:hover {
            background-color: var(--burgundy-wine);
            color: white;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(42, 0, 0, 0.08);
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.active {
            background-color: var(--deep-maroon);
            color: white;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .orders-table {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(42, 0, 0, 0.08);
        }
        
        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .modal-content {
            border-radius: 12px;
        }
        
        .modal-header {
            background-color: var(--deep-maroon);
            color: white;
            border-radius: 12px 12px 0 0;
        }
    </style>
</head>
<body>
    
    <!-- Admin header -->
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Orders Management</h1>
                <div>
                    <a href="dashboard.php" class="btn-admin me-2">
                        <i class="fas fa-arrow-left me-2"></i>Dashboard
                    </a>
                    <a href="../php/logout.php" class="btn-admin">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Success message -->
        <?php if($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Order statistics cards with filtering -->
        <div class="stats-cards">
            <a href="?status=" class="stat-card <?php echo !$status_filter ? 'active' : ''; ?>" style="text-decoration: none; color: inherit;">
                <div class="stat-number"><?php echo mysqli_num_rows($orders_result) + ($status_filter ? array_sum($stats) - $stats[$status_filter . '_count'] : 0); ?></div>
                <div class="stat-label">All Orders</div>
            </a>
            
            <a href="?status=pending" class="stat-card <?php echo $status_filter == 'pending' ? 'active' : ''; ?>" style="text-decoration: none; color: inherit;">
                <div class="stat-number"><?php echo $stats['pending_count']; ?></div>
                <div class="stat-label">Pending</div>
            </a>
            
            <a href="?status=processing" class="stat-card <?php echo $status_filter == 'processing' ? 'active' : ''; ?>" style="text-decoration: none; color: inherit;">
                <div class="stat-number"><?php echo $stats['processing_count']; ?></div>
                <div class="stat-label">Processing</div>
            </a>
            
            <a href="?status=completed" class="stat-card <?php echo $status_filter == 'completed' ? 'active' : ''; ?>" style="text-decoration: none; color: inherit;">
                <div class="stat-number"><?php echo $stats['completed_count']; ?></div>
                <div class="stat-label">Completed</div>
            </a>
            
            <a href="?status=cancelled" class="stat-card <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>" style="text-decoration: none; color: inherit;">
                <div class="stat-number"><?php echo $stats['cancelled_count']; ?></div>
                <div class="stat-label">Cancelled</div>
            </a>
        </div>

        <!-- Orders table -->
        <div class="orders-table">
            <h2 class="h4 mb-4" style="color: var(--deep-maroon);">
                <?php echo $status_filter ? ucfirst($status_filter) . ' ' : 'All '; ?>Orders
            </h2>
            <div class="table-responsive">
                <table class="table table-hover">
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
                        <?php if(mysqli_num_rows($orders_result) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['full_name']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y g:i A', strtotime($order['order_date'])); ?></td>
                                    <td><?php echo $order['item_count']; ?> item(s)</td>
                                    <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="viewOrder(<?php echo $order['order_id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-success" 
                                                onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, '<?php echo $order['status']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p>No orders found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="order_id" id="status_order_id">
                        
                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select name="status" id="status_select" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-admin">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to update order status
        function updateOrderStatus(orderId, currentStatus) {
            document.getElementById('status_order_id').value = orderId;
            document.getElementById('status_select').value = currentStatus;
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }
        
        // Function to view order details (to be implemented with order items)
        function viewOrder(orderId) {
            alert('Order details view will be implemented. Order ID: ' + orderId);
        }
    </script>
    
</body>
</html>