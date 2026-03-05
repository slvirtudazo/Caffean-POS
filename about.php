<?php

/**
 * Purge Coffee Shop - About Page
 */
require_once 'php/db_connection.php';
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purge Coffee - About Page</title>

    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/about-page.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
</head>

<body class="page-about">

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
                    <li class="nav-item"><a class="nav-link" href="supplies-page.php">Supplies</a></li>
                    <li class="nav-item"><a class="nav-link active" href="about.php">About</a></li>
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
            <span class="about-hero-label">
                Our Story
            </span>
            <h1 class="about-hero-title">
                Our First Leap, Your Best Sip
            </h1>
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
                    <span class="section-eyebrow">From Our Kitchen to Your Favorite Cup</span>
                    <h2 class="about-subtitle">A Shared Dream, Freshly Brewed</h2>
                    <p class="about-text">
                        Purge Coffee started as a late-night family dream brewed right in our own kitchen.
                        Our first business venture took that dining table idea and transformed it into a
                        welcoming space to share our love of coffee with the community.
                    </p>
                    <p class="about-text">
                        Driven to serve only the best, we hand-select our premium beans from ethical farms
                        focused on sustainability. Each single-origin roast receives the meticulous,
                        dedicated care that only a passionate family business can offer.
                    </p>
                    <p class="about-text">
                        Today, our doors are wide open, and our espresso machines are humming.
                        Whether you're stopping by for a robust morning pick-me-up, a delicate
                        pastry, or just a cozy spot to connect with friends, we want you to feel
                        right at home. From our family to yours—welcome to Purge Coffee.
                    </p>
                    <div class="about-stats">
                        <div class="about-stat">
                            <span class="stat-num">2+</span>
                            <span class="stat-label">Years Brewing</span>
                        </div>
                        <div class="about-stat">
                            <span class="stat-num">1</span>
                            <span class="stat-label">Big Family Leap</span>
                        </div>
                        <div class="about-stat">
                            <span class="stat-num">20+</span>
                            <span class="stat-label">Handcrafted Recipes</span>
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
                <h2 class="about-subtitle">Our Core Values</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-4 reveal-up" style="--delay: 0s">
                    <div class="value-card">
                        <div class="value-icon-wrap">
                            <i class="fas fa-leaf value-icon"></i>
                        </div>
                        <h3 class="value-title">Sustainability</h3>
                        <p class="value-text">
                            We keep our community green through eco-friendly
                            brewing and fully compostable packaging.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 reveal-up" style="--delay: 0.1s">
                    <div class="value-card">
                        <div class="value-icon-wrap">
                            <i class="fas fa-handshake value-icon"></i>
                        </div>
                        <h3 class="value-title">Community</h3>
                        <p class="value-text">
                            We build genuine relationships from farm to cup,
                            welcoming you to our family's table with every visit.
                        </p>
                    </div>
                </div>
                <div class="col-md-4 reveal-up" style="--delay: 0.2s">
                    <div class="value-card">
                        <div class="value-icon-wrap">
                            <i class="fas fa-medal value-icon"></i>
                        </div>
                        <h3 class="value-title">Quality</h3>
                        <p class="value-text">
                            Crafted to strict homegrown standards.
                            Pouring uncompromised quality, from our family to yours.
                        </p>
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
                    <div class="process-icon"><i class="fas fa-globe-asia"></i></div>
                    <h4 class="process-title">Ethical Sourcing</h4>
                    <p class="process-text">
                        We partner directly with farming families, ensuring fair
                        trade practices that help their communities thrive.
                    </p>
                </div>
                <div class="process-step reveal-up" style="--delay: 0.15s">
                    <div class="process-icon"><i class="fas fa-fire"></i></div>
                    <h4 class="process-title">Small-Batch Roasting</h4>
                    <p class="process-text">
                        We roast in small, focused batches to unlock the truest, homegrown flavor of every bean.
                    </p>
                </div>
                <div class="process-step reveal-up" style="--delay: 0.3s">
                    <div class="process-icon"><i class="fas fa-mug-hot"></i></div>
                    <h4 class="process-title">Expert Brewing</h4>
                    <p class="process-text">We pour our hearts into every cup, ensuring your first sip feels exactly like a warm welcome home.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════════════════════════════════════════
         CTA BANNER
    ═══════════════════════════════════════════ -->
    <section class="about-cta-section">
        <div class="container text-center reveal-up">
            <h2 class="about-cta-title">Come Taste What We've Been Brewing</h2>
            <p class="about-cta-text">Explore our family's menu and find your new favorite daily ritual.</p>
            <a href="menu.php" class="about-cta-btn">View All Menu</a>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>window.IS_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;</script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>

    <!-- Scroll Reveal Animations -->
    <script>
        (function() {
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
            }, {
                threshold: 0.15
            });

            revealEls.forEach(el => observer.observe(el));

            // Scroll chevron in hero
            const chevron = document.querySelector('.about-hero-scroll');
            if (chevron) {
                chevron.addEventListener('click', () => {
                    document.querySelector('.about-story-section').scrollIntoView({
                        behavior: 'smooth'
                    });
                });
            }
        })();
    </script>
</body>

</html>