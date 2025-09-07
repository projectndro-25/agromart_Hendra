<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$resellerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($resellerId <= 0) { header('Location: index.php'); exit; }

/* Detail reseller + pemilik */
$sql = "
  SELECT r.*, u.nama AS nama_user, u.email
  FROM reseller r
  JOIN users u ON u.id = r.user_id
  WHERE r.id = ?
  LIMIT 1
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $resellerId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$r = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$r) { header('Location: index.php'); exit; }

$st    = strtolower($r['status']);
$badge = $st==='aktif' ? 'success' : ($st==='blokir' ? 'danger' : 'warning');

/* Produk milik reseller ini saja */
$total = 0; $produk = [];
$stmt  = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM produk WHERE reseller_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $r['id']);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$total = (int)mysqli_fetch_assoc($rs)['c'];
mysqli_stmt_close($stmt);

if ($total > 0) {
  $stmt = mysqli_prepare($conn, "
    SELECT p.id, p.nama_produk, p.harga, p.stok, p.created_at
    FROM produk p
    WHERE p.reseller_id = ?
    ORDER BY p.created_at DESC
    LIMIT 10
  ");
  mysqli_stmt_bind_param($stmt, 'i', $r['id']);
  mysqli_stmt_execute($stmt);
  $rs = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($rs)) $produk[] = $row;
  mysqli_stmt_close($stmt);
}
?>
<div class="content" style="padding:20px">
  <h1>🏪 Detail Reseller</h1>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card agw-panel">
        <div class="card-body">
          <h5 class="mb-3"><?= htmlspecialchars($r['nama_toko']) ?></h5>
          <div class="mb-2"><strong>Pemilik:</strong> <?= htmlspecialchars($r['nama_user'] ?? '-') ?></div>
          <div class="mb-2"><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($r['email'] ?? '') ?>"><?= htmlspecialchars($r['email'] ?? '-') ?></a></div>
          <div class="mb-2"><strong>Status:</strong> <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($r['status']) ?></span></div>
          <?php if(!empty($r['alamat'])): ?><div class="mb-2"><strong>Alamat:</strong> <?= nl2br(htmlspecialchars($r['alamat'])) ?></div><?php endif; ?>
          <?php if(!empty($r['deskripsi'])): ?><div class="mb-2"><strong>Deskripsi:</strong><br><?= nl2br(htmlspecialchars($r['deskripsi'])) ?></div><?php endif; ?>

          <div class="d-flex gap-2 mt-3">
            <?php if ($st==='blokir'): ?>
              <a href="buka_blokir.php?id=<?= (int)$r['id'] ?>" class="btn btn-success">Unblokir</a>
            <?php else: ?>
              <a href="blokir.php?id=<?= (int)$r['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Blokir reseller ini?')">Blokir</a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-light">Kembali</a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card agw-panel">
        <div class="card-body">
          <h6 class="mb-3">Produk Terbaru (<?= (int)$total ?> total)</h6>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead><tr><th>Nama</th><th>Harga</th><th>Stok</th><th>Tanggal</th><th></th></tr></thead>
              <tbody>
              <?php if ($produk): foreach ($produk as $p): ?>
                <tr>
                  <td><?= htmlspecialchars($p['nama_produk']) ?></td>
                  <td>Rp <?= number_format((float)$p['harga'],0,',','.') ?></td>
                  <td><?= (int)$p['stok'] ?></td>
                  <td><?= htmlspecialchars($p['created_at']) ?></td>
                  <td>
                    <!-- kirim context asal halaman -->
                    <a class="btn btn-sm btn-outline-primary"
                       href="../produk/detail.php?id=<?= (int)$p['id'] ?>&from=reseller&id_reseller=<?= (int)$r['id'] ?>">
                      Detail
                    </a>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="5" class="text-center text-muted">Belum ada produk.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
