<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$st = mysqli_prepare($conn, "SELECT id, nama_kategori FROM kategori WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($st, "i", $id);
mysqli_stmt_execute($st);
$data = mysqli_stmt_get_result($st);
if (!$data || mysqli_num_rows($data) === 0) {
  header("Location: index.php?msg=" . urlencode("Kategori tidak ditemukan"));
  exit;
}
$row = mysqli_fetch_assoc($data);

$err = '';
if (isset($_POST['update'])) {
  $nama = trim($_POST['nama_kategori'] ?? '');

  if ($nama === '') {
    $err = 'Nama kategori wajib diisi.';
  } else {
    $cek = mysqli_prepare($conn, "SELECT id FROM kategori WHERE nama_kategori = ? AND id <> ? LIMIT 1");
    mysqli_stmt_bind_param($cek, "si", $nama, $id);
    mysqli_stmt_execute($cek);
    $dup = mysqli_stmt_get_result($cek);

    if ($dup && mysqli_num_rows($dup) > 0) {
      $err = 'Nama kategori sudah digunakan.';
    } else {
      $upd = mysqli_prepare($conn, "UPDATE kategori SET nama_kategori = ? WHERE id = ?");
      mysqli_stmt_bind_param($upd, "si", $nama, $id);
      if (mysqli_stmt_execute($upd)) {
        header("Location: index.php?msg=" . urlencode("Kategori berhasil diperbarui!"));
        exit;
      } else {
        $err = 'Gagal memperbarui kategori!';
      }
    }
  }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
  <div class="page">
    <h1>✏️ Edit Kategori</h1>

    <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="POST" class="card" style="max-width:560px">
      <div style="display:flex;flex-direction:column;gap:10px">
        <label>Nama Kategori</label>
        <input type="text" name="nama_kategori" value="<?= htmlspecialchars($row['nama_kategori']); ?>" class="input" required>
      </div>
      <div class="toolbar" style="margin-top:14px">
        <button type="submit" name="update" class="btn" style="background:#2e7d32;color:#fff">Update</button>
        <a href="index.php" class="btn btn-secondary">Kembali</a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
