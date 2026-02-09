<?php
/**
 * Purge Coffee Shop - Menu Page
 * Comprehensive menu displaying all products with filtering by category,
 * price sorting, and best sellers highlighting
 */

require_once 'php/config.php';

// Get filter parameters from URL
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$sort_filter = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$best_sellers_only = isset($_GET['bestsellers']) && $_GET['bestsellers'] == '1';

// Build SQL query based on filters
$where_clauses = array("p.status = 1");
$order_by = "p.category_id, p.name";

// Apply category filter if selected
if ($category_filter > 0) {
    $where_clauses[] = "p.category_id = $category_filter";
}

// Apply best sellers filter if enabled
if ($best_sellers_only) {
    $where_clauses[] = "(
        COALESCE((SELECT COUNT(*) FROM order_items oi 
                  JOIN orders o ON oi.order_id = o.order_id 
                  WHERE oi.product_id = p.product_id AND o.status = 'completed'), 0) * 3 +
        COALESCE((SELECT SUM(interaction_count) FROM product_interactions 
                  WHERE product_id = p.product_id AND interaction_type = 'add_to_cart'), 0) * 2 +
        COALESCE((SELECT SUM(interaction_count) FROM product_interactions 
                  WHERE product_id = p.product_id AND interaction_type = 'favorite'), 0) * 1
    ) > 0";
}

// Apply price sorting
if ($sort_filter == 'price_low') {
    $order_by = "p.price ASC";
} elseif ($sort_filter == 'price_high') {
    $order_by = "p.price DESC";
} elseif ($sort_filter == 'popular') {
    $order_by = "(
        COALESCE((SELECT COUNT(*) FROM order_items oi 
                  JOIN orders o ON oi.order_id = o.order_id 
                  WHERE oi.product_id = p.product_id AND o.status = 'completed'), 0) * 3 +
        COALESCE((SELECT SUM(interaction_count) FROM product_interactions 
                  WHERE product_id = p.product_id AND interaction_type = 'add_to_cart'), 0) * 2 +
        COALESCE((SELECT SUM(interaction_count) FROM product_interactions 
                  WHERE product_id = p.product_id AND interaction_type = 'favorite'), 0) * 1
    ) DESC";
}

// Construct final query
$where_string = implode(" AND ", $where_clauses);
$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   JOIN categories c ON p.category_id = c.category_id 
                   WHERE $where_string
                   ORDER BY $order_by";
$products_result = mysqli_query($conn, $products_query);

// Fetch all categories for filter sidebar
$categories_query = "SELECT * FROM categories ORDER BY category_id";
$categories_result = mysqli_query($conn, $categories_query);

