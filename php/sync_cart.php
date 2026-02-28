<?php

/**
 * sync_cart.php — Cart DB sync utility
 * Saves/loads session cart to/from user_carts table for logged-in users.
 */

// Save session cart to DB for logged-in user
function saveCartToDb($conn, $user_id) {
    if (!$user_id || !$conn) return;

    // Clear current cart rows for this user
    $del = mysqli_prepare($conn, "DELETE FROM user_carts WHERE user_id = ?");
    mysqli_stmt_bind_param($del, 'i', $user_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    if (empty($_SESSION['cart'])) return;

    // Insert each cart item
    $ins = mysqli_prepare($conn,
        "INSERT INTO user_carts
            (user_id, product_id, quantity, size, temperature, sugar_level, milk, addons, special_instructions)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($_SESSION['cart'] as $pid => $item) {
        $uid   = (int)$user_id;
        $pid_i = (int)$pid;
        $qty   = (int)($item['quantity']              ?? 1);
        $size  = (string)($item['size']               ?? 'Short');
        $temp  = (string)($item['temperature']        ?? 'Hot');
        $sugar = (string)($item['sugar_level']        ?? '0%');
        $milk  = (string)($item['milk']               ?? 'Whole');
        $addons = json_encode($item['addons']         ?? []);
        $notes  = (string)($item['special_instructions'] ?? '');

        mysqli_stmt_bind_param($ins, 'iiissssss',
            $uid, $pid_i, $qty, $size, $temp, $sugar, $milk, $addons, $notes
        );
        mysqli_stmt_execute($ins);
    }
    mysqli_stmt_close($ins);
}

// Load cart from DB into session for logged-in user
// DB items only added if not already in session (session takes priority)
function loadCartFromDb($conn, $user_id) {
    if (!$user_id || !$conn) return;

    $sel = mysqli_prepare($conn,
        "SELECT uc.product_id, uc.quantity, uc.size, uc.temperature,
                uc.sugar_level, uc.milk, uc.addons, uc.special_instructions
         FROM user_carts uc
         INNER JOIN products p ON p.product_id = uc.product_id AND p.status = 1
         WHERE uc.user_id = ?"
    );
    mysqli_stmt_bind_param($sel, 'i', $user_id);
    mysqli_stmt_execute($sel);
    $result = mysqli_stmt_get_result($sel);

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $pid = $row['product_id'];
        if (!isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid] = [
                'quantity'             => (int)$row['quantity'],
                'size'                 => $row['size'],
                'temperature'          => $row['temperature'],
                'sugar_level'          => $row['sugar_level'],
                'milk'                 => $row['milk'],
                'addons'               => json_decode($row['addons'] ?? '[]', true) ?: [],
                'special_instructions' => $row['special_instructions'] ?? ''
            ];
        }
    }
    mysqli_stmt_close($sel);
}