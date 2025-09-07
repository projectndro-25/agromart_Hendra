<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

/* CSRF */
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
$csrf = $_SESSION['csrf'];

/* helpers */
function table_exists($c,$t){
  $q = mysqli_prepare($c,"SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
  mysqli_stmt_bind_param($q,"s",$t); mysqli_stmt_execute($q);
  $r = mysqli_stmt_get_result($q);
  return $r && (int)mysqli_fetch_assoc($r)['c']>0;
}
function get_columns($c,$t){
  $q = mysqli_prepare($c,"SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=?");
  mysqli_stmt_bind_param($q,"s",$t); mysqli_stmt_execute($q);
  $res = mysqli_stmt_get_result($q);
  $cols = [];
  while($res && ($row=mysqli_fetch_assoc($res))) $cols[] = strtolower($row['column_name']);
  return $cols;
}

/* input */
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id<=0){ echo "<script>alert('Pesanan tidak valid');location.href='index.php';</script>"; exit; }

/* fetch header */
$st = mysqli_prepare($conn,"SELECT id,is_flagged,flag_note FROM pesanan WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($st,"i",$id);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
if (!$r || !mysqli_num_rows($r)){ echo "<script>alert('Pesanan tidak ditemukan');location.href='index.php';</script>"; exit; }
$ord = mysqli_fetch_assoc($r);

/* POST: update + log adaptif */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!hash_equals($csrf, $_POST['csrf'] ?? '')) { die('CSRF mismatch'); }

  $flag = (int)($_POST['is_flagged'] ?? 0);
  $note = trim($_POST['flag_note'] ?? '');

  // update pesanan
  $up = mysqli_prepare($conn,"UPDATE pesanan SET is_flagged=?, flag_note=?, updated_at=NOW() WHERE id=?");
  mysqli_stmt_bind_param($up,"isi",$flag,$note,$id);
  mysqli_stmt_execute($up);

  // optional log
  if (table_exists($conn,'order_watch_log')) {
    $cols = get_columns($conn,'order_watch_log'); // lowercased
    // siapkan kolom dan value yang tersedia
    $insertCols = [];
    $valuesExpr = [];
    $bindTypes  = '';
    $bindVals   = [];

    // wajib: pesanan_id
    if (in_array('pesanan_id',$cols,true)) {
      $insertCols[] = 'pesanan_id'; $valuesExpr[]='?'; $bindTypes.='i'; $bindVals[]=$id;
    } else {
      // kalau tidak ada kolom pesanan_id, tidak usah insert log
      // (biar aman & tidak error)
      goto after_log;
    }

    // flag kolom yang mungkin tersedia
    if (in_array('flag_value',$cols,true)) { $insertCols[]='flag_value'; $valuesExpr[]='?'; $bindTypes.='i'; $bindVals[]=$flag; }
    elseif (in_array('flag',$cols,true))     { $insertCols[]='flag';       $valuesExpr[]='?'; $bindTypes.='i'; $bindVals[]=$flag; }
    elseif (in_array('is_flagged',$cols,true)){ $insertCols[]='is_flagged'; $valuesExpr[]='?'; $bindTypes.='i'; $bindVals[]=$flag; }

    // note/notes/catatan
    if (in_array('notes',$cols,true))       { $insertCols[]='notes';    $valuesExpr[]='?'; $bindTypes.='s'; $bindVals[]=$note; }
    elseif (in_array('note',$cols,true))    { $insertCols[]='note';     $valuesExpr[]='?'; $bindTypes.='s'; $bindVals[]=$note; }
    elseif (in_array('catatan',$cols,true)) { $insertCols[]='catatan';  $valuesExpr[]='?'; $bindTypes.='s'; $bindVals[]=$note; }

    // timestamp: pakai NOW() agar tidak perlu bind
    if (in_array('logged_at',$cols,true))   { $insertCols[]='logged_at';   $valuesExpr[]='NOW()'; }
    elseif (in_array('created_at',$cols,true)) { $insertCols[]='created_at'; $valuesExpr[]='NOW()'; }
    elseif (in_array('tanggal',$cols,true)) { $insertCols[]='tanggal';     $valuesExpr[]='NOW()'; }

    // who
    $adminId = (int)($_SESSION['user_id'] ?? 0);
    if (in_array('logged_by',$cols,true))   { $insertCols[]='logged_by';   $valuesExpr[]='?'; $bindTypes.='i'; $bindVals[]=$adminId; }
    elseif (in_array('admin_id',$cols,true)){ $insertCols[]='admin_id';    $valuesExpr[]='?'; $bindTypes.='i'; $bindVals[]=$adminId; }
    elseif (in_array('user_id',$cols,true)) { $insertCols[]='user_id';     $valuesExpr[]='?'; $bindTypes.='i'; $bindVals[]=$adminId; }

    // eksekusi jika ada minimal 2 kolom (pesanan_id + sesuatu)
    if (count($insertCols) >= 2) {
      $sql = "INSERT INTO order_watch_log (".implode(',',$insertCols).") VALUES (".implode(',',$valuesExpr).")";
      $ins = mysqli_prepare($conn,$sql);
      if ($bindTypes !== '') {
        mysqli_stmt_bind_param($ins,$bindTypes,...$bindVals);
      }
      // swallow error agar tidak memblok UI walau struktur log beda-beda
      @mysqli_stmt_execute($ins);
    }
  }
  after_log:

  header("Location: pantau.php?id=".$id."&msg=".urlencode("Catatan tersimpan"));
  exit;
}

$msg = trim($_GET['msg'] ?? '');
?>
<style>
  .cardx{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(0,0,0,.08);padding:18px;max-width:720px}
  .input,.select,textarea{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px}
  .muted{opacity:.75}
</style>

<div class="content">
  <div class="page">
    <h1>🔎 Pantau Pesanan <?= (int)$ord['id'] ?></h1>
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <div class="cardx">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="id" value="<?= (int)$ord['id'] ?>">

        <label><b>Status Pantau</b></label>
        <select name="is_flagged" class="select" style="margin:6px 0 12px">
          <option value="0" <?= (int)$ord['is_flagged']===0?'selected':'' ?>>OFF (normal)</option>
          <option value="1" <?= (int)$ord['is_flagged']===1?'selected':'' ?>>ON (bermasalah)</option>
        </select>

        <label><b>Catatan Internal</b></label>
        <textarea name="flag_note" rows="5" placeholder="Tulis detail masalah, langkah, dsb"><?= htmlspecialchars($ord['flag_note'] ?? '') ?></textarea>

        <div style="margin-top:12px;display:flex;gap:10px;align-items:center">
          <button class="btn btn-warning" type="submit">Simpan</button>
          <a class="btn btn-secondary" href="detail.php?id=<?= (int)$ord['id'] ?>">Kembali</a>
        </div>
        <div class="muted" style="margin-top:8px">Log pantau akan dicatat di <code>order_watch_log</code> bila tabelnya ada (kolomnya otomatis disesuaikan).</div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
