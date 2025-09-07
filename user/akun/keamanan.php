<?php
require_once __DIR__ . '/../includes/auth_user.php';
include __DIR__ . '/../includes/header.php';
flash_show();

if($_SERVER['REQUEST_METHOD']==='POST'){
  $old = $_POST['old'] ?? '';
  $new = $_POST['new'] ?? '';
  $st = mysqli_prepare($conn, "SELECT password FROM users WHERE id=?");
  mysqli_stmt_bind_param($st,'i',$user_id);
  mysqli_stmt_execute($st);
  $res = mysqli_stmt_get_result($st);
  $row = $res? mysqli_fetch_assoc($res):null;
  if(!$row || !password_verify($old, $row['password'])){
    $_SESSION['flash']=['type'=>'err','msg'=>'Password lama salah.'];
  }else{
    $hash = password_hash($new, PASSWORD_BCRYPT);
    $u = mysqli_prepare($conn,"UPDATE users SET password=? WHERE id=?");
    mysqli_stmt_bind_param($u,'si',$hash,$user_id);
    mysqli_stmt_execute($u);
    $_SESSION['flash']=['type'=>'ok','msg'=>'Password diperbarui.'];
  }
  header('Location: keamanan.php'); exit;
}
?>
<h1>Keamanan Akun</h1>
<div class="card p16">
  <form method="post" class="grid" style="gap:10px;max-width:420px">
    <input class="btn-outline" type="password" name="old" placeholder="Password lama" required>
    <input class="btn-outline" type="password" name="new" placeholder="Password baru" required>
    <div><button class="btn">Simpan</button></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
