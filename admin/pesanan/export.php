<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: text/csv; charset=utf-8');
$fname = 'pesanan_'.date('Ymd_His').'.csv';
header('Content-Disposition: attachment; filename="'.$fname.'"');

$out = fopen('php://output','w');

/* helpers */
function col_exists($c,$t,$col){
  $q = mysqli_prepare($c,"SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?");
  mysqli_stmt_bind_param($q,"ss",$t,$col); mysqli_stmt_execute($q);
  $r=mysqli_stmt_get_result($q); return $r && (int)mysqli_fetch_assoc($r)['c']>0;
}
function table_exists($c,$t){
  $q = mysqli_prepare($c,"SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
  mysqli_stmt_bind_param($q,"s",$t); mysqli_stmt_execute($q);
  $r=mysqli_stmt_get_result($q); return $r && (int)mysqli_fetch_assoc($r)['c']>0;
}
$hasResellerCol = col_exists($conn,'pesanan','reseller_id');
$hasDetail      = table_exists($conn,'detail_pesanan');

$id = (int)($_GET['id'] ?? 0);

/* header row */
fputcsv($out, ['No Pesanan','Tanggal','Pembeli','Email Pembeli','Reseller','Status','Total','Item Count']);

/* single */
if ($id>0) {
  $selectRes = $hasResellerCol ? ", r.nama AS reseller_nama" : ", NULL AS reseller_nama";
  $sql = "
    SELECT p.id, p.created_at, u.nama AS pembeli, u.email AS pembeli_email,
           IFNULL(p.total_harga,0) AS total_harga, p.status $selectRes
    FROM pesanan p
    LEFT JOIN users u ON u.id=p.user_id
    ".($hasResellerCol? "LEFT JOIN users r ON r.id=p.reseller_id" : "")."
    WHERE p.id=? LIMIT 1
  ";
  $st = mysqli_prepare($conn,$sql);
  mysqli_stmt_bind_param($st,"i",$id);
  mysqli_stmt_execute($st);
  $h = mysqli_stmt_get_result($st);
  if ($h && ($row=mysqli_fetch_assoc($h))) {
    $itemCount = null;
    if ($hasDetail) {
      $q = mysqli_prepare($conn,"SELECT COUNT(*) c FROM detail_pesanan WHERE pesanan_id=?");
      mysqli_stmt_bind_param($q,"i",$id); mysqli_stmt_execute($q);
      $itemCount = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($q))['c'];
    }
    fputcsv($out, [
      $row['id'], $row['created_at'], $row['pembeli'], $row['pembeli_email'],
      $row['reseller_nama'], $row['status'], $row['total_harga'], $itemCount
    ]);
  }
  exit;
}

/* list by filters (same as index) */
$STATUSES = ['pending','dibayar','dikirim','selesai','batal'];
$q        = trim($_GET['q'] ?? '');
$status   = trim($_GET['status'] ?? '');
$from     = trim($_GET['from'] ?? '');
$to       = trim($_GET['to'] ?? '');
$reseller = (int)($_GET['reseller'] ?? 0);

$where=[]; $params=[]; $types='';
if ($q!==''){
  $like='%'.strtolower($q).'%';
  if ($hasResellerCol){ $where[]="(CAST(p.id AS CHAR) LIKE ? OR LOWER(u.nama) LIKE ? OR LOWER(r.nama) LIKE ?)"; $params[]=$like;$types.='s'; $params[]=$like;$types.='s'; $params[]=$like;$types.='s'; }
  else { $where[]="(CAST(p.id AS CHAR) LIKE ? OR LOWER(u.nama) LIKE ?)"; $params[]=$like;$types.='s'; $params[]=$like;$types.='s'; }
}
if ($status!=='' && in_array($status,$STATUSES,true)){ $where[]="p.status=?"; $params[]=$status; $types.='s'; }
if ($from!==''){ $where[]="DATE(p.created_at)>=?"; $params[]=$from; $types.='s'; }
if ($to!==''){   $where[]="DATE(p.created_at)<=?"; $params[]=$to;   $types.='s'; }
if ($hasResellerCol && $reseller>0){ $where[]="p.reseller_id=?"; $params[]=$reseller; $types.='i'; }
$whereSql = $where?('WHERE '.implode(' AND ',$where)):'';

$joinUser="LEFT JOIN users u ON u.id=p.user_id";
$joinRes =$hasResellerCol? "LEFT JOIN users r ON r.id=p.reseller_id":"";

$st = mysqli_prepare($conn, "
  SELECT p.id, p.created_at, u.nama AS pembeli, u.email AS pembeli_email,
         IFNULL(p.total_harga,0) AS total_harga, p.status
         ".($hasResellerCol?", r.nama AS reseller_nama":", NULL AS reseller_nama")."
  FROM pesanan p $joinUser $joinRes $whereSql
  ORDER BY p.id DESC
  LIMIT 10000
");
if ($types) mysqli_stmt_bind_param($st,$types,...$params);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
while($row = $res && mysqli_fetch_assoc($res)){
  $itemCount=null;
  if ($hasDetail){
    $q = mysqli_prepare($conn,"SELECT COUNT(*) c FROM detail_pesanan WHERE pesanan_id=?");
    mysqli_stmt_bind_param($q,"i",$row['id']);
    mysqli_stmt_execute($q);
    $itemCount = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($q))['c'];
  }
  fputcsv($out, [
    $row['id'],$row['created_at'],$row['pembeli'],$row['pembeli_email'],
    $row['reseller_nama'],$row['status'],$row['total_harga'],$itemCount
  ]);
}
exit;
