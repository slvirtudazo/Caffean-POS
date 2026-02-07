<?php
/**
 * Purge Coffee Shop - Admin Dashboard
 * This is the main administrative interface that provides an overview of the system,
 * including statistics on products, orders, and users. Only accessible to admin users.
 */

require_once '../php/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("location: ../login.php");
    exit();
}

// Fetch statistics for the dashboard
$stats = array();

// Count total products
$product_query = "SELECT COUNT(*) as total FROM products WHERE status = 1";
$product_result = mysqli_query($conn, $product_query);
$stats['products'] = mysqli_fetch_assoc($product_result)['total'];

// Count total orders
$orders_query = "SELECT COUNT(*) as total FROM orders";
$orders_result = mysqli_query($conn, $orders_query);
$stats['orders'] = mysqli_fetch_assoc($orders_result)['total'];

// Count total customers
$customers_query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
$customers_result = mysqli_query($conn, $customers_query);
$stats['customers'] = mysqli_fetch_assoc($customers_result)['total'];

// Calculate total revenue
$revenue_query = "SELECT SUM(total_amount) as revenue FROM orders WHERE status = 'completed'";
$revenue_result = mysqli_query($conn, $revenue_query);
$stats['revenue'] = mysqli_fetch_assoc($revenue_result)['revenue'] ?? 0;

// Fetch recent orders
$recent_orders_query = "SELECT o.*, u.full_name 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.user_id 
                        ORDER BY o.order_date DESC 
                        LIMIT 5";
$recent_orders = mysqli_query($conn, $recent_orders_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Purge Coffee</title>
    
    <link rel="icon" type="image/png" href="../images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Admin-specific styling with warm coffee shop colors */
        :root {
            --ivory-cream: #F5F1E8;
            --warm-sand: #E2D9C8;
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
            color: var(--ivory-cream);
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(42, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(42, 0, 0, 0.12);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background-color: var(--burgundy-wine);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--deep-maroon);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--dark-brown);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .admin-table {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(42, 0, 0, 0.08);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--deep-maroon);
            margin-bottom: 1.5rem;
        }
        
        .btn-admin {
            background-color: var(--deep-maroon);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease;
        }
        
        .btn-admin:hover {
            background-color: var(--burgundy-wine);
            color: white;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-processing {
            background-color: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    
    <!-- Admin header with branding and user info -->
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="admin-title">Purge Coffee - Admin Dashboard</h1>
                <div>
                    <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../php/logout.php" class="btn-admin">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics cards showing key metrics -->
        <div class="stats-grid">
            <!-- Total Products stat card -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-coffee"></i>
                </div>
                <div class="stat-value"><?php echo $stats['products']; ?></div>
                <div class="stat-label">Total Products</div>
            </div>

            <!-- Total Orders stat card -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-value"><?php echo $stats['orders']; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>

            <!-- Total Customers stat card -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo $stats['customers']; ?></div>
                <div class="stat-label">Total Customers</div>
            </div>

            <!-- Total Revenue stat card -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-peso-sign"></i>
                </div>
                <div class="stat-value">₱<?php echo number_format($stats['revenue'], 2); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Recent orders table -->
        <div class="admin-table">
            <h2 class="section-title">Recent Orders</h2>
            
            <?php if(mysqli_num_rows($recent_orders) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center" style="color: var(--dark-brown);">No orders found.</p>
            <?php endif; ?>
        </div>

        <!-- Quick actions section -->
        <div class="admin-table mt-4">
            <h2 class="section-title">Quick Actions</h2>
            <div class="d-flex gap-3 flex-wrap">
                <a href="products.php" class="btn-admin">
                    <i class="fas fa-box me-2"></i>Manage Products
                </a>
                <a href="orders.php" class="btn-admin">
                    <i class="fas fa-list me-2"></i>View All Orders
                </a>
                <a href="customers.php" class="btn-admin">
                    <i class="fas fa-users me-2"></i>Manage Customers
                </a>
                <a href="../index.php" class="btn-admin">
                    <i class="fas fa-home me-2"></i>View Website
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>