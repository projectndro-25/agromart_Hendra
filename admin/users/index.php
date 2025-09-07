<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

/* --------- CSRF --------- */
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf'];

/* --------- FILTER & PAGINATION --------- */
$q      = trim($_GET['q'] ?? '');
$role   = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = max(10, min(50, (int)($_GET['limit'] ?? 10)));
$off    = ($page - 1) * $limit;

$where  = [];
$params = [];
$types  = '';

if ($q !== '') {
  $where[] = "(LOWER(nama) LIKE ? OR LOWER(email) LIKE ?)";
  $like = '%'.strtolower($q).'%';
  $params[] = $like; $types .= 's';
  $params[] = $like; $types .= 's';
}
if ($role !== '') {
  $where[] = "role = ?";
  $params[] = $role; $types .= 's';
}
if ($status !== '') {
  $where[] = "status = ?";
  $params[] = $status; $types .= 's';
}
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/* hitung total */
$sqlCount = "SELECT COUNT(*) AS c FROM users $whereSql";
$stc = mysqli_prepare($conn, $sqlCount);
if ($params) { mysqli_stmt_bind_param($stc, $types, ...$params); }
mysqli_stmt_execute($stc);
$rc    = mysqli_stmt_get_result($stc);
$total = (int)mysqli_fetch_assoc($rc)['c'];
mysqli_free_result($rc);

/* ambil data */
$sql = "
  SELECT id, nama, email, no_hp, role, status, is_blocked, created_at
  FROM users
  $whereSql
  ORDER BY id DESC
  LIMIT ? OFFSET ?
";
$st = mysqli_prepare($conn, $sql);
if ($params) {
  $types2  = $types . 'ii';
  $params2 = array_merge($params, [$limit, $off]);
  mysqli_stmt_bind_param($st, $types2, ...$params2);
} else {
  mysqli_stmt_bind_param($st, 'ii', $limit, $off);
}
mysqli_stmt_execute($st);
$rows = mysqli_stmt_get_result($st);

/* helper */
function badgeRole($r){
  $map = [
    'admin'      => '#ef4444',
    'superadmin' => '#dc2626',
    'reseller'   => '#7c3aed',
    'user'       => '#2563eb',
  ];
  $c = $map[strtolower($r)] ?? '#475569';
  return '<span style="padding:4px 10px;border-radius:999px;background:'.$c.';color:#fff;font-size:12px">'.htmlspecialchars($r).'</span>';
}
function fmtDate($s){ return $s ? htmlspecialchars(date('Y-m-d H:i', strtotime($s))) : '-'; }

/* pagination nav */
$pages = (int)ceil($total / $limit);

/* message */
$msg = trim($_GET['msg'] ?? '');
?>

<style>
/* === Perapihan khusus halaman Users (scoped) === */
.users-page .table-wrap{overflow-x:auto}
.users-page .table{table-layout:fixed;width:100%}
.users-page .table th,.users-page .table td{vertical-align:middle;white-space:nowrap}

/* Nama & Email boleh melipat */
.users-page .table td:nth-child(2),
.users-page .table td:nth-child(3){white-space:normal}

/* Email wrap aman + batasi lebar agar tidak menabrak kolom lain */
.users-page td.email-cell{
  white-space:normal;
  word-break:break-word;
  overflow-wrap:anywhere;
  max-width:280px; /* boleh ubah sesuai selera */
}

.users-page .col-no{width:72px;text-align:center}
.users-page .col-role{width:120px}
.users-page .col-status{width:120px}
.users-page .col-aksi{width:260px;text-align:center}
.users-page .toolbar .input{min-width:240px}
.users-page .toolbar .select{min-width:140px}
.users-page h1{margin-top:18px}
.actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}

/* === Modern header (Gen Z friendly) === */
.users-page .table thead th{
  background:#f5f7fb;              /* soft background */
  position:sticky; top:0; z-index:2;
  font-weight:800; letter-spacing:.2px;
  height:56px;                      /* lebih lega */
  border-bottom:2px solid #e5e7eb;
}
.users-page .table thead th:first-child{ border-top-left-radius:12px; }
.users-page .table thead th:last-child{  border-top-right-radius:12px; }
.users-page .table{
  border-radius:12px;
  box-shadow:0 10px 24px rgba(0,0,0,.06); /* floating card */
  overflow:hidden;
}

