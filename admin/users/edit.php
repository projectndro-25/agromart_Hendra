<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

/* CSRF */
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf'];

/* Ambil data user */
$id = (int)($_GET['id'] ?? 0);
$st = mysqli_prepare($conn, "SELECT id, nama, email, no_hp, alamat, role, status, is_blocked, created_at FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
$user = $res ? mysqli_fetch_assoc($res) : null;

if (!$user) {
  $_SESSION['flash'] = ['ok'=>false, 'msg'=>'Pengguna tidak ditemukan.'];
  header("Location: index.php");
  exit;
}

$isSuperTarget = (strtolower($user['role']) === 'superadmin');

/* Submit */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    $_SESSION['flash'] = ['ok'=>false, 'msg'=>'Token tidak valid. Coba lagi.'];
    header("Location: edit.php?id=".$id); exit;
  }

  $nama   = trim($_POST['nama'] ?? '');
  $email  = trim($_POST['email'] ?? '');
  $no_hp  = trim($_POST['no_hp'] ?? '');
  $alamat = trim($_POST['alamat'] ?? '');
  $role   = trim($_POST['role'] ?? $user['role']);
  $status = trim($_POST['status'] ?? $user['status']);
  $blokir = isset($_POST['is_blocked']) ? (int)$_POST['is_blocked'] : (int)$user['is_blocked'];

  // Proteksi superadmin: tidak boleh ubah role/blokir/status
  if ($isSuperTarget) {
    $role   = $user['role'];
    $status = $user['status'];
    $blokir = (int)$user['is_blocked']; // harus tetap 0
  }

  // Validasi sederhana
  if ($nama === '') {
    $_SESSION['flash'] = ['ok'=>false, 'msg'=>'Nama tidak boleh kosong.'];
    header("Location: edit.php?id=".$id); exit;
  }
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'] = ['ok'=>false, 'msg'=>'Email tidak valid.'];
    header("Location: edit.php?id=".$id); exit;
  }

  // Email unik
  $stE = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? AND id<>? LIMIT 1");
  mysqli_stmt_bind_param($stE, 'si', $email, $id);
  mysqli_stmt_execute($stE);
  $rE = mysqli_stmt_get_result($stE);
  if ($rE && mysqli_num_rows($rE) > 0) {
    $_SESSION['flash'] = ['ok'=>false, 'msg'=>'Email sudah dipakai user lain.'];
    header("Location: edit.php?id=".$id); exit;
  }

  // Jika menurunkan role admin terakhir => tolak
  if (!$isSuperTarget && strtolower($user['role']) === 'admin' && strtolower($role) !== 'admin') {
    $qCount = mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role IN ('admin','superadmin') AND status='aktif'");
    $cntAdmin = (int)mysqli_fetch_assoc($qCount)['c'];
    if ($cntAdmin <= 1) {
      $_SESSION['flash'] = ['ok'=>false, 'msg'=>'Tidak dapat menurunkan role: ini admin aktif terakhir.'];
      header("Location: edit.php?id=".$id); exit;
    }
  }

  // Update
  $stU = mysqli_prepare($conn, "
    UPDATE users 
      SET nama=?, email=?, no_hp=?, alamat=?, role=?, status=?, is_blocked=?, updated_at=NOW()
    WHERE id=?
    LIMIT 1
  ");
  mysqli_stmt_bind_param($stU, 'ssssssii', $nama, $email, $no_hp, $alamat, $role, $status, $blokir, $id);

  if (mysqli_stmt_execute($stU)) {
    $_SESSION['flash'] = ['ok'=>true, 'msg'=>'Perubahan disimpan.'];
  } else {
    $_SESSION['flash'] = ['ok'=>false, 'msg'=>'Gagal menyimpan perubahan: '.mysqli_error($conn)];
  }
  header("Location: edit.php?id=".$id);
  exit;
}

