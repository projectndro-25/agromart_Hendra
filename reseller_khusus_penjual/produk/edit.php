<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_reseller.php';
require_once __DIR__ . '/../../config/db.php';

$uid        = (int)$_SESSION['user_id'];
$resellerId = 0;
/* cari reseller.id milik user */
$qRes = mysqli_query($conn, "SELECT id FROM reseller WHERE user_id=$uid LIMIT 1");
if ($qRes && mysqli_num_rows($qRes)) { $resellerId = (int)mysqli_fetch_assoc($qRes)['id']; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0 || $resellerId <= 0) { header('Location: index.php'); exit; }

/* deteksi kolom satuan & kolom foto */
$colSatuanExists = false;
$chkSat = mysqli_query($conn, "SHOW COLUMNS FROM produk LIKE 'satuan'");
if ($chkSat && mysqli_num_rows($chkSat)) $colSatuanExists = true;

$photoCol = null;
foreach (['foto','gambar','image','thumbnail'] as $cand) {
  $c = mysqli_query($conn, "SHOW COLUMNS FROM produk LIKE '$cand'");
  if ($c && mysqli_num_rows($c)) { $photoCol = $cand; break; }
}

/* ambil produk & pastikan milik reseller ini */
$cols = "id,nama_produk,deskripsi,harga,stok,kategori_id,status" . ($colSatuanExists ? ",satuan" : "") . ($photoCol ? ",`$photoCol` AS foto_db" : "");
$st = mysqli_prepare($conn, "SELECT $cols FROM produk WHERE id=? AND reseller_id=? LIMIT 1");
mysqli_stmt_bind_param($st, 'ii', $id, $resellerId);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
$produk = $r ? mysqli_fetch_assoc($r) : null;
mysqli_stmt_close($st);

if (!$produk) { header('Location: index.php'); exit; }

$msg = '';

