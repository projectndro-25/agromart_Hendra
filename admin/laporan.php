<?php
include 'auth_admin.php';
include 'includes/header.php';
include 'includes/sidebar.php';
require_once __DIR__ . '/../includes/auth_admin.php';
?>

<h1>Laporan Transaksi</h1>
<p>Data laporan transaksi penjualan produk di AgroMart.</p>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>ID Transaksi</th>
        <th>Tanggal</th>
        <th>Produk</th>
        <th>Jumlah</th>
        <th>Total Harga</th>
    </tr>
    <tr>
        <td>1001</td>
        <td>2025-08-27</td>
        <td>Pupuk Organik</td>
        <td>5</td>
        <td>Rp 250.000</td>
    </tr>
    <tr>
        <td>1002</td>
        <td>2025-08-27</td>
        <td>Benih Padi</td>
        <td>10</td>
        <td>Rp 500.000</td>
    </tr>
</table>

<?php include 'includes/footer.php'; ?>
