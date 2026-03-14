<?php

// Checkout Page — collects customer info, order type, address or pickup, and payment method.
// Shows a receipt dialog with a save-as-PNG option on successful order.
require_once 'php/db_connection.php';
require_once 'php/product_images.php';

// Redirect admin users away from checkout.
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

// Require authentication.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Redirect to cart if it's empty.
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// Normalize legacy integer cart entries.
foreach ($_SESSION['cart'] as $pid => &$v) {
    if (!is_array($v)) {
        $v = [
            'quantity'             => (int)$v,
            'size'                 => 'Short',
            'temperature'          => 'Hot',
            'sugar_level'          => '0%',
            'milk'                 => 'Whole',
            'addons'               => [],
            'special_instructions' => ''
        ];
    }
}
unset($v);

$user_id = $_SESSION['user_id'];

// Fetch user profile info and saved default address.
$user_stmt = mysqli_prepare(
    $conn,
    "SELECT full_name, email, mobile_number, house_unit, street_name, barangay,
            city_municipality, province, zip_code
     FROM users WHERE user_id = ?"
);
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_info = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
mysqli_stmt_close($user_stmt);

// Check if the user has a usable saved default address.
$has_default_addr = !empty($user_info['house_unit']) && !empty($user_info['city_municipality']);

// Fetch active cart products from the database.
$cart_items   = [];
$subtotal     = 0.0;
$DELIVERY_FEE = 50.0;

$ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
$res = mysqli_query(
    $conn,
    "SELECT product_id, name, price, image_path FROM products
     WHERE product_id IN ($ids) AND status = 1"
);
while ($p = mysqli_fetch_assoc($res)) {
    $pid2                      = $p['product_id'];
    $o                         = $_SESSION['cart'][$pid2];
    $p['quantity']             = $o['quantity'];
    $p['size']                 = $o['size']                 ?? 'Short';
    $p['temperature']          = $o['temperature']          ?? 'Hot';
    $p['sugar_level']          = $o['sugar_level']          ?? '0%';
    $p['milk']                 = $o['milk']                 ?? 'Whole';
    $p['addons']               = $o['addons']               ?? [];
    $p['special_instructions'] = $o['special_instructions'] ?? '';
    $p['item_total']           = $p['price'] * $p['quantity'];
    $p['image_path']           = resolveProductImage($p['name'], $p['image_path']);
    $subtotal                 += $p['item_total'];
    $cart_items[]              = $p;
}

$min_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Checkout — Caffean</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/buttons.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/checkout.css?v=<?php echo time(); ?>">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Caffean" />
                <span>caffean</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="menu.php">Menu</a></li>
                    <li class="nav-item"><a class="nav-link" href="supplies-page.php">Supplies</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                </ul>
            </div>
            <div class="nav-icons">
                <i class="fas fa-search nav-icon" onclick="showSearchOverlay()"></i>
                <a href="cart.php" class="text-decoration-none">
                    <i class="fas fa-shopping-cart nav-icon"></i>
                </a>
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

    <!-- Checkout Section -->
    <section class="checkout-page">
        <div class="container">

            <h1 class="checkout-title">CHECKOUT</h1>
            <div id="s2-alert-zone"></div>

            <div class="row g-4">

                <!-- LEFT: Forms -->
                <div class="col-lg-7">

                    <!-- Customer Information -->
                    <div class="checkout-card">
                        <h3><i class="fas fa-user"></i>Customer Information</h3>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Full Name *</label>
                                <input type="text" id="co-name" class="form-control"
                                    value="<?= htmlspecialchars($user_info['full_name'] ?? '') ?>"
                                    autocomplete="name" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address *</label>
                                <input type="email" id="co-email" class="form-control"
                                    value="<?= htmlspecialchars($user_info['email'] ?? '') ?>"
                                    autocomplete="email" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mobile Number *</label>
                                <input type="tel" id="co-mobile" class="form-control"
                                    value="<?= htmlspecialchars($user_info['mobile_number'] ?? '') ?>"
                                    maxlength="11"
                                    inputmode="numeric"
                                    autocomplete="tel" />
                            </div>
                        </div>
                    </div>

                    <!-- Order Type -->
                    <div class="checkout-card">
                        <h3><i class="fas fa-location-dot"></i>Order Type</h3>

                        <div class="payment-options order-type-options">
                            <div class="payment-option">
                                <input type="radio" name="order_type" id="order-delivery"
                                    value="delivery" onchange="switchOrderType('delivery')" checked />
                                <label for="order-delivery">
                                    <span class="pay-icon"><i class="fas fa-truck"></i></span>
                                    <span class="pay-label">Delivery</span>
                                </label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" name="order_type" id="order-pickup"
                                    value="pickup" onchange="switchOrderType('pickup')" />
                                <label for="order-pickup">
                                    <span class="pay-icon"><i class="fas fa-store"></i></span>
                                    <span class="pay-label">Pickup</span>
                                </label>
                            </div>
                        </div>

                        <!-- Delivery Address Fields -->
                        <div id="delivery-fields" class="mt-3">

                            <?php if ($has_default_addr): ?>

                                <div class="addr-source-toggle mb-3">
                                    <button type="button" class="addr-src-btn active" id="btn-use-default"
                                        onclick="useDefaultAddress()">Use Default Address
                                    </button>
                                    <button type="button" class="addr-src-btn" id="btn-new-addr"
                                        onclick="useNewAddress()">Enter New Address
                                    </button>
                                </div>
                                <!-- Saved default address preview -->
                                <div class="addr-default-preview" id="addr-default-preview">
                                    <div class="addr-preview-text">
                                        <span class="addr-preview-label">Delivering to your saved address</span>
                                        <span class="addr-preview-detail">
                                            <?= htmlspecialchars(implode(', ', array_filter([
                                                $user_info['house_unit'],
                                                $user_info['street_name'],
                                                $user_info['barangay'],
                                                $user_info['city_municipality'],
                                                $user_info['province'],
                                                $user_info['zip_code']
                                            ]))) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="row g-3" id="addr-form-fields"
                                <?= $has_default_addr ? 'style="display:none;"' : '' ?>>
                                <div class="col-md-6">
                                    <label class="form-label">House / Unit No. *</label>
                                    <input type="text" id="del-house" class="form-control"
                                        autocomplete="address-line1" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Street *</label>
                                    <input type="text" id="del-street" class="form-control"
                                        autocomplete="address-line2" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Barangay *</label>
                                    <input type="text" id="del-brgy" class="form-control" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City / Municipality *</label>
                                    <input type="text" id="del-city" class="form-control"
                                        autocomplete="address-level2" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Province *</label>
                                    <input type="text" id="del-province" class="form-control"
                                        autocomplete="address-level1" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">ZIP Code *</label>
                                    <input type="text" id="del-zip" class="form-control"
                                        maxlength="4"
                                        inputmode="numeric"
                                        autocomplete="postal-code" />
                                </div>
                                <div class="col-12">
                                    <label class="form-label">
                                        Delivery Notes <span class="opt-text">(Optional)</span>
                                    </label>
                                    <textarea id="del-notes" class="form-control del-notes-ta" rows="3"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Pickup Fields -->
                        <div id="pickup-fields" class="mt-3" style="display:none;">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Pickup Branch *</label>
                                    <select id="pick-branch" class="form-select">
                                        <option value="">Select Branch</option>
                                        <option value="Diversion Road, Matina Balusong (Main)">Diversion Road, Matina Balusong (Main)</option>
                                        <option value="Quimpo Boulevard, Ecoland">Quimpo Boulevard, Ecoland</option>
                                        <option value="J.P. Laurel Avenue, Lanang">J.P. Laurel Avenue, Lanang</option>
                                        <option value="Polo Street, Obrero">Polo Street, Obrero</option>
                                        <option value="Prime Square, F. Torres">Prime Square, F. Torres</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pickup Date *</label>
                                    <input type="text" id="pick-date" class="form-control"
                                        placeholder="mm/dd/yyyy"
                                        min="<?= $min_date ?>"
                                        onfocus="this.type='date'"
                                        onblur="if(!this.value) this.type='text'" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pickup Time *</label>
                                    <select id="pick-time" class="form-select">
                                        <option value="">Select Time</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-card">
                        <h3><i class="fas fa-credit-card"></i>Payment Method</h3>
                        <div class="payment-options">
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="pay-cod"
                                    value="Cash on Delivery" checked />
                                <label for="pay-cod">
                                    <span class="pay-icon"><i class="fas fa-money-bill-wave"></i></span>
                                    <span class="pay-label">Cash on Delivery</span>
                                </label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="pay-gcash"
                                    value="GCash" />
                                <label for="pay-gcash">
                                    <span class="pay-icon"><i class="fas fa-mobile-alt"></i></span>
                                    <span class="pay-label">GCash</span>
                                </label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="pay-maya"
                                    value="Maya" />
                                <label for="pay-maya">
                                    <span class="pay-icon"><i class="fas fa-wallet"></i></span>
                                    <span class="pay-label">Maya</span>
                                </label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="pay-bank"
                                    value="GoTyme" />
                                <label for="pay-bank">
                                    <span class="pay-icon"><i class="fas fa-building-columns"></i></span>
                                    <span class="pay-label">GoTyme</span>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- RIGHT: Order Summary -->
                <div class="col-lg-5">
                    <div class="checkout-card order-summary sum-card sum-sticky">
                        <h3 class="sum-title">
                            <i class="fas fa-receipt me-2"></i>Order Summary
                        </h3>

                        <div class="sum-items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="sum-item-line">
                                    <span class="sil-name">
                                        <?= htmlspecialchars($item['name']) ?>
                                        <span class="sil-qty">× <?= $item['quantity'] ?></span>
                                        <span class="sil-opts">
                                            <?= htmlspecialchars($item['size'] . ' · ' . $item['temperature'] . ' · Sugar ' . $item['sugar_level']) ?>
                                        </span>
                                    </span>
                                    <span class="sil-price">
                                        ₱<?= number_format($item['item_total'], 2) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="sum-bottom">
                            <div class="sum-hr"></div>
                            <div class="sum-calc-row">
                                <span>Subtotal</span>
                                <span>₱<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="sum-calc-row" id="co-fee-row">
                                <span>Delivery Fee</span>
                                <span id="co-fee">₱<?= number_format($DELIVERY_FEE, 2) ?></span>
                            </div>
                            <div class="sum-hr"></div>
                            <div class="sum-total-row">
                                <span>Total Amount</span>
                                <span id="co-total">₱<?= number_format($subtotal + $DELIVERY_FEE, 2) ?></span>
                            </div>

                            <button class="btn-cart-main mt-3" id="btn-place-order" onclick="placeOrder()">
                                <i class="fas fa-check-circle me-2"></i>Place Order
                            </button>
                            <a href="cart.php" class="btn-cart-back mt-2 d-block text-decoration-none text-center">
                                <i class="fas fa-arrow-left me-1"></i>Back to Cart
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Order Confirmation Receipt Dialog -->
    <div class="receipt-overlay" id="receipt-overlay">
        <div class="receipt-dialog">

            <!-- Capturable receipt area -->
            <div class="receipt-content" id="receipt-content">

                <!-- Shop logo and name -->
                <div class="receipt-logo-wrap">
                    <img src="images/coffee_beans_logo.png" alt="Caffean" class="receipt-logo-img" />
                    <span class="receipt-logo-name">Caffean</span>
                </div>

                <hr class="receipt-divider" />

                <!-- Heading -->
                <div class="receipt-thank-you">Thank you for your purchase!</div>
                <div class="receipt-subline">Your order has been received and is being processed.</div>

                <!-- Order number box with copy button -->
                <div class="receipt-order-box">
                    <div class="receipt-order-box-inner">
                        <span class="receipt-order-label">Order number</span>
                        <span class="receipt-order-num" id="r-order-num">—</span>
                    </div>
                    <button class="receipt-copy-btn" onclick="copyOrderNum()" title="Copy order number">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>

                <!-- Status badge + date -->
                <div class="receipt-status-row">
                    <span class="receipt-status-badge">Order Confirmed</span>
                    <span class="receipt-datetime" id="r-datetime">—</span>
                </div>

                <hr class="receipt-divider" />

                <!-- Customer section -->
                <div class="receipt-section-hd">Customer</div>
                <div class="receipt-info-row">
                    <span class="r-key">Name</span>
                    <span class="r-val" id="r-name">—</span>
                </div>
                <div class="receipt-info-row">
                    <span class="r-key">Email</span>
                    <span class="r-val" id="r-email">—</span>
                </div>
                <div class="receipt-info-row">
                    <span class="r-key">Mobile</span>
                    <span class="r-val" id="r-mobile">—</span>
                </div>

                <hr class="receipt-divider" />

                <!-- Order details section -->
                <div class="receipt-section-hd">Order Details</div>
                <div class="receipt-info-row">
                    <span class="r-key">Type</span>
                    <span class="r-val" id="r-type">—</span>
                </div>
                <div class="receipt-info-row" id="r-address-row" style="display:none;">
                    <span class="r-key">Address</span>
                    <span class="r-val" id="r-address">—</span>
                </div>
                <div class="receipt-info-row" id="r-branch-row" style="display:none;">
                    <span class="r-key">Branch</span>
                    <span class="r-val" id="r-branch">—</span>
                </div>
                <div class="receipt-info-row" id="r-pickup-row" style="display:none;">
                    <span class="r-key">Pickup</span>
                    <span class="r-val" id="r-pickup">—</span>
                </div>
                <div class="receipt-info-row">
                    <span class="r-key">Payment</span>
                    <span class="r-val" id="r-payment">—</span>
                </div>
                <div class="receipt-info-row" id="r-notes-row" style="display:none;">
                    <span class="r-key">Delivery Notes</span>
                    <span class="r-val r-val-note" id="r-del-notes">—</span>
                </div>

                <hr class="receipt-divider" />

                <!-- Items section -->
                <div class="receipt-section-hd">Order Summary</div>
                <div id="r-items"></div>

                <hr class="receipt-divider" />

                <!-- Totals -->
                <div class="receipt-amount-row">
                    <span>Subtotal</span>
                    <span id="r-subtotal">—</span>
                </div>
                <div class="receipt-amount-row" id="r-fee-row">
                    <span>Delivery Fee</span>
                    <span id="r-fee">—</span>
                </div>
                <hr class="receipt-divider" />
                <div class="receipt-total-row">
                    <span>Total Amount</span>
                    <span id="r-total">—</span>
                </div>

                <hr class="receipt-divider" />
                <div class="receipt-footer-note">Thank you for choosing Caffean!
                </div>
            </div>

            <!-- Action buttons — excluded from PNG capture -->
            <div class="receipt-actions">
                <button class="btn-receipt-close" onclick="closeReceipt()">
                    Close
                </button>
                <button class="btn-receipt-save" onclick="saveReceiptPng()">
                    Save Receipt
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
    <script>
        const DELIVERY_FEE = <?= $DELIVERY_FEE ?>;
        const subtotal = <?= $subtotal ?>;
        let orderType = 'delivery';

        // Saved default address from the user profile.
        const defaultAddr = {
            house_unit: <?= json_encode($user_info['house_unit']        ?? '') ?>,
            street_name: <?= json_encode($user_info['street_name']       ?? '') ?>,
            barangay: <?= json_encode($user_info['barangay']          ?? '') ?>,
            city_municipality: <?= json_encode($user_info['city_municipality'] ?? '') ?>,
            province: <?= json_encode($user_info['province']          ?? '') ?>,
            zip_code: <?= json_encode($user_info['zip_code']          ?? '') ?>
        };

        // Track whether the user is using their saved address or entering a new one.
        let usingDefaultAddr = <?= $has_default_addr ? 'true' : 'false' ?>;

        // Pre-fill delivery fields with the saved default address.
        function useDefaultAddress() {
            usingDefaultAddr = true;
            document.getElementById('btn-use-default')?.classList.add('active');
            document.getElementById('btn-new-addr')?.classList.remove('active');
            document.getElementById('addr-form-fields').style.display = 'none';
            document.getElementById('addr-default-preview').style.display = 'flex';
        }

        // Show blank delivery fields for a new address.
        function useNewAddress() {
            usingDefaultAddr = false;
            document.getElementById('btn-new-addr')?.classList.add('active');
            document.getElementById('btn-use-default')?.classList.remove('active');
            document.getElementById('addr-form-fields').style.display = '';
            document.getElementById('addr-default-preview').style.display = 'none';
        }

        // Pass cart items from PHP to JS for receipt rendering.
        const cartItems = <?= json_encode(array_map(function ($i) {
                                return [
                                    'name'        => $i['name'],
                                    'quantity'    => $i['quantity'],
                                    'size'        => $i['size'],
                                    'temperature' => $i['temperature'],
                                    'sugar_level' => $i['sugar_level'],
                                    'item_total'  => $i['item_total'],
                                    'image_path'             => $i['image_path'] ?? '',
                                    'special_instructions'   => $i['special_instructions'] ?? ''
                                ];
                            }, $cart_items)) ?>;

        // Format a number with commas.
        function fmt(n) {
            return parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // Toggle delivery or pickup fields and update the fee.
        function switchOrderType(type) {
            orderType = type;
            const isDel = type === 'delivery';
            document.getElementById('delivery-fields').style.display = isDel ? 'block' : 'none';
            document.getElementById('pickup-fields').style.display = isDel ? 'none' : 'block';
            document.getElementById('co-fee-row').style.display = isDel ? 'flex' : 'none';
            document.getElementById('co-total').textContent = '₱' + fmt(subtotal + (isDel ? DELIVERY_FEE : 0));
        }

        // Restrict an input field to digits only.
        function digitsOnly(el) {
            el.addEventListener('keydown', function(e) {
                const ctrl = e.ctrlKey || e.metaKey;
                const nav = [8, 9, 35, 36, 37, 39, 46].includes(e.keyCode);
                if (!ctrl && !nav && !/^\d$/.test(e.key)) e.preventDefault();
            });
            el.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
            });
            el.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text');
                const digits = pasted.replace(/\D/g, '');
                const max = parseInt(this.getAttribute('maxlength') || '99');
                this.value = (this.value + digits).slice(0, max);
            });
        }

        digitsOnly(document.getElementById('co-mobile'));
        digitsOnly(document.getElementById('del-zip'));

        // Validate email format.
        function validEmail(v) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);
        }

        // Validate, submit the order, and show the receipt on success.
        function placeOrder() {
            clearAlert();
            const name = document.getElementById('co-name').value.trim();
            const email = document.getElementById('co-email').value.trim();
            const mobile = document.getElementById('co-mobile').value.trim();
            const payment = document.querySelector('input[name="payment_method"]:checked')?.value || '';

            if (!name) return showAlert('Please enter your full name.');
            if (!validEmail(email)) return showAlert('Please enter a valid email address.');
            if (!/^09\d{9}$/.test(mobile))
                return showAlert('Enter a valid PH mobile number (+63 9XX XXX XXXX).');
            if (!payment) return showAlert('Please select a payment method.');

            const data = {
                name,
                email,
                mobile,
                payment_method: payment,
                order_type: orderType
            };

            if (orderType === 'delivery') {
                let house, street, brgy, city, province, zip;

                if (usingDefaultAddr) {
                    // Use the saved default address.
                    house = defaultAddr.house_unit;
                    street = defaultAddr.street_name;
                    brgy = defaultAddr.barangay;
                    city = defaultAddr.city_municipality;
                    province = defaultAddr.province;
                    zip = defaultAddr.zip_code;
                } else {
                    // Use the manually entered address.
                    house = document.getElementById('del-house').value.trim();
                    street = document.getElementById('del-street').value.trim();
                    brgy = document.getElementById('del-brgy').value.trim();
                    city = document.getElementById('del-city').value.trim();
                    province = document.getElementById('del-province').value.trim();
                    zip = document.getElementById('del-zip').value.trim();
                }

                if (!house || !street || !brgy || !city || !province || !zip)
                    return showAlert('Please fill in all required delivery address fields.');
                if (!/^\d{4}$/.test(zip))
                    return showAlert('Enter a valid 4-digit Philippine ZIP code.');
                Object.assign(data, {
                    house_unit: house,
                    street_name: street,
                    barangay: brgy,
                    city_municipality: city,
                    province,
                    zip_code: zip,
                    delivery_notes: document.getElementById('del-notes').value.trim()
                });
            } else {
                const branch = document.getElementById('pick-branch').value;
                const date = document.getElementById('pick-date').value;
                const time = document.getElementById('pick-time').value;
                if (!branch) return showAlert('Please select a pickup branch.');
                if (!date) return showAlert('Please select a pickup date.');
                if (!time) return showAlert('Please select a pickup time.');
                Object.assign(data, {
                    pickup_branch: branch,
                    pickup_date: date,
                    pickup_time: time
                });
            }

            const btn = document.getElementById('btn-place-order');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Placing Order…';

            const fd = new FormData();
            Object.entries(data).forEach(([k, v]) => fd.append(k, v));

            fetch('php/place_order.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        // Show the receipt dialog before redirecting.
                        buildReceipt(d.order_id, data);
                        document.getElementById('receipt-overlay').classList.add('open');
                    } else {
                        showAlert(d.message || 'Something went wrong. Please try again.');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Place Order';
                    }
                })
                .catch(() => {
                    showAlert('Network error. Please check your connection and try again.');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Place Order';
                });
        }

        // Generate a randomised order number.
        function genOrderNum(orderId) {
            const year = new Date().getFullYear();
            const pad = String(orderId).padStart(3, '0');
            const rand = String(Math.floor(10 + Math.random() * 90));
            return 'ORD-' + year + '-' + rand + pad;
        }

        // Copy the order number to the clipboard.
        let _orderNumStr = '';

        function copyOrderNum() {
            if (!_orderNumStr) return;
            navigator.clipboard.writeText(_orderNumStr).then(() => {
                const btn = document.querySelector('.receipt-copy-btn');
                btn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-copy"></i>';
                }, 2000);
            });
        }

        // Populate the receipt dialog with order data.
        function buildReceipt(orderId, data) {
            const isDel = data.order_type === 'delivery';
            const total = subtotal + (isDel ? DELIVERY_FEE : 0);
            const now = new Date();

            // Set the order number in the receipt.
            _orderNumStr = genOrderNum(orderId);
            document.getElementById('r-order-num').textContent = _orderNumStr;

            // Set the date and time.
            const dateStr = now.toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }) + ' at ' + now.toLocaleTimeString('en-PH', {
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('r-datetime').textContent = dateStr;

            // Set customer details.
            document.getElementById('r-name').textContent = data.name;
            document.getElementById('r-email').textContent = data.email;
            document.getElementById('r-mobile').textContent = data.mobile;

            // Set order type and payment details.
            document.getElementById('r-type').textContent = isDel ? 'Delivery' : 'Pickup';
            document.getElementById('r-payment').textContent = data.payment_method;

            if (isDel) {
                // Format the address into 3 lines.
                const addrLines = [
                    [data.house_unit, data.street_name].filter(Boolean).join(', '),
                    [data.barangay, data.city_municipality].filter(Boolean).join(', '),
                    [data.province, data.zip_code].filter(Boolean).join(', ')
                ].filter(Boolean);
                document.getElementById('r-address').innerHTML = addrLines.join('<br>');
                document.getElementById('r-address-row').style.display = 'flex';
                document.getElementById('r-branch-row').style.display = 'none';
                document.getElementById('r-pickup-row').style.display = 'none';
                // Set delivery notes if provided.
                const notes = (data.delivery_notes || '').trim();
                document.getElementById('r-del-notes').textContent = notes || 'None';
                document.getElementById('r-notes-row').style.display = 'flex';
            } else {
                document.getElementById('r-branch').textContent = data.pickup_branch;
                document.getElementById('r-pickup').textContent = data.pickup_date + ' at ' + data.pickup_time;
                document.getElementById('r-branch-row').style.display = 'flex';
                document.getElementById('r-pickup-row').style.display = 'flex';
                document.getElementById('r-address-row').style.display = 'none';
                document.getElementById('r-notes-row').style.display = 'none';
            }

            // Render order items with thumbnails.
            const itemsEl = document.getElementById('r-items');
            itemsEl.innerHTML = cartItems.map(item => {
                const imgHtml = item.image_path ?
                    `<img class="receipt-item-img" src="${item.image_path}" alt="${item.name}" crossorigin="anonymous" />` :
                    `<div class="receipt-item-img-placeholder"><i class="bi bi-cup-hot"></i></div>`;
                const siNote = item.special_instructions ?
                    `<div class="receipt-item-si">${item.special_instructions}</div>` : '';
                return `
                <div class="receipt-item">
                    ${imgHtml}
                    <div class="receipt-item-detail">
                        <div class="receipt-item-name">${item.name}</div>
                        <div class="receipt-item-meta">${item.size} · ${item.temperature} · Sugar ${item.sugar_level}</div>
                        <div class="receipt-item-qty">Qty: ${item.quantity}</div>
                        ${siNote}
                    </div>
                    <div class="receipt-item-price">₱${fmt(item.item_total)}</div>
                </div>`;
            }).join('');

            // Set totals.
            document.getElementById('r-subtotal').textContent = '₱' + fmt(subtotal);
            if (isDel) {
                document.getElementById('r-fee').textContent = '₱' + fmt(DELIVERY_FEE);
                document.getElementById('r-fee-row').style.display = 'flex';
            } else {
                document.getElementById('r-fee-row').style.display = 'none';
            }
            document.getElementById('r-total').textContent = '₱' + fmt(total);
        }

        // Save the receipt as a PNG using html2canvas.
        function saveReceiptPng() {
            const el = document.getElementById('receipt-content');
            html2canvas(el, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = 'caffean-receipt.png';
                link.click();
            });
        }

        // Close the receipt dialog and redirect to the account page.
        function closeReceipt() {
            document.getElementById('receipt-overlay').classList.remove('open');
            window.location.href = 'account.php';
        }

        // Inline alert helper.
        function showAlert(msg) {
            const zone = document.getElementById('s2-alert-zone');
            zone.innerHTML = `<div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>${msg}
                <button class="dismiss-btn" onclick="clearAlert()">&#x2715;</button>
            </div>`;
            zone.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
            setTimeout(clearAlert, 6000);
        }

        function clearAlert() {
            document.getElementById('s2-alert-zone').innerHTML = '';
        }

        // Build pickup time slots from 8:00 AM to 8:00 PM.
        (function buildTimeSlots() {
            const sel = document.getElementById('pick-time');
            if (!sel) return;
            for (let h = 8; h <= 20; h++) {
                ['00', '30'].forEach(m => {
                    if (h === 20 && m === '30') return;
                    const val = `${String(h).padStart(2, '0')}:${m}`;
                    const ampm = h < 12 ? 'AM' : 'PM';
                    const hr12 = h > 12 ? h - 12 : h;
                    const label = `${hr12}:${m} ${ampm}`;
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = label;
                    sel.appendChild(opt);
                });
            }
        })();
    </script>

</body>

</html>