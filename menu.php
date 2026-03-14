<?php

// Menu Page — displays all products with category filter and price or popularity sorting.

require_once 'php/db_connection.php';
require_once 'php/product_images.php';

$is_admin      = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_logged_in  = isset($_SESSION['user_id']);

// Get filter and sort parameters from the URL.
$category_filter   = isset($_GET['category'])    ? intval($_GET['category'])   : 0;
$price_sort        = isset($_GET['price_sort'])  ? $_GET['price_sort']         : '';   // 'low' or 'high'
$show_popular      = isset($_GET['popular'])     && $_GET['popular']     == '1';

// Validate the price_sort value.
if (!in_array($price_sort, ['low', 'high'])) $price_sort = '';

// Supply category IDs — excluded from this menu.
define('MENU_EXCLUDED_CATS', [10, 11, 12]);
$excluded_sql = implode(',', MENU_EXCLUDED_CATS);

// Build SQL WHERE clauses.
$where_clauses = ["p.status = 1", "p.category_id NOT IN ($excluded_sql)"];

if ($category_filter > 0) {
    $where_clauses[] = "p.category_id = $category_filter";
}

// Build ORDER BY — price_sort takes precedence over popular.
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

// Build and run the final query.
$where_string    = implode(" AND ", $where_clauses);
$products_query  = "SELECT p.*, c.name as category_name
                    FROM products p
                    JOIN categories c ON p.category_id = c.category_id
                    WHERE $where_string
                    ORDER BY $order_by";
$products_result = mysqli_query($conn, $products_query);

// Fetch categories for the sidebar.
$categories_query  = "SELECT * FROM categories ORDER BY category_id";
$categories_result = mysqli_query($conn, $categories_query);

// Get the total product count.
$total_products = mysqli_num_rows($products_result);

// Helper: build a URL preserving current params with overrides and removals.
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

// Resolve the active category name.
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
    <title>Caffean - Menu Page</title>

    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/menu_page.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/components.css?v=<?php echo time(); ?>">
</head>

