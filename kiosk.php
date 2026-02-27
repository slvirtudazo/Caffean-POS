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
    "SELECT p.product_id, p.name, p.description, p.price, p.image_path,
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
            <div class="nav-icons">
                <button class="btn-cancel-order" onclick="goBack()">Back</button>
            </div>
        </div>
    </nav>

    <div class="kiosk-progress" id="kiosk-progress">
        <div class="kiosk-progress-inner">
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
                                9 => 'fa-plus-circle'
                            ];
                            foreach ($categories_map as $cid => $cat):
                                $icon = $cat_icons[$cid] ?? 'fa-circle';
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
                <div class="kiosk-section-title">All Menu</div>

                <?php foreach ($categories_map as $cid => $cat): ?>
                    <div class="kiosk-cat-group" id="cat-group-<?= $cid ?>" data-cat-id="<?= $cid ?>">
                        <div class="kiosk-cat-label"><?= htmlspecialchars($cat['name']) ?></div>
                        <div class="kiosk-products-grid">
                            <?php foreach ($cat['products'] as $product): ?>
                                <div class="kiosk-product-card" data-price="<?= $product['price'] ?>">
                                    <?php $img = kioskProductImage($product); ?>
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
                                            <span class="kiosk-prod-price">₱<?= number_format($product['price'], 2) ?></span>
                                            <button class="btn-kiosk-add"
                                                onclick="kioskAddToCart(<?= $product['product_id'] ?>, '<?= addslashes(htmlspecialchars($product['name'])) ?>', <?= $product['price'] ?>, '<?= $img ?>')">
                                                <i class="fas fa-plus"></i>
                                            </button>
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
            <h1 class="kiosk-page-title">Your Cart</h1>
            <div id="step3-content"></div>
        </div>
    </div>

    <div class="kiosk-step" id="step4">
        <div class="kiosk-checkout-page">
            <h1 class="kiosk-page-title">Checkout</h1>

            <div class="kiosk-co-layout">

                <!-- Alert spans both columns -->
                <div class="kiosk-co-alert-row" id="step4-alert"></div>

                <!-- Left column: order type + name + payment -->
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
                                <label class="kiosk-co-label">Mobile <span class="kiosk-co-optional">(Optional)</span></label>
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

                <!-- Right column: order summary + actions -->
                <div class="kiosk-co-right">
                    <div class="kiosk-co-card kiosk-co-summary-card">
                        <div class="kiosk-co-card-title">
                            <i class="fas fa-receipt"></i> Order Summary
                        </div>
                        <div id="co-sum-items"></div>
                        <hr class="k-sum-hr">
                        <div class="k-sum-total">
                            <span>Total</span>
                            <span id="co-sum-total">₱0.00</span>
                        </div>
                        <button class="btn-kiosk-main mt-3" onclick="placeKioskOrder()">
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

    <div class="kiosk-step" id="step5">
        <div class="kiosk-confirm-page">
            <div class="kiosk-confirm-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="kiosk-confirm-h1">Order Placed!</h1>
            <p class="kiosk-confirm-sub" id="confirm-greeting">Thank you! Your order is being prepared.</p>

            <div class="kiosk-order-number-box">
                <div class="kon-label">Your Order Number</div>
                <div class="kon-number" id="confirm-order-num">#000</div>
                <div class="kon-type" id="confirm-order-type"></div>
            </div>

            <div class="kiosk-wait-notice">
                <i class="fas fa-bell"></i>
                <span>Please wait for your number to be called at the claim counter. Keep this receipt with you.</span>
            </div>

            <div class="kiosk-receipt" id="confirm-receipt">
                <div class="kiosk-receipt-title">Receipt</div>
                <div id="receipt-details"></div>
            </div>

            <button class="btn-kiosk-new-order" onclick="startNewOrder()">
                <i class="fas fa-rotate-left me-2"></i>New Order
            </button>
        </div>
    </div>

    <!-- Delete item warning dialog -->
    <div class="kiosk-dialog-overlay" id="delete-dialog-overlay">
        <div class="kiosk-dialog">
            <div class="kiosk-dialog-icon" style="color:#c0392b;"><i class="fas fa-trash-alt"></i></div>
            <h2 class="kiosk-dialog-title">Remove Item?</h2>
            <p class="kiosk-dialog-msg">Are you sure you want to remove this item from your cart?</p>
            <div class="kiosk-dialog-actions">
                <button class="kiosk-dialog-btn kiosk-dialog-cancel" onclick="closeDeleteDialog()">Cancel</button>
                <button class="kiosk-dialog-btn kiosk-dialog-confirm" onclick="confirmDeleteItem()" style="background:#c0392b; color:#fff;">Remove</button>
            </div>
        </div>
    </div>

    <!-- Back warning dialog — shown when cart has items -->
    <div class="kiosk-dialog-overlay" id="back-dialog-overlay">
        <div class="kiosk-dialog">
            <div class="kiosk-dialog-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <h2 class="kiosk-dialog-title">Go Back?</h2>
            <p class="kiosk-dialog-msg">You have items in your cart. Are you sure you want to go back?</p>
            <div class="kiosk-dialog-actions">
                <button class="kiosk-dialog-btn kiosk-dialog-cancel" onclick="closeBackDialog()">Stay</button>
                <button class="kiosk-dialog-btn kiosk-dialog-confirm" onclick="confirmGoBack()">Go Back</button>
            </div>
        </div>
    </div>

    <div class="kiosk-cart-bar" id="kiosk-cart-bar">
        <div class="cart-bar-left">
            <div class="cart-bar-count" id="cbar-count">0</div>
            <span class="cart-bar-label">items in cart</span>
        </div>
        <span class="cart-bar-total" id="cbar-total">₱0.00</span>
        <button class="btn-view-cart" onclick="goToStep(3)">
            View Cart <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </div>

    <div class="kiosk-toast" id="kiosk-toast"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* ── State ───────────────────────────────────────────────────── */
        let kioskOrderType = 'dine_in'; // 'dine_in' | 'take_out'
        let kioskPayment = 'Cash';
        let kioskCart = {}; // { product_id: { name, price, qty, size, temp, sugar, milk, notes, img } }

        /* Step 1 is default — lock body scroll immediately */
        document.body.classList.add('kiosk-no-scroll');

        /* ── Step navigation ─────────────────────────────────────────── */
        /* ── Step navigation ─────────────────────────────────────────── */
        let currentStep = 1;

        function goToStep(n) {
            document.querySelectorAll('.kiosk-step').forEach(s => s.classList.remove('active'));
            document.getElementById('step' + n).classList.add('active');
            currentStep = n;

            /* Lock body scroll on step 1 (Type tab has no scroll) */
            document.body.classList.toggle('kiosk-no-scroll', n === 1);

            /* update progress dots — kp1-kp5 */
            for (let i = 1; i <= 5; i++) {
                const dot = document.getElementById('kp' + i);
                if (dot) {
                    dot.className = 'kp-step';
                    if (i < n) dot.classList.add('done');
                    if (i === n) dot.classList.add('active');
                }
            }

            /* sync cart totals */
            updateCartBar();

            /* SHOW CART DETAILS BAR ONLY ON STEP 2 (Menu) */
            const cartBar = document.getElementById('kiosk-cart-bar');
            if (cartBar) {
                if (n === 2) {
                    cartBar.style.display = 'flex';
                } else {
                    cartBar.style.display = 'none';
                }
            }

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

            /* render step-specific content */
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

        /* ── Step 2: add to kiosk cart ───────────────────────────────── */
        function kioskAddToCart(pid, name, price, img) {
            if (kioskCart[pid]) {
                kioskCart[pid].qty++;
            } else {
                kioskCart[pid] = {
                    name,
                    price,
                    qty: 1,
                    size: 'Short',
                    temp: 'Hot',
                    sugar: '0%',
                    milk: 'Whole',
                    notes: '',
                    img
                };
            }
            updateCartBar();
            showToast(name + ' has been added to your cart!');
        }

        /* Recalc totals and update floating bar */
        function updateCartBar() {
            const totalQty = Object.values(kioskCart).reduce((s, i) => s + i.qty, 0);
            const totalAmt = Object.values(kioskCart).reduce((s, i) => s + i.price * i.qty, 0);

            document.getElementById('cbar-count').textContent = totalQty;
            document.getElementById('cbar-total').textContent = '₱' + totalAmt.toFixed(2);
        }

        /* ── Step 3: render cart ─────────────────────────────────────── */
        function renderCartStep() {
            const wrap = document.getElementById('step3-content');
            const items = Object.entries(kioskCart);

            if (!items.length) {
                wrap.innerHTML = `
            <div class="kiosk-empty">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Go back to browse our menu.</p>
                <button class="btn-kiosk-main mt-3" style="width:auto; display:inline-block; padding:0.75rem 2rem;"
                    onclick="goToStep(2)">Browse Menu</button>
            </div>`;
                return;
            }

            let subtotal = 0;
            items.forEach(([pid, it]) => subtotal += it.price * it.qty);

            let cardsHtml = '';
            items.forEach(([pid, it]) => {
                const imgHtml = it.img ?
                    `<img src="${it.img}" alt="${it.name}" class="k-ci-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
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
                <button class="k-ci-del" onclick="confirmDeleteKioskItem(${pid})"><i class="fas fa-trash-alt"></i></button>
            </div>

            <div class="k-ci-cust-panel">
                <div class="row g-2 mt-1">
                    <div class="col-6 col-md-3">
                        <label class="k-cust-lbl">Size</label>
                        <select class="k-cust-sel" onchange="updateKioskOpt(${pid},'size',this.value)">
                            ${['Short','Tall','Grande','Venti'].map(s=>`<option${it.size===s?' selected':''}>${s}</option>`).join('')}
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
                        <label class="k-cust-lbl">Instructions (Optional)</label>
                        <textarea class="k-cust-ta" rows="2"
                            placeholder="e.g. extra hot, less ice..."
                            onchange="updateKioskOpt(${pid},'notes',this.value)">${it.notes}</textarea>
                    </div>
                </div>
            </div>

            <div class="k-qty-row">
                <button class="k-qty-btn" onclick="changeKioskQty(${pid},-1)"><i class="fas fa-minus"></i></button>
                <span class="k-qty-num" id="kqty-${pid}">${it.qty}</span>
                <button class="k-qty-btn" onclick="changeKioskQty(${pid},1)"><i class="fas fa-plus"></i></button>
            </div>
        </div>`;
            });

            wrap.innerHTML = `
        <div class="kiosk-cart-layout">
            <div>${cardsHtml}</div>
            <div>
                <div class="kiosk-sum-card">
                    <div class="kiosk-sum-title">Order Summary</div>
                    <div id="k-sum-lines"></div>
                    <hr class="k-sum-hr">
                    <div class="k-sum-total">
                        <span>Total</span>
                        <span id="k-sum-total">₱${subtotal.toFixed(2)}</span>
                    </div>
                    <button class="btn-kiosk-main mt-3" onclick="goToStep(4)">
                        <i class="fas fa-arrow-right me-1"></i> Proceed to Checkout
                    </button>
                    <button class="btn-kiosk-back mt-2" onclick="goToStep(2)">
                        <i class="fas fa-arrow-left me-1"></i> Add More Items
                    </button>
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
                html += `<div class="k-sum-line"><span>${it.name} ×${it.qty}</span><span>₱${line.toFixed(2)}</span></div>`;
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
            updateCartBar();
            renderCartStep();
        }

        function changeKioskQty(pid, delta) {
            if (!kioskCart[pid]) return;
            kioskCart[pid].qty = Math.max(1, kioskCart[pid].qty + delta);
            const el = document.getElementById('kqty-' + pid);
            if (el) el.textContent = kioskCart[pid].qty;
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
                '<i class="fas fa-utensils"></i> Dine In' :
                '<i class="fas fa-shopping-bag"></i> Take Out';

            /* Summary items */
            const sumItems = document.getElementById('co-sum-items');
            let total = 0;
            let html = '';
            Object.entries(kioskCart).forEach(([pid, it]) => {
                const line = it.price * it.qty;
                total += line;
                html += `<div class="k-sum-line"><span>${it.name} ×${it.qty}</span><span>₱${line.toFixed(2)}</span></div>`;
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

        /* ── Step 5: show confirmation ───────────────────────────────── */
        function showConfirmation(data) {
            document.getElementById('confirm-order-num').textContent = '#' + String(data.order_id).padStart(3, '0');
            document.getElementById('confirm-greeting').textContent =
                (data.customer_name && data.customer_name !== 'Guest') ?
                `Thank you, ${data.customer_name}! Your order is being prepared.` :
                'Thank you! Your order is being prepared.';
            document.getElementById('confirm-order-type').textContent =
                data.kiosk_order_type === 'dine_in' ? '🍽 Dine In' : '🛍 Take Out';

            /* Receipt */
            let itemsHtml = '<div class="kr-items">';
            data.items.forEach(it => {
                itemsHtml += `<div class="kr-item-line">
            <span>${it.name} ×${it.qty}</span>
            <span>₱${it.subtotal.toFixed(2)}</span>
        </div>`;
            });
            itemsHtml += '</div>';

            document.getElementById('receipt-details').innerHTML = `
        <div class="kr-detail-row"><span class="kr-lbl">Order #</span><span>#${String(data.order_id).padStart(3,'0')}</span></div>
        <div class="kr-detail-row"><span class="kr-lbl">Date</span><span>${data.order_date}</span></div>
        <div class="kr-detail-row"><span class="kr-lbl">Type</span><span>${data.kiosk_order_type === 'dine_in' ? 'Dine In' : 'Take Out'}</span></div>
        <div class="kr-detail-row"><span class="kr-lbl">Payment</span><span>${data.payment_method}</span></div>
        ${itemsHtml}
        <div class="kr-total"><span>Total</span><span>₱${parseFloat(data.total).toFixed(2)}</span></div>`;

            /* Clear cart and go to step 5 */
            kioskCart = {};
            updateCartBar();
            goToStep(5);
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

        function scrollToCategory(cid) {
            /* Highlight active sidebar item */
            document.querySelectorAll('.kiosk-cat-item').forEach(i => i.classList.remove('active'));
            const btn = cid === 'all' ? document.getElementById('catbtn-all') : document.getElementById('catbtn-' + cid);
            if (btn) btn.classList.add('active');

            /* Scroll to first group (all) or specific category group */
            const group = cid === 'all' ?
                document.querySelector('.kiosk-cat-group') :
                document.getElementById('cat-group-' + cid);
            if (group) group.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        /* Sort cards by price within each category grid */
        function kioskSort(mode) {
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
        }

        /* ── New order reset ─────────────────────────────────────── */
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