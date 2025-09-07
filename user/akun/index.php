<?php
require_once __DIR__ . '/../includes/auth_user.php';
include __DIR__ . '/../includes/header.php';
$st = mysqli_prepare($conn,"SELECT email, role, nama FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($st,'i',$user_id);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
$u = $res? mysqli_fetch_assoc($res):['email'=>'-','role'=>'user','nama'=>'-'];
?>
<h1>Akun</h1>
<div class="card p16">
  <div class="row" style="gap:20px">
    <div><b>Email</b><div class="muted"><?= htmlspecialchars($u['email']) ?></div></div>
    <div><b>Nama</b><div class="muted"><?= htmlspecialchars($u['nama'] ?? '-') ?></div></div>
    <div><b>Role</b><div class="muted"><?= htmlspecialchars($u['role']) ?></div></div>
  </div>
  <div class="row" style="gap:10px;margin-top:14px">
    <a class="btn-outline" href="/agromart/user/alamat/index.php">Kelola Alamat</a>
    <a class="btn-outline" href="/agromart/user/wishlist/index.php">Wishlist</a>
    <a class="btn-outline" href="/agromart/user/pesanan/index.php">Pesanan</a>
    <a class="btn" href="/agromart/user/akun/keamanan.php">Keamanan</a>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
