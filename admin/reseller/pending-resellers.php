<?php
// admin/widgets/pending-resellers.php
require_once __DIR__ . '/../includes/auth_admin.php';
$db = $conn ?? ($koneksi ?? null);
if (!$db) { die('Koneksi DB tidak ditemukan'); }

$sql = "
SELECT r.id, r.nama_toko, r.created_at, u.email
FROM reseller r
JOIN users u ON u.id=r.user_id
WHERE r.status='pending'
ORDER BY r.created_at DESC
LIMIT 5
";
$rs = $db->query($sql);
?>
<div class="card agw-panel">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h6 class="mb-0">Reseller Menunggu Approval</h6>
      <a class="btn btn-sm btn-light" href="../reseller/pending.php"><i class="fa-solid fa-users"></i> Lihat Semua</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead><tr><th>Nama Toko</th><th>Email</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        <?php if($rs && $rs->num_rows>0): while($r=$rs->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($r['nama_toko']) ?></td>
            <td><a href="mailto:<?= htmlspecialchars($r['email']) ?>"><?= htmlspecialchars($r['email']) ?></a></td>
            <td><?= htmlspecialchars($r['created_at']) ?></td>
            <td class="text-end">
              <a href="../reseller/approve.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-success">Approve</a>
              <a href="../reseller/reject.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tolak reseller ini?')">Tolak</a>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="4" class="text-center text-muted">Tidak ada reseller pending.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
