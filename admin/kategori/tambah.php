<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

$err = '';

if (isset($_POST['simpan'])) {
  $nama = trim($_POST['nama_kategori'] ?? '');

  if ($nama === '') {
    $err = 'Nama kategori wajib diisi!';
  } else {
    $cek = mysqli_prepare($conn, "SELECT id FROM kategori WHERE nama_kategori = ? LIMIT 1");
    mysqli_stmt_bind_param($cek, "s", $nama);
    mysqli_stmt_execute($cek);
    $dup = mysqli_stmt_get_result($cek);

    if ($dup && mysqli_num_rows($dup) > 0) {
      $err = 'Nama kategori sudah ada!';
    } else {
      $ins = mysqli_prepare($conn, "INSERT INTO kategori (nama_kategori) VALUES (?)");
      mysqli_stmt_bind_param($ins, "s", $nama);
      if (mysqli_stmt_execute($ins)) {
        header("Location: index.php?msg=" . urlencode("Kategori berhasil ditambahkan!"));
        exit;
      } else {
        $err = 'Gagal menambahkan kategori!';
      }
    }
  }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content">
  <div class="page">
    <h1>➕ Tambah Kategori</h1>

    <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="POST" class="card" style="max-width:560px">
      <div style="display:flex;flex-direction:column;gap:10px">
        <label>Nama Kategori</label>
        <input type="text" name="nama_kategori" class="input" placeholder="Masukkan nama kategori" required>
      </div>
      <div class="toolbar" style="margin-top:14px">
        <button type="submit" name="simpan" class="btn" style="background:#2e7d32;color:#fff">Simpan</button>
        <a href="index.php" class="btn btn-secondary">Kembali</a>
      </div>
    </form>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
