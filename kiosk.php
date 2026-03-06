<?php

/**
 * Purge Coffee Shop — Self-Order Kiosk (kiosk.php)
 * Guest ordering: Dine In / Take Out → Browse → Cart → Checkout → Confirmation
 * No login required. Cart stored in $_SESSION['kiosk_cart'].
 */

require_once 'php/db_connection.php';

/* ── Redirect admin away ─────────────────────────────────────── */
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

/* ── Init kiosk cart ─────────────────────────────────────────── */
if (!isset($_SESSION['kiosk_cart'])) $_SESSION['kiosk_cart'] = [];

/* ── Fetch all active products grouped by category ──────────── */
$products_res = mysqli_query(
    $conn,
    "SELECT p.product_id, p.name, p.description, p.price, p.image_path, p.net_content,
            c.category_id, c.name AS category_name
     FROM products p
     JOIN categories c ON p.category_id = c.category_id
     WHERE p.status = 1
     ORDER BY c.category_id, p.name"
);

$categories_map = [];   // category_id => ['name' => ..., 'products' => [...]]
while ($row = mysqli_fetch_assoc($products_res)) {
    $cid = $row['category_id'];
    if (!isset($categories_map[$cid])) {
        $categories_map[$cid] = ['name' => $row['category_name'], 'products' => []];
    }
    $categories_map[$cid]['products'][] = $row;
}

/* ── Image helper ────────────────────────────────────────────── */
function kioskProductImage($product)
{
    if (!empty($product['image_path'])) return htmlspecialchars($product['image_path']);
    $map = [6 => 'pastry.png', 7 => 'pastry.png', 8 => 'pastry.png'];
    return 'images/' . ($map[$product['category_id']] ?? 'coffee.png');
}

