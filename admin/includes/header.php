<?php

/**
 * Admin Header Include — admin/includes/header.php
 * Outputs the full HTML head, top navbar, and sidebar for all admin pages.
 */

if (!defined('BASE_URL')) define('BASE_URL', '/purge-coffee');

$admin_name    = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$current_page  = basename($_SERVER['PHP_SELF']);

// Map each admin page to its page-specific CSS file
$page_css_map = [
    'dashboard.php'      => 'dashboard.css',
    'products.php'       => 'products.css',
    'orders.php'         => 'orders.css',
    'instore_orders.php' => 'orders.css',
    'customers.php'      => 'customers.css',
];
$page_css_file = $page_css_map[$current_page] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Purge Coffee - Admin</title>
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/images/coffee_beans_logo.png" />

  <!-- Shared admin styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css?v=<?= time() ?>" />

  <!-- Page-specific styles -->
  <?php if ($page_css_file): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/<?= $page_css_file ?>?v=<?= time() ?>" />
  <?php endif; ?>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

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

    /* Sortable column indicators */
    .admin-table th[data-sort]::after      { content: ' ↕'; font-size: 0.65rem; opacity: 0.4; }
    .admin-table th[data-sort].sort-asc::after  { content: ' ↑'; opacity: 0.9; }
    .admin-table th[data-sort].sort-desc::after { content: ' ↓'; opacity: 0.9; }
  </style>

  <!-- Shared sortable table utility used by all admin table pages -->
  <script>
    function initSortableTable(tableId) {
      var table = document.getElementById(tableId);
      if (!table) return;
      var headers    = table.querySelectorAll('thead th[data-sort]');
      var currentCol = -1;
      var currentDir = 'asc';

      function parseAdminDate(str) {
        if (!str) return 0;
        var d = new Date(str.replace(',', ''));
        return isNaN(d) ? 0 : d.getTime();
      }

      headers.forEach(function (th) {
        th.addEventListener('click', function () {
          var col  = th.cellIndex;
          var type = th.dataset.sort;
          currentDir = (currentCol === col && currentDir === 'asc') ? 'desc' : 'asc';
          currentCol = col;

          table.querySelectorAll('thead th[data-sort]').forEach(function (h) {
            h.classList.remove('sort-asc', 'sort-desc');
          });
          th.classList.add(currentDir === 'asc' ? 'sort-asc' : 'sort-desc');

          var tbody = table.querySelector('tbody');
          var rows  = Array.from(tbody.querySelectorAll('tr')).filter(function (r) {
            return !r.querySelector('.empty-state');
          });
          if (!rows.length) return;

          rows.sort(function (a, b) {
            var aT = a.cells[col] ? a.cells[col].textContent.trim() : '';
            var bT = b.cells[col] ? b.cells[col].textContent.trim() : '';
            var cmp = 0;

            if (type === 'number') {
              cmp = (parseFloat(aT.replace(/[^0-9.-]/g, '')) || 0) -
                    (parseFloat(bT.replace(/[^0-9.-]/g, '')) || 0);
            } else if (type === 'date') {
              cmp = parseAdminDate(aT) - parseAdminDate(bT);
            } else {
              cmp = aT.toLowerCase().localeCompare(bT.toLowerCase());
            }
            return currentDir === 'asc' ? cmp : -cmp;
          });

          rows.forEach(function (r) { tbody.appendChild(r); });
        });
      });
    }
  </script>
</head>

<body>

  <!-- Top navbar -->
  <nav class="admin-navbar">
    <div class="admin-nav-inner">
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="admin-brand">
        <img src="<?= BASE_URL ?>/images/coffee_beans_logo.png" alt="Purge Coffee" />
        <span>purge coffee</span>
      </a>
      <div class="nav-right">
        <div class="admin-chip">
          <div class="admin-avatar"><?= $admin_initial ?></div>
          <div style="display:flex;flex-direction:column;line-height:1.2;">
            <span class="admin-name"><?= $admin_name ?></span>
            <span style="font-size:0.7rem;color:var(--text-muted);font-family:var(--font-subheading);">Administrator</span>
          </div>
        </div>
        <a href="<?= BASE_URL ?>/" target="_blank" class="btn-ghost">
          <i class="fas fa-arrow-up-right-from-square"></i> View Store
        </a>
      </div>
    </div>
  </nav>

  <!-- Body wrapper: sidebar + main -->
  <div class="admin-body">

    <aside class="admin-sidebar">
      <ul class="sidebar-nav">
        <li>
          <a href="<?= BASE_URL ?>/admin/dashboard.php"
            <?= $current_page === 'dashboard.php' ? 'class="active"' : '' ?>>
            <span class="snav-icon">
              <i class="far fa-chart-bar snav-ic-out"></i>
              <i class="fas fa-chart-bar snav-ic-fill"></i>
            </span>
            <span class="snav-text">Dashboard</span>
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/products.php"
            <?= $current_page === 'products.php' ? 'class="active"' : '' ?>>
            <span class="snav-icon">
              <i class="fas fa-box-open snav-ic-out"></i>
              <i class="fas fa-box-open snav-ic-fill"></i>
            </span>
            <span class="snav-text">Products</span>
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/orders.php"
            <?= $current_page === 'orders.php' ? 'class="active"' : '' ?>>
            <span class="snav-icon">
              <i class="far fa-file-lines snav-ic-out"></i>
              <i class="fas fa-file-lines snav-ic-fill"></i>
            </span>
            <span class="snav-text">Online Orders</span>
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/instore_orders.php"
            <?= $current_page === 'instore_orders.php' ? 'class="active"' : '' ?>>
            <span class="snav-icon">
              <i class="far fa-building snav-ic-out"></i>
              <i class="fas fa-building snav-ic-fill"></i>
            </span>
            <span class="snav-text">In-Store Orders</span>
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/customers.php"
            <?= $current_page === 'customers.php' ? 'class="active"' : '' ?>>
            <span class="snav-icon">
              <i class="far fa-user snav-ic-out"></i>
              <i class="fas fa-user snav-ic-fill"></i>
            </span>
            <span class="snav-text">Customers</span>
          </a>
        </li>
      </ul>

      <!-- Logout pinned to sidebar bottom -->
      <div class="sidebar-logout">
        <a href="<?= BASE_URL ?>/php/logout.php" class="sidebar-logout-btn">
          <span class="snav-icon">
            <i class="fas fa-right-from-bracket"></i>
          </span>
          <span class="snav-text">Log Out</span>
        </a>
      </div>
    </aside>

    <!-- Main content area -->
    <main class="admin-wrapper">