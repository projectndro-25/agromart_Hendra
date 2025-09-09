<?php
// db.php → file koneksi database ke MySQL

$host = "h2avn9.h.filess.io";   // server (misalnya: localhost / 127.0.0.1)
$user = "agromart_stairswar";        // default user XAMPP
$pass = "699db939ac16d87d681ba90136782353e6a6a0ff";            // default password XAMPP biasanya kosong
$db   = "agromart_stairswar";    // nama database

// Buat koneksi
$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    // ❌ Jangan tampilkan detail error di production
    error_log("Database connection failed: " . mysqli_connect_error());
    exit("⚠️ Koneksi ke database gagal. Silakan coba beberapa saat lagi.");
}

// ✅ Kalau berhasil, jangan tampilkan pesan apapun
