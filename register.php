<?php
session_start();
require_once "config/db.php"; // koneksi database

$message = "";

// Kalau form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ambil input
    $nama     = mysqli_real_escape_string($conn, $_POST['name']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; // tetap plain sesuai DB sekarang
    $isReseller = isset($_POST['is_reseller']) ? 1 : 0;

    // Cek apakah email sudah terdaftar
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        $message = "❌ Email sudah digunakan, silakan login.";
    } else {
        // Tentukan role: reseller jika dicentang, else user
        $role = $isReseller ? 'reseller' : 'user';

        // Simpan user baru (pakai plain password, mengikuti struktur DB sekarang)
        $sqlUser = "INSERT INTO users (nama, email, password, role, status, created_at) 
                    VALUES ('$nama', '$email', '$password', '$role', 'aktif', NOW())";

        if (mysqli_query($conn, $sqlUser)) {
            $newUserId = mysqli_insert_id($conn);

            // Jika daftar sebagai reseller, buat baris di tabel reseller (status pending)
            if ($isReseller) {
                // nama_toko ambil dari nama, fallback ke email
                $namaToko = $nama !== '' ? mysqli_real_escape_string($conn, $nama) : $email;
                $sqlRes = "INSERT IGNORE INTO reseller (user_id, nama_toko, status, created_at) 
                           VALUES ($newUserId, '$namaToko', 'pending', NOW())";
                mysqli_query($conn, $sqlRes);

                $message = "✅ Pendaftaran reseller berhasil! Toko kamu <b>menunggu approval admin</b> sebelum aktif.";
            } else {
                $message = "✅ Pendaftaran berhasil! Silakan login.";
            }
        } else {
            $message = "❌ Gagal mendaftar: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register - AgroMart</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f1f1;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 350px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            width: 100%;
            padding: 10px;
            background: green;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: darkgreen;
        }
        .message {
            text-align: center;
            margin-top: 10px;
            color: red;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
        }
        /* password field wrapper */
        .password-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #666;
        }
        .toggle-password:hover {
            color: black;
        }
        /* checkbox tampil default, tidak ubah style global */
        .cb-row { margin: 6px 0 10px; font-size: 14px; color: #333; }
        .cb-row label { cursor: pointer; }
    </style>
</head>
<body>
<div class="container">
    <h2>Daftar Akun</h2>

    <?php if ($message != "") echo "<p class='message'>$message</p>"; ?>

    <form method="POST">
        <input type="text" name="name" placeholder="Nama Lengkap" required>
        <input type="email" name="email" placeholder="Email" required>

        <div class="password-wrapper">
            <input type="password" name="password" id="password" placeholder="Password" required>
            <span class="toggle-password" onclick="togglePassword()">👁️</span>
        </div>

        <!-- Tambahan: daftar sebagai reseller -->
        <div class="cb-row">
            <label>
                <input type="checkbox" name="is_reseller" value="1">
                Daftar sebagai <strong>Reseller</strong> (toko akan <em>pending</em> sampai admin approve)
            </label>
        </div>

        <button type="submit">Daftar</button>
    </form>

    <div class="login-link">
        <p>Sudah punya akun? <a href="login.php">Login</a></p>
    </div>
</div>

<script>
function togglePassword() {
    const passwordField = document.getElementById("password");
    const toggle = document.querySelector(".toggle-password");
    if (passwordField.type === "password") {
        passwordField.type = "text";
        toggle.textContent = "🙈"; // berubah jadi monyet tutup mata
    } else {
        passwordField.type = "password";
        toggle.textContent = "👁️"; // kembali ke ikon mata
    }
}
</script>
</body>
</html>
