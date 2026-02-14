<?php
/**
 * Purge Coffee Shop - Homepage
 * Main landing page with hero section, best seller products, and footer
 */

require_once 'php/db_connection.php';

// Fetch top 4 best seller products for display
$bestsellers_query = "SELECT 
    p.product_id,
    p.name,
    p.description,
    p.price,
    p.category_id,
    c.name as category_name,
    (
        COALESCE((SELECT COUNT(*) FROM order_items oi 
                  JOIN orders o ON oi.order_id = o.order_id 
                  WHERE oi.product_id = p.product_id AND o.status = 'completed'), 0) * 3 +
        COALESCE((SELECT SUM(interaction_count) FROM product_interactions 
                  WHERE product_id = p.product_id AND interaction_type = 'add_to_cart'), 0) * 2 +
        COALESCE((SELECT SUM(interaction_count) FROM product_interactions 
                  WHERE product_id = p.product_id AND interaction_type = 'favorite'), 0) * 1
    ) as popularity_score
FROM products p
JOIN categories c ON p.category_id = c.category_id
WHERE p.status = 1
ORDER BY popularity_score DESC, p.created_at DESC
LIMIT 4";

$bestsellers_result = mysqli_query($conn, $bestsellers_query);

// If no best sellers, get sample products
$show_samples = mysqli_num_rows($bestsellers_result) == 0;
if ($show_samples) {
    $sample_query = "SELECT p.*, c.name as category_name 
                     FROM products p 
                     JOIN categories c ON p.category_id = c.category_id 
                     WHERE p.status = 1 
                     ORDER BY RAND() 
                     LIMIT 4";
    $bestsellers_result = mysqli_query($conn, $sample_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purge Coffee - The Richest Coffee in the City</title>
    
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/home-bestsellers.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/menu-page.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/coffee-page.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/pastry-page.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/about-page.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/contact-page.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/footer-section.css?v=<?php echo time(); ?>">
</head>
<body>
    
    <!-- Top Banner -->
    <div class="top-banner">Shipping Nationwide</div>

    <!-- Navigation Bar -->
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
                        <a class="nav-link active" href="index.php">Home</a>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <p class="hero-label">Welcome</p>
                    <h1 class="hero-title">We serve the richest coffee in the city!</h1>
                    <p class="hero-description">
                        Experience the perfect blend of tradition and innovation in every cup. 
                        Our expertly crafted beverages and artisan pastries are made with passion and precision.
                    </p>
                    <a href="menu.php" class="btn-primary">Order Now</a>
                </div>
                
                <div class="col-lg-6 hero-image text-center">
                    <img src="images/coffee_mug.png" alt="Premium Coffee">
                </div>
            </div>
        </div>
    </section>

    <!-- Best Sellers Section -->
    <section class="home-bestsellers-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Best Sellers</h2>
                <div class="section-divider"></div>
            </div>

            <div class="product-grid">
                <?php while($product = mysqli_fetch_assoc($bestsellers_result)): 
                    // Determine image based on category
                    $image_path = 'images/coffee.png';
                    if(in_array($product['category_id'], [7])) {
                        $image_path = 'images/pastry.png';
                    }
                ?>
                    <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>" data-testid="bestseller-product-card">
                        <div class="product-image-wrapper">
                            <div class="favorite-icon" onclick="toggleFavorite(<?php echo $product['product_id']; ?>, this.querySelector('i'))" data-testid="favorite-icon">
                                <i class="far fa-heart"></i>
                            </div>
                            <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="product-footer">
                                <span class="product-price">₱ <?php echo number_format($product['price'], 2); ?></span>
                                <button class="btn-order" onclick="addToCart(<?php echo $product['product_id']; ?>)" data-testid="add-to-cart-btn">
                                    Order Now
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="view-menu-container">
                <a href="menu.php" class="btn-view-menu" data-testid="view-full-menu-btn">View Full Menu</a>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
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
                    <h3>OUR POLICIES</h3>
                    <ul class="footer-links">
                        <li><a href="#">Privacy</a></li>
                        <li><a href="#">Terms Of Use</a></li>
                        <li><a href="#">Shipping & Delivery</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>SOCIAL MEDIA</h3>
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