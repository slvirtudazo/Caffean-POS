<?php

// Admin header — outputs the HTML head, navbar, and sidebar.

if (!defined('BASE_URL')) define('BASE_URL', '/caffean-pos');

$admin_name    = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$current_page  = basename($_SERVER['PHP_SELF']);

// Fetch admin's profile image and email from the database
$admin_avatar_src = '';
$admin_email      = '';
if (isset($_SESSION['user_id'])) {
  $a_stmt = mysqli_prepare($conn, "SELECT profile_image, email FROM users WHERE user_id = ?");
  mysqli_stmt_bind_param($a_stmt, 'i', $_SESSION['user_id']);
  mysqli_stmt_execute($a_stmt);
  $a_row = mysqli_fetch_assoc(mysqli_stmt_get_result($a_stmt));
  mysqli_stmt_close($a_stmt);
  if (!empty($a_row['profile_image'])) {
    $admin_avatar_src = BASE_URL . '/' . htmlspecialchars($a_row['profile_image']);
  }
  $admin_email = htmlspecialchars($a_row['email'] ?? '');
}

// Map each page to its CSS file
$page_css_map = [
  'dashboard.php'        => 'dashboard.css',
  'products.php'         => 'products.css',
  'orders.php'           => 'orders.css',
  'instore_orders.php'   => 'orders.css',
  'customers.php'        => 'customers.css',
  'insights.php'         => 'dashboard.css',
  'profile_settings.php' => null,
];
$page_css_file = $page_css_map[$current_page] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Caffean - Admin</title>
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/images/coffee_beans_logo.png" />

  <!-- Shared admin stylesheet -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css?v=<?= time() ?>" />

  <!-- Page-specific stylesheet -->
  <?php if ($page_css_file): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/<?= $page_css_file ?>?v=<?= time() ?>" />
  <?php endif; ?>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

  <style>
    /* Column sort indicators */
    .admin-table th[data-sort]::after {
      content: ' ↕';
      font-size: 0.65rem;
      opacity: 0.4;
    }

    .admin-table th[data-sort].sort-asc::after {
      content: ' ↑';
      opacity: 0.9;
    }

    .admin-table th[data-sort].sort-desc::after {
      content: ' ↓';
      opacity: 0.9;
    }
  </style>

  <!-- Shared table utilities: sorting, pagination, search -->
  <script>
    // Pagination state keyed by table ID
    window._pgState = {};

    // Pagination — shows 10 rows per page
    function initTablePagination(tableId, pageSize) {
      pageSize = pageSize || 10;
      var table = document.getElementById(tableId);
      var pgEl = document.getElementById(tableId + '-pagination');
      if (!table) return;

      window._pgState[tableId] = {
        page: 1,
        pageSize: pageSize
      };

      function renderPage() {
        var state = window._pgState[tableId];
        var allRows = Array.from(table.querySelectorAll('tbody tr')).filter(function(r) {
          return !r.classList.contains('empty-row');
        });
        var visRows = allRows.filter(function(r) {
          return r.dataset.searchMatch !== 'false';
        });
        var total = Math.max(1, Math.ceil(visRows.length / state.pageSize));
        if (state.page > total) state.page = total;

        var start = (state.page - 1) * state.pageSize;
        var end = start + state.pageSize;

        /* Hide all rows, then show the current page's matching rows */
        allRows.forEach(function(r) {
          r.style.display = 'none';
        });
        visRows.slice(start, end).forEach(function(r) {
          r.style.display = '';
        });

        if (pgEl) {
          pgEl.querySelector('.page-info').textContent = 'Page ' + state.page + ' of ' + total;
          pgEl.querySelector('.btn-prev').disabled = state.page <= 1;
          pgEl.querySelector('.btn-next').disabled = state.page >= total;
        }
      }

      if (pgEl) {
        pgEl.querySelector('.btn-prev').addEventListener('click', function() {
          if (window._pgState[tableId].page > 1) {
            window._pgState[tableId].page--;
            renderPage();
          }
        });
        pgEl.querySelector('.btn-next').addEventListener('click', function() {
          var s = window._pgState[tableId];
          var all = Array.from(table.querySelectorAll('tbody tr')).filter(function(r) {
            return !r.classList.contains('empty-row') && r.dataset.searchMatch !== 'false';
          });
          var total = Math.max(1, Math.ceil(all.length / s.pageSize));
          if (s.page < total) {
            s.page++;
            renderPage();
          }
        });
      }

      window._pgState[tableId].renderPage = renderPage;
      renderPage();
    }

    // Search — filters rows and resets to page 1
    function initTableSearch(inputId, tableId) {
      var input = document.getElementById(inputId);
      if (!input) return;
      input.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        var table = document.getElementById(tableId);
        Array.from(table.querySelectorAll('tbody tr')).forEach(function(r) {
          if (r.classList.contains('empty-row')) return;
          r.dataset.searchMatch = (!q || r.textContent.toLowerCase().includes(q)) ? 'true' : 'false';
        });
        if (window._pgState && window._pgState[tableId]) {
          window._pgState[tableId].page = 1;
          window._pgState[tableId].renderPage();
        }
      });
    }

    // Sortable table — applies default desc sort on init
    function initSortableTable(tableId, defaultColIdx) {
      var table = document.getElementById(tableId);
      if (!table) return;
      var headers = table.querySelectorAll('thead th[data-sort]');
      var currentCol = -1;
      var currentDir = 'asc';

      function parseAdminDate(str) {
        if (!str) return 0;
        var d = new Date(str.replace(',', '').replace('·', ''));
        return isNaN(d) ? 0 : d.getTime();
      }

      // Extract numeric value from prefixed IDs like PR-2026-00090
      function parseId(str) {
        var m = str.match(/(\d+)$/);
        return m ? parseInt(m[1], 10) : 0;
      }

      // Sort all non-empty rows by column, type, and direction
      function sortRows(col, type, dir) {
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr')).filter(function(r) {
          return !r.classList.contains('empty-row') && !r.querySelector('.empty-state');
        });
        if (!rows.length) return;

        rows.sort(function(a, b) {
          var aT = a.cells[col] ? a.cells[col].textContent.trim() : '';
          var bT = b.cells[col] ? b.cells[col].textContent.trim() : '';
          var cmp = 0;

          if (type === 'number') {
            // Handle prefixed IDs and plain numbers
            var aHasPrefix = /^[A-Z]+-/.test(aT);
            var bHasPrefix = /^[A-Z]+-/.test(bT);
            if (aHasPrefix || bHasPrefix) {
              cmp = parseId(aT) - parseId(bT);
            } else {
              cmp = (parseFloat(aT.replace(/[^0-9.-]/g, '')) || 0) -
                (parseFloat(bT.replace(/[^0-9.-]/g, '')) || 0);
            }
          } else if (type === 'date') {
            cmp = parseAdminDate(aT) - parseAdminDate(bT);
          } else if (type === 'text') {
            // Also handle prefixed IDs sorted as text
            var aNum = /^[A-Z]+-/.test(aT) ? parseId(aT) : null;
            var bNum = /^[A-Z]+-/.test(bT) ? parseId(bT) : null;
            if (aNum !== null && bNum !== null) {
              cmp = aNum - bNum;
            } else {
              cmp = aT.toLowerCase().localeCompare(bT.toLowerCase());
            }
          } else {
            cmp = aT.toLowerCase().localeCompare(bT.toLowerCase());
          }
          return dir === 'asc' ? cmp : -cmp;
        });

        rows.forEach(function(r) {
          tbody.appendChild(r);
        });
      }

      headers.forEach(function(th) {
        th.addEventListener('click', function() {
          var col = th.cellIndex;
          var type = th.dataset.sort;
          currentDir = (currentCol === col && currentDir === 'asc') ? 'desc' : 'asc';
          currentCol = col;

          table.querySelectorAll('thead th[data-sort]').forEach(function(h) {
            h.classList.remove('sort-asc', 'sort-desc');
          });
          th.classList.add(currentDir === 'asc' ? 'sort-asc' : 'sort-desc');

          sortRows(col, type, currentDir);

          // Reset to page 1 after sorting
          if (window._pgState && window._pgState[tableId]) {
            window._pgState[tableId].page = 1;
            window._pgState[tableId].renderPage();
          }
        });
      });

      // Apply default desc sort on the specified column
      if (typeof defaultColIdx === 'number') {
        var defaultTh = table.querySelector('thead th:nth-child(' + (defaultColIdx + 1) + ')');
        if (defaultTh && defaultTh.dataset.sort) {
          currentCol = defaultColIdx;
          currentDir = 'desc';
          defaultTh.classList.add('sort-desc');
          sortRows(defaultColIdx, defaultTh.dataset.sort, 'desc');
        }
      }
    }
  </script>