/* ── Net content helper — DB value or category default ──────── */
function kioskNetContent($product)
{
    if (!empty($product['net_content'])) return htmlspecialchars($product['net_content']);
    $defaults = [1 => '12 oz', 2 => '16 oz', 3 => '12 oz', 4 => '16 oz', 5 => '12 oz', 9 => '1 oz'];
    return $defaults[$product['category_id']] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Self-Order Kiosk — Purge Coffee</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/kiosk.css?v=<?php echo time(); ?>">
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Purge Coffee">
                <span>purge coffee</span>
            </a>
        </div>
    </nav>

    <div class="kiosk-progress" id="kiosk-progress">
        <div class="kiosk-bar-wrapper">
            <div class="k-bar-left"></div>
            <div class="k-bar-center kiosk-progress-inner">
                <div class="kp-step active" id="kp1">
                    <div class="kp-dot">1</div>
                    <span class="kp-lbl">Type</span>
                </div>
                <div class="kp-step" id="kp2">
                    <div class="kp-dot">2</div>
                    <span class="kp-lbl">Menu</span>
                </div>
                <div class="kp-step" id="kp3">
                    <div class="kp-dot">3</div>
                    <span class="kp-lbl">Cart</span>
                </div>
                <div class="kp-step" id="kp4">
                    <div class="kp-dot">4</div>
                    <span class="kp-lbl">Pay</span>
                </div>
                <div class="kp-step" id="kp5">
                    <div class="kp-dot">5</div>
                    <span class="kp-lbl">Done</span>
                </div>
            </div>
            <div class="k-bar-right">
                <button class="btn-view-cart" onclick="goBack()">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </button>
            </div>
        </div>
    </div>

    <div class="kiosk-step active" id="step1">
        <div class="welcome-section">
            <div class="welcome-inner">
                <h1 class="welcome-heading">Welcome!</h1>
                <p class="welcome-sub">How would you like your order today?</p>
                <div class="order-type-row">
                    <div class="ot-card" onclick="selectOrderType('dine_in')">
                        <i class="fas fa-utensils"></i>
                        <div class="ot-card-title">Dine In</div>
                        <div class="ot-card-desc">Enjoy your order<br>in our cozy shop</div>
                    </div>
                    <div class="ot-card" onclick="selectOrderType('take_out')">
                        <i class="fas fa-shopping-bag"></i>
                        <div class="ot-card-title">Take Out</div>
                        <div class="ot-card-desc">Your order packed<br>and ready to go</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="kiosk-step" id="step2">
        <div class="kiosk-menu-wrap">

            <div class="kiosk-cat-sidebar">
                <div class="filter-panel">

                    <div class="filter-section">
                        <h3 class="filter-title">
                            <i class="fas fa-list-ul"></i> Categories
                        </h3>
                        <div class="category-list">

                            <div class="kiosk-cat-item category-item active" id="catbtn-all"
                                onclick="scrollToCategory('all')">
                                <span class="category-icon"><i class="fas fa-th"></i></span>
                                <span class="category-name">All Categories</span>
                                <span class="category-count">
                                    <?php echo array_sum(array_map(fn($c) => count($c['products']), $categories_map)); ?>
                                </span>
                            </div>

                            <?php
                            $cat_icons = [
                                1 => 'fa-mug-hot',
                                2 => 'fa-glass-water',
                                3 => 'fa-cup-straw',
                                4 => 'fa-ice-cream',
                                5 => 'fa-leaf',
                                6 => 'fa-cake-candles',
                                7 => 'fa-bread-slice',
                                8 => 'fa-burger',
                                9 => 'fa-plus-circle',
                            ];
                            /* Name-based fallback for categories beyond ID 9 */
                            $cat_icons_by_name = [
                                'Coffee Beans'      => 'fa-seedling',
                                'Milk & Creamers'   => 'fa-droplet',
                                'Brewing Equipment' => 'fa-flask',
                            ];
                            foreach ($categories_map as $cid => $cat):
                                $icon = $cat_icons[$cid]
                                    ?? ($cat_icons_by_name[$cat['name']] ?? 'fa-circle');
                            ?>
                                <div class="kiosk-cat-item category-item" id="catbtn-<?= $cid ?>"
                                    onclick="scrollToCategory(<?= $cid ?>)">
                                    <span class="category-icon"><i class="fas <?= $icon ?>"></i></span>
                                    <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                                    <span class="category-count"><?= count($cat['products']) ?></span>
                                </div>
                            <?php endforeach; ?>

                        </div>
                    </div>

                    <div class="filter-section">
                        <h3 class="filter-title">
                            <i class="fas fa-sort-amount-down"></i> Sort By
                        </h3>
                        <div class="sort-options">
                            <div class="sort-item" id="sort-low" onclick="kioskSort('low')">
                                <i class="fas fa-arrow-down"></i> Price: Low to High
                            </div>
                            <div class="sort-item" id="sort-high" onclick="kioskSort('high')">
                                <i class="fas fa-arrow-up"></i> Price: High to Low
                            </div>
                            <div class="sort-item" id="sort-popular" onclick="kioskSort('popular')">
                                <i class="fas fa-fire"></i> Best Sellers
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="kiosk-products-area" id="kiosk-products-area">
                <div class="kiosk-active-header" id="kiosk-active-header">
                    <div class="kiosk-active-filters" id="kiosk-active-filters"></div>
                </div>

                <?php foreach ($categories_map as $cid => $cat): ?>
                    <div class="kiosk-cat-group" id="cat-group-<?= $cid ?>" data-cat-id="<?= $cid ?>">
                        <div class="kiosk-cat-label"><?= htmlspecialchars($cat['name']) ?></div>
                        <div class="kiosk-products-grid">
                            <?php foreach ($cat['products'] as $idx => $product): ?>
                                <?php $img = kioskProductImage($product); ?>
                                <div class="kiosk-product-card"
                                    data-price="<?= $product['price'] ?>"
                                    data-idx="<?= $idx ?>"
                                    data-pid="<?= $product['product_id'] ?>">

                                    <img src="<?= $img ?>"
                                        alt="<?= htmlspecialchars($product['name']) ?>"
                                        class="kiosk-prod-img"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="kiosk-prod-img-placeholder" style="display:none;">
                                        <i class="fas fa-mug-hot"></i>
                                    </div>

                                    <div class="kiosk-prod-info">
                                        <div class="kiosk-prod-name"><?= htmlspecialchars($product['name']) ?></div>
                                        <div class="kiosk-prod-desc"><?= htmlspecialchars($product['description'] ?? '') ?></div>
                                        <div class="kiosk-prod-footer">
                                            <div class="kiosk-prod-price-wrap">
                                                <span class="kiosk-prod-price">₱<?= number_format($product['price'], 2) ?></span>
                                                <?php $knet = kioskNetContent($product); if ($knet): ?>
                                                <span class="kiosk-prod-net"><?= $knet ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Qty selector — always visible, minus disabled at 0 -->
                                            <div class="kpf-qty-row" id="kpf-<?= $product['product_id'] ?>">
                                                <button class="kpf-qty-btn" disabled
                                                    id="kpf-minus-<?= $product['product_id'] ?>"
                                                    onclick="kioskCardQty(<?= $product['product_id'] ?>, -1)">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <span class="kpf-qty-num" id="kpf-num-<?= $product['product_id'] ?>">0</span>
                                                <button class="kpf-qty-btn kpf-plus"
                                                    onclick="kioskCardQty(<?= $product['product_id'] ?>, 1, '<?= addslashes(htmlspecialchars($product['name'])) ?>', <?= $product['price'] ?>, '<?= $img ?>')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

    <div class="kiosk-step" id="step3">
        <div class="kiosk-cart-page">
            <h1 class="kiosk-page-title">Shopping Cart</h1>
            <div id="step3-content"></div>
        </div>
    </div>

    <div class="kiosk-step" id="step4">
        <div class="kiosk-checkout-page">
            <h1 class="kiosk-page-title">Checkout</h1>

            <div class="kiosk-co-layout">

                <div class="kiosk-co-alert-row" id="step4-alert"></div>

                <div class="kiosk-co-left">

                    <div class="kiosk-co-card">
                        <div class="kiosk-co-card-title">
                            <i class="fas fa-location-dot"></i> Order Type
                        </div>
                        <div class="k-order-type-muted" id="co-order-type-display">
                            <i class="fas fa-utensils"></i> Dine In
                        </div>
                    </div>

                    <div class="kiosk-co-card">
                        <div class="kiosk-co-card-title">
                            <i class="fas fa-user"></i> Your Name
                            <span class="kiosk-co-optional">(Optional)</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="kiosk-co-label">Name</label>
                                <input type="text" id="co-name" class="kiosk-co-input" placeholder="e.g. Juan">
                            </div>
                            <div class="col-6">
                                <label class="kiosk-co-label">
                                    Mobile <span class="kiosk-co-optional">(Optional)</span>
                                </label>
                                <input type="tel" id="co-mobile" class="kiosk-co-input" placeholder="09XXXXXXXXX" maxlength="11">
                            </div>
                        </div>
                    </div>

                    <div class="kiosk-co-card">
                        <div class="kiosk-co-card-title">
                            <i class="fas fa-credit-card"></i> Payment Method
                        </div>
                        <div class="k-pay-opts">
                            <label class="k-pay-opt active" onclick="selectPayment(this, 'Cash')">
                                <input type="radio" name="k_payment" value="Cash" checked>
                                <i class="fas fa-money-bills"></i>
                                <div class="k-pay-name">Cash</div>
                            </label>
                            <label class="k-pay-opt k-pay-disabled">
                                <input type="radio" name="k_payment" value="Credit/Debit Card" disabled>
                                <i class="fas fa-credit-card"></i>
                                <div class="k-pay-name">Card</div>
                            </label>
                            <label class="k-pay-opt k-pay-disabled">
                                <input type="radio" name="k_payment" value="Tap-to-Pay (GCash)" disabled>
                                <i class="fas fa-mobile-screen-button"></i>
                                <div class="k-pay-name">GCash</div>
                            </label>
                            <label class="k-pay-opt k-pay-disabled">
                                <input type="radio" name="k_payment" value="Tap-to-Pay (Maya)" disabled>
                                <i class="fas fa-wallet"></i>
                                <div class="k-pay-name">Maya</div>
                            </label>
                        </div>
                    </div>

                </div>

                <div class="kiosk-co-right">
                    <div class="kiosk-co-summary-card">
                        <div class="k-co-sum-title">
                            <i class="fas fa-receipt"></i> Order Summary
                        </div>
                        <div id="co-sum-items"></div>
                        <hr class="k-sum-hr">
                        <div class="k-sum-total">
                            <span>Total</span>
                            <span id="co-sum-total">₱0.00</span>
                        </div>
                        <div class="k-co-sum-actions">
                            <button class="btn-kiosk-main" onclick="placeKioskOrder()">
                                <i class="fas fa-check-circle me-2"></i>Place Order
                            </button>
                            <button class="btn-kiosk-back mt-2" onclick="goToStep(3)">
                                <i class="fas fa-arrow-left me-1"></i> Back to Cart
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="kiosk-step" id="step5">
        <div class="kiosk-receipt-page">
            <div class="kiosk-receipt-card">
                <div class="kiosk-receipt-content" id="confirm-receipt">

                    <div class="kiosk-receipt-logo">
                        <img src="images/coffee_beans_logo.png" alt="Purge Coffee">
                        <span>Purge Coffee</span>
                    </div>

                    <h2 class="kiosk-receipt-heading" id="confirm-greeting">Thank you for your order!</h2>
                    <p class="kiosk-receipt-subline">Your order has been received and is being prepared.</p>

                    <div class="kiosk-receipt-order-box">
                        <div>
                            <div class="kiosk-receipt-order-label">Order Number</div>
                            <div class="kiosk-receipt-order-num" id="confirm-order-num">—</div>
                        </div>
                        <button class="kiosk-receipt-copy-btn" onclick="copyOrderNum()" title="Copy">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>

                    <div class="kiosk-receipt-status-row">
                        <span class="kiosk-receipt-status-badge">Order Confirmed</span>
                        <span class="kiosk-receipt-datetime" id="confirm-datetime"></span>
                    </div>

                    <hr class="kiosk-receipt-divider">

                    <div class="kiosk-receipt-section-hd">Order Details</div>
                    <div id="receipt-order-details"></div>

                    <hr class="kiosk-receipt-divider">

                    <div class="kiosk-receipt-section-hd">Order Summary</div>
                    <div id="receipt-items"></div>

                    <hr class="kiosk-receipt-divider" style="margin-top:10px;">

                    <div id="receipt-totals"></div>

                    <div class="kiosk-receipt-footer-note">
                        Thank you for choosing Purge Coffee! &#128578;
                    </div>

                </div>
                <div class="kiosk-receipt-actions">
                    <button class="btn-kiosk-new-order" onclick="startNewOrder()">
                        <i class="fas fa-rotate-left"></i> New Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="kiosk-dialog-overlay" id="delete-dialog-overlay">
        <div class="kd-modal">
            <div class="kd-modal-header">
                <h3><i class="fas fa-trash kd-modal-icon"></i> Remove Item</h3>
                <button class="kd-modal-close" onclick="closeDeleteDialog()">&#x2715;</button>
            </div>
            <div class="kd-modal-body">
                <p class="kd-modal-subtitle">Are you sure you want to remove this item from your cart? This cannot be undone.</p>
            </div>
            <div class="kd-modal-footer">
                <button class="kd-btn-cancel" onclick="closeDeleteDialog()">Cancel</button>
                <button class="kd-btn-delete" onclick="confirmDeleteItem()">
                    <i class="fas fa-trash"></i> Remove Item
                </button>
            </div>
        </div>
    </div>

    <div class="kiosk-dialog-overlay" id="back-dialog-overlay">
        <div class="kd-modal">
            <div class="kd-modal-header">
                <h3><i class="fas fa-triangle-exclamation kd-modal-icon"></i> Go Back?</h3>
                <button class="kd-modal-close" onclick="closeBackDialog()">&#x2715;</button>
            </div>
            <div class="kd-modal-body">
                <p class="kd-modal-subtitle">You have items in your cart. Are you sure you want to go back?</p>
            </div>
            <div class="kd-modal-footer">
                <button class="kd-btn-cancel" onclick="closeBackDialog()">Stay</button>
                <button class="kd-btn-goback" onclick="confirmGoBack()">
                    <i class="fas fa-arrow-left"></i> Go Back
                </button>
            </div>
        </div>
    </div>

    <!-- Cart bar — visible only on step 2 (Menu) -->
    <div class="kiosk-cart-bar" id="kiosk-cart-bar">
        <div class="kcb-wrapper">
            <!-- Left: empty spacer — mirrors k-bar-left -->
            <div class="kcb-bar-left"></div>

            <!-- Center: 5-column grid mirroring progress dots — col 1 = count, col 3 = subtotal -->
            <div class="kcb-bar-center">
                <div class="kcb-col">
                    <div class="kcb-left">
                        <span class="kcb-count" id="cbar-count">0</span>
                        <span class="kcb-label">items in cart</span>
                    </div>
                </div>
                <div class="kcb-col"></div>
                <div class="kcb-col">
                    <span class="kcb-total" id="cbar-total">₱0.00</span>
                </div>
                <div class="kcb-col"></div>
                <div class="kcb-col"></div>
            </div>

            <!-- Right: Continue button — mirrors k-bar-right (Back button) -->
            <div class="kcb-bar-right">
                <button class="kcb-btn" id="kcb-btn" onclick="goToStep(3)" disabled>
                    Continue <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="kiosk-toast" id="kiosk-toast"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* ── State ───────────────────────────────────────────────────── */
        let kioskOrderType = 'dine_in';
        let kioskPayment = 'Cash';
        let kioskCart = {};
        let kioskCurrentCat = 'all'; // 'all' | category id string
        let kioskCurrentSort = null; // null | 'low' | 'high' | 'popular'

        /* Step 1 is default — lock body scroll immediately */
        document.body.classList.add('kiosk-no-scroll');

        /* ── Step navigation ─────────────────────────────────────────── */
        /* ── Step navigation ─────────────────────────────────────────── */
        let currentStep = 1;

        function goToStep(n) {
            document.querySelectorAll('.kiosk-step').forEach(s => s.classList.remove('active'));
            document.getElementById('step' + n).classList.add('active');
            currentStep = n;

            /* Lock body scroll on step 1 */
            document.body.classList.toggle('kiosk-no-scroll', n === 1);

            /* Update progress dots */
            for (let i = 1; i <= 5; i++) {
                const dot = document.getElementById('kp' + i);
                if (dot) {
                    dot.className = 'kp-step';
                    if (i < n) dot.classList.add('done');
                    if (i === n) dot.classList.add('active');
                }
            }

            /* Show cart bar only on step 2 (Menu), sync totals */
            updateCartBar();
            const cartBar = document.getElementById('kiosk-cart-bar');
            if (cartBar) cartBar.style.display = (n === 2) ? 'block' : 'none';

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

            /* Render step-specific content */
            if (n === 3) renderCartStep();
            if (n === 4) renderCheckoutSummary();
        }

        /* Store the intended back target for dialog confirmation */
        let backTarget = null;

        /* Go back — warn if cart has items, navigate freely if empty */
        function goBack() {
            const hasItems = Object.keys(kioskCart).length > 0;
            backTarget = currentStep <= 1 ? 'home' : currentStep - 1;

            if (hasItems) {
                document.getElementById('back-dialog-overlay').classList.add('active');
            } else {
                executeGoBack();
            }
        }

        /* Execute the back navigation after confirmation */
        function confirmGoBack() {
            closeBackDialog();
            executeGoBack();
        }

        /* Close warning dialog */
        function closeBackDialog() {
            document.getElementById('back-dialog-overlay').classList.remove('active');
        }

        /* Perform the actual navigation */
        function executeGoBack() {
            if (backTarget === 'home') {
                window.location.href = 'index.php';
            } else {
                goToStep(backTarget);
            }
        }

        /* ── Step 1: select order type ───────────────────────────────── */
        function selectOrderType(type) {
            kioskOrderType = type;
            goToStep(2);
        }

        /* ── Step 2: inline card qty selector ───────────────────────── */

        /* Update qty display and minus-button disabled state */
        function setCardUI(pid, qty) {
            const numEl = document.getElementById('kpf-num-' + pid);
            const minusEl = document.getElementById('kpf-minus-' + pid);
            if (numEl) numEl.textContent = qty;
            if (minusEl) minusEl.disabled = (qty === 0);

            /* Highlight card border when qty > 0 */
            const card = document.querySelector(`.kiosk-product-card[data-pid="${pid}"]`);
            if (card) card.classList.toggle('in-cart', qty > 0);
        }

        /* Unified handler for both - and + on every card */
        function kioskCardQty(pid, delta, name, price, img) {
            const current = kioskCart[pid] ? kioskCart[pid].qty : 0;
            const next = Math.max(0, current + delta);

            if (next === 0) {
                /* Remove from cart */
                delete kioskCart[pid];
            } else if (!kioskCart[pid]) {
                /* First add — create cart entry with addons array */
                kioskCart[pid] = {
                    name,
                    price,
                    qty: next,
                    size: 'Short',
                    temp: 'Hot',
                    sugar: '0%',
                    milk: 'Whole',
                    addons: [],
                    notes: '',
                    img
                };
                showToast((name || 'Item') + ' added to cart!');
            } else {
                kioskCart[pid].qty = next;
            }

            setCardUI(pid, next);
            updateCartBar();
        }

        /* Update add-ons for a kiosk cart item */
        function updateKioskAddons(pid, checkbox) {
            if (!kioskCart[pid]) return;
            if (!kioskCart[pid].addons) kioskCart[pid].addons = [];
            const val = checkbox.value;
            if (checkbox.checked) {
                if (!kioskCart[pid].addons.includes(val)) kioskCart[pid].addons.push(val);
            } else {
                kioskCart[pid].addons = kioskCart[pid].addons.filter(a => a !== val);
            }
        }

        /* Legacy aliases */
        function kioskCardAdd(pid, name, price, img) {
            kioskCardQty(pid, 1, name, price, img);
        }

        function kioskAddToCart(pid, name, price, img) {
            kioskCardQty(pid, 1, name, price, img);
        }

        /* Recalculate totals, update the cart bar, and toggle Continue button */
        function updateCartBar() {
            const totalQty = Object.values(kioskCart).reduce((s, i) => s + i.qty, 0);
            const totalAmt = Object.values(kioskCart).reduce((s, i) => s + i.price * i.qty, 0);
            const countEl = document.getElementById('cbar-count');
            const totalEl = document.getElementById('cbar-total');
            const btn = document.getElementById('kcb-btn');
            if (countEl) countEl.textContent = totalQty;
            if (totalEl) totalEl.textContent = '₱' + totalAmt.toFixed(2);
            /* Enable Continue button only when at least one item is in the cart */
            if (btn) btn.disabled = (totalQty === 0);
        }

        /* ── Step 3: render cart with online cart design ─────────── */
        function renderCartStep() {
            const wrap = document.getElementById('step3-content');
            const items = Object.entries(kioskCart);

            if (!items.length) {
                wrap.innerHTML = `
                <div class="kiosk-empty">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Go back to browse our menu.</p>
                    <button class="btn-kiosk-main mt-3" style="width:auto;display:inline-block;padding:0.75rem 2rem;"
                        onclick="goToStep(2)">Browse Menu</button>
                </div>`;
                return;
            }

            let subtotal = 0;
            items.forEach(([pid, it]) => subtotal += it.price * it.qty);

            /* Build cart item cards using online ci-card style */
            let cardsHtml = '';
            items.forEach(([pid, it]) => {
                const imgHtml = it.img ?
                    `<img src="${it.img}" alt="${it.name}" class="k-ci-img"
                           onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                       <div class="k-ci-img-ph" style="display:none;"><i class="fas fa-mug-hot"></i></div>` :
                    `<div class="k-ci-img-ph"><i class="fas fa-mug-hot"></i></div>`;

                cardsHtml += `
                <div class="k-ci-card" id="kci-${pid}">
                    <div class="k-ci-top">
                        ${imgHtml}
                        <div class="k-ci-info">
                            <div class="k-ci-name">${it.name}</div>
                            <div class="k-ci-price">₱${it.price.toFixed(2)}</div>
                        </div>
                        <button class="k-ci-del" onclick="confirmDeleteKioskItem(${pid})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>

                    <div class="k-ci-cust-panel">
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <label class="k-cust-lbl">Size</label>
                                <select class="k-cust-sel" onchange="updateKioskOpt(${pid},'size',this.value)">
                                    ${[['Short','Short (8 fl oz)'],['Tall','Tall (12 fl oz)'],['Grande','Grande (16 fl oz)'],['Venti','Venti (20 fl oz)']].map(([v,l])=>`<option value="${v}"${it.size===v?' selected':''}>${l}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="k-cust-lbl">Temperature</label>
                                <select class="k-cust-sel" onchange="updateKioskOpt(${pid},'temp',this.value)">
                                    ${['Hot','Iced','Blended'].map(t=>`<option${it.temp===t?' selected':''}>${t}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="k-cust-lbl">Sugar Level</label>
                                <select class="k-cust-sel" onchange="updateKioskOpt(${pid},'sugar',this.value)">
                                    ${['0%','25%','50%','75%','100%'].map(s=>`<option${it.sugar===s?' selected':''}>${s}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="k-cust-lbl">Milk</label>
                                <select class="k-cust-sel" onchange="updateKioskOpt(${pid},'milk',this.value)">
                                    ${['Whole','Skim','Oat','Almond','Soy'].map(m=>`<option${it.milk===m?' selected':''}>${m}</option>`).join('')}
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="k-cust-lbl">Add-ons <span style="font-weight:400;opacity:0.6;">(Optional)</span></label>
                                <div class="k-addons-grid">
                                    ${[
                                        ['Extra Espresso Shot','Extra Espresso Shot (1 fl oz)'],
                                        ['Vanilla Syrup','Vanilla Syrup (0.5 fl oz)'],
                                        ['Whipped Cream','Whipped Cream (1 fl oz)'],
                                        ['Coffee Jelly','Coffee Jelly (1 fl oz)'],
                                        ['Pearl (Boba)','Pearl (Boba) (1 fl oz)']
                                    ].map(([v,l])=>`<label class="k-addon-item"><input type="checkbox" value="${v}" ${(it.addons||[]).includes(v)?'checked':''} onchange="updateKioskAddons(${pid},this)">${l}</label>`).join('')}
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="k-cust-lbl">Instructions (Optional)</label>
                                <textarea class="k-cust-ta" rows="2"
                                    placeholder="e.g. extra hot, less ice..."
                                    onchange="updateKioskOpt(${pid},'notes',this.value)">${it.notes}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="k-qty-row">
                        <div class="k-qty-wrap">
                            <button class="k-qty-btn" onclick="changeKioskQty(${pid},-1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="k-qty-num" id="kqty-${pid}">${it.qty}</span>
                            <button class="k-qty-btn" onclick="changeKioskQty(${pid},1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
            });

            /* Two-column layout: items left, summary right */
            wrap.innerHTML = `
            <div class="row g-4">
                <div class="col-lg-8">${cardsHtml}</div>
                <div class="col-lg-4">
                    <div class="kiosk-sum-card">
                        <div class="kiosk-sum-title">
                            <i class="fas fa-receipt me-2"></i>Order Summary
                        </div>
                        <div id="k-sum-lines"></div>
                        <hr class="k-sum-hr">
                        <div class="k-sum-total">
                            <span>Total</span>
                            <span id="k-sum-total">₱${subtotal.toFixed(2)}</span>
                        </div>
                        <div class="k-sum-actions">
                            <button class="btn-kiosk-main" onclick="goToStep(4)">
                                <i class="fas fa-arrow-right me-1"></i> Proceed to Checkout
                            </button>
                            <button class="btn-kiosk-back mt-2" onclick="goToStep(2)">
                                <i class="fas fa-arrow-left me-1"></i> Add More Items
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;

            renderSummaryLines();
        }

        /* Refresh just the summary line items in step 3 */
        function renderSummaryLines() {
            const el = document.getElementById('k-sum-lines');
            if (!el) return;
            let html = '';
            let total = 0;
            Object.entries(kioskCart).forEach(([pid, it]) => {
                const line = it.price * it.qty;
                total += line;
                html += `<div class="k-sum-line">
                    <span>${it.name} <span class="k-sum-qty">×${it.qty}</span></span>
                    <span>₱${line.toFixed(2)}</span>
                </div>`;
            });
            el.innerHTML = html;
            const tot = document.getElementById('k-sum-total');
            if (tot) tot.textContent = '₱' + total.toFixed(2);
        }

        /* ── Cart item operations ────────────────────────────────────── */
        let deleteTarget = null;

        /* Show delete confirmation dialog */
        function confirmDeleteKioskItem(pid) {
            deleteTarget = pid;
            document.getElementById('delete-dialog-overlay').classList.add('active');
        }

        /* Confirm and execute item removal */
        function confirmDeleteItem() {
            closeDeleteDialog();
            if (deleteTarget !== null) {
                removeKioskItem(deleteTarget);
                deleteTarget = null;
            }
        }

        /* Close delete dialog */
        function closeDeleteDialog() {
            document.getElementById('delete-dialog-overlay').classList.remove('active');
        }

        function removeKioskItem(pid) {
            delete kioskCart[pid];
            setCardUI(pid, 0);
            updateCartBar();
            renderCartStep();
        }

        function changeKioskQty(pid, delta) {
            if (!kioskCart[pid]) return;
            kioskCart[pid].qty = Math.max(1, kioskCart[pid].qty + delta);
            const el = document.getElementById('kqty-' + pid);
            if (el) el.textContent = kioskCart[pid].qty;
            setCardUI(pid, kioskCart[pid].qty);
            updateCartBar();
            renderSummaryLines();
        }

        function updateKioskOpt(pid, field, value) {
            if (!kioskCart[pid]) return;
            kioskCart[pid][field] = value;
        }

        /* ── Step 4: render checkout summary ────────────────────────── */
        function renderCheckoutSummary() {
            /* Update order type display */
            const badge = document.getElementById('co-order-type-display');
            badge.innerHTML = kioskOrderType === 'dine_in' ?
                '<i class="fas fa-utensils me-1"></i> Dine In' :
                '<i class="fas fa-shopping-bag me-1"></i> Take Out';

            /* Build summary item lines */
            const sumItems = document.getElementById('co-sum-items');
            let total = 0;
            let html = '';
            Object.entries(kioskCart).forEach(([pid, it]) => {
                const line = it.price * it.qty;
                total += line;
                html += `<div class="k-sum-line">
                    <span>${it.name} <span class="k-sum-qty">×${it.qty}</span></span>
                    <span>₱${line.toFixed(2)}</span>
                </div>`;
            });
            sumItems.innerHTML = html;
            document.getElementById('co-sum-total').textContent = '₱' + total.toFixed(2);
        }

        /* Activate payment option */
        function selectPayment(el, value) {
            kioskPayment = value;
            document.querySelectorAll('.k-pay-opt').forEach(o => o.classList.remove('active'));
            el.classList.add('active');
        }

        /* ── Step 4: place order via AJAX ────────────────────────────── */
        function placeKioskOrder() {
            const name = document.getElementById('co-name').value.trim();
            const mobile = document.getElementById('co-mobile').value.trim();

            if (!Object.keys(kioskCart).length) {
                showAlert('step4-alert', 'Your cart is empty.', 'danger');
                return;
            }

            /* Validate mobile if provided */
            if (mobile && !/^09\d{9}$/.test(mobile)) {
                showAlert('step4-alert', 'Please enter a valid Philippine mobile number (09XXXXXXXXX).', 'danger');
                return;
            }

            const btn = document.querySelector('#step4 .btn-kiosk-main');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';

            const fd = new FormData();
            fd.append('kiosk_order_type', kioskOrderType);
            fd.append('payment_method', kioskPayment);
            fd.append('customer_name', name || 'Guest');
            fd.append('mobile', mobile);
            fd.append('cart', JSON.stringify(kioskCart));

            fetch('php/kiosk_place_order.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showConfirmation(data);
                    } else {
                        showAlert('step4-alert', data.message || 'Order failed. Please try again.', 'danger');
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Place Order';
                    }
                })
                .catch(() => {
                    showAlert('step4-alert', 'Network error. Please try again.', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Place Order';
                });
        }

        /* ── Step 5: show confirmation receipt ───────────────────────── */
        function showConfirmation(data) {
            /* Order number */
            const orderNumEl = document.getElementById('confirm-order-num');
            const orderNum = 'ORD-' + new Date().getFullYear() + '-' + String(data.order_id).padStart(5, '0');
            orderNumEl.textContent = orderNum;
            orderNumEl.dataset.num = orderNum;

            /* Greeting */
            document.getElementById('confirm-greeting').textContent =
                (data.customer_name && data.customer_name !== 'Guest') ?
                `Thank you, ${data.customer_name}!` : 'Thank you for your order!';

            /* Datetime */
            document.getElementById('confirm-datetime').textContent = data.order_date || new Date().toLocaleString();

            /* Order details rows */
            const orderType = data.kiosk_order_type === 'dine_in' ? 'Dine In' : 'Take Out';
            document.getElementById('receipt-order-details').innerHTML = `
                <div class="kiosk-receipt-info-row">
                    <span class="r-key">Type</span>
                    <span class="r-val">${orderType}</span>
                </div>
                <div class="kiosk-receipt-info-row">
                    <span class="r-key">Payment</span>
                    <span class="r-val">${data.payment_method}</span>
                </div>`;

            /* Item rows */
            let itemsHtml = '';
            let subtotal = 0;
            data.items.forEach(it => {
                subtotal += it.subtotal;
                const imgHtml = it.image_path ?
                    `<img src="${it.image_path}" alt="${it.name}" class="kiosk-receipt-item-img"
                           onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                       <div class="kiosk-receipt-item-img-ph" style="display:none;"><i class="fas fa-mug-hot"></i></div>` :
                    `<div class="kiosk-receipt-item-img-ph"><i class="fas fa-mug-hot"></i></div>`;

                const meta = [it.size, it.temp, it.sugar ? `Sugar ${it.sugar}` : null]
                    .filter(Boolean).join(' · ');

                itemsHtml += `
                <div class="kiosk-receipt-item">
                    ${imgHtml}
                    <div class="kiosk-receipt-item-detail">
                        <div class="kiosk-receipt-item-name">${it.name}</div>
                        ${meta ? `<div class="kiosk-receipt-item-meta">${meta}</div>` : ''}
                        <div class="kiosk-receipt-item-qty">Qty: ${it.qty}</div>
                    </div>
                    <div class="kiosk-receipt-item-price">₱${it.subtotal.toFixed(2)}</div>
                </div>`;
            });
            document.getElementById('receipt-items').innerHTML = itemsHtml;

            /* Totals */
            document.getElementById('receipt-totals').innerHTML = `
                <div class="kiosk-receipt-amount-row">
                    <span>Subtotal</span>
                    <span>₱${subtotal.toFixed(2)}</span>
                </div>
                <hr class="kiosk-receipt-divider" style="margin:8px 0;">
                <div class="kiosk-receipt-total-row">
                    <span>Total Amount</span>
                    <span>₱${parseFloat(data.total).toFixed(2)}</span>
                </div>`;

            /* Clear cart and go to step 5 */
            kioskCart = {};
            goToStep(5);
        }

        /* Copy order number to clipboard */
        function copyOrderNum() {
            const num = document.getElementById('confirm-order-num').dataset.num || '';
            if (navigator.clipboard && num) {
                navigator.clipboard.writeText(num);
                showToast('Order number copied!');
            }
        }

        /* ── Utilities ───────────────────────────────────────────────── */
        function showToast(msg) {
            const t = document.getElementById('kiosk-toast');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 2200);
        }

        function showAlert(containerId, msg, type) {
            const el = document.getElementById(containerId);
            if (!el) return;
            el.innerHTML = `<div class="alert alert-${type} py-2 px-3" style="font-size:0.88rem;">${msg}</div>`;
            setTimeout(() => el.innerHTML = '', 4000);
        }

        /* Filter groups by category — shows/hides groups, updates active bar */
        function scrollToCategory(cid) {
            kioskCurrentCat = cid;

            /* Update sidebar active state */
            document.querySelectorAll('.kiosk-cat-item').forEach(i => i.classList.remove('active'));
            const btn = cid === 'all' ? document.getElementById('catbtn-all') : document.getElementById('catbtn-' + cid);
            if (btn) btn.classList.add('active');

            /* Show only the selected category group, or all if 'all' */
            document.querySelectorAll('.kiosk-cat-group').forEach(g => {
                g.style.display = (cid === 'all' || g.dataset.catId == cid) ? '' : 'none';
            });

            updateKioskActiveHeader();
        }

        /* Sort cards by price within each visible group */
        function kioskSort(mode) {
            kioskCurrentSort = mode;

            document.querySelectorAll('.sort-item').forEach(s => s.classList.remove('active'));
            const el = document.getElementById('sort-' + mode);
            if (el) el.classList.add('active');

            document.querySelectorAll('.kiosk-cat-group').forEach(group => {
                const grid = group.querySelector('.kiosk-products-grid');
                if (!grid) return;
                const cards = Array.from(grid.querySelectorAll('.kiosk-product-card'));
                cards.sort((a, b) => {
                    const pa = parseFloat(a.dataset.price || 0);
                    const pb = parseFloat(b.dataset.price || 0);
                    if (mode === 'low') return pa - pb;
                    if (mode === 'high') return pb - pa;
                    return 0;
                });
                cards.forEach(c => grid.appendChild(c));
            });

            updateKioskActiveHeader();
        }

        /* Build and show/hide the active filters bar */
        function updateKioskActiveHeader() {
            const header = document.getElementById('kiosk-active-header');
            const sectionTitle = document.querySelector('.kiosk-section-title');
            const hasCat = kioskCurrentCat !== 'all';
            const hasSort = kioskCurrentSort !== null;

            if (!hasCat && !hasSort) {
                header.classList.remove('visible');
                if (sectionTitle) sectionTitle.style.display = '';
                return;
            }

            /* Hide "ALL MENU" title when any filter is active */
            if (sectionTitle) sectionTitle.style.display = 'none';

            const sortLabels = {
                low: 'Price: Low to High',
                high: 'Price: High to Low',
                popular: 'Best Sellers'
            };
            const sortIcons = {
                low: 'fa-arrow-down',
                high: 'fa-arrow-up',
                popular: 'fa-fire'
            };

            let html = '<span class="kiosk-filter-label">Active Filters:</span>';

            /* Category chip */
            if (hasCat) {
                const catName = document.getElementById('catbtn-' + kioskCurrentCat)
                    ?.querySelector('.category-name')?.textContent?.trim() || '';
                html += `<span class="kiosk-sort-badge">${catName}
                    <span onclick="kioskClearCategory()"><i class="fas fa-times"></i></span>
                </span>`;
            }

            /* Sort chip */
            if (hasSort) {
                html += `<span class="kiosk-sort-badge">
                    <i class="fas ${sortIcons[kioskCurrentSort]}"></i>
                    ${sortLabels[kioskCurrentSort]}
                    <span onclick="kioskClearSort()"><i class="fas fa-times"></i></span>
                </span>`;
            }

            html += `<button class="kiosk-clear-sort" onclick="kioskClearAll()">Clear All <i class="fas fa-times"></i></button>`;

            document.getElementById('kiosk-active-filters').innerHTML = html;
            header.classList.add('visible');
        }

        /* Clear category filter only */
        function kioskClearCategory() {
            scrollToCategory('all');
        }

        /* Clear sort only — restore original card order */
        function kioskClearSort() {
            kioskCurrentSort = null;
            document.querySelectorAll('.sort-item').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.kiosk-cat-group').forEach(group => {
                const grid = group.querySelector('.kiosk-products-grid');
                if (!grid) return;
                const cards = Array.from(grid.querySelectorAll('.kiosk-product-card'));
                cards.sort((a, b) => parseInt(a.dataset.idx || 0) - parseInt(b.dataset.idx || 0));
                cards.forEach(c => grid.appendChild(c));
            });
            updateKioskActiveHeader();
        }

        /* Clear all active filters */
        function kioskClearAll() {
            kioskCurrentSort = null;
            document.querySelectorAll('.sort-item').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.kiosk-cat-group').forEach(group => {
                const grid = group.querySelector('.kiosk-products-grid');
                if (!grid) return;
                const cards = Array.from(grid.querySelectorAll('.kiosk-product-card'));
                cards.sort((a, b) => parseInt(a.dataset.idx || 0) - parseInt(b.dataset.idx || 0));
                cards.forEach(c => grid.appendChild(c));
            });
            scrollToCategory('all');
        }

        function startNewOrder() {
            kioskCart = {};
            kioskOrderType = 'dine_in';
            kioskPayment = 'Cash';
            updateCartBar();
            goToStep(1);
        }
    </script>


</body>

</html>