<?php
require_once __DIR__ . '/../includes/auth_user.php';
include __DIR__ . '/../includes/header.php';
flash_show();

$sql = "SELECT p.id, p.nama_produk, p.harga, p.gambar
        FROM wishlist w
        JOIN produk p ON p.id=w.produk_id
        WHERE w.user_id=?
        ORDER BY w.id DESC";
$st = mysqli_prepare($conn,$sql);
mysqli_stmt_bind_param($st,'i',$user_id);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
?>
<h1>Wishlist</h1>
<div class="grid grid-4">
<?php if($res && mysqli_num_rows($res)): while($p=mysqli_fetch_assoc($res)): ?>
  <div class="card p16">
    <a href="/agromart/user/produk.php?id=<?= (int)$p['id'] ?>">
      <img class="img" src="<?= htmlspecialchars(!empty($p['gambar'])?'/agromart/'.$p['gambar']:'https://placehold.co/600x400?text=AgroMart') ?>">
      <div style="margin-top:10px;font-weight:600;min-height:42px"><?= htmlspecialchars($p['nama_produk']) ?></div>
      <div class="price"><?= rupiah($p['harga']) ?></div>
    </a>
    <form action="toggle.php" method="post" style="margin-top:8px">
      <input type="hidden" name="produk_id" value="<?= (int)$p['id'] ?>">
      <button class="btn btn-danger">Hapus</button>
    </form>
  </div>
<?php endwhile; else: ?>
  <div class="card p16">Wishlist kosong.</div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
