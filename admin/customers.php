<?php
/**
 * Purge Coffee Shop - Admin Customers Management
 * This page allows administrators to view all registered customers,
 * see their order history, and manage customer accounts.
 */

require_once '../php/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("location: ../login.php");
    exit();
}

// Fetch all customers with order statistics
$customers_query = "SELECT u.*, 
                    COUNT(DISTINCT o.order_id) as total_orders,
                    COALESCE(SUM(o.total_amount), 0) as total_spent
                    FROM users u
                    LEFT JOIN orders o ON u.user_id = o.user_id
                    WHERE u.role = 'customer'
                    GROUP BY u.user_id
                    ORDER BY u.created_at DESC";
$customers_result = mysqli_query($conn, $customers_query);

// Calculate customer statistics
$total_customers = mysqli_num_rows($customers_result);
$stats_query = "SELECT 
                COUNT(DISTINCT CASE WHEN o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN u.user_id END) as active_customers,
                COUNT(DISTINCT CASE WHEN NOT EXISTS (SELECT 1 FROM orders WHERE user_id = u.user_id) THEN u.user_id END) as no_orders
                FROM users u
                LEFT JOIN orders o ON u.user_id = o.user_id
                WHERE u.role = 'customer'";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers Management - Purge Coffee Admin</title>
    
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(42, 0, 0, 0.08);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--deep-maroon);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--dark-brown);
        }
        
        .customers-table {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(42, 0, 0, 0.08);
        }
        
        .customer-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-vip {
            background-color: #ffd700;
            color: #000;
        }
        
        .badge-regular {
            background-color: #e2d9c8;
            color: var(--dark-brown);
        }
        
        .badge-new {
            background-color: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    
    <!-- Admin header -->
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Customers Management</h1>
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
        <!-- Customer statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_customers; ?></div>
                <div class="stat-label">Total Customers</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active_customers']; ?></div>
                <div class="stat-label">Active (30 days)</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['no_orders']; ?></div>
                <div class="stat-label">No Orders Yet</div>
            </div>
        </div>

        <!-- Customers table -->
        <div class="customers-table">
            <h2 class="h4 mb-4" style="color: var(--deep-maroon);">All Customers</h2>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Registered</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($customers_result, 0);
                        while($customer = mysqli_fetch_assoc($customers_result)): 
                            // Determine customer status badge
                            $badge_class = 'badge-new';
                            $badge_text = 'New';
                            
                            if ($customer['total_orders'] >= 10) {
                                $badge_class = 'badge-vip';
                                $badge_text = 'VIP';
                            } elseif ($customer['total_orders'] > 0) {
                                $badge_class = 'badge-regular';
                                $badge_text = 'Regular';
                            }
                        ?>
                            <tr>
                                <td><?php echo $customer['user_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($customer['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                <td><?php echo $customer['total_orders']; ?></td>
                                <td><strong>₱<?php echo number_format($customer['total_spent'], 2); ?></strong></td>
                                <td>
                                    <span class="customer-badge <?php echo $badge_class; ?>">
                                        <?php echo $badge_text; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        
                        <?php if($total_customers == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <p>No customers registered yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customer insights section -->
        <div class="customers-table mt-4">
            <h2 class="h4 mb-4" style="color: var(--deep-maroon);">Customer Insights</h2>
            <div class="row">
                <div class="col-md-6">
                    <h5 style="color: var(--burgundy-wine);">Top Customers by Spending</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $top_customers_query = "SELECT u.full_name, SUM(o.total_amount) as total_spent
                                                   FROM users u
                                                   JOIN orders o ON u.user_id = o.user_id
                                                   WHERE u.role = 'customer' AND o.status = 'completed'
                                                   GROUP BY u.user_id
                                                   ORDER BY total_spent DESC
                                                   LIMIT 5";
                            $top_result = mysqli_query($conn, $top_customers_query);
                            
                            if(mysqli_num_rows($top_result) > 0):
                                while($top = mysqli_fetch_assoc($top_result)):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($top['full_name']); ?></td>
                                    <td><strong>₱<?php echo number_format($top['total_spent'], 2); ?></strong></td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted">No completed orders yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <h5 style="color: var(--burgundy-wine);">Recent Registrations</h5>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recent_query = "SELECT full_name, created_at
                                           FROM users
                                           WHERE role = 'customer'
                                           ORDER BY created_at DESC
                                           LIMIT 5";
                            $recent_result = mysqli_query($conn, $recent_query);
                            
                            while($recent = mysqli_fetch_assoc($recent_result)):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($recent['full_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($recent['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>