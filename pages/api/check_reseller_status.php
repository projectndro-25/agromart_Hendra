<?php
// pages/api/check_reseller_status.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/db.php';

$out = ['ok' => false, 'status' => null];

if (empty($_SESSION['user_id'])) { echo json_encode($out); exit; }

$uid = (int)$_SESSION['user_id'];
$st  = mysqli_prepare($conn, "SELECT status FROM reseller WHERE user_id = ? LIMIT 1");
mysqli_stmt_bind_param($st, 'i', $uid);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);

if ($res && mysqli_num_rows($res) === 1) {
  $row = mysqli_fetch_assoc($res);
  $status = strtolower($row['status']);       // pending | aktif | blokir
  $_SESSION['reseller_status'] = $status;     // segarkan session
  echo json_encode(['ok' => true, 'status' => $status]); 
  exit;
}

echo json_encode($out);
