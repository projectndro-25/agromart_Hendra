<?php
require_once __DIR__ . '/../includes/auth_user.php';
$id = (int)($_GET['id'] ?? 0);
$st = mysqli_prepare($conn, "DELETE FROM user_addresses WHERE id=? AND user_id=?");
mysqli_stmt_bind_param($st,'ii',$id,$user_id);
mysqli_stmt_execute($st);
$_SESSION['flash']=['type'=>'ok','msg'=>'Alamat dihapus.'];
header('Location: index.php');
