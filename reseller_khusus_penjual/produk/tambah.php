<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_reseller.php'; // set $_SESSION['reseller_id']
require_once __DIR__ . '/../../config/db.php';

$uid        = (int)($_SESSION['user_id'] ?? 0);
$resellerId = (int)($_SESSION['reseller_id'] ?? 0);
$msg = '';

/* Safety: reseller harus ada */
if ($resellerId <= 0) {
  $msg = 'Toko kamu belum terdaftar. Hubungi admin.';
}

/* --- Daftar satuan yang diizinkan --- */
$allowedUnits = [
  'pcs','unit','set','lusin','kodi',
  'kg','gram','liter','ml',
  'pack','box','dus','karung','sak','botol','rim',
  'meter','cm'
];

/* Cek apakah tabel produk punya kolom `satuan` */
$colSatuanExists = false;
$chk = mysqli_query($conn, "SHOW COLUMNS FROM produk LIKE 'satuan'");
if ($chk && mysqli_num_rows($chk) > 0) { $colSatuanExists = true; }

/* Cek apakah tabel produk punya kolom foto (pilih salah satu nama) */
$photoCol = null;
foreach (['foto','gambar','image','thumbnail'] as $cand) {
  $c = mysqli_query($conn, "SHOW COLUMNS FROM produk LIKE '$cand'");
  if ($c && mysqli_num_rows($c) > 0) { $photoCol = $cand; break; }
}

/* Cek ketersediaan tabel produk_videos (biar insert video aman) */
$hasProdukVideos = false;
$tv = mysqli_query($conn, "SHOW TABLES LIKE 'produk_videos'");
if ($tv && mysqli_num_rows($tv) > 0) { $hasProdukVideos = true; }

