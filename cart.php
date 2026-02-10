<?php
/**
 * Purge Coffee Shop - Shopping Cart Page
 * This page displays all items in the customer's cart with quantity controls,
 * price calculations, and checkout functionality. Uses session storage for cart management.
 */

require_once 'php/db_connection.php';

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

$cart_items = array();
$subtotal = 0;
$shipping = 50.00; // Flat shipping rate
$total = 0;

// Fetch product details for items in cart
if (!empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $ids_string = implode(',', array_map('intval', $product_ids));
    
    $query = "SELECT product_id, name, price, category_id FROM products WHERE product_id IN ($ids_string)";
    $result = mysqli_query($conn, $query);
    
    while ($product = mysqli_fetch_assoc($result)) {
        $product['quantity'] = $_SESSION['cart'][$product['product_id']];
        $product['item_total'] = $product['price'] * $product['quantity'];
        $subtotal += $product['item_total'];
        $cart_items[] = $product;
    }
    
    $total = $subtotal + $shipping;
}

// Handle cart updates via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $product_id = intval($_POST['product_id']);
        
        if ($_POST['action'] == 'update' && isset($_POST['quantity'])) {
            $quantity = intval($_POST['quantity']);
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        } elseif ($_POST['action'] == 'remove') {
            unset($_SESSION['cart'][$product_id]);
        }
        
        // Reload page to reflect changes
        header("Location: cart.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Purge Coffee</title>
    
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        /* Additional styles specific to the cart page */
        .cart-section {
            padding: var(--spacing-xxl) 0;
            background-color: var(--ivory-cream);
            min-height: 80vh;
        }
        
        .cart-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .cart-item {
            background: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-md);
            box-shadow: var(--shadow-sm);
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: var(--spacing-md);
            align-items: center;
            transition: var(--transition-normal);
        }
        
        .cart-item:hover {
            box-shadow: var(--shadow-md);
        }
        
        .cart-item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: var(--radius-md);
        }
        
        .cart-item-info h3 {
            font-family: var(--font-subheading);
            color: var(--deep-maroon);
            font-size: 1.25rem;
            margin-bottom: var(--spacing-xs);
        }
        
        .cart-item-price {
            color: var(--burgundy-wine);
            font-weight: 600;
            font-size: 1.125rem;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-sm);
        }
        
        .qty-btn {
            width: 35px;
            height: 35px;
            border: 2px solid var(--burgundy-wine);
            background: white;
            color: var(--burgundy-wine);
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-fast);
        }
        
        .qty-btn:hover {
            background: var(--burgundy-wine);
            color: white;
        }
        
        .qty-input {
            width: 60px;
            text-align: center;
            border: 2px solid var(--warm-sand);
            border-radius: var(--radius-sm);
            padding: var(--spacing-xs);
            font-weight: 600;
        }
        
        .remove-btn {
            background: transparent;
            border: none;
            color: #c33;
            cursor: pointer;
            font-size: 1.5rem;
            padding: var(--spacing-sm);
            transition: var(--transition-fast);
        }
        
        .remove-btn:hover {
            color: #a11;
            transform: scale(1.1);
        }
        
        .cart-summary {
            background: white;
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 100px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--warm-sand);
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--deep-maroon);
            margin-top: var(--spacing-md);
            padding-top: var(--spacing-md);
            border-top: 2px solid var(--deep-maroon);
        }
        
        .empty-cart {
            text-align: center;
            padding: var(--spacing-xxl);
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .empty-cart i {
            font-size: 5rem;
            color: var(--warm-sand);
            margin-bottom: var(--spacing-md);
        }
        
        .btn-checkout {
            width: 100%;
            background: var(--deep-maroon);
            color: white;
            border: none;
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-family: var(--font-subheading);
            font-weight: 600;
            font-size: 1.125rem;
            cursor: pointer;
            transition: var(--transition-normal);
            margin-top: var(--spacing-md);
        }
        
        .btn-checkout:hover {
            background: var(--burgundy-wine);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    
    <!-- Top banner displaying shipping information -->
    <div class="top-banner">Shipping Nationwide</div>

    <!-- Main navigation bar -->
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
                        <a class="nav-link" href="menu.php">Menu</a>
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

    <!-- Main cart section -->
    <section class="cart-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Shopping Cart</h2>
                <div class="section-divider"></div>
            </div>

            <?php if (empty($cart_items)): ?>
                <!-- Display empty cart message if no items -->
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3 style="font-family: var(--font-heading); color: var(--deep-maroon); margin-bottom: var(--spacing-md);">
                        Your cart is empty
                    </h3>
                    <p style="color: var(--dark-brown); margin-bottom: var(--spacing-lg);">
                        Looks like you haven't added any items to your cart yet. Browse our delicious menu!
                    </p>
                    <a href="coffee.php" class="btn-primary">Start Shopping</a>
                </div>
            <?php else: ?>
                <!-- Display cart items and summary -->
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Loop through all cart items -->
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <!-- Product image based on category -->
                                <img src="images/<?php echo $item['category_id'] == 3 ? 'pastry.png' : 'coffee.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="cart-item-image">
                                
                                <!-- Product information and controls -->
                                <div class="cart-item-info">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <div class="cart-item-price">â‚±<?php echo number_format($item['price'], 2); ?></div>
                                    
                                    <!-- Quantity adjustment controls -->
                                    <form method="POST" class="quantity-control">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <input type="hidden" name="action" value="update">
                                        
                                        <button type="submit" class="qty-btn" 
                                                onclick="this.nextElementSibling.value = Math.max(1, parseInt(this.nextElementSibling.value) - 1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        
                                        <input type="number" name="quantity" class="qty-input" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="99" 
                                               onchange="this.form.submit()">
                                        
                                        <button type="submit" class="qty-btn" 
                                                onclick="this.previousElementSibling.value = Math.min(99, parseInt(this.previousElementSibling.value) + 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </form>
                                    
                                    <div style="margin-top: var(--spacing-xs); font-weight: 600; color: var(--dark-brown);">
                                        Item Total: â‚±<?php echo number_format($item['item_total'], 2); ?>
                                    </div>
                                </div>
                                
                                <!-- Remove item button -->
                                <form method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="remove-btn" title="Remove from cart">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Order summary sidebar -->
                    <div class="col-lg-4">
                        <div class="cart-summary">
                            <h3 style="font-family: var(--font-heading); color: var(--deep-maroon); margin-bottom: var(--spacing-md);">
                                Order Summary
                            </h3>
                            
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>â‚±<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span>â‚±<?php echo number_format($shipping, 2); ?></span>
                            </div>
                            
                            <div class="summary-row">
                                <span>Total</span>
                                <span>â‚±<?php echo number_format($total, 2); ?></span>
                            </div>
                            
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <button class="btn-checkout" onclick="alert('Checkout functionality will be implemented soon!')">
                                    Proceed to Checkout
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn-checkout" style="text-decoration: none; display: block; text-align: center;">
                                    Login to Checkout
                                </a>
                            <?php endif; ?>
                            
                            <a href="coffee.php" style="display: block; text-align: center; margin-top: var(--spacing-md); color: var(--burgundy-wine); text-decoration: none; font-weight: 600;">
                                <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    
</body>
</html>