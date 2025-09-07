<?php
session_start();
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../includes/auth_admin.php";

/* ===================== Dropdown Kategori ===================== */
$kategori = mysqli_query($conn, "SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC");

/* ===================== Dropdown Reseller (pakai tabel reseller) ===================== */
/* Hanya reseller aktif & akunnya tidak diblokir */
$reseller = mysqli_query($conn, "
    SELECT r.id, r.nama_toko, r.status, u.no_hp
    FROM reseller r
    LEFT JOIN users u ON u.id = r.user_id
    WHERE r.status = 'aktif'
      AND (u.is_blocked IS NULL OR u.is_blocked = 0)
    ORDER BY r.nama_toko ASC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = $_POST['nama_produk'];
    $deskripsi   = $_POST['deskripsi'];

    // Normalisasi harga (terima 15.000, 15.000,00, 15000.00)
    $harga       = (float) str_replace(['.', ','], ['', '.'], $_POST['harga']);

    $stok        = (int) $_POST['stok'];
    $id_kategori = (int) $_POST['kategori'];
    $satuan      = $_POST['satuan'];
    $status      = $_POST['status'];

    // Reseller: wajib pilih; nilai yang dikirim harus reseller.id
    $id_reseller = isset($_POST['reseller']) ? (int) $_POST['reseller'] : 0;

    // Validasi reseller ada di tabel reseller
    $resCheck = mysqli_query($conn, "SELECT id, user_id FROM reseller WHERE id = {$id_reseller} LIMIT 1");
    if (!$resCheck || mysqli_num_rows($resCheck) === 0) {
        $_SESSION['error'] = "Reseller tidak valid.";
        header("Location: tambah.php");
        exit;
    }
    $resRow  = mysqli_fetch_assoc($resCheck);
    $resUser = (int)$resRow['user_id'];

    // Opsional: update no_hp user bila diisi
    $no_hp_input = trim($_POST['no_hp'] ?? '');
    if ($no_hp_input !== '' && $resUser > 0) {
        $uphp = mysqli_prepare($conn, "UPDATE users SET no_hp=? WHERE id=?");
        mysqli_stmt_bind_param($uphp, "si", $no_hp_input, $resUser);
        mysqli_stmt_execute($uphp);
    }

    // Upload gambar ke uploads/products
    $gambar = null;
    if (!empty($_FILES['gambar']['name'])) {
        $targetDir = __DIR__ . "/../../uploads/products/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $gambar = time() . "_" . basename($_FILES['gambar']['name']);
        $targetFile = $targetDir . $gambar;
        @move_uploaded_file($_FILES['gambar']['tmp_name'], $targetFile);
    }

    // INSERT prepared (reseller_id = reseller.id)
    $sql = "INSERT INTO produk
            (nama_produk, deskripsi, harga, stok, kategori_id, reseller_id, satuan, status, gambar)
            VALUES (?,?,?,?,?,?,?,?,?)";
    $st = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param(
        $st,
        "ssdiiisss", // s,s,d,i,i,i,s,s,s
        $nama, $deskripsi, $harga, $stok, $id_kategori, $id_reseller, $satuan, $status, $gambar
    );

    if ($st && mysqli_stmt_execute($st)) {
        $_SESSION['success'] = "Produk berhasil ditambahkan!";
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['error'] = "Gagal menambahkan produk: " . mysqli_error($conn);
        header("Location: tambah.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Produk - Admin</title>
    <link rel="stylesheet" href="../includes/style.css">
    <style>
        .card { background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.08); max-width:700px; margin:20px auto;}
        h2 { font-size:22px; margin-bottom:20px; color:#333;}
        label { font-weight:600; margin-top:12px; display:block; color:#444;}
        input, select, textarea { width:100%; padding:10px; margin-top:6px; border-radius:8px; border:1px solid #ccc; font-size:15px;}
        .btn { padding:10px 18px; border-radius:8px; border:none; cursor:pointer; margin-top:18px; font-weight:600;}
        .btn-primary { background:#28a745; color:#fff;}
        .btn-secondary { background:#6c757d; color:#fff; text-decoration:none; padding:10px 18px; display:inline-block; margin-left:8px;}
        .hint { font-size:.9em; color:#666; margin-top:4px;}
        .flash-ok{padding:10px;background:#d4edda;color:#155724;border-radius:6px;margin-bottom:12px}
        .flash-err{padding:10px;background:#f8d7da;color:#721c24;border-radius:6px;margin-bottom:12px}
    </style>
</head>
<body>
<div class="content">
    <div class="card">
        <h2>➕ Tambah Produk</h2>

        <?php if(isset($_SESSION['error'])): ?>
          <div class="flash-err">❌ <?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php elseif(isset($_SESSION['success'])): ?>
          <div class="flash-ok">✅ <?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>Nama Produk</label>
            <input type="text" name="nama_produk" required>

            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="4" required></textarea>

            <label>Harga</label>
            <!-- pakai text + formatter ribuan seperti halaman edit -->
            <input
              type="text"
              name="harga"
              id="harga"
              inputmode="decimal"
              pattern="[0-9.,]+"
              placeholder="cth: 15.000 atau 15.000,00"
              required
            >
            <div class="hint">Ketik dengan titik untuk ribuan (15.000). Desimal pakai koma (15.000,50).</div>

            <label>Stok</label>
            <input type="number" name="stok" required min="1">

            <label>Satuan</label>
            <select name="satuan" required>
                <option value="">-- Pilih Satuan --</option>
                <option value="kg">kg</option>
                <option value="liter">liter</option>
                <option value="pcs">pcs</option>
                <option value="pak">pak</option>
                <option value="dus">dus</option>
            </select>
            <div class="hint">Pilih satuan sesuai produk.</div>

            <label>Kategori</label>
            <select name="kategori" required>
                <option value="">-- Pilih Kategori --</option>
                <?php while ($row = mysqli_fetch_assoc($kategori)) { ?>
                    <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nama_kategori']) ?></option>
                <?php } ?>
            </select>

            <label>Reseller</label>
            <select name="reseller" required>
                <option value="">-- Pilih Reseller --</option>
                <?php if ($reseller && mysqli_num_rows($reseller)): ?>
                    <?php while ($row = mysqli_fetch_assoc($reseller)) { ?>
                        <option value="<?= (int)$row['id'] ?>">
                            <?= htmlspecialchars($row['nama_toko']) ?>
                        </option>
                    <?php } ?>
                <?php else: ?>
                    <option value="" disabled>(Tidak ada reseller tersedia)</option>
                <?php endif; ?>
            </select>
            <div class="hint">Daftar dari tabel <b>reseller</b> (hanya toko aktif & tidak diblokir).</div>

            <label>No HP Reseller (opsional)</label>
            <input type="text" name="no_hp" placeholder="Masukkan nomor HP reseller">

            <label>Status</label>
            <select name="status" required>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </select>

            <label>Gambar Produk</label>
            <input type="file" name="gambar" accept="image/*">

            <button type="submit" class="btn btn-primary">Simpan Produk</button>
            <a href="index.php" class="btn-secondary">Batal</a>
        </form>
    </div>
</div>

<script>
// Formatter ribuan untuk input harga (Indonesia)
const hargaEl = document.getElementById('harga');
if (hargaEl) {
  hargaEl.addEventListener('input', () => {
    let v = hargaEl.value.replace(/[^\d,]/g,'');
    let parts = v.split(',');
    let intPart = (parts[0] || '').replace(/\D/g,'');
    let decPart = parts[1] ? ',' + parts[1] : '';
    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    hargaEl.value = intPart + decPart;
  });
}
</script>
</body>
</html>
