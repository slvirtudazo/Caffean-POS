<?php
/**
 * Purge Coffee Shop - Supplies Page
 * This page showcases the three main product categories: Coffee Beans, Milk & Creamers, and Brewing Equipment.
 */

require_once 'php/db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Supplies - Purge Coffee</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Base styles -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    
    <!-- Supplies Page Styles -->
    <link rel="stylesheet" href="css/supplies-page.css?v=<?php echo time(); ?>">
    
    <!-- Search Styles -->
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
</head>
<body>

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
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="supplies-page.php">Offers</a>
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

    <!-- Main Supplies Section -->
    <section class="offers-section">
        <div class="container">

            <!-- Section Header -->
            <div class="section-header">
                <h2 class="section-title">What We Offer</h2>
                <p>
                    We provide premium products for café-quality drinks at home.
                </p>
            </div>

            <!-- Offer Cards Grid -->
            <div class="offers-grid">

                <!-- Coffee Beans -->
                <div class="offer-card">
                    <img src="images/coffee_beans_offer.png" alt="Premium Coffee Beans" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Coffee Beans</h3>
                        <p>
                            Sourced from the finest coffee-growing regions, our premium beans are carefully 
                            roasted to perfection for an exceptional brewing experience at home.
                        </p>
                    </div>
                </div>

                <!-- Milk & Creamers -->
                <div class="offer-card">
                    <img src="images/milk_creamer_offer.png" alt="Milk & Creamers" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Milk & Creamers</h3>
                        <p>
                            Complete your coffee with our selection of premium dairy and plant-based options, 
                            each chosen to complement the rich flavors of our signature roasts.
                        </p>
                    </div>
                </div>

                <!-- Brewing Equipment -->
                <div class="offer-card">
                    <img src="images/equipment_offer.png" alt="Brewing Equipment" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Brewing Equipment</h3>
                        <p>
                            Professional-grade espresso machines, grinders, and brewing tools that bring 
                            the artisan café experience into your own kitchen.
                        </p>
                    </div>
                </div>

            </div>

            <!-- Call to Action -->
            <div class="cta-block">
                <h3>Ready to elevate your coffee experience?</h3>
                <p>
                    Visit our café or contact us to learn more about our premium products and services. 
                    Our knowledgeable staff is here to help you find exactly what you need.
                </p>
                <a href="about.php" class="btn-primary">Get In Touch</a>
            </div>

        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/search.js"></script> 
</body>
</html>