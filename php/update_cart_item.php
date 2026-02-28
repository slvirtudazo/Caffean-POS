<?php

/**
 * Purge Coffee Shop — Update Cart Item AJAX Handler (php/update_cart_item.php)
 * Actions: update_qty | update_option | update_addons | remove
 */
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once 'db_connection.php';

// Load cart sync utility if available
if (file_exists(__DIR__ . '/sync_cart.php')) {
    require_once 'sync_cart.php';
}

ob_clean();
header('Content-Type: application/json');
$r = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($r);
    exit();
}

/* ── Ensure cart exists and is normalised ─────────────────── */
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
foreach ($_SESSION['cart'] as $pid => &$v) {
    if (!is_array($v)) {
        $v = [
            'quantity'             => (int)$v,
            'size'                 => 'Short',  // first dropdown option
            'temperature'          => 'Hot',    // first dropdown option
            'sugar_level'          => '0%',     // first dropdown option
            'milk'                 => 'Whole',  // first dropdown option
            'addons'               => [],
            'special_instructions' => ''
        ];
    }
}
unset($v);

$action = trim($_POST['action'] ?? '');
$pid    = intval($_POST['product_id'] ?? 0);

if (!$pid) {
    $r['message'] = 'Invalid product.';
    echo json_encode($r);
    exit();
}

/* ── Execute action ───────────────────────────────────────── */
switch ($action) {

    case 'update_qty':
        /* FIX #2: clamp at minimum 1 — never removes via qty update */
        $qty = max(1, intval($_POST['quantity'] ?? 1));
        if (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['quantity'] = $qty;
        } else {
            $_SESSION['cart'][$pid] = [
                'quantity'             => $qty,
                'size'                 => 'Short',  // first dropdown option
                'temperature'          => 'Hot',    // first dropdown option
                'sugar_level'          => '0%',     // first dropdown option
                'milk'                 => 'Whole',  // first dropdown option
                'addons'               => [],
                'special_instructions' => ''
            ];
        }
        break;

    case 'update_option':
        $field   = $_POST['field'] ?? '';
        $value   = trim($_POST['value'] ?? '');
        $allowed = ['size', 'temperature', 'sugar_level', 'milk', 'special_instructions'];
        if (isset($_SESSION['cart'][$pid]) && in_array($field, $allowed)) {
            $_SESSION['cart'][$pid][$field] = $value;
        }
        break;

    case 'update_addons':
        if (isset($_SESSION['cart'][$pid])) {
            $raw    = $_POST['addons'] ?? [];
            $valid  = ['Extra Espresso Shot', 'Vanilla Syrup', 'Whipped Cream', 'Coffee Jelly', 'Pearl (Boba)'];
            $addons = array_filter((array)$raw, fn($a) => in_array($a, $valid));
            $_SESSION['cart'][$pid]['addons'] = array_values($addons);
        }
        break;

    case 'remove':
        unset($_SESSION['cart'][$pid]);
        break;

    default:
        $r['message'] = 'Unknown action.';
        echo json_encode($r);
        exit();
}

/* ── Recalculate subtotal ─────────────────────────────────── */
$subtotal = 0.0;
$prices   = [];

if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $res = mysqli_query(
        $conn,
        "SELECT product_id, price FROM products WHERE product_id IN ($ids) AND status = 1"
    );
    while ($row = mysqli_fetch_assoc($res)) $prices[$row['product_id']] = (float)$row['price'];
    foreach ($_SESSION['cart'] as $p => $opts) {
        $subtotal += ($prices[$p] ?? 0) * $opts['quantity'];
    }
}

// Sync updated cart to DB for logged-in users
if (isset($_SESSION['user_id']) && function_exists('saveCartToDb')) {
    saveCartToDb($conn, $_SESSION['user_id']);
}

$cartCount = array_sum(array_column(array_values($_SESSION['cart'] ?: []), 'quantity'));

/* ── Build response ───────────────────────────────────────── */
$r['success']    = true;
$r['subtotal']   = round($subtotal, 2);
$r['cart_count'] = $cartCount;
$r['cart_empty'] = empty($_SESSION['cart']);

if ($action !== 'remove' && isset($_SESSION['cart'][$pid]) && isset($prices[$pid])) {
    $opts            = $_SESSION['cart'][$pid];
    $r['item_total'] = round($prices[$pid] * $opts['quantity'], 2);
    $r['item']       = $opts;
}

ob_clean();
echo json_encode($r);
exit();