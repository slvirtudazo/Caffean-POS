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

// Fetch user info
$user_stmt = mysqli_prepare($conn, "SELECT full_name, email FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_info = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
mysqli_stmt_close($user_stmt);

$shipping = 50.00;
$subtotal = 0;
$cart_items = [];

// Fetch products in cart
$ids_str = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
$res = mysqli_query($conn, "SELECT product_id, name, price FROM products WHERE product_id IN ($ids_str) AND status = 1");
while ($p = mysqli_fetch_assoc($res)) {
    $cart_entry      = $_SESSION['cart'][$p['product_id']];
    $p['quantity']   = is_array($cart_entry) ? (int)($cart_entry['quantity'] ?? 1) : (int)$cart_entry;
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
        .form-control, .form-select {
            border: 1px solid rgba(60, 21, 24, 0.2);
            border-radius: 8px;
            font-family: var(--font-body);
            padding: 10px 14px;
        }
        .form-control:focus, .form-select:focus {
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
        .summary-row:last-of-type { border-bottom: none; }
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error .dismiss-btn {
            margin-left: auto;
            background: none;
            border: none;
            color: #7b1a1a;
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
            line-height: 1;
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

        /* ── Payment Method Card Radio Buttons ─────────────────── */
        .payment-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .payment-option { position: relative; }
        .payment-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        .payment-option label {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: 1.5px solid rgba(60, 21, 24, 0.2);
            border-radius: 10px;
            cursor: pointer;
            font-family: var(--font-subheading);
            font-size: 0.9rem;
            font-weight: 600;
            color: rgba(60, 21, 24, 0.75);
            background: #fff;
            transition: all 0.18s ease;
            user-select: none;
        }
        .payment-option label:hover {
            border-color: var(--deep-maroon, #3c1518);
            background: rgba(60, 21, 24, 0.03);
            color: var(--deep-maroon, #3c1518);
        }
        .payment-option input[type="radio"]:checked + label {
            border-color: var(--deep-maroon, #3c1518);
            background: rgba(60, 21, 24, 0.07);
            color: var(--deep-maroon, #3c1518);
            box-shadow: 0 0 0 3px rgba(60, 21, 24, 0.08);
        }
        .payment-option label .pay-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(60, 21, 24, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: rgba(60, 21, 24, 0.5);
            transition: all 0.18s ease;
            flex-shrink: 0;
        }
        .payment-option input[type="radio"]:checked + label .pay-icon {
            background: var(--deep-maroon, #3c1518);
            color: #fff;
        }
        .payment-option label .pay-label { line-height: 1.2; }
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
                <div class="alert-error" id="checkoutError">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                    <button class="dismiss-btn" onclick="document.getElementById('checkoutError').remove()">&#x2715;</button>
                </div>
            <?php endif; ?>

            <form action="place_order.php" method="POST">

                <!-- Hidden fields — pulled from logged-in user session -->
                <input type="hidden" name="name"       value="<?= htmlspecialchars($user_info['full_name'] ?? '') ?>"/>
                <input type="hidden" name="email"      value="<?= htmlspecialchars($user_info['email'] ?? '') ?>"/>
                <input type="hidden" name="order_type" value="delivery"/>

                <div class="row g-4">

                    <!-- LEFT — Contact, Address & Payment -->
                    <div class="col-lg-7">

                        <!-- Contact Info -->
                        <div class="checkout-card">
                            <h3><i class="fas fa-user me-2"></i>Contact Information</h3>
                            <div class="mb-1">
                                <label class="form-label">Mobile Number *</label>
                                <input type="tel" name="mobile" class="form-control"
                                    placeholder="09XXXXXXXXX"
                                    pattern="^09\d{9}$"
                                    title="Enter a valid Philippine mobile number (e.g. 09123456789)"
                                    value="<?= htmlspecialchars($_POST['mobile'] ?? '') ?>"
                                    required/>
                            </div>
                        </div>

                        <!-- Delivery Address -->
                        <div class="checkout-card">
                            <h3><i class="fas fa-map-marker-alt me-2"></i>Delivery Address</h3>
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label">House / Unit No. *</label>
                                    <input type="text" name="house_unit" class="form-control"
                                        placeholder="e.g. Unit 4B / 123"
                                        value="<?= htmlspecialchars($_POST['house_unit'] ?? '') ?>"
                                        required/>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Street *</label>
                                    <input type="text" name="street_name" class="form-control"
                                        placeholder="Street name"
                                        value="<?= htmlspecialchars($_POST['street_name'] ?? '') ?>"
                                        required/>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Barangay *</label>
                                    <input type="text" name="barangay" class="form-control"
                                        placeholder="Barangay"
                                        value="<?= htmlspecialchars($_POST['barangay'] ?? '') ?>"
                                        required/>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">City / Municipality *</label>
                                    <input type="text" name="city_municipality" class="form-control"
                                        placeholder="City / Municipality"
                                        value="<?= htmlspecialchars($_POST['city_municipality'] ?? '') ?>"
                                        required/>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">Province *</label>
                                    <input type="text" name="province" class="form-control"
                                        placeholder="Province"
                                        value="<?= htmlspecialchars($_POST['province'] ?? '') ?>"
                                        required/>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label">ZIP Code *</label>
                                    <input type="text" name="zip_code" class="form-control"
                                        placeholder="ZIP Code"
                                        value="<?= htmlspecialchars($_POST['zip_code'] ?? '') ?>"
                                        required/>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Delivery Notes <span style="opacity:0.5">(optional)</span></label>
                                    <textarea name="delivery_notes" class="form-control" rows="2"
                                        placeholder="Landmark, gate code, special instructions…"><?= htmlspecialchars($_POST['delivery_notes'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="checkout-card">
                            <h3><i class="fas fa-credit-card me-2"></i>Payment Method</h3>
                            <div class="payment-options">

                                <div class="payment-option">
                                    <input type="radio" name="payment_method" id="pay_cod"
                                           value="Cash on Delivery"
                                           <?= (($_POST['payment_method'] ?? 'Cash on Delivery') === 'Cash on Delivery') ? 'checked' : '' ?>
                                           required/>
                                    <label for="pay_cod">
                                        <span class="pay-icon"><i class="fas fa-money-bill-wave"></i></span>
                                        <span class="pay-label">Cash on Delivery</span>
                                    </label>
                                </div>

                                <div class="payment-option">
                                    <input type="radio" name="payment_method" id="pay_gcash"
                                           value="GCash"
                                           <?= (($_POST['payment_method'] ?? '') === 'GCash') ? 'checked' : '' ?>/>
                                    <label for="pay_gcash">
                                        <span class="pay-icon"><i class="fas fa-mobile-alt"></i></span>
                                        <span class="pay-label">GCash</span>
                                    </label>
                                </div>

                                <div class="payment-option">
                                    <input type="radio" name="payment_method" id="pay_maya"
                                           value="Maya"
                                           <?= (($_POST['payment_method'] ?? '') === 'Maya') ? 'checked' : '' ?>/>
                                    <label for="pay_maya">
                                        <span class="pay-icon"><i class="fas fa-wallet"></i></span>
                                        <span class="pay-label">Maya</span>
                                    </label>
                                </div>

                                <div class="payment-option">
                                    <input type="radio" name="payment_method" id="pay_bank"
                                           value="Bank Transfer"
                                           <?= (($_POST['payment_method'] ?? '') === 'Bank Transfer') ? 'checked' : '' ?>/>
                                    <label for="pay_bank">
                                        <span class="pay-icon"><i class="fas fa-university"></i></span>
                                        <span class="pay-label">Bank Transfer</span>
                                    </label>
                                </div>

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
                                        <span><?= htmlspecialchars($item['name']) ?> &times; <?= $item['quantity'] ?></span>
                                        <span>&#8369;<?= number_format($item['item_total'], 2) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Totals -->
                            <div class="summary-row"><span>Subtotal</span><span>&#8369;<?= number_format($subtotal, 2) ?></span></div>
                            <div class="summary-row"><span>Shipping</span><span>&#8369;<?= number_format($shipping, 2) ?></span></div>
                            <div class="summary-row summary-total"><span>Total</span><span>&#8369;<?= number_format($total, 2) ?></span></div>

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