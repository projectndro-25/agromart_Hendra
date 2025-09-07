<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_reseller.php';
require_once __DIR__ . '/../../config/db.php';

$uid        = (int)$_SESSION['user_id'];
$resellerId = 0;
$qRes = mysqli_query($conn, "SELECT id FROM reseller WHERE user_id=$uid LIMIT 1");
if ($qRes && mysqli_num_rows($qRes)) { $resellerId = (int)mysqli_fetch_assoc($qRes)['id']; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0 || $resellerId <= 0) { header('Location: index.php'); exit; }

/* deteksi kolom foto */
$photoCol = null;
foreach (['foto','gambar','image','thumbnail'] as $cand) {
  $c = mysqli_query($conn, "SHOW COLUMNS FROM produk LIKE '$cand'");
  if ($c && mysqli_num_rows($c)) { $photoCol = $cand; break; }
}

/* ambil record milik sendiri */
$cols = "id";
if ($photoCol) $cols .= ", `$photoCol` AS foto_db";
$st = mysqli_prepare($conn, "SELECT $cols FROM produk WHERE id=? AND reseller_id=? LIMIT 1");
mysqli_stmt_bind_param($st, 'ii', $id, $resellerId);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
$row = $r ? mysqli_fetch_assoc($r) : null;
mysqli_stmt_close($st);

if (!$row) { header('Location: index.php'); exit; }

/* hapus file foto kalau ada */
if ($photoCol && !empty($row['foto_db'])) {
  $old = $row['foto_db'];
  $p1 = __DIR__ . '/../../' . ltrim($old,'/');
  $p2 = __DIR__ . '/../../uploads/products/' . basename($old);
  if (is_file($p1)) @unlink($p1); elseif (is_file($p2)) @unlink($p2);
}

/* delete row */
$del = mysqli_prepare($conn, "DELETE FROM produk WHERE id=? AND reseller_id=? LIMIT 1");
mysqli_stmt_bind_param($del, 'ii', $id, $resellerId);
if ($del && mysqli_stmt_execute($del)) {
  $_SESSION['success'] = 'Produk berhasil dihapus.';
} else {
  $_SESSION['error'] = 'Gagal menghapus produk.';
}
if ($del) mysqli_stmt_close($del);

header('Location: index.php'); exit;
