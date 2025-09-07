<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$id = (int)($_GET['id'] ?? 0);
if ($id<=0) { die('ID tidak valid'); }

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf'];

$st = mysqli_prepare($conn, "SELECT id, nama, email FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
$user = mysqli_fetch_assoc($res);
if (!$user) { die('User tidak ditemukan.'); }

$err = $ok = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { die('CSRF invalid'); }

  $p1 = $_POST['password'] ?? '';
  $p2 = $_POST['password2'] ?? '';
  if (strlen($p1) < 6) { $err = 'Password minimal 6 karakter.'; }
  elseif ($p1 !== $p2) { $err = 'Konfirmasi password tidak sama.'; }
  else {
    $hash = password_hash($p1, PASSWORD_DEFAULT);
    $u = mysqli_prepare($conn, "UPDATE users SET password=?, updated_at=NOW() WHERE id=?");
    mysqli_stmt_bind_param($u, 'si', $hash, $id);
    if (mysqli_stmt_execute($u)) {
      $ok = 'Password berhasil direset.';
      header('Location: index.php?msg='.urlencode($ok));
      exit;
    } else {
      $err = 'Gagal reset password.';
    }
  }
}
?>
<style>
.wrap{padding:20px}
.card{background:#fff;border-radius:14px;box-shadow:0 10px 28px rgba(0,0,0,.06);padding:20px;max-width:520px}
label{font-weight:600;margin-top:10px;display:block}
input{width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:10px;margin-top:6px}
.btn{padding:10px 14px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;cursor:pointer;text-decoration:none}
.btn-primary{background:#f59e0b;color:#fff;border-color:#f59e0b}
.alert{padding:10px;border-radius:10px;margin-bottom:10px}
.alert-ok{background:#e9f9ee;color:#27ae60}
.alert-err{background:#ffeaea;color:#d63031}
</style>

<div class="wrap">
  <h1>🔑 Reset Password</h1>
  <div class="card">
    <p>Untuk user: <b><?= htmlspecialchars($user['nama']) ?></b> (<?= htmlspecialchars($user['email']) ?>)</p>

    <?php if($err): ?><div class="alert alert-err">❌ <?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if($ok):  ?><div class="alert alert-ok">✅ <?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <label>Password Baru</label>
      <input type="password" name="password" required>
      <label>Konfirmasi Password Baru</label>
      <input type="password" name="password2" required>

      <div style="margin-top:14px;display:flex;gap:8px">
        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn" href="index.php">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
