<?php
session_start();
require_once "config/db.php"; // koneksi database

$message = "";

// Kalau form disubmit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $pass  = (string)($_POST['password'] ?? '');

    if ($email === '' || $pass === '') {
        $message = "❌ Email / password wajib diisi!";
    } else {
        // Ambil user by email (prepared)
        $st = mysqli_prepare($conn, "SELECT id, nama, email, password, role, status, IFNULL(is_blocked,0) AS is_blocked FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($st, 's', $email);
        mysqli_stmt_execute($st);
        $res  = mysqli_stmt_get_result($st);

        if ($res && mysqli_num_rows($res) === 1) {
            $user = mysqli_fetch_assoc($res);

            // --- Cek blokir/status (longgarkan untuk admin/superadmin)
            $role      = strtolower((string)$user['role']);
            $isBlocked = (int)($user['is_blocked'] ?? 0);
            $statusOk  = strtolower((string)$user['status']) === 'aktif';

            if ($isBlocked === 1 && !in_array($role, ['admin','superadmin'], true)) {
                $message = "❌ Akun diblokir. Hubungi admin.";
            } elseif (!$statusOk) {
                $message = "❌ Akun nonaktif.";
            } else {
                // ===== Verifikasi password: bcrypt -> md5 -> plaintext =====
                $stored = (string)$user['password'];
                $ok = false;
                $migrate = false;

                // 1) bcrypt (baru)
                if (strlen($stored) >= 50 && password_get_info($stored)['algo']) {
                    $ok = password_verify($pass, $stored);
                    if ($ok && password_needs_rehash($stored, PASSWORD_BCRYPT)) {
                        $migrate = true;
                    }
                } else {
                    // 2) fallback md5 lama
                    if (!$ok && hash_equals($stored, md5($pass))) {
                        $ok = true; $migrate = true;
                    }
                    // 3) fallback plaintext lama
                    if (!$ok && hash_equals($stored, $pass)) {
                        $ok = true; $migrate = true;
                    }
                }

                if ($ok) {
                    // Migrasi ke bcrypt bila perlu
                    if ($migrate) {
                        $newHash = password_hash($pass, PASSWORD_BCRYPT);
                        $u = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
                        mysqli_stmt_bind_param($u, 'si', $newHash, $user['id']);
                        mysqli_stmt_execute($u);
                    }

                    // Set session dasar
                    $_SESSION['user_id']  = (int)$user['id'];
                    $_SESSION['username'] = $user['nama'];
                    $_SESSION['role']     = $role;

                    // ======== Tambahan: handle reseller ========
                    if ($role === 'reseller') {
                        // Ambil status toko dari tabel reseller
                        $_SESSION['reseller_status'] = 'pending'; // default
                        $uid = (int)$user['id'];

                        $st2 = mysqli_prepare($conn, "SELECT status FROM reseller WHERE user_id = ? LIMIT 1");
                        mysqli_stmt_bind_param($st2, 'i', $uid);
                        mysqli_stmt_execute($st2);
                        $res2 = mysqli_stmt_get_result($st2);
                        if ($res2 && mysqli_num_rows($res2) === 1) {
                            $row = mysqli_fetch_assoc($res2);
                            $_SESSION['reseller_status'] = strtolower($row['status']); // pending | aktif | blokir
                        }

                        // Redirect sesuai status toko
                        if ($_SESSION['reseller_status'] === 'pending') {
                            header("Location: pages/reseller_pending.php"); exit;
                        }
                        if ($_SESSION['reseller_status'] === 'blokir') {
                            header("Location: pages/reseller_blocked.php"); exit;
                        }
                        // aktif
                        header("Location: reseller_khusus_penjual/index.php"); exit;
                    }
                    // ======== End handle reseller ========

                    // Redirect sesuai role non-reseller
                    if (in_array($role, ['superadmin','admin'])) {
                        header("Location: admin/index.php");
                    } else {
                        header("Location: pages/profile.php");
                    }
                    exit;
                } else {
                    $message = "❌ Password salah!";
                }
            }
        } else {
            $message = "❌ Email tidak ditemukan!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - AgroMart</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f1f1;
            display: flex; justify-content: center; align-items: center;
            height: 100vh;
        }
        .container {
            background: white; padding: 30px; border-radius: 10px; width: 350px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; margin-bottom: 20px; }
        input { width: 100%; padding: 10px; margin: 8px 0; border-radius: 5px; border: 1px solid #ccc; }
        button { width: 100%; padding: 10px; background: green; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: darkgreen; }
        .message { text-align: center; margin-top: 10px; color: red; }
        .extra-links { text-align: center; margin-top: 15px; }
        .extra-links a { color: #0066cc; text-decoration: none; }
        .extra-links a:hover { text-decoration: underline; }
        .password-wrapper { position: relative; }
        .toggle-password {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            cursor: pointer; font-size: 18px; color: #666;
        }
        .toggle-password:hover { color: black; }
    </style>
</head>
<body>
<div class="container">
    <h2>Login</h2>

    <?php if ($message !== "") echo "<p class='message'>$message</p>"; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <div class="password-wrapper">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <span class="toggle-password" onclick="togglePassword()">👁️</span>
        </div>
        <button type="submit">Login</button>
    </form>

    <div class="extra-links">
        <p><a href="forgot_password.php">Lupa Password?</a></p>
        <p>Belum punya akun? <a href="register.php">Daftar</a></p>
    </div>
</div>

<script>
function togglePassword() {
    const f = document.getElementById("password");
    const t = document.querySelector(".toggle-password");
    if (f.type === "password") { f.type = "text"; t.textContent = "🙈"; }
    else { f.type = "password"; t.textContent = "👁️"; }
}
</script>
</body>
</html>
