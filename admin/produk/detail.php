<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/auth_admin.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

/* Tentukan URL kembali (default ke daftar produk) */
$backUrl = "index.php";
if (isset($_GET['from']) && $_GET['from'] === 'reseller' && isset($_GET['id_reseller'])) {
    $backUrl = "../reseller/detail.php?id=" . (int)$_GET['id_reseller'];
}

/* Cek ID produk */
if (!isset($_GET['id'])) {
    echo "<div class='content'><p>❌ Produk tidak ditemukan.</p>
            <a href='".htmlspecialchars($backUrl)."' style='padding:10px 16px; background:#7f8c8d; color:#fff; text-decoration:none; border-radius:6px;'>⬅️ Kembali</a>
          </div>";
    include __DIR__ . '/../includes/footer.php';
    exit;
}
$id = (int) $_GET['id'];

/* Ambil produk + kategori + reseller + user akun (tanpa kolom logo — kita ambil terpisah) */
$q = "
    SELECT p.*, k.nama_kategori,
           r.id AS reseller_id, r.nama_toko, r.deskripsi AS deskripsi_toko, r.alamat AS alamat_toko, r.status AS status_reseller,
           u.email, u.no_hp, u.is_blocked
    FROM produk p
    LEFT JOIN kategori k ON p.kategori_id = k.id
    LEFT JOIN reseller r ON p.reseller_id = r.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE p.id = ?
    LIMIT 1
";
$st = mysqli_prepare($conn, $q);
mysqli_stmt_bind_param($st, "i", $id);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
$produk = $r ? mysqli_fetch_assoc($r) : null;

/* Helper format rupiah */
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

/* Normalisasi URL foto produk */
function buildFotoUrl(array $row): ?string {
    $candidates = ['gambar','foto','image','thumbnail'];
    $val = null;
    foreach ($candidates as $c) {
        if (!empty($row[$c])) { $val = trim((string)$row[$c]); break; }
    }
    if (!$val) return null;
    if (preg_match('~^https?://~i', $val)) return $val;
    if (strpos($val, '/') === 0) return $val;

    $base = '/agromart';
    if (stripos($val, 'uploads/') === 0) {
        $url = $base . '/' . $val;
    } else {
        $url = $base . '/uploads/products/' . $val;
    }
    $webRoot  = realpath(__DIR__ . '/../../');
    $path1    = $webRoot . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $url), '/');
    if (is_file($path1)) return $url;

    $alt1 = $base . '/uploads/' . $val;
    $pathAlt1 = $webRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $val;
    if (is_file($pathAlt1)) return $alt1;

    if (stripos($val, 'uploads/products/') === 0) {
        $pathVal = $webRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $val);
        if (is_file($pathVal)) return $base . '/' . $val;
    }
    return $url;
}

/* Helper: cari kolom logo pada tabel reseller dan ambil nilainya untuk reseller_id tertentu */
function fetch_reseller_logo(mysqli $conn, int $resellerId): ?string {
    if ($resellerId <= 0) return null;
    $candidates = ['logo','foto_logo','avatar','foto','image'];
    $logoCol = null;
    foreach ($candidates as $col) {
        $rs = mysqli_query($conn, "SHOW COLUMNS FROM reseller LIKE '$col'");
        if ($rs && mysqli_num_rows($rs) > 0) { $logoCol = $col; break; }
    }
    if (!$logoCol) return null;
    $rid = (int)$resellerId;
    $rs = mysqli_query($conn, "SELECT `$logoCol` AS logo_path FROM reseller WHERE id=$rid LIMIT 1");
    if ($rs && ($row = mysqli_fetch_assoc($rs)) && !empty($row['logo_path'])) {
        // Kembalikan sebagai URL relatif untuk <img src="/agromart/…">
        return $row['logo_path'];
    }
    return null;
}

