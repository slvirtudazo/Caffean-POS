<?php
/**
 * Purge Coffee Shop - Offers Page
 * This page showcases the three main product categories: Coffee Beans, Milk & Creamers, and Brewing Equipment.
 * Updated to remove decorative coffee bean logo for cleaner design
 */

require_once 'php/db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Offers - Purge Coffee</title>
    
    <!-- Favicon - Site icon displayed in browser tab -->
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    
    <!-- Bootstrap CSS - Responsive grid and component framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome - Icon library for UI elements -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Base styles - Core CSS variables and global styles -->
    <link rel="stylesheet" href="css/style.css">
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
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="offers.php">Offers</a>
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

    <!-- Main offers section - Product categories showcase -->
    <section class="offers-section">
        <div class="container">
            <!-- Section header - Title without decorative coffee bean logo -->
            <div class="section-header">
                <h2 class="section-title">What We Offer</h2>
                <!-- REMOVED: Coffee bean logo divider for cleaner, modern look -->
                
                <!-- Introductory description text -->
                <p class="text-center" style="max-width: 600px; margin: 0 auto; color: var(--dark-brown);">
                    Beyond our exceptional menu of coffee and pastries, we offer premium products 
                    for coffee enthusiasts who want to create café-quality beverages at home.
                </p>
            </div>

            <!-- Grid displaying three main product categories -->
            <div class="offers-grid">
                <!-- Coffee Beans category card - Premium beans offering -->
                <div class="offer-card">
                    <!-- Category image - Background for the card -->
                    <img src="images/coffee_beans_offer.png" alt="Premium Coffee Beans" class="offer-image">
                    
                    <!-- Overlay with gradient background - Contains category information -->
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

            <!-- Call to action section - Encourages visitor engagement -->
            <div class="text-center mt-5">
                <h3 style="font-family: var(--font-heading); color: var(--deep-maroon); margin-bottom: var(--spacing-md);">
                    Ready to elevate your coffee experience?
                </h3>
                <p style="color: var(--dark-brown); margin-bottom: var(--spacing-lg); max-width: 500px; margin-left: auto; margin-right: auto;">
                    Visit our café or contact us to learn more about our premium products and services. 
                    Our knowledgeable staff is here to help you find exactly what you need.
                </p>
                <!-- CTA button - Links to About page with contact information -->
                <a href="about.php" class="btn-primary">Get In Touch</a>
            </div>
        </div>
    </section>

    <!-- Bootstrap JavaScript - Required for responsive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Main JavaScript - Core functionality for cart, favorites, and interactions -->
    <script src="js/main.js"></script>
    
</body>
</html>