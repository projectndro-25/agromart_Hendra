<?php
require_once __DIR__ . '/../includes/auth_user.php';
include __DIR__ . '/../includes/header.php';
flash_show();

$st = mysqli_prepare($conn, "SELECT * FROM v_user_orders WHERE user_id=? ORDER BY created_at DESC");
mysqli_stmt_bind_param($st,'i',$user_id);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
?>
<h1>Pesanan Saya</h1>
<div class="card p16">
  <table class="table">
    <thead><tr><th>ID</th><th>Status</th><th>Total</th><th>Tanggal</th><th></th></tr></thead>
    <tbody>
    <?php if($res && mysqli_num_rows($res)): while($p=mysqli_fetch_assoc($res)): ?>
      <tr>
        <td>#<?= (int)$p['pesanan_id'] ?></td>
        <td><span class="badge"><?= htmlspecialchars($p['status']) ?></span></td>
        <td><?= rupiah($p['total_amount']) ?></td>
        <td><?= htmlspecialchars($p['created_at']) ?></td>
        <td class="row">
          <a class="btn-outline" href="detail.php?id=<?= (int)$p['pesanan_id'] ?>">Detail</a>
          <?php if($p['status']==='menunggu_bayar'): ?>
            <a class="btn" href="bayar.php?id=<?= (int)$p['pesanan_id'] ?>">Bayar</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; else: ?>
      <tr><td colspan="5" class="muted">Belum ada pesanan.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
