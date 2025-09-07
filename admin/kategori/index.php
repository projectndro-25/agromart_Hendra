<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

$sql = "
  SELECT k.id, k.nama_kategori, COUNT(p.id) AS jml_produk
  FROM kategori k
  LEFT JOIN produk p ON p.kategori_id = k.id
  GROUP BY k.id, k.nama_kategori
  ORDER BY k.id DESC
";
$result = mysqli_query($conn, $sql);
$msg = trim($_GET['msg'] ?? '');

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
  <div class="page">
    <h1>🗂 Kategori</h1>

    <?php if ($msg): ?>
      <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="toolbar">
      <a class="btn" style="background:#2e7d32;color:#fff" href="tambah.php">+ Tambah Kategori</a>
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th style="width:72px">NO</th>
            <th>Nama Kategori</th>
            <th style="width:160px">Jumlah Produk</th>
            <th style="width:260px">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($result && mysqli_num_rows($result) > 0): $no=1; ?>
          <?php while ($r = mysqli_fetch_assoc($result)): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= htmlspecialchars($r['nama_kategori']) ?></td>
              <td><span class="badge badge-primary" style="background:#bbdefb;color:#0d47a1"><?= (int)$r['jml_produk'] ?></span></td>
              <td class="actions">
                <a class="btn" href="detail.php?id=<?= (int)$r['id'] ?>">Detail</a>
                <a class="btn btn-warning" href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
                <a class="btn btn-danger" href="hapus.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Hapus kategori ini?')">Hapus</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="4" style="text-align:center;padding:16px">Belum ada kategori.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
