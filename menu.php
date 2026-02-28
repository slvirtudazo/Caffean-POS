<?php

/**
 * Purge Coffee Shop - Menu Page
 * Comprehensive menu displaying all products with filtering by category
 * and sorting by price or popularity (Best Sellers)
 */

require_once 'php/db_connection.php';

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Get filter parameters from URL
$category_filter   = isset($_GET['category'])    ? intval($_GET['category'])   : 0;
$price_sort        = isset($_GET['price_sort'])  ? $_GET['price_sort']         : '';   // 'low' | 'high'
$show_popular      = isset($_GET['popular'])     && $_GET['popular']     == '1';

// Validate price_sort value
if (!in_array($price_sort, ['low', 'high'])) $price_sort = '';

// Build SQL WHERE clauses
$where_clauses = ["p.status = 1"];

if ($category_filter > 0) {
    $where_clauses[] = "p.category_id = $category_filter";
}

// Build ORDER BY — price_sort takes precedence over popular
$popularity_expr = "(
    COALESCE((SELECT COUNT(*) FROM order_items oi
              JOIN orders o ON oi.order_id = o.order_id
              WHERE oi.product_id = p.product_id AND o.status = 'completed'), 0) * 3 +
    COALESCE((SELECT SUM(interaction_count) FROM product_interactions
              WHERE product_id = p.product_id AND interaction_type = 'add_to_cart'), 0) * 2 +
    COALESCE((SELECT SUM(interaction_count) FROM product_interactions
              WHERE product_id = p.product_id AND interaction_type = 'favorite'), 0) * 1
)";

if ($price_sort === 'low') {
    $order_by = "p.price ASC";
} elseif ($price_sort === 'high') {
    $order_by = "p.price DESC";
} elseif ($show_popular) {
    $order_by = "$popularity_expr DESC";
} else {
    $order_by = "p.category_id, p.name";
}

// Construct final query
$where_string    = implode(" AND ", $where_clauses);
$products_query  = "SELECT p.*, c.name as category_name
                    FROM products p
                    JOIN categories c ON p.category_id = c.category_id
                    WHERE $where_string
                    ORDER BY $order_by";
$products_result = mysqli_query($conn, $products_query);

// Fetch categories for sidebar
$categories_query  = "SELECT * FROM categories ORDER BY category_id";
$categories_result = mysqli_query($conn, $categories_query);

// Total product count
$total_products = mysqli_num_rows($products_result);

/**
 * Helper: build a URL preserving current params, with overrides and removals.
 */
function buildFilterUrl($overrides = [], $removals = [])
{
    $keys   = ['category', 'price_sort', 'popular'];
    $params = [];
    foreach ($keys as $k) {
        if (isset($_GET[$k]) && $_GET[$k] !== '') {
            $params[$k] = $_GET[$k];
        }
    }
    foreach ($overrides as $k => $v) {
        $params[$k] = $v;
    }
    foreach ($removals as $k) {
        unset($params[$k]);
    }
    return 'menu.php' . ($params ? '?' . http_build_query($params) : '');
}

$has_active_filters = ($category_filter > 0 || $price_sort !== '' || $show_popular);