</head>

<body>

  <!-- Top navbar -->
  <nav class="admin-navbar">
    <div class="admin-nav-inner">
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="admin-brand">
        <img src="<?= BASE_URL ?>/images/coffee_beans_logo.png" alt="Caffean" />
        <span>Caffean</span>
      </a>
      <div class="nav-right">
        <a href="<?= BASE_URL ?>/" target="_blank" class="btn-ghost">
          <i class="fas fa-arrow-up-right-from-square"></i> View Store
        </a>
      </div>
    </div>
  </nav>

  <!-- Body wrapper — sidebar and main content -->
  <div class="admin-body">

    <aside class="admin-sidebar">

      <!-- Sidebar profile info -->
      <div class="admin-sidebar-profile">
        <div class="admin-avatar-wrap">
          <?php if ($admin_avatar_src): ?>
            <img src="<?= $admin_avatar_src ?>" alt="Profile" class="admin-sidebar-avatar" id="adminAvatarPreview" />
          <?php else: ?>
            <div class="admin-sidebar-avatar" id="adminAvatarInitial"><?= $admin_initial ?></div>
          <?php endif; ?>
        </div>
        <div class="admin-sidebar-info">
          <h2><?= $admin_name ?></h2>
          <p>Administrator</p>
        </div>
      </div>

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
        <li>
          <a href="<?= BASE_URL ?>/admin/insights.php"
            <?= $current_page === 'insights.php' ? 'class="active"' : '' ?>>
            <span class="snav-icon">
              <i class="fas fa-chart-line snav-ic-out"></i>
              <i class="fas fa-chart-line snav-ic-fill"></i>
            </span>
            <span class="snav-text">Insights</span>
          </a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/admin/profile_settings.php"
            <?= $current_page === 'profile_settings.php' ? 'class="active"' : '' ?>>
            <span class="snav-icon">
              <i class="fas fa-gear snav-ic-out"></i>
              <i class="fas fa-gear snav-ic-fill"></i>
            </span>
            <span class="snav-text">Profile Settings</span>
          </a>
        </li>
      </ul>

      <!-- Sidebar logout link -->
      <div class="sidebar-logout">
        <a href="<?= BASE_URL ?>/php/logout.php" class="sidebar-logout-btn">
          <span class="snav-icon">
            <i class="fas fa-right-from-bracket"></i>
          </span>
          <span class="snav-text">Log Out</span>
        </a>
      </div>
    </aside>

    <!-- Main content -->
    <main class="admin-wrapper">