<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
    header('Location: index.php?msg=Token%20tidak%20valid'); exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { header('Location: index.php?msg=User%20tidak%20valid'); exit; }

/* dilarang hapus diri sendiri */
if ($id === (int)($_SESSION['user_id'] ?? 0)) {
    header('Location: index.php?msg=Tidak%20bisa%20hapus%20akun%20sendiri'); exit;
}

/* cek role target */
$st = mysqli_prepare($conn, "SELECT role FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
if (!$res || !mysqli_num_rows($res)) { header('Location: index.php?msg=User%20tidak%20ditemukan'); exit; }
$u = mysqli_fetch_assoc($res);

/* superadmin tidak boleh dihapus */
if (strtolower($u['role']) === 'superadmin') {
    header('Location: index.php?msg=Superadmin%20tidak%20boleh%20dihapus'); exit;
}

/* eksekusi hapus (hard) */
$d = mysqli_prepare($conn, "DELETE FROM users WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($d, 'i', $id);
if (mysqli_stmt_execute($d)) {
    header('Location: index.php?msg=User%20dihapus');
} else {
    header('Location: index.php?msg=Gagal%20menghapus%20user');
}
exit;
