<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/db.php';

if (empty($_SESSION['user_id'])) {
  header('Location: ../../login.php'); exit;
}
if (($_SESSION['role'] ?? '') !== 'reseller') {
  header('Location: ../../admin/index.php'); exit;
}

/* Pastikan selalu punya reseller_id & reseller_status di session */
if (empty($_SESSION['reseller_id']) || !isset($_SESSION['reseller_status'])) {
  $uid = (int)$_SESSION['user_id'];
  $qr  = mysqli_query($conn, "SELECT id, status FROM reseller WHERE user_id=$uid LIMIT 1");
  if ($qr && mysqli_num_rows($qr)) {
    $r = mysqli_fetch_assoc($qr);
    $_SESSION['reseller_id']     = (int)$r['id'];
    $_SESSION['reseller_status'] = $r['status'];
  }
}

/* Gate by status */
$st = $_SESSION['reseller_status'] ?? null;
if ($st !== 'aktif') {
  if ($st === 'pending') { header('Location: ../../pages/reseller_pending.php'); exit; }
  if ($st === 'blokir')  { header('Location: ../../pages/reseller_blocked.php'); exit; }
  header('Location: ../../pages/reseller_pending.php'); exit;
}

/* helper */
function rupiah($n){ return 'Rp ' . number_format((float)$n,0,',','.'); }
