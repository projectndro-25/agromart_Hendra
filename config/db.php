<?php
// db.php → file koneksi database ke MySQL

$host = "localhost";   // server (misalnya: localhost / 127.0.0.1)
$user = "root";        // default user XAMPP
$pass = "";            // default password XAMPP biasanya kosong
$db   = "agromart";    // nama database

// Buat koneksi
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    // ❌ Jangan tampilkan detail error di production
    error_log("Database connection failed: " . mysqli_connect_error());
    exit("⚠️ Koneksi ke database gagal. Silakan coba beberapa saat lagi.");
}

// ✅ Kalau berhasil, jangan tampilkan pesan apapun
