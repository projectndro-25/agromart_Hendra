<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_reseller.php';
require_once __DIR__ . '/../../config/db.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$uid = (int)$_SESSION['user_id'];

/* Rupiah helper (jaga-jaga kalau belum ada) */
if (!function_exists('rupiah')) {
  function rupiah($n){ return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
}

/* --- Deteksi kolom pemilik produk --- */
$ownerCol = null;
$hasUserId = mysqli_query($conn, "SHOW COLUMNS FROM produk LIKE 'user_id'");
$hasResId  = mysqli_query($conn, "SHOW COLUMNS FROM produk LIKE 'reseller_id'");
if ($hasUserId && mysqli_num_rows($hasUserId)) { $ownerCol = 'user_id'; }
elseif ($hasResId && mysqli_num_rows($hasResId)) { $ownerCol = 'reseller_id'; }

/* Jika owner pakai reseller_id, ambil reseller.id milik user login */
$resellerId = null;
if ($ownerCol === 'reseller_id') {
  $qr = mysqli_query($conn, "SELECT id FROM reseller WHERE user_id=$uid LIMIT 1");
  if ($qr && mysqli_num_rows($qr)) { $row = mysqli_fetch_assoc($qr); $resellerId = (int)$row['id']; }
}

/* Query daftar produk milik toko */
$rs = false;
if ($ownerCol === 'user_id') {
  $sql = "
    SELECT p.id, p.nama_produk, p.harga, p.stok, k.nama_kategori, p.created_at
    FROM produk p
    LEFT JOIN kategori k ON k.id = p.kategori_id
    WHERE p.user_id = $uid
    ORDER BY p.created_at DESC
  ";
  $rs = mysqli_query($conn, $sql);
} elseif ($ownerCol === 'reseller_id' && $resellerId !== null) {
  $sql = "
    SELECT p.id, p.nama_produk, p.harga, p.stok, k.nama_kategori, p.created_at
    FROM produk p
    LEFT JOIN kategori k ON k.id = p.kategori_id
    WHERE p.reseller_id = $resellerId
    ORDER BY p.created_at DESC
  ";
  $rs = mysqli_query($conn, $sql);
}
?>
<h1>📦 Produk Saya</h1>
<div class="card" style="margin-top:12px"><div class="card-body">

  <?php if (!$ownerCol): ?>
    <div class="badge badge-warn" style="margin-bottom:10px">
      Struktur tabel <b>produk</b> tidak memiliki kolom <code>user_id</code> atau <code>reseller_id</code>.
      Tambahkan salah satu untuk menandai pemilik produk.
    </div>
  <?php elseif ($ownerCol==='reseller_id' && $resellerId===null): ?>
    <div class="badge badge-warn" style="margin-bottom:10px">
      Toko kamu belum memiliki baris di tabel <b>reseller</b>. Hubungi admin.
    </div>
  <?php endif; ?>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
    <div class="muted">Semua produk milik toko kamu</div>
    <a class="btn btn-primary" href="tambah.php"><i class="fa-solid fa-plus"></i> Tambah Produk</a>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Nama</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Tanggal</th>
          <th style="width:140px">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if($rs && mysqli_num_rows($rs)): while($p=mysqli_fetch_assoc($rs)): ?>
        <tr>
          <td><?= htmlspecialchars($p['nama_produk']) ?></td>
          <td><?= htmlspecialchars($p['nama_kategori'] ?? '-') ?></td>
          <td><?= rupiah($p['harga']) ?></td>
          <td><?= (int)$p['stok'] ?></td>
          <td><?= htmlspecialchars($p['created_at']) ?></td>
          <td>
            <a class="btn btn-sm btn-primary" href="edit.php?id=<?= (int)$p['id'] ?>">
              <i class="fa-solid fa-pen"></i> Edit
            </a>
            <a class="btn btn-sm" style="background:#e74c3c;color:#fff"
               href="hapus.php?id=<?= (int)$p['id'] ?>"
               onclick="return confirm('Hapus produk ini?')">
              <i class="fa-solid fa-trash"></i> Hapus
            </a>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="6" class="muted">Belum ada produk.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
