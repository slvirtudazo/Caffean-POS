<?php
/**
 * Purge Coffee Shop - About Page
 * Enhanced UI with animations, search fix, and matching footer.
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
    <link rel="stylesheet" href="css/components.css?v=<?php echo time(); ?>"> 
    <link rel="stylesheet" href="css/footer-section.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/about-page.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Top Banner -->
    <div class="top-banner">Shipping Nationwide</div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="supplies-page.php">Offers</a></li>
                    <li class="nav-item"><a class="nav-link active" href="about.php">About</a></li>
                </ul>
            </div>

            <div class="nav-icons">
                <i class="fas fa-search nav-icon" onclick="showSearchOverlay()"></i>
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

    <!-- ═══════════════════════════════════════════
         HERO SECTION
    ═══════════════════════════════════════════ -->
    <section class="about-hero">
        <div class="about-hero-bg"></div>
        <div class="container position-relative">
            <span class="about-hero-label">Our Story</span>
            <h1 class="about-hero-title">Crafted with Passion,<br>Served with Purpose</h1>
            <p class="about-hero-subtitle">
                From bean to cup — every detail matters at Purge Coffee.
            </p>
            <div class="about-hero-scroll">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         STORY SECTION
    ═══════════════════════════════════════════ -->
    <section class="about-story-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6 reveal-left">
                    <span class="section-eyebrow">Who We Are</span>
                    <h2 class="about-subtitle">Born from a Love of Coffee</h2>
                    <p class="about-text">
                        Purge Coffee was founded with one simple belief — that a great cup of coffee can transform your entire day. What began as a small passion project by a group of dedicated baristas has grown into the city's most beloved coffee destination.
                    </p>
                    <p class="about-text">
                        We travel the world to source the finest single-origin beans, working directly with farmers who share our commitment to quality and sustainability. Every roast is carefully crafted in small batches to bring out each bean's unique character and flavor.
                    </p>
                    <p class="about-text">
                        Today, we continue that tradition of ethical sourcing, ensuring every bean we roast not only tastes exceptional but supports sustainable agricultural practices. Whether you're looking for a robust espresso to start your day, a delicate pastry to treat yourself, or a warm space to connect with friends, Purge Coffee is your sanctuary. Welcome to our family.
                    </p>
                    <div class="about-stats">
                        <div class="about-stat">
                            <span class="stat-num">5+</span>
                            <span class="stat-label">Years Brewing</span>
                        </div>
                        <div class="about-stat">
                            <span class="stat-num">12K+</span>
                            <span class="stat-label">Happy Customers</span>
                        </div>
                        <div class="about-stat">
                            <span class="stat-num">30+</span>
                            <span class="stat-label">Signature Drinks</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 reveal-right">
                    <div class="about-image-wrapper">
                        <img src="images/coffee_mug.png" alt="Purge Coffee" class="about-image">
                        <div class="about-image-accent"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         VALUES SECTION
    ═══════════════════════════════════════════ -->
    <section class="about-values-section">
        <div class="container">
            <div class="text-center mb-5 reveal-up">
                <span class="section-eyebrow">What Drives Us</span>
                <h2 class="about-subtitle" style="color: var(--ivory-cream);">Our Core Values</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-4 reveal-up" style="--delay: 0s">
                    <div class="value-card">
                        <div class="value-icon-wrap">
                            <i class="fas fa-leaf value-icon"></i>
                        </div>
                        <h3 class="value-title">Sustainability</h3>
                        <p class="value-text">We are committed to eco-friendly practices, from sourcing our beans to our fully compostable packaging.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal-up" style="--delay: 0.1s">
                    <div class="value-card">
                        <div class="value-icon-wrap">
                            <i class="fas fa-handshake value-icon"></i>
                        </div>
                        <h3 class="value-title">Community</h3>
                        <p class="value-text">We build lasting relationships with our farmers, our team, and every customer who walks through our doors.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal-up" style="--delay: 0.2s">
                    <div class="value-card">
                        <div class="value-icon-wrap">
                            <i class="fas fa-medal value-icon"></i>
                        </div>
                        <h3 class="value-title">Quality</h3>
                        <p class="value-text">We never compromise on quality. Every cup served meets our highest standards of taste and craftsmanship.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         PROCESS / HIGHLIGHTS SECTION
    ═══════════════════════════════════════════ -->
    <section class="about-process-section">
        <div class="container">
            <div class="text-center mb-5 reveal-up">
                <span class="section-eyebrow">From Farm to Cup</span>
                <h2 class="about-subtitle">How We Do It</h2>
            </div>
            <div class="process-grid">
                <div class="process-step reveal-up" style="--delay: 0s">
                    <div class="process-num">01</div>
                    <div class="process-icon"><i class="fas fa-globe-asia"></i></div>
                    <h4 class="process-title">Ethical Sourcing</h4>
                    <p class="process-text">We partner directly with farmers in the best coffee-growing regions, ensuring fair trade and premium quality.</p>
                </div>
                <div class="process-connector"></div>
                <div class="process-step reveal-up" style="--delay: 0.15s">
                    <div class="process-num">02</div>
                    <div class="process-icon"><i class="fas fa-fire"></i></div>
                    <h4 class="process-title">Small-Batch Roasting</h4>
                    <p class="process-text">Each batch is roasted in-house with precision, unlocking the unique flavors locked inside every bean.</p>
                </div>
                <div class="process-connector"></div>
                <div class="process-step reveal-up" style="--delay: 0.3s">
                    <div class="process-num">03</div>
                    <div class="process-icon"><i class="fas fa-mug-hot"></i></div>
                    <h4 class="process-title">Expert Brewing</h4>
                    <p class="process-text">Our skilled baristas brew every cup to order, making sure each sip is exactly as it should be — perfect.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         CTA BANNER
    ═══════════════════════════════════════════ -->
    <section class="about-cta-section">
        <div class="container text-center reveal-up">
            <h2 class="about-cta-title">Ready to Experience the Difference?</h2>
            <p class="about-cta-text">Explore our menu and find your next favorite brew.</p>
            <a href="menu.php" class="btn-primary about-cta-btn">View Our Menu</a>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         FOOTER — Matches Home Page
    ═══════════════════════════════════════════ -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <!-- Brand & Contact -->
                <div class="footer-section">
                    <div class="footer-brand">
                        <span class="footer-brand-name">PURGE COFFEE</span>
                    </div>
                    <div class="footer-contact">
                        <p><i class="fas fa-phone"></i> 0960 315 0070</p>
                        <p><i class="fas fa-envelope"></i> purgecoffee@gmail.com</p>
                    </div>
                </div>

                <!-- Policies -->
                <div class="footer-section">
                    <h3>OUR POLICIES</h3>
                    <ul class="footer-links">
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms Of Use</a></li>
                        <li><a href="#">Shipping &amp; Delivery</a></li>
                    </ul>
                </div>

                <!-- Social Media -->
                <div class="footer-section">
                    <h3>SOCIAL MEDIA</h3>
                    <div class="social-icons">
                        <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>

            <hr>

            <div class="footer-bottom">
                <p>&copy; 2026 Purge Coffee | All Rights Reserved</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/search.js"></script>

    <!-- Scroll Reveal Animations -->
    <script>
    (function () {
        const revealEls = document.querySelectorAll('.reveal-up, .reveal-left, .reveal-right');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(el => {
                if (el.isIntersecting) {
                    const delay = el.target.style.getPropertyValue('--delay') || '0s';
                    el.target.style.transitionDelay = delay;
                    el.target.classList.add('revealed');
                    observer.unobserve(el.target);
                }
            });
        }, { threshold: 0.15 });

        revealEls.forEach(el => observer.observe(el));

        // Scroll chevron in hero
        const chevron = document.querySelector('.about-hero-scroll');
        if (chevron) {
            chevron.addEventListener('click', () => {
                document.querySelector('.about-story-section').scrollIntoView({ behavior: 'smooth' });
            });
        }
    })();
    </script>
</body>
</html>