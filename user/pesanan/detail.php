<?php
require_once __DIR__ . '/../includes/auth_user.php';
include __DIR__ . '/../includes/header.php';

$id = (int)($_GET['id'] ?? 0);
/* header */
$st = mysqli_prepare($conn, "SELECT p.*, u.email FROM pesanan p JOIN users u ON u.id=p.user_id WHERE p.id=? AND p.user_id=? LIMIT 1");
mysqli_stmt_bind_param($st,'ii',$id,$user_id);
mysqli_stmt_execute($st);
$hdrRes = mysqli_stmt_get_result($st);
$hdr = $hdrRes? mysqli_fetch_assoc($hdrRes):null;
if(!$hdr){ echo '<div class="card p16">Pesanan tidak ditemukan.</div>'; include __DIR__.'/../includes/footer.php'; exit; }

/* items via VIEW */
$it = mysqli_prepare($conn, "SELECT * FROM v_user_order_items WHERE pesanan_id=? AND user_id=?");
mysqli_stmt_bind_param($it,'ii',$id,$user_id);
mysqli_stmt_execute($it);
$items = mysqli_stmt_get_result($it);
?>
<h1>Pesanan #<?= (int)$id ?></h1>
<div class="card p16">
  <div class="muted">Status: <b><?= htmlspecialchars($hdr['status']) ?></b></div>
  <pre class="muted" style="white-space:pre-wrap;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:10px"><?= htmlspecialchars($hdr['alamat_text'] ?? '') ?></pre>
  <table class="table">
    <thead><tr><th>Produk</th><th>Jumlah</th><th>Harga</th><th>Subtotal</th><th></th></tr></thead>
    <tbody>
      <?php $total=0; if($items): while($r=mysqli_fetch_assoc($items)): $total += (float)$r['subtotal']; ?>
        <tr>
          <td><?= htmlspecialchars($r['nama_produk']) ?></td>
          <td><?= (int)$r['jumlah'] ?></td>
          <td><?= rupiah($r['harga']) ?></td>
          <td><?= rupiah($r['subtotal']) ?></td>
          <td>
            <?php if($hdr['status']==='selesai'): ?>
              <a class="btn-outline" href="ulasan.php?pesanan_id=<?= (int)$id ?>&produk_id=<?= (int)$r['produk_id'] ?>">Ulas</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; endif; ?>
    </tbody>
  </table>
  <div class="row" style="justify-content:flex-end;margin-top:10px">
    <div class="price">Total: <?= rupiah($total) ?></div>
  </div>
  <?php if($hdr['status']==='menunggu_bayar'): ?>
    <div style="text-align:right;margin-top:10px"><a class="btn" href="bayar.php?id=<?= (int)$id ?>">Upload Bukti Bayar</a></div>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
