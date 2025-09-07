<?php
require_once __DIR__ . '/../includes/auth_user.php';
include __DIR__ . '/../includes/header.php';
flash_show();

$res = mysqli_prepare($conn, "SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC, id DESC");
mysqli_stmt_bind_param($res,'i',$user_id);
mysqli_stmt_execute($res);
$rows = mysqli_stmt_get_result($res);
?>
<h1>Alamat Pengiriman</h1>
<div class="row" style="justify-content:space-between;margin-bottom:10px">
  <div class="muted">Atur alamatmu untuk checkout lebih cepat</div>
  <a class="btn" href="tambah.php">+ Tambah</a>
</div>
<div class="grid">
<?php if($rows && mysqli_num_rows($rows)): while($a=mysqli_fetch_assoc($rows)): ?>
  <div class="card p16">
    <div class="row" style="justify-content:space-between">
      <b><?= htmlspecialchars($a['label']) ?></b>
      <?php if($a['is_default']): ?><span class="badge">Default</span><?php endif; ?>
    </div>
    <div class="muted" style="white-space:pre-wrap;margin-top:6px">
      <?= htmlspecialchars($a['penerima']) ?> (<?= htmlspecialchars($a['phone']) ?>)
      <?= "\n".htmlspecialchars($a['alamat'].' '.$a['city'].' '.$a['province'].' '.$a['postal_code']) ?>
    </div>
    <div class="row" style="gap:8px;margin-top:10px">
      <a class="btn-outline" href="edit.php?id=<?= (int)$a['id'] ?>">Edit</a>
      <a class="btn btn-danger" onclick="return confirm('Hapus alamat ini?')" href="hapus.php?id=<?= (int)$a['id'] ?>">Hapus</a>
    </div>
  </div>
<?php endwhile; else: ?>
  <div class="card p16">Belum ada alamat.</div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