/* UI helper badge */
function chip($text, $ok=true){
  return $ok
    ? '<span class="chip chip-ok">'.$text.'</span>'
    : '<span class="chip chip-bad">'.$text.'</span>';
}
?>
<style>
/* Notif ala Gen Z: clean, rounded, lembut */
.flash {
  padding:12px 14px;border-radius:12px;margin:12px 0;display:flex;align-items:center;gap:10px;
  box-shadow:0 6px 20px rgba(0,0,0,.06)
}
.flash-ok  {background:#e8fff1;color:#14532d;border:1px solid #bbf7d0}
.flash-bad {background:#fff1f1;color:#7f1d1d;border:1px solid #fecaca}
.flash .close{margin-left:auto;cursor:pointer;opacity:.7}
.form-card{background:#fff;padding:22px;border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.08);max-width:800px;margin:18px auto}
.form-card h2{margin:0 0 14px 0}
label{font-weight:600;display:block;margin-top:12px}
input,select,textarea{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:10px;margin-top:6px}
.btn{display:inline-block;padding:10px 16px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;text-decoration:none}
.btn-primary{background:#16a34a;color:#fff;border-color:#16a34a}
.btn-secondary{background:#6b7280;color:#fff;border-color:#6b7280}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.chip{padding:4px 10px;border-radius:999px;font-size:12px;display:inline-block}
.chip-ok{background:#e9f9ee;color:#27ae60}
.chip-bad{background:#ffeaea;color:#d63031}
</style>

<div class="content">
  <div class="form-card">
    <h2>✏️ Edit Pengguna</h2>

    <?php if (!empty($_SESSION['flash'])): 
      $f = $_SESSION['flash']; unset($_SESSION['flash']); ?>
      <div class="flash <?= $f['ok'] ? 'flash-ok' : 'flash-bad' ?>" id="flashBox">
        <span><?= $f['ok'] ? '✅' : '❌' ?></span>
        <div><?= htmlspecialchars($f['msg']) ?></div>
        <span class="close" onclick="document.getElementById('flashBox').style.display='none'">✖</span>
      </div>
      <script>
        setTimeout(()=>{ const b=document.getElementById('flashBox'); if(b){ b.style.opacity='0'; b.style.transition='opacity .4s'; setTimeout(()=>b.style.display='none', 400);} }, 3600);
      </script>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">

      <label>Nama Lengkap</label>
      <input type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>" required>

      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

      <div class="row">
        <div>
          <label>No HP</label>
          <input type="text" name="no_hp" value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>">
        </div>
        <div>
          <label>Dibuat</label>
          <input type="text" value="<?= htmlspecialchars($user['created_at']) ?>" disabled>
        </div>
      </div>

      <label>Alamat</label>
      <textarea name="alamat" rows="3"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>

      <div class="row">
        <div>
          <label>Role</label>
          <?php if ($isSuperTarget): ?>
            <input type="text" value="Superadmin" disabled>
          <?php else: ?>
            <select name="role" required>
              <?php foreach (['user'=>'User','reseller'=>'Reseller','admin'=>'Admin'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= strtolower($user['role'])===$k?'selected':'' ?>><?= $v ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>

        <div>
          <label>Status</label>
          <?php if ($isSuperTarget): ?>
            <input type="text" value="Aktif" disabled>
          <?php else: ?>
            <select name="status" required>
              <option value="aktif" <?= strtolower($user['status'])==='aktif'?'selected':'' ?>>Aktif</option>
              <option value="nonaktif" <?= strtolower($user['status'])==='nonaktif'?'selected':'' ?>>Nonaktif</option>
            </select>
          <?php endif; ?>
        </div>
      </div>

      <div class="row">
        <div>
          <label>Blokir</label>
          <?php if ($isSuperTarget): ?>
            <input type="text" value="Tidak" disabled>
          <?php else: ?>
            <select name="is_blocked">
              <option value="0" <?= (int)$user['is_blocked']===0?'selected':'' ?>>Tidak</option>
              <option value="1" <?= (int)$user['is_blocked']===1?'selected':'' ?>>Ya</option>
            </select>
          <?php endif; ?>
        </div>
        <div style="display:flex;align-items:flex-end;gap:8px">
          <span><?= (int)$user['is_blocked']===1 ? chip('⛔ Diblokir',false) : chip('✅ Aktif',true) ?></span>
        </div>
      </div>

      <div style="margin-top:16px; display:flex; gap:8px;">
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="index.php" class="btn btn-secondary">Kembali</a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
