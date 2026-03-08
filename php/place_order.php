<?php

/**
 * Purge Coffee Shop — Place Order
 * Validates inputs, inserts order + full item customizations, clears session cart.
 * Returns JSON consumed by cart.php to show the order success screen.
 */

require_once 'db_connection.php';

header('Content-Type: application/json');

$r = ['success' => false, 'message' => ''];

// Auth and method guards
if (!isset($_SESSION['user_id'])) {
    $r['message'] = 'Please log in to manage your cart, favorites, and orders.';
    echo json_encode($r); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $r['message'] = 'Invalid request method.';
    echo json_encode($r); exit();
}
if (empty($_SESSION['cart'])) {
    $r['message'] = 'Your cart is empty.';
    echo json_encode($r); exit();
}

// Sanitize inputs
$user_id        = intval($_SESSION['user_id']);
$full_name      = trim($_POST['name']           ?? '');
$email          = trim($_POST['email']          ?? '');
$mobile         = trim($_POST['mobile']         ?? '');
$order_type     = trim($_POST['order_type']     ?? 'delivery');
$payment_method = trim($_POST['payment_method'] ?? '');

$allowed_payments = ['Cash on Delivery', 'GCash', 'Maya', 'Credit/Debit Card', 'GoTyme'];
$allowed_types    = ['delivery', 'pickup'];

// Validate required fields
if (!$full_name || !$email || !$mobile) {
    $r['message'] = 'Customer information is incomplete.';
    echo json_encode($r); exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $r['message'] = 'Invalid email address.';
    echo json_encode($r); exit();
}
if (!preg_match('/^09\d{9}$/', $mobile)) {
    $r['message'] = 'Invalid Philippine mobile number.';
    echo json_encode($r); exit();
}
if (!in_array($order_type, $allowed_types)) {
    $r['message'] = 'Invalid order type.';
    echo json_encode($r); exit();
}
if (!in_array($payment_method, $allowed_payments)) {
    $r['message'] = 'Invalid payment method.';
    echo json_encode($r); exit();
}

// Build delivery or pickup fields
$delivery_address = '';
$house_unit = $street_name = $barangay = $city_municipality = $province = $zip_code = '';
$delivery_notes = $pickup_branch = '';
$pickup_date_val = $pickup_time_val = null;

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
        echo json_encode($r); exit();
    }
    $delivery_address = "$house_unit, $street_name, $barangay, $city_municipality, $province $zip_code";
} else {
    $pickup_branch    = trim($_POST['pickup_branch'] ?? '');
    $pickup_date_val  = trim($_POST['pickup_date']   ?? '') ?: null;
    $pickup_time_val  = trim($_POST['pickup_time']   ?? '') ?: null;

    if (!$pickup_branch || !$pickup_date_val || !$pickup_time_val) {
        $r['message'] = 'Please complete all pickup details.';
        echo json_encode($r); exit();
    }
    $delivery_address = "Pickup: $pickup_branch on $pickup_date_val at $pickup_time_val";
}

// Validate cart products against DB (status = 1 only)
$ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
$res = mysqli_query($conn, "SELECT product_id, name, price FROM products WHERE product_id IN ($ids) AND status = 1");
$valid_products = [];
while ($p = mysqli_fetch_assoc($res)) $valid_products[$p['product_id']] = $p;

if (empty($valid_products)) {
    $r['message'] = 'No valid products found in cart.';
    echo json_encode($r); exit();
}

// Calculate subtotal and total from DB prices
$DELIVERY_FEE = ($order_type === 'delivery') ? 50.00 : 0.00;
$subtotal = 0.0;
foreach ($valid_products as $pid => $p) {
    $entry = $_SESSION['cart'][$pid];
    $qty   = is_array($entry) ? intval($entry['quantity'] ?? 1) : intval($entry);
    $subtotal += $p['price'] * $qty;
}

$total = round($subtotal + $DELIVERY_FEE, 2);

// Generate unique online order number: ON-YYYY-NNN
$year         = date('Y');
$last_row     = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT order_number FROM orders WHERE order_number LIKE 'ON-{$year}-%' ORDER BY order_id DESC LIMIT 1"
));
$last_seq     = $last_row ? intval(substr($last_row['order_number'], -3)) : 0;
$order_number = 'ON-' . $year . '-' . str_pad($last_seq + 1, 3, '0', STR_PAD_LEFT);

// Run DB transaction
mysqli_begin_transaction($conn);
try {
    // Insert order
    $stmt = mysqli_prepare($conn,
        "INSERT INTO orders
            (order_number, user_id, total_amount, status, payment_method, delivery_address,
             mobile_number, order_type,
             house_unit, street_name, barangay, city_municipality, province, zip_code,
             delivery_notes, pickup_branch, pickup_date, pickup_time)
         VALUES (?,?,?,'pending',?,?, ?,?, ?,?,?,?,?,?, ?,?,?,?)"
    );
    mysqli_stmt_bind_param($stmt, 'sidsssssssssssssss',
        $order_number, $user_id, $total,
        $payment_method, $delivery_address,
        $mobile, $order_type,
        $house_unit, $street_name, $barangay, $city_municipality, $province, $zip_code,
        $delivery_notes, $pickup_branch, $pickup_date_val, $pickup_time_val
    );
    mysqli_stmt_execute($stmt);
    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    // Insert order items with full customization
    $stmt = mysqli_prepare($conn,
        "INSERT INTO order_items
            (order_id, product_id, quantity, price_at_time,
             size, temperature, sugar_level, milk_type, addons, special_instructions)
         VALUES (?,?,?,?, ?,?,?,?,?,?)"
    );

    $items_response = [];
    foreach ($valid_products as $pid => $p) {
        $opts  = $_SESSION['cart'][$pid];
        $qty   = is_array($opts) ? intval($opts['quantity'] ?? 1) : intval($opts);
        $price = (float)$p['price'];

        // Extract customization options
        $size     = is_array($opts) ? ($opts['size']                 ?? null) : null;
        $temp     = is_array($opts) ? ($opts['temperature']          ?? null) : null;
        $sugar    = is_array($opts) ? ($opts['sugar_level']          ?? null) : null;
        $milk     = is_array($opts) ? ($opts['milk']                 ?? null) : null;
        $addons   = is_array($opts) ? implode(', ', (array)($opts['addons'] ?? [])) : null;
        $notes    = is_array($opts) ? ($opts['special_instructions'] ?? null) : null;

        mysqli_stmt_bind_param($stmt, 'iiidssssss',
            $order_id, $pid, $qty, $price,
            $size, $temp, $sugar, $milk, $addons, $notes
        );
        mysqli_stmt_execute($stmt);

        $items_response[] = [
            'name'     => $p['name'],
            'quantity' => $qty,
            'subtotal' => round($price * $qty, 2),
        ];
    }
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    // Clear cart after successful order
    $_SESSION['cart'] = [];

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
    $r['message'] = 'Order could not be processed. Please try again.';
    echo json_encode($r);
}