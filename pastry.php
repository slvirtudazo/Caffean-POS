<?php

/**
 * Purge Coffee Shop - Pastry Menu Page
 */
require_once 'php/db_connection.php';

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

$items_per_page  = 4;
$current_page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset          = ($current_page - 1) * $items_per_page;

$count_result    = mysqli_query(
    $conn,
    "SELECT COUNT(*) as total FROM products WHERE category_id = 3 AND status = 1"
);
$total_products  = mysqli_fetch_assoc($count_result)['total'];
$total_pages     = ceil($total_products / $items_per_page);

$products_result = mysqli_query(
    $conn,
    "SELECT p.*, c.name as category_name
     FROM products p JOIN categories c ON p.category_id = c.category_id
     WHERE p.category_id = 3 AND p.status = 1
     ORDER BY p.name
     LIMIT $items_per_page OFFSET $offset"
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purge Coffee - Pastry Menu</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/pastry-page.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/footer-section.css?v=<?php echo time(); ?>">
    <style>
        .product-category {
            font-family: var(--font-subheading);
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--burgundy-wine);
            opacity: 0.75;
            margin: -4px 0 8px 0;
        }
    </style>
</head>

<body>

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
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="coffee.php">Coffee</a></li>
                    <li class="nav-item"><a class="nav-link active" href="pastry.php">Pastry</a></li>
                    <li class="nav-item"><a class="nav-link" href="supplies-page.php">Supplies</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
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

    <section class="menu-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Pastry Menu</h2>
                <div class="section-divider"></div>
            </div>
            <div class="product-grid">
                <?php if (mysqli_num_rows($products_result) > 0):
                    while ($product = mysqli_fetch_assoc($products_result)): ?>
                        <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>">
                            <div class="product-image-wrapper">
                                <div class="favorite-icon" onclick="toggleFavorite(<?php echo $product['product_id']; ?>, this.querySelector('i'))">
                                    <i class="far fa-heart"></i>
                                </div>
                                <img src="images/pastry.png" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            </div>
                            <div class="product-info">
                                <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <div class="product-footer">
                                    <span class="product-price">₱ <?php echo number_format($product['price'], 2); ?></span>
                                    <?php if (!$is_admin): ?>
                                        <button class="btn-order" onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                            Order Now
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile;
                else: ?>
                    <p class="text-center">No pastry products available at the moment.</p>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="carousel-controls">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?>" class="carousel-arrow">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    <div class="pagination-dots">
                        <?php for ($i = 1; $i <= min(5, $total_pages); $i++): ?>
                            <a href="?page=<?php echo $i; ?>"
                                class="pagination-dot <?php echo $i == $current_page ? 'active' : ''; ?>"></a>
                        <?php endfor; ?>
                    </div>
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>" class="carousel-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
</body>

</html>