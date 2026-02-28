<?php

/**
 * Purge Coffee Shop — Add to Cart Handler (php/add_to_cart.php)
 * FIX #1: Output buffering ensures clean JSON — no stray output can corrupt the response.
 * FIX #5: Default options use the FIRST item of each dropdown (Short, Hot, 0%, Whole).
 */
ob_start();                  // capture any accidental output (warnings, notices, etc.)
error_reporting(0);          // suppress PHP notices/warnings from reaching the buffer
ini_set('display_errors', 0);

require_once 'db_connection.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

/* Normalise any legacy integer cart entries */
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

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $quantity   = max(1, intval($_POST['quantity'] ?? 1));

    $stmt = mysqli_prepare(
        $conn,
        "SELECT product_id, name, price FROM products WHERE product_id = ? AND status = 1"
    );
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($product) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
            $response['message'] = $product['name'] . ' quantity updated!';
        } else {
            /* FIX #5: defaults match the first option in each dropdown */
            $_SESSION['cart'][$product_id] = [
                'quantity'             => $quantity,
                'size'                 => 'Short', // first dropdown option
                'temperature'          => 'Hot',   // first dropdown option
                'sugar_level'          => '0%',    // first dropdown option
                'milk'                 => 'Whole', // first dropdown option
                'addons'               => [],
                'special_instructions' => ''
            ];
            $response['message'] = $product['name'] . ' added to cart!';
        }
        $response['success']      = true;
        $response['product_name'] = $product['name'];
        $response['cart_count']   = array_sum(
            array_column(array_values($_SESSION['cart']), 'quantity')
        );
    } else {
        $response['message'] = 'Product not found or unavailable.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

/* ── AJAX response ─────────────────────────────────────────── */
if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    ob_end_clean();             // discard any accidental output before JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

/* ── Non-AJAX fallback ─────────────────────────────────────── */
if ($response['success']) {
    $_SESSION['cart_message'] = $response['message'];
} else {
    $_SESSION['cart_error'] = $response['message'];
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'menu.php'));
exit();