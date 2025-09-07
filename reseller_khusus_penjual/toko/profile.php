<?php
// reseller_khusus_penjual/toko/profile.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../includes/auth_reseller.php'; // seharusnya set $_SESSION['user_id']
require_once __DIR__ . '/../../config/db.php';

$uid = (int)($_SESSION['user_id'] ?? 0);
$ok = '';
$err = '';

/* ---------- Helper: cari kolom logo di tabel reseller ---------- */
function detect_reseller_logo_column(mysqli $conn): ?string {
  $candidates = ['logo','foto_logo','avatar','foto','image'];
  foreach ($candidates as $col) {
    $rs = mysqli_query($conn, "SHOW COLUMNS FROM reseller LIKE '$col'");
    if ($rs && mysqli_num_rows($rs) > 0) return $col;
  }
  return null;
}
$logoCol = detect_reseller_logo_column($conn);

/* ---------- Ambil data reseller + akun user ---------- */
$q = mysqli_query($conn, "
  SELECT r.*, u.email, u.no_hp
  FROM reseller r
  JOIN users u ON u.id = r.user_id
  WHERE r.user_id = {$uid}
  LIMIT 1
");
$R = ($q && mysqli_num_rows($q)) ? mysqli_fetch_assoc($q) : null;

/* Jika belum ada profil toko (mis. status pending), arahkan */
if (!$R) {
  header('Location: ../../pages/reseller_pending.php');
  exit;
}

/* ---------- Proses submit ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Ambil input
  $nama_toko = mysqli_real_escape_string($conn, $_POST['nama_toko'] ?? '');
  $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
  $alamat    = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
  $no_hp_raw = trim($_POST['no_hp'] ?? '');

  // Sanitasi sederhana: hanya +, spasi, -, dan angka
  $no_hp = preg_replace('~[^0-9+\-\s]~', '', $no_hp_raw);

  // Siapkan nilai/logo yang akan disimpan (jika ada upload)
  $newLogoPath = null;

  // Jika ada input file & ada kolom logo di database
  if ($logoCol && !empty($_FILES['logo']['name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
    $allowedExt = ['jpg','jpeg','png','webp'];
    $maxSize    = 3 * 1024 * 1024; // 3 MB

    $origName = $_FILES['logo']['name'];
    $tmpPath  = $_FILES['logo']['tmp_name'];
    $size     = (int)$_FILES['logo']['size'];

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
      $err = 'Format logo harus JPG, JPEG, PNG, atau WEBP.';
    } elseif ($size > $maxSize) {
      $err = 'Ukuran logo maksimal 3MB.';
    } else {
      // Folder target: /agromart/uploads/resellers
      $uploadBase = realpath(__DIR__ . '/../../uploads');
      if ($uploadBase === false) { mkdir(__DIR__ . '/../../uploads', 0775, true); $uploadBase = realpath(__DIR__ . '/../../uploads'); }
      $destDir = $uploadBase . DIRECTORY_SEPARATOR . 'resellers';
      if (!is_dir($destDir)) { mkdir($destDir, 0775, true); }

      // Nama file aman & unik
      $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
      $newName  = 'logo_' . time() . '_' . substr(sha1($safeBase . mt_rand()), 0, 8) . '.' . $ext;
      $dest     = $destDir . DIRECTORY_SEPARATOR . $newName;

      if (move_uploaded_file($tmpPath, $dest)) {
        // relative path dari root web app
        $newLogoPath = 'uploads/resellers/' . $newName;

        // Hapus logo lama (jika ada & path relatif dan file ada)
        if (!empty($R[$logoCol])) {
          $old = __DIR__ . '/../../' . ltrim($R[$logoCol], '/');
          if (is_file($old)) { @unlink($old); }
        }
      } else {
        $err = 'Gagal mengunggah logo.';
      }
    }
  }

  if ($err === '' && $nama_toko === '') {
    $err = 'Nama toko wajib diisi.';
  }

  if ($err === '') {
    $rid = (int)$R['id'];

    // Update reseller: nama_toko, deskripsi, alamat (+ logo jika ada)
    if ($logoCol && $newLogoPath !== null) {
      $sql1 = "UPDATE reseller
               SET nama_toko='{$nama_toko}', deskripsi='{$deskripsi}', alamat='{$alamat}', `$logoCol`='".mysqli_real_escape_string($conn,$newLogoPath)."'
               WHERE id={$rid} LIMIT 1";
    } else {
      $sql1 = "UPDATE reseller
               SET nama_toko='{$nama_toko}', deskripsi='{$deskripsi}', alamat='{$alamat}'
               WHERE id={$rid} LIMIT 1";
    }
    $u1 = mysqli_query($conn, $sql1);

    // Update nomor HP di users
    $sql2 = "UPDATE users SET no_hp=" . ($no_hp === '' ? "NULL" : "'" . mysqli_real_escape_string($conn, $no_hp) . "'") . "
             WHERE id={$uid} LIMIT 1";
    $u2 = mysqli_query($conn, $sql2);

    if ($u1 || $u2) {
      $ok = 'Profil toko berhasil diperbarui.';
    } else {
      $err = 'Tidak ada perubahan atau gagal menyimpan.';
    }
  }

  // Refresh data
  $q = mysqli_query($conn, "
    SELECT r.*, u.email, u.no_hp
    FROM reseller r
    JOIN users u ON u.id = r.user_id
    WHERE r.user_id = {$uid}
    LIMIT 1
  ");
  $R = ($q && mysqli_num_rows($q)) ? mysqli_fetch_assoc($q) : $R;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<h1>🏪 Profil Toko</h1>

<div class="card" style="margin-top:12px">
  <div class="card-body">
    <?php if($ok): ?><div class="badge badge-ok" style="margin-bottom:8px">✅ <?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if($err): ?><div class="badge badge-danger" style="margin-bottom:8px">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data" autocomplete="off">
      <div style="margin-bottom:10px">
        <label>Nama Toko</label><br>
        <input type="text" name="nama_toko"
               value="<?= htmlspecialchars($R['nama_toko'] ?? '') ?>"
               style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px" required>
      </div>

      <div style="margin-bottom:10px">
        <label>Deskripsi</label><br>
        <textarea name="deskripsi" rows="4"
                  style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px"><?= htmlspecialchars($R['deskripsi'] ?? '') ?></textarea>
      </div>

      <div style="margin-bottom:10px">
        <label>Alamat</label><br>
        <textarea name="alamat" rows="3"
                  style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px"><?= htmlspecialchars($R['alamat'] ?? '') ?></textarea>
      </div>

      <div style="margin-bottom:10px">
        <label>No HP (WhatsApp)</label><br>
        <input type="text" name="no_hp" placeholder="+62 8xx xxxx xxxx"
               value="<?= htmlspecialchars($R['no_hp'] ?? '') ?>"
               style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px">
      </div>

      <!-- Upload Logo -->
      <div style="margin:16px 0">
        <label>Logo / Foto Profil Toko (JPG/PNG/WEBP, maks 3MB)</label><br>
        <?php if ($logoCol && !empty($R[$logoCol])): ?>
          <div style="display:flex;align-items:center;gap:12px;margin:8px 0">
            <img src="/agromart/<?= htmlspecialchars($R[$logoCol]) ?>"
                 alt="Logo Toko" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:1px solid #e6e8ef">
            <span class="muted" style="font-size:12px">Logo saat ini</span>
          </div>
        <?php elseif(!$logoCol): ?>
          <div class="muted" style="font-size:12px;margin:6px 0">
            *Catatan: tabel <code>reseller</code> belum memiliki kolom untuk menyimpan logo
            (contoh: <code>logo</code>). Upload tetap diterima namun tidak akan disimpan ke database.
          </div>
        <?php endif; ?>
        <input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp"
               style="width:100%;padding:10px;border:1px solid #e6e8ef;border-radius:10px">
      </div>

      <div style="margin-bottom:10px" class="muted">
        Email akun: <b><?= htmlspecialchars($R['email'] ?? '') ?></b><br>
        Status toko: <b><?= htmlspecialchars($R['status'] ?? '-') ?></b>
      </div>

      <button class="btn btn-primary" type="submit"><i class="fa-solid fa-check"></i> Simpan</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
