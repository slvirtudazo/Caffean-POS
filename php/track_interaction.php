<?php
/**
 * Purge Coffee Shop - Product Interaction Tracker
 * This script tracks user interactions (favorites, add-to-cart, unfavorites)
 * to dynamically calculate and display best seller products.
 */

require_once 'db_connection.php';

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $interaction_type = isset($_POST['interaction_type']) ? mysqli_real_escape_string($conn, $_POST['interaction_type']) : '';
    
    // Validate inputs
    if ($product_id <= 0 || empty($interaction_type)) {
        exit(); // Silent fail
    }
    
    // Validate interaction type
    $valid_types = ['favorite', 'unfavorite', 'add_to_cart'];
    if (!in_array($interaction_type, $valid_types)) {
        exit(); // Silent fail
    }
    
    // Check if product exists
    $check_product = "SELECT product_id FROM products WHERE product_id = ? AND status = 1";
    $stmt = mysqli_prepare($conn, $check_product);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) == 0) {
        mysqli_stmt_close($stmt);
        exit(); // Product doesn't exist
    }
    mysqli_stmt_close($stmt);
    
    // Handle unfavorite (decrement count)
    if ($interaction_type == 'unfavorite') {
        $update_query = "UPDATE product_interactions 
                        SET interaction_count = GREATEST(0, interaction_count - 1),
                            last_interaction = NOW()
                        WHERE product_id = ? AND interaction_type = 'favorite'";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "i", $product_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        exit(); // Done
    }
    
    // Check if interaction record exists
    $check_query = "SELECT interaction_id, interaction_count 
                   FROM product_interactions 
                   WHERE product_id = ? AND interaction_type = ?";
    
    $stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($stmt, "is", $product_id, $interaction_type);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Update existing record
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        $update_query = "UPDATE product_interactions 
                        SET interaction_count = interaction_count + 1,
                            last_interaction = NOW()
                        WHERE product_id = ? AND interaction_type = ?";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "is", $product_id, $interaction_type);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        // Insert new record
        mysqli_stmt_close($stmt);
        
        $insert_query = "INSERT INTO product_interactions 
                        (product_id, interaction_type, interaction_count, last_interaction) 
                        VALUES (?, ?, 1, NOW())";
        
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, "is", $product_id, $interaction_type);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Silent success - no response needed
exit();
?>