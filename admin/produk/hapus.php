<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/auth_admin.php';

if (!isset($_GET['id'])) { 
    $_SESSION['error'] = "ID produk tidak ditemukan.";
    header('Location: index.php'); 
    exit; 
}

$id = (int)$_GET['id'];

// Cek produk dulu (prepared)
$st = mysqli_prepare($conn, "SELECT gambar FROM produk WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($st, "i", $id);
mysqli_stmt_execute($st);
$g = mysqli_stmt_get_result($st);
if (!$g || !mysqli_num_rows($g)) {
    $_SESSION['error'] = "Produk tidak ditemukan.";
    header('Location: index.php');
    exit;
}
$row = mysqli_fetch_assoc($g);

// Hapus file gambar jika ada
if (!empty($row['gambar'])) {
    $path = __DIR__ . "/../../uploads/" . $row['gambar'];
    if (is_file($path)) { @unlink($path); }
}

// Hapus stok history (jaga2 jika FK belum cascade)
$sh = mysqli_prepare($conn, "DELETE FROM stok_history WHERE produk_id = ?");
mysqli_stmt_bind_param($sh, "i", $id);
mysqli_stmt_execute($sh);

// Hapus produk (prepared)
$dp = mysqli_prepare($conn, "DELETE FROM produk WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($dp, "i", $id);
if (mysqli_stmt_execute($dp)) {
    $_SESSION['success'] = "✅ Produk berhasil dihapus.";
} else {
    $_SESSION['error'] = "❌ Gagal menghapus produk.";
}

header("Location: index.php");
exit;
