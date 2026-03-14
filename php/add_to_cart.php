<?php

// Add to Cart handler — processes POST requests and updates the session cart.
// Output buffering prevents stray output from corrupting the JSON response.
ob_start();
error_reporting(0); // Suppress notices and warnings.
ini_set('display_errors', 0);

require_once 'db_connection.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Normalize legacy integer cart entries to the standard array format.
foreach ($_SESSION['cart'] as $pid => &$v) {
    if (!is_array($v)) {
        $v = [
            'quantity'             => (int)$v,
            'size'                 => 'Short',  // default: first dropdown option
            'temperature'          => 'Hot',    // default: first dropdown option
            'sugar_level'          => '0%',     // default: first dropdown option
            'milk'                 => 'Whole',  // default: first dropdown option
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
            // Add new item with default options for each customization field.
            $_SESSION['cart'][$product_id] = [
                'quantity'             => $quantity,
                'size'                 => 'Short', // default: first dropdown option
                'temperature'          => 'Hot',   // default: first dropdown option
                'sugar_level'          => '0%',    // default: first dropdown option
                'milk'                 => 'Whole', // default: first dropdown option
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

// Return JSON for AJAX requests.
if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    ob_end_clean(); // Discard buffered output before sending JSON.
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Non-AJAX fallback: store a flash message and redirect back.
if ($response['success']) {
    $_SESSION['cart_message'] = $response['message'];
} else {
    $_SESSION['cart_error'] = $response['message'];
}
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'menu.php'));
exit();
