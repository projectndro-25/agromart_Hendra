<?php
// user/keranjang/checkout.php (baru)
require_once __DIR__ . '/../includes/auth_user.php';

mysqli_begin_transaction($conn);
try{
  /* Ambil alamat default */
  $adr = mysqli_prepare($conn, "SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC, id DESC LIMIT 1");
  mysqli_stmt_bind_param($adr,'i',$user_id);
  mysqli_stmt_execute($adr);
  $ar = mysqli_stmt_get_result($adr);
  $alamat = $ar && mysqli_num_rows($ar) ? mysqli_fetch_assoc($ar) : null;
  $alamat_text = $alamat
    ? ($alamat['penerima'].' | '.$alamat['phone']."\n".$alamat['alamat'].' '.$alamat['city'].' '.$alamat['province'].' '.$alamat['postal_code'])
    : 'Alamat belum diatur';

  /* cart + items */
  $cidSt = mysqli_prepare($conn, "SELECT id FROM cart WHERE user_id=? LIMIT 1");
  mysqli_stmt_bind_param($cidSt,'i',$user_id);
  mysqli_stmt_execute($cidSt);
  $cidRes = mysqli_stmt_get_result($cidSt);
  $cart_id = $cidRes && mysqli_num_rows($cidRes) ? (int)mysqli_fetch_assoc($cidRes)['id'] : 0;
  if ($cart_id===0) throw new Exception('Keranjang kosong.');

  $itSt = mysqli_prepare($conn, "SELECT ci.*, p.reseller_id, p.stok FROM cart_items ci JOIN produk p ON p.id=ci.produk_id WHERE ci.cart_id=?");
  mysqli_stmt_bind_param($itSt,'i',$cart_id);
  mysqli_stmt_execute($itSt);
  $itRes = mysqli_stmt_get_result($itSt);
  $items = [];
  while($row=mysqli_fetch_assoc($itRes)) $items[]=$row;
  if (!$items) throw new Exception('Keranjang kosong.');

  /* cek kolom reseller_id */
  $hasRes = mysqli_query($conn, "SHOW COLUMNS FROM pesanan LIKE 'reseller_id'");
  $split = $hasRes && mysqli_num_rows($hasRes)>0;

  $status_awal = 'menunggu_bayar';
  if ($split){
    // grup per reseller
    $groups=[];
    foreach($items as $it){ $groups[(int)$it['reseller_id']][]=$it; }

    foreach($groups as $rid=>$rows){
      // validasi stok semua item reseller ini
      foreach($rows as $it){
        if ((int)$it['qty'] > (int)$it['stok']) {
          throw new Exception('Stok tidak cukup untuk salah satu item.');
        }
      }

      // insert pesanan
      $ins = mysqli_prepare($conn, "INSERT INTO pesanan (user_id,reseller_id,status,alamat_text,created_at) VALUES (?,?, ?, ?, NOW())");
      mysqli_stmt_bind_param($ins,'iiss',$user_id,$rid,$status_awal,$alamat_text);
      mysqli_stmt_execute($ins);
      $pid = mysqli_insert_id($conn);

      // insert detail + update stok + stok_history
      foreach($rows as $it){
        $d = mysqli_prepare($conn, "INSERT INTO detail_pesanan (pesanan_id,produk_id,jumlah,harga) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($d,'iiid',$pid,$it['produk_id'],$it['qty'],$it['harga']);
        mysqli_stmt_execute($d);

        // kurangi stok
        $newStok = (int)$it['stok'] - (int)$it['qty'];
        $u = mysqli_prepare($conn, "UPDATE produk SET stok=? WHERE id=?");
        mysqli_stmt_bind_param($u,'ii',$newStok,$it['produk_id']);
        mysqli_stmt_execute($u);

        // stok_history
        $minus = -1 * (int)$it['qty'];
        $h = mysqli_prepare($conn, "INSERT INTO stok_history (produk_id, change_qty, note, created_at) VALUES (?, ?, 'ORDER_CHECKOUT', NOW())");
        mysqli_stmt_bind_param($h,'ii',$it['produk_id'],$minus);
        mysqli_stmt_execute($h);
      }

      // catat status awal
      $hs = mysqli_prepare($conn, "INSERT INTO order_status_history (pesanan_id, status, note, created_at) VALUES (?, ?, 'Pesanan dibuat', NOW())");
      mysqli_stmt_bind_param($hs,'is',$pid,$status_awal);
      mysqli_stmt_execute($hs);
    }
  }else{
    // validasi stok global
    foreach($items as $it){
      if ((int)$it['qty'] > (int)$it['stok']) throw new Exception('Stok tidak cukup untuk salah satu item.');
    }
    $ins = mysqli_prepare($conn, "INSERT INTO pesanan (user_id,status,alamat_text,created_at) VALUES (?, ?, ?, NOW())");
    mysqli_stmt_bind_param($ins,'iss',$user_id,$status_awal,$alamat_text);
    mysqli_stmt_execute($ins);
    $pid = mysqli_insert_id($conn);
    foreach($items as $it){
      $d = mysqli_prepare($conn, "INSERT INTO detail_pesanan (pesanan_id,produk_id,jumlah,harga) VALUES (?,?,?,?)");
      mysqli_stmt_bind_param($d,'iiid',$pid,$it['produk_id'],$it['qty'],$it['harga']);
      mysqli_stmt_execute($d);

      $newStok = (int)$it['stok'] - (int)$it['qty'];
      $u = mysqli_prepare($conn, "UPDATE produk SET stok=? WHERE id=?");
      mysqli_stmt_bind_param($u,'ii',$newStok,$it['produk_id']);
      mysqli_stmt_execute($u);

      $minus = -1 * (int)$it['qty'];
      $h = mysqli_prepare($conn, "INSERT INTO stok_history (produk_id, change_qty, note, created_at) VALUES (?, ?, 'ORDER_CHECKOUT', NOW())");
      mysqli_stmt_bind_param($h,'ii',$it['produk_id'],$minus);
      mysqli_stmt_execute($h);
    }
    $hs = mysqli_prepare($conn, "INSERT INTO order_status_history (pesanan_id, status, note, created_at) VALUES (?, ?, 'Pesanan dibuat', NOW())");
    mysqli_stmt_bind_param($hs,'is',$pid,$status_awal);
    mysqli_stmt_execute($hs);
  }

  /* kosongkan cart */
  $del = mysqli_prepare($conn, "DELETE FROM cart_items WHERE cart_id=?");
  mysqli_stmt_bind_param($del,'i',$cart_id);
  mysqli_stmt_execute($del);

  mysqli_commit($conn);
  $_SESSION['flash']=['type'=>'ok','msg'=>'Checkout berhasil. Silakan lakukan pembayaran.'];
  header('Location: /agromart/user/pesanan/index.php'); exit;
}catch(Exception $e){
  mysqli_rollback($conn);
  $_SESSION['flash']=['type'=>'err','msg'=>'Checkout gagal: '.$e->getMessage()];
  header('Location: /agromart/user/keranjang/index.php'); exit;
}
