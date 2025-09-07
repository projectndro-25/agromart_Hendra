<?php
// user/pesanan/bayar.php (baru)
require_once __DIR__ . '/../includes/auth_user.php';
include __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
/* validasi owner */
$st = mysqli_prepare($conn, "SELECT id FROM pesanan WHERE id=? AND user_id=? LIMIT 1");
mysqli_stmt_bind_param($st,'ii',$id,$user_id);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
if(!$r || !mysqli_num_rows($r)){ echo '<div class="card p16">Pesanan tidak ditemukan.</div>'; include __DIR__.'/../includes/footer.php'; exit; }

if ($_SERVER['REQUEST_METHOD']==='POST'){
  $amount = (float)($_POST['amount'] ?? 0);
  $proof = null;

  if(!empty($_FILES['proof']['name']) && is_uploaded_file($_FILES['proof']['tmp_name'])){
    $ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
    $ok = ['jpg','jpeg','png','webp','pdf'];
    if(in_array($ext,$ok,true) && $_FILES['proof']['size']<= 5*1024*1024){
      $base = realpath(__DIR__ . '/../../uploads'); if(!$base){ mkdir(__DIR__.'/../../uploads',0775,true); $base=realpath(__DIR__.'/../../uploads'); }
      $pDir = $base.'/payments'; if(!is_dir($pDir)) mkdir($pDir,0775,true);
      $name = 'pay_'.$id.'_'.time().'_'.substr(sha1(mt_rand()),0,6).'.'.$ext;
      move_uploaded_file($_FILES['proof']['tmp_name'], $pDir.'/'.$name);
      $proof = 'uploads/payments/'.$name;
    }
  }

  /* upsert payment (pending) */
  $sql = "INSERT INTO payments (pesanan_id, method, amount, status, paid_at, proof_path)
          VALUES (?, 'manual_transfer', ?, 'pending', NOW(), ?)
          ON DUPLICATE KEY UPDATE amount=VALUES(amount), status='pending', paid_at=NOW(), proof_path=VALUES(proof_path)";
  $st = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($st,'ids',$id,$amount,$proof);
  mysqli_stmt_execute($st);

  /* update status pesanan + history */
  $newStatus = 'dibayar-menunggu-verifikasi';
  $up = mysqli_prepare($conn, "UPDATE pesanan SET status=? WHERE id=?");
  mysqli_stmt_bind_param($up,'si',$newStatus,$id);
  mysqli_stmt_execute($up);

  $hist = mysqli_prepare($conn, "INSERT INTO order_status_history (pesanan_id, status, note, created_at) VALUES (?, ?, 'Pembayaran diunggah user', NOW())");
  mysqli_stmt_bind_param($hist,'is',$id,$newStatus);
  mysqli_stmt_execute($hist);

  $_SESSION['flash']=['type'=>'ok','msg'=>'Bukti pembayaran diunggah. Menunggu verifikasi.'];
  header('Location: detail.php?id='.$id); exit;
}
?>
<h1>Upload Bukti Pembayaran</h1>
<div class="card p16">
  <form method="post" enctype="multipart/form-data">
    <div class="row" style="gap:10px">
      <input class="btn-outline" name="amount" type="number" step="100" min="0" placeholder="Jumlah transfer (Rp)">
      <input class="btn-outline" name="proof" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf">
      <button class="btn">Kirim</button>
    </div>
    <div class="muted" style="margin-top:8px">Admin akan memverifikasi pembayaranmu.</div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
