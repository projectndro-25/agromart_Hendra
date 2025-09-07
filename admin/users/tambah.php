<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$err = $ok = '';
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) { die('CSRF invalid'); }

  $nama   = trim($_POST['nama'] ?? '');
  $email  = trim($_POST['email'] ?? '');
  $no_hp  = trim($_POST['no_hp'] ?? '');
  $alamat = trim($_POST['alamat'] ?? '');
  $role   = trim($_POST['role'] ?? 'user');
  $status = trim($_POST['status'] ?? 'aktif');
  $pass   = $_POST['password'] ?? '';
  $cpass  = $_POST['password2'] ?? '';

  // validasi minimal
  if (strlen($nama) < 3)            { $err = 'Nama minimal 3 karakter.'; }
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $err = 'Email tidak valid.'; }
  elseif (strlen($pass) < 6)        { $err = 'Password minimal 6 karakter.'; }
  elseif ($pass !== $cpass)         { $err = 'Konfirmasi password tidak sama.'; }
  else {
    // email unik?
    $st = mysqli_prepare($conn, "SELECT id FROM users WHERE email=? LIMIT 1");
    mysqli_stmt_bind_param($st, 's', $email);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    if ($rs && mysqli_num_rows($rs)>0) {
      $err = 'Email sudah dipakai.';
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $st2 = mysqli_prepare($conn, "
        INSERT INTO users (nama,email,no_hp,alamat,password,role,is_blocked,status,created_at,updated_at)
        VALUES (?,?,?,?,?,?, '0', ?, NOW(), NOW())
      ");
      mysqli_stmt_bind_param($st2, 'sssssss', $nama,$email,$no_hp,$alamat,$hash,$role,$status);
      if (mysqli_stmt_execute($st2)) {
        $ok = 'User berhasil dibuat.';
        header('Location: index.php?msg='.urlencode($ok));
        exit;
      } else {
        $err = 'Gagal menyimpan user.';
      }
    }
  }
}
?>
<style>
.wrap{padding:20px}
.card{background:#fff;border-radius:14px;box-shadow:0 10px 28px rgba(0,0,0,.06);padding:20px;max-width:720px}
label{font-weight:600;margin-top:10px;display:block}
input,select,textarea{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:10px;margin-top:6px}
.btn{padding:10px 14px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;text-decoration:none}
.btn-primary{background:#16a34a;color:#fff;border-color:#16a34a}
.alert{padding:10px;border-radius:10px;margin-bottom:10px}
.alert-ok{background:#e9f9ee;color:#27ae60}
.alert-err{background:#ffeaea;color:#d63031}
</style>

<div class="wrap">
  <h1>➕ Tambah User</h1>
  <div class="card">
    <?php if($err): ?><div class="alert alert-err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if($ok):  ?><div class="alert alert-ok">✅ <?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">

      <label>Nama Lengkap</label>
      <input type="text" name="nama" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>

      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>

      <label>No HP</label>
      <input type="text" name="no_hp" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">

      <label>Alamat</label>
      <textarea name="alamat" rows="3"><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>

      <label>Role</label>
      <select name="role">
        <?php foreach (['user','reseller','admin','superadmin'] as $r): ?>
          <option value="<?= $r ?>" <?= (($_POST['role'] ?? '')===$r)?'selected':'' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Status</label>
      <select name="status">
        <?php foreach (['aktif','nonaktif'] as $s): ?>
          <option value="<?= $s ?>" <?= (($_POST['status'] ?? 'aktif')===$s)?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Konfirmasi Password</label>
      <input type="password" name="password2" required>

      <div style="margin-top:14px;display:flex;gap:8px">
        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn" href="index.php">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
