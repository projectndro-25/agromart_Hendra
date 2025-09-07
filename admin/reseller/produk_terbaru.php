<?php
// admin/reseller/produk_terbaru.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

$kw = trim($_GET['q'] ?? '');
$limit = max(10, min(300, (int)($_GET['limit'] ?? 100)));

$where = [];
$params = [];
$types  = '';

if ($kw !== '') {
  // cari di nama produk / nama toko / email
  $where[] = "(p.nama_produk LIKE ? OR r.nama_toko LIKE ? OR u.email LIKE ?)";
  $like = "%{$kw}%";
  $params[] = $like; $types .= 's';
  $params[] = $like; $types .= 's';
  $params[] = $like; $types .= 's';
}
$wsql = $where ? ('WHERE '.implode(' AND ',$where)) : '';

$sql = "
  SELECT 
    p.id, p.nama_produk, p.harga, p.stok, p.created_at, COALESCE(p.updated_at, p.created_at) AS ts,
    r.id AS reseller_id, r.nama_toko, r.status AS status_reseller,
    u.id AS user_id, u.email
  FROM produk p
  JOIN reseller r ON r.id = p.reseller_id
  LEFT JOIN users u ON u.id = r.user_id
  $wsql
  ORDER BY COALESCE(p.updated_at, p.created_at) DESC
  LIMIT ?
";
$params[] = $limit; $types .= 'i';

$stmt = mysqli_prepare($conn, $sql);
if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$rs = mysqli_stmt_get_result($stmt);
$rows = $rs ? mysqli_fetch_all($rs, MYSQLI_ASSOC) : [];
mysqli_stmt_close($stmt);

// partial render (AJAX refresh) → kirim hanya <tbody>
if (isset($_GET['partial']) && $_GET['partial']=='1') {
  foreach ($rows as $i=>$row) {
    echo '<tr>';
    echo '<td>'.($i+1).'</td>';
    echo '<td><a href="../produk/detail.php?id='.(int)$row['id'].'" class="link">'.htmlspecialchars($row['nama_produk']).'</a></td>';
    echo '<td>Rp '.number_format((float)$row['harga'],0,',','.').'</td>';
    echo '<td>'.(int)$row['stok'].'</td>';
    echo '<td><a href="detail.php?id='.(int)$row['reseller_id'].'" class="link">'.htmlspecialchars($row['nama_toko']).'</a></td>';
    echo '<td><a href="mailto:'.htmlspecialchars($row['email']).'">'.htmlspecialchars($row['email']).'</a></td>';
    $badge = ($row['status_reseller']==='aktif'?'success':($row['status_reseller']==='blokir'?'danger':'warning'));
    echo '<td><span class="badge bg-'.$badge.'">'.htmlspecialchars($row['status_reseller']).'</span></td>';
    echo '<td>'.htmlspecialchars($row['ts']).'</td>';
    echo '</tr>';
  }
  exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>
<div class="content" style="padding:20px">
  <h1>🧭 Produk Terbaru (Global)</h1>

  <form method="get" class="mb-3" style="display:flex;gap:8px;align-items:center">
    <input type="text" name="q" value="<?= htmlspecialchars($kw) ?>" placeholder="Cari produk / toko / email..."
           class="form-control" style="max-width:360px">
    <select name="limit" class="form-select" style="width:120px">
      <?php foreach([50,100,150,200,300] as $opt): ?>
        <option value="<?= $opt ?>" <?= $opt==$limit?'selected':'' ?>>Limit <?= $opt ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary">Filter</button>
    <button type="button" class="btn btn-light" onclick="location.href='produk_terbaru.php'">Reset</button>
  </form>

  <div class="card agw-panel">
    <div class="card-body">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <div class="text-muted">Auto-refresh tiap 10 detik • <?= count($rows) ?> item</div>
        <button id="btnRefresh" class="btn btn-sm btn-outline-secondary">Refresh</button>
      </div>
      <div class="table-responsive">
        <table class="table align-middle" id="tbl">
          <thead>
            <tr>
              <th>#</th>
              <th>Produk</th>
              <th>Harga</th>
              <th>Stok</th>
              <th>Toko</th>
              <th>Email</th>
              <th>Status Reseller</th>
              <th>Update</th>
            </tr>
          </thead>
          <tbody id="tbody">
          <?php foreach ($rows as $i=>$row): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><a class="link" href="../produk/detail.php?id=<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['nama_produk']) ?></a></td>
              <td>Rp <?= number_format((float)$row['harga'],0,',','.') ?></td>
              <td><?= (int)$row['stok'] ?></td>
              <td><a class="link" href="detail.php?id=<?= (int)$row['reseller_id'] ?>"><?= htmlspecialchars($row['nama_toko']) ?></a></td>
              <td><a href="mailto:<?= htmlspecialchars($row['email']) ?>"><?= htmlspecialchars($row['email']) ?></a></td>
              <?php $badge = ($row['status_reseller']==='aktif'?'success':($row['status_reseller']==='blokir'?'danger':'warning')); ?>
              <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($row['status_reseller']) ?></span></td>
              <td><?= htmlspecialchars($row['ts']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// Auto-refresh table body tiap 10 detik (tanpa reload full)
const q = new URLSearchParams(location.search);
function refreshBody(){
  const u = new URL(location.href);
  u.searchParams.set('partial','1');
  fetch(u.toString()).then(r=>r.text()).then(html=>{
    document.getElementById('tbody').innerHTML = html;
  });
}
document.getElementById('btnRefresh').addEventListener('click', refreshBody);
setInterval(refreshBody, 10000);
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
