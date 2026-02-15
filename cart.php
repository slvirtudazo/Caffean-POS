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
    <link rel="stylesheet" href="css/buttons.css">
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>
    
    <!-- Top banner -->
    <div class="top-banner">Shipping Nationwide</div>

    <!-- Navigation -->
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

    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <!-- Page Title (No divider below) -->
            <div class="section-header">
                <h2 class="section-title">SHOPPING CART</h2>
            </div>

            <?php if (empty($cart_items)): ?>
                <!-- Empty Cart State -->
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added any items to your cart yet. Browse our delicious menu!</p>
                    <a href="menu.php" class="btn-start-shopping">Start Shopping</a>
                </div>
            <?php else: ?>
                <!-- Cart Items and Summary -->
                <div class="row">
                    <!-- Cart Items List -->
                    <div class="col-lg-8">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <!-- Product Image -->
                                <img src="images/<?php echo $item['category_id'] == 3 ? 'pastry.png' : ($item['category_id'] == 2 ? 'iced_coffee.png' : 'coffee.png'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="cart-item-image">
                                
                                <!-- Product Info -->
                                <div class="cart-item-info">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p class="cart-item-price">₱<?php echo number_format($item['price'], 2); ?></p>
                                    
                                    <!-- Quantity Controls -->
                                    <form method="POST" class="quantity-form">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <input type="hidden" name="action" value="update">
                                        <div class="quantity-control">
                                            <button type="button" class="qty-btn" onclick="updateQty(<?php echo $item['product_id']; ?>, -1, <?php echo $item['quantity']; ?>)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                   class="qty-input" min="1" readonly>
                                            <button type="button" class="qty-btn" onclick="updateQty(<?php echo $item['product_id']; ?>, 1, <?php echo $item['quantity']; ?>)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Item Total & Remove -->
                                <div class="cart-item-actions">
                                    <div class="item-total">₱<?php echo number_format($item['item_total'], 2); ?></div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="remove-btn" onclick="return confirm('Remove this item from cart?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Order Summary Sidebar -->
                    <div class="col-lg-4">
                        <div class="cart-summary">
                            <h3>Order Summary</h3>
                            
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>₱<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span>₱<?php echo number_format($shipping, 2); ?></span>
                            </div>
                            
                            <div class="summary-row summary-total">
                                <span>Total</span>
                                <span>₱<?php echo number_format($total, 2); ?></span>
                            </div>
                            
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <button class="btn-checkout btn-full-width" onclick="alert('Checkout functionality will be implemented soon!')">
                                    Proceed to Checkout
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn-checkout btn-full-width">
                                    Login to Checkout
                                </a>
                            <?php endif; ?>
                            
                            <a href="menu.php" class="continue-shopping">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        function updateQty(productId, change, currentQty) {
            const newQty = currentQty + change;
            if (newQty > 0) {
                const form = document.querySelector(`form input[value="${productId}"]`).closest('form');
                const qtyInput = form.querySelector('input[name="quantity"]');
                qtyInput.value = newQty;
                form.submit();
            }
        }
    </script>
</body>
</html>