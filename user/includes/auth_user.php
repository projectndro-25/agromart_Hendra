<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user_id'])) {
  $_SESSION['flash'] = ['type'=>'warn', 'msg'=>'Silakan login dulu.'];
  header('Location: /agromart/login.php');
  exit;
}
$user_id = (int)$_SESSION['user_id'];

// Opsional: kalau admin diarahkan ke panel admin
if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
  header('Location: /agromart/admin/index.php'); 
  exit;
}

/* ==== Helpers (dipusatkan di sini) ==== */
if (!function_exists('flash_show')) {
  function flash_show() {
    if (!empty($_SESSION['flash'])) {
      $f = $_SESSION['flash']; unset($_SESSION['flash']);
      $bg = $f['type']==='ok' ? '#e8fff1' : ($f['type']==='err' ? '#ffeaea' : '#fff7e6');
      $col= $f['type']==='ok' ? '#0a7f3f' : ($f['type']==='err' ? '#c0392b' : '#8a6d3b');
      echo '<div style="margin:14px 0;padding:12px 14px;border-radius:10px;background:'.$bg.';color:'.$col.'">'.$f['msg'].'</div>';
    }
  }
}

if (!function_exists('rupiah')) {
  function rupiah($n){ return 'Rp ' . number_format((float)$n,0,',','.'); }
}
