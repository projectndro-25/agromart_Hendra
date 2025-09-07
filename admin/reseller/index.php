<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : 'aktif';
$allowed = ['aktif','blokir','semua'];
if (!in_array($status,$allowed)) $status = 'aktif';

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$where = [];
if ($status !== 'semua') {
  $where[] = "r.status = '".mysqli_real_escape_string($conn,$status)."'";
}
if ($search !== '') {
  $s = mysqli_real_escape_string($conn,$search);
  $where[] = "(r.nama_toko LIKE '%$s%' OR u.email LIKE '%$s%' OR u.nama LIKE '%$s%')";
}
$whereSql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

$sql = "
SELECT r.*, u.nama AS nama_user, u.email
FROM reseller r
JOIN users u ON u.id = r.user_id
$whereSql
ORDER BY r.created_at DESC
";
$rs = mysqli_query($conn,$sql);
?>
<div class="content" style="padding:20px">
  <h1>🛍️ Reseller</h1>

  <form method="get" class="mb-3" style="display:flex;gap:8px;flex-wrap:wrap">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama toko / email / nama user" class="form-control" style="max-width:280px">
    <select name="status" class="form-select" style="max-width:160px">
      <option value="aktif"  <?= $status==='aktif'?'selected':'' ?>>Aktif</option>
      <option value="blokir" <?= $status==='blokir'?'selected':'' ?>>Blokir</option>
      <option value="semua"  <?= $status==='semua'?'selected':'' ?>>Semua</option>
    </select>
    <button class="btn btn-primary">Filter</button>
    <a class="btn btn-light" href="index.php">Reset</a>
  </form>

  <div class="card agw-panel">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Nama Toko</th>
              <th>Email</th>
              <th>Status</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($rs && mysqli_num_rows($rs)): while($r=mysqli_fetch_assoc($rs)): ?>
            <tr>
              <td><?= htmlspecialchars($r['nama_toko']) ?></td>
              <td><a href="mailto:<?= htmlspecialchars($r['email']) ?>"><?= htmlspecialchars($r['email']) ?></a></td>
              <td>
                <?php $st=strtolower($r['status']);
                  $badge = $st==='aktif'?'success':($st==='blokir'?'danger':'warning'); ?>
                <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($r['status']) ?></span>
              </td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="detail.php?id=<?= (int)$r['id'] ?>">Detail</a>
                <?php if ($st==='blokir'): ?>
                  <a class="btn btn-sm btn-success" href="buka_blokir.php?id=<?= (int)$r['id'] ?>">Unblokir</a>
                <?php else: ?>
                  <a class="btn btn-sm btn-outline-danger" href="blokir.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Blokir reseller ini?')">Blokir</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
