<?php
/**
 * Purge Coffee Shop - Coffee Menu Page
 * Displays all coffee beverages with pagination, allowing users to browse through
 * different coffee offerings including hot coffee, iced coffee, and special drinks.
 */

require_once 'php/db_connection.php';

// Pagination configuration
$items_per_page = 4;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total count of coffee products (categories 1, 2, and 4)
$count_query = "SELECT COUNT(*) as total 
                FROM products 
                WHERE category_id IN (1, 2, 4) AND status = 1";
$count_result = mysqli_query($conn, $count_query);
$total_products = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_products / $items_per_page);

// Fetch coffee products for current page
$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   JOIN categories c ON p.category_id = c.category_id 
                   WHERE p.category_id IN (1, 2, 4) AND p.status = 1 
                   ORDER BY p.category_id, p.name 
                   LIMIT $items_per_page OFFSET $offset";
$products_result = mysqli_query($conn, $products_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coffee Menu - Purge Coffee</title>
    
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/coffee-page.css">
</head>
<body>
    
    <div class="top-banner">Shipping Nationwide</div>

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
                        <a class="nav-link active" href="coffee.php">Coffee</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pastry.php">Pastry</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="offers.php">Offers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
            </div>
            
            <div class="nav-icons">
                <i class="fas fa-search nav-icon"></i>
                <a href="cart.php" class="text-decoration-none">
                    <i class="fas fa-shopping-cart nav-icon"></i>
                </a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="account.php" class="text-decoration-none">
                        <i class="fas fa-user nav-icon"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="text-decoration-none">
                        <i class="fas fa-user nav-icon"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section class="menu-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Coffee Menu</h2>
                <div class="section-divider"></div>
            </div>

            <div class="product-grid">
                <?php 
                if(mysqli_num_rows($products_result) > 0):
                    while($product = mysqli_fetch_assoc($products_result)): 
                ?>
                    <div class="product-card">
                        <div class="product-image-wrapper">
                            <div class="favorite-icon">
                                <i class="far fa-heart"></i>
                            </div>
                            <img src="images/coffee.png" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description">
                                <?php echo htmlspecialchars($product['description']); ?>
                            </p>
                            <div class="product-footer">
                                <span class="product-price">₱ <?php echo number_format($product['price'], 2); ?></span>
                                <button class="btn-order" onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                    Order Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else: 
                ?>
                    <p class="text-center">No coffee products available at the moment.</p>
                <?php endif; ?>
            </div>

            <?php if($total_pages > 1): ?>
            <div class="carousel-controls">
                <?php if($current_page > 1): ?>
                    <a href="?page=<?php echo $current_page - 1; ?>" class="carousel-arrow">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <div class="pagination-dots">
                    <?php for($i = 1; $i <= min(5, $total_pages); $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="pagination-dot <?php echo $i == $current_page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>

                <?php if($current_page < $total_pages): ?>
                    <a href="?page=<?php echo $current_page + 1; ?>" class="carousel-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

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