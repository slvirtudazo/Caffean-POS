<?php
/**
 * Purge Coffee Shop - Homepage
 * This is the main landing page that welcomes visitors with an elegant presentation
 * of the coffee shop's offerings, including hero section, best seller products, and special offers.
 * Best Sellers are calculated dynamically based on favorites, cart additions, and orders.
 */

// Include database configuration
require_once 'php/config.php';

// Fetch best seller products based on user interactions
// Products are ranked by: orders (weight 3), cart additions (weight 2), and favorites (weight 1)
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
LIMIT 4";

$bestsellers_result = mysqli_query($conn, $bestsellers_query);

// If no best sellers yet (new system), show sample products: 1 coffee and 1 pastry
$show_samples = false;
if (mysqli_num_rows($bestsellers_result) == 0) {
    $show_samples = true;
    // Get 1 coffee product (categories 1, 2, or 4)
    $sample_coffee_query = "SELECT p.*, c.name as category_name 
                            FROM products p 
                            JOIN categories c ON p.category_id = c.category_id 
                            WHERE p.category_id IN (1, 2, 4) AND p.status = 1 
                            ORDER BY p.created_at DESC 
                            LIMIT 1";
    $coffee_result = mysqli_query($conn, $sample_coffee_query);
    
    // Get 1 pastry product (category 3)
    $sample_pastry_query = "SELECT p.*, c.name as category_name 
                            FROM products p 
                            JOIN categories c ON p.category_id = c.category_id 
                            WHERE p.category_id = 3 AND p.status = 1 
                            ORDER BY p.created_at DESC 
                            LIMIT 1";
    $pastry_result = mysqli_query($conn, $sample_pastry_query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purge Coffee - The Richest Coffee in the City</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    
    <!-- Top Banner -->
    <div class="top-banner">
        Shipping Nationwide
    </div>

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
                        <a class="nav-link" href="coffee.php">Coffee</a>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <p class="hero-label">Welcome</p>
                    <h1 class="hero-title">We serve the richest coffee in the city!</h1>
                    <p class="hero-description">
                        Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
                    </p>
                    <a href="coffee.php" class="btn-primary">Order Now</a>
                </div>
                <div class="col-lg-6 hero-image">
                    <img src="images/coffee_mug.png" alt="Premium Coffee">
                </div>
            </div>
        </div>
    </section>

    <!-- Best Sellers Section - Dynamic based on user interactions -->
    <section class="menu-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Best Sellers</h2>
                <div class="section-divider"></div>
                <p style="text-align: center; color: var(--dark-brown); margin-top: var(--spacing-md);">
                    Our customers' favorites - tried, loved, and highly recommended!
                </p>
            </div>

            <div class="product-grid">
                <?php 
                if ($show_samples):
                    // Display sample products (1 coffee, 1 pastry) for new systems
                    if (mysqli_num_rows($coffee_result) > 0):
                        $product = mysqli_fetch_assoc($coffee_result);
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
                            <p class="product-description">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?>
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
                    endif;
                    
                    if (mysqli_num_rows($pastry_result) > 0):
                        $product = mysqli_fetch_assoc($pastry_result);
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
                            <p class="product-description">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?>
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
                    endif;
                else:
                    // Display actual best sellers based on interactions
                    while($product = mysqli_fetch_assoc($bestsellers_result)): 
                        $image_path = 'images/coffee.png';
                        if($product['category_id'] == 3) {
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
                            <p class="product-description">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?>
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
                endif;
                
                // If no products at all
                if ($show_samples && mysqli_num_rows($coffee_result) == 0 && mysqli_num_rows($pastry_result) == 0):
                ?>
                    <p class="text-center">No products available at the moment.</p>
                <?php endif; ?>
            </div>

            <div class="text-center mt-4">
                <a href="coffee.php" class="btn-secondary">View Full Menu</a>
            </div>
        </div>
    </section>

    <!-- What We Offer Section -->
    <section class="offers-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">What We Offer</h2>
                <div class="section-divider"></div>
            </div>

            <div class="offers-grid">
                <div class="offer-card">
                    <img src="images/coffee_beans_offer.png" alt="Coffee Beans" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Coffee Beans</h3>
                    </div>
                </div>
                <div class="offer-card">
                    <img src="images/milk_creamer_offer.png" alt="Milk & Creamers" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Milk & Creamers</h3>
                    </div>
                </div>
                <div class="offer-card">
                    <img src="images/equipment_offer.png" alt="Brewing Equipment" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Brewing Equipment</h3>
                    </div>
                </div>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="js/main.js"></script>
    
</body>
</html>