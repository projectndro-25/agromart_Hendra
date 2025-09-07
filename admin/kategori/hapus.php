<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$ck = mysqli_prepare($conn, "SELECT id FROM kategori WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($ck, "i", $id);
mysqli_stmt_execute($ck);
$rck = mysqli_stmt_get_result($ck);
if (!$rck || mysqli_num_rows($rck) === 0) {
  header("Location: index.php?msg=" . urlencode("Kategori tidak ditemukan"));
  exit;
}

$cp = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM produk WHERE kategori_id = ?");
mysqli_stmt_bind_param($cp, "i", $id);
mysqli_stmt_execute($cp);
$rp = mysqli_stmt_get_result($cp);
$row = mysqli_fetch_assoc($rp);
if ((int)$row['c'] > 0) {
  header("Location: index.php?msg=" . urlencode("Tidak bisa dihapus: masih ada produk pada kategori ini"));
  exit;
}

$del = mysqli_prepare($conn, "DELETE FROM kategori WHERE id = ?");
mysqli_stmt_bind_param($del, "i", $id);
if (mysqli_stmt_execute($del)) {
  header("Location: index.php?msg=" . urlencode("Kategori berhasil dihapus!"));
} else {
  header("Location: index.php?msg=" . urlencode("Gagal menghapus kategori!"));
}
exit;