if ($_SERVER['REQUEST_METHOD']==='POST' && !$msg) {
  $nama   = trim($_POST['nama'] ?? '');
  $katId  = (int)($_POST['kategori_id'] ?? 0);
  $harga  = (float)($_POST['harga'] ?? 0);
  $stok   = (int)($_POST['stok'] ?? 0);

  // satuan (default pcs)
  $satuan = strtolower(trim($_POST['satuan'] ?? 'pcs'));
  if (!in_array($satuan, $allowedUnits, true)) { $satuan = 'pcs'; }

  /* ======================== Upload Foto (opsional) ======================== */
  $photoValue = null; // akan berisi "uploads/products/xxx.ext"
  if (!empty($_FILES['foto']['name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
    $allowedExt = ['jpg','jpeg','png','webp'];
    $maxSize    = 3 * 1024 * 1024; // 3MB

    $origName = $_FILES['foto']['name'];
    $tmpPath  = $_FILES['foto']['tmp_name'];
    $size     = (int)$_FILES['foto']['size'];

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
      $msg = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';
    } elseif ($size > $maxSize) {
      $msg = 'Ukuran foto maksimal 3MB.';
    } else {
      $uploadRoot = __DIR__ . '/../../uploads';
      if (!is_dir($uploadRoot)) { @mkdir($uploadRoot, 0775, true); }
      $prodDir = $uploadRoot . DIRECTORY_SEPARATOR . 'products';
      if (!is_dir($prodDir)) { @mkdir($prodDir, 0775, true); }

      $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
      $newName  = 'prd_' . time() . '_' . substr(sha1($safeBase . mt_rand()), 0, 8) . '.' . $ext;
      $dest     = $prodDir . DIRECTORY_SEPARATOR . $newName;

      if (move_uploaded_file($tmpPath, $dest)) {
        $photoValue = 'uploads/products/' . $newName;
      } else {
        $msg = 'Gagal mengunggah foto.';
      }
    }
  }
  /* ====================== END Upload Foto (opsional) ====================== */

  /* ================== Video testimoni (URL atau File; opsional) ================== */
  // prioritas: kalau ada file, pakai file; kalau tidak, pakai URL (YouTube/link langsung)
  $videoTitleForm = trim($_POST['video_title'] ?? '');
  $videoTitle     = ($videoTitleForm !== '') ? $videoTitleForm : 'Testimoni singkat';
  $videoUrlInput  = trim($_POST['video_url'] ?? ''); // bisa YouTube atau link langsung
  $finalVideoUrl  = null;                            // yang nanti disimpan ke tabel produk_videos

  // 1) Jika upload file video
  if (!$msg && !empty($_FILES['video_file']['name']) && is_uploaded_file($_FILES['video_file']['tmp_name'])) {
    $vAllowedExt = ['mp4','m4v','webm','ogv','ogg','mov','mkv','avi','3gp'];
    // Note: pastikan upload_max_filesize & post_max_size di php.ini cukup besar untuk video
    $vMaxSize    = 100 * 1024 * 1024; // 100MB (sesuaikan kebutuhan)

    $vOrig  = $_FILES['video_file']['name'];
    $vTmp   = $_FILES['video_file']['tmp_name'];
    $vSize  = (int)$_FILES['video_file']['size'];
    $vExt   = strtolower(pathinfo($vOrig, PATHINFO_EXTENSION));

    if (!in_array($vExt, $vAllowedExt, true)) {
      $msg = 'Format video tidak didukung. Gunakan: '.implode(', ', $vAllowedExt).'.';
    } elseif ($vSize > $vMaxSize) {
      $msg = 'Ukuran video terlalu besar (maks 100MB).';
    } else {
      $uploadRoot = __DIR__ . '/../../uploads';
      $vidDir = $uploadRoot . DIRECTORY_SEPARATOR . 'resellers' . DIRECTORY_SEPARATOR . 'videos';
      if (!is_dir($vidDir)) { @mkdir($vidDir, 0775, true); }

      $baseName = preg_replace('/[^a-zA-Z0-9_\-]/','_', pathinfo($vOrig, PATHINFO_FILENAME));
      $newVid   = 'vid_' . time() . '_' . substr(sha1($baseName . mt_rand()), 0, 8) . '.' . $vExt;
      $destVid  = $vidDir . DIRECTORY_SEPARATOR . $newVid;

      if (move_uploaded_file($vTmp, $destVid)) {
        $finalVideoUrl = 'uploads/resellers/videos/' . $newVid; // relative path untuk dipakai di front-end
      } else {
        $msg = 'Gagal mengunggah file video.';
      }
    }
  }

  // 2) Jika tidak upload file tapi ada URL
  if (!$finalVideoUrl && $videoUrlInput !== '') {
    // Simpan apa adanya; embed di front-end akan otomatis meng-handle YouTube/path lokal
    $finalVideoUrl = $videoUrlInput;
  }
  /* ================= END Video testimoni ================= */

  if ($nama === '' && !$msg) {
    $msg = 'Nama produk wajib diisi.';
  }

  if (!$msg) {
    // Validasi kategori agar tidak gagal oleh FK
    $ck = mysqli_query($conn, "SELECT id FROM kategori WHERE id=$katId LIMIT 1");
    if (!$ck || !mysqli_num_rows($ck)) {
      $msg = 'Kategori tidak valid.';
    } else {
      // Siapkan INSERT sesuai struktur kolom
      if ($colSatuanExists && $photoCol && $photoValue !== null) {
        $stmt = mysqli_prepare($conn, "
          INSERT INTO produk (nama_produk, kategori_id, reseller_id, harga, stok, satuan, `$photoCol`, status, created_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', NOW())
        ");
        mysqli_stmt_bind_param($stmt, 'siiisss', $nama, $katId, $resellerId, $harga, $stok, $satuan, $photoValue);
      } elseif ($colSatuanExists && (!$photoCol || $photoValue === null)) {
        $stmt = mysqli_prepare($conn, "
          INSERT INTO produk (nama_produk, kategori_id, reseller_id, harga, stok, satuan, status, created_at)
          VALUES (?, ?, ?, ?, ?, ?, 'aktif', NOW())
        ");
        mysqli_stmt_bind_param($stmt, 'siiiss', $nama, $katId, $resellerId, $harga, $stok, $satuan);
      } elseif (!$colSatuanExists && $photoCol && $photoValue !== null) {
        $stmt = mysqli_prepare($conn, "
          INSERT INTO produk (nama_produk, kategori_id, reseller_id, harga, stok, `$photoCol`, status, created_at)
          VALUES (?, ?, ?, ?, ?, ?, 'aktif', NOW())
        ");
        mysqli_stmt_bind_param($stmt, 'siiiss', $nama, $katId, $resellerId, $harga, $stok, $photoValue);
      } else {
        $stmt = mysqli_prepare($conn, "
          INSERT INTO produk (nama_produk, kategori_id, reseller_id, harga, stok, status, created_at)
          VALUES (?, ?, ?, ?, ?, 'aktif', NOW())
        ");
        mysqli_stmt_bind_param($stmt, 'siiii', $nama, $katId, $resellerId, $harga, $stok);
      }

      if ($stmt && mysqli_stmt_execute($stmt)) {
        $newProdukId = (int)mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Jika ada video & tabel produk_videos tersedia => simpan otomatis
        if ($hasProdukVideos && $finalVideoUrl) {
          $iv = mysqli_prepare($conn, "
            INSERT INTO produk_videos (produk_id, reseller_id, title, video_url, created_at)
            VALUES (?, ?, ?, ?, NOW())
          ");
          if ($iv) {
            mysqli_stmt_bind_param($iv, 'iiss', $newProdukId, $resellerId, $videoTitle, $finalVideoUrl);
            mysqli_stmt_execute($iv);
            mysqli_stmt_close($iv);
          }
        }

        header('Location: index.php');
        exit;
      } else {
        $msg = 'Gagal menyimpan produk: '.mysqli_error($conn);
        if ($stmt) { mysqli_stmt_close($stmt); }
        // Rollback file jika gagal insert produk
        if (!empty($photoValue) && file_exists(__DIR__ . '/../../' . $photoValue)) {
          @unlink(__DIR__ . '/../../' . $photoValue);
        }
        // Video tidak dihapus supaya bisa dipakai ulang jika mau submit lagi
      }
    }
  }
}

/* Ambil list kategori aktif */
$kat = mysqli_query($conn, "SELECT id, nama_kategori FROM kategori WHERE (status='aktif' OR status IS NULL) ORDER BY nama_kategori");

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>➕ Tambah Produk</h1>
<div class="card" style="margin-top:12px"><div class="card-body">
  <?php if($msg): ?><div class="badge badge-warn" style="margin-bottom:8px"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <!-- penting: enctype untuk upload -->
  <form method="post" enctype="multipart/form-data">
    <div style="margin-bottom:10px">
      <label>Nama Produk</label><br>
      <input type="text" name="nama" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px" required>
    </div>

    <div style="margin-bottom:10px">
      <label>Kategori</label><br>
      <select name="kategori_id" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px" required>
        <?php if($kat): while($k=mysqli_fetch_assoc($kat)): ?>
          <option value="<?= (int)$k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></option>
        <?php endwhile; endif; ?>
      </select>
    </div>

    <div style="display:flex; gap:10px; margin-bottom:10px; align-items:flex-end">
      <div style="flex:1">
        <label>Harga</label><br>
        <input type="number" name="harga" min="0" step="1" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px" required>
      </div>
      <div style="flex:1">
        <label>Stok</label><br>
        <input type="number" name="stok" min="0" step="1" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px" required>
      </div>
      <div style="flex:1">
        <label>Satuan</label><br>
        <select name="satuan" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px">
          <?php
          $options = ['pcs','unit','kg','gram','liter','ml','pack','box','dus','karung','sak','botol','meter','cm','lusin','kodi','set','rim'];
          foreach ($options as $opt): ?>
            <option value="<?= $opt ?>"><?= strtoupper($opt) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if(!$colSatuanExists): ?>
          <div class="muted" style="font-size:12px;margin-top:6px">
            *Catatan: kolom <b>satuan</b> belum ada di tabel <code>produk</code>, nilai ini tidak akan disimpan.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div style="margin-bottom:10px">
      <label>Foto Produk (JPG/PNG/WEBP, maks 3MB)</label><br>
      <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px">
      <?php if(!$photoCol): ?>
        <div class="muted" style="font-size:12px;margin-top:6px">
          *Catatan: tabel <code>produk</code> belum punya kolom foto (<code>foto/gambar/image/thumbnail</code>), jadi nama file tidak disimpan ke database. File tetap diupload ke <code>uploads/products/</code>.
        </div>
      <?php endif; ?>
    </div>

    <!-- =================== Video Testimoni (opsional) =================== -->
    <div style="margin:18px 0 8px">
      <strong>Video Testimoni (opsional)</strong>
      <div class="muted" style="font-size:12px;margin-top:4px">
        Isi salah satu: URL YouTube / link video <em>atau</em> upload file video.
      </div>
    </div>

    <div style="margin-bottom:8px">
      <label>Judul Video</label><br>
      <input type="text" name="video_title" placeholder="Contoh: Testimoni singkat" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px">
    </div>

    <div style="margin-bottom:8px">
      <label>URL Video (YouTube/link langsung)</label><br>
      <input type="text" name="video_url" placeholder="https://youtu.be/xxxx atau https://..." style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px">
    </div>

    <div style="margin-bottom:16px">
      <label>Atau Upload File Video</label><br>
      <input type="file" name="video_file" accept="video/*" style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px">
      <div class="muted" style="font-size:12px;margin-top:6px">
        Format yang didukung: MP4, M4V, WEBM, OGV/OGG, MOV, MKV, AVI, 3GP. Batas bawaan 100MB (sesuaikan di php.ini jika perlu).
      </div>
    </div>
    <!-- ================= END Video Testimoni ================= -->

    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i> Simpan</button>
    <a class="btn" href="index.php">Batal</a>
  </form>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
