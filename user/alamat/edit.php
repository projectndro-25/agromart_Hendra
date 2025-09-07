<?php
require_once __DIR__ . '/../includes/auth_user.php';
include __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = mysqli_prepare($conn,"SELECT * FROM user_addresses WHERE id=? AND user_id=? LIMIT 1");
mysqli_stmt_bind_param($st,'ii',$id,$user_id);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
$a = $res? mysqli_fetch_assoc($res):null;
if(!$a){ echo '<div class="card p16">Alamat tidak ditemukan.</div>'; include __DIR__.'/../includes/footer.php'; exit; }

if($_SERVER['REQUEST_METHOD']==='POST'){
  $label = trim($_POST['label']); $penerima=trim($_POST['penerima']);
  $phone=trim($_POST['phone']); $alamat=trim($_POST['alamat']);
  $city=trim($_POST['city']); $province=trim($_POST['province']); $postal=trim($_POST['postal_code']);
  $is_def = !empty($_POST['is_default'])?1:0;

  $up = mysqli_prepare($conn, "UPDATE user_addresses SET label=?, penerima=?, phone=?, alamat=?, city=?, province=?, postal_code=?, is_default=? WHERE id=? AND user_id=?");
  mysqli_stmt_bind_param($up,'sssssssiii',$label,$penerima,$phone,$alamat,$city,$province,$postal,$is_def,$id,$user_id);
  mysqli_stmt_execute($up);
  if ($is_def){ mysqli_query($conn,"UPDATE user_addresses SET is_default=0 WHERE user_id=$user_id AND id<>$id"); }
  $_SESSION['flash']=['type'=>'ok','msg'=>'Alamat diperbarui.']; header('Location: index.php'); exit;
}
?>
<h1>Edit Alamat</h1>
<div class="card p16">
  <form method="post" class="grid" style="gap:10px">
    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
    <input class="btn-outline" name="label" value="<?= htmlspecialchars($a['label']) ?>" required>
    <input class="btn-outline" name="penerima" value="<?= htmlspecialchars($a['penerima']) ?>" required>
    <input class="btn-outline" name="phone" value="<?= htmlspecialchars($a['phone']) ?>" required>
    <textarea class="btn-outline" name="alamat" rows="3" required><?= htmlspecialchars($a['alamat']) ?></textarea>
    <div class="row" style="gap:10px">
      <input class="btn-outline" name="city" value="<?= htmlspecialchars($a['city']) ?>" required>
      <input class="btn-outline" name="province" value="<?= htmlspecialchars($a['province']) ?>" required>
      <input class="btn-outline" name="postal_code" value="<?= htmlspecialchars($a['postal_code']) ?>" required>
    </div>
    <label class="row"><input type="checkbox" name="is_default" value="1" <?= $a['is_default']?'checked':'' ?>> Jadikan default</label>
    <div style="text-align:right"><button class="btn">Simpan</button></div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
