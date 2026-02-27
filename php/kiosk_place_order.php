<?php

/**
 * Purge Coffee Shop — Kiosk Order Handler (php/kiosk_place_order.php)
 * Places walk-in kiosk orders with no logged-in user (user_id = NULL).
 * Auto-applies the kiosk schema migration if not yet run.
 */

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once 'db_connection.php';

header('Content-Type: application/json');

/* ── Auto-apply kiosk migration if needed ────────────────────── */
function kioskMigrate($conn)
{
    /* Make user_id nullable if still NOT NULL */
    $col = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT IS_NULLABLE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = 'orders'
           AND COLUMN_NAME  = 'user_id'"
    ));
    if ($col && $col['IS_NULLABLE'] === 'NO') {
        mysqli_query($conn, "ALTER TABLE `orders` DROP FOREIGN KEY `orders_ibfk_1`");
        mysqli_query($conn, "ALTER TABLE `orders` MODIFY `user_id` int(11) DEFAULT NULL");
        mysqli_query($conn,
            "ALTER TABLE `orders` ADD CONSTRAINT `orders_ibfk_1`
             FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL"
        );
    }

    /* Add kiosk columns if missing */
    $existing = [];
    $res = mysqli_query($conn,
        "SELECT COLUMN_NAME FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders'"
    );
    while ($r = mysqli_fetch_assoc($res)) $existing[] = $r['COLUMN_NAME'];

    if (!in_array('is_kiosk', $existing))
        mysqli_query($conn, "ALTER TABLE `orders` ADD COLUMN `is_kiosk` tinyint(1) NOT NULL DEFAULT 0");

    if (!in_array('kiosk_order_type', $existing))
        mysqli_query($conn, "ALTER TABLE `orders` ADD COLUMN `kiosk_order_type` enum('dine_in','take_out') DEFAULT NULL");

    if (!in_array('customer_name', $existing))
        mysqli_query($conn, "ALTER TABLE `orders` ADD COLUMN `customer_name` varchar(100) DEFAULT NULL");

    /* Index for kiosk queue display — skip if already exists */
    $idx = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT INDEX_NAME FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = 'orders'
           AND INDEX_NAME   = 'idx_kiosk'"
    ));
    if (!$idx)
        mysqli_query($conn, "ALTER TABLE `orders` ADD INDEX `idx_kiosk` (`is_kiosk`,`status`,`order_date`)");
}

kioskMigrate($conn);

/* ── Must be POST ────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}

/* ── Sanitize inputs ─────────────────────────────────────────── */
$kiosk_order_type = trim($_POST['kiosk_order_type'] ?? 'dine_in');
$payment_method   = trim($_POST['payment_method']   ?? '');
$customer_name    = trim($_POST['customer_name']    ?? 'Guest');
$mobile           = trim($_POST['mobile']           ?? '');
$cart_json        = trim($_POST['cart']             ?? '{}');

$allowed_types    = ['dine_in', 'take_out'];
$allowed_payments = ['Cash', 'Credit/Debit Card', 'Tap-to-Pay (GCash)', 'Tap-to-Pay (Maya)'];

/* ── Validate inputs ─────────────────────────────────────────── */
if (!in_array($kiosk_order_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid order type.']);
    exit();
}
if (!in_array($payment_method, $allowed_payments)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method.']);
    exit();
}
if ($mobile && !preg_match('/^09\d{9}$/', $mobile)) {
    echo json_encode(['success' => false, 'message' => 'Invalid mobile number.']);
    exit();
}

/* ── Parse cart JSON ─────────────────────────────────────────── */
$cart = json_decode($cart_json, true);
if (empty($cart) || !is_array($cart)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
    exit();
}

/* ── Validate products from DB ───────────────────────────────── */
$ids   = implode(',', array_map('intval', array_keys($cart)));
$res   = mysqli_query($conn, "SELECT product_id, name, price FROM products WHERE product_id IN ($ids) AND status = 1");
$prods = [];
while ($p = mysqli_fetch_assoc($res)) $prods[$p['product_id']] = $p;

if (empty($prods)) {
    echo json_encode(['success' => false, 'message' => 'No valid products found.']);
    exit();
}

/* ── Calculate total ─────────────────────────────────────────── */
$total = 0.0;
foreach ($prods as $pid => $p) {
    $qty    = intval($cart[$pid]['qty'] ?? 1);
    $total += $p['price'] * $qty;
}
$total = round($total, 2);

/* ── DB transaction ──────────────────────────────────────────── */
mysqli_begin_transaction($conn);
try {
    $delivery_address = $kiosk_order_type === 'dine_in' ? 'Dine In' : 'Take Out';
    $order_type_db    = 'pickup';

    /* Insert order — user_id is NULL for kiosk guests */
    $stmt = mysqli_prepare($conn,
        "INSERT INTO orders
            (user_id, total_amount, status, payment_method, delivery_address,
             mobile_number, order_type, is_kiosk, kiosk_order_type, customer_name)
         VALUES (NULL, ?, 'pending', ?, ?, ?, ?, 1, ?, ?)"
    );
    mysqli_stmt_bind_param($stmt, 'dssssss',
        $total,
        $payment_method,
        $delivery_address,
        $mobile,
        $order_type_db,
        $kiosk_order_type,
        $customer_name
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new \RuntimeException(mysqli_stmt_error($stmt));
    }

    $order_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    /* Insert order items */
    $stmt_items = mysqli_prepare($conn,
        "INSERT INTO order_items
            (order_id, product_id, quantity, price_at_time, size, temperature, sugar_level, milk_type, special_instructions)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $items_response = [];
    foreach ($prods as $pid => $p) {
        $opts  = $cart[$pid];
        $qty   = intval($opts['qty']   ?? 1);
        $price = (float)$p['price'];
        $size  = $opts['size']  ?? 'Short';
        $temp  = $opts['temp']  ?? 'Hot';
        $sugar = $opts['sugar'] ?? '0%';
        $milk  = $opts['milk']  ?? 'Whole';
        $notes = $opts['notes'] ?? '';

        mysqli_stmt_bind_param($stmt_items, 'iiidsssss',
            $order_id, $pid, $qty, $price, $size, $temp, $sugar, $milk, $notes
        );

        if (!mysqli_stmt_execute($stmt_items)) {
            throw new \RuntimeException(mysqli_stmt_error($stmt_items));
        }

        $items_response[] = [
            'name'     => $p['name'],
            'qty'      => $qty,
            'subtotal' => round($price * $qty, 2)
        ];
    }
    mysqli_stmt_close($stmt_items);
    mysqli_commit($conn);

    echo json_encode([
        'success'          => true,
        'order_id'         => $order_id,
        'order_date'       => date('F d, Y · g:i A'),
        'customer_name'    => $customer_name,
        'payment_method'   => $payment_method,
        'kiosk_order_type' => $kiosk_order_type,
        'total'            => $total,
        'items'            => $items_response
    ]);

} catch (\Throwable $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Order could not be placed: ' . $e->getMessage()]);
}

ob_end_flush();