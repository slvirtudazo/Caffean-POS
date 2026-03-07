<?php
/* Purge Coffee — Favorites AJAX Handler */
require_once 'db_connection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

/* ── BATCH CHECK — which of these IDs are favorited ─────── */
if ($action === 'batch' && isset($_GET['ids'])) {
    $raw = explode(',', $_GET['ids']);
    $ids = array_filter(array_map('intval', $raw));

    if (empty($ids)) {
        echo json_encode(['success' => true, 'favorited' => []]);
        exit();
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types        = str_repeat('i', count($ids));
    $stmt = mysqli_prepare($conn,
        "SELECT product_id FROM favorites WHERE user_id = ? AND product_id IN ($placeholders)");
    mysqli_stmt_bind_param($stmt, 'i' . $types, $user_id, ...$ids);
    mysqli_stmt_execute($stmt);
    $result = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    echo json_encode([
        'success'   => true,
        'favorited' => array_column($result, 'product_id')
    ]);
    exit();
}

/* ── GET favorites list with pagination ─────────────────── */
if ($action === 'get') {
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $per_page = 5;
    $offset   = ($page - 1) * $per_page;

    /* Total count */
    $stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $total = (int)mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
    mysqli_stmt_close($stmt);

    /* Fetch page of favorites joined with product + category */
    $stmt = mysqli_prepare($conn,
        "SELECT f.id AS fav_id, p.product_id, p.name, p.price,
                p.image_path, c.name AS category
         FROM favorites f
         JOIN products p  ON p.product_id = f.product_id
         LEFT JOIN categories c ON c.category_id = p.category_id
         WHERE f.user_id = ?
         ORDER BY f.created_at DESC
         LIMIT ? OFFSET ?");
    mysqli_stmt_bind_param($stmt, 'iii', $user_id, $per_page, $offset);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    echo json_encode([
        'success'    => true,
        'items'      => $rows,
        'total'      => $total,
        'page'       => $page,
        'per_page'   => $per_page,
        'total_pages'=> (int)ceil($total / $per_page)
    ]);
    exit();
}

/* ── TOGGLE (add if missing, remove if exists) ──────────── */
if ($action === 'toggle' && isset($_POST['product_id'])) {
    $pid = (int)$_POST['product_id'];

    $stmt = mysqli_prepare($conn,
        "SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $pid);
    mysqli_stmt_execute($stmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($existing) {
        $stmt = mysqli_prepare($conn,
            "DELETE FROM favorites WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $existing['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => true, 'state' => 'removed']);
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $pid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['success' => true, 'state' => 'added']);
    }
    exit();
}

/* ── REMOVE single favorite ──────────────────────────────── */
if ($action === 'remove' && isset($_POST['product_id'])) {
    $pid = (int)$_POST['product_id'];
    $stmt = mysqli_prepare($conn,
        "DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $pid);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true]);
    exit();
}

/* ── CHECK if a product is favorited ────────────────────── */
if ($action === 'check' && isset($_GET['product_id'])) {
    $pid = (int)$_GET['product_id'];
    $stmt = mysqli_prepare($conn,
        "SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $pid);
    mysqli_stmt_execute($stmt);
    $found = (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true, 'favorited' => $found]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);