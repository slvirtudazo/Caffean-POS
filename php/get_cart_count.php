<?php

/**
 * Purge Coffee Shop — Get Cart Count (php/get_cart_count.php)
 * Returns the total quantity of items in the session cart as JSON.
 * Called by main.js updateCartCount() to keep the navbar badge in sync.
 */
require_once 'db_connection.php';

header('Content-Type: application/json');

$count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (is_array($item) && isset($item['quantity'])) {
            $count += (int)$item['quantity'];
        } elseif (is_numeric($item)) {
            $count += (int)$item;
        }
    }
}

echo json_encode(['count' => $count]);
