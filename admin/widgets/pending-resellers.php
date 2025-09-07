<?php
// admin/widgets/pending-resellers.php
// Widget dashboard: daftar reseller dengan status 'pending' (ambil dari TABEL reseller)
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

$sql = "
  SELECT r.id, r.nama_toko, r.created_at, u.email
  FROM reseller r
  JOIN users u ON u.id = r.user_id
  WHERE r.status = 'pending'
  ORDER BY r.created_at DESC
  LIMIT 5
";
$rs = mysqli_query($conn, $sql);
?>
<div class="card agw-panel">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0">Reseller Menunggu Approval</h6>
      <a class="btn btn-sm btn-success" href="/agromart/admin/reseller/pending.php">
        <i class="fa-solid fa-users"></i>&nbsp; Lihat Semua
      </a>
    </div>

    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead>
          <tr>
            <th>Nama Toko</th>
            <th>User/Email</th>
            <th>Tanggal Daftar</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($rs && mysqli_num_rows($rs)): while($row = mysqli_fetch_assoc($rs)): ?>
            <tr>
              <td><?= htmlspecialchars($row['nama_toko']) ?></td>
              <td><a href="mailto:<?= htmlspecialchars($row['email']) ?>"><?= htmlspecialchars($row['email']) ?></a></td>
              <td><?= htmlspecialchars($row['created_at']) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="/agromart/admin/reseller/pending.php">Kelola</a>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr>
              <td colspan="4" class="text-center text-muted">Tidak ada reseller pending.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
