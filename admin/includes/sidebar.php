<?php 
// sidebar.php
$BASE = '/agromart';
$ADMIN_BASE = $BASE . '/admin';
?>

<aside class="sidebar">
    <ul>
        <li><a href="<?= $ADMIN_BASE ?>/index.php">🏠 Dashboard</a></li>
        <li><a href="<?= $ADMIN_BASE ?>/produk/index.php">📦 Produk</a></li>
        <li><a href="<?= $ADMIN_BASE ?>/kategori/index.php">🗂 Kategori</a></li>
        <li><a href="<?= $ADMIN_BASE ?>/pesanan/index.php">🧾 Pesanan</a></li>

        <!-- ✅ arahkan ke halaman LIST -->
        <li><a href="<?= $ADMIN_BASE ?>/reseller/index.php">🛍️ Reseller</a></li>
        <li><a href="<?= $ADMIN_BASE ?>/reseller/pending.php">⏳ Pending Reseller</a></li>
        <li><a href="/admin/reseller/produk_terbaru.php">Produk Terbaru</a></li>


        <li><a href="<?= $ADMIN_BASE ?>/users/index.php">👥 Pengguna</a></li>
        <li><a href="<?= $ADMIN_BASE ?>/laporan.php">📊 Laporan</a></li>
    </ul>
</aside>
