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
    'dashboard.php'  => 'dashboard.css',
    'products.php'   => 'products.css',
    'orders.php'     => 'orders.css',
    'customers.php'  => 'customers.css',
];
$page_css_file = $page_css_map[$current_page] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Purge Coffee — Admin</title>
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/images/coffee_beans_logo.png" />

  <!-- Shared admin styles (formerly admin-style.css) -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin.css?v=<?= time() ?>" />

  <!-- Page-specific styles -->
  <?php if ($page_css_file): ?>
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/<?= $page_css_file ?>?v=<?= time() ?>" />
  <?php endif; ?>

  <link rel="stylesheet" href="<?= BASE_URL ?>/css/footer-section.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <!-- ══ Shared Table Sorting Utility — defined here so page scripts can call it ══ -->
  <script>
  /**
   * initSortableTable(tableId)
   * Makes every <th data-sort="..."> in the table header clickable.
   *
   * data-sort values:
   *   "number"  — strips non-numeric chars (₱, #, commas), sorts numerically
   *               Toggle: lowest → highest ↔ highest → lowest
   *   "date"    — parses "Mon DD, YYYY" and "Mon DD, YYYY hh:mm AM/PM" formats
   *               Toggle: oldest → newest ↔ newest → oldest
   *   "status"  — alphabetical sort on badge/cell text  (A→Z ↔ Z→A)
   *   "text"    — locale-aware alphabetical             (A→Z ↔ Z→A)
   *
   * Clicking the same header a second time reverses direction.
   * First click is always ascending.
   * Columns without data-sort (e.g. "Actions") are never sortable.
   */
  function initSortableTable(tableId) {
    var table = document.getElementById(tableId);
    if (!table) return;

    var headers    = table.querySelectorAll('thead th[data-sort]');
    var currentCol = -1;
    var currentDir = 'asc';

    /* Parse PHP date() strings:
       "Feb 20, 2026"          (M d, Y)
       "Feb 20, 2026 03:45 PM" (M d, Y h:i A)
       Returns a timestamp, or 0 on failure.                          */
    function parseAdminDate(str) {
      if (!str) return 0;
      var d = new Date(str.replace(',', ''));   // remove comma so Date() is happy
      if (!isNaN(d)) return d.getTime();
      d = new Date(str);
      return isNaN(d) ? 0 : d.getTime();
    }

    headers.forEach(function (th) {
      th.addEventListener('click', function () {
        var col  = th.cellIndex;
        var type = th.dataset.sort;

        /* Toggle direction if same column; reset to asc for new column */
        if (currentCol === col) {
          currentDir = (currentDir === 'asc') ? 'desc' : 'asc';
        } else {
          currentCol = col;
          currentDir = 'asc';
        }

        /* Visual highlight on active column */
        table.querySelectorAll('thead th[data-sort]').forEach(function (h) {
          h.classList.remove('sort-asc', 'sort-desc');
        });
        th.classList.add(currentDir === 'asc' ? 'sort-asc' : 'sort-desc');

        /* Collect rows, skipping the empty-state placeholder */
        var tbody = table.querySelector('tbody');
        var rows  = Array.from(tbody.querySelectorAll('tr')).filter(function (r) {
          return !r.querySelector('.empty-state');
        });
        if (rows.length === 0) return;

        rows.sort(function (a, b) {
          var aText = a.cells[col] ? a.cells[col].textContent.trim() : '';
          var bText = b.cells[col] ? b.cells[col].textContent.trim() : '';
          var cmp   = 0;

          if (type === 'number') {
            var aNum = parseFloat(aText.replace(/[^0-9.-]/g, '')) || 0;
            var bNum = parseFloat(bText.replace(/[^0-9.-]/g, '')) || 0;
            cmp = aNum - bNum;

          } else if (type === 'date') {
            cmp = parseAdminDate(aText) - parseAdminDate(bText);

          } else {
            /* text / status — locale-aware case-insensitive */
            cmp = aText.toLowerCase().localeCompare(bText.toLowerCase());
          }

          return (currentDir === 'asc') ? cmp : -cmp;
        });

        rows.forEach(function (r) { tbody.appendChild(r); });
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

<!-- ══ BODY WRAPPER: sidebar + main ═════════════════════════════ -->
<div class="admin-body">

  <!-- ── Sidebar Navigation ───────────────────────────────────── -->
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

  <!-- ── Main Content ─────────────────────────────────────────── -->
  <main class="admin-wrapper">