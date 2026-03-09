<?php
/**
 * Caffean Shop - AJAX Search Handler
 * Queries the database for products matching the search term.
 */

require_once 'db_connection.php';

header('Content-Type: application/json');

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

// Search both name and description
$searchTerm = "%{$query}%";
$sql = "SELECT product_id, name, description, price, category_id 
        FROM products 
        WHERE status = 1 AND (name LIKE ? OR description LIKE ?) 
        LIMIT 10";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $searchTerm);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}

mysqli_stmt_close($stmt);
echo json_encode($products);
?>