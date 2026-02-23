<?php

/**
 * Purge Coffee Shop - Enhanced Homepage
 */

require_once 'php/db_connection.php';

// ── Admin check ───────────────────────────────────────────────
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Fetch top 6 best sellers
$bestsellers_query = "SELECT
    p.product_id, p.name, p.description, p.price, p.category_id,
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

$show_samples = false;
if (mysqli_num_rows($bestsellers_result) == 0) {
    $show_samples = true;
    $coffee_result = mysqli_query(
        $conn,
        "SELECT p.*, c.name as category_name
         FROM products p JOIN categories c ON p.category_id = c.category_id
         WHERE p.category_id IN (1, 2, 4) AND p.status = 1
         ORDER BY p.created_at DESC LIMIT 3"
    );
    $pastry_result = mysqli_query(
        $conn,
        "SELECT p.*, c.name as category_name
         FROM products p JOIN categories c ON p.category_id = c.category_id
         WHERE p.category_id = 7 AND p.status = 1
         ORDER BY p.created_at DESC LIMIT 3"
    );
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purge Coffee - Home</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/buttons.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/home-bestsellers.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/footer-section.css?v=<?php echo time(); ?>">
    <style>
        /* Product category label below product name */
        .product-category {
            font-family: var(--font-subheading);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--burgundy-wine);
            opacity: 0.75;
            margin: -4px 0 8px 0;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Purge Coffee">
                <span>purge coffee</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="supplies-page.php">Offers</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                </ul>
            </div>
            <div class="nav-icons">
                <i class="fas fa-search nav-icon" onclick="showSearchOverlay()"></i>
                <?php if (!$is_admin): ?>
                    <a href="cart.php" class="text-decoration-none">
                        <i class="fas fa-shopping-cart nav-icon"></i>
                    </a>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $is_admin ? 'admin/dashboard.php' : 'account.php'; ?>" class="text-decoration-none">
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
                    <p class="hero-description">Experience the perfect blend of premium beans and expert craftsmanship in every cup we brew</p>
                    <a href="menu.php" class="btn-primary">Order Now</a>
                </div>
                <div class="col-lg-6 hero-image">
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
                <p class="section-subtitle">Our most popular drinks and treats!</p>
            </div>

            <div class="product-grid">
                <?php if ($show_samples): ?>
                    <?php while ($product = mysqli_fetch_assoc($coffee_result)): ?>
                        <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>">
                            <div class="product-image-wrapper">
                                <div class="favorite-icon" onclick="toggleFavorite(<?php echo $product['product_id']; ?>, this.querySelector('i'))">
                                    <i class="far fa-heart"></i>
                                </div>
                                <img src="images/coffee.png" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="product-footer">
                                    <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                    <?php if (!$is_admin): ?>
                                        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['product_id']; ?>, this)">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <?php while ($product = mysqli_fetch_assoc($pastry_result)): ?>
                        <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>">
                            <div class="product-image-wrapper">
                                <div class="favorite-icon" onclick="toggleFavorite(<?php echo $product['product_id']; ?>, this.querySelector('i'))">
                                    <i class="far fa-heart"></i>
                                </div>
                                <img src="images/pastry.png" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="product-footer">
                                    <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                    <?php if (!$is_admin): ?>
                                        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['product_id']; ?>, this)">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                <?php else: ?>
                    <?php while ($product = mysqli_fetch_assoc($bestsellers_result)):
                        $image_path = ($product['category_id'] == 7) ? 'images/pastry.png' : 'images/coffee.png';
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
                                <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="product-footer">
                                    <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                    <?php if (!$is_admin): ?>
                                        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['product_id']; ?>, this)">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

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
                <div class="offer-promo-card">
                    <div class="promo-icon"><i class="fas fa-gift"></i></div>
                    <h3 class="promo-title">Buy 2 Get 1 Free</h3>
                    <p class="promo-description">Purchase any two coffee drinks and get the third one absolutely free. This offer is valid for all regular-sized drinks.</p>
                </div>
                <div class="offer-promo-card">
                    <div class="promo-icon"><i class="fas fa-graduation-cap"></i></div>
                    <h3 class="promo-title">Student Discount</h3>
                    <p class="promo-description">Students receive 15% off on all orders. Simply present your valid student ID at checkout to claim the discount.</p>
                </div>
                <div class="offer-promo-card">
                    <div class="promo-icon"><i class="fas fa-truck"></i></div>
                    <h3 class="promo-title">Free Delivery Over ₱500</h3>
                    <p class="promo-description">Enjoy free nationwide delivery on orders above ₱500. Experience fast and reliable shipping straight to your doorstep.</p>
                </div>
            </div>
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
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-seedling"></i></div>
                    <h3 class="feature-title">Premium Beans</h3>
                    <p class="feature-description">We source the finest, ethically grown coffee beans from well-known regions worldwide. Each bean is carefully selected by our team. This ensures rich flavor and consistent quality in every cup.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-leaf"></i></div>
                    <h3 class="feature-title">Fresh Ingredients</h3>
                    <p class="feature-description">Every ingredient is carefully chosen and freshly prepared each day. We follow strict standards to maintain freshness and safety. This ensures that every order reflects our commitment to quality.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shipping-fast"></i></div>
                    <h3 class="feature-title">Fast Nationwide Delivery</h3>
                    <p class="feature-description">We provide fast and reliable delivery service across the Philippines. Our system keeps your order processed efficiently. You can track your order at every stage for complete convenience.</p>
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
                <div class="location-details">
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="detail-text">
                            <h4>Address</h4>
                            <p>123 Tulip Drive, Matina<br>Davao City 8000, Philippines</p>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-clock"></i></div>
                        <div class="detail-text">
                            <h4>Operating Hours</h4>
                            <p>Monday–Friday: 8:00 AM – 10:00 PM<br>Saturday–Sunday: 10:00 AM – 8:00 PM</p>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-icon"><i class="fas fa-phone"></i></div>
                        <div class="detail-text">
                            <h4>Contact</h4>
                            <p>+63 912 345 6789</p>
                        </div>
                    </div>
                </div>
                <div class="location-map">
                    <div class="map-placeholder">
                        <i class="fas fa-map-marked-alt"></i>
                        <p>Map coming soon</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <?php include 'includes/search-overlay.php'; ?>
</body>

</html>