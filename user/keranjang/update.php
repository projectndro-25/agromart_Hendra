<?php
require_once __DIR__ . '/../includes/auth_user.php';
if ($_SERVER['REQUEST_METHOD']!=='POST'){ header('Location: index.php'); exit; }
$id = (int)($_POST['id'] ?? 0);
$qty = max(0,(int)($_POST['qty'] ?? 0));

/* Pastikan item milik cart user */
$st = mysqli_prepare($conn, "SELECT ci.id FROM cart_items ci JOIN cart c ON c.id=ci.cart_id WHERE ci.id=? AND c.user_id=?");
mysqli_stmt_bind_param($st,'ii',$id,$user_id);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
if (!$r || !mysqli_num_rows($r)){ $_SESSION['flash']=['type'=>'err','msg'=>'Item tidak ditemukan.']; header('Location: index.php'); exit; }

if ($qty===0){
  $d = mysqli_prepare($conn, "DELETE FROM cart_items WHERE id=?");
  mysqli_stmt_bind_param($d,'i',$id);
  mysqli_stmt_execute($d);
}else{
  $u = mysqli_prepare($conn, "UPDATE cart_items SET qty=? WHERE id=?");
  mysqli_stmt_bind_param($u,'ii',$qty,$id);
  mysqli_stmt_execute($u);
}

$_SESSION['flash']=['type'=>'ok','msg'=>'Keranjang diperbarui.'];
header('Location: /agromart/user/keranjang/index.php');
