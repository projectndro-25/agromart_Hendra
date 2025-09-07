<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf'];

$id = (int)($_GET['id'] ?? 0);
if ($id<=0){ echo "<script>alert('Pesanan tidak valid');location.href='index.php';</script>"; exit; }

function col_exists($c,$t,$col){
  $q = mysqli_prepare($c,"SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?");
  mysqli_stmt_bind_param($q,"ss",$t,$col); mysqli_stmt_execute($q);
  $r = mysqli_stmt_get_result($q); return $r && (int)mysqli_fetch_assoc($r)['c']>0;
}
function table_exists($c,$t){
  $q = mysqli_prepare($c,"SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
  mysqli_stmt_bind_param($q,"s",$t); mysqli_stmt_execute($q);
  $r = mysqli_stmt_get_result($q); return $r && (int)mysqli_fetch_assoc($r)['c']>0;
}
$hasResellerCol = col_exists($conn,'pesanan','reseller_id');
$hasTimeline    = table_exists($conn,'order_status_history');
$hasDetail      = table_exists($conn,'detail_pesanan');

$STATUSES = ['pending','dibayar','dikirim','selesai','batal'];

/* update status */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_status'])) {
  if (!hash_equals($csrf, $_POST['csrf'] ?? '')) { die('CSRF mismatch'); }
  $new = trim($_POST['status'] ?? '');
  if (!in_array($new,$STATUSES,true)) { $new='pending'; }

  // ambil current
  $cur = mysqli_query($conn,"SELECT status FROM pesanan WHERE id=".$id." LIMIT 1");
  $old = $cur && mysqli_num_rows($cur)? strtolower(mysqli_fetch_assoc($cur)['status']):'pending';

  $st = mysqli_prepare($conn,"UPDATE pesanan SET status=?, updated_at=NOW() WHERE id=?");
  mysqli_stmt_bind_param($st,"si",$new,$id);
  mysqli_stmt_execute($st);

  if ($hasTimeline && $old !== $new) {
    $adminId = (int)($_SESSION['user_id'] ?? 0);
    $tl = mysqli_prepare($conn,"INSERT INTO order_status_history(pesanan_id,status_from,status_to,changed_at,changed_by) VALUES(?,?,?,NOW(),?)");
    mysqli_stmt_bind_param($tl,"sssi",$id,$old,$new,$adminId);
    mysqli_stmt_execute($tl);
  }
  header("Location: detail.php?id=".$id."&msg=".urlencode("Status diperbarui"));
  exit;
}

/* header + user/reseller */
$selectRes = $hasResellerCol ? ", r.nama AS reseller_nama, r.email AS reseller_email" : "";
$sql = "
  SELECT p.*, u.nama AS pembeli, u.email AS pembeli_email
  $selectRes
  FROM pesanan p
  LEFT JOIN users u ON u.id=p.user_id
  ".($hasResellerCol? "LEFT JOIN users r ON r.id=p.reseller_id" : "")."
  WHERE p.id=? LIMIT 1
";
$st = mysqli_prepare($conn,$sql);
mysqli_stmt_bind_param($st,"i",$id);
mysqli_stmt_execute($st);
$hdr = mysqli_stmt_get_result($st);
if (!$hdr || !mysqli_num_rows($hdr)) { echo "<script>alert('Pesanan tidak ditemukan');location.href='index.php';</script>"; exit; }
$ord = mysqli_fetch_assoc($hdr);

