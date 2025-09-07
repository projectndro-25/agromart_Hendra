<?php
require_once __DIR__ . '/../includes/auth_user.php';
include __DIR__ . '/../includes/header.php';

flash_show();
/* cart id */
$st = mysqli_prepare($conn, "SELECT id FROM cart WHERE user_id=?");
mysqli_stmt_bind_param($st,'i',$user_id);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
$cart_id = $r && mysqli_num_rows($r) ? (int)mysqli_fetch_assoc($r)['id'] : 0;

/* items */
$items = [];
if ($cart_id>0){
  $st = mysqli_prepare($conn, "SELECT ci.id, ci.produk_id, p.nama_produk, ci.qty, ci.harga, (ci.qty*ci.harga) AS subtotal
                               FROM cart_items ci
                               JOIN produk p ON p.id=ci.produk_id
                               WHERE ci.cart_id=?");
  mysqli_stmt_bind_param($st,'i',$cart_id);
  mysqli_stmt_execute($st);
  $res = mysqli_stmt_get_result($st);
  while($row=mysqli_fetch_assoc($res)) $items[]=$row;
}
/* totals from view */
$tot = 0; $qty=0;
$tv = mysqli_prepare($conn, "SELECT total_qty,total_amount FROM v_user_cart_totals WHERE user_id=?");
mysqli_stmt_bind_param($tv,'i',$user_id);
mysqli_stmt_execute($tv);
$rv = mysqli_stmt_get_result($tv);
if ($rv && mysqli_num_rows($rv)){ $V=mysqli_fetch_assoc($rv); $tot=(float)$V['total_amount']; $qty=(int)$V['total_qty']; }
?>
<h1>Keranjang</h1>
<div class="card p16">
  <?php if(!$items): ?>
    <div class="muted">Keranjangmu masih kosong.</div>
  <?php else: ?>
    <table class="table">
      <thead><tr><th>Produk</th><th>Qty</th><th>Harga</th><th>Subtotal</th><th></th></tr></thead>
      <tbody>
      <?php foreach($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['nama_produk']) ?></td>
          <td>
            <form class="row" action="update.php" method="post">
              <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
              <input type="number" name="qty" min="0" value="<?= (int)$it['qty'] ?>" class="btn-outline" style="width:90px">
              <button class="btn" type="submit">Update</button>
            </form>
          </td>
          <td><?= rupiah($it['harga']) ?></td>
          <td><?= rupiah($it['subtotal']) ?></td>
          <td>
            <form action="update.php" method="post">
              <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
              <input type="hidden" name="qty" value="0">
              <button class="btn btn-danger">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px">
      <div class="muted">Total Item: <b><?= $qty ?></b></div>
      <div class="price"><?= rupiah($tot) ?></div>
    </div>
    <form action="checkout.php" method="post" style="text-align:right;margin-top:10px">
      <button class="btn">Checkout</button>
    </form>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