/* Ambil logo reseller (jika ada) */
$logoReseller = null;
if ($produk && !empty($produk['reseller_id'])) {
    $logoReseller = fetch_reseller_logo($conn, (int)$produk['reseller_id']);
}
?>
<div class="content">
  <h1>🔍 Detail Produk</h1>

  <?php if (!$produk): ?>
    <div class="card" style="padding:25px; border:1px solid #ddd; border-radius:12px; background:#fff; max-width:950px; margin:auto;">
      <p>❌ Produk tidak ditemukan.</p>
      <a href="<?= htmlspecialchars($backUrl) ?>"
         style="padding:10px 16px; background:#7f8c8d; color:#fff; text-decoration:none; border-radius:6px;">⬅️ Kembali</a>
    </div>
  <?php else: ?>

  <div class="card" style="padding:25px; border:1px solid #ddd; border-radius:12px; background:#fff; max-width:950px; margin:auto;">
    <!-- Foto Produk -->
    <div style="text-align:center; margin-bottom:25px;">
      <?php $fotoUrl = buildFotoUrl($produk); ?>
      <?php if ($fotoUrl): ?>
        <img src="<?= htmlspecialchars($fotoUrl) ?>"
             alt="<?= htmlspecialchars($produk['nama_produk']) ?>"
             style="max-width:280px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); object-fit:cover">
      <?php else: ?>
        <div style="padding:14px 20px; background:#f7f7f7; border-radius:8px; display:inline-block">📷 Tidak ada foto</div>
      <?php endif; ?>
    </div>

    <!-- Info Produk -->
    <h2 style="margin:0 0 12px 0;"><?= htmlspecialchars($produk['nama_produk']) ?></h2>
    <p style="margin:6px 0"><b>📄 Deskripsi:</b><br><?= nl2br(htmlspecialchars($produk['deskripsi'] ?? '-')) ?></p>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; margin-top:14px;">
      <p style="margin:6px 0"><b>💰 Harga:</b> <?= rupiah($produk['harga']) ?></p>
      <p style="margin:6px 0"><b>📦 Stok:</b> <?= (int) $produk['stok'] ?></p>
      <p style="margin:6px 0"><b>⚖️ Satuan:</b> <?= htmlspecialchars($produk['satuan'] ?? '-') ?></p>
      <p style="margin:6px 0"><b>🗂 Kategori:</b> <?= htmlspecialchars($produk['nama_kategori'] ?? '-') ?></p>
      <p style="margin:6px 0">
        <b>📌 Status Produk:</b>
        <?php
          $sp = strtolower((string)$produk['status']);
          echo ($sp === 'aktif' || $sp === '1')
              ? '<span style="padding:4px 10px; background:#e9f9ee; color:#27ae60; border-radius:6px; font-size:13px;">✅ Aktif</span>'
              : '<span style="padding:4px 10px; background:#ffeaea; color:#d63031; border-radius:6px; font-size:13px;">❌ Nonaktif</span>';
        ?>
      </p>
    </div>

    <!-- Info Reseller -->
    <div style="
        margin-top:20px;
        padding:20px;
        border-radius:14px;
        background:linear-gradient(135deg, #f5f7fa, #e6ecf5);
        box-shadow:0 4px 12px rgba(0,0,0,0.08);
        border-left:6px solid #6c5ce7;
    ">
      <h3 style="margin:0 0 15px 0; font-size:18px; font-weight:600; color:#2d3436; display:flex; align-items:center;">
        <span style="font-size:20px; margin-right:8px; color:#6c5ce7;">👤</span> Informasi Reseller
      </h3>

      <?php if ($logoReseller): ?>
        <div style="display:flex;align-items:center;gap:10px;margin:6px 0 14px 0">
          <img src="/agromart/<?= htmlspecialchars($logoReseller) ?>"
               alt="Logo Reseller" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:1px solid #e5e7eb">
          <span class="muted" style="font-size:13px">Logo Reseller</span>
        </div>
      <?php endif; ?>

      <p><b>Nama Toko:</b> <?= htmlspecialchars($produk['nama_toko'] ?? '-') ?></p>
      <p><b>Email:</b> <a href="mailto:<?= htmlspecialchars($produk['email'] ?? '-') ?>" style="color:#0984e3; text-decoration:none;">
          <?= htmlspecialchars($produk['email'] ?? '-') ?>
      </a></p>
      <p><b>No HP:</b> <?= htmlspecialchars($produk['no_hp'] ?? '-') ?></p>
      <p><b>Alamat:</b> <?= htmlspecialchars($produk['alamat_toko'] ?? '-') ?></p>
      <p><b>Deskripsi Toko:</b> <?= nl2br(htmlspecialchars($produk['deskripsi_toko'] ?? '-')) ?></p>
      <p>
        <b>Status Reseller:</b>
        <?php
          $blocked = (!empty($produk['is_blocked']) && (string)$produk['is_blocked'] === '1');
          $statusOk = (strtolower((string)$produk['status_reseller']) === 'aktif' && !$blocked);
          echo $statusOk
            ? '<span style="padding:4px 10px; background:#e9f9ee; color:#27ae60; border-radius:6px; font-size:13px;">✅ Aktif</span>'
            : '<span style="padding:4px 10px; background:#ffeaea; color:#d63031; border-radius:6px; font-size:13px;">⛔ Tidak Aktif/Blokir</span>';
        ?>
      </p>
    </div>

    <!-- Tombol Aksi -->
    <div style="margin-top:25px;">
      <a href="<?= htmlspecialchars($backUrl) ?>"
         style="padding:10px 16px; background:#7f8c8d; color:#fff; text-decoration:none; border-radius:6px; margin-right:8px;">⬅️ Kembali</a>

      <a href="edit.php?id=<?= (int) $produk['id'] ?>"
         style="padding:10px 16px; background:#3498db; color:#fff; text-decoration:none; border-radius:6px; margin-right:8px;">✏️ Edit</a>

      <a href="hapus.php?id=<?= (int) $produk['id'] ?>"
         onclick="return confirm('Yakin ingin menghapus produk ini?')"
         style="padding:10px 16px; background:#e74c3c; color:#fff; text-decoration:none; border-radius:6px; margin-right:8px;">🗑 Hapus</a>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
