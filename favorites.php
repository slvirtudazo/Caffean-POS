<?php
// Favorites AJAX handler — handles toggle, remove, batch check, get, and check actions.

// Suppress PHP error output to keep JSON responses clean.
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

require_once 'php/db_connection.php';
require_once 'php/product_images.php';

// Route all requests through a single function for a clean exit point.
$response = handleRequest();

// Discard buffered output, then send clean JSON.
ob_end_clean();
header('Content-Type: application/json');
echo json_encode($response);
exit;

function handleRequest()
{
    global $conn;

    // Require authentication.
    if (!isset($_SESSION['user_id'])) {
        return ['success' => false, 'message' => 'Not logged in.'];
    }

    $user_id = (int)$_SESSION['user_id'];
    $action  = $_POST['action'] ?? $_GET['action'] ?? '';

    // Batch check: return which of the given product IDs are favorited.
    if ($action === 'batch' && isset($_GET['ids'])) {
        $raw = explode(',', $_GET['ids']);
        $ids = array_values(array_filter(array_map('intval', $raw)));

        if (empty($ids)) {
            return ['success' => true, 'favorited' => []];
        }

        $ph   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = mysqli_prepare(
            $conn,
            "SELECT product_id FROM favorites WHERE user_id = ? AND product_id IN ($ph)"
        );
        mysqli_stmt_bind_param($stmt, str_repeat('i', count($ids) + 1), $user_id, ...$ids);
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);

        return [
            'success'   => true,
            'favorited' => array_column($rows, 'product_id'),
        ];
    }

    // Get favorites list with pagination.
    if ($action === 'get') {
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $per_page = 5;
        $offset   = ($page - 1) * $per_page;

        // Get total count.
        $stmt = mysqli_prepare(
            $conn,
            "SELECT COUNT(*) FROM favorites WHERE user_id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $total = (int)mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
        mysqli_stmt_close($stmt);

        // Fetch paginated rows.
        $stmt = mysqli_prepare(
            $conn,
            "SELECT p.product_id, p.name, p.price, p.image_path, c.name AS category
             FROM favorites f
             JOIN products p ON p.product_id = f.product_id
             LEFT JOIN categories c ON c.category_id = p.category_id
             WHERE f.user_id = ?
             ORDER BY f.created_at DESC
             LIMIT ? OFFSET ?"
        );
        mysqli_stmt_bind_param($stmt, 'iii', $user_id, $per_page, $offset);
        mysqli_stmt_execute($stmt);
        $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);

        // Resolve image URLs.
        foreach ($rows as &$row) {
            $row['image_path'] = resolveProductImage($row['name'], $row['image_path']);
        }
        unset($row);

        return [
            'success'     => true,
            'items'       => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int)ceil($total / $per_page),
        ];
    }

    // Toggle: add if not favorited, remove if already favorited.
    if ($action === 'toggle' && isset($_POST['product_id'])) {
        $pid = (int)$_POST['product_id'];

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id FROM favorites WHERE user_id = ? AND product_id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $pid);
        mysqli_stmt_execute($stmt);
        $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($exists) {
            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM favorites WHERE user_id = ? AND product_id = ?"
            );
            mysqli_stmt_bind_param($stmt, 'ii', $user_id, $pid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'state' => 'removed'];
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO favorites (user_id, product_id) VALUES (?, ?)"
            );
            mysqli_stmt_bind_param($stmt, 'ii', $user_id, $pid);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return ['success' => true, 'state' => 'added'];
        }
    }

    // Remove a single favorite.
    if ($action === 'remove' && isset($_POST['product_id'])) {
        $pid  = (int)$_POST['product_id'];
        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM favorites WHERE user_id = ? AND product_id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $pid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return ['success' => true];
    }

    // Check if a single product is favorited.
    if ($action === 'check' && isset($_GET['product_id'])) {
        $pid  = (int)$_GET['product_id'];
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id FROM favorites WHERE user_id = ? AND product_id = ?"
        );
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $pid);
        mysqli_stmt_execute($stmt);
        $found = (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        return ['success' => true, 'favorited' => $found];
    }

    return ['success' => false, 'message' => 'Invalid action.'];
}
