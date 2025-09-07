<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

/* ===== CSRF ===== */
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf'];

/* ===== helper: cek kolom ada/tidak ===== */
function col_exists($conn, $table, $col) {
  $q = mysqli_prepare($conn, "
    SELECT COUNT(*) c FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name=? AND column_name=?
  ");
  mysqli_stmt_bind_param($q, "ss", $table, $col);
  mysqli_stmt_execute($q);
  $r = mysqli_stmt_get_result($q);
  return $r && (int)mysqli_fetch_assoc($r)['c'] > 0;
}
$hasResellerCol = col_exists($conn, 'pesanan', 'reseller_id');

$statuses = ['pending','dibayar','dikirim','selesai','batal'];

/* ===== FILTERS ===== */
$q         = trim($_GET['q'] ?? '');
$status    = trim($_GET['status'] ?? '');
$date      = trim($_GET['date'] ?? ''); // satu tanggal (YYYY-MM-DD)
$reseller  = (int)($_GET['reseller'] ?? 0);
$perPage   = (int)($_GET['per'] ?? 10);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = in_array($perPage, [10,25,50,100], true) ? $perPage : 10;
$offset    = ($page - 1) * $perPage;

/* ===== Build WHERE (prepared) ===== */
$where  = [];
$params = [];
$types  = '';

if ($q !== '') {
  $like = '%'.strtolower($q).'%';
  if ($hasResellerCol) {
    $where[]  = "(CAST(p.id AS CHAR) LIKE ? OR LOWER(u.nama) LIKE ? OR LOWER(r.nama) LIKE ?)";
    $params[] = $like; $types .= 's';
    $params[] = $like; $types .= 's';
    $params[] = $like; $types .= 's';
  } else {
    $where[]  = "(CAST(p.id AS CHAR) LIKE ? OR LOWER(u.nama) LIKE ?)";
    $params[] = $like; $types .= 's';
    $params[] = $like; $types .= 's';
  }
}
if ($status !== '' && in_array($status, $statuses, true)) {
  $where[]  = "p.status = ?";
  $params[] = $status; $types .= 's';
}
if ($date !== '') { // filter 1 tanggal
  $where[]  = "DATE(p.created_at) = ?";
  $params[] = $date; $types .= 's';
}
if ($hasResellerCol && $reseller > 0) {
  $where[]  = "p.reseller_id = ?";
  $params[] = $reseller; $types .= 'i';
}
$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/* ===== COUNT ===== */
$joinUser = "LEFT JOIN users u ON u.id = p.user_id";
$joinRes  = $hasResellerCol ? "LEFT JOIN users r ON r.id = p.reseller_id" : "";

$sqlCount = "
  SELECT COUNT(*) c
  FROM pesanan p
  $joinUser
  $joinRes
  $whereSql
";
$stc = mysqli_prepare($conn, $sqlCount);
if ($types) { mysqli_stmt_bind_param($stc, $types, ...$params); }
mysqli_stmt_execute($stc);
$resc  = mysqli_stmt_get_result($stc);
$total = (int)mysqli_fetch_assoc($resc)['c'];

/* ===== DATA ===== */
$selectReseller = $hasResellerCol ? ", r.nama AS reseller_nama" : ", NULL AS reseller_nama";

$sql = "
  SELECT
    p.id,
    p.user_id,
    ".($hasResellerCol ? "p.reseller_id" : "NULL AS reseller_id").",
    IFNULL(p.total_harga,0) AS total_harga,
    p.status,
    p.created_at,
    p.catatan,
    IFNULL(p.is_flagged,0) AS is_flagged,
    p.flag_note,
    u.nama  AS pembeli,
    u.email AS pembeli_email
    $selectReseller
  FROM pesanan p
  $joinUser
  $joinRes
  $whereSql
  ORDER BY p.id DESC
  LIMIT ? OFFSET ?
";
$st = mysqli_prepare($conn, $sql);
if ($types) {
  $types2  = $types . 'ii';
  $params2 = array_merge($params, [$perPage, $offset]);
  mysqli_stmt_bind_param($st, $types2, ...$params2);
} else {
  mysqli_stmt_bind_param($st, 'ii', $perPage, $offset);
}
mysqli_stmt_execute($st);
$rows = mysqli_stmt_get_result($st);

/* ===== reseller dropdown (optional) ===== */
$listReseller = null;
if ($hasResellerCol) {
  $listReseller = mysqli_query($conn,
    "SELECT id, nama FROM users WHERE LOWER(role)='reseller' ORDER BY nama ASC");
}

/* ===== pagination ===== */
$pages = max(1, (int)ceil($total / $perPage));
?>
<style>
  .orders .table-wrap{overflow-x:auto}
  .orders .badge{border-radius:999px;padding:4px 10px;font-weight:700;font-size:12px}
  .orders .b-pending{background:#fff7ed;color:#b45309}
  .orders .b-dibayar{background:#e0f2fe;color:#075985}
  .orders .b-dikirim{background:#ede9fe;color:#5b21b6}
  .orders .b-selesai{background:#e9f9ee;color:#27ae60}
  .orders .b-batal{background:#ffeaea;color:#d63031}
  .orders .toolbar .input{min-width:240px}
  .orders .toolbar .select{min-width:140px}

  /* Kolom PANTAU */
  .orders td .watch {display:flex;flex-direction:column;align-items:flex-start;gap:8px}
  .orders td .watch .note{
    white-space:pre-wrap;
    overflow-wrap:anywhere;
    word-break:break-word;
    border:1px solid #eef1f5;
    background:#fff;
    border-radius:10px;
    padding:8px 10px;
    max-width:420px;
    line-height:1.5;
  }
  .orders td .watch .flag-badge{
    background:#ffeaea;color:#d63031;border-radius:999px;font-weight:800;
    font-size:12px;padding:6px 12px;display:inline-block
  }

  /* Bar filter */
  .toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
  .toolbar-left{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
  .toolbar-right{display:flex;gap:8px;align-items:center;margin-left:auto}
</style>

<div class="content">
  <div class="page orders">
    <h1>📦 Pesanan</h1>

    <!-- Filter bar -->
    <form class="toolbar" method="get">
      <!-- KIRI: pencarian + filter + per/terapkan/reset -->
      <div class="toolbar-left">
        <input class="input" type="text" name="q"
               placeholder="Cari no. pesanan / pembeli<?= $hasResellerCol?', reseller':'' ?>"
               value="<?= htmlspecialchars($q) ?>">

        <select class="select" name="status">
          <option value="">Semua Status</option>
          <?php foreach ($statuses as $s): ?>
            <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>

        <!-- Satu input tanggal -->
        <input class="input" type="date" name="date" value="<?= htmlspecialchars($date) ?>">

        <?php if ($hasResellerCol): ?>
          <select class="select" name="reseller" title="Reseller">
            <option value="0">Semua Reseller</option>
            <?php if ($listReseller): while($r = mysqli_fetch_assoc($listReseller)): ?>
              <option value="<?= (int)$r['id'] ?>" <?= $reseller===(int)$r['id']?'selected':'' ?>>
                <?= htmlspecialchars($r['nama']) ?>
              </option>
            <?php endwhile; endif; ?>
          </select>
        <?php endif; ?>

        <!-- DIPINDAH KE KIRI -->
        <select class="select" name="per">
          <?php foreach ([10,25,50,100] as $n): ?>
            <option value="<?= $n ?>" <?= $perPage===$n?'selected':'' ?>><?= $n ?>/hal</option>
          <?php endforeach; ?>
        </select>

        <button class="btn" type="submit">Terapkan</button>
        <a class="btn btn-secondary" href="index.php">Reset</a>
      </div>

      <!-- KANAN: Export saja (nempel kanan) -->
      <div class="toolbar-right">
        <a class="btn" href="export.php?<?= http_build_query([
          'q'=>$q,'status'=>$status,'date'=>$date
        ] + ($hasResellerCol?['reseller'=>$reseller]:[])) ?>">⬇️ Export</a>
      </div>
    </form>

    <div class="table-wrap">
      <table class="table">
        <thead>
        <tr>
          <th style="width:90px">No</th>
          <th>Pembeli</th>
          <th>Reseller<?= $hasResellerCol?'':' (—)' ?></th>
          <th>Total</th>
          <th>Status</th>
          <th>Tanggal</th>
          <th>Pantau</th>
          <th style="width:240px">Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($rows && mysqli_num_rows($rows)>0): ?>
          <?php while($o = mysqli_fetch_assoc($rows)): ?>
            <tr>
              <td><?= (int)$o['id'] ?></td>
              <td>
                <?= htmlspecialchars($o['pembeli'] ?? '-') ?><br>
                <?php if (!empty($o['pembeli_email'])): ?>
                  <a href="mailto:<?= htmlspecialchars($o['pembeli_email']) ?>">
                    <?= htmlspecialchars($o['pembeli_email']) ?>
                  </a>
                <?php else: ?>
                  <span class="muted">-</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($o['reseller_nama'] ?? '-') ?></td>
              <td>Rp <?= number_format((float)$o['total_harga'],0,',','.') ?></td>
              <td>
                <?php $b = 'b-'.($o['status'] ?: 'pending'); ?>
                <span class="badge <?= htmlspecialchars($b) ?>"><?= ucfirst($o['status'] ?: 'pending') ?></span>
              </td>
              <td><?= htmlspecialchars($o['created_at'] ?? '-') ?></td>

              <!-- Kolom Pantau -->
              <td>
                <div class="watch">
                  <?php if ((int)$o['is_flagged'] === 1): ?>
                    <span class="flag-badge">Dipantau</span>
                  <?php else: ?>
                    <span class="badge b-pending" style="background:#f3f4f6;color:#6b7280">Normal</span>
                  <?php endif; ?>

                  <?php if (!empty($o['flag_note'])): ?>
                    <div class="note"><?= nl2br(htmlspecialchars($o['flag_note'])) ?></div>
                  <?php endif; ?>
                </div>
              </td>

              <td class="actions">
                <a class="btn" href="detail.php?id=<?= (int)$o['id'] ?>">Detail</a>
                <a class="btn btn-warning" href="pantau.php?id=<?= (int)$o['id'] ?>">Pantau</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" style="text-align:center;padding:16px">Belum ada pesanan yang cocok.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages>1): ?>
      <div style="display:flex;gap:6px;align-items:center;margin-top:12px;flex-wrap:wrap">
        <?php for($i=1;$i<=$pages;$i++): ?>
          <a class="btn<?= $i===$page?' btn-secondary':'' ?>"
             href="?<?= http_build_query([
               'q'=>$q,'status'=>$status,'date'=>$date,'per'=>$perPage,'page'=>$i
             ] + ($hasResellerCol?['reseller'=>$reseller]:[])) ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
