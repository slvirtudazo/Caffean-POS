<?php

/**
 * Purge Coffee Shop — Place Order AJAX Handler (php/place_order.php)
 * Validates inputs, inserts order + items, clears session cart.
 * Returns JSON to cart.php Section 2 → triggers Section 3.
 */
require_once 'db_connection.php';

header('Content-Type: application/json');
$r = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $r['message'] = 'You must be logged in to place an order.';
    echo json_encode($r);
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $r['message'] = 'Invalid request method.';
    echo json_encode($r);
    exit();
}
if (empty($_SESSION['cart'])) {
    $r['message'] = 'Your cart is empty.';
    echo json_encode($r);
    exit();
}

/* ── Sanitise inputs ──────────────────────────────────────────── */
$user_id         = intval($_SESSION['user_id']);
$full_name       = trim($_POST['name']           ?? '');
$email           = trim($_POST['email']          ?? '');
$mobile          = trim($_POST['mobile']         ?? '');
$order_type      = trim($_POST['order_type']     ?? 'delivery');
$payment_method  = trim($_POST['payment_method'] ?? '');
$promo_code      = strtoupper(trim($_POST['promo_code'] ?? ''));

$allowed_payments = ['Cash on Delivery', 'GCash', 'Maya', 'Credit/Debit Card', 'Bank Transfer'];
$allowed_types    = ['delivery', 'pickup'];

/* ── Basic validation ─────────────────────────────────────────── */
if (!$full_name || !$email || !$mobile) {
    $r['message'] = 'Customer information is incomplete.';
    echo json_encode($r);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $r['message'] = 'Invalid email address.';
    echo json_encode($r);
    exit();
}
if (!preg_match('/^09\d{9}$/', $mobile)) {
    $r['message'] = 'Invalid Philippine mobile number.';
    echo json_encode($r);
    exit();
}
if (!in_array($order_type, $allowed_types)) {
    $r['message'] = 'Invalid order type.';
    echo json_encode($r);
    exit();
}
if (!in_array($payment_method, $allowed_payments)) {
    $r['message'] = 'Invalid payment method.';
    echo json_encode($r);
    exit();
}

/* ── Address / Pickup fields ──────────────────────────────────── */
$delivery_address = '';
$house_unit = $street_name = $barangay = $city_municipality = $province = $zip_code = '';
$delivery_notes = $pickup_branch = $pickup_date = $pickup_time = '';

if ($order_type === 'delivery') {
    $house_unit       = trim($_POST['house_unit']       ?? '');
    $street_name      = trim($_POST['street_name']      ?? '');
    $barangay         = trim($_POST['barangay']         ?? '');
    $city_municipality = trim($_POST['city_municipality'] ?? '');
    $province         = trim($_POST['province']         ?? '');
    $zip_code         = trim($_POST['zip_code']         ?? '');
    $delivery_notes   = trim($_POST['delivery_notes']   ?? '');

    if (!$house_unit || !$street_name || !$barangay || !$city_municipality || !$province || !$zip_code) {
        $r['message'] = 'Please complete all delivery address fields.';
        echo json_encode($r);
        exit();
    }
    $delivery_address = "$house_unit, $street_name, $barangay, $city_municipality, $province $zip_code";
} else {
    $pickup_branch = trim($_POST['pickup_branch'] ?? '');
    $pickup_date   = trim($_POST['pickup_date']   ?? '');
    $pickup_time   = trim($_POST['pickup_time']   ?? '');

    if (!$pickup_branch || !$pickup_date || !$pickup_time) {
        $r['message'] = 'Please complete all pickup details.';
        echo json_encode($r);
        exit();
    }
    $delivery_address = "Pickup: $pickup_branch on $pickup_date at $pickup_time";
}

/* ── Fetch & validate products ─────────────────────────────────── */
$ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
$res = mysqli_query(
    $conn,
    "SELECT product_id, name, price FROM products WHERE product_id IN ($ids) AND status = 1"
);
$valid_products = [];
while ($p = mysqli_fetch_assoc($res)) $valid_products[$p['product_id']] = $p;

