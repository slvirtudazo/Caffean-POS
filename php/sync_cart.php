<?php

// Cart DB sync utility — saves and loads the session cart for logged-in users.

// Saves the session cart to the database for the given user.
function saveCartToDb($conn, $user_id)
{
    if (!$user_id || !$conn) return;

    // Delete existing cart rows for this user.
    $del = mysqli_prepare($conn, "DELETE FROM user_carts WHERE user_id = ?");
    mysqli_stmt_bind_param($del, 'i', $user_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);

    if (empty($_SESSION['cart'])) return;

    // Insert each cart item into the database.
    $ins = mysqli_prepare(
        $conn,
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

        mysqli_stmt_bind_param(
            $ins,
            'iiissssss',
            $uid,
            $pid_i,
            $qty,
            $size,
            $temp,
            $sugar,
            $milk,
            $addons,
            $notes
        );
        mysqli_stmt_execute($ins);
    }
    mysqli_stmt_close($ins);
}

// Loads the cart from the database into the session for the given user.
// Session items take priority — DB items are only added if not already present.
function loadCartFromDb($conn, $user_id)
{
    if (!$user_id || !$conn) return;

    $sel = mysqli_prepare(
        $conn,
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
