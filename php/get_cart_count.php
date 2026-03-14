<?php

// Returns the total cart item quantity as JSON for the navbar badge counter.
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
