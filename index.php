<?php

/**
 * Purge Coffee Shop - Homepage
 */

require_once 'php/db_connection.php';

// ── Admin check ───────────────────────────────────────────────
$is_admin      = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_logged_in  = isset($_SESSION['user_id']);
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
    <link rel="stylesheet" href="css/footer-section.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/components.css?v=<?php echo time(); ?>">
</head>

<body class="page-home">

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
                    <li class="nav-item"><a class="nav-link" href="supplies-page.php">Supplies</a></li>
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
                    <p class="hero-label">Locally Crafted</p>
                    <h1 class="hero-title">
                        We serve the richest coffee in Davao!
                    </h1>
                    <?php if ($is_logged_in): ?>
                        <a href="menu.php" class="btn-hero">Order Online</a>
                    <?php else: ?>
                        <button class="btn-hero" onclick="showLoginRequiredPopup()">Order Online</button>
                    <?php endif; ?>
                    <a href="<?= $is_admin ? 'menu.php' : 'kiosk.php' ?>" class="btn-hero-secondary ms-2">Self-Order Kiosk</a>
                </div>
                <div class="col-lg-6 hero-image">
                    <img src="images/coffee_mug.png" alt="Premium Coffee">
                </div>
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
                <a href="menu.php" class="btn-promo">Order Now</a>
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

    <!-- Login Required Popup -->
    <div id="login-required-popup" class="login-popup-overlay" style="display:none;" onclick="closeLoginPopup(event)">
        <div class="login-popup-card">
            <h3 class="login-popup-title">Login Required</h3>
            <p class="login-popup-message">Please log in to manage your<br>cart, favorites, and orders.</p>
            <div class="login-popup-actions">
                <a href="login.php" class="btn-popup-login">Log In</a>
                <button class="btn-popup-close" onclick="document.getElementById('login-required-popup').style.display='none'">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        /* Show the login-required popup */
        function showLoginRequiredPopup() {
            document.getElementById('login-required-popup').style.display = 'flex';
        }
        /* Close popup when clicking outside the card */
        function closeLoginPopup(event) {
            if (event.target === document.getElementById('login-required-popup')) {
                document.getElementById('login-required-popup').style.display = 'none';
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>window.IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;</script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>

</body>

</html>