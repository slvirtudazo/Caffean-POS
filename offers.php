<?php
/**
 * Purge Coffee Shop - Offers Page
 * This page showcases the three main product categories: Coffee Beans, Milk & Creamers, and Brewing Equipment.
 * It provides an elegant visual presentation of what the coffee shop offers beyond regular menu items.
 */

require_once 'php/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Offers - Purge Coffee</title>
    
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    
    <!-- Top banner displaying shipping information -->
    <div class="top-banner">Shipping Nationwide</div>

    <!-- Main navigation bar with branding and menu links -->
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
                        <a class="nav-link" href="coffee.php">Coffee</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="pastry.php">Pastry</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="offers.php">Offers</a>
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

    <!-- Main offers section displaying three product categories -->
    <section class="offers-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">What We Offer</h2>
                <div class="section-divider"></div>
                <p class="text-center" style="max-width: 600px; margin: 0 auto; color: var(--dark-brown);">
                    Beyond our exceptional menu of coffee and pastries, we offer premium products 
                    for coffee enthusiasts who want to create café-quality beverages at home.
                </p>
            </div>

            <!-- Grid displaying three main product categories -->
            <div class="offers-grid">
                <!-- Coffee Beans offer card -->
                <div class="offer-card">
                    <img src="images/coffee_beans_offer.png" alt="Premium Coffee Beans" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Coffee Beans</h3>
                        <p style="margin: 0; font-size: 0.9rem;">
                            Sourced from the finest coffee-growing regions, our premium beans are carefully 
                            roasted to perfection for an exceptional brewing experience at home.
                        </p>
                    </div>
                </div>

                <!-- Milk & Creamers offer card -->
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

                <!-- Brewing Equipment offer card -->
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

            <!-- Call to action section -->
            <div class="text-center mt-5">
                <h3 style="font-family: var(--font-heading); color: var(--deep-maroon); margin-bottom: var(--spacing-md);">
                    Ready to elevate your coffee experience?
                </h3>
                <p style="color: var(--dark-brown); margin-bottom: var(--spacing-lg); max-width: 500px; margin-left: auto; margin-right: auto;">
                    Visit our café or contact us to learn more about our premium products and services. 
                    Our knowledgeable staff is here to help you find exactly what you need.
                </p>
                <a href="contact.php" class="btn-primary">Get In Touch</a>
            </div>
        </div>
    </section>

    <!-- Footer section with company information and links -->
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