/* garis pemisah lembut */
.users-page .table td{border-color:#eef1f5}

/* badge nonaktif kecil */
.users-page .muted{opacity:.7}

/* kecilkan tombol paging */
.users-page .pager .btn{padding:6px 10px}
</style>

<div class="content">
  <div class="page users-page">
    <h1>👥 Pengguna</h1>

    <?php if ($msg): ?>
      <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="get" class="toolbar">
      <input class="input" type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari nama / email…">
      <select class="select" name="role">
        <option value="">Semua Role</option>
        <?php foreach (['admin','superadmin','reseller','user'] as $r): ?>
          <option value="<?= $r ?>" <?= $role===$r?'selected':'' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="select" name="status">
        <option value="">Semua Status</option>
        <?php foreach (['aktif','nonaktif'] as $s): ?>
          <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <select class="select" name="limit">
        <?php foreach ([10,20,50] as $n): ?>
          <option value="<?= $n ?>" <?= $limit===$n?'selected':'' ?>><?= $n ?>/hal</option>
        <?php endforeach; ?>
      </select>
      <button class="btn" type="submit">Filter</button>
      <a class="btn btn-secondary" href="index.php">Reset</a>
      <div style="flex:1"></div>
      <a class="btn" style="background:#16a34a" href="tambah.php">+ Tambah User</a>
    </form>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th class="col-no">NO</th>
            <th>Nama</th>
            <th>Email</th>
            <th>No HP</th>
            <th class="col-role">Role</th>
            <th class="col-status">Status</th>
            <th>Dibuat</th>
            <th class="col-aksi">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($rows && mysqli_num_rows($rows)): $no = $off+1; ?>
          <?php while($r = mysqli_fetch_assoc($rows)): ?>
            <tr>
              <td class="col-no"><?= $no++ ?></td>
              <td><?= htmlspecialchars($r['nama']) ?></td>
              <td class="email-cell">
                <?php if (!empty($r['email'])): ?>
                  <a href="mailto:<?= htmlspecialchars($r['email']) ?>"><?= htmlspecialchars($r['email']) ?></a>
                <?php else: ?> <span class="muted">-</span> <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($r['no_hp'] ?? '-') ?></td>
              <td><?= badgeRole($r['role']) ?></td>
              <td>
                <?php if (strtolower($r['status'])==='aktif'): ?>
                  <span class="badge badge-ok">Aktif</span>
                <?php else: ?>
                  <span class="badge badge-off">Nonaktif</span>
                <?php endif; ?>
              </td>
              <td><?= fmtDate($r['created_at']) ?></td>
              <td class="actions col-aksi">
                <a class="btn" href="edit.php?id=<?= (int)$r['id'] ?>">✏️ Edit</a>
                <a class="btn btn-warning" href="reset_password.php?id=<?= (int)$r['id'] ?>">🔑 Reset</a>
                <?php
                  $self      = ((int)$r['id'] === (int)($_SESSION['user_id'] ?? 0));
                  $roleLower = strtolower($r['role']);
                  $isSuper   = ($roleLower === 'superadmin');
                  $canDelete = (!$isSuper) && (!$self);
                ?>
                <?php if ($canDelete): ?>
                  <form action="hapus.php" method="post" onsubmit="return confirm('Hapus user ini?')">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn" style="border-color:#ef4444;color:#ef4444;background:#fff">🗑 Hapus</button>
                  </form>
                <?php else: ?>
                  <span class="badge" title="Tidak bisa hapus superadmin / diri sendiri">🚫 Tidak bisa hapus</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" style="text-align:center;padding:16px">Tidak ada pengguna yang cocok.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages>1): ?>
      <div class="pager" style="display:flex;gap:6px;align-items:center;margin-top:12px">
        <?php for($i=1;$i<=$pages;$i++): ?>
          <a class="btn<?= $i===$page?' btn-secondary':'' ?>" style="padding:6px 10px"
             href="?<?= http_build_query(['q'=>$q,'role'=>$role,'status'=>$status,'limit'=>$limit,'page'=>$i]) ?>">
             <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

  </div><!-- /.page -->
</div><!-- /.content -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
