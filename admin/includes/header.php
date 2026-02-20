<?php
/**
 * Admin Header Include — admin/includes/header.php
 * Navbar style mirrors the customer-facing site exactly:
 *   ivory-cream background · Oleo Script brand · Outfit nav links
 *   underline active state · deep-maroon palette
 */

if (!defined('BASE_URL')) define('BASE_URL', '/purge-coffee');

$admin_name    = isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Admin';
$admin_initial = strtoupper(substr($admin_name, 0, 1));
$current_page  = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Purge Coffee — Admin</title>
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/images/coffee_beans_logo.png" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/admin/assets/css/admin-style.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="<?= BASE_URL ?>/css/footer-section.css?v=<?= time() ?>" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>

<!-- ══ TOP NAVBAR — mirrors customer-site header exactly ══════ -->
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
        <i class="fas fa-sign-out-alt"></i> Logout
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