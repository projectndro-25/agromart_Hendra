<?php
require_once __DIR__ . '/../includes/auth_user.php';
include __DIR__ . '/../includes/header.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
  $label = trim($_POST['label'] ?? '');
  $penerima = trim($_POST['penerima'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $alamat = trim($_POST['alamat'] ?? '');
  $city = trim($_POST['city'] ?? '');
  $province = trim($_POST['province'] ?? '');
  $postal = trim($_POST['postal_code'] ?? '');
  $is_def = !empty($_POST['is_default']) ? 1 : 0;

  $ins = mysqli_prepare($conn,"INSERT INTO user_addresses (user_id,label,penerima,phone,alamat,city,province,postal_code,is_default) VALUES (?,?,?,?,?,?,?,?,?)");
  mysqli_stmt_bind_param($ins,'isssssssi',$user_id,$label,$penerima,$phone,$alamat,$city,$province,$postal,$is_def);
  mysqli_stmt_execute($ins);

  if ($is_def){
    mysqli_query($conn,"UPDATE user_addresses SET is_default=0 WHERE user_id=$user_id AND id<>".mysqli_insert_id($conn));
  }
  $_SESSION['flash']=['type'=>'ok','msg'=>'Alamat ditambahkan.'];
  header('Location: index.php'); exit;
}
?>
<h1>Tambah Alamat</h1>
<div class="card p16">
  <form method="post" class="grid" style="gap:10px">
    <input class="btn-outline" name="label" placeholder="Rumah / Kantor" required>
    <input class="btn-outline" name="penerima" placeholder="Nama penerima" required>
    <input class="btn-outline" name="phone" placeholder="No. HP" required>
    <textarea class="btn-outline" name="alamat" rows="3" placeholder="Alamat lengkap" required></textarea>
    <div class="row" style="gap:10px">
      <input class="btn-outline" name="city" placeholder="Kota" required>
      <input class="btn-outline" name="province" placeholder="Provinsi" required>
      <input class="btn-outline" name="postal_code" placeholder="Kode Pos" required>
    </div>
    <label class="row"><input type="checkbox" name="is_default" value="1"> Jadikan default</label>
    <div style="text-align:right"><button class="btn">Simpan</button></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
