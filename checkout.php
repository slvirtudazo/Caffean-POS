<?php

/**
 * Purge Coffee Shop — Checkout Page (checkout.php)
 * Collects delivery address and payment method, then submits to place_order.php.
 */

require_once 'php/db_connection.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Cart must not be empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$shipping = 50.00;
$subtotal = 0;
$cart_items = [];

// Fetch products in cart
$ids_str = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
$res = mysqli_query($conn, "SELECT product_id, name, price FROM products WHERE product_id IN ($ids_str) AND status = 1");
while ($p = mysqli_fetch_assoc($res)) {
    $p['quantity']   = $_SESSION['cart'][$p['product_id']];
    $p['item_total'] = $p['price'] * $p['quantity'];
    $subtotal       += $p['item_total'];
    $cart_items[]    = $p;
}
$total = $subtotal + $shipping;

// Flash error from place_order.php
$error = $_SESSION['checkout_error'] ?? '';
unset($_SESSION['checkout_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Checkout — Purge Coffee</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/footer-section.css" />
    <link rel="stylesheet" href="css/search.css" />
    <style>
        .checkout-page {
            padding: 60px 0;
            background: var(--ivory-cream, #f5f1e8);
            min-height: 80vh;
        }

        .checkout-title {
            font-family: var(--font-heading);
            font-size: 2rem;
            color: var(--deep-maroon, #3c1518);
            margin-bottom: 30px;
            text-align: center;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .checkout-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid rgba(60, 21, 24, 0.1);
            box-shadow: 0 1px 4px rgba(60, 21, 24, 0.07);
            padding: 28px;
            margin-bottom: 24px;
        }

        .checkout-card h3 {
            font-family: var(--font-subheading);
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--deep-maroon, #3c1518);
            margin-bottom: 20px;
        }

        .form-label {
            font-family: var(--font-subheading);
            font-size: 0.85rem;
            font-weight: 600;
            color: rgba(60, 21, 24, 0.7);
        }

        .form-control,
        .form-select {
            border: 1px solid rgba(60, 21, 24, 0.2);
            border-radius: 8px;
            font-family: var(--font-body);
            padding: 10px 14px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--deep-maroon, #3c1518);
            box-shadow: 0 0 0 3px rgba(60, 21, 24, 0.1);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(60, 21, 24, 0.07);
            font-family: var(--font-body);
            font-size: 0.9rem;
            color: rgba(60, 21, 24, 0.8);
        }

        .summary-row:last-of-type {
            border-bottom: none;
        }

        .summary-total {
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--deep-maroon, #3c1518);
            margin-top: 8px;
            border-top: 2px solid rgba(60, 21, 24, 0.15);
            padding-top: 12px;
        }

        .btn-place-order {
            width: 100%;
            background: var(--deep-maroon, #3c1518);
            color: #fff;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-family: var(--font-subheading);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            letter-spacing: 0.5px;
        }

        .btn-place-order:hover {
            background: var(--burgundy-wine, #5b1312);
            transform: scale(1.02);
        }

        .cart-item-line {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 0.88rem;
            font-family: var(--font-body);
            color: rgba(60, 21, 24, 0.75);
            border-bottom: 1px solid rgba(60, 21, 24, 0.05);
        }

        .alert-error {
            background: #fdecea;
            color: #7b1a1a;
            border: 1px solid #f5c6c6;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-family: var(--font-body);
            font-size: 0.9rem;
        }

        .back-link {
            font-family: var(--font-subheading);
            font-size: 0.9rem;
            color: var(--deep-maroon, #3c1518);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 20px;
        }

        .back-link:hover {
            text-decoration: underline;
            color: var(--deep-maroon, #3c1518);
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Purge Coffee Logo" />
                <span>purge coffee</span>
            </a>
            <div class="nav-icons">
                <i class="fas fa-search nav-icon" onclick="showSearchOverlay()"></i>
                <a href="cart.php" class="text-decoration-none">
                    <i class="fas fa-shopping-cart nav-icon"></i>
                </a>
                <a href="account.php" class="text-decoration-none">
                    <i class="fas fa-user nav-icon"></i>
                </a>
            </div>
        </div>
    </nav>

    <section class="checkout-page">
        <div class="container">
            <h1 class="checkout-title">Checkout</h1>

            <?php if ($error): ?>
                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="php/place_order.php" method="POST">
                <div class="row g-4">

                    <!-- LEFT — Delivery & Payment -->
                    <div class="col-lg-7">

                        <!-- Delivery Address -->
                        <div class="checkout-card">
                            <h3><i class="fas fa-map-marker-alt me-2"></i>Delivery Address</h3>
                            <div class="mb-3">
                                <label class="form-label">Full Address *</label>
                                <textarea name="delivery_address" class="form-control" rows="3"
                                    placeholder="House/Unit No., Street, Barangay, City, Province"
                                    required><?= htmlspecialchars($_POST['delivery_address'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="checkout-card">
                            <h3><i class="fas fa-credit-card me-2"></i>Payment Method</h3>
                            <div class="mb-3">
                                <label class="form-label">Select Payment Method *</label>
                                <select name="payment_method" class="form-select" required>
                                    <option value="" disabled selected>— Choose method —</option>
                                    <option value="Cash on Delivery" <?= (($_POST['payment_method'] ?? '') === 'Cash on Delivery') ? 'selected' : '' ?>>Cash on Delivery</option>
                                    <option value="GCash" <?= (($_POST['payment_method'] ?? '') === 'GCash')            ? 'selected' : '' ?>>GCash</option>
                                    <option value="Maya" <?= (($_POST['payment_method'] ?? '') === 'Maya')             ? 'selected' : '' ?>>Maya</option>
                                    <option value="Bank Transfer" <?= (($_POST['payment_method'] ?? '') === 'Bank Transfer')    ? 'selected' : '' ?>>Bank Transfer</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <!-- RIGHT — Order Summary -->
                    <div class="col-lg-5">
                        <div class="checkout-card">
                            <h3><i class="fas fa-receipt me-2"></i>Order Summary</h3>

                            <!-- Items list -->
                            <div class="mb-3">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="cart-item-line">
                                        <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                                        <span>₱<?= number_format($item['item_total'], 2) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Totals -->
                            <div class="summary-row"><span>Subtotal</span><span>₱<?= number_format($subtotal, 2) ?></span></div>
                            <div class="summary-row"><span>Shipping</span><span>₱<?= number_format($shipping, 2) ?></span></div>
                            <div class="summary-row summary-total"><span>Total</span><span>₱<?= number_format($total, 2) ?></span></div>

                            <button type="submit" class="btn-place-order mt-4">
                                <i class="fas fa-check-circle me-2"></i>Place Order
                            </button>

                            <a href="cart.php" class="back-link d-block text-center mt-3">
                                <i class="fas fa-arrow-left"></i> Back to Cart
                            </a>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/search.js"></script>
</body>

</html>