// Get total product count
$total_products = mysqli_num_rows($products_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Purge Coffee</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Base Styles -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Menu Page Specific Styles -->
    <link rel="stylesheet" href="css/menu-page.css">
</head>
<body>
    
    <!-- Top Banner -->
    <div class="top-banner">Shipping Nationwide</div>

    <!-- Navigation Bar -->
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
                        <a class="nav-link active" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="offers.php">Offers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
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

    <!-- Main Menu Section -->
    <section class="menu-main-section">
        <div class="container-fluid">
            <div class="row">
                
                <!-- Sidebar Filter Panel -->
                <div class="col-lg-3 col-md-4 menu-sidebar">
                    <div class="filter-panel">
                        
                        <!-- Categories Filter -->
                        <div class="filter-section">
                            <h3 class="filter-title">
                                <i class="fas fa-list-ul"></i> Categories
                            </h3>
                            <div class="category-list">
                                <!-- All Categories Option -->
                                <a href="menu.php" class="category-item <?php echo $category_filter == 0 ? 'active' : ''; ?>">
                                    <span class="category-icon"><i class="fas fa-th"></i></span>
                                    <span class="category-name">All Categories</span>
                                    <span class="category-count">
                                        <?php 
                                        $all_count_query = "SELECT COUNT(*) as total FROM products WHERE status = 1";
                                        $all_count = mysqli_fetch_assoc(mysqli_query($conn, $all_count_query))['total'];
                                        echo $all_count;
                                        ?>
                                    </span>
                                </a>
                                
                                <!-- Dynamic Category List -->
                                <?php 
                                // Icon mapping for categories
                                $category_icons = array(
                                    1 => 'fa-mug-hot',      // Hot Coffee
                                    2 => 'fa-glass-water',  // Iced Coffee
                                    3 => 'fa-cup-straw',    // Non-Coffee
                                    4 => 'fa-ice-cream',    // Milkshakes
                                    5 => 'fa-leaf',         // Tea
                                    6 => 'fa-cake-candles', // Desserts
                                    7 => 'fa-bread-slice',  // Pastry
                                    8 => 'fa-burger',       // Snacks
                                    9 => 'fa-plus-circle'   // Add Ons
                                );
                                
                                mysqli_data_seek($categories_result, 0);
                                while($category = mysqli_fetch_assoc($categories_result)): 
                                    // Count products in this category
                                    $cat_id = $category['category_id'];
                                    $count_query = "SELECT COUNT(*) as count FROM products WHERE category_id = $cat_id AND status = 1";
                                    $count_result = mysqli_query($conn, $count_query);
                                    $product_count = mysqli_fetch_assoc($count_result)['count'];
                                    
                                    $icon = isset($category_icons[$cat_id]) ? $category_icons[$cat_id] : 'fa-circle';
                                ?>
                                    <a href="?category=<?php echo $category['category_id']; ?>" 
                                       class="category-item <?php echo $category_filter == $category['category_id'] ? 'active' : ''; ?>">
                                        <span class="category-icon"><i class="fas <?php echo $icon; ?>"></i></span>
                                        <span class="category-name"><?php echo htmlspecialchars($category['name']); ?></span>
                                        <span class="category-count"><?php echo $product_count; ?></span>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <!-- Price Sorting Filter -->
                        <div class="filter-section">
                            <h3 class="filter-title">
                                <i class="fas fa-sort-amount-down"></i> Sort By Price
                            </h3>
                            <div class="sort-options">
                                <a href="?category=<?php echo $category_filter; ?>&sort=price_low&bestsellers=<?php echo $best_sellers_only ? '1' : '0'; ?>" 
                                   class="sort-item <?php echo $sort_filter == 'price_low' ? 'active' : ''; ?>">
                                    <i class="fas fa-arrow-down"></i> Price: Low to High
                                </a>
                                <a href="?category=<?php echo $category_filter; ?>&sort=price_high&bestsellers=<?php echo $best_sellers_only ? '1' : '0'; ?>" 
                                   class="sort-item <?php echo $sort_filter == 'price_high' ? 'active' : ''; ?>">
                                    <i class="fas fa-arrow-up"></i> Price: High to Low
                                </a>
                                <a href="?category=<?php echo $category_filter; ?>&sort=popular&bestsellers=<?php echo $best_sellers_only ? '1' : '0'; ?>" 
                                   class="sort-item <?php echo $sort_filter == 'popular' ? 'active' : ''; ?>">
                                    <i class="fas fa-fire"></i> Most Popular
                                </a>
                            </div>
                        </div>

                        <!-- Best Sellers Toggle -->
                        <div class="filter-section">
                            <h3 class="filter-title">
                                <i class="fas fa-star"></i> Best Sellers
                            </h3>
                            <div class="bestseller-toggle">
                                <label class="toggle-switch">
                                    <input type="checkbox" id="bestsellerToggle" 
                                           <?php echo $best_sellers_only ? 'checked' : ''; ?>
                                           onchange="toggleBestsellers(this)">
                                    <span class="toggle-slider"></span>
                                    <span class="toggle-label">Show Best Sellers Only</span>
                                </label>
                            </div>
                        </div>

                        <!-- Clear Filters Button -->
                        <div class="filter-section">
                            <a href="menu.php" class="btn-clear-filters">
                                <i class="fas fa-times-circle"></i> Clear All Filters
                            </a>
                        </div>
                        
                    </div>
                </div>

                <!-- Main Products Display Area -->
                <div class="col-lg-9 col-md-8 menu-content">
                    
                    <!-- Active Filters Display -->
                    <?php if($category_filter > 0 || $sort_filter != 'default' || $best_sellers_only): ?>
                    <div class="active-filters">
                        <span class="filter-label">Active Filters:</span>
                        
                        <?php if($category_filter > 0): 
                            $cat_name_query = "SELECT name FROM categories WHERE category_id = $category_filter";
                            $cat_name = mysqli_fetch_assoc(mysqli_query($conn, $cat_name_query))['name'];
                        ?>
                            <span class="filter-badge">
                                <?php echo htmlspecialchars($cat_name); ?>
                                <a href="menu.php"><i class="fas fa-times"></i></a>
                            </span>
                        <?php endif; ?>
                        
                        <?php if($sort_filter != 'default'): ?>
                            <span class="filter-badge">
                                <?php 
                                $sort_labels = array(
                                    'price_low' => 'Price: Low to High',
                                    'price_high' => 'Price: High to Low',
                                    'popular' => 'Most Popular'
                                );
                                echo $sort_labels[$sort_filter];
                                ?>
                                <a href="?category=<?php echo $category_filter; ?>"><i class="fas fa-times"></i></a>
                            </span>
                        <?php endif; ?>
                        
                        <?php if($best_sellers_only): ?>
                            <span class="filter-badge">
                                Best Sellers Only
                                <a href="?category=<?php echo $category_filter; ?>&sort=<?php echo $sort_filter; ?>">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Results Header -->
                    <div class="results-header">
                        <h2 class="results-title">
                            <?php 
                            if($category_filter > 0) {
                                echo htmlspecialchars($cat_name);
                            } elseif($best_sellers_only) {
                                echo "Best Sellers";
                            } else {
                                echo "All Menu Items";
                            }
                            ?>
                        </h2>
                        <p class="results-count">
                            Showing <?php echo $total_products; ?> 
                            <?php echo $total_products == 1 ? 'item' : 'items'; ?>
                        </p>
                    </div>

                    <!-- Products Grid -->
                    <div class="products-grid">
                        <?php 
                        if($total_products > 0):
                            mysqli_data_seek($products_result, 0);
                            while($product = mysqli_fetch_assoc($products_result)): 
                                // Determine image based on category
                                $image_map = array(
                                    1 => 'coffee.png',     // Hot Coffee
                                    2 => 'coffee.png',     // Iced Coffee
                                    3 => 'coffee.png',     // Non-Coffee
                                    4 => 'coffee.png',     // Milkshakes
                                    5 => 'coffee.png',     // Tea
                                    6 => 'pastry.png',     // Desserts
                                    7 => 'pastry.png',     // Pastry
                                    8 => 'pastry.png',     // Snacks
                                    9 => 'coffee.png'      // Add Ons
                                );
                                $product_image = isset($image_map[$product['category_id']]) ? $image_map[$product['category_id']] : 'coffee.png';
                        ?>
                            <div class="product-card" data-product-id="<?php echo $product['product_id']; ?>">
                                <!-- Product Image Section -->
                                <div class="product-image-wrapper">
                                    <!-- Favorite Heart Icon -->
                                    <div class="favorite-icon" onclick="toggleFavorite(<?php echo $product['product_id']; ?>, this.querySelector('i'))">
                                        <i class="far fa-heart"></i>
                                    </div>
                                    <!-- Category Badge -->
                                    <div class="category-badge">
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                    </div>
                                    <img src="images/<?php echo $product_image; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="product-image">
                                </div>
                                
                                <!-- Product Information Section -->
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <p class="product-description">
                                        <?php echo htmlspecialchars($product['description']); ?>
                                    </p>
                                    
                                    <!-- Product Footer with Price and Button -->
                                    <div class="product-footer">
                                        <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                        <button class="btn-order" onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endwhile;
                        else: 
                        ?>
                            <!-- Empty State when no products match filters -->
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
        </div>
    </section>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Main JavaScript -->
    <script src="js/main.js"></script>
    
    <!-- Menu Page Specific JavaScript -->
    <script src="js/menu-page.js"></script>
    
</body>
</html>