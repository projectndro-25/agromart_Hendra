<?php
// agromart/pages/profile.php
session_start();

// Wajib login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Keamanan ekstra
if (!isset($_SESSION['__regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['__regenerated'] = true;
}

// Ambil data dasar dari session (pakai default aman)
$user_id  = (int)($_SESSION['user_id'] ?? 0);
$username = $_SESSION['username'] ?? 'User';
$role     = strtolower(trim($_SESSION['role'] ?? 'user'));

// ===== Router: arahkan sesuai role =====
// - admin    -> /admin/index.php
// - reseller -> /user/index.php (reseller juga boleh belanja di portal user)
// - user     -> /user/index.php
//
// NOTE: kalau kamu mau reseller diarahkan ke panel khusus reseller,
// ganti target jadi "../reseller_khusus_penjual/index.php".
switch ($role) {
    case 'admin':
        header("Location: ../admin/index.php");
        exit();
    case 'reseller':
    case 'user':
    default:
        header("Location: ../user/index.php");
        exit();
}

// ----------
// Fallback tampilan (hanya muncul jika redirect gagal karena alasan tertentu)
// ----------
$roleEmoji = ($role === 'admin') ? "🛠️" : "🙋";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profile - AgroMart</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f6f9; margin:0; }
        .container { width:420px; margin:80px auto; background:#fff; padding:22px;
            border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.08); text-align:center; }
        h2 { color:#111827; margin:0 0 8px; }
        .info { margin:18px 0; text-align:left; color:#374151; }
        .info p { margin:8px 0; font-size:16px; }
        .logout { display:inline-block; background:#e74c3c; color:#fff; padding:10px 18px;
            border-radius:8px; text-decoration:none; font-weight:600; }
        .logout:hover { background:#c0392b; }
    </style>
</head>
<body>
<div class="container">
    <h2>Welcome, <?= htmlspecialchars($username) ?> 👋</h2>
    <div class="info">
        <p><strong>User ID:</strong> <?= $user_id ?></p>
        <p><strong>Username:</strong> <?= htmlspecialchars($username) ?></p>
        <p><strong>Role:</strong> <?= $roleEmoji . " " . htmlspecialchars(ucfirst($role)) ?></p>
    </div>
    <a class="logout" href="../logout.php">Logout</a>
</div>
</body>
</html>
