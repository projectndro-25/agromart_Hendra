<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Sesuaikan dengan folder proyek kamu di localhost
$BASE = '/agromart';
$ADMIN_BASE = $BASE . '/admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="AgroMart - Sistem Manajemen Produk dan Penjualan" />
  <meta name="author" content="AgroMart Team" />
  <title>AgroMart Admin</title>

  <!-- Global Styles -->
  <link rel="stylesheet" href="<?= $BASE ?>/assets/css/style.css">

  <!-- (Opsional) Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= $BASE ?>/assets/favicon.png">
</head>
<body>
  <!-- Navbar -->
  <nav class="navbar">
    <div class="navbar-left">
      <!-- Tombol toggle sidebar (muncul hanya di HP/Tablet) -->
      <span class="menu-toggle">☰</span>
      🌾 <span class="brand">AgroMart Admin</span>
    </div>
    <div class="navbar-right">
      <span class="user-info">
        Halo, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?> 
        (<?= htmlspecialchars($_SESSION['role'] ?? '-') ?>)
      </span>
      <a class="logout-link" href="<?= $BASE ?>/logout.php">Logout</a>
    </div>
  </nav>

  <!-- Main Wrapper -->
  <div class="main">
