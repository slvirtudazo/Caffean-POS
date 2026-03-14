<?php

// Shopping Cart Page
require_once 'php/db_connection.php';
require_once 'php/product_images.php';

// Redirect admin users away from the cart.
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit();
}

// Normalize legacy integer cart entries to the standard array format.
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

// Fetch active cart products from the database.
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
        $p['image_path']           = resolveProductImage($p['name'], $p['image_path']);
        $subtotal                 += $p['item_total'];
        $cart_items[]              = $p;
    }
}

// Fetch the logged-in user's name and email.
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
    <title>Shopping Cart — Caffean</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/buttons.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/search.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/cart.css?v=<?php echo time(); ?>">
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

    <!-- Cart Section -->
    <section id="cart-s1" class="pg-section active">
        <div class="container">
            <h1 class="cart-pg-title">Shopping Cart</h1>

            <?php if (empty($cart_items)): ?>
                <div class="cart-empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any items yet.<br>Discover our delicious drinks and pastries to get started!</p>
                    <a href="menu.php" class="btn-browse-menu">Browse Menu</a>
                </div>
            <?php else: ?>
                <div class="row g-4">

                    <!-- Left: Cart Items -->
                    <div class="col-lg-7" id="cart-items-col">
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

                                <!-- Product top row -->
                                <div class="ci-top">
                                    <div class="ci-img-wrap">
                                        <img src="<?= $item['image_path'] ? htmlspecialchars($item['image_path']) : 'images/placeholder.png' ?>"
                                            alt="<?= htmlspecialchars($item['name']) ?>" />
                                    </div>
                                    <div class="ci-info">
                                        <h4 class="ci-name"><?= htmlspecialchars($item['name']) ?></h4>
                                        <p class="ci-base-price">₱<?= number_format($item['price'], 2) ?></p>
                                        <p class="ci-opts-preview" id="opts-<?= $pid ?>"><?= htmlspecialchars($opts) ?></p>
                                    </div>
                                    <div class="ci-right">
                                        <button class="ci-del-btn"
                                            onclick="confirmDeleteItem(<?= $pid ?>, '<?= addslashes(htmlspecialchars($item['name'])) ?>')"
                                            title="Remove item">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Customization options -->
                                <div class="ci-customize">
                                    <div class="ci-cust-fields" id="cust-<?= $pid ?>">
                                        <div class="row g-2 mt-1">
                                            <div class="col-6 col-md-3">
                                                <label class="cust-lbl">Size</label>
                                                <select class="cust-sel"
                                                    onchange="updateOption(<?= $pid ?>, 'size', this.value)">
                                                    <?php
                                                    $sizes = [
                                                        'Short'  => 'Short (8 fl oz)',
                                                        'Tall'   => 'Tall (12 fl oz)',
                                                        'Grande' => 'Grande (16 fl oz)',
                                                        'Venti'  => 'Venti (20 fl oz)',
                                                    ];
                                                    foreach ($sizes as $val => $label): ?>
                                                        <option value="<?= $val ?>" <?= $item['size'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label class="cust-lbl">Temperature</label>
                                                <select class="cust-sel"
                                                    onchange="updateOption(<?= $pid ?>, 'temperature', this.value)">
                                                    <?php foreach (['Hot', 'Iced', 'Blended'] as $t): ?>
                                                        <option value="<?= $t ?>" <?= $item['temperature'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label class="cust-lbl">Sugar Level</label>
                                                <select class="cust-sel"
                                                    onchange="updateOption(<?= $pid ?>, 'sugar_level', this.value)">
                                                    <?php foreach (['0%', '25%', '50%', '75%', '100%'] as $sl): ?>
                                                        <option value="<?= $sl ?>" <?= $item['sugar_level'] === $sl ? 'selected' : '' ?>><?= $sl ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <label class="cust-lbl">Milk</label>
                                                <select class="cust-sel"
                                                    onchange="updateOption(<?= $pid ?>, 'milk', this.value)">
                                                    <?php foreach (['Whole', 'Skim', 'Oat', 'Almond', 'None'] as $m): ?>
                                                        <option value="<?= $m ?>" <?= $item['milk'] === $m ? 'selected' : '' ?>><?= $m ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="cust-lbl">
                                                    Add-ons <span class="opt-text">(Optional)</span>
                                                </label>
                                                <div class="addons-grid">
                                                    <?php
                                                    $addons = [
                                                        'Extra Espresso Shot' => 'Extra Espresso Shot (1 fl oz)',
                                                        'Vanilla Syrup'       => 'Vanilla Syrup (0.5 fl oz)',
                                                        'Whipped Cream'       => 'Whipped Cream (1 fl oz)',
                                                        'Coffee Jelly'        => 'Coffee Jelly (1 fl oz)',
                                                        'Pearl (Boba)'        => 'Pearl (Boba) (1 fl oz)',
                                                    ];
                                                    foreach ($addons as $val => $label): ?>
                                                        <label class="addon-item">
                                                            <input type="checkbox"
                                                                value="<?= $val ?>"
                                                                <?= in_array($val, $item['addons']) ? 'checked' : '' ?>
                                                                onchange="updateAddons(<?= $pid ?>)" />
                                                            <?= $label ?>
                                                        </label>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <label class="cust-lbl">
                                                    Instructions <span class="opt-text">(Optional)</span>
                                                </label>
                                                <textarea class="cust-ta" rows="2"
                                                    placeholder="Add any special requests..."
                                                    onchange="updateOption(<?= $pid ?>, 'special_instructions', this.value)"><?= htmlspecialchars($item['special_instructions']) ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quantity controls -->
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
                    <div class="col-lg-5">
                        <div class="sum-card sum-sticky" id="s1-sum-card">
                            <h3 class="sum-title"><i class="fas fa-receipt me-2"></i>Order Summary</h3>
                            <div class="sum-items" id="s1-item-lines">
                                <?php foreach ($cart_items as $item): ?>
                                    <div class="sum-item-line" id="sline-<?= $item['product_id'] ?>">
                                        <span class="sil-name">
                                            <?= htmlspecialchars($item['name']) ?>
                                            <span class="sil-qty">× <span id="siqty-<?= $item['product_id'] ?>"><?= $item['quantity'] ?></span></span>
                                            <small class="sil-opts" id="s1opts-<?= $item['product_id'] ?>">
                                                <?= htmlspecialchars($item['size'] . ' · ' . $item['temperature'] . ' · Sugar ' . $item['sugar_level']) ?>
                                            </small>
                                        </span>
                                        <span class="sil-price" id="sitot-<?= $item['product_id'] ?>">
                                            ₱<?= number_format($item['item_total'], 2) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="sum-footer-wrap">
                                <div class="sum-bottom">
                                    <div class="sum-hr"></div>
                                    <div class="sum-calc-row">
                                        <span>Subtotal</span>
                                        <span id="s1-sub">₱<?= number_format($subtotal, 2) ?></span>
                                    </div>
                                    <div class="sum-calc-row">
                                        <span>Delivery Fee</span>
                                        <span id="s1-ship">₱<?= number_format($DELIVERY_FEE, 2) ?></span>
                                    </div>
                                    <div class="sum-hr"></div>
                                    <div class="sum-total-row">
                                        <span>Total Amount</span>
                                        <span id="s1-total">₱<?= number_format($subtotal + $DELIVERY_FEE, 2) ?></span>
                                    </div>
                                </div>
                                <div class="sum-actions">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="checkout.php" class="btn-cart-main text-center text-decoration-none">
                                            Proceed to Checkout <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="login.php" class="btn-cart-main text-center text-decoration-none">
                                            Login to Checkout
                                        </a>
                                    <?php endif; ?>
                                    <button class="btn-cart-back mt-2" onclick="window.location.href='menu.php'">
                                        <i class="fas fa-arrow-left me-1"></i> Continue Shopping
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Delete confirmation modal -->
    <div class="cart-modal-overlay" id="cart-del-modal" role="dialog" aria-modal="true">
        <div class="cart-modal">
            <div class="cart-modal-header">
                <h3>Remove Item</h3>
                <button class="cart-modal-close" onclick="closeDeleteModal()" title="Close">&#x2715;</button>
            </div>
            <div class="cart-modal-body">
                <p class="cart-modal-subtitle">Are you sure you want to remove <strong id="del-modal-name">this item</strong> from your cart? This cannot be undone.</p>
            </div>
            <div class="cart-modal-footer">
                <button class="cart-modal-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="cart-modal-btn-delete" id="del-modal-confirm">Remove Item</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/search.js?v=<?php echo time(); ?>"></script>
    <script>
        const DELIVERY_FEE = <?= $DELIVERY_FEE ?>;
        let subtotal = <?= $subtotal ?>;

        // Keep the summary card height aligned with a single item card.
        function syncSumHeight() {
            const cards = document.querySelectorAll('#cart-items-col .ci-card');
            const sumCard = document.getElementById('s1-sum-card');
            if (!sumCard) return;
            sumCard.style.minHeight = cards.length === 1 ? cards[0].offsetHeight + 'px' : '';
        }

        document.addEventListener('DOMContentLoaded', syncSumHeight);
        window.addEventListener('resize', syncSumHeight);

        // Update a single customization option for a cart item.
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

        // Update add-ons for a cart item.
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

        // Rebuild the options preview text in the item card.
        function rebuildOptsSummary(pid, item) {
            const el = document.getElementById('opts-' + pid);
            if (el) {
                const parts = [item.size, item.temperature, 'Sugar ' + item.sugar_level, item.milk + ' Milk'];
                if (item.addons && item.addons.length) parts.push(item.addons.join(', '));
                el.textContent = parts.join(' · ');
            }
            const s1el = document.getElementById('s1opts-' + pid);
            if (s1el) s1el.textContent = [item.size, item.temperature, 'Sugar ' + item.sugar_level].join(' · ');
        }

        // Change a cart item's quantity.
        function changeQty(pid, delta) {
            const qEl = document.getElementById('qty-' + pid);
            const current = parseInt(qEl.textContent);
            if (current <= 1 && delta < 0) return;
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

        // Show the delete confirmation modal.
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
            const pid = _pendingDeletePid;
            if (pid !== null) {
                closeDeleteModal();
                removeCartItem(pid);
            }
        });

        document.getElementById('cart-del-modal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

        // Remove an item from the cart.
        function removeCartItem(pid) {
            const fd = new FormData();
            fd.append('action', 'remove');
            fd.append('product_id', pid);
            fetch('php/update_cart_item.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => {
                    if (!r.ok) throw new Error('Server error ' + r.status);
                    return r.json();
                })
                .then(d => {
                    if (!d.success) return;
                    document.querySelector(`.ci-card[data-pid="${pid}"]`)?.remove();
                    document.getElementById('sline-' + pid)?.remove();
                    subtotal = d.subtotal;
                    refreshTotals();
                    syncSumHeight();
                    if (typeof updateCartCount === 'function') updateCartCount();
                    if (d.cart_empty) location.reload();
                })
                .catch(err => console.error('Remove item failed:', err));
        }

        // Refresh the subtotal, fee, and total in the order summary.
        function refreshTotals() {
            setText('s1-sub', '₱' + fmt(subtotal));
            setText('s1-ship', '₱' + fmt(DELIVERY_FEE));
            setText('s1-total', '₱' + fmt(subtotal + DELIVERY_FEE));
        }

        function setText(id, v) {
            const el = document.getElementById(id);
            if (el) el.textContent = v;
        }

        // Format a number with a thousands comma.
        function fmt(n) {
            return parseFloat(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
    </script>

</body>

</html>