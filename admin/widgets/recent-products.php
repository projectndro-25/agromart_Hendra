<?php
require_once __DIR__ . '/../includes/auth_admin.php';
$db = $conn ?? ($koneksi ?? null);
if (!$db) { die('Koneksi DB tidak ditemukan ($conn / $koneksi).'); }

$sql = "
SELECT 
  p.id, p.nama_produk, k.nama_kategori, p.harga, p.stok, p.created_at,
  r.nama_toko, u.nama AS nama_akun
FROM produk p
LEFT JOIN kategori k ON p.kategori_id = k.id
LEFT JOIN reseller r ON p.reseller_id = r.id
LEFT JOIN users u ON u.id = r.user_id
ORDER BY p.created_at DESC
LIMIT 10
";
$rs = $db->query($sql);
?>
<div class="card agw-panel">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0">Produk Terbaru</h6>
      <a class="btn btn-sm btn-light" href="produk/index.php"><i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Semua</a>
    </div>
    <table id="recentProductsTable" class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Nama Produk</th>
          <th>Kategori</th>
          <th>Harga</th>
          <th>Stok</th>
          <th>Reseller</th>
          <th>Tanggal</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $rs->fetch_assoc()):
          $namaReseller = $row['nama_toko'] ?: ($row['nama_akun'] ?? '-'); ?>
          <tr>
            <td><?= htmlspecialchars($row['nama_produk']) ?></td>
            <td><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
            <td>Rp <?= number_format((float)$row['harga'],0,',','.') ?></td>
            <td><?= (int)$row['stok'] ?></td>
            <td><?= htmlspecialchars($namaReseller) ?></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="produk/detail.php?id=<?= (int)$row['id'] ?>">Detail</a>
              <a class="btn btn-sm btn-outline-danger" href="produk/hapus.php?id=<?= (int)$row['id'] ?>" onclick="return confirm('Hapus produk ini?')">Hapus</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  if (window.DataTable) new DataTable('#recentProductsTable', { perPage: 10, searchable: true, fixedHeight: true });
});
</script>
