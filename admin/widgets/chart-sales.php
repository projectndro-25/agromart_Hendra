<?php
// admin/widgets/chart-sales.php
require_once __DIR__ . '/../includes/auth_admin.php';
$db = $conn ?? ($koneksi ?? null);
if (!$db) { die('Koneksi DB tidak ditemukan ($conn / $koneksi).'); }

// Ambil 30 hari terakhir
$sql = "
SELECT 
  DATE(created_at) as tgl,
  COALESCE(SUM(CASE WHEN status IN ('dibayar','dikirim','selesai') THEN total_harga ELSE 0 END),0) AS gmv,
  COUNT(*) AS orders
FROM pesanan
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
GROUP BY DATE(created_at)
ORDER BY DATE(created_at)
";
$rs = $db->query($sql);
$labels=[]; $gmv=[]; $orders=[];
while($row = $rs->fetch_assoc()){
  $labels[] = $row['tgl'];
  $gmv[]    = (float)$row['gmv'];
  $orders[] = (int)$row['orders'];
}
?>
<div class="card agw-panel">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0">Tren Penjualan (30 Hari)</h5>
      <span class="text-muted small">GMV & jumlah pesanan</span>
    </div>
    <canvas id="salesChart" height="110"></canvas>
  </div>
</div>
<script>
(function(){
  const ctx = document.getElementById('salesChart').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($labels) ?>,
      datasets: [
        {
          label: 'GMV',
          data: <?= json_encode($gmv) ?>,
          fill: true,
          tension: 0.35
        },
        {
          label: 'Orders',
          data: <?= json_encode($orders) ?>,
          fill: false,
          tension: 0.35
        }
      ]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
})();
</script>
