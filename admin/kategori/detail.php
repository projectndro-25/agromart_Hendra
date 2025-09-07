<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$st = mysqli_prepare($conn, "SELECT id, nama_kategori FROM kategori WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($st, "i", $id);
mysqli_stmt_execute($st);
$kategori = mysqli_stmt_get_result($st);
if (!$kategori || mysqli_num_rows($kategori) === 0) {
  header("Location: index.php?msg=" . urlencode("Kategori tidak ditemukan"));
  exit;
}
$kat = mysqli_fetch_assoc($kategori);

$sp = mysqli_prepare($conn, "SELECT id, nama_produk, harga, stok FROM produk WHERE kategori_id = ? ORDER BY id DESC");
mysqli_stmt_bind_param($sp, "i", $id);
mysqli_stmt_execute($sp);
$produk = mysqli_stmt_get_result($sp);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
  <div class="page">
    <h1>🗂 Produk dalam Kategori: <b><?= htmlspecialchars($kat['nama_kategori']) ?></b></h1>

    <div class="toolbar">
      <a href="index.php" class="btn btn-secondary">⬅ Kembali</a>
      <a href="../produk/tambah.php?kategori_id=<?= (int)$id; ?>" class="btn" style="background:#16a34a;color:#fff">+ Tambah Produk</a>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th style="width:72px">NO</th>
            <th>Nama Produk</th>
            <th style="width:160px">Harga</th>
            <th style="width:120px">Stok</th>
            <th style="width:220px">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($produk && mysqli_num_rows($produk) > 0): $no=1; ?>
          <?php while ($row = mysqli_fetch_assoc($produk)): ?>
            <tr>
              <td><?= $no++; ?></td>
              <td><?= htmlspecialchars($row['nama_produk']); ?></td>
              <td>Rp <?= number_format((float)$row['harga'], 0, ',', '.'); ?></td>
              <td><?= (int)$row['stok']; ?></td>
              <td class="actions">
                <a href="../produk/edit.php?id=<?= (int)$row['id']; ?>" class="btn btn-warning">✏️ Edit</a>
                <a href="../produk/hapus.php?id=<?= (int)$row['id']; ?>" onclick="return confirm('Yakin hapus produk ini?')" class="btn btn-danger">🗑️ Hapus</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5" style="text-align:center;padding:16px">Belum ada produk.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
