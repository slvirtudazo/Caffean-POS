<?php

/**
 * Purge Coffee Shop — Product Interaction Tracker (track_interaction.php)
 * Increments or decrements favorite/add_to_cart counts for bestseller ranking.
 * Silent endpoint — no response body on success.
 */

require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit();

$product_id       = intval($_POST['product_id']     ?? 0);
$interaction_type = trim($_POST['interaction_type'] ?? '');

// Validate inputs and interaction type
if ($product_id <= 0 || !in_array($interaction_type, ['favorite', 'unfavorite', 'add_to_cart'])) {
    exit();
}

// Verify product exists and is active
$stmt = mysqli_prepare($conn, "SELECT product_id FROM products WHERE product_id = ? AND status = 1");
mysqli_stmt_bind_param($stmt, 'i', $product_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
$exists = mysqli_stmt_num_rows($stmt) > 0;
mysqli_stmt_close($stmt);

if (!$exists) exit();

// Decrement favorite count on unfavorite
if ($interaction_type === 'unfavorite') {
    $stmt = mysqli_prepare($conn,
        "UPDATE product_interactions
         SET interaction_count = GREATEST(0, interaction_count - 1), last_interaction = NOW()
         WHERE product_id = ? AND interaction_type = 'favorite'"
    );
    mysqli_stmt_bind_param($stmt, 'i', $product_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    exit();
}

// Upsert: increment if record exists, otherwise insert
$stmt = mysqli_prepare($conn,
    "INSERT INTO product_interactions (product_id, interaction_type, interaction_count, last_interaction)
     VALUES (?, ?, 1, NOW())
     ON DUPLICATE KEY UPDATE
       interaction_count = interaction_count + 1,
       last_interaction  = NOW()"
);
mysqli_stmt_bind_param($stmt, 'is', $product_id, $interaction_type);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

exit();