<?php

/**
 * Purge Coffee Shop - Contact Page
 * Handles form submission and saves messages to contact_messages table.
 */

require_once 'php/db_connection.php';

$success = '';
$error   = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validate
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Save to database
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO contact_messages (name, email, subject, message)
             VALUES (?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $subject, $message);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Thank you for contacting us! We'll get back to you soon.";
            // Clear fields after successful submission
            $name = $email = $subject = $message = '';
        } else {
            $error = "Something went wrong. Please try again.";
        }
        mysqli_stmt_close($stmt);
    }
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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/search.css">
    <link rel="stylesheet" href="css/contact-page.css">
</head>

<body>
    <!-- Top banner with shipping information -->
    <div class="top-banner">Shipping Nationwide</div>

    <!-- Navigation bar -->
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
                        <a class="nav-link" href="supplies-page.php">Offers</a>
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
                <?php if (isset($_SESSION['user_id'])): ?>
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

    <!-- Contact information section with cards for phone, email, and location -->
    <section class="contact-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Get In Touch</h2>
                <div class="section-divider"></div>
                <p class="text-center" style="max-width: 600px; margin: 0 auto; color: var(--dark-brown);">
                    We'd love to hear from you! Whether you have a question about our menu, want to place a special order,
                    or just want to say hello, our team is ready to answer all your questions.
                </p>
            </div>

            <!-- Contact information cards grid -->
            <div class="contact-grid">
                <!-- Phone contact card -->
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3>Call Us</h3>
                    <p>0960 315 0070</p>
                    <p style="font-size: 0.875rem; margin-top: var(--spacing-xs); opacity: 0.8;">
                        Mon-Sat: 7:00 AM - 9:00 PM<br>
                        Sunday: 8:00 AM - 8:00 PM
                    </p>
                </div>

                <!-- Email contact card -->
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3>Email Us</h3>
                    <p>purgecoffee@gmail.com</p>
                    <p style="font-size: 0.875rem; margin-top: var(--spacing-xs); opacity: 0.8;">
                        We'll respond within 24 hours
                    </p>
                </div>

                <!-- Location contact card -->
                <div class="contact-card">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3>Visit Us</h3>
                    <p>Davao City, Philippines</p>
                    <p style="font-size: 0.875rem; margin-top: var(--spacing-xs); opacity: 0.8;">
                        Come experience our café atmosphere
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact form section -->
    <section class="contact-form-section">
        <div class="container">
            <div class="form-container">
                <h2 style="font-family: var(--font-heading); color: var(--deep-maroon); text-align: center; margin-bottom: var(--spacing-md);">
                    Send Us a Message
                </h2>
                <p style="text-align: center; color: var(--dark-brown); margin-bottom: var(--spacing-xl);">
                    Fill out the form below and we'll get back to you as soon as possible.
                </p>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Contact form with all necessary fields -->
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label class="form-label">Your Name</label>
                        <input type="text" name="name" class="form-control" required
                            placeholder="Enter your full name"
                            value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required
                            placeholder="your.email@example.com"
                            value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" required
                            placeholder="What is this regarding?"
                            value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-textarea" required
                            placeholder="Tell us more about your inquiry..."><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Send Message</button>
                </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/search.js"></script>
</body>

</html>