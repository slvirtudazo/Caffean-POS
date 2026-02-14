<?php
/**
 * Purge Coffee Shop - Contact Page
 * Complete contact information and contact form
 */

require_once 'php/db_connection.php';

// Handle contact form submission
$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    // In a real application, you would send an email or save to database
    $message_sent = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Purge Coffee</title>
    
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/contact-page.css?v=<?php echo time(); ?>">
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
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="contact.php">Contact</a>
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

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Contact Us</h2>
                <div class="section-divider"></div>
                <p class="section-subtitle">We'd love to hear from you! Reach out with any questions or feedback.</p>
            </div>

            <div class="row g-5">
                <!-- Contact Information -->
                <div class="col-lg-5">
                    <div class="contact-info-wrapper">
                        <h3 class="contact-info-title">Get In Touch</h3>
                        
                        <div class="contact-info-item" data-testid="contact-phone">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Phone</h4>
                                <p>0960 315 0070</p>
                                <p class="text-muted">Mon-Sat: 8AM - 8PM</p>
                            </div>
                        </div>

                        <div class="contact-info-item" data-testid="contact-email">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Email</h4>
                                <p>purgecoffee@gmail.com</p>
                                <p class="text-muted">We'll reply within 24 hours</p>
                            </div>
                        </div>

                        <div class="contact-info-item" data-testid="contact-address">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-details">
                                <h4>Location</h4>
                                <p>123 Coffee Street, Downtown</p>
                                <p class="text-muted">Manila, Philippines</p>
                            </div>
                        </div>

                        <div class="social-links">
                            <h4>Follow Us</h4>
                            <div class="social-icons-contact">
                                <a href="#" class="social-link" data-testid="social-facebook"><i class="fab fa-facebook"></i></a>
                                <a href="#" class="social-link" data-testid="social-instagram"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-link" data-testid="social-twitter"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="col-lg-7">
                    <div class="contact-form-wrapper">
                        <h3 class="contact-form-title">Send Us A Message</h3>
                        
                        <?php if($message_sent): ?>
                            <div class="alert alert-success" role="alert" data-testid="success-message">
                                <i class="fas fa-check-circle"></i> Thank you for your message! We'll get back to you soon.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" class="contact-form" data-testid="contact-form">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Full Name *</label>
                                        <input type="text" id="name" name="name" class="form-control" required data-testid="input-name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address *</label>
                                        <input type="email" id="email" name="email" class="form-control" required data-testid="input-email">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" class="form-control" data-testid="input-phone">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="subject">Subject *</label>
                                        <input type="text" id="subject" name="subject" class="form-control" required data-testid="input-subject">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="message">Message *</label>
                                        <textarea id="message" name="message" class="form-control" rows="6" required data-testid="input-message"></textarea>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="submit_contact" class="btn-primary" data-testid="submit-button">
                                        <i class="fas fa-paper-plane"></i> Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    
</body>
</html>