<?php

/**
 * Purge Coffee Shop — Apply Promo Code (php/apply_promo.php)
 * Validates a promo code against the promo_codes table and returns discount data.
 */

require_once 'db_connection.php';

header('Content-Type: application/json');

$r = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($r);
    exit();
}

$code     = strtoupper(trim($_POST['code']    ?? ''));
$subtotal = (float)($_POST['subtotal']         ?? 0);

if (!$code) {
    $r['message'] = 'Please enter a promo code.';
    echo json_encode($r);
    exit();
}

// Fetch active, non-expired promo code
$stmt = mysqli_prepare(
    $conn,
    "SELECT discount_type, discount_value, min_order_amount
     FROM promo_codes
     WHERE code = ? AND is_active = 1
       AND (expires_at IS NULL OR expires_at > NOW())"
);
mysqli_stmt_bind_param($stmt, 's', $code);
mysqli_stmt_execute($stmt);
$promo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$promo) {
    $r['message'] = 'Invalid or expired promo code.';
    echo json_encode($r);
    exit();
}

if ($subtotal < $promo['min_order_amount']) {
    $r['message'] = 'Minimum order of ₱' . number_format($promo['min_order_amount'], 2) . ' required for this code.';
    echo json_encode($r);
    exit();
}

// Calculate discount amount
if ($promo['discount_type'] === 'percentage') {
    $discount = round($subtotal * ($promo['discount_value'] / 100), 2);
    $label    = $promo['discount_value'] . '% off';
} else {
    $discount = min((float)$promo['discount_value'], $subtotal);
    $label    = '₱' . number_format($discount, 2) . ' off';
}

echo json_encode([
    'success'         => true,
    'discount_amount' => $discount,
    'discount_type'   => $promo['discount_type'],
    'message'         => "Promo applied! You saved $label.",
]);