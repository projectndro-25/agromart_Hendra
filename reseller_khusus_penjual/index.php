<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/auth_reseller.php';
require_once __DIR__ . '/../config/db.php';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$uid = (int)$_SESSION['user_id'];

/* Ambil reseller.id dari user login */
$resellerId = null;
$q = mysqli_query($conn, "SELECT id FROM reseller WHERE user_id=$uid LIMIT 1");
if ($q && mysqli_num_rows($q)) {
  $row = mysqli_fetch_assoc($q);
  $resellerId = (int)$row['id'];
}

/* Inisialisasi ringkasan */
$totalProduk = $totalPesanan = 0;

if ($resellerId) {
  // Hitung produk
  if ($qr = mysqli_query($conn, "SELECT COUNT(*) c FROM produk WHERE reseller_id=$resellerId")) {
    $r = mysqli_fetch_assoc($qr);
    $totalProduk = (int)$r['c'];
    mysqli_free_result($qr);
  }

  // Hitung pesanan (via tabel pesanan jika ada reseller_id)
  $chk = mysqli_query($conn, "SHOW COLUMNS FROM pesanan LIKE 'reseller_id'");
  if ($chk && mysqli_num_rows($chk)) {
    if ($qr = mysqli_query($conn, "SELECT COUNT(*) c FROM pesanan WHERE reseller_id=$resellerId")) {
      $r = mysqli_fetch_assoc($qr);
      $totalPesanan = (int)$r['c'];
      mysqli_free_result($qr);
    }
  } else {
    // fallback lewat detail_pesanan + produk
    $sql = "
      SELECT COUNT(DISTINCT pe.id) c
      FROM pesanan pe
      JOIN detail_pesanan dp ON dp.pesanan_id = pe.id
      JOIN produk p ON p.id = dp.produk_id
      WHERE p.reseller_id = $resellerId
    ";
    if ($qr = mysqli_query($conn,$sql)) {
      $r = mysqli_fetch_assoc($qr);
      $totalPesanan = (int)$r['c'];
      mysqli_free_result($qr);
    }
  }
}
?>
<h1>📊 Dashboard Reseller</h1>
<div class="card" style="margin-top:12px"><div class="card-body">

  <?php if(!$resellerId): ?>
    <div class="badge badge-warn">⚠️ Akun kamu belum punya data toko di tabel reseller. Hubungi admin.</div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
      <div class="card" style="padding:16px;text-align:center">
        <h3>Total Produk</h3>
        <div class="val"><?= $totalProduk ?></div>
      </div>
      <div class="card" style="padding:16px;text-align:center">
        <h3>Total Pesanan</h3>
        <div class="val"><?= $totalPesanan ?></div>
      </div>
    </div>
  <?php endif; ?>

</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
