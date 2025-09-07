<?php
// reseller_khusus_penjual/includes/header.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Reseller Panel - AgroMart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+..." crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    :root{ --bg:#f5f7fb; --card:#fff; --txt:#0b1220; --muted:#6b7280; --pri:#2563eb; }
    *{ box-sizing:border-box }
    body{ margin:0; font-family:Arial, sans-serif; background:var(--bg); color:var(--txt); }
    .layout{ display:grid; grid-template-columns:240px 1fr; min-height:100vh; }
    .side{ background:#fff; border-right:1px solid #eef0f4; padding:18px; }
    .brand{ font-weight:800; font-size:18px; margin-bottom:14px }
    .nav a{ display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; text-decoration:none; color:#111; }
    .nav a:hover{ background:#f2f5ff }
    .content{ padding:22px; }
    .card{ background:var(--card); border:1px solid #eef0f4; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,.05) }
    .card-body{ padding:16px 18px }
    .grid{ display:grid; gap:14px }
    .grid-2{ grid-template-columns:repeat(2,1fr) }
    .btn{ display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:10px; border:1px solid #e6e8ef; background:#fff; color:#111; text-decoration:none; }
    .btn:hover{ transform:translateY(-2px); box-shadow:0 10px 24px rgba(0,0,0,.08) }
    .btn-primary{ background:var(--pri); color:#fff; border-color:var(--pri) }
    .table{ width:100%; border-collapse:collapse }
    .table th,.table td{ padding:10px 12px; border-bottom:1px solid #f1f5f9; text-align:left; vertical-align:middle }
    .muted{ color:var(--muted) }
    .badge{ display:inline-block; padding:6px 10px; font-size:12px; border-radius:999px; border:1px solid #e6e8ef; }
    .badge-ok{ background:#e9f9ee; color:#1e7d3b; border-color:#bdeec9 }
    .badge-warn{ background:#fff7ed; color:#b45309; border-color:#fed7aa }
  </style>
</head>
<body>
<div class="layout">