/* list kategori */
$kat = mysqli_query($conn, "SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama   = trim($_POST['nama'] ?? '');
  $desk   = trim($_POST['deskripsi'] ?? '');
  $katId  = (int)($_POST['kategori_id'] ?? 0);
  $harga  = (float)($_POST['harga'] ?? 0);
  $stok   = (int)($_POST['stok'] ?? 0);
  $satuan = $colSatuanExists ? strtolower(trim($_POST['satuan'] ?? 'pcs')) : null;

  if ($nama === '') $msg = 'Nama wajib diisi.';
  if (!$msg) {
    // validasi kategori ada
    $ck = mysqli_query($conn, "SELECT id FROM kategori WHERE id=$katId LIMIT 1");
    if (!$ck || !mysqli_num_rows($ck)) $msg = 'Kategori tidak valid.';
  }

  /* upload foto (opsional) */
  $newPhotoVal = null;
  if (!$msg && $photoCol && !empty($_FILES['foto']['name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
    $allowedExt = ['jpg','jpeg','png','webp'];
    $maxSize    = 3 * 1024 * 1024;
    $origName   = $_FILES['foto']['name'];
    $tmp        = $_FILES['foto']['tmp_name'];
    $size       = (int)$_FILES['foto']['size'];
    $ext        = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext,$allowedExt,true))      $msg = 'Format foto harus JPG/PNG/WEBP.';
    elseif ($size > $maxSize)                  $msg = 'Ukuran foto maksimal 3MB.';
    else {
      $baseUpload = realpath(__DIR__ . '/../../uploads');
      if ($baseUpload === false) { mkdir(__DIR__ . '/../../uploads', 0775, true); $baseUpload = realpath(__DIR__ . '/../../uploads'); }
      $prodDir = $baseUpload . DIRECTORY_SEPARATOR . 'products';
      if (!is_dir($prodDir)) mkdir($prodDir, 0775, true);

      $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
      $newName  = 'prd_' . time() . '_' . substr(sha1($safeBase . mt_rand()),0,8) . '.' . $ext;
      $dest     = $prodDir . DIRECTORY_SEPARATOR . $newName;

      if (move_uploaded_file($tmp, $dest)) {
        $newPhotoVal = 'uploads/products/' . $newName;
        // hapus foto lama bila ada dan disimpan relatif
        if (!empty($produk['foto_db'])) {
          $old = $produk['foto_db'];
          $try1 = __DIR__ . '/../../' . ltrim($old,'/');
          $try2 = __DIR__ . '/../../uploads/products/' . basename($old);
          if (is_file($try1)) @unlink($try1); elseif (is_file($try2)) @unlink($try2);
        }
      } else {
        $msg = 'Gagal mengunggah foto.';
      }
    }
  }

  if (!$msg) {
    /* build UPDATE sesuai kolom yang ada */
    $sets = "nama_produk=?, deskripsi=?, harga=?, stok=?, kategori_id=?";
    $types = "ssdii";
    $params = [$nama, $desk, $harga, $stok, $katId];

    if ($colSatuanExists) { $sets .= ", satuan=?";  $types .= "s"; $params[] = $satuan; }
    if ($photoCol && $newPhotoVal !== null) { $sets .= ", `$photoCol`=?"; $types .= "s"; $params[] = $newPhotoVal; }

    $sets .= " WHERE id=? AND reseller_id=?";
    $types .= "ii";
    $params[] = $id; $params[] = $resellerId;

    $sql = "UPDATE produk SET $sets";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if ($stmt && mysqli_stmt_execute($stmt)) {
      mysqli_stmt_close($stmt);
      $_SESSION['success'] = 'Produk berhasil diperbarui.';
      header('Location: index.php'); exit;
    } else {
      $msg = 'Gagal memperbarui produk: '.mysqli_error($conn);
      if ($stmt) mysqli_stmt_close($stmt);
    }
  }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>✏️ Edit Produk</h1>
<div class="card" style="margin-top:12px"><div class="card-body">
  <?php if($msg): ?><div class="badge badge-warn" style="margin-bottom:10px"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div style="margin-bottom:10px">
      <label>Nama Produk</label><br>
      <input type="text" name="nama" value="<?= htmlspecialchars($produk['nama_produk']) ?>" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px" required>
    </div>

    <div style="margin-bottom:10px">
      <label>Deskripsi</label><br>
      <textarea name="deskripsi" rows="4" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px"><?= htmlspecialchars($produk['deskripsi'] ?? '') ?></textarea>
    </div>

    <div style="display:flex; gap:10px; margin-bottom:10px; align-items:flex-end">
      <div style="flex:1">
        <label>Harga</label><br>
        <input type="number" name="harga" min="0" step="1" value="<?= (float)$produk['harga'] ?>" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px" required>
      </div>
      <div style="flex:1">
        <label>Stok</label><br>
        <input type="number" name="stok" min="0" step="1" value="<?= (int)$produk['stok'] ?>" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px" required>
      </div>
      <div style="flex:1">
        <label>Kategori</label><br>
        <select name="kategori_id" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px" required>
          <?php if($kat): while($k=mysqli_fetch_assoc($kat)): ?>
            <option value="<?= (int)$k['id'] ?>" <?= ((int)$k['id']===(int)$produk['kategori_id'])?'selected':'' ?>>
              <?= htmlspecialchars($k['nama_kategori']) ?>
            </option>
          <?php endwhile; endif; ?>
        </select>
      </div>
    </div>

    <?php if($colSatuanExists): ?>
      <div style="margin-bottom:10px">
        <label>Satuan</label><br>
        <input type="text" name="satuan" value="<?= htmlspecialchars($produk['satuan'] ?? 'pcs') ?>" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px">
      </div>
    <?php endif; ?>

    <?php if($photoCol): ?>
      <div style="margin-bottom:10px">
        <label>Foto Produk (opsional, ganti)</label><br>
        <?php if(!empty($produk['foto_db'])): ?>
          <img src="/agromart/<?= htmlspecialchars(ltrim($produk['foto_db'],'/')) ?>" alt="" width="120" style="border-radius:8px;display:block;margin-bottom:6px">
        <?php endif; ?>
        <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px">
      </div>
    <?php endif; ?>

    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i> Simpan</button>
    <a class="btn" href="index.php">Batal</a>
  </form>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
