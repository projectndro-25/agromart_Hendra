<?php
require_once __DIR__ . '/../includes/auth_user.php';
include __DIR__ . '/../includes/header.php';

$pesanan_id = (int)($_GET['pesanan_id'] ?? $_POST['pesanan_id'] ?? 0);
$produk_id  = (int)($_GET['produk_id'] ?? $_POST['produk_id'] ?? 0);

/* Validasi pemilik & status selesai */
$st = mysqli_prepare($conn, "SELECT status FROM pesanan WHERE id=? AND user_id=? LIMIT 1");
mysqli_stmt_bind_param($st,'ii',$pesanan_id,$user_id);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st); $row = $r? mysqli_fetch_assoc($r):null;
if(!$row || $row['status']!=='selesai'){
  echo '<div class="card p16">Ulasan hanya untuk pesanan selesai.</div>'; include __DIR__.'/../includes/footer.php'; exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $rating = max(1,min(5,(int)($_POST['rating'] ?? 5)));
  $komentar = trim($_POST['komentar'] ?? '');

  /* Upsert review */
  $sql = "INSERT INTO reviews (pesanan_id, produk_id, user_id, rating, komentar)
          VALUES (?,?,?,?,?)
          ON DUPLICATE KEY UPDATE rating=VALUES(rating), komentar=VALUES(komentar)";
  $st = mysqli_prepare($conn,$sql);
  mysqli_stmt_bind_param($st,'iiiis',$pesanan_id,$produk_id,$user_id,$rating,$komentar);
  mysqli_stmt_execute($st);

  /* Ambil id review (baru/eksisting) */
  $ridQ = mysqli_prepare($conn, "SELECT id FROM reviews WHERE pesanan_id=? AND produk_id=? AND user_id=? LIMIT 1");
  mysqli_stmt_bind_param($ridQ,'iii',$pesanan_id,$produk_id,$user_id);
  mysqli_stmt_execute($ridQ);
  $ridRes = mysqli_stmt_get_result($ridQ);
  $review = $ridRes ? mysqli_fetch_assoc($ridRes) : null;
  $review_id = $review ? (int)$review['id'] : 0;

  /* Upload media (foto/video) optional */
  if ($review_id && !empty($_FILES['media']['name'][0])) {
    $base = realpath(__DIR__ . '/../../uploads'); if(!$base){ mkdir(__DIR__.'/../../uploads',0775,true); $base=realpath(__DIR__.'/../../uploads'); }
    $dir = $base.'/reviews'; if(!is_dir($dir)) mkdir($dir,0775,true);

    $allowImg = ['jpg','jpeg','png','webp'];
    $allowVid = ['mp4','webm','ogg','mov'];
    $maxSize  = 12 * 1024 * 1024; // 12MB per file

    foreach($_FILES['media']['name'] as $i => $name){
      if (empty($name)) continue;
      if (!is_uploaded_file($_FILES['media']['tmp_name'][$i])) continue;

      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $size= (int)$_FILES['media']['size'][$i];
      if ($size > $maxSize) continue;

      $type = in_array($ext,$allowImg,true) ? 'image' : (in_array($ext,$allowVid,true) ? 'video' : null);
      if (!$type) continue;

      $fname = 'rev_'.$review_id.'_'.time().'_'.substr(sha1(mt_rand()),0,6).'.'.$ext;
      $ok = move_uploaded_file($_FILES['media']['tmp_name'][$i], $dir.'/'.$fname);
      if ($ok){
        $path = 'uploads/reviews/'.$fname;
        $insM = mysqli_prepare($conn, "INSERT INTO review_media (review_id, media_type, media_path) VALUES (?,?,?)");
        mysqli_stmt_bind_param($insM,'iss',$review_id,$type,$path);
        mysqli_stmt_execute($insM);
      }
    }
  }

  $_SESSION['flash']=['type'=>'ok','msg'=>'Ulasan disimpan. Terima kasih!'];
  header('Location: detail.php?id='.$pesanan_id); exit;
}
?>
<h1>Tulis Ulasan</h1>
<div class="card p16">
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="pesanan_id" value="<?= $pesanan_id ?>">
    <input type="hidden" name="produk_id" value="<?= $produk_id ?>">

    <div class="row" style="gap:10px;align-items:center">
      <label>Rating</label>
      <select name="rating" class="btn-outline">
        <?php for($i=5;$i>=1;$i--): ?><option value="<?= $i ?>"><?= $i ?> ⭐</option><?php endfor; ?>
      </select>
    </div>

    <textarea name="komentar" rows="4" class="btn-outline" style="width:100%;margin-top:10px;border-radius:12px" placeholder="Bagikan pengalamanmu…"></textarea>

    <div style="margin-top:10px">
      <div class="muted" style="margin-bottom:6px">Lampirkan foto/video (opsional, maks 12MB/berkas, hingga 5 berkas)</div>
      <input class="btn-outline" type="file" name="media[]" multiple accept=".jpg,.jpeg,.png,.webp,.mp4,.webm,.ogg,.mov">
    </div>

    <div style="text-align:right;margin-top:12px"><button class="btn">Kirim</button></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
