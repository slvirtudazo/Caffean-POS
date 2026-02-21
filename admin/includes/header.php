<?php

/**
 * Admin Header Include — admin/includes/header.php
 * Navbar style mirrors the customer-facing site exactly.
 */

if (!defined('BASE_URL')) define('BASE_URL', '/purge-coffee');

$admin_name    = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$current_page  = basename($_SERVER['PHP_SELF']);

/* Map each admin page to its own CSS file */
$page_css_map = [
  'dashboard.php' => 'dashboard.css',
  'products.php'  => 'products.css',
  'orders.php'    => 'orders.css',
  'customers.php' => 'customers.css',
  // messages.php uses inline <style> — no separate CSS file needed
];
$page_css_file = $page_css_map[$current_page] ?? null;

/* Unread messages count — shown as live badge in sidebar */
$_unread_row   = mysqli_fetch_assoc(mysqli_query(
  $conn,
  "SELECT COUNT(*) AS c FROM contact_messages WHERE is_read = 0"
));
$_unread_count = (int)($_unread_row['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Purge Coffee — Admin</title>
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/images/coffee_beans_logo.png" />

  <!-- Shared admin styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css?v=<?= time() ?>" />

  <!-- Page-specific styles -->
  <?php if ($page_css_file): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/<?= $page_css_file ?>?v=<?= time() ?>" />
  <?php endif; ?>

  <link rel="stylesheet" href="<?= BASE_URL ?>/css/footer-section.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <style>
    /* Unread badge inside sidebar link */
    .sidebar-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 18px;
      height: 18px;
      padding: 0 5px;
      border-radius: 50px;
      background: var(--burgundy-wine, #5b1312);
      color: #fff;
      font-size: 0.7rem;
      font-weight: 700;
      margin-left: auto;
      line-height: 1;
    }
  </style>

  <!-- ══ Shared Table Sorting Utility ══ -->
  <script>
    function initSortableTable(tableId) {
      var table = document.getElementById(tableId);
      if (!table) return;

      var headers = table.querySelectorAll('thead th[data-sort]');
      var currentCol = -1;
      var currentDir = 'asc';

      function parseAdminDate(str) {
        if (!str) return 0;
        var d = new Date(str.replace(',', ''));
        if (!isNaN(d)) return d.getTime();
        d = new Date(str);
        return isNaN(d) ? 0 : d.getTime();
      }

      headers.forEach(function(th) {
        th.addEventListener('click', function() {
          var col = th.cellIndex;
          var type = th.dataset.sort;

          if (currentCol === col) {
            currentDir = (currentDir === 'asc') ? 'desc' : 'asc';
          } else {
            currentCol = col;
            currentDir = 'asc';
          }

          table.querySelectorAll('thead th[data-sort]').forEach(function(h) {
            h.classList.remove('sort-asc', 'sort-desc');
          });
          th.classList.add(currentDir === 'asc' ? 'sort-asc' : 'sort-desc');

          var tbody = table.querySelector('tbody');
          var rows = Array.from(tbody.querySelectorAll('tr')).filter(function(r) {
            return !r.querySelector('.empty-state');
          });
          if (rows.length === 0) return;

          rows.sort(function(a, b) {
            var aText = a.cells[col] ? a.cells[col].textContent.trim() : '';
            var bText = b.cells[col] ? b.cells[col].textContent.trim() : '';
            var cmp = 0;

            if (type === 'number') {
              cmp = (parseFloat(aText.replace(/[^0-9.-]/g, '')) || 0) -
                (parseFloat(bText.replace(/[^0-9.-]/g, '')) || 0);
            } else if (type === 'date') {
              cmp = parseAdminDate(aText) - parseAdminDate(bText);
            } else {
              cmp = aText.toLowerCase().localeCompare(bText.toLowerCase());
            }

            return (currentDir === 'asc') ? cmp : -cmp;
          });

          rows.forEach(function(r) {
            tbody.appendChild(r);
          });
        });
      });
    }
  </script>
</head>

<body>

  <!-- ══ TOP NAVBAR ════════════════════════════════════════════ -->
  <nav class="admin-navbar">
    <div class="admin-nav-inner">

      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="admin-brand">
        <img src="<?= BASE_URL ?>/images/coffee_beans_logo.png" alt="Purge Coffee" />
        <span>purge coffee</span>
      </a>

      <div class="nav-right">
        <div class="admin-chip">
          <div class="admin-avatar"><?= $admin_initial ?></div>
          <span class="admin-name"><?= $admin_name ?></span>
        </div>
        <a href="<?= BASE_URL ?>/php/logout.php" class="btn-logout" title="Logout">
          <i class="fas fa-sign-out-alt"></i> Log Out
        </a>
      </div>

    </div>
  </nav>

  <!-- ══ BODY WRAPPER ══════════════════════════════════════════ -->
  <div class="admin-body">

    <!-- ── Sidebar ──────────────────────────────────────────── -->
    <aside class="admin-sidebar">

      <div class="sidebar-section">
        <p class="sidebar-label"><i class="fas fa-bars"></i> Navigation</p>
        <ul class="sidebar-nav">
          <li>
            <a href="<?= BASE_URL ?>/admin/dashboard.php"
              <?= $current_page === 'dashboard.php' ? 'class="active"' : '' ?>>
              <i class="fas fa-chart-pie"></i> Dashboard
            </a>
          </li>
          <li>
            <a href="<?= BASE_URL ?>/admin/products.php"
              <?= $current_page === 'products.php' ? 'class="active"' : '' ?>>
              <i class="fas fa-box-open"></i> Products
            </a>
          </li>
          <li>
            <a href="<?= BASE_URL ?>/admin/orders.php"
              <?= $current_page === 'orders.php' ? 'class="active"' : '' ?>>
              <i class="fas fa-receipt"></i> Orders
            </a>
          </li>
          <li>
            <a href="<?= BASE_URL ?>/admin/customers.php"
              <?= $current_page === 'customers.php' ? 'class="active"' : '' ?>>
              <i class="fas fa-users"></i> Customers
            </a>
          </li>
          <li>
            <a href="<?= BASE_URL ?>/admin/messages.php"
              <?= $current_page === 'messages.php' ? 'class="active"' : '' ?>
              style="display:flex;align-items:center;gap:8px;">
              <i class="fas fa-envelope"></i> Messages
              <?php if ($_unread_count > 0): ?>
                <span class="sidebar-badge"><?= $_unread_count ?></span>
              <?php endif; ?>
            </a>
          </li>
        </ul>
      </div>

      <div class="sidebar-section">
        <p class="sidebar-label"><i class="fas fa-store"></i> Store</p>
        <ul class="sidebar-nav">
          <li>
            <a href="<?= BASE_URL ?>/" target="_blank">
              <i class="fas fa-external-link-alt"></i> View Store
            </a>
          </li>
        </ul>
      </div>

    </aside>

    <!-- ── Main Content ─────────────────────────────────────── -->
    <main class="admin-wrapper">