<body class="page-menu">

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Caffean Logo">
                <span>caffean</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="menu.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="supplies_page.php">Supplies</a></li>
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
                                            mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE status = 1 AND category_id NOT IN ($excluded_sql)")
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
                                    9 => 'fa-plus-circle',
                                    10 => 'fa-seedling',
                                    11 => 'fa-droplet',
                                    12 => 'fa-flask',
                                ];

                                mysqli_data_seek($categories_result, 0);
                                while ($category = mysqli_fetch_assoc($categories_result)):
                                    $cat_id = $category['category_id'];
                                    // Skip supply-only categories.
                                    if (in_array($cat_id, MENU_EXCLUDED_CATS)) continue;
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
                    // Resolve the product image using the central helper.
                    function getProductImage($product, $image_map)
                    {
                        return resolveProductImage(
                            $product['name'],
                            $product['image_path'] ?? '',
                            $product['category_id'] ?? 0
                        );
                    }

                    // Net content defaults by category — DB value takes precedence.
                    $net_defaults = [
                        1 => '12 oz',
                        2 => '16 oz',
                        3 => '12 oz',
                        4 => '16 oz',
                        5 => '12 oz',
                        9 => '1 oz',
                    ];
                    function getNetContent($product, $defaults)
                    {
                        if (!empty($product['net_content'])) return htmlspecialchars($product['net_content']);
                        return $defaults[$product['category_id']] ?? '';
                    }

                    // Render a compact product card.
                    function renderProductCard($product, $img_src, $is_admin, $is_logged_in)
                    {
                        global $net_defaults;
                        $id   = $product['product_id'];
                        $name = htmlspecialchars($product['name']);
                        $desc = htmlspecialchars($product['description']);
                        $price = number_format($product['price'], 2);
                        $net  = getNetContent($product, $net_defaults);
                        echo '<div class="product-card" data-product-id="' . $id . '">';

                        // Favorite heart button on the top-right corner of the card.
                        if (!$is_admin) {
                            $onclick = $is_logged_in ? "toggleFav(event,$id,this)" : "event.stopPropagation();showLoginRequiredPopup()";
                            echo '<button class="fav-card-btn" data-name="' . htmlspecialchars($product['name'], ENT_QUOTES) . '" onclick="' . $onclick . '" title="Save to favorites" aria-label="Save to favorites">';
                            echo  '<i class="far fa-heart"></i>';
                            echo '</button>';
                        }

                        echo  '<div class="product-image-wrapper">';
                        echo   '<img src="' . htmlspecialchars($img_src) . '" alt="' . $name . '" class="product-image">';
                        echo  '</div>';
                        echo  '<div class="product-info">';
                        echo   '<h3 class="product-name">' . $name . '</h3>';
                        echo   '<p class="product-description">' . $desc . '</p>';
                        echo   '<div class="product-footer">';
                        // Price and net content stacked on the left.
                        echo    '<div class="product-meta">';
                        echo     '<span class="product-price">₱' . $price . '</span>';
                        if ($net !== '') echo '<span class="product-net">' . $net . '</span>';
                        echo    '</div>';
                        if (!$is_admin) {
                            if ($is_logged_in) {
                                // Logged-in: inline quantity selector matching kiosk style.
                                echo '<div class="kpf-qty-row" id="mpf-' . $id . '">';
                                echo  '<button class="kpf-qty-btn" disabled id="mpf-minus-' . $id . '" onclick="menuCardQty(' . $id . ', -1)"><i class="fas fa-minus"></i></button>';
                                echo  '<span class="kpf-qty-num" id="mpf-num-' . $id . '">0</span>';
                                echo  '<button class="kpf-qty-btn kpf-plus" onclick="menuCardQty(' . $id . ', 1)"><i class="fas fa-plus"></i></button>';
                                echo '</div>';
                            } else {
                                // Guest: single button triggers the login popup.
                                echo '<button class="btn-order" onclick="showLoginRequiredPopup()"><i class="fas fa-plus"></i></button>';
                            }
                        }
                        echo   '</div>';
                        echo  '</div>';
                        echo '</div>';
                    }
                    ?>

                    <?php if ($total_products > 0): ?>
                        <?php
                        // Grouped view — all categories, no sort override.
                        $use_grouped = ($category_filter === 0 && $price_sort === '' && !$show_popular);

                        if ($use_grouped):
                            // Collect products into category groups.
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
                                            renderProductCard($product, getProductImage($product, null), $is_admin, $is_logged_in);
                                        endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                        <?php else: ?>
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
                                    renderProductCard($product, getProductImage($product, null), $is_admin, $is_logged_in);
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
        // Save scroll position before navigating.
        function saveScrollPosition() {
            sessionStorage.setItem('menuScrollY', window.scrollY);
            sessionStorage.setItem('sidebarScrollY', document.querySelector('.menu-sidebar').scrollTop);
        }

        // Restore scroll position on page load.
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

        // Handle category filter link clicks.
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

        // Restore sidebar scroll position on page load.
        window.addEventListener('load', restoreScrollPosition);
    </script>

    <div id="login-required-popup" class="login-popup-overlay" style="display:none;" onclick="closeLoginPopup(event)">
        <div class="login-popup-card">
            <h3 class="login-popup-title">Login Required</h3>
            <p class="login-popup-message">Please log in to manage your<br>cart, favorites, and orders.</p>
            <div class="login-popup-actions">
                <a href="login.php" class="btn-popup-login">Log In</a>
                <button class="btn-popup-close" onclick="document.getElementById('login-required-popup').style.display='none'">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // Show the login-required popup.
        function showLoginRequiredPopup() {
            document.getElementById('login-required-popup').style.display = 'flex';
        }
        // Close the popup on overlay click.
        function closeLoginPopup(event) {
            if (event.target === document.getElementById('login-required-popup')) {
                document.getElementById('login-required-popup').style.display = 'none';
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        window.IS_LOGGED_IN = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    </script>
    <?php
    // Embed per-product cart quantities from the session for UI init.
    $cart_qtys = [];
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $pid => $opts) {
            $cart_qtys[(int)$pid] = is_array($opts) ? (int)$opts['quantity'] : (int)$opts;
        }
    }
    ?>
    <script>
        window.serverCart = <?php echo json_encode($cart_qtys); ?>;
    </script>
    <script src="js/main.js?v=<?php echo time(); ?>"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
    <script src="js/menu_page.js?v=<?php echo time(); ?>"></script>

    <script>
        /* ── Favorites: toggle on heart button click ─────────── */
        function toggleFav(e, productId, btn) {
            e.stopPropagation();
            if (!window.IS_LOGGED_IN) {
                showLoginRequiredPopup();
                return;
            }

            const productName = btn.getAttribute('data-name') || 'Product';
            const fd = new FormData();
            fd.append('action', 'toggle');
            fd.append('product_id', productId);

            fetch('favorites.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => {
                    if (!r.ok) throw new Error(r.status);
                    return r.json();
                })
                .then(d => {
                    if (!d.success) throw new Error(d.message || 'failed');
                    const icon = btn.querySelector('i');
                    const isNowActive = d.state === 'added';
                    btn.classList.toggle('active', isNowActive);
                    icon.className = isNowActive ? 'fas fa-heart' : 'far fa-heart';
                    btn.classList.add('pop');
                    btn.addEventListener('animationend', () => btn.classList.remove('pop'), {
                        once: true
                    });
                    showNotification(
                        isNowActive ? productName + ' added to favorites.' : productName + ' removed from favorites.',
                        isNowActive ? 'success' : 'info'
                    );
                })
                .catch(() => showNotification('Could not update favorites.', 'error'));
        }
        // Initial favorite states are loaded by main.js loadFavoritesForMenu().
    </script>
</body>

</html>