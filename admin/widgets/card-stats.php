<?php
// admin/widgets/card-stats.php
require_once __DIR__ . '/../includes/auth_admin.php';
$db = $conn ?? ($koneksi ?? null);
if (!$db) { die('Koneksi DB tidak ditemukan ($conn / $koneksi).'); }

function rupiah($angka) {
  if ($angka === null) $angka = 0;
  return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Hitung angka
$produk_total    = ($db->query("SELECT COUNT(*) AS c FROM produk WHERE status='aktif'"))->fetch_assoc()['c'] ?? 0;
$kategori_total  = ($db->query("SELECT COUNT(*) AS c FROM kategori WHERE status='aktif'"))->fetch_assoc()['c'] ?? 0;
$pesanan_total   = ($db->query("SELECT COUNT(*) AS c FROM pesanan"))->fetch_assoc()['c'] ?? 0;
$user_total      = ($db->query("SELECT COUNT(*) AS c FROM users"))->fetch_assoc()['c'] ?? 0;

// ...
$reseller_aktif   = ($db->query("SELECT COUNT(*) AS c FROM reseller WHERE status='aktif'"))->fetch_assoc()['c'] ?? 0;
$reseller_pending = ($db->query("SELECT COUNT(*) AS c FROM reseller WHERE status='pending'"))->fetch_assoc()['c'] ?? 0;
// ...

// GMV bulan berjalan (dibayar/dikirim/selesai)
$qGmv = "
SELECT COALESCE(SUM(total_harga),0) AS gmv
FROM pesanan
WHERE status IN ('dibayar','dikirim','selesai')
  AND created_at >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
  AND created_at <  DATE_ADD(DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01'), INTERVAL 1 MONTH)
";
$gmv_bulan_ini = ($db->query($qGmv))->fetch_assoc()['gmv'] ?? 0;
?>
<div class="agw-cards">
  <div class="row g-3">
    <div class="col-6 col-md-4 col-xl-2">
      <div class="agw-card agw-grad-1">
        <div class="agw-card-ico"><i class="fa-solid fa-box"></i></div>
        <div class="agw-card-num"><?= (int)$produk_total ?></div>
        <div class="agw-card-label">Produk Aktif</div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="agw-card agw-grad-2">
        <div class="agw-card-ico"><i class="fa-solid fa-layer-group"></i></div>
        <div class="agw-card-num"><?= (int)$kategori_total ?></div>
        <div class="agw-card-label">Kategori</div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="agw-card agw-grad-3">
        <div class="agw-card-ico"><i class="fa-solid fa-cart-shopping"></i></div>
        <div class="agw-card-num"><?= (int)$pesanan_total ?></div>
        <div class="agw-card-label">Total Pesanan</div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="agw-card agw-grad-4">
        <div class="agw-card-ico"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="agw-card-num"><?= rupiah($gmv_bulan_ini) ?></div>
        <div class="agw-card-label">GMV Bulan Ini</div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="agw-card agw-grad-5">
        <div class="agw-card-ico"><i class="fa-solid fa-store"></i></div>
        <div class="agw-card-num"><?= (int)$reseller_aktif ?></div>
        <div class="agw-card-label">Reseller Aktif</div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="agw-card agw-grad-6">
        <div class="agw-card-ico"><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="agw-card-num"><?= (int)$reseller_pending ?></div>
        <div class="agw-card-label">Reseller Pending</div>
      </div>
    </div>
  </div>
</div>
