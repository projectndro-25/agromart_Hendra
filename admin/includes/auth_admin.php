<?php
// auth_admin.php di: /admin/includes/auth_admin.php

// Pastikan session aktif, tapi jangan double-start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek login
$userId = $_SESSION['user_id'] ?? null;
$role   = strtolower($_SESSION['role'] ?? '');

// Jika belum login → arahkan ke /admin/login.php (bukan root login.php)
if (!$userId) {
    header('Location: ' . dirname(__DIR__) . '/login.php');
    exit;
}

// Hanya izinkan admin & superadmin
if (!in_array($role, ['admin', 'superadmin'], true)) {
    header('Location: ' . dirname(__DIR__) . '/login.php');
    exit;
}

/**
 * (Opsional) Jika ingin cek status blokir user dari DB:
 * Pastikan $conn sudah dibuat setelah include db.php di file pemanggil.
 * 
 * if (isset($conn) && $conn instanceof mysqli) {
 *     $uid = (int)$userId;
 *     $q   = mysqli_query($conn, "SELECT is_blocked FROM users WHERE id={$uid} LIMIT 1");
 *     if ($q && ($r = mysqli_fetch_assoc($q)) && (int)$r['is_blocked'] === 1) {
 *         session_unset();
 *         session_destroy();
 *         header('Location: ' . dirname(__DIR__) . '/login.php');
 *         exit;
 *     }
 * }
 */
