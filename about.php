<?php
/**
 * Purge Coffee Shop - About Page
 * This page provides information about the coffee shop, its story,
 * mission, and values. Replaces the Contact page in navigation.
 */

require_once 'php/db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Purge Coffee</title>
    
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/footer-section.css">
    
    <style>
        /* About page specific styles */
        .about-section {
            padding: var(--spacing-xxl) 0;
            background-color: var(--ivory-cream);
        }
        
        .about-hero {
            background: linear-gradient(135deg, var(--deep-maroon) 0%, var(--burgundy-wine) 100%);
            color: var(--ivory-cream);
            padding: var(--spacing-xxl) 0;
            text-align: center;
            margin-bottom: var(--spacing-xxl);
        }
        
        .about-hero h1 {
            font-family: var(--font-heading);
            font-size: 3rem;
            color: var(--ivory-cream);
            margin-bottom: var(--spacing-md);
        }
        
        .about-hero p {
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto;
            opacity: 0.95;
        }
        
        .about-content {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .about-card {
            background: white;
            padding: var(--spacing-xxl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-xl);
        }
        
        .about-card h2 {
            font-family: var(--font-heading);
            color: var(--deep-maroon);
            font-size: 2rem;
            margin-bottom: var(--spacing-lg);
        }
        
        .about-card p {
            color: var(--dark-brown);
            line-height: 1.8;
            font-size: 1.05rem;
            margin-bottom: var(--spacing-md);
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-lg);
            margin-top: var(--spacing-xl);
        }
        
        .value-item {
            text-align: center;
            padding: var(--spacing-lg);
            background: var(--warm-sand);
            border-radius: var(--radius-lg);
            transition: var(--transition-normal);
        }
        
        .value-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .value-icon {
            width: 70px;
            height: 70px;
            background: var(--deep-maroon);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin: 0 auto var(--spacing-md);
        }
        
        .value-item h3 {
            font-family: var(--font-subheading);
            color: var(--deep-maroon);
            margin-bottom: var(--spacing-sm);
        }
        
        .value-item p {
            color: var(--dark-brown);
            font-size: 0.95rem;
            margin: 0;
        }
        
        .contact-cta {
            background: var(--deep-maroon);
            color: white;
            padding: var(--spacing-xxl);
            border-radius: var(--radius-xl);
            text-align: center;
            margin-top: var(--spacing-xxl);
        }
        
        .contact-cta h2 {
            font-family: var(--font-heading);
            font-size: 2rem;
            margin-bottom: var(--spacing-md);
        }
        
        .contact-cta p {
            font-size: 1.125rem;
            margin-bottom: var(--spacing-lg);
            opacity: 0.95;
        }
        
        .contact-info {
            display: flex;
            justify-content: center;
            gap: var(--spacing-xxl);
            flex-wrap: wrap;
            margin-top: var(--spacing-lg);
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        .contact-item i {
            font-size: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .about-hero h1 {
                font-size: 2rem;
            }
            
            .about-card {
                padding: var(--spacing-lg);
            }
            
            .contact-info {
                flex-direction: column;
                gap: var(--spacing-md);
            }
        }
    </style>
</head>
<body>
    
    <!-- Top banner -->
    <div class="top-banner">Shipping Nationwide</div>

    <!-- Navigation -->
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
                        <a class="nav-link" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="offers.php">Offers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About</a>
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
    <div class="about-hero">
        <div class="container">
            <h1>About Purge Coffee</h1>
            <p>Crafting exceptional coffee experiences since 2020. We believe every cup tells a story.</p>
        </div>
    </div>

    <!-- Main About Content -->
    <section class="about-section">
        <div class="container about-content">
            
            <!-- Our Story -->
            <div class="about-card">
                <h2>Our Story</h2>
                <p>
                    Purge Coffee was born from a simple passion: to serve the richest, most flavorful coffee 
                    in the city. What started as a small café in Davao City has grown into a beloved community 
                    gathering place where friends meet, ideas flourish, and every cup is crafted with care.
                </p>
                <p>
                    Our journey began with sourcing the finest coffee beans from local and international growers, 
                    building relationships with farmers who share our commitment to quality and sustainability. 
                    Today, we continue that tradition, bringing you exceptional coffee experiences every single day.
                </p>
            </div>

            <!-- Our Mission -->
            <div class="about-card">
                <h2>Our Mission</h2>
                <p>
                    At Purge Coffee, we're more than just a café—we're a community hub dedicated to bringing people 
                    together over exceptional coffee. Our mission is to create memorable moments, one cup at a time, 
                    while supporting sustainable practices and celebrating the artistry of coffee making.
                </p>
                <p>
                    We strive to provide not just great coffee, but an experience that uplifts your day, sparks 
                    conversation, and creates lasting memories.
                </p>
            </div>

            <!-- Our Values -->
            <div class="about-card">
                <h2>Our Values</h2>
                <div class="values-grid">
                    
                    <!-- Quality -->
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <h3>Quality First</h3>
                        <p>We source only the finest beans and ingredients, ensuring every product meets our high standards.</p>
                    </div>
                    
                    <!-- Community -->
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Community</h3>
                        <p>We're committed to building connections and creating a welcoming space for everyone.</p>
                    </div>
                    
                    <!-- Sustainability -->
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3>Sustainability</h3>
                        <p>We practice environmentally conscious operations and support ethical sourcing.</p>
                    </div>
                    
                    <!-- Craftsmanship -->
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-coffee"></i>
                        </div>
                        <h3>Craftsmanship</h3>
                        <p>Every drink is carefully crafted by our skilled baristas with attention to detail.</p>
                    </div>
                    
                </div>
            </div>

            <!-- Contact Call to Action -->
            <div class="contact-cta">
                <h2>Get In Touch</h2>
                <p>Have questions or want to learn more? We'd love to hear from you!</p>
                
                <div class="contact-info">
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span>0960 315 0070</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>purgecoffee@gmail.com</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Davao City, Philippines</span>
                    </div>
                </div>
                
                <div style="margin-top: var(--spacing-xl);">
                    <a href="menu.php" class="btn-primary" style="background: white; color: var(--deep-maroon);">
                        Explore Our Menu
                    </a>
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
                        <span class="footer-brand-name">PURGE COFFEE</span>
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