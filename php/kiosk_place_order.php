<?php

/**
 * Purge Coffee Shop — Kiosk Order Handler (php/kiosk_place_order.php)
 * Places walk-in kiosk orders. user_id = NULL for guest walk-ins.
 */

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once 'db_connection.php';

header('Content-Type: application/json');

// Must be a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

// Sanitize inputs
$kiosk_order_type = trim($_POST['kiosk_order_type'] ?? 'dine_in');
$payment_method   = trim($_POST['payment_method']   ?? '');
$customer_name    = trim($_POST['customer_name']    ?? 'Guest');
$mobile           = trim($_POST['mobile']           ?? '');
$cart_json        = trim($_POST['cart']             ?? '{}');

$allowed_types    = ['dine_in', 'take_out'];
$allowed_payments = ['Cash', 'Credit/Debit Card', 'Tap-to-Pay (GCash)', 'Tap-to-Pay (Maya)'];

// Validate inputs
if (!in_array($kiosk_order_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order type.']); exit();
}
if (!in_array($payment_method, $allowed_payments)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method.']); exit();
}
if ($mobile && !preg_match('/^09\d{9}$/', $mobile)) {
    echo json_encode(['success' => false, 'message' => 'Invalid mobile number.']); exit();
}

// Parse and validate cart JSON
$cart = json_decode($cart_json, true);
if (empty($cart) || !is_array($cart)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']); exit();
}

// Validate products from DB (active only)
$ids   = implode(',', array_map('intval', array_keys($cart)));
$res   = mysqli_query($conn, "SELECT product_id, name, price FROM products WHERE product_id IN ($ids) AND status = 1");
$prods = [];
while ($p = mysqli_fetch_assoc($res)) $prods[$p['product_id']] = $p;

if (empty($prods)) {
    echo json_encode(['success' => false, 'message' => 'No valid products found.']); exit();
}

// Calculate total from DB prices
$total = 0.0;
foreach ($prods as $pid => $p) {
    $qty    = intval($cart[$pid]['qty'] ?? 1);
    $total += $p['price'] * $qty;
}
$total = round($total, 2);

// Generate unique order number: PC-YYYY-NNNNN
$year     = date('Y');
$last_row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT order_number FROM orders WHERE order_number LIKE 'PC-{$year}-%' ORDER BY order_id DESC LIMIT 1"
));
$last_seq     = $last_row ? intval(substr($last_row['order_number'], -5)) : 0;
$order_number = 'PC-' . $year . '-' . str_pad($last_seq + 1, 5, '0', STR_PAD_LEFT);

// DB transaction
mysqli_begin_transaction($conn);
try {
    $delivery_address = ($kiosk_order_type === 'dine_in') ? 'Dine In' : 'Take Out';

    // Insert order — user_id is NULL for walk-in guests
    $stmt = mysqli_prepare($conn,
        "INSERT INTO orders
            (order_number, user_id, total_amount, status, payment_method, delivery_address,
             mobile_number, order_type, is_kiosk, kiosk_order_type, customer_name)
         VALUES (?, NULL, ?, 'pending', ?, ?, ?, 'pickup', 1, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'sdssss',
        $order_number, $total,
        $payment_method, $delivery_address,
        $mobile, $kiosk_order_type, $customer_name
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new \RuntimeException(mysqli_stmt_error($stmt));
    }
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Insert order items with customization options
    $stmt_items = mysqli_prepare($conn,
        "INSERT INTO order_items
            (order_id, product_id, quantity, price_at_time,
             size, temperature, sugar_level, milk_type, special_instructions)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $items_response = [];
    foreach ($prods as $pid => $p) {
        $opts  = $cart[$pid];
        $qty   = intval($opts['qty']   ?? 1);
        $price = (float)$p['price'];
        $size  = $opts['size']  ?? null;
        $temp  = $opts['temp']  ?? null;
        $sugar = $opts['sugar'] ?? null;
        $milk  = $opts['milk']  ?? null;
        $notes = $opts['notes'] ?? null;

        mysqli_stmt_bind_param($stmt_items, 'iiidsssss',
            $order_id, $pid, $qty, $price, $size, $temp, $sugar, $milk, $notes
        );
        if (!mysqli_stmt_execute($stmt_items)) {
            throw new \RuntimeException(mysqli_stmt_error($stmt_items));
        }

        $items_response[] = [
            'name'     => $p['name'],
            'qty'      => $qty,
            'subtotal' => round($price * $qty, 2),
        ];
    }
    mysqli_stmt_close($stmt_items);
    mysqli_commit($conn);

    echo json_encode([
        'success'          => true,
        'order_id'         => $order_id,
        'order_number'     => $order_number,
        'order_date'       => date('F d, Y · g:i A'),
        'customer_name'    => $customer_name,
        'payment_method'   => $payment_method,
        'kiosk_order_type' => $kiosk_order_type,
        'total'            => $total,
        'items'            => $items_response,
    ]);

} catch (\Throwable $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Order could not be placed. Please try again.']);
}

ob_end_flush();