<?php
// user/keranjang/add.php (baru)
require_once __DIR__ . '/../includes/auth_user.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /agromart/user/index.php'); exit; }

$pid = (int)($_POST['id'] ?? ($_POST['produk_id'] ?? 0));
$qty = max(1,(int)($_POST['qty'] ?? 1));
if ($pid <= 0) { $_SESSION['flash']=['type'=>'err','msg'=>'Produk tidak valid.']; header('Location: /agromart/user/index.php'); exit; }

/* Snapshot harga + validasi status aktif */
$st = mysqli_prepare($conn, "SELECT harga, stok FROM produk WHERE id=? AND status='aktif' LIMIT 1");
mysqli_stmt_bind_param($st,'i',$pid);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
$row = $r? mysqli_fetch_assoc($r):null;
if(!$row){ $_SESSION['flash']=['type'=>'err','msg'=>'Produk tidak tersedia.']; header('Location: /agromart/user/index.php'); exit; }
$harga = (float)$row['harga'];

/* pastikan cart ada (prepared + ODKU) */
$insCart = mysqli_prepare($conn, "INSERT INTO cart (user_id) VALUES (?) ON DUPLICATE KEY UPDATE user_id=user_id");
mysqli_stmt_bind_param($insCart,'i',$user_id);
mysqli_stmt_execute($insCart);

/* ambil cart_id milik user */
$cidSt = mysqli_prepare($conn, "SELECT id FROM cart WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($cidSt,'i',$user_id);
mysqli_stmt_execute($cidSt);
$cidRes = mysqli_stmt_get_result($cidSt);
$cart_id = $cidRes && mysqli_num_rows($cidRes) ? (int)mysqli_fetch_assoc($cidRes)['id'] : 0;
if ($cart_id===0) { $_SESSION['flash']=['type'=>'err','msg'=>'Keranjang tidak tersedia.']; header('Location: /agromart/user/index.php'); exit; }

/* cek item: update qty atau insert baru (snapshot harga) */
$cek = mysqli_prepare($conn, "SELECT id, qty FROM cart_items WHERE cart_id=? AND produk_id=? LIMIT 1");
mysqli_stmt_bind_param($cek,'ii',$cart_id,$pid);
mysqli_stmt_execute($cek);
$cekRes = mysqli_stmt_get_result($cek);

if($it = mysqli_fetch_assoc($cekRes)){
  $newQty = (int)$it['qty'] + $qty;
  $u = mysqli_prepare($conn, "UPDATE cart_items SET qty=? WHERE id=?");
  mysqli_stmt_bind_param($u,'ii',$newQty,$it['id']);
  mysqli_stmt_execute($u);
}else{
  $ins = mysqli_prepare($conn, "INSERT INTO cart_items (cart_id, produk_id, qty, harga) VALUES (?,?,?,?)");
  mysqli_stmt_bind_param($ins,'iiid',$cart_id,$pid,$qty,$harga);
  mysqli_stmt_execute($ins);
}

$_SESSION['flash']=['type'=>'ok','msg'=>'Ditambahkan ke keranjang.'];
header('Location: /agromart/user/keranjang/index.php');
