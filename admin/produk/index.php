<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../includes/auth_admin.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

// helper rupiah
function rupiah($n){ return 'Rp '.number_format((float)$n,0,',','.'); }

/**
 * Normalisasi URL foto agar selalu benar dari /agromart
 * - support kolom: gambar|foto|image|thumbnail
 * - support nilai: absolute URL, path mulai '/', path 'uploads/...', atau hanya filename
 * - fallback cek beberapa kandidat lokasi file
 */
function buildFotoUrl(array $row): ?string {
  $candidates = ['gambar','foto','image','thumbnail'];
  $val = null;
  foreach ($candidates as $c) {
    if (!empty($row[$c])) { $val = trim((string)$row[$c]); break; }
  }
  if (!$val) return null;

  // Absolute URL
  if (preg_match('~^https?://~i', $val)) return $val;

  // Root-relative
  if (strpos($val, '/') === 0) return $val;

  // Base path project
  $base = '/agromart';

  // default rak
  $url = $base . '/uploads/products/' . $val;

  // Jika sudah 'uploads/...'
  if (stripos($val, 'uploads/') === 0) $url = $base . '/' . $val;

  // verifikasi fisik
  $webRoot = realpath(__DIR__ . '/../../'); // /agromart
  $try = [
    $url,
    $base . '/uploads/' . $val,            // /agromart/uploads/<file>
    $base . '/uploads/products/' . $val,   // /agromart/uploads/products/<file>
  ];
  foreach ($try as $u) {
    $abs = $webRoot . DIRECTORY_SEPARATOR . ltrim(str_replace('/','/',$u), '/');
    $abs = str_replace('/', DIRECTORY_SEPARATOR, $abs);
    if (is_file($abs)) return $u;
  }

  // terakhir: tetap kembalikan $url (biar minimal coba tampil)
  return $url;
}

// Ambil produk + kategori + reseller (JOIN ke tabel reseller + users)
$sql = "
  SELECT
    p.id, p.nama_produk, p.gambar, p.harga, p.stok, p.satuan, p.status AS status_produk,
    k.nama_kategori,
    r.id AS reseller_id, r.nama_toko, r.status AS status_toko,
    u.nama AS nama_akun, u.is_blocked
  FROM produk p
  LEFT JOIN kategori k ON k.id = p.kategori_id
  LEFT JOIN reseller r ON r.id = p.reseller_id
  LEFT JOIN users u ON u.id = r.user_id
  ORDER BY p.id DESC
";
$res = mysqli_query($conn, $sql);
?>
<div class="content">
  <h1>📦 Menu Produk (Admin)</h1>

  <!-- Pesan sukses/error -->
  <?php if(isset($_SESSION['success'])): ?>
    <div style="padding:10px; background:#d4edda; color:#155724; border-radius:6px; margin-bottom:15px;">
      ✅ <?= $_SESSION['success']; ?>
    </div>
    <?php unset($_SESSION['success']); ?>
  <?php elseif(isset($_SESSION['error'])): ?>
    <div style="padding:10px; background:#f8d7da; color:#721c24; border-radius:6px; margin-bottom:15px;">
      ❌ <?= $_SESSION['error']; ?>
    </div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <a href="tambah.php" class="btn btn-success" style="margin:10px 0; display:inline-block;">+ Tambah Produk</a>

  <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse; background:#fff;">
    <thead style="background:#2ecc71; color:#fff;">
      <tr>
        <th>Gambar</th>
        <th>Nama Produk</th>
        <th>Kategori</th>
        <th>Harga</th>
        <th>Stok</th>
        <th>Satuan</th>
        <th>Reseller</th>
        <th>Status Produk</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if($res && mysqli_num_rows($res)): while($row = mysqli_fetch_assoc($res)):
        // Nama toko dari reseller, fallback ke nama akun
        $namaReseller = $row['nama_toko'] ?: ($row['nama_akun'] ?? '-');
        $fotoUrl = buildFotoUrl($row);
      ?>
        <tr style="text-align:center">
          <td>
            <?php if($fotoUrl): ?>
              <img src="<?= htmlspecialchars($fotoUrl) ?>" alt="" width="60" height="60" style="border-radius:6px; object-fit:cover">
            <?php else: ?>
              📷 -
            <?php endif; ?>
          </td>
          <td style="text-align:left"><?= htmlspecialchars($row['nama_produk']) ?></td>
          <td><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
          <td><?= rupiah($row['harga']) ?></td>
          <td><?= (int)$row['stok'] ?></td>
          <td><?= htmlspecialchars($row['satuan'] ?? '-') ?></td>
          <td>
            <?= htmlspecialchars($namaReseller) ?><br>
            <?php if($row['reseller_id']): ?>
              <?php if($row['is_blocked']=='1' || $row['status_toko']==='blokir'): ?>
                <span style="color:#e74c3c; font-size:0.9em;">⛔ Diblokir</span>
              <?php else: ?>
                <span style="color:#27ae60; font-size:0.9em;">✅ Aktif</span>
              <?php endif; ?>
            <?php endif; ?>
          </td>
          <td>
            <?php
              $s = strtolower((string)$row['status_produk']);
              echo ($s==='aktif' || $s==='1') ? '✅ Aktif' : '❌ Nonaktif';
            ?>
          </td>
          <td>
            <a href="detail.php?id=<?= (int)$row['id'] ?>" style="text-decoration:none;">🔍 Detail</a> |
            <a href="hapus.php?id=<?= (int)$row['id'] ?>"
               onclick="return confirm('Yakin ingin menghapus produk ini?')"
               style="color:#c0392b; text-decoration:none;">🗑 Hapus</a>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="9" style="text-align:center; padding:16px;">Belum ada data.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
