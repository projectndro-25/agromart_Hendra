<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
/* DEBUG SEMENTARA – boleh dihapus jika sudah stabil */
ini_set('display_errors', 1); error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Reseller Diblokir - AgroMart</title>
  <style>
    body{font-family:Arial,sans-serif;background:#f1f1f1;display:flex;justify-content:center;align-items:center;height:100vh;margin:0}
    .box{background:#fff;padding:30px;border-radius:10px;width:380px;box-shadow:0 4px 10px rgba(0,0,0,.1);text-align:center}
    h2{margin:0 0 14px 0}
    p{margin:6px 0;color:#333}
    .btn{display:inline-block;padding:10px 14px;border-radius:6px;border:none;background:#e11d48;color:#fff;text-decoration:none;margin-top:12px}
    .muted{color:#6b7280;font-size:14px}
  </style>
</head>
<body>
  <div class="box">
    <h2>🚫 Toko Diblokir</h2>
    <p class="muted">Halo, <?= htmlspecialchars($_SESSION['username'] ?? 'Reseller'); ?>.</p>
    <p>Status toko kamu saat ini <b>Diblokir</b>. Jika ini kesalahan, silakan hubungi admin.</p>
    <a class="btn" href="../logout.php">Logout</a>
  </div>

  <script>
    const check = async () => {
      try {
        const r = await fetch('./api/check_reseller_status.php', {cache:'no-store'});
        const j = await r.json();
        console.log('status reseller:', j);
        if (j && j.ok) {
          if (j.status === 'aktif') {
            window.location.href = '../reseller_khusus_penjual/index.php';
          } else if (j.status === 'pending') {
            window.location.href = './reseller_pending.php';
          }
        }
      } catch(e) { console.log('cek status error', e); }
    };
    check(); setInterval(check, 3000);
  </script>
</body>
</html>
