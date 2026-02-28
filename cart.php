<?php

/**
 * Purge Coffee Shop — Shopping Cart (cart.php)
 * Three in-page sections: Cart → Checkout → Confirmation
 */
require_once 'php/db_connection.php';

// ── Redirect admin away from cart ────────────────────────────
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

/* ── Session cart normalisation ─────────────────────────────── */
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
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

/* ── Fetch cart products ─────────────────────────────────────── */
$cart_items   = [];
$subtotal     = 0.0;
$DELIVERY_FEE = 50.0;

if (!empty($_SESSION['cart'])) {
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
        $subtotal                 += $p['item_total'];
        $cart_items[]              = $p;
    }
}

/* ── Logged-in user ──────────────────────────────────────────── */
$user = null;
if (isset($_SESSION['user_id'])) {
    $st = mysqli_prepare($conn, "SELECT full_name, email FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($st, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($st);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Shopping Cart — Purge Coffee</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/buttons.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/cart.css?v=<?php echo time(); ?>">
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
                    <i class="fas fa-shopping-cart nav-icon active-icon"></i>
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

    <!-- ════════════════════════════════════════════════════════
         SECTION 1 — CART ITEMS
         ════════════════════════════════════════════════════════ -->
    <section id="cart-s1" class="pg-section active">
        <div class="container">
            <h1 class="cart-pg-title">SHOPPING CART</h1>

            <?php if (empty($cart_items)): ?>
                <div class="cart-empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any items yet.<br>Discover our delicious roasts and pastries to get started!</p>
                    <a href="menu.php" class="btn-browse-menu">Browse Menu</a>
                </div>
            <?php else: ?>

                <div class="cart-layout">
                    <div class="row g-4">

                        <!-- Left: Cart Items -->
                        <div class="col-lg-8" id="cart-items-col">
                            <?php foreach ($cart_items as $item):
                                $pid  = $item['product_id'];
                                $opts = implode(' · ', array_filter([
                                    $item['size'],
                                    $item['temperature'],
                                    'Sugar ' . $item['sugar_level'],
                                    $item['milk'] . ' Milk',
                                    !empty($item['addons']) ? implode(', ', $item['addons']) : ''
                                ]));
                            ?>
                                <div class="ci-card" data-pid="<?= $pid ?>">

                                    <!-- Top row: image | info | delete -->
                                    <div class="ci-top">
                                        <div class="ci-img-wrap">
                                            <?php if ($item['image_path']): ?>
                                                <img src="<?= htmlspecialchars($item['image_path']) ?>"
                                                    alt="<?= htmlspecialchars($item['name']) ?>" />
                                            <?php else: ?>
                                                <div class="ci-img-placeholder">
                                                    <i class="fas fa-mug-hot"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="ci-info">
                                            <h4 class="ci-name"><?= htmlspecialchars($item['name']) ?></h4>
                                            <!-- FIX #3: price only shows here; removed duplicate from right column -->
                                            <p class="ci-base-price">₱<?= number_format($item['price'], 2) ?></p>
                                            <p class="ci-opts-preview" id="opts-<?= $pid ?>"><?= htmlspecialchars($opts) ?></p>
                                        </div>

                                        <!-- Delete triggers confirmation modal -->
                                        <div class="ci-right">
                                            <button class="ci-del-btn"
                                                onclick="confirmDeleteItem(<?= $pid ?>, '<?= addslashes(htmlspecialchars($item['name'])) ?>')"
                                                title="Remove item">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Customize toggle -->
                                    <div class="ci-customize">
                                        <button class="ci-cust-toggle" type="button"
                                            onclick="toggleCustomize(<?= $pid ?>)">
                                            <i class="fas fa-sliders me-1"></i>Customize
                                            <i class="fas fa-chevron-down ci-chev" id="chev-<?= $pid ?>"></i>
                                        </button>

                                        <div class="ci-cust-fields" id="cust-<?= $pid ?>" style="display:none;">
                                            <div class="row g-2 mt-2">

                                                <!-- FIX #5: first option in array is the default -->
                                                <!-- FIX #8: asterisk on required labels -->
                                                <div class="col-6 col-md-3">
                                                    <label class="cust-lbl">Size *</label>
                                                    <select class="cust-sel"
                                                        onchange="updateOption(<?= $pid ?>, 'size', this.value)">
                                                        <?php foreach (['Short', 'Tall', 'Grande', 'Venti'] as $s): ?>
                                                            <option value="<?= $s ?>" <?= $item['size'] === $s ? 'selected' : '' ?>>
                                                                <?= $s ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-6 col-md-3">
                                                    <label class="cust-lbl">Temperature *</label>
                                                    <select class="cust-sel"
                                                        onchange="updateOption(<?= $pid ?>, 'temperature', this.value)">
                                                        <?php foreach (['Hot', 'Iced', 'Blended'] as $t): ?>
                                                            <option value="<?= $t ?>" <?= $item['temperature'] === $t ? 'selected' : '' ?>>
                                                                <?= $t ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-6 col-md-3">
                                                    <label class="cust-lbl">Sugar Level *</label>
                                                    <select class="cust-sel"
                                                        onchange="updateOption(<?= $pid ?>, 'sugar_level', this.value)">
                                                        <?php foreach (['0%', '25%', '50%', '75%', '100%'] as $sl): ?>
                                                            <option value="<?= $sl ?>" <?= $item['sugar_level'] === $sl ? 'selected' : '' ?>>
                                                                <?= $sl ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="col-6 col-md-3">
                                                    <label class="cust-lbl">Milk *</label>
                                                    <select class="cust-sel"
                                                        onchange="updateOption(<?= $pid ?>, 'milk', this.value)">
                                                        <?php foreach (['Whole', 'Skim', 'Oat', 'Almond', 'None'] as $m): ?>
                                                            <option value="<?= $m ?>" <?= $item['milk'] === $m ? 'selected' : '' ?>>
                                                                <?= $m ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <!-- FIX #8: add-ons labelled as optional -->
                                                <div class="col-12">
                                                    <label class="cust-lbl">
                                                        Add-ons <span class="opt-text">(Optional)</span>
                                                    </label>
                                                    <div class="addons-grid">
                                                        <?php foreach (['Extra Espresso Shot', 'Vanilla Syrup', 'Whipped Cream', 'Coffee Jelly', 'Pearl (Boba)'] as $addon): ?>
                                                            <label class="addon-item">
                                                                <input type="checkbox"
                                                                    value="<?= $addon ?>"
                                                                    <?= in_array($addon, $item['addons']) ? 'checked' : '' ?>
                                                                    onchange="updateAddons(<?= $pid ?>)" />
                                                                <?= $addon ?>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <!-- FIX #8: special instructions labelled as optional -->
                                                <div class="col-12">
                                                    <label class="cust-lbl">
                                                        Special Instructions <span class="opt-text">(Optional)</span>
                                                    </label>
                                                    <textarea class="cust-ta" rows="2"
                                                        placeholder="Add any special requests..."
                                                        onchange="updateOption(<?= $pid ?>, 'special_instructions', this.value)"><?= htmlspecialchars($item['special_instructions']) ?></textarea>
                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                    <!-- Quantity controls -->
                                    <!-- FIX #2: handled in JS — qty clamped at 1, never removes -->
                                    <div class="ci-qty-row">
                                        <div class="qty-wrap">
                                            <button class="qty-circle" onclick="changeQty(<?= $pid ?>, -1)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span class="qty-num" id="qty-<?= $pid ?>"><?= $item['quantity'] ?></span>
                                            <button class="qty-circle" onclick="changeQty(<?= $pid ?>, 1)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Right: Order Summary -->
                        <div class="col-lg-4">
                            <div class="sum-card sum-sticky" id="s1-sum-card">
                                <h3 class="sum-title">Order Summary</h3>

                                <div class="sum-items" id="s1-item-lines">
                                    <?php foreach ($cart_items as $item): ?>
                                        <div class="sum-item-line" id="sline-<?= $item['product_id'] ?>">
                                            <span class="sil-name">
                                                <?= htmlspecialchars($item['name']) ?>
                                                <span class="sil-qty">×<span id="siqty-<?= $item['product_id'] ?>"><?= $item['quantity'] ?></span></span>
                                            </span>
                                            <span class="sil-price" id="sitot-<?= $item['product_id'] ?>">
                                                ₱<?= number_format($item['item_total'], 2) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="sum-bottom">
                                    <div class="sum-hr"></div>
                                    <div class="sum-calc-row">
                                        <span>Subtotal</span>
                                        <span id="s1-sub">₱<?= number_format($subtotal, 2) ?></span>
                                    </div>
                                    <div class="sum-calc-row">
                                        <span>Shipping</span>
                                        <span id="s1-ship">₱<?= number_format($DELIVERY_FEE, 2) ?></span>
                                    </div>
                                    <div class="sum-hr"></div>
                                    <div class="sum-total-row">
                                        <span>Total</span>
                                        <span id="s1-total">₱<?= number_format($subtotal + $DELIVERY_FEE, 2) ?></span>
                                    </div>

                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <button class="btn-cart-main btn-full mt-3" onclick="goToSection(2)">
                                            Proceed to Checkout
                                        </button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn-cart-main btn-full mt-3 text-center d-block">
                                            Login to Checkout
                                        </a>
                                    <?php endif; ?>

                                    <button class="btn-cart-back btn-full mt-2"
                                        onclick="window.location.href='menu.php'">
                                        <i class="fas fa-arrow-left me-1"></i> Continue Shopping
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endif; ?>
                </div>
    </section>

    <!-- ── Delete Confirmation Modal ──────────────────────────── -->
    <div class="cart-modal-overlay" id="cart-del-modal" role="dialog" aria-modal="true">
        <div class="cart-modal">
            <div class="cart-modal-header">
                <h3><i class="fas fa-trash-alt"></i> Remove Item</h3>
                <button class="cart-modal-close" onclick="closeDeleteModal()" title="Close">&#x2715;</button>
            </div>
            <div class="cart-modal-body">
                Are you sure you want to remove <strong id="del-modal-name">this item</strong> from your cart?
                This action cannot be undone.
            </div>
            <div class="cart-modal-footer">
                <button class="cart-modal-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="cart-modal-btn-delete" id="del-modal-confirm">
                    <i class="fas fa-trash-alt"></i> Remove Item
                </button>
            </div>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════
         SECTION 2 — CHECKOUT
         ════════════════════════════════════════════════════════ -->
    <section id="cart-s2" class="pg-section">
        <div class="container">
            <h1 class="cart-pg-title">CHECKOUT</h1>
            <div id="s2-alert-zone"></div>

            <div class="row g-4">

                <!-- LEFT: Forms -->
                <div class="col-lg-7">

                    <!-- Customer Information -->
                    <div class="co-card">
                        <h3 class="co-card-title">
                            <i class="fas fa-user me-2"></i>Customer Information
                        </h3>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="co-label">Full Name *</label>
                                <input type="text" id="co-name" class="co-input"
                                    value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                                    placeholder="Your full name" />
                            </div>
                            <div class="col-md-6">
                                <label class="co-label">Email Address *</label>
                                <input type="email" id="co-email" class="co-input"
                                    value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                    placeholder="you@email.com" />
                            </div>
                            <div class="col-md-6">
                                <label class="co-label">Mobile Number *</label>
                                <input type="tel" id="co-mobile" class="co-input"
                                    placeholder="09XXXXXXXXX" maxlength="11" />
                            </div>
                        </div>
                    </div>

                    <!-- Order Type -->
                    <div class="co-card">
                        <h3 class="co-card-title">
                            <i class="fas fa-location-dot me-2"></i>Order Type
                        </h3>
                        <div class="ot-options">
                            <label class="ot-opt active" id="ot-del-label">
                                <input type="radio" name="order_type" value="delivery"
                                    onchange="switchOrderType('delivery')" checked />
                                <div class="ot-inner">
                                    <i class="fas fa-truck"></i>
                                    <span>Delivery</span>
                                </div>
                            </label>
                            <label class="ot-opt" id="ot-pick-label">
                                <input type="radio" name="order_type" value="pickup"
                                    onchange="switchOrderType('pickup')" />
                                <div class="ot-inner">
                                    <i class="fas fa-store"></i>
                                    <span>Pickup</span>
                                </div>
                            </label>
                        </div>

                        <!-- Delivery Address Fields -->
                        <div id="delivery-fields" class="addr-fields">
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="co-label">House/Unit No. *</label>
                                    <input type="text" id="del-house" class="co-input"
                                        placeholder="e.g. Unit 4B" />
                                </div>
                                <div class="col-md-6">
                                    <label class="co-label">Street *</label>
                                    <input type="text" id="del-street" class="co-input"
                                        placeholder="Street name" />
                                </div>
                                <div class="col-md-6">
                                    <label class="co-label">Barangay *</label>
                                    <input type="text" id="del-brgy" class="co-input"
                                        placeholder="Barangay" />
                                </div>
                                <div class="col-md-6">
                                    <label class="co-label">City / Municipality *</label>
                                    <input type="text" id="del-city" class="co-input"
                                        placeholder="City" />
                                </div>
                                <div class="col-md-6">
                                    <label class="co-label">Province *</label>
                                    <input type="text" id="del-province" class="co-input"
                                        placeholder="Province" />
                                </div>
                                <div class="col-md-6">
                                    <label class="co-label">ZIP Code *</label>
                                    <input type="text" id="del-zip" class="co-input"
                                        placeholder="ZIP" maxlength="10" />
                                </div>
                                <div class="col-12">
                                    <label class="co-label">
                                        Delivery Notes <span class="opt-text">(Optional)</span>
                                    </label>
                                    <textarea id="del-notes" class="co-input" rows="2"
                                        placeholder="Gate code, landmark, etc."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Pickup Fields -->
                        <div id="pickup-fields" class="addr-fields" style="display:none;">
                            <div class="row g-3 mt-2">
                                <div class="col-12">
                                    <label class="co-label">Pickup Branch *</label>
                                    <select id="pick-branch" class="co-input">
                                        <option value="" disabled selected>— Select branch —</option>
                                        <option value="Main Branch - Daet">Main Branch - Daet</option>
                                        <option value="Branch 2 - Naga">Branch 2 - Naga</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="co-label">Pickup Date *</label>
                                    <input type="date" id="pick-date" class="co-input" />
                                </div>
                                <div class="col-md-6">
                                    <label class="co-label">Pickup Time *</label>
                                    <select id="pick-time" class="co-input">
                                        <option value="" disabled selected>— Select time —</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="co-card">
                        <h3 class="co-card-title">
                            <i class="fas fa-credit-card me-2"></i>Payment Method
                        </h3>
                        <div class="pay-options">
                            <label class="pay-opt active">
                                <input type="radio" name="payment_method" value="Cash on Delivery"
                                    checked onchange="activatePayOpt(this)" />
                                <div class="pay-inner">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <div><span class="pay-name">Cash on Delivery</span></div>
                                </div>
                            </label>
                            <label class="pay-opt">
                                <input type="radio" name="payment_method" value="GCash"
                                    onchange="activatePayOpt(this)" />
                                <div class="pay-inner">
                                    <i class="fas fa-mobile-alt"></i>
                                    <div><span class="pay-name">GCash</span></div>
                                </div>
                            </label>
                            <label class="pay-opt">
                                <input type="radio" name="payment_method" value="Maya"
                                    onchange="activatePayOpt(this)" />
                                <div class="pay-inner">
                                    <i class="fas fa-wallet"></i>
                                    <div><span class="pay-name">Maya</span></div>
                                </div>
                            </label>
                            <label class="pay-opt">
                                <input type="radio" name="payment_method" value="Bank Transfer"
                                    onchange="activatePayOpt(this)" />
                                <div class="pay-inner">
                                    <i class="fas fa-building-columns"></i>
                                    <div><span class="pay-name">Bank Transfer</span></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Promo Code -->
                    <div class="co-card">
                        <h3 class="co-card-title">
                            <i class="fas fa-tag me-2"></i>Promo Code
                            <span class="opt-text">(Optional)</span>
                        </h3>
                        <div class="promo-row">
                            <input type="text" id="promo-input" class="co-input"
                                placeholder="Enter promo code" style="text-transform:uppercase;" />
                            <button class="btn-cart-back" onclick="applyPromo()"
                                style="white-space:nowrap;">Apply</button>
                        </div>
                        <p class="promo-msg" id="promo-msg"></p>
                    </div>

                </div>

                <!-- RIGHT: Order Summary -->
                <div class="col-lg-5">
                    <div class="sum-card sum-sticky">
                        <h3 class="sum-title">Order Summary</h3>
                        <div class="sum-items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="sum-item-line">
                                    <span class="sil-name">
                                        <?= htmlspecialchars($item['name']) ?>
                                        <span class="sil-qty">×<?= $item['quantity'] ?></span>
                                        <small class="sil-opts" id="s2opts-<?= $item['product_id'] ?>">
                                            <?= htmlspecialchars($item['size'] . ' · ' . $item['temperature'] . ' · Sugar ' . $item['sugar_level']) ?>
                                        </small>
                                    </span>
                                    <span class="sil-price">₱<?= number_format($item['item_total'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="sum-hr"></div>
                        <div class="sum-calc-row">
                            <span>Subtotal</span>
                            <span id="s2-sub">₱<?= number_format($subtotal, 2) ?></span>
                        </div>
                        <div class="sum-calc-row" id="s2-fee-row">
                            <span>Delivery Fee</span>
                            <span id="s2-fee">₱<?= number_format($DELIVERY_FEE, 2) ?></span>
                        </div>
                        <div class="sum-calc-row discount-row" id="s2-disc-row" style="display:none;">
                            <span class="disc-label">Discount</span>
                            <span id="s2-disc" class="disc-val">−₱0.00</span>
                        </div>
                        <div class="sum-hr"></div>
                        <div class="sum-total-row">
                            <span>Total</span>
                            <span id="s2-total">₱<?= number_format($subtotal + $DELIVERY_FEE, 2) ?></span>
                        </div>

                        <button class="btn-cart-main btn-full mt-3" id="btn-place-order"
                            onclick="placeOrder()">
                            <i class="fas fa-check-circle me-2"></i>Place Order
                        </button>
                        <button class="btn-cart-back btn-full mt-2" onclick="goToSection(1)">
                            <i class="fas fa-arrow-left me-2"></i>Back to Cart
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════════════════
         SECTION 3 — ORDER CONFIRMATION
         ════════════════════════════════════════════════════════ -->
    <section id="cart-s3" class="pg-section">
        <div class="container">
            <div class="success-wrap">
                <div class="success-icon-ring">
                    <i class="fas fa-circle-check"></i>
                </div>
                <h1 class="success-h1">Order Confirmed!</h1>
                <p class="success-sub" id="s3-greeting">
                    Thank you! Your order has been placed successfully.
                </p>
                <div class="s3-details-card">
                    <div class="s3-detail-row">
                        <span class="s3-lbl">Order #</span>
                        <span id="s3-oid">—</span>
                    </div>
                    <div class="s3-detail-row">
                        <span class="s3-lbl">Date</span>
                        <span id="s3-date">—</span>
                    </div>
                    <div class="s3-detail-row">
                        <span class="s3-lbl">Type</span>
                        <span id="s3-type">—</span>
                    </div>
                    <div class="s3-detail-row">
                        <span class="s3-lbl">Payment</span>
                        <span id="s3-pay">—</span>
                    </div>
                    <div class="s3-detail-row">
                        <span class="s3-lbl">Total</span>
                        <span id="s3-total" class="s3-total-val">—</span>
                    </div>
                </div>
                <div class="s3-items-title">Ordered Items</div>
                <div class="s3-items-card">
                    <div id="s3-items"></div>
                </div>
                <div class="success-actions mt-4">
                    <a href="menu.php" class="btn-cart-main">Continue Shopping</a>
                    <a href="account.php" class="btn-cart-back">View Orders</a>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/search.js"></script>
    <script>
        const DELIVERY_FEE = <?= $DELIVERY_FEE ?>;
        let subtotal = <?= $subtotal ?>;
        let discountAmt = 0;
        let appliedPromo = '';
        let orderType = 'delivery';

        /* ── Section Navigation ─────────────────────────────── */
        function goToSection(n) {
            document.querySelectorAll('.pg-section').forEach(s => s.classList.remove('active'));
            document.getElementById('cart-s' + n).classList.add('active');
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        /* ── Sync Summary Card height to first ci-card ──────── */
        /* Removed — Order Summary now sizes to its own content only */

        /* ── Customization Panel ────────────────────────────── */
        function toggleCustomize(pid) {
            const panel = document.getElementById('cust-' + pid);
            const chev = document.getElementById('chev-' + pid);
            const open = panel.style.display !== 'none';
            panel.style.display = open ? 'none' : 'block';
            chev.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
        }

        function updateOption(pid, field, value) {
            const fd = new FormData();
            fd.append('action', 'update_option');
            fd.append('product_id', pid);
            fd.append('field', field);
            fd.append('value', value);
            fetch('php/update_cart_item.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) rebuildOptsSummary(pid, d.item);
                });
        }

        function updateAddons(pid) {
            const checked = document.querySelectorAll(`#cust-${pid} .addon-item input:checked`);
            const fd = new FormData();
            fd.append('action', 'update_addons');
            fd.append('product_id', pid);
            checked.forEach(c => fd.append('addons[]', c.value));
            fetch('php/update_cart_item.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) rebuildOptsSummary(pid, d.item);
                });
        }

        function rebuildOptsSummary(pid, item) {
            const el = document.getElementById('opts-' + pid);
            if (!el) return;
            const parts = [item.size, item.temperature, 'Sugar ' + item.sugar_level, item.milk + ' Milk'];
            if (item.addons && item.addons.length) parts.push(item.addons.join(', '));
            el.textContent = parts.join(' · ');
            const s2el = document.getElementById('s2opts-' + pid);
            if (s2el) s2el.textContent = [item.size, item.temperature, 'Sugar ' + item.sugar_level].join(' · ');
        }

        /* ── Quantity (FIX #2: clamp at 1, never removes) ──── */
        function changeQty(pid, delta) {
            const qEl = document.getElementById('qty-' + pid);
            const current = parseInt(qEl.textContent);
            if (current <= 1 && delta < 0) return; // Clamp: cannot go below 1

            const qty = current + delta;
            const fd = new FormData();
            fd.append('action', 'update_qty');
            fd.append('product_id', pid);
            fd.append('quantity', qty);
            fetch('php/update_cart_item.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(d => {
                    if (!d.success) return;
                    qEl.textContent = qty;
                    const sqty = document.getElementById('siqty-' + pid);
                    const stot = document.getElementById('sitot-' + pid);
                    if (sqty) sqty.textContent = qty;
                    if (stot) stot.textContent = '₱' + fmt(d.item_total);
                    subtotal = d.subtotal;
                    refreshTotals();
                });
        }

        /* ── Delete Confirmation Modal ─────────────────────── */
        let _pendingDeletePid = null;

        function confirmDeleteItem(pid, name) {
            _pendingDeletePid = pid;
            document.getElementById('del-modal-name').textContent = name;
            document.getElementById('cart-del-modal').classList.add('open');
        }

        function closeDeleteModal() {
            document.getElementById('cart-del-modal').classList.remove('open');
            _pendingDeletePid = null;
        }

        document.getElementById('del-modal-confirm').addEventListener('click', function() {
            if (_pendingDeletePid !== null) {
                closeDeleteModal();
                removeCartItem(_pendingDeletePid);
            }
        });

        // Close on overlay click (outside modal)
        document.getElementById('cart-del-modal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        /* ── Remove Item ───────────────────────────────────── */
        function removeCartItem(pid) {
            const fd = new FormData();
            fd.append('action', 'remove');
            fd.append('product_id', pid);
            fetch('php/update_cart_item.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(d => {
                    if (!d.success) return;
                    document.querySelector(`.ci-card[data-pid="${pid}"]`)?.remove();
                    document.getElementById('sline-' + pid)?.remove();
                    subtotal = d.subtotal;
                    refreshTotals();
                    if (typeof updateCartCount === 'function') updateCartCount();
                    if (d.cart_empty) location.reload();
                });
        }

        /* ── Totals Refresh ────────────────────────────────── */
        function refreshTotals() {
            const fee = orderType === 'delivery' ? DELIVERY_FEE : 0;
            const total = Math.max(0, subtotal + fee - discountAmt);
            setText('s1-sub', '₱' + fmt(subtotal));
            setText('s1-ship', '₱' + fmt(fee));
            setText('s1-total', '₱' + fmt(subtotal + fee));
            setText('s2-sub', '₱' + fmt(subtotal));
            setText('s2-fee', '₱' + fmt(fee));
            setText('s2-total', '₱' + fmt(total));
        }

        function setText(id, v) {
            const el = document.getElementById(id);
            if (el) el.textContent = v;
        }

        /* ── Order Type Switch ─────────────────────────────── */
        function switchOrderType(type) {
            orderType = type;
            const isDel = type === 'delivery';
            document.getElementById('delivery-fields').style.display = isDel ? 'block' : 'none';
            document.getElementById('pickup-fields').style.display = isDel ? 'none' : 'block';
            document.getElementById('ot-del-label').classList.toggle('active', isDel);
            document.getElementById('ot-pick-label').classList.toggle('active', !isDel);
            refreshTotals();
        }

        /* ── Payment Method ────────────────────────────────── */
        function activatePayOpt(radio) {
            document.querySelectorAll('.pay-opt').forEach(el => el.classList.remove('active'));
            radio.closest('.pay-opt').classList.add('active');
        }

        /* ── Promo Code ────────────────────────────────────── */
        function applyPromo() {
            const code = document.getElementById('promo-input').value.trim();
            if (!code) {
                setPromoMsg('Please enter a promo code.', false);
                return;
            }
            const fd = new FormData();
            fd.append('code', code);
            fd.append('subtotal', subtotal);
            fetch('php/apply_promo.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        discountAmt = d.discount_amount;
                        appliedPromo = code;
                        document.getElementById('s2-disc-row').style.display = 'flex';
                        document.getElementById('s2-disc').textContent = '−₱' + fmt(discountAmt);
                        setPromoMsg(d.message, true);
                    } else {
                        discountAmt = 0;
                        appliedPromo = '';
                        document.getElementById('s2-disc-row').style.display = 'none';
                        setPromoMsg(d.message, false);
                    }
                    refreshTotals();
                });
        }

        function setPromoMsg(msg, ok) {
            const el = document.getElementById('promo-msg');
            el.textContent = msg;
            el.className = 'promo-msg ' + (ok ? 'promo-ok' : 'promo-err');
        }

        /* ── Place Order ───────────────────────────────────── */
        function placeOrder() {
            clearAlert();
            const name = document.getElementById('co-name').value.trim();
            const email = document.getElementById('co-email').value.trim();
            const mobile = document.getElementById('co-mobile').value.trim();
            const payment = document.querySelector('input[name="payment_method"]:checked')?.value || '';

            if (!name || !email || !mobile) return showAlert('Please fill in all customer information fields.', 'error');
            if (!/^09\d{9}$/.test(mobile)) return showAlert('Enter a valid PH mobile number (09XXXXXXXXX).', 'error');

            const data = {
                name,
                email,
                mobile,
                payment_method: payment,
                order_type: orderType,
                promo_code: appliedPromo
            };

            if (orderType === 'delivery') {
                const house = document.getElementById('del-house').value.trim();
                const street = document.getElementById('del-street').value.trim();
                const brgy = document.getElementById('del-brgy').value.trim();
                const city = document.getElementById('del-city').value.trim();
                const province = document.getElementById('del-province').value.trim();
                const zip = document.getElementById('del-zip').value.trim();
                if (!house || !street || !brgy || !city || !province || !zip)
                    return showAlert('Please fill in all required delivery address fields.', 'error');
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
                if (!branch || !date || !time)
                    return showAlert('Please select your pickup branch, date, and time.', 'error');
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
                        fillSuccessScreen(d);
                        goToSection(3);
                        if (typeof cart !== 'undefined') cart = [];
                        localStorage.setItem('coffeeCart', '[]');
                        if (typeof updateCartCount === 'function') updateCartCount();
                    } else {
                        showAlert(d.message || 'Something went wrong. Please try again.', 'error');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Place Order';
                    }
                })
                .catch(() => {
                    showAlert('Network error. Please try again.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Place Order';
                });
        }

        /* ── Populate Section 3 ────────────────────────────── */
        function fillSuccessScreen(d) {
            document.getElementById('s3-greeting').textContent =
                `Thank you, ${d.customer_name}! Your order has been placed successfully.`;
            document.getElementById('s3-oid').textContent = '#' + d.order_id;
            document.getElementById('s3-date').textContent = d.order_date;
            document.getElementById('s3-type').textContent = d.order_type === 'delivery' ? 'Delivery' : 'Store Pickup';
            document.getElementById('s3-pay').textContent = d.payment_method;
            document.getElementById('s3-total').textContent = '₱' + fmt(d.total);
            document.getElementById('s3-items').innerHTML = d.items.map(it =>
                `<div class="s3-item-row">
                    <span class="s3-item-name">${it.name} <span class="s3-item-qty">×${it.quantity}</span></span>
                    <span class="s3-item-price">₱${fmt(it.subtotal)}</span>
                 </div>`
            ).join('');
        }

        /* ── Inline Alert (Section 2) ──────────────────────── */
        function showAlert(msg, type) {
            const zone = document.getElementById('s2-alert-zone');
            zone.innerHTML = `<div class="s2-alert s2-alert-${type}">
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'} me-2"></i>${msg}
                <button class="s2-alert-close" onclick="clearAlert()"><i class="fas fa-times"></i></button>
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

        /* ── Helpers ───────────────────────────────────────── */
        function fmt(n) {
            return parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        /* ── Populate pickup time slots ────────────────────── */
        (function buildTimeSlots() {
            const sel = document.getElementById('pick-time');
            if (!sel) return;
            for (let h = 7; h <= 20; h++) {
                ['00', '30'].forEach(m => {
                    if (h === 20 && m === '30') return;
                    const val = `${String(h).padStart(2, '0')}:${m}`;
                    const ampm = h < 12 ? 'AM' : 'PM';
                    const hr12 = h <= 12 ? h : h - 12;
                    const label = `${hr12 === 0 ? 12 : hr12}:${m} ${ampm}`;
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = label;
                    sel.appendChild(opt);
                });
            }
        })();
    </script>

    <script src="js/search.js?v=<?php echo time(); ?>"></script>

</body>

</html>