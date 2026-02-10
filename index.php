<?php
/**
 * Purge Coffee Shop - Homepage
 * Main landing page with hero section, best seller products, and special offers
 */

require_once 'php/db_connection.php';

// Fetch top 6 best seller products (changed from 4 to 6 for 3-column layout)
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

// If no best sellers yet, show 6 sample products (3 coffee, 3 pastry)
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
    <title>Purge Coffee - The Richest Coffee in the City</title>
    
    <!-- Favicon - Site icon displayed in browser tab -->
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    
    <!-- Bootstrap CSS - Responsive grid and component framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome - Icon library for UI elements -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Base styles - Core CSS variables and global styles -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Home Best Sellers section styles - Specific to homepage product display -->
    <link rel="stylesheet" href="css/home-bestsellers.css?v=<?php echo time(); ?>">
    
    <!-- Footer styles - Dedicated footer component styling -->
    <link rel="stylesheet" href="css/footer-section.css?v=<?php echo time(); ?>">
</head>
<body>
    
    <!-- Top promotional banner - Displays shipping information -->
    <div class="top-banner">Shipping Nationwide</div>

    <!-- Main navigation bar - Site-wide navigation menu -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <!-- Brand logo and name - Links to homepage -->
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Purge Coffee Logo">
                <span>purge coffee</span>
            </a>
            
            <!-- Mobile menu toggle button - Visible on small screens -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation links - Main menu items -->
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
                </ul>
            </div>
            
            <!-- Navigation icons - Search, cart, and user account -->
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

    <!-- Hero Section - Main landing area with call-to-action -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <!-- Left column - Text content and CTA button -->
                <div class="col-lg-6 hero-content">
                    <p class="hero-label">Welcome</p>
                    <h1 class="hero-title">We serve the richest coffee in the city!</h1>
                    <p class="hero-description">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
                    </p>
                    <a href="menu.php" class="btn-primary">Order Now</a>
                </div>
                
                <!-- Right column - Hero image -->
                <div class="col-lg-6 hero-image">
                    <img src="images/coffee_mug.png" alt="Premium Coffee">
                </div>
            </div>
        </div>
    </section>

    <!-- Best Sellers Section - Showcase top products without pricing -->
    <section class="home-bestsellers-section">
        <div class="container">
            <!-- Section header - Title without decorative coffee bean logo -->
            <div class="section-header">
                <h2 class="section-title">Best Sellers</h2>
                <!-- Removed: Coffee bean logo divider for cleaner look -->
            </div>

            <!-- Product grid - 3-column layout matching menu page style -->
            <div class="product-grid">
                <?php 
                // Display sample products if no best sellers exist
                if ($show_samples):
                    // Show coffee products
                    while($product = mysqli_fetch_assoc($coffee_result)): 
                ?>
                    <!-- Individual product card - Simplified display without pricing -->
                    <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>">
                        <!-- Product image wrapper - Contains image and favorite icon -->
                        <div class="product-image-wrapper">
                            <!-- Favorite toggle - Click to add/remove from favorites -->
                            <div class="favorite-icon" onclick="toggleFavorite(<?php echo $product['product_id']; ?>, this.querySelector('i'))">
                                <i class="far fa-heart"></i>
                            </div>
                            <img src="images/coffee.png" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                        </div>
                        
                        <!-- Product information - Name and description only -->
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <!-- Removed: Price and Order button for cleaner showcase -->
                        </div>
                    </div>
                <?php 
                    endwhile;
                    
                    // Show pastry products
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
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                    // Display actual best sellers based on popularity score
                    while($product = mysqli_fetch_assoc($bestsellers_result)): 
                        // Determine image based on category (coffee or pastry)
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
                        </div>
                    </div>
                <?php 
                    endwhile;
                endif;
                ?>
            </div>

            <!-- Call-to-action button - Link to full menu with pricing -->
            <div class="view-menu-container">
                <a href="menu.php" class="btn-view-menu">View Full Menu</a>
            </div>
        </div>
    </section>

    <!-- What We Offer Section - Product categories showcase -->
    <section class="offers-section">
        <div class="container">
            <!-- Section header - Title without decorative coffee bean logo -->
            <div class="section-header">
                <h2 class="section-title">What We Offer</h2>
                <!-- Removed: Coffee bean logo divider for cleaner look -->
            </div>

            <!-- Offers grid - Three main product categories -->
            <div class="offers-grid">
                <!-- Coffee Beans category card - Premium beans offering -->
                <div class="offer-card">
                    <img src="images/coffee_beans_offer.png" alt="Coffee Beans" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Coffee Beans</h3>
                        <p style="margin: 0; font-size: 0.9rem;">
                            Sourced from the finest coffee-growing regions, our premium beans are carefully 
                            roasted to perfection for an exceptional brewing experience at home.
                        </p>
                    </div>
                </div>
                
                <!-- Milk & Creamers category card - Dairy and alternatives -->
                <div class="offer-card">
                    <img src="images/milk_creamer_offer.png" alt="Milk & Creamers" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Milk & Creamers</h3>
                        <p style="margin: 0; font-size: 0.9rem;">
                            Complete your coffee with our selection of premium dairy and plant-based options, 
                            each chosen to complement the rich flavors of our signature roasts.
                        </p>
                    </div>
                </div>
                
                <!-- Brewing Equipment category card - Professional tools -->
                <div class="offer-card">
                    <img src="images/equipment_offer.png" alt="Brewing Equipment" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Brewing Equipment</h3>
                        <p style="margin: 0; font-size: 0.9rem;">
                            Professional-grade espresso machines, grinders, and brewing tools that bring 
                            the artisan café experience into your own kitchen.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section - Site information and links -->
    <footer class="footer">
        <div class="container">
            <!-- Footer content grid - Three columns of information -->
            <div class="footer-content">
                <!-- Column 1: Brand and contact information -->
                <div class="footer-section">
                    <div class="footer-brand">
                        <span class="footer-brand-name">PURGE COFFEE</span>
                    </div>
                    <div class="footer-contact">
                        <p><i class="fas fa-phone"></i> 0960 315 0070</p>
                        <p><i class="fas fa-envelope"></i> purgecoffee@gmail.com</p>
                    </div>
                </div>

                <!-- Column 2: Policy links -->
                <div class="footer-section">
                    <h3>OUR POLICIES</h3>
                    <ul class="footer-links">
                        <li><a href="#">Privacy</a></li>
                        <li><a href="#">Terms Of Use</a></li>
                        <li><a href="#">Shipping & Delivery</a></li>
                    </ul>
                </div>

                <!-- Column 3: Social media links -->
                <div class="footer-section">
                    <h3>SOCIAL MEDIA</h3>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>

            <!-- Footer divider - Visual separator -->
            <div class="footer-divider"></div>

            <!-- Copyright notice -->
            <div class="footer-bottom">
                <p>&copy; 2026 Purge Coffee | All Rights Reserved</
    </footer>

    <!-- Bootstrap JavaScript - Required for responsive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Main JavaScript - Core functionality for cart, favorites, and interactions -->
    <script src="js/main.js"></script>
    
</body>
</html>