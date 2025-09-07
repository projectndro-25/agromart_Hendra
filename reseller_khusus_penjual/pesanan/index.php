<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_reseller.php';
require_once __DIR__ . '/../../config/db.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$uid = (int)$_SESSION['user_id'];

/* Deteksi owner produk */
$ownerCol = null;
$hasUserId = mysqli_query($conn, "SHOW COLUMNS FROM produk LIKE 'user_id'");
$hasResId  = mysqli_query($conn, "SHOW COLUMNS FROM produk LIKE 'reseller_id'");
if ($hasUserId && mysqli_num_rows($hasUserId)) { $ownerCol = 'user_id'; }
elseif ($hasResId && mysqli_num_rows($hasResId)) { $ownerCol = 'reseller_id'; }

/* Jika pakai reseller_id, ambil reseller.id */
$resellerId = null;
if ($ownerCol === 'reseller_id') {
  $qr = mysqli_query($conn, "SELECT id FROM reseller WHERE user_id=$uid LIMIT 1");
  if ($qr && mysqli_num_rows($qr)) { $row = mysqli_fetch_assoc($qr); $resellerId = (int)$row['id']; }
}

/* Deteksi apakah ada kolom pesanan.reseller_id (langsung) */
$useResInOrder = false;
$chk = mysqli_query($conn, "SHOW COLUMNS FROM pesanan LIKE 'reseller_id'");
if ($chk && mysqli_num_rows($chk)) $useResInOrder = true;

/* SQL pesanan toko */
if ($useResInOrder) {
  $ownerVal = ($ownerCol==='reseller_id' && $resellerId!==null) ? $resellerId : $uid;
  $sql = "
    SELECT pe.id, pe.total_harga, pe.status, pe.created_at
    FROM pesanan pe
    WHERE pe.reseller_id = $ownerVal
    ORDER BY pe.created_at DESC
    LIMIT 50
  ";
} else {
  // fallback via detail_pesanan -> produk
  if ($ownerCol === 'user_id') {
    $sql = "
      SELECT pe.id, SUM(dp.subtotal) AS total_harga, pe.status, pe.created_at
      FROM pesanan pe
      JOIN detail_pesanan dp ON dp.pesanan_id = pe.id
      JOIN produk p ON p.id = dp.produk_id
      WHERE p.user_id = $uid
      GROUP BY pe.id, pe.status, pe.created_at
      ORDER BY pe.created_at DESC
      LIMIT 50
    ";
  } elseif ($ownerCol === 'reseller_id' && $resellerId !== null) {
    $sql = "
      SELECT pe.id, SUM(dp.subtotal) AS total_harga, pe.status, pe.created_at
      FROM pesanan pe
      JOIN detail_pesanan dp ON dp.pesanan_id = pe.id
      JOIN produk p ON p.id = dp.produk_id
      WHERE p.reseller_id = $resellerId
      GROUP BY pe.id, pe.status, pe.created_at
      ORDER BY pe.created_at DESC
      LIMIT 50
    ";
  } else {
    $sql = null;
  }
}

$rs = $sql ? mysqli_query($conn,$sql) : false;
?>
<h1>🧾 Pesanan Toko</h1>
<div class="card" style="margin-top:12px"><div class="card-body">
  <?php if(!$ownerCol): ?>
    <div class="badge badge-warn" style="margin-bottom:10px">
      Tidak menemukan kolom pemilik pada tabel <b>produk</b> (user_id/reseller_id).
    </div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>#</th><th>Total</th><th>Status</th><th>Tanggal</th></tr></thead>
      <tbody>
      <?php if($rs && mysqli_num_rows($rs)): while($o=mysqli_fetch_assoc($rs)): ?>
        <tr>
          <td>#<?= (int)$o['id'] ?></td>
          <td><?= rupiah($o['total_harga']) ?></td>
          <td>
            <?php
              $st = strtolower($o['status']);
              if ($st==='selesai') echo '<span class="badge badge-ok">Selesai</span>';
              elseif ($st==='pending') echo '<span class="badge">Pending</span>';
              else echo '<span class="badge">'.htmlspecialchars($o['status']).'</span>';
            ?>
          </td>
          <td><?= htmlspecialchars($o['created_at']) ?></td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="4" class="muted">Belum ada pesanan.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
