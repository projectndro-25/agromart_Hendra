<?php
// admin/widgets/table-recent-orders.php
require_once __DIR__ . '/../includes/auth_admin.php';
$db = $conn ?? ($koneksi ?? null);
if (!$db) { die('Koneksi DB tidak ditemukan ($conn / $koneksi).'); }

$sql = "
SELECT pe.id, ub.nama AS pembeli, ur.nama AS reseller, pe.total_harga, pe.status, pe.created_at
FROM pesanan pe
LEFT JOIN users ub ON pe.user_id = ub.id
LEFT JOIN users ur ON pe.reseller_id = ur.id
ORDER BY pe.created_at DESC
LIMIT 10
";
$rs = $db->query($sql);
?>
<div class="card agw-panel">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0">Pesanan Terbaru</h6>
      <a class="btn btn-sm btn-light" href="pesanan/index.php"><i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Semua</a>
    </div>
    <table id="recentOrdersTable" class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>No. Pesanan</th>
          <th>Pembeli</th>
          <th>Reseller</th>
          <th>Total</th>
          <th>Status</th>
          <th>Tanggal</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $rs->fetch_assoc()): ?>
          <tr>
            <td>#<?= (int)$row['id'] ?></td>
            <td><?= htmlspecialchars($row['pembeli'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['reseller'] ?? '-') ?></td>
            <td>Rp <?= number_format((float)$row['total_harga'],0,',','.') ?></td>
            <td><span class="badge bg-outline agw-badge-<?= htmlspecialchars($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
            <td><?= htmlspecialchars($row['created_at']) ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="pesanan/detail.php?id=<?= (int)$row['id'] ?>">Detail</a></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  if (window.DataTable) new DataTable('#recentOrdersTable', { perPage: 10, searchable: true, fixedHeight: true });
});
</script>
