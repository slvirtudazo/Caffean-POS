<?php

// Order Success Page — shown after a successful checkout.
require_once 'php/db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['order_success'])) {
    header("Location: index.php");
    exit();
}

$order_id = intval($_SESSION['order_success']);
unset($_SESSION['order_success']);

// Fetch the order details.
$stmt = mysqli_prepare(
    $conn,
    "SELECT o.order_id, o.total_amount, o.status, o.order_date, o.payment_method, o.delivery_address,
            u.full_name
     FROM orders o
     JOIN users u ON o.user_id = u.user_id
     WHERE o.order_id = ? AND o.user_id = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $order_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    header("Location: account.php");
    exit();
}

// Fetch items for this order.
$stmt = mysqli_prepare(
    $conn,
    "SELECT p.name, oi.quantity, oi.price_at_time
     FROM order_items oi
     JOIN products p ON oi.product_id = p.product_id
     WHERE oi.order_id = ?"
);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
$items = [];
while ($row = mysqli_fetch_assoc($items_result)) $items[] = $row;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Confirmed — Caffean</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <style>
        body {
            background: var(--ivory-cream, #f5f1e8);
        }

        .success-page {
            padding: 70px 0;
            min-height: 80vh;
        }

        .success-icon {
            font-size: 3.5rem;
            color: #2e7d32;
            margin-bottom: 16px;
        }

        .success-title {
            font-family: var(--font-heading);
            font-size: 2rem;
            color: var(--deep-maroon, #3c1518);
            margin-bottom: 8px;
        }

        .success-sub {
            font-family: var(--font-body);
            color: rgba(60, 21, 24, 0.65);
            margin-bottom: 30px;
        }

        .order-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid rgba(60, 21, 24, 0.1);
            box-shadow: 0 1px 4px rgba(60, 21, 24, 0.07);
            padding: 28px;
            max-width: 560px;
            margin: 0 auto 24px;
        }

        .order-card h3 {
            font-family: var(--font-subheading);
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(60, 21, 24, 0.5);
            margin-bottom: 16px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 7px 0;
            border-bottom: 1px solid rgba(60, 21, 24, 0.06);
            font-family: var(--font-body);
            font-size: 0.9rem;
            color: rgba(60, 21, 24, 0.8);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: rgba(60, 21, 24, 0.5);
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 0.88rem;
            font-family: var(--font-body);
            color: rgba(60, 21, 24, 0.75);
            border-bottom: 1px solid rgba(60, 21, 24, 0.05);
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0 0;
            font-family: var(--font-subheading);
            font-weight: 700;
            font-size: 1rem;
            color: var(--deep-maroon, #3c1518);
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
            border-radius: 6px;
            padding: 3px 10px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .btn-continue {
            display: inline-block;
            background: var(--deep-maroon, #3c1518);
            color: #fff;
            padding: 12px 32px;
            border-radius: 10px;
            font-family: var(--font-subheading);
            font-weight: 700;
            text-decoration: none;
            transition: background 0.2s;
            margin: 6px;
        }

        .btn-continue:hover {
            background: var(--burgundy-wine, #5b1312);
            color: #fff;
        }

        .btn-outline-maroon {
            display: inline-block;
            border: 2px solid var(--deep-maroon, #3c1518);
            color: var(--deep-maroon, #3c1518);
            padding: 10px 28px;
            border-radius: 10px;
            font-family: var(--font-subheading);
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
            margin: 6px;
        }

        .btn-outline-maroon:hover {
            background: var(--deep-maroon, #3c1518);
            color: #fff;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/coffee_beans_logo.png" alt="Caffean Logo" />
                <span>caffean</span>
            </a>
            <div class="nav-icons">
                <a href="cart.php" class="text-decoration-none"><i class="fas fa-shopping-cart nav-icon"></i></a>
                <a href="account.php" class="text-decoration-none"><i class="fas fa-user nav-icon"></i></a>
            </div>
        </div>
    </nav>

    <section class="success-page text-center">
        <div class="container">
            <div class="success-icon"><i class="fas fa-circle-check"></i></div>
            <h1 class="success-title">Order Confirmed!</h1>
            <p class="success-sub">Thank you, <?= htmlspecialchars($order['full_name']) ?>! Your order has been placed successfully.</p>

            <!-- Order Details -->
            <div class="order-card text-start">
                <h3>Order Details</h3>
                <div class="detail-row"><span class="detail-label">Order ID</span><span>#<?= $order['order_id'] ?></span></div>
                <div class="detail-row"><span class="detail-label">Date</span><span><?= date('F d, Y, g:i A', strtotime($order['order_date'])) ?></span></div>
                <div class="detail-row"><span class="detail-label">Status</span><span class="badge-pending"><?= ucfirst($order['status']) ?></span></div>
                <div class="detail-row"><span class="detail-label">Payment</span><span><?= htmlspecialchars($order['payment_method']) ?></span></div>
                <div class="detail-row"><span class="detail-label">Delivery to</span><span style="max-width:60%;text-align:right;"><?= htmlspecialchars($order['delivery_address']) ?></span></div>
            </div>

            <!-- Items Ordered -->
            <div class="order-card text-start">
                <h3>Items Ordered</h3>
                <?php foreach ($items as $item): ?>
                    <div class="item-row">
                        <span><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></span>
                        <span>₱<?= number_format($item['price_at_time'] * $item['quantity'], 2) ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="total-row">
                    <span>Total Paid</span>
                    <span>₱<?= number_format($order['total_amount'], 2) ?></span>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-3">
                <a href="menu.php" class="btn-continue">Continue Shopping</a>
                <a href="account.php" class="btn-outline-maroon">View My Orders</a>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>

</html>