// Resolve category name
$cat_name = '';
if ($category_filter > 0) {
    $res = mysqli_query($conn, "SELECT name FROM categories WHERE category_id = $category_filter");
    if ($res) $cat_name = mysqli_fetch_assoc($res)['name'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purge Coffee - Menu Page</title>

    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/menu-page.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>"></head>

<body>

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
                    <li class="nav-item"><a class="nav-link active" href="menu.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="supplies-page.php">Supplies</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
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

    <section class="menu-main-section">
        <div class="container-fluid">
            <div class="row menu-layout-row">

                <div class="menu-sidebar">
                    <div class="filter-panel" id="filterPanel">

                        <div class="filter-section">
                            <h3 class="filter-title">
                                <i class="fas fa-list-ul"></i> Categories
                            </h3>
                            <div class="category-list">
                                <a href="<?php echo buildFilterUrl([], ['category']); ?>"
                                    class="category-item <?php echo $category_filter == 0 ? 'active' : ''; ?>">
                                    <span class="category-icon"><i class="fas fa-th"></i></span>
                                    <span class="category-name">All Categories</span>
                                    <span class="category-count">
                                        <?php
                                        $all_count = mysqli_fetch_assoc(
                                            mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE status = 1")
                                        )['total'];
                                        echo $all_count;
                                        ?>
                                    </span>
                                </a>

                                <?php
                                $category_icons = [
                                    1 => 'fa-mug-hot',
                                    2 => 'fa-glass-water',
                                    3 => 'fa-cup-straw',
                                    4 => 'fa-ice-cream',
                                    5 => 'fa-leaf',
                                    6 => 'fa-cake-candles',
                                    7 => 'fa-bread-slice',
                                    8 => 'fa-burger',
                                    9 => 'fa-plus-circle'
                                ];

                                mysqli_data_seek($categories_result, 0);
                                while ($category = mysqli_fetch_assoc($categories_result)):
                                    $cat_id        = $category['category_id'];
                                    $product_count = mysqli_fetch_assoc(
                                        mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE category_id = $cat_id AND status = 1")
                                    )['count'];
                                    $icon          = $category_icons[$cat_id] ?? 'fa-circle';
                                    $cat_url       = buildFilterUrl(['category' => $cat_id], []);
                                ?>
                                    <a href="<?php echo $cat_url; ?>"
                                        class="category-item <?php echo $category_filter == $cat_id ? 'active' : ''; ?>">
                                        <span class="category-icon"><i class="fas <?php echo $icon; ?>"></i></span>
                                        <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                        <span class="category-count"><?php echo $product_count; ?></span>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <div class="filter-section">
                            <h3 class="filter-title">
                                <i class="fas fa-sort-amount-down"></i> Sort By
                            </h3>
                            <div class="sort-options">
                                <div class="sort-item <?php echo $price_sort === 'low' ? 'active' : ''; ?>"
                                    data-sort-param="price_sort"
                                    data-sort-value="low">
                                    <i class="fas fa-arrow-down"></i> Price: Low to High
                                </div>
                                <div class="sort-item <?php echo $price_sort === 'high' ? 'active' : ''; ?>"
                                    data-sort-param="price_sort"
                                    data-sort-value="high">
                                    <i class="fas fa-arrow-up"></i> Price: High to Low
                                </div>
                                <div class="sort-item <?php echo $show_popular ? 'active' : ''; ?>"
                                    data-sort-param="popular"
                                    data-sort-value="1">
                                    <i class="fas fa-fire"></i> Best Sellers
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="menu-content">

                    <?php
                    // Resolve image for a product
                    $image_map = [
                        1 => 'coffee.png',
                        2 => 'coffee.png',
                        3 => 'coffee.png',
                        4 => 'coffee.png',
                        5 => 'coffee.png',
                        6 => 'pastry.png',
                        7 => 'pastry.png',
                        8 => 'pastry.png',
                        9 => 'coffee.png'
                    ];
                    function getProductImage($product, $image_map)
                    {
                        return !empty($product['image_path'])
                            ? $product['image_path']
                            : 'images/' . ($image_map[$product['category_id']] ?? 'coffee.png');
                    }

                    // Render a single compact product card
                    function renderProductCard($product, $img_src, $is_admin)
                    {
                        $id   = $product['product_id'];
                        $name = htmlspecialchars($product['name']);
                        $desc = htmlspecialchars($product['description']);
                        $price = number_format($product['price'], 2);
                        echo '<div class="product-card" data-product-id="' . $id . '">';
                        echo  '<div class="product-image-wrapper">';
                        echo   '<img src="' . htmlspecialchars($img_src) . '" alt="' . $name . '" class="product-image">';
                        echo  '</div>';
                        echo  '<div class="product-info">';
                        echo   '<h3 class="product-name">' . $name . '</h3>';
                        echo   '<p class="product-description">' . $desc . '</p>';
                        echo   '<div class="product-footer">';
                        echo    '<span class="product-price">₱' . $price . '</span>';
                        if (!$is_admin) {
                            echo '<button class="btn-order" onclick="addToCart(' . $id . ')"><i class="fas fa-plus"></i></button>';
                        }
                        echo   '</div>';
                        echo  '</div>';
                        echo '</div>';
                    }
                    ?>

                    <?php if ($total_products > 0): ?>
                        <?php
                        // Grouped view — all categories, no sort override
                        $use_grouped = ($category_filter === 0 && $price_sort === '' && !$show_popular);

                        if ($use_grouped):
                            // Collect products into category groups
                            $groups = [];
                            mysqli_data_seek($products_result, 0);
                            while ($p = mysqli_fetch_assoc($products_result)) {
                                $groups[$p['category_name']][] = $p;
                            }
                        ?>
                            <?php foreach ($groups as $group_name => $items): ?>
                                <div class="menu-cat-group">
                                    <p class="menu-cat-label"><?php echo htmlspecialchars($group_name); ?></p>
                                    <div class="products-grid">
                                        <?php foreach ($items as $product):
                                            renderProductCard($product, getProductImage($product, $image_map), $is_admin);
                                        endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <!-- Filtered / sorted flat grid with active-filter bar -->
                            <?php if ($has_active_filters): ?>
                                <div class="menu-content-sticky-header">
                                    <div class="active-filters">
                                        <span class="filter-label">Active Filters:</span>
                                        <?php if ($category_filter > 0): ?>
                                            <span class="filter-badge">
                                                <?php echo htmlspecialchars($cat_name); ?>
                                                <a href="<?php echo buildFilterUrl([], ['category']); ?>"><i class="fas fa-times"></i></a>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($price_sort === 'low'): ?>
                                            <span class="filter-badge">Price: Low to High
                                                <a href="<?php echo buildFilterUrl([], ['price_sort']); ?>"><i class="fas fa-times"></i></a>
                                            </span>
                                        <?php elseif ($price_sort === 'high'): ?>
                                            <span class="filter-badge">Price: High to Low
                                                <a href="<?php echo buildFilterUrl([], ['price_sort']); ?>"><i class="fas fa-times"></i></a>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($show_popular): ?>
                                            <span class="filter-badge">Best Sellers
                                                <a href="<?php echo buildFilterUrl([], ['popular']); ?>"><i class="fas fa-times"></i></a>
                                            </span>
                                        <?php endif; ?>
                                        <a href="menu.php" class="btn-clear-active-filters">Clear All <i class="fas fa-times"></i></a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="products-grid">
                                <?php
                                mysqli_data_seek($products_result, 0);
                                while ($product = mysqli_fetch_assoc($products_result)):
                                    renderProductCard($product, getProductImage($product, $image_map), $is_admin);
                                endwhile; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No products found</h3>
                            <p>Try adjusting your filters or browse all categories</p>
                            <a href="menu.php" class="btn-primary">View All Products</a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </section>

    <script>
        // Save scroll position before navigation
        function saveScrollPosition() {
            sessionStorage.setItem('menuScrollY', window.scrollY);
            sessionStorage.setItem('sidebarScrollY', document.querySelector('.menu-sidebar').scrollTop);
        }

        // Restore scroll position on page load
        function restoreScrollPosition() {
            const scrollY = sessionStorage.getItem('menuScrollY');
            const sidebarScrollY = sessionStorage.getItem('sidebarScrollY');

            if (scrollY !== null) {
                window.scrollTo(0, parseInt(scrollY));
                sessionStorage.removeItem('menuScrollY');
            }

            if (sidebarScrollY !== null) {
                document.querySelector('.menu-sidebar').scrollTop = parseInt(sidebarScrollY);
                sessionStorage.removeItem('sidebarScrollY');
            }
        }

        // Handle category filter clicks
        document.querySelectorAll('.category-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (this.classList.contains('active')) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();
                saveScrollPosition();

                this.style.opacity = '0.5';
                document.body.style.cursor = 'wait';

                window.location.href = this.href;
            });
        });

        // Restore position on page load
        window.addEventListener('load', restoreScrollPosition);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
    <script src="js/menu-page.js?v=<?php echo time(); ?>"></script>
</body>

</html>