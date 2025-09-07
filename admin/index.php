<?php
// /admin/index.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
// ❌ JANGAN include widgets/pending-resellers.php di atas sini agar tidak dobel
?>

<?php if (!defined('AGW_ASSETS')): define('AGW_ASSETS', true); ?>
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+..." crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
  <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    .agw-panel { border: none; border-radius: 14px; box-shadow: 0 10px 28px rgba(0,0,0,.06); background:#fff; }
    .agw-card { border-radius: 14px; padding: 16px; background:#fff; box-shadow: 0 10px 28px rgba(0,0,0,.06); }
    .agw-card-ico { font-size: 20px; opacity:.85 }
    .agw-card-num { font-size: 26px; font-weight: 800; margin-top:6px }
    .agw-card-label { font-size: 12px; color:#667085 }
    .agw-grad-1{ background: linear-gradient(135deg,#e9f5ff,#ffffff); }
    .agw-grad-2{ background: linear-gradient(135deg,#f3ecff,#ffffff); }
    .agw-grad-3{ background: linear-gradient(135deg,#eafff3,#ffffff); }
    .agw-grad-4{ background: linear-gradient(135deg,#fff6eb,#ffffff); }
    .agw-grad-5{ background: linear-gradient(135deg,#ffeef5,#ffffff); }
    .agw-grad-6{ background: linear-gradient(135deg,#eefaff,#ffffff); }
    .bg-outline { border:1px solid currentColor; background: transparent; }
    .agw-badge-pending{ color:#856404; border-color:#ffe58f; background:#fffbe6; }
    .agw-btn{ border-radius: 10px; border: 1px solid #e5e7eb; background:#fff; padding:10px 14px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:8px; }
    .agw-btn:hover{ transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.08); }
  </style>
<?php endif; ?>

<div class="content">
  <div class="dash-wrap" style="padding:20px">
    <h1>📊 Dashboard</h1>

    <!-- ROW 1: Card Stats -->
    <div class="row g-3" style="margin-bottom:12px">
      <div class="col-12">
        <?php include __DIR__ . '/widgets/card-stats.php'; ?>
      </div>
    </div>

    <!-- ROW 2: Sales (70%) + Order Status (30%) -->
    <div class="row g-3">
      <div class="col-lg-8">
        <?php include __DIR__ . '/widgets/chart-sales.php'; ?>
      </div>
      <div class="col-lg-4">
        <?php include __DIR__ . '/widgets/chart-order-status.php'; ?>
      </div>
    </div>

    <!-- ROW 3: Recent Orders (60%) + Pending Resellers (40%) -->
    <div class="row g-3" style="margin-top:12px">
      <div class="col-lg-7">
        <?php include __DIR__ . '/widgets/recent-orders.php'; ?>
      </div>
      <div class="col-lg-5">
        <?php include __DIR__ . '/widgets/pending-resellers.php'; ?>  <!-- ✅ tampil SEKALI & sinkron -->
      </div>
    </div>

    <!-- ROW 4: Recent Products -->
    <div class="row g-3" style="margin-top:12px">
      <div class="col-12">
        <?php include __DIR__ . '/widgets/recent-products.php'; ?>
      </div>
    </div>

    <!-- FOOTER: Quick Actions -->
    <div class="row g-3" style="margin-top:12px">
      <div class="col-12">
        <?php include __DIR__ . '/widgets/quick-actions.php'; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
