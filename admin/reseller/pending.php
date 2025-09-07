<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$sql = "
SELECT r.*, u.nama AS nama_user, u.email
FROM reseller r
JOIN users u ON u.id=r.user_id
WHERE r.status='pending'
ORDER BY r.created_at DESC
";
$rs = mysqli_query($conn,$sql);
?>
<div class="content" style="padding:20px">
  <h1>⏳ Pending Reseller</h1>

  <div class="card agw-panel">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Nama Toko</th>
              <th>Email</th>
              <th>Tanggal Daftar</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($rs && mysqli_num_rows($rs)): while($r=mysqli_fetch_assoc($rs)): ?>
            <tr>
              <td><?= htmlspecialchars($r['nama_toko']) ?></td>
              <td><a href="mailto:<?= htmlspecialchars($r['email']) ?>"><?= htmlspecialchars($r['email']) ?></a></td>
              <td><?= htmlspecialchars($r['created_at']) ?></td>
              <td class="text-end">
                <a href="approve.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-success">Approve</a>
                <a href="reject.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tolak reseller ini?')">Reject</a>
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
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
