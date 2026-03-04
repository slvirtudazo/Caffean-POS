<?php

/**
 * Purge Coffee Shop - Supplies Page
 * Displays Coffee Beans, Milk & Creamers, and Brewing Equipment
 * Layout mirrors the Menu page: sticky sidebar + 5-column product grid
 * Supports category filter, price sort, and best sellers sort
 */

require_once 'php/db_connection.php';

$is_admin      = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_logged_in  = isset($_SESSION['user_id']);

// Supply category IDs
define('SUPPLY_CAT_IDS', [10, 11, 12]);
$supply_ids_sql = implode(',', SUPPLY_CAT_IDS);

// Get filter/sort parameters
$cat_filter   = isset($_GET['category'])   ? intval($_GET['category']) : 0;
$price_sort   = isset($_GET['price_sort']) ? $_GET['price_sort']       : '';
$show_popular = isset($_GET['popular'])    && $_GET['popular'] == '1';

// Validate inputs
if ($cat_filter > 0 && !in_array($cat_filter, SUPPLY_CAT_IDS)) $cat_filter = 0;
if (!in_array($price_sort, ['low', 'high'])) $price_sort = '';

// Build WHERE clause
$where = $cat_filter > 0
    ? "p.status = 1 AND p.category_id = $cat_filter"
    : "p.status = 1 AND p.category_id IN ($supply_ids_sql)";

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

// Fetch products
$products_result = mysqli_query($conn,
    "SELECT p.*, c.name AS category_name
     FROM products p
     JOIN categories c ON p.category_id = c.category_id
     WHERE $where
     ORDER BY $order_by");

// Fetch supply categories for sidebar
$cats_result = mysqli_query($conn,
    "SELECT * FROM categories WHERE category_id IN ($supply_ids_sql) ORDER BY category_id");

// Per-category counts for sidebar badge
$cat_counts = [];
$count_res = mysqli_query($conn,
    "SELECT category_id, COUNT(*) AS cnt FROM products
     WHERE status = 1 AND category_id IN ($supply_ids_sql)
     GROUP BY category_id");
while ($row = mysqli_fetch_assoc($count_res)) {
    $cat_counts[$row['category_id']] = $row['cnt'];
}
$total_supply = array_sum($cat_counts);

// Active filters flag
$has_active_filters = ($cat_filter > 0 || $price_sort !== '' || $show_popular);

// Resolve filtered category name for badge display
$cat_name = '';
if ($cat_filter > 0) {
    $res = mysqli_query($conn, "SELECT name FROM categories WHERE category_id = $cat_filter");
    if ($res) $cat_name = mysqli_fetch_assoc($res)['name'];
}

// Helper — build URL preserving current params with overrides/removals
function buildSupplyUrl($overrides = [], $removals = []) {
    $keys   = ['category', 'price_sort', 'popular'];
    $params = [];
    foreach ($keys as $k) {
        if (isset($_GET[$k]) && $_GET[$k] !== '') $params[$k] = $_GET[$k];
    }
    foreach ($overrides as $k => $v) $params[$k] = $v;
    foreach ($removals  as $k)      unset($params[$k]);
    return 'supplies-page.php' . ($params ? '?' . http_build_query($params) : '');
}

// Render one product card
function renderSupplyCard($product, $is_admin, $is_logged_in) {
    $id    = $product['product_id'];
    $name  = htmlspecialchars($product['name']);
    $desc  = htmlspecialchars($product['description']);
    $price = number_format($product['price'], 2);
    $net   = htmlspecialchars($product['net_content'] ?? '');
    $img   = !empty($product['image_path'])
           ? htmlspecialchars($product['image_path'])
           : 'images/placeholder.png';

    echo '<div class="product-card" data-product-id="' . $id . '">';

    // Favorite heart button — top-right corner of card
    if (!$is_admin) {
        $onclick = $is_logged_in ? "toggleFav(event,$id,this)" : "event.stopPropagation();showLoginRequiredPopup()";
        echo '<button class="fav-card-btn" onclick="' . $onclick . '" title="Save to favorites" aria-label="Save to favorites">';
        echo  '<i class="far fa-heart"></i>';
        echo '</button>';
    }

    echo  '<div class="product-image-wrapper">';
    echo   '<img src="' . $img . '" alt="' . $name . '" class="product-image">';
    echo  '</div>';
    echo  '<div class="product-info">';
    echo   '<h3 class="product-name">' . $name . '</h3>';
    echo   '<p class="product-description">' . $desc . '</p>';
    echo   '<div class="product-footer">';
    echo    '<div class="product-meta">';
    echo     '<span class="product-price">&#8369;' . $price . '</span>';
    if ($net !== '') echo '<span class="product-net">' . $net . '</span>';
    echo    '</div>';
    if (!$is_admin) {
        // Guest triggers login popup; logged-in user adds to cart directly
        $onclick = $is_logged_in ? "addToCart($id)" : "showLoginRequiredPopup()";
        echo '<button class="btn-order" onclick="' . $onclick . '"><i class="fas fa-plus"></i></button>';
    }
    echo   '</div>';
    echo  '</div>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purge Coffee - Supplies</title>

    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/supplies-page.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/components.css?v=<?php echo time(); ?>">
</head>

<body>

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
                    <li class="nav-item"><a class="nav-link active" href="supplies-page.php">Supplies</a></li>
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

    <!-- Main supplies section -->
    <section class="supplies-main-section">
        <div class="container-fluid">
            <div class="supplies-layout-row">

                <!-- Sidebar -->
                <div class="supplies-sidebar">
                    <div class="filter-panel">

                        <!-- Categories filter -->
                        <div class="filter-section">
                            <h3 class="filter-title">
                                <i class="fas fa-list-ul"></i> Categories
                            </h3>
                            <div class="category-list">

                                <a href="<?php echo buildSupplyUrl([], ['category']); ?>"
                                   class="category-item <?php echo $cat_filter === 0 ? 'active' : ''; ?>">
                                    <span class="category-icon"><i class="fas fa-th"></i></span>
                                    <span class="category-name">All Categories</span>
                                    <span class="category-count"><?php echo $total_supply; ?></span>
                                </a>

                                <?php
                                $cat_icons = [
                                    10 => 'fas fa-seedling',
                                    11 => 'fas fa-wine-bottle',
                                    12 => 'fas fa-mug-hot',
                                ];
                                mysqli_data_seek($cats_result, 0);
                                while ($cat = mysqli_fetch_assoc($cats_result)):
                                    $cid   = $cat['category_id'];
                                    $icon  = $cat_icons[$cid] ?? 'fas fa-box';
                                    $count = $cat_counts[$cid] ?? 0;
                                ?>
                                <a href="<?php echo buildSupplyUrl(['category' => $cid], []); ?>"
                                   class="category-item <?php echo $cat_filter === $cid ? 'active' : ''; ?>">
                                    <span class="category-icon"><i class="<?php echo $icon; ?>"></i></span>
                                    <span class="category-name"><?php echo htmlspecialchars($cat['name']); ?></span>
                                    <span class="category-count"><?php echo $count; ?></span>
                                </a>
                                <?php endwhile; ?>

                            </div>
                        </div>

                        <!-- Sort By -->
                        <div class="filter-section">
                            <h3 class="filter-title">
                                <i class="fas fa-sort-amount-down"></i> Sort By
                            </h3>
                            <div class="sort-options">
                                <div class="sort-item <?php echo $price_sort === 'low'  ? 'active' : ''; ?>"
                                     data-sort-param="price_sort" data-sort-value="low">
                                    <i class="fas fa-arrow-down"></i> Price: Low to High
                                </div>
                                <div class="sort-item <?php echo $price_sort === 'high' ? 'active' : ''; ?>"
                                     data-sort-param="price_sort" data-sort-value="high">
                                    <i class="fas fa-arrow-up"></i> Price: High to Low
                                </div>
                                <div class="sort-item <?php echo $show_popular ? 'active' : ''; ?>"
                                     data-sort-param="popular" data-sort-value="1">
                                    <i class="fas fa-fire"></i> Best Sellers
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Main content -->
                <div class="supplies-content">

                    <?php if ($has_active_filters): ?>
                        <!-- Active filters sticky bar -->
                        <div class="supplies-content-sticky-header">
                            <div class="active-filters">
                                <span class="filter-label">Active Filters:</span>

                                <?php if ($cat_filter > 0): ?>
                                    <span class="filter-badge">
                                        <?php echo htmlspecialchars($cat_name); ?>
                                        <a href="<?php echo buildSupplyUrl([], ['category']); ?>"><i class="fas fa-times"></i></a>
                                    </span>
                                <?php endif; ?>

                                <?php if ($price_sort === 'low'): ?>
                                    <span class="filter-badge">Price: Low to High
                                        <a href="<?php echo buildSupplyUrl([], ['price_sort']); ?>"><i class="fas fa-times"></i></a>
                                    </span>
                                <?php elseif ($price_sort === 'high'): ?>
                                    <span class="filter-badge">Price: High to Low
                                        <a href="<?php echo buildSupplyUrl([], ['price_sort']); ?>"><i class="fas fa-times"></i></a>
                                    </span>
                                <?php endif; ?>

                                <?php if ($show_popular): ?>
                                    <span class="filter-badge">Best Sellers
                                        <a href="<?php echo buildSupplyUrl([], ['popular']); ?>"><i class="fas fa-times"></i></a>
                                    </span>
                                <?php endif; ?>

                                <a href="supplies-page.php" class="btn-clear-active-filters">Clear All <i class="fas fa-times"></i></a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (mysqli_num_rows($products_result) > 0): ?>

                        <?php
                        // Grouped view — default (no sort/filter applied)
                        $use_grouped = ($cat_filter === 0 && $price_sort === '' && !$show_popular);

                        if ($use_grouped):
                            $groups = [];
                            while ($p = mysqli_fetch_assoc($products_result)) {
                                $groups[$p['category_name']][] = $p;
                            }
                        ?>
                            <?php foreach ($groups as $group_name => $items): ?>
                                <div class="menu-cat-group">
                                    <p class="menu-cat-label"><?php echo htmlspecialchars($group_name); ?></p>
                                    <div class="products-grid">
                                        <?php foreach ($items as $product):
                                            renderSupplyCard($product, $is_admin, $is_logged_in);
                                        endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <!-- Filtered/sorted flat grid -->
                            <div class="products-grid">
                                <?php while ($product = mysqli_fetch_assoc($products_result)):
                                    renderSupplyCard($product, $is_admin, $is_logged_in);
                                endwhile; ?>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h3>No products found</h3>
                            <p>Try adjusting your filters or browse all categories</p>
                            <a href="supplies-page.php" class="btn-primary">View All Supplies</a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </section>

    <script>
        // Save scroll position before navigating
        function saveScrollPosition() {
            sessionStorage.setItem('suppliesScrollY', window.scrollY);
            const sidebar = document.querySelector('.supplies-sidebar');
            if (sidebar) sessionStorage.setItem('sidebarScrollY', sidebar.scrollTop);
        }

        // Restore scroll position on page load
        function restoreScrollPosition() {
            const scrollY  = sessionStorage.getItem('suppliesScrollY');
            const sidebarY = sessionStorage.getItem('sidebarScrollY');
            if (scrollY !== null) {
                window.scrollTo(0, parseInt(scrollY));
                sessionStorage.removeItem('suppliesScrollY');
            }
            if (sidebarY !== null) {
                const sidebar = document.querySelector('.supplies-sidebar');
                if (sidebar) sidebar.scrollTop = parseInt(sidebarY);
                sessionStorage.removeItem('sidebarScrollY');
            }
        }

        // Category click — freeze item instantly to prevent padding snap-back on hover exit
        document.querySelectorAll('.category-item').forEach(item => {
            item.addEventListener('click', function (e) {
                if (this.classList.contains('active')) { e.preventDefault(); return; }
                e.preventDefault();
                this.style.transition = 'none';
                this.style.pointerEvents = 'none';
                this.style.opacity = '0.5';
                document.body.style.cursor = 'wait';
                saveScrollPosition();
                window.location.href = this.href;
            });
        });

        // Sort item click — mutually exclusive params, then navigate
        document.querySelectorAll('.sort-item[data-sort-param]').forEach(item => {
            item.addEventListener('click', function () {
                if (typeof saveScrollPosition === 'function') saveScrollPosition();
                const param     = this.dataset.sortParam;
                const value     = this.dataset.sortValue;
                const isActive  = this.classList.contains('active');
                const urlParams = new URLSearchParams(window.location.search);

                if (isActive) {
                    urlParams.delete(param);
                } else {
                    if (param === 'price_sort' || param === 'popular') {
                        urlParams.delete('price_sort');
                        urlParams.delete('popular');
                    }
                    urlParams.set(param, value);
                }

                this.style.opacity = '0.5';
                document.body.style.cursor = 'wait';
                const search = urlParams.toString();
                window.location.href = window.location.pathname + (search ? '?' + search : '');
            });
        });

        window.addEventListener('load', restoreScrollPosition);
    </script>

    <!-- Login Required Popup -->
    <div id="login-required-popup" class="login-popup-overlay" style="display:none;" onclick="closeLoginPopup(event)">
        <div class="login-popup-card">
            <h3 class="login-popup-title">Login Required</h3>
            <p class="login-popup-message">You must be logged in to save and track your order transactions.</p>
            <div class="login-popup-actions">
                <a href="login.php" class="btn-popup-login">Log In</a>
                <button class="btn-popup-close" onclick="document.getElementById('login-required-popup').style.display='none'">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        window.IS_LOGGED_IN = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

        /* Show login-required popup */
        function showLoginRequiredPopup() {
            document.getElementById('login-required-popup').style.display = 'flex';
        }
        /* Close popup on overlay click */
        function closeLoginPopup(event) {
            if (event.target === document.getElementById('login-required-popup')) {
                document.getElementById('login-required-popup').style.display = 'none';
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>

    <script>
        /* ── Favorites: toggle + initial state ───────────────── */

        // Toggle favorite on heart button click
        function toggleFav(e, productId, btn) {
            e.stopPropagation();
            if (!window.IS_LOGGED_IN) { showLoginRequiredPopup(); return; }

            const fd = new FormData();
            fd.append('action', 'toggle');
            fd.append('product_id', productId);

            fetch('favorites.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (!d.success) return;
                    const icon = btn.querySelector('i');
                    const isNowActive = d.state === 'added';
                    btn.classList.toggle('active', isNowActive);
                    icon.className = isNowActive ? 'fas fa-heart' : 'far fa-heart';
                    btn.classList.add('pop');
                    btn.addEventListener('animationend', () => btn.classList.remove('pop'), { once: true });
                });
        }

        // On page load, mark cards the user has already favorited
        document.addEventListener('DOMContentLoaded', () => {
            if (!window.IS_LOGGED_IN) return;
            const ids = [...document.querySelectorAll('.product-card[data-product-id]')]
                        .map(c => c.dataset.productId).join(',');
            if (!ids) return;
            fetch(`favorites.php?action=batch&ids=${ids}`)
                .then(r => r.json())
                .then(d => {
                    if (!d.favorited) return;
                    d.favorited.forEach(pid => {
                        const card = document.querySelector(`.product-card[data-product-id="${pid}"]`);
                        const btn  = card?.querySelector('.fav-card-btn');
                        if (!btn) return;
                        btn.classList.add('active');
                        btn.querySelector('i').className = 'fas fa-heart';
                    });
                });
        });
    </script>
</body>

</html>