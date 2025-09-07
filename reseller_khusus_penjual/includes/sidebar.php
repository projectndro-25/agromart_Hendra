<?php
// reseller_khusus_penjual/includes/sidebar.php

// Dapatkan path skrip saat ini, contoh:
// /agromart/reseller_khusus_penjual/produk/index.php
$script = $_SERVER['SCRIPT_NAME'] ?? '/';

// Cari segmen '/reseller_khusus_penjual/' agar konsisten di halaman mana pun
$needle = '/reseller_khusus_penjual/';
$pos = strpos($script, $needle);

// Jika ditemukan, ambil prefix sampai akhir folder panel
if ($pos !== false) {
  // hasil: /agromart/reseller_khusus_penjual
  $panelPath = substr($script, 0, $pos + strlen(rtrim($needle, '/')));
} else {
  // fallback aman (jika server tidak menampilkan script_name normal)
  $panelPath = '/agromart/reseller_khusus_penjual';
}

// Path root aplikasi (satu level di atas folder panel), contoh: /agromart
$appPath = rtrim(dirname($panelPath), '/');
?>
  <aside class="side">
    <div class="brand">🛍️ Reseller</div>
    <nav class="nav">
      <a href="<?= $panelPath ?>/index.php"><i class="fa-solid fa-gauge"></i> Dashboard</a>
      <a href="<?= $panelPath ?>/produk/index.php"><i class="fa-solid fa-box"></i> Produk Saya</a>
      <a href="<?= $panelPath ?>/pesanan/index.php"><i class="fa-solid fa-receipt"></i> Pesanan Toko</a>
      <a href="<?= $panelPath ?>/toko/profile.php"><i class="fa-solid fa-store"></i> Profil Toko</a>
      <a href="<?= $appPath ?>/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
  </aside>
  <main class="content">
