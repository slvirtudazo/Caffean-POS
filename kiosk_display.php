<?php

/**
 * Purge Coffee Shop — Order Queue Display (kiosk_display.php)
 * Shows kiosk orders currently being served and pending.
 * Intended to be displayed on a screen at the claim counter.
 * Auto-refreshes every 15 seconds.
 */

require_once 'php/db_connection.php';

/* Fetch kiosk orders placed in the last 3 hours, pending or processing */
$serving_res = mysqli_query(
    $conn,
    "SELECT order_id, kiosk_order_type, customer_name, status, order_date
     FROM orders
     WHERE is_kiosk = 1
       AND status IN ('processing', 'pending')
       AND order_date >= DATE_SUB(NOW(), INTERVAL 3 HOUR)
     ORDER BY order_id ASC
     LIMIT 30"
);
$orders = [];
while ($row = mysqli_fetch_assoc($serving_res)) $orders[] = $row;

/* Partition into now-serving (processing) and upcoming (pending) */
$now_serving = array_filter($orders, fn($o) => $o['status'] === 'processing');
$upcoming    = array_filter($orders, fn($o) => $o['status'] === 'pending');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="15"> <!-- Auto-refresh every 15s -->
    <title>Order Queue — Purge Coffee</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/kiosk.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="queue-page">

        <!-- Header -->
        <div class="queue-header">
            <div class="queue-logo-area">
                <img src="images/coffee_beans_logo.png" alt="Purge Coffee">
                <span>purge coffee</span>
            </div>
            <div class="queue-clock" id="queue-clock"><?php echo date('g:i A'); ?></div>
        </div>

        <!-- Body -->
        <div class="queue-body">

            <!-- Now Serving -->
            <div class="queue-section serving-now">
                <div class="queue-section-title">
                    <i class="fas fa-bell me-2"></i>Now Serving
                </div>
                <div class="queue-numbers-grid">
                    <?php if (empty($now_serving)): ?>
                        <div class="queue-empty-notice">No orders being served yet.</div>
                    <?php else: ?>
                        <?php foreach ($now_serving as $o): ?>
                            <div class="queue-number-tag now-serving">
                                <span class="qt-type">
                                    <?= $o['kiosk_order_type'] === 'dine_in' ? '🍽 Dine In' : '🛍 Take Out' ?>
                                </span>
                                #<?= str_pad($o['order_id'], 3, '0', STR_PAD_LEFT) ?>
                                <?php if (!empty($o['customer_name']) && $o['customer_name'] !== 'Guest'): ?>
                                    <div style="font-size:0.65rem; font-family: var(--font-body); color: rgba(245,241,232,0.6); margin-top:0.25rem;">
                                        <?= htmlspecialchars($o['customer_name']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming / Pending -->
            <div class="queue-section">
                <div class="queue-section-title">
                    <i class="fas fa-clock me-2"></i>Preparing
                </div>
                <div class="queue-numbers-grid">
                    <?php if (empty($upcoming)): ?>
                        <div class="queue-empty-notice">No orders in queue.</div>
                    <?php else: ?>
                        <?php foreach ($upcoming as $o): ?>
                            <div class="queue-number-tag">
                                <span class="qt-type">
                                    <?= $o['kiosk_order_type'] === 'dine_in' ? '🍽 Dine In' : '🛍 Take Out' ?>
                                </span>
                                #<?= str_pad($o['order_id'], 3, '0', STR_PAD_LEFT) ?>
                                <?php if (!empty($o['customer_name']) && $o['customer_name'] !== 'Guest'): ?>
                                    <div style="font-size:0.65rem; font-family: var(--font-body); color: rgba(245,241,232,0.6); margin-top:0.25rem;">
                                        <?= htmlspecialchars($o['customer_name']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="queue-footer">
            Please listen for your order number to be called. Thank you for choosing Purge Coffee!
            &nbsp;·&nbsp; Auto-refreshes every 15 seconds.
        </div>
    </div>

    <script>
        /* Update clock every second without full page reload */
        function updateClock() {
            const now = new Date();
            let h = now.getHours(),
                m = now.getMinutes();
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12;
            document.getElementById('queue-clock').textContent =
                h + ':' + String(m).padStart(2, '0') + ' ' + ampm;
        }
        setInterval(updateClock, 1000);
    </script>
</body>

</html>