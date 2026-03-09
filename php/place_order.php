<?php

/**
 * Caffean Shop — Place Order (php/place_order.php)
 * Validates inputs, inserts order with full item customizations, clears the session cart.
 * Returns JSON consumed by checkout.php to render the receipt dialog on success.
 *
 * ROOT CAUSE FIX: mysqli_stmt_bind_param type string was 18 chars for 17 parameters,
 * throwing a PHP 8.x ValueError caught silently by \Throwable on every attempt.
 */

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once 'db_connection.php';

header('Content-Type: application/json');

// Disable mysqli auto-throwing (PHP 8.1+ default); all errors handled manually below
mysqli_report(MYSQLI_REPORT_OFF);

$r = ['success' => false, 'message' => ''];

// Auth and method guards
if (!isset($_SESSION['user_id'])) {
    $r['message'] = 'Please log in to place an order.';
    ob_clean(); echo json_encode($r); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $r['message'] = 'Invalid request method.';
    ob_clean(); echo json_encode($r); exit();
}
if (empty($_SESSION['cart'])) {
    $r['message'] = 'Your cart is empty.';
    ob_clean(); echo json_encode($r); exit();
}

// Sanitize customer inputs
$user_id        = intval($_SESSION['user_id']);
$full_name      = trim($_POST['name']           ?? '');
$email          = trim($_POST['email']          ?? '');
$mobile         = trim($_POST['mobile']         ?? '');
$order_type     = trim($_POST['order_type']     ?? 'delivery');
$payment_method = trim($_POST['payment_method'] ?? '');

$allowed_payments = ['Cash on Delivery', 'GCash', 'Maya', 'Credit/Debit Card', 'GoTyme'];
$allowed_types    = ['delivery', 'pickup'];

// Validate required customer fields
if (!$full_name || !$email || !$mobile) {
    $r['message'] = 'Customer information is incomplete.';
    ob_clean(); echo json_encode($r); exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $r['message'] = 'Invalid email address.';
    ob_clean(); echo json_encode($r); exit();
}
if (!preg_match('/^09\d{9}$/', $mobile)) {
    $r['message'] = 'Invalid Philippine mobile number.';
    ob_clean(); echo json_encode($r); exit();
}
if (!in_array($order_type, $allowed_types)) {
    $r['message'] = 'Invalid order type.';
    ob_clean(); echo json_encode($r); exit();
}
if (!in_array($payment_method, $allowed_payments)) {
    $r['message'] = 'Invalid payment method.';
    ob_clean(); echo json_encode($r); exit();
}

// Build delivery or pickup fields
$delivery_address  = '';
$house_unit        = '';
$street_name       = '';
$barangay          = '';
$city_municipality = '';
$province          = '';
$zip_code          = '';
$delivery_notes    = '';
$pickup_branch     = '';
$pickup_date_val   = null;
$pickup_time_val   = null;

if ($order_type === 'delivery') {
    $house_unit        = trim($_POST['house_unit']        ?? '');
    $street_name       = trim($_POST['street_name']       ?? '');
    $barangay          = trim($_POST['barangay']          ?? '');
    $city_municipality = trim($_POST['city_municipality'] ?? '');
    $province          = trim($_POST['province']          ?? '');
    $zip_code          = trim($_POST['zip_code']          ?? '');
    $delivery_notes    = trim($_POST['delivery_notes']    ?? '');

    if (!$house_unit || !$street_name || !$barangay || !$city_municipality || !$province || !$zip_code) {
        $r['message'] = 'Please complete all delivery address fields.';
        ob_clean(); echo json_encode($r); exit();
    }
    $delivery_address = "$house_unit, $street_name, $barangay, $city_municipality, $province $zip_code";
} else {
    $pickup_branch   = trim($_POST['pickup_branch'] ?? '');
    $pickup_date_val = trim($_POST['pickup_date']   ?? '') ?: null;
    $pickup_time_val = trim($_POST['pickup_time']   ?? '') ?: null;

    if (!$pickup_branch || !$pickup_date_val || !$pickup_time_val) {
        $r['message'] = 'Please complete all pickup details.';
        ob_clean(); echo json_encode($r); exit();
    }
    $delivery_address = "Pickup: $pickup_branch on $pickup_date_val at $pickup_time_val";
}

// Validate cart products against the DB (active products only)
$ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
$res = mysqli_query($conn,
    "SELECT product_id, name, price FROM products WHERE product_id IN ($ids) AND status = 1"
);
if (!$res) {
    $r['message'] = 'Could not verify cart products. Please try again.';
    ob_clean(); echo json_encode($r); exit();
}

$valid_products = [];
while ($p = mysqli_fetch_assoc($res)) {
    $valid_products[$p['product_id']] = $p;
}

if (empty($valid_products)) {
    $r['message'] = 'No valid products found in cart.';
    ob_clean(); echo json_encode($r); exit();
}

// Calculate verified total from DB prices
$DELIVERY_FEE = ($order_type === 'delivery') ? 50.00 : 0.00;
$subtotal     = 0.0;
foreach ($valid_products as $pid => $p) {
    $entry     = $_SESSION['cart'][$pid];
    $qty       = is_array($entry) ? intval($entry['quantity'] ?? 1) : intval($entry);
    $subtotal += (float)$p['price'] * $qty;
}
$total = round($subtotal + $DELIVERY_FEE, 2);

// Begin transaction — all inserts are atomic
mysqli_begin_transaction($conn);
try {
    // Generate next sequential order number using the established PC- series
    $year     = date('Y');
    $last_row = mysqli_fetch_assoc(
        mysqli_query($conn,
            "SELECT order_number FROM orders
             WHERE order_number LIKE 'PC-{$year}-%'
             ORDER BY order_id DESC LIMIT 1"
        )
    );
    if ($last_row) {
        $parts    = explode('-', $last_row['order_number']);
        $last_seq = intval(end($parts));
    } else {
        $last_seq = 0;
    }
    $order_number = 'PC-' . $year . '-' . str_pad($last_seq + 1, 5, '0', STR_PAD_LEFT);

    // Insert the main order record
    // FIXED: type string is 17 chars matching exactly 17 placeholders and 17 variables.
    // Bug was 'sidsssssssssssssss' (18 chars) — one extra 's' threw a PHP 8.x ValueError.
    $stmt = mysqli_prepare($conn,
        "INSERT INTO orders
            (order_number, user_id, total_amount, status, payment_method, delivery_address,
             mobile_number, order_type,
             house_unit, street_name, barangay, city_municipality, province, zip_code,
             delivery_notes, pickup_branch, pickup_date, pickup_time)
         VALUES (?,?,?,'pending',?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    if (!$stmt) {
        throw new \RuntimeException('Order prepare failed: ' . mysqli_error($conn));
    }

    // 17 type chars (s=1, i=2, d=3, s×14=4-17) matching 17 placeholders and 17 variables
    mysqli_stmt_bind_param($stmt, 'sidssssssssssssss',
        $order_number,     // s  1
        $user_id,          // i  2
        $total,            // d  3
        $payment_method,   // s  4
        $delivery_address, // s  5
        $mobile,           // s  6
        $order_type,       // s  7
        $house_unit,       // s  8
        $street_name,      // s  9
        $barangay,         // s 10
        $city_municipality,// s 11
        $province,         // s 12
        $zip_code,         // s 13
        $delivery_notes,   // s 14
        $pickup_branch,    // s 15
        $pickup_date_val,  // s 16
        $pickup_time_val   // s 17
    );
    if (!mysqli_stmt_execute($stmt)) {
        throw new \RuntimeException('Order insert failed: ' . mysqli_stmt_error($stmt));
    }
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    if (!$order_id) {
        throw new \RuntimeException('No order_id returned after insert.');
    }

    // Insert each cart item with full customization options
    // Type string 'iiidssssss' = 10 chars matching 10 placeholders and 10 variables
    $stmt_items = mysqli_prepare($conn,
        "INSERT INTO order_items
            (order_id, product_id, quantity, price_at_time,
             size, temperature, sugar_level, milk_type, addons, special_instructions)
         VALUES (?,?,?,?,?,?,?,?,?,?)"
    );
    if (!$stmt_items) {
        throw new \RuntimeException('Items prepare failed: ' . mysqli_error($conn));
    }

    $items_response = [];
    foreach ($valid_products as $pid => $p) {
        $opts   = $_SESSION['cart'][$pid];
        $qty    = is_array($opts) ? intval($opts['quantity']  ?? 1) : intval($opts);
        $price  = (float)$p['price'];
        $size   = is_array($opts) ? ($opts['size']                 ?? 'Short') : 'Short';
        $temp   = is_array($opts) ? ($opts['temperature']          ?? 'Hot')   : 'Hot';
        $sugar  = is_array($opts) ? ($opts['sugar_level']          ?? '0%')    : '0%';
        $milk   = is_array($opts) ? ($opts['milk']                 ?? 'Whole') : 'Whole';
        $addons = is_array($opts) ? implode(', ', (array)($opts['addons'] ?? [])) : '';
        $notes  = is_array($opts) ? ($opts['special_instructions'] ?? '')      : '';

        mysqli_stmt_bind_param($stmt_items, 'iiidssssss',
            $order_id, // i 1
            $pid,      // i 2
            $qty,      // i 3
            $price,    // d 4
            $size,     // s 5
            $temp,     // s 6
            $sugar,    // s 7
            $milk,     // s 8
            $addons,   // s 9
            $notes     // s 10
        );
        if (!mysqli_stmt_execute($stmt_items)) {
            throw new \RuntimeException('Item insert failed [pid=' . $pid . ']: ' . mysqli_stmt_error($stmt_items));
        }

        $items_response[] = [
            'name'     => $p['name'],
            'quantity' => $qty,
            'subtotal' => round($price * $qty, 2),
        ];
    }
    mysqli_stmt_close($stmt_items);

    mysqli_commit($conn);

    // Clear the session cart after a successful commit
    $_SESSION['cart'] = [];

    ob_clean();
    echo json_encode([
        'success'        => true,
        'order_id'       => $order_id,
        'order_number'   => $order_number,
        'order_date'     => date('F d, Y · g:i A'),
        'customer_name'  => $full_name,
        'payment_method' => $payment_method,
        'order_type'     => $order_type,
        'total'          => $total,
        'items'          => $items_response,
    ]);

} catch (\Throwable $e) {
    mysqli_rollback($conn);
    error_log('[place_order] ' . $e->getMessage() . ' | user_id=' . $user_id);
    $r['message'] = 'Order could not be processed. Please try again.';
    ob_clean();
    echo json_encode($r);
}

ob_end_flush();