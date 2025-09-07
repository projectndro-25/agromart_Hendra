<?php
// /user/includes/header.php
// Syarat: $conn & $user_id sudah tersedia dari file yang include ini (mis. /user/index.php)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Ambil ringkas jumlah item cart dari VIEW (kalau belum ada, badge 0)
$cartQty = 0;
if (isset($conn, $user_id)) {
  if ($st = mysqli_prepare($conn, "SELECT total_qty FROM v_user_cart_totals WHERE user_id=?")) {
    mysqli_stmt_bind_param($st, "i", $user_id);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    if ($row = mysqli_fetch_assoc($res)) $cartQty = (int)$row['total_qty'];
    mysqli_stmt_close($st);
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AgroMart</title>
<style>
  :root{
    --bg:#f7f9fb; --card:#fff; --border:#e5e7eb; --text:#111827; --muted:#6b7280;
    --brand:#2563eb; --brand-200:#dbeafe; --brand-600:#1d4ed8; --ok:#16a34a; --danger:#e11d48;
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--text);font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif}
  a{color:inherit;text-decoration:none}
  .container{max-width:1100px;margin:0 auto;padding:18px}
  /* Navbar */
  .nav{background:#fff;border-bottom:1px solid var(--border);position:sticky;top:0;z-index:20}
  .nav-inner{display:flex;align-items:center;justify-content:space-between;gap:12px}
  .brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:20px}
  .pill{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;background:#fff;border:1px solid var(--border);border-radius:10px}
  .pill:hover{box-shadow:0 8px 18px rgba(0,0,0,.06);transform:translateY(-1px)}
  .badge{display:inline-block;min-width:20px;padding:2px 6px;border-radius:999px;background:var(--brand);color:#fff;font-size:12px;text-align:center}
  /* Headings & sections */
  h1{font-size:26px;margin:18px 0 8px}
  .muted{color:var(--muted);font-size:14px}
  /* Filters */
  .filters{display:flex;gap:10px;flex-wrap:wrap;margin:14px 0}
  select,input[type="text"]{padding:10px 12px;border:1px solid var(--border);border-radius:10px;background:#fff}
  button,.btn{padding:10px 12px;border:0;border-radius:10px;font-weight:600;cursor:pointer}
  .btn-primary{background:var(--brand);color:#fff}
  .btn-primary:hover{background:var(--brand-600)}
  /* Cards grid */
  .grid{display:grid;grid-template-columns:repeat(1,minmax(0,1fr));gap:14px}
  @media(min-width:640px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
  @media(min-width:900px){.grid{grid-template-columns:repeat(3,minmax(0,1fr));}}
  .card{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden}
  .p16{padding:16px}
  .product-img{width:100%;height:180px;object-fit:cover;background:#f3f4f6}
  .title{font-weight:700;margin:8px 0 2px;font-size:16px;line-height:1.3}
  .sub{color:var(--muted);font-size:13px;margin-bottom:8px}
  .price{font-weight:800;font-size:18px;margin:6px 0}
  .row{display:flex;gap:8px;align-items:center}
  .qty{width:70px}
  /* Pagination */
  .pagination{display:flex;gap:8px;justify-content:center;margin:18px 0}
  .page{padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:#fff}
  .page.active{background:var(--brand);color:#fff;border-color:var(--brand)}
  /* Footer */
  .footer{margin-top:30px;padding:18px 0;border-top:1px solid var(--border);color:var(--muted);font-size:14px}
</style>
</head>
<body>
  <div class="nav">
    <div class="container nav-inner">
      <a class="brand" href="/agromart/user/index.php">🌱 AgroMart</a>
      <div class="row" style="gap:12px">
        <a class="pill" href="/agromart/user/pesanan/index.php">🧾 Pesanan</a>
        <a class="pill" href="/agromart/user/wishlist/index.php">💚 Wishlist</a>
        <a class="pill" href="/agromart/user/keranjang/index.php">🛒 Keranjang <span class="badge"><?= (int)$cartQty ?></span></a>
        <a class="pill" href="/agromart/user/akun/index.php">👤 Akun</a>
      </div>
    </div>
  </div>
  <div class="container">