/* items */
$items = null;
if ($hasDetail) {
  $items = mysqli_prepare($conn,"
    SELECT d.id, d.jumlah, d.harga, d.subtotal, p.nama_produk, p.id AS produk_id
    FROM detail_pesanan d
    LEFT JOIN produk p ON p.id=d.produk_id
    WHERE d.pesanan_id=?
    ORDER BY d.id ASC
  ");
  mysqli_stmt_bind_param($items,"i",$id);
  mysqli_stmt_execute($items);
  $items = mysqli_stmt_get_result($items);
}

/* timeline */
$timeline = null;
if ($hasTimeline) {
  $tl = mysqli_prepare($conn,"SELECT status_from,status_to,changed_at,changed_by FROM order_status_history WHERE pesanan_id=? ORDER BY changed_at ASC");
  mysqli_stmt_bind_param($tl,"i",$id);
  mysqli_stmt_execute($tl);
  $timeline = mysqli_stmt_get_result($tl);
}
$msg = trim($_GET['msg'] ?? '');
?>
<style>
  .order-detail .pill{border-radius:999px;padding:4px 12px;font-weight:700;font-size:12px}
  .s-pending{background:#fff7ed;color:#b45309}.s-dibayar{background:#e0f2fe;color:#075985}
  .s-dikirim{background:#ede9fe;color:#5b21b6}.s-selesai{background:#e9f9ee;color:#27ae60}
  .s-batal{background:#ffeaea;color:#d63031}
  .cardx{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08);padding:18px}
  .grid2{display:grid;grid-template-columns:2fr 1fr;gap:16px}
  .toolbar{display:flex;gap:10px;align-items:center;margin-bottom:10px;flex-wrap:wrap}
  .input,.select{padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px}
  .muted{opacity:.7}
</style>

<div class="content">
  <div class="page order-detail">
    <h1>🧾 Pesanan <?= (int)$ord['id'] ?> <span class="pill s-<?= htmlspecialchars($ord['status']) ?>"><?= ucfirst($ord['status']) ?></span></h1>

    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <form method="post" class="toolbar">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <select class="select" name="status">
        <?php foreach($STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= strtolower($ord['status'])===$s?'selected':'' ?>>Ubah ke: <?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-warning" name="update_status" value="1">Update Status</button>
      <a class="btn" href="pantau.php?id=<?= (int)$ord['id'] ?>">Pantau</a>
      <a class="btn" href="export.php?id=<?= (int)$ord['id'] ?>">Export</a>
      <div style="flex:1"></div>
      <a class="btn btn-secondary" href="index.php">Kembali</a>
    </form>

    <div class="grid2">
      <div class="cardx">
        <h3>Ringkasan & Item</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:8px 0 14px">
          <div><b>Tanggal</b><br><?= htmlspecialchars($ord['created_at'] ?? '-') ?></div>
          <div><b>Catatan Pembeli</b><br><?= $ord['catatan']? nl2br(htmlspecialchars($ord['catatan'])):'-' ?></div>
          <div><b>Total</b><br>Rp <?= number_format((float)$ord['total_harga'],0,',','.') ?></div>
        </div>

        <table class="table">
          <thead><tr>
            <th>Produk</th><th>Qty</th><th>Harga</th><th>Subtotal</th>
          </tr></thead>
          <tbody>
            <?php if ($hasDetail && $items && mysqli_num_rows($items)>0): ?>
              <?php while($it=mysqli_fetch_assoc($items)): ?>
                <tr>
                  <td><?= htmlspecialchars($it['nama_produk'] ?? '(produk dihapus)') ?></td>
                  <td><?= (int)$it['jumlah'] ?></td>
                  <td>Rp <?= number_format((float)$it['harga'],0,',','.') ?></td>
                  <td>Rp <?= number_format((float)$it['subtotal'],0,',','.') ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="4" style="text-align:center;padding:14px">Item tidak tersedia.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="cardx">
        <h3>Info Pembeli & Reseller</h3>
        <div style="margin:8px 0 14px">
          <b>Pembeli</b><br>
          <?= htmlspecialchars($ord['pembeli'] ?? '-') ?><br>
          <?php if(!empty($ord['pembeli_email'])): ?>
            <a href="mailto:<?= htmlspecialchars($ord['pembeli_email']) ?>"><?= htmlspecialchars($ord['pembeli_email']) ?></a>
          <?php else: ?><span class="muted">-</span><?php endif; ?>
        </div>
        <div style="margin:8px 0 14px">
          <b>Reseller</b><br>
          <?= $hasResellerCol ? htmlspecialchars($ord['reseller_nama'] ?? '-') : '-' ?><br>
          <?php if($hasResellerCol && !empty($ord['reseller_email'])): ?>
            <a href="mailto:<?= htmlspecialchars($ord['reseller_email']) ?>"><?= htmlspecialchars($ord['reseller_email']) ?></a>
          <?php endif; ?>
        </div>

        <h3>Timeline Status</h3>
        <div>
          <?php if ($hasTimeline && $timeline && mysqli_num_rows($timeline)>0): ?>
            <ul style="padding-left:18px">
              <?php while($t=mysqli_fetch_assoc($timeline)): ?>
                <li><b><?= htmlspecialchars(ucfirst($t['status_from'] ?: '—')) ?> ➜ <?= htmlspecialchars(ucfirst($t['status_to'])) ?></b> <span class="muted">(
                  <?= htmlspecialchars($t['changed_at']) ?>)</span></li>
              <?php endwhile; ?>
            </ul>
          <?php else: ?>
            <div class="muted">Belum ada riwayat.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
