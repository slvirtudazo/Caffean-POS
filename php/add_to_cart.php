<?php
/**
 * Purge Coffee Shop - Add to Cart Handler
 * This script processes requests to add products to the shopping cart.
 * It can be called via AJAX for seamless cart updates without page reload.
 */

require_once 'db_connection.php';

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

$response = array('success' => false, 'message' => '');

// Process add to cart request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Validate product exists in database
    $check_query = "SELECT product_id, name, price FROM products WHERE product_id = ? AND status = 1";
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
        
        // Add to cart or update quantity if already in cart
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
            $response['message'] = 'Product quantity updated in cart!';
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
            $response['message'] = 'Product added to cart!';
        }
        
        $response['success'] = true;
        $response['product_name'] = $product['name'];
        $response['cart_count'] = array_sum($_SESSION['cart']);
        
    } else {
        $response['message'] = 'Product not found or unavailable.';
    }
    
    mysqli_stmt_close($stmt);
    
} else {
    $response['message'] = 'Invalid request.';
}

// Return JSON response for AJAX calls
if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// For non-AJAX requests, redirect back with message
if ($response['success']) {
    $_SESSION['cart_message'] = $response['message'];
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    $_SESSION['cart_error'] = $response['message'];
    header("Location: " . $_SERVER['HTTP_REFERER']);
}
exit();
?>