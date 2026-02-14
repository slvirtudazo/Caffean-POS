<?php
/**
 * Purge Coffee Shop - About Page
 * Coffee shop background story and mission
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
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/about-page.css?v=<?php echo time(); ?>">
</head>
<body>
    
    <div class="top-banner">Shipping Nationwide</div>

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

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">About Purge Coffee</h2>
                <div class="section-divider"></div>
            </div>

            <div class="about-content">
                <div class="row align-items-center mb-5">
                    <div class="col-lg-6">
                        <div class="about-image-wrapper">
                            <img src="images/coffee_mug.png" alt="Purge Coffee" class="about-image">
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <h3 class="about-subtitle">Our Story</h3>
                        <p class="about-text">
                            Founded in 2020, Purge Coffee was born from a simple passion: to serve the richest, 
                            most exceptional coffee in the city. What started as a small neighborhood café has 
                            grown into a beloved destination for coffee enthusiasts who appreciate quality, 
                            craftsmanship, and community.
                        </p>
                        <p class="about-text">
                            Our name "Purge" represents our commitment to pure, unadulterated coffee excellence—
                            purging the ordinary to deliver the extraordinary. Every cup we serve is a testament 
                            to this philosophy, crafted with precision and served with pride.
                        </p>
                    </div>
                </div>

                <div class="row align-items-center mb-5">
                    <div class="col-lg-6 order-lg-2">
                        <div class="about-image-wrapper">
                            <img src="images/coffee_beans_offer.png" alt="Our Coffee" class="about-image">
                        </div>
                    </div>
                    <div class="col-lg-6 order-lg-1">
                        <h3 class="about-subtitle">Our Mission</h3>
                        <p class="about-text">
                            At Purge Coffee, we believe that every cup should be an experience. Our mission is 
                            to source the finest beans from ethical farms around the world, roast them to 
                            perfection, and serve them in an atmosphere that feels like home.
                        </p>
                        <p class="about-text">
                            We're more than just a coffee shop—we're a gathering place where friendships are 
                            formed, ideas are shared, and memories are made. Whether you're grabbing your morning 
                            espresso or settling in for an afternoon with friends, we're here to make every visit 
                            special.
                        </p>
                    </div>
                </div>

                <div class="values-section">
                    <h3 class="about-subtitle text-center mb-4">Our Values</h3>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="value-card">
                                <i class="fas fa-award value-icon"></i>
                                <h4>Quality First</h4>
                                <p>We never compromise on the quality of our beans, equipment, or service. 
                                   Excellence is our standard.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="value-card">
                                <i class="fas fa-leaf value-icon"></i>
                                <h4>Sustainability</h4>
                                <p>We partner with ethical farms and use eco-friendly practices to protect 
                                   our planet for future generations.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="value-card">
                                <i class="fas fa-heart value-icon"></i>
                                <h4>Community</h4>
                                <p>We're proud to be part of this neighborhood, supporting local businesses 
                                   and giving back to our community.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    
</body>
</html>