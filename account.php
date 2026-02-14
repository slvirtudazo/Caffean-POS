<?php
/**
 * Purge Coffee Shop - User Account Page
 * This page displays user account information, order history, and allows
 * customers to manage their profile settings.
 */

require_once 'php/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

// Fetch user information
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($stmt);

// Fetch user's order history
$orders_query = "SELECT o.*, COUNT(oi.id) as item_count 
                 FROM orders o 
                 LEFT JOIN order_items oi ON o.order_id = oi.order_id 
                 WHERE o.user_id = ? 
                 GROUP BY o.order_id 
                 ORDER BY o.order_date DESC 
                 LIMIT 10";
$stmt = mysqli_prepare($conn, $orders_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Calculate user statistics
$stats_query = "SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_spent,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders
                FROM orders 
                WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($stats_result);
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Purge Coffee</title>
    
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        /* Account page specific styles */
        .account-section {
            padding: var(--spacing-xxl) 0;
            background-color: var(--ivory-cream);
            min-height: 80vh;
        }
        
        .account-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .account-header {
            background: white;
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: var(--spacing-xl);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            background-color: var(--deep-maroon);
            color: var(--ivory-cream);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .user-details h2 {
            font-family: var(--font-heading);
            color: var(--deep-maroon);
            margin-bottom: var(--spacing-xs);
        }
        
        .user-details p {
            color: var(--dark-brown);
            margin: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-xl);
        }
        
        .stat-card {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            text-align: center;
            transition: var(--transition-normal);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
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
            margin: 0 auto var(--spacing-md);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--deep-maroon);
            margin-bottom: var(--spacing-xs);
        }
        
        .stat-label {
            color: var(--dark-brown);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .orders-section {
            background: white;
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .section-title {
            font-family: var(--font-heading);
            font-size: 1.75rem;
            color: var(--deep-maroon);
            margin-bottom: var(--spacing-lg);
        }
        
        .order-card {
            border: 2px solid var(--warm-sand);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            transition: var(--transition-fast);
        }
        
        .order-card:hover {
            border-color: var(--burgundy-wine);
            box-shadow: var(--shadow-sm);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-sm);
            padding-bottom: var(--spacing-sm);
            border-bottom: 1px solid var(--warm-sand);
        }
        
        .order-id {
            font-family: var(--font-subheading);
            font-weight: 600;
            color: var(--deep-maroon);
            font-size: 1.125rem;
        }
        
        .order-status {
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
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--spacing-md);
            margin-top: var(--spacing-sm);
        }
        
        .order-detail-item {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.75rem;
            color: var(--dark-brown);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--deep-maroon);
        }
        
        .btn-logout {
            background-color: var(--deep-maroon);
            color: var(--ivory-cream);
            padding: var(--spacing-sm) var(--spacing-md);
            border: none;
            border-radius: var(--radius-md);
            font-family: var(--font-subheading);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-normal);
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-logout:hover {
            background-color: var(--burgundy-wine);
            color: var(--ivory-cream);
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: var(--spacing-xxl) 0;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--warm-sand);
            margin-bottom: var(--spacing-md);
        }
    </style>
</head>
<body>
    
    <!-- Top banner -->
    <div class="top-banner">Shipping Nationwide</div>

    <!-- Main navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Purge Coffee Logo">
                <span>purge coffee</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="offers.php">Offers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                </ul>
            </div>
            
            <div class="nav-icons">
                <i class="fas fa-search nav-icon"></i>
                <a href="cart.php" class="text-decoration-none">
                    <i class="fas fa-shopping-cart nav-icon"></i>
                </a>
                <a href="account.php" class="text-decoration-none">
                    <i class="fas fa-user nav-icon"></i>
                </a>
            </div>
        </div>
    </nav>

    <!-- Account section -->
    <section class="account-section">
        <div class="container account-container">
            <!-- Account header with user info -->
            <div class="account-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <h2><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                        <p style="font-size: 0.875rem; margin-top: 0.25rem;">
                            Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>
                </div>
                <a href="php/logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>

            <!-- User statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total_orders'] ?? 0; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                    <div class="stat-value">â‚±<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['pending_orders'] ?? 0; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
            </div>

            <!-- Order history section -->
            <div class="orders-section">
                <h3 class="section-title">Order History</h3>
                
                <?php if(mysqli_num_rows($orders_result) > 0): ?>
                    <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <span class="order-id">Order #<?php echo $order['order_id']; ?></span>
                                <span class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            
                            <div class="order-details">
                                <div class="order-detail-item">
                                    <span class="detail-label">Date</span>
                                    <span class="detail-value">
                                        <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                                    </span>
                                </div>
                                
                                <div class="order-detail-item">
                                    <span class="detail-label">Items</span>
                                    <span class="detail-value"><?php echo $order['item_count']; ?> item(s)</span>
                                </div>
                                
                                <div class="order-detail-item">
                                    <span class="detail-label">Total Amount</span>
                                    <span class="detail-value">â‚±<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                                
                                <div class="order-detail-item">
                                    <span class="detail-label">Payment</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['payment_method']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <h3 style="font-family: var(--font-heading); color: var(--deep-maroon); margin-bottom: var(--spacing-md);">
                            No Orders Yet
                        </h3>
                        <p style="color: var(--dark-brown); margin-bottom: var(--spacing-lg);">
                            Start exploring our menu and place your first order!
                        </p>
                        <a href="coffee.php" class="btn-primary">Browse Menu</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-brand">
                        <img src="images/coffee_beans_logo.png" alt="Purge Coffee">
                        <span class="footer-brand-name">purge coffee</span>
                    </div>
                    <div class="footer-contact">
                        <p><i class="fas fa-phone"></i> 0960 315 0070</p>
                        <p><i class="fas fa-envelope"></i> purgecoffee@gmail.com</p>
                    </div>
                </div>

                <div class="footer-section">
                    <h3>Our Policies</h3>
                    <ul class="footer-links">
                        <li><a href="#">Privacy</a></li>
                        <li><a href="#">Terms Of Use</a></li>
                        <li><a href="#">Shipping & Delivery</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Social Media</h3>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>

            <div class="footer-divider"></div>

            <div class="footer-bottom">
                <p>&copy; 2026 Purge Coffee | All Rights Reserved</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    
</body>
</html>