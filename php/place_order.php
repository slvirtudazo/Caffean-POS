<?php

/**
 * Purge Coffee Shop — Place Order Handler (php/place_order.php)
 * Validates checkout form, inserts order + order items, clears cart.
 */

require_once 'db_connection.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Must have items in cart
if (empty($_SESSION['cart'])) {
    header("Location: ../cart.php");
    exit();
}

// Must come via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../checkout.php");
    exit();
}

// ── Validate inputs ───────────────────────────────────────────
$delivery_address = trim($_POST['delivery_address'] ?? '');
$payment_method   = trim($_POST['payment_method']   ?? '');
$allowed_payments = ['Cash on Delivery', 'GCash', 'Maya', 'Bank Transfer'];

if ($delivery_address === '' || $payment_method === '' || !in_array($payment_method, $allowed_payments)) {
    $_SESSION['checkout_error'] = 'Please fill in all required fields correctly.';
    header("Location: ../checkout.php");
    exit();
}

$user_id  = intval($_SESSION['user_id']);
$shipping = 50.00;
$subtotal = 0.00;

// ── Fetch & validate products ─────────────────────────────────
$ids_str = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
$res     = mysqli_query($conn, "SELECT product_id, price FROM products WHERE product_id IN ($ids_str) AND status = 1");

$valid_products = [];
while ($p = mysqli_fetch_assoc($res)) {
    $valid_products[$p['product_id']] = $p['price'];
}

if (empty($valid_products)) {
    $_SESSION['checkout_error'] = 'No valid products found. Please try again.';
    header("Location: ../checkout.php");
    exit();
}

foreach ($valid_products as $pid => $price) {
    $qty      = intval($_SESSION['cart'][$pid]);
    $subtotal += $price * $qty;
}

$total = $subtotal + $shipping;

// ── Begin transaction ─────────────────────────────────────────
mysqli_begin_transaction($conn);

try {
    // 1. Insert order
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO orders (user_id, total_amount, status, payment_method, delivery_address)
         VALUES (?, ?, 'pending', ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, "idss", $user_id, $total, $payment_method, $delivery_address);
    mysqli_stmt_execute($stmt);
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // 2. Insert order items
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO order_items (order_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)"
    );
    foreach ($valid_products as $pid => $price) {
        $qty = intval($_SESSION['cart'][$pid]);
        mysqli_stmt_bind_param($stmt, "iiid", $order_id, $pid, $qty, $price);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);

    // 3. Commit
    mysqli_commit($conn);

    // 4. Clear cart
    $_SESSION['cart'] = [];

    // 5. Redirect to success page with order ID
    $_SESSION['order_success'] = $order_id;
    header("Location: ../order_success.php");
    exit();
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['checkout_error'] = 'Something went wrong. Please try again.';
    header("Location: ../checkout.php");
    exit();
}
