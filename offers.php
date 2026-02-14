<?php
/**
 * Purge Coffee Shop - Offers Page
 * Display coffee beans, milk & creamers, and brewing equipment with descriptions
 */

require_once 'php/db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offers - Purge Coffee</title>
    
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
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
                        <a class="nav-link active" href="offers.php">Offers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
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

    <!-- What We Offer Section -->
    <section class="offers-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">What We Offer</h2>
                <div class="section-divider"></div>
            </div>

            <div class="offers-grid">
                <!-- Coffee Beans -->
                <div class="offer-card" data-testid="offer-coffee-beans">
                    <img src="images/coffee_beans_offer.png" alt="Coffee Beans" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Coffee Beans</h3>
                        <p class="offer-description">
                            Sourced from the finest coffee-growing regions around the world, our premium beans 
                            are carefully roasted to perfection. Each batch is crafted to bring out unique flavor 
                            profiles, from bold and robust to smooth and mellow. Whether you prefer a dark roast 
                            for its rich intensity or a light roast for its delicate notes, our selection offers 
                            an exceptional brewing experience at home.
                        </p>
                    </div>
                </div>
                
                <!-- Milk & Creamers -->
                <div class="offer-card" data-testid="offer-milk-creamers">
                    <img src="images/milk_creamer_offer.png" alt="Milk & Creamers" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Milk & Creamers</h3>
                        <p class="offer-description">
                            Complete your coffee with our selection of premium dairy and plant-based options. 
                            From whole milk and half-and-half to oat, almond, and soy alternatives, each choice 
                            is carefully selected to complement the rich flavors of our signature roasts. Our 
                            creamers add the perfect touch of sweetness and texture, creating a barista-quality 
                            experience in every cup.
                        </p>
                    </div>
                </div>
                
                <!-- Brewing Equipment -->
                <div class="offer-card" data-testid="offer-brewing-equipment">
                    <img src="images/equipment_offer.png" alt="Brewing Equipment" class="offer-image">
                    <div class="offer-overlay">
                        <h3 class="offer-title">Brewing Equipment</h3>
                        <p class="offer-description">
                            Professional-grade espresso machines, precision grinders, and artisan brewing tools 
                            that bring the authentic café experience into your own kitchen. Our equipment range 
                            includes everything from manual pour-over sets for the traditional enthusiast to 
                            automated espresso machines for convenient luxury. Each piece is selected for its 
                            quality, durability, and ability to extract the perfect cup every time.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    
</body>
</html>