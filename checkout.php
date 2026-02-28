<?php
/**
 * Purge Coffee Shop — Checkout Page
 * Collects customer info, order type, address/pickup, and payment method.
 */
require_once 'php/db_connection.php';

// Redirect admin away from checkout
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Cart must not be empty
if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

/* ── Session cart normalisation ─────────────────────────────── */
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

// Fetch user info
$user_stmt = mysqli_prepare($conn, "SELECT full_name, email FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
mysqli_stmt_execute($user_stmt);
$user_info = mysqli_fetch_assoc(mysqli_stmt_get_result($user_stmt));
mysqli_stmt_close($user_stmt);

/* ── Fetch cart products ─────────────────────────────────────── */
$cart_items   = [];
$subtotal     = 0.0;
$DELIVERY_FEE = 50.0;

$ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
$res = mysqli_query($conn,
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
    $subtotal                 += $p['item_total'];
    $cart_items[]              = $p;
}

$min_date = date('Y-m-d'); // Prevent past pickup dates
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
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/buttons.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/checkout.css?v=<?php echo time(); ?>">
</head>

<body>

    <!-- ── Navbar ────────────────────────────────────────────── -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Purge Coffee" />
                <span>purge coffee</span>
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

    <!-- ── Checkout Section ──────────────────────────────────── -->
    <section class="checkout-page">
        <div class="container">

            <h1 class="checkout-title">CHECKOUT</h1>
            <div id="s2-alert-zone"></div>

            <div class="row g-4">

                <!-- LEFT: Forms -->
                <div class="col-lg-7">

                    <!-- Customer Information -->
                    <div class="checkout-card">
                        <h3><i class="fas fa-user me-2"></i>Customer Information</h3>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Full Name *</label>
                                <input type="text" id="co-name" class="form-control"
                                    value="<?= htmlspecialchars($user_info['full_name'] ?? '') ?>"
                                    placeholder="Enter your full name"
                                    autocomplete="name" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address *</label>
                                <input type="email" id="co-email" class="form-control"
                                    value="<?= htmlspecialchars($user_info['email'] ?? '') ?>"
                                    placeholder="example@gmail.com"
                                    autocomplete="email" />
                            </div>
                            <div class="col-md-6">
                                <!-- Digits only, 11 chars, enforced via JS -->
                                <label class="form-label">Mobile Number *</label>
                                <input type="tel" id="co-mobile" class="form-control"
                                    placeholder="09XXXXXXXXX"
                                    maxlength="11"
                                    inputmode="numeric"
                                    autocomplete="tel" />
                            </div>
                        </div>
                    </div>

                    <!-- Order Type -->
                    <div class="checkout-card">
                        <h3><i class="fas fa-location-dot me-2"></i>Order Type</h3>

                        <!-- Same card-radio layout as payment; height matches input fields via .order-type-options -->
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

                        <!-- Delivery Address Fields — mt-3 adds the gap below options -->
                        <div id="delivery-fields" class="mt-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">House / Unit No. *</label>
                                    <input type="text" id="del-house" class="form-control"
                                        placeholder="e.g., Blk 2 Lot 5, Unit A"
                                        autocomplete="address-line1" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Street *</label>
                                    <input type="text" id="del-street" class="form-control"
                                        placeholder="e.g., Emerald Avenue, San Pedro St."
                                        autocomplete="address-line2" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Barangay *</label>
                                    <input type="text" id="del-brgy" class="form-control"
                                        placeholder="e.g., Brgy. Magang, Matina" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City / Municipality *</label>
                                    <input type="text" id="del-city" class="form-control"
                                        placeholder="City / Municipality"
                                        autocomplete="address-level2" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Province *</label>
                                    <input type="text" id="del-province" class="form-control"
                                        placeholder="e.g., Camarines Norte, Davao del Sur"
                                        autocomplete="address-level1" />
                                </div>
                                <div class="col-md-6">
                                    <!-- PH ZIP codes are 4 digits; digits only via JS -->
                                    <label class="form-label">ZIP Code *</label>
                                    <input type="text" id="del-zip" class="form-control"
                                        placeholder="e.g. 8000"
                                        maxlength="4"
                                        inputmode="numeric"
                                        autocomplete="postal-code" />
                                </div>
                                <div class="col-12">
                                    <label class="form-label">
                                        Delivery Notes <span class="opt-text">(Optional)</span>
                                    </label>
                                    <textarea id="del-notes" class="form-control del-notes-ta" rows="3"
                                        placeholder="e.g., Beside the green gate, leave at guard house..."></textarea>
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
                                    <!-- min set server-side to prevent past dates -->
                                    <label class="form-label">Pickup Date *</label>
                                    <input type="date" id="pick-date" class="form-control"
                                        min="<?= $min_date ?>" />
                                </div>
                                <div class="col-md-6">
                                    <!-- Slots 8:00 AM – 8:00 PM built by JS -->
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
                        <h3><i class="fas fa-credit-card me-2"></i>Payment Method</h3>
                        <!-- CSS :checked handles all active states — no JS needed here -->
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
                                    value="Bank Transfer" />
                                <label for="pay-bank">
                                    <span class="pay-icon"><i class="fas fa-building-columns"></i></span>
                                    <span class="pay-label">Bank Transfer</span>
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
                            <!-- Fee row hidden for pickup (free), updated by switchOrderType() -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
    <script>
        const DELIVERY_FEE = <?= $DELIVERY_FEE ?>;
        const subtotal     = <?= $subtotal ?>;
        let   orderType    = 'delivery';

        /* ── Format number with commas ─────────────────────── */
        function fmt(n) {
            return n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        /* ── Order Type Switch ─────────────────────────────── */
        function switchOrderType(type) {
            orderType = type;
            const isDel = type === 'delivery';
            document.getElementById('delivery-fields').style.display = isDel ? 'block' : 'none';
            document.getElementById('pickup-fields').style.display   = isDel ? 'none'  : 'block';
            // Update fee row and total: delivery = ₱50, pickup = free
            const feeRow = document.getElementById('co-fee-row');
            feeRow.style.display = isDel ? 'flex' : 'none';
            document.getElementById('co-total').textContent = '₱' + fmt(subtotal + (isDel ? DELIVERY_FEE : 0));
        }

        /* ── Digits-only enforcement (mobile + ZIP) ────────── */
        function digitsOnly(el) {
            el.addEventListener('keydown', function(e) {
                const ctrl = e.ctrlKey || e.metaKey;
                const nav  = [8, 9, 35, 36, 37, 39, 46].includes(e.keyCode);
                if (!ctrl && !nav && !/^\d$/.test(e.key)) e.preventDefault();
            });
            el.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
            });
            el.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasted = (e.clipboardData || window.clipboardData).getData('text');
                const digits = pasted.replace(/\D/g, '');
                const max    = parseInt(this.getAttribute('maxlength') || '99');
                this.value   = (this.value + digits).slice(0, max);
            });
        }
        digitsOnly(document.getElementById('co-mobile'));
        digitsOnly(document.getElementById('del-zip'));

        /* ── Validate email format ─────────────────────────── */
        function validEmail(v) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);
        }

        /* ── Place Order ───────────────────────────────────── */
        function placeOrder() {
            clearAlert();
            const name    = document.getElementById('co-name').value.trim();
            const email   = document.getElementById('co-email').value.trim();
            const mobile  = document.getElementById('co-mobile').value.trim();
            const payment = document.querySelector('input[name="payment_method"]:checked')?.value || '';

            if (!name)              return showAlert('Please enter your full name.');
            if (!validEmail(email)) return showAlert('Please enter a valid email address.');
            if (!/^09\d{9}$/.test(mobile))
                                    return showAlert('Enter a valid PH mobile number (09XXXXXXXXX).');
            if (!payment)           return showAlert('Please select a payment method.');

            const data = { name, email, mobile, payment_method: payment, order_type: orderType };

            if (orderType === 'delivery') {
                const house    = document.getElementById('del-house').value.trim();
                const street   = document.getElementById('del-street').value.trim();
                const brgy     = document.getElementById('del-brgy').value.trim();
                const city     = document.getElementById('del-city').value.trim();
                const province = document.getElementById('del-province').value.trim();
                const zip      = document.getElementById('del-zip').value.trim();
                if (!house || !street || !brgy || !city || !province || !zip)
                    return showAlert('Please fill in all required delivery address fields.');
                if (!/^\d{4}$/.test(zip))
                    return showAlert('Enter a valid 4-digit Philippine ZIP code.');
                Object.assign(data, {
                    house_unit: house, street_name: street, barangay: brgy,
                    city_municipality: city, province, zip_code: zip,
                    delivery_notes: document.getElementById('del-notes').value.trim()
                });
            } else {
                const branch = document.getElementById('pick-branch').value;
                const date   = document.getElementById('pick-date').value;
                const time   = document.getElementById('pick-time').value;
                if (!branch) return showAlert('Please select a pickup branch.');
                if (!date)   return showAlert('Please select a pickup date.');
                if (!time)   return showAlert('Please select a pickup time.');
                Object.assign(data, { pickup_branch: branch, pickup_date: date, pickup_time: time });
            }

            const btn = document.getElementById('btn-place-order');
            btn.disabled    = true;
            btn.innerHTML   = '<i class="fas fa-spinner fa-spin me-2"></i>Placing Order…';

            const fd = new FormData();
            Object.entries(data).forEach(([k, v]) => fd.append(k, v));

            fetch('php/place_order.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        window.location.href = 'order_success.php?order_id=' + d.order_id;
                    } else {
                        showAlert(d.message || 'Something went wrong. Please try again.');
                        btn.disabled  = false;
                        btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Place Order';
                    }
                })
                .catch(() => {
                    showAlert('Network error. Please check your connection and try again.');
                    btn.disabled  = false;
                    btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Place Order';
                });
        }

        /* ── Inline Alert ──────────────────────────────────── */
        function showAlert(msg) {
            const zone = document.getElementById('s2-alert-zone');
            zone.innerHTML = `<div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>${msg}
                <button class="dismiss-btn" onclick="clearAlert()">&#x2715;</button>
            </div>`;
            zone.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            setTimeout(clearAlert, 6000);
        }

        function clearAlert() {
            document.getElementById('s2-alert-zone').innerHTML = '';
        }

        /* ── Pickup time slots: 8:00 AM – 8:00 PM only ────── */
        (function buildTimeSlots() {
            const sel = document.getElementById('pick-time');
            if (!sel) return;
            for (let h = 8; h <= 20; h++) {
                ['00', '30'].forEach(m => {
                    if (h === 20 && m === '30') return; // last slot is 8:00 PM
                    const val   = `${String(h).padStart(2, '0')}:${m}`;
                    const ampm  = h < 12 ? 'AM' : 'PM';
                    const hr12  = h > 12 ? h - 12 : h;
                    const label = `${hr12}:${m} ${ampm}`;
                    const opt   = document.createElement('option');
                    opt.value       = val;
                    opt.textContent = label;
                    sel.appendChild(opt);
                });
            }
        })();
    </script>

</body>
</html>