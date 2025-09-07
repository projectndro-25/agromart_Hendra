<?php
// admin/widgets/quick-actions.php
require_once __DIR__ . '/../includes/auth_admin.php';
?>
<div class="card agw-panel">
  <div class="card-body">
    <h6 class="mb-3">Quick Actions</h6>
    <div class="d-flex flex-wrap gap-2">
      <a href="kategori/tambah.php" class="btn agw-btn">
        <i class="fa-solid fa-plus"></i> Tambah Kategori
      </a>
      <a href="reseller/pending.php" class="btn agw-btn">
        <i class="fa-solid fa-user-clock"></i> Reseller Pending
      </a>
      <a href="produk/tambah.php" class="btn agw-btn">
        <i class="fa-solid fa-box-open"></i> Tambah Produk
      </a>
      <a href="laporan.php" class="btn agw-btn">
        <i class="fa-solid fa-chart-line"></i> Laporan Penjualan
      </a>
    </div>
  </div>
</div>
