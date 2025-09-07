<?php
// admin/widgets/chart-order-status.php
require_once __DIR__ . '/../includes/auth_admin.php';
$db = $conn ?? ($koneksi ?? null);
if (!$db) { die('Koneksi DB tidak ditemukan ($conn / $koneksi).'); }

$statuses = ['pending','dibayar','dikirim','selesai','batal'];
$data = [];
foreach ($statuses as $s) {
  $r = $db->query("SELECT COUNT(*) AS c FROM pesanan WHERE status='$s'")->fetch_assoc();
  $data[] = (int)($r['c'] ?? 0);
}
?>
<div class="card agw-panel">
  <div class="card-body">
    <h6 class="mb-3">Distribusi Status Pesanan</h6>
    <canvas id="orderStatusChart" height="170"></canvas>
  </div>
</div>
<script>
(function(){
  const ctx = document.getElementById('orderStatusChart').getContext('2d');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($statuses) ?>,
      datasets: [{ data: <?= json_encode($data) ?> }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'bottom' },
        tooltip: { enabled: true }
      },
      cutout: '60%'
    }
  });
})();
</script>
