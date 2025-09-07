<?php
require_once __DIR__ . '/../includes/auth_user.php';
if ($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location:/agromart/user/index.php'); exit; }
$pid = (int)($_POST['produk_id'] ?? 0);

$st = mysqli_prepare($conn, "SELECT 1 FROM wishlist WHERE user_id=? AND produk_id=?");
mysqli_stmt_bind_param($st,'ii',$user_id,$pid);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
if ($res && mysqli_num_rows($res)){
  $d = mysqli_prepare($conn, "DELETE FROM wishlist WHERE user_id=? AND produk_id=?");
  mysqli_stmt_bind_param($d,'ii',$user_id,$pid);
  mysqli_stmt_execute($d);
  $_SESSION['flash']=['type'=>'ok','msg'=>'Dihapus dari wishlist.'];
} else {
  $i = mysqli_prepare($conn, "INSERT IGNORE INTO wishlist (user_id,produk_id) VALUES (?,?)");
  mysqli_stmt_bind_param($i,'ii',$user_id,$pid);
  mysqli_stmt_execute($i);
  $_SESSION['flash']=['type'=>'ok','msg'=>'Ditambahkan ke wishlist.'];
}
$ref = $_SERVER['HTTP_REFERER'] ?? '/agromart/user/index.php';
header('Location: '.$ref);