if (empty($valid_products)) {
    $r['message'] = 'No valid products found in cart.';
    echo json_encode($r);
    exit();
}

$DELIVERY_FEE = $order_type === 'delivery' ? 50.00 : 0.00;
$subtotal     = 0.0;
foreach ($valid_products as $pid => $p) {
    $cart_entry = $_SESSION['cart'][$pid];
    $qty = is_array($cart_entry) ? intval($cart_entry['quantity'] ?? 1) : intval($cart_entry);
    $subtotal += $p['price'] * $qty;
}

/* ── Promo / Discount ─────────────────────────────────────────── */
$discount_amount = 0.0;
if ($promo_code) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT discount_type, discount_value, min_order_amount
         FROM promo_codes
         WHERE code = ? AND is_active = 1
           AND (expires_at IS NULL OR expires_at > NOW())"
    );
    mysqli_stmt_bind_param($stmt, 's', $promo_code);
    mysqli_stmt_execute($stmt);
    $promo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($promo && $subtotal >= $promo['min_order_amount']) {
        if ($promo['discount_type'] === 'percentage') {
            $discount_amount = round($subtotal * ($promo['discount_value'] / 100), 2);
        } else {
            $discount_amount = min((float)$promo['discount_value'], $subtotal);
        }
    }
}

$total = round($subtotal + $DELIVERY_FEE - $discount_amount, 2);

/* ── Database transaction ─────────────────────────────────────── */
mysqli_begin_transaction($conn);
try {
    /* 1. Insert order */
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO orders
            (user_id, total_amount, status, payment_method, delivery_address,
             mobile_number, order_type,
             house_unit, street_name, barangay, city_municipality, province, zip_code,
             delivery_notes, pickup_branch, pickup_date, pickup_time,
             promo_code, discount_amount)
         VALUES (?,?,'pending',?,?, ?,?, ?,?,?,?,?,?, ?,?,?,?, ?,?)"
    );

    $pickup_date_val = $pickup_date ?: null;
    $pickup_time_val = $pickup_time ?: null;

    mysqli_stmt_bind_param(
        $stmt,
        'idsssssssssssssssd',
        $user_id,
        $total,
        $payment_method,
        $delivery_address,
        $mobile,
        $order_type,
        $house_unit,
        $street_name,
        $barangay,
        $city_municipality,
        $province,
        $zip_code,
        $delivery_notes,
        $pickup_branch,
        $pickup_date_val,
        $pickup_time_val,
        $promo_code,
        $discount_amount
    );
    mysqli_stmt_execute($stmt);
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    /* 2. Insert order items */
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO order_items (order_id, product_id, quantity, price_at_time)
         VALUES (?,?,?,?)"
    );

    $items_response = [];
    foreach ($valid_products as $pid => $p) {
        $opts  = $_SESSION['cart'][$pid];
        $qty   = is_array($opts) ? intval($opts['quantity'] ?? 1) : intval($opts);
        $price = (float)$p['price'];

        mysqli_stmt_bind_param($stmt, 'iiid', $order_id, $pid, $qty, $price);
        mysqli_stmt_execute($stmt);

        $items_response[] = [
            'name'     => $p['name'],
            'quantity' => $qty,
            'subtotal' => round($price * $qty, 2)
        ];
    }
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    /* 3. Clear cart */
    $_SESSION['cart'] = [];

    /* 4. Respond */
    $r = [
        'success'        => true,
        'order_id'       => $order_id,
        'order_date'     => date('F d, Y · g:i A'),
        'customer_name'  => $full_name,
        'payment_method' => $payment_method,
        'order_type'     => $order_type,
        'total'          => $total,
        'items'          => $items_response
    ];
} catch (\Throwable $e) {
    mysqli_rollback($conn);
    $r['message'] = 'Order could not be processed. Please try again.';
}

echo json_encode($r);
