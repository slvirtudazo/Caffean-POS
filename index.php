<?php
/**
 * Purge Coffee Shop - Enhanced Homepage
 * Main landing page with hero section, best seller products, special offers, 
 * why choose us, store location, and improved navigation
 */

require_once 'php/db_connection.php';

// Fetch top 6 best seller products
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
HAVING popularity_score > 0
ORDER BY popularity_score DESC
LIMIT 6";

$bestsellers_result = mysqli_query($conn, $bestsellers_query);

// If no best sellers yet, show 6 sample products
$show_samples = false;
if (mysqli_num_rows($bestsellers_result) == 0) {
    $show_samples = true;
    $sample_coffee_query = "SELECT p.*, c.name as category_name 
                            FROM products p 
                            JOIN categories c ON p.category_id = c.category_id 
                            WHERE p.category_id IN (1, 2, 4) AND p.status = 1 
                            ORDER BY p.created_at DESC 
                            LIMIT 3";
    $coffee_result = mysqli_query($conn, $sample_coffee_query);
    
    $sample_pastry_query = "SELECT p.*, c.name as category_name 
                            FROM products p 
                            JOIN categories c ON p.category_id = c.category_id 
                            WHERE p.category_id = 7 AND p.status = 1 
                            ORDER BY p.created_at DESC 
                            LIMIT 3";
    $pastry_result = mysqli_query($conn, $sample_pastry_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purge Coffee - Home Page</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Stylesheets -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/home-bestsellers.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/footer-section.css?v=<?php echo time(); ?>">

    <!-- Search Bar -->
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
</head>
<body>
    
    <!-- Top Announcement Banner -->
    <div class="top-banner">Shipping Nationwide</div>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <!-- Brand Logo -->
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Purge Coffee Logo">
                <span>purge coffee</span>
            </a>
            
            <!-- Mobile Menu Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Links -->
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="supplies-page.php">Offers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                </ul>
            </div>
            
            <!-- Navigation Icons -->
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
                <!-- Hero Content -->
                <div class="col-lg-6 hero-content">
                    <p class="hero-label">Welcome</p>
                    <h1 class="hero-title">We serve the richest coffee in the city!</h1>
                    <p class="hero-description">
                        Experience the perfect blend of premium beans and expert craftsmanship in every cup we brew
                    </p>
                    <a href="menu.php" class="btn-primary">Order Now</a>
                </div>
                
                <!-- Hero Image -->
                <div class="col-lg-6 hero-image">
                    <img src="images/coffee_mug.png" alt="Premium Coffee">
                </div>
            </div>
        </div>
    </section>

    <!-- Best Sellers Section -->
    <section class="home-bestsellers-section">
        <div class="container">
            <!-- Section Header -->
            <div class="section-header">
                <h2 class="section-title">Best Sellers</h2>
                <p class="section-subtitle">Our most popular drinks and treats!</p>
            </div>

            <!-- Product Grid -->
            <div class="product-grid">
                <?php 
                if($show_samples):
                    // Show sample coffee products
                    while($product = mysqli_fetch_assoc($coffee_result)): 
                ?>
                    <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>">
                        <div class="product-image-wrapper">
                            <div class="favorite-icon" onclick="toggleFavorite(<?php echo $product['product_id']; ?>, this.querySelector('i'))">
                                <i class="far fa-heart"></i>
                            </div>
                            <img src="images/coffee.png" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        </div>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <div class="product-footer">
                                <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                <button class="btn-add-cart" onclick="addToCart(<?php echo $product['product_id']; ?>, this)">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile;
                    
                    // Show sample pastry products
                    while($product = mysqli_fetch_assoc($pastry_result)): 
                ?>
                    <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>">
                        <div class="product-image-wrapper">
                            <div class="favorite-icon" onclick="toggleFavorite(<?php echo $product['product_id']; ?>, this.querySelector('i'))">
                                <i class="far fa-heart"></i>
                            </div>
                            <img src="images/pastry.png" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <div class="product-footer">
                                <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                <button class="btn-add-cart" onclick="addToCart(<?php echo $product['product_id']; ?>, this)">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                    // Display actual best sellers
                    while($product = mysqli_fetch_assoc($bestsellers_result)): 
                        $image_path = 'images/coffee.png';
                        if($product['category_id'] == 7) {
                            $image_path = 'images/pastry.png';
                        }
                ?>
                    <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>">
                        <div class="product-image-wrapper">
                            <div class="favorite-icon" onclick="toggleFavorite(<?php echo $product['product_id']; ?>, this.querySelector('i'))">
                                <i class="far fa-heart"></i>
                            </div>
                            <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            
                            <div class="product-footer">
                                <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                <button class="btn-add-cart" onclick="addToCart(<?php echo $product['product_id']; ?>, this)">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile;
                endif;
                ?>
            </div>

            <!-- View Menu Button -->
            <div class="view-menu-container">
                <a href="menu.php" class="btn-view-menu">View Full Menu</a>
            </div>
        </div>
    </section>

    <!-- Special Offers Section -->
    <section class="special-offers-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Special Offers</h2>
                <p class="section-subtitle">Limited time deals you don't want to miss!</p>
            </div>

            <div class="offers-grid">
                <!-- Buy 2 Get 1 Free -->
                <div class="offer-promo-card">
                    <div class="promo-icon">
                        <i class="fas fa-gift"></i>
                    </div>
                    <h3 class="promo-title">Buy 2 Get 1 Free</h3>
                    <p class="promo-description">Purchase any two coffee drinks and get the third one absolutely free. This offer is valid for all regular-sized drinks.</p>
                </div>

                <!-- Student Discount -->
                <div class="offer-promo-card">
                    <div class="promo-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 class="promo-title">Student Discount</h3>
                    <p class="promo-description">Students receive 15% off on all orders. Simply present your valid student ID at checkout to claim the discount.</p>
                </div>

                <!-- Free Delivery -->
                <div class="offer-promo-card">
                    <div class="promo-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h3 class="promo-title">Free Delivery Over ₱500</h3>
                    <p class="promo-description">Enjoy free nationwide delivery on orders above ₱500. Experience fast and reliable shipping straight to your doorstep.</p>
                </div>
            </div>

            <!-- Shop Now Button -->
            <div class="view-menu-container">
                <a href="menu.php" class="btn-promo">Shop Now</a>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="why-choose-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Why Choose Us?</h2>
                <p class="section-subtitle">Excellence in every cup we serve!</p>
            </div>

            <div class="features-grid">
                <!-- Premium Beans -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-seedling"></i>
                    </div>
                    <h3 class="feature-title">Premium Beans</h3>
                    <p class="feature-description">
                        We source the finest, ethically grown coffee beans from well-known regions worldwide.
                        Each bean is carefully selected by our team.
                        This ensures rich flavor and consistent quality in every cup.
                    </p>
                </div>

                <!-- Fresh Ingredients -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3 class="feature-title">Fresh Ingredients</h3>
                    <p class="feature-description">
                        Every ingredient is carefully chosen and freshly prepared each day.
                        We follow strict standards to maintain freshness and safety.
                        This ensures that every order reflects our commitment to quality.
                    </p>
                </div>

                <!-- Fast Delivery -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3 class="feature-title">Fast Nationwide Delivery</h3>
                    <p class="feature-description">We provide fast and reliable delivery service across the Philippines.
                        Our system keeps your order processed efficiently.
                        You can track your order at every stage for complete convenience.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- Store Location Section -->
    <section class="store-location-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Visit Our Store</h2>
                <p class="section-subtitle">Come experience the Purge Coffee difference in person!</p>
            </div>

            <div class="location-content">
                <!-- Location Details -->
                <div class="location-details">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="detail-text">
                            <h4>Address</h4>
                            <p>123 Tulip Drive, Matina<br>Davao City 8000, Philippines</p>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="detail-text">
                            <h4>Operating Hours</h4>
                            <p>Monday-Friday: 8:00 AM - 10:00 PM<br>
                            Saturday-Sunday: 10:00 AM - 8:00 PM</p>
                        </div>
                    </div>

                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="detail-text">
                            <h4>Contact Details</h4>
                            <p>0960 315 0070<br>purgecoffee@gmail.com</p>
                        </div>
                    </div>
                </div>

                <!-- Map Placeholder -->
                <div class="location-map">
                    <div class="map-placeholder">
                        <i class="fas fa-map-marked-alt"></i>
                        <p>Interactive Map</p>
                        <small>Google Maps integration can be added here</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <!-- Brand & Contact -->
                <div class="footer-section">
                    <div class="footer-brand">
                        <span class="footer-brand-name">PURGE COFFEE</span>
                    </div>
                    <div class="footer-contact">
                        <p><i class="fas fa-phone"></i> 0960 315 0070</p>
                        <p><i class="fas fa-envelope"></i> purgecoffee@gmail.com</p>
                    </div>
                </div>

                <!-- Policies -->
                <div class="footer-section">
                    <h3>OUR POLICIES</h3>
                    <ul class="footer-links">
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms Of Use</a></li>
                        <li><a href="#">Shipping & Delivery</a></li>
                    </ul>
                </div>

                <!-- Social Media -->
                <div class="footer-section">
                    <h3>SOCIAL MEDIA</h3>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>

            <hr>

            <div class="footer-bottom">
                <p>&copy; 2026 Purge Coffee | All Rights Reserved</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/search.js"></script> 
</body>
</html>