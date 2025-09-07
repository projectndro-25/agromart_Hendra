<?php
session_start();
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../includes/auth_admin.php";

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID produk tidak ditemukan.";
    header("Location: index.php");
    exit;
}

$id = (int) $_GET['id'];

// Ambil produk
$st = mysqli_prepare($conn, "SELECT * FROM produk WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($st, "i", $id);
mysqli_stmt_execute($st);
$result = mysqli_stmt_get_result($st);
if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['error'] = "Produk tidak ditemukan.";
    header("Location: index.php");
    exit;
}
$produk = mysqli_fetch_assoc($result);
$old_reseller_id = isset($produk['reseller_id']) ? (int)$produk['reseller_id'] : null;

// Ambil kategori
$kategori = [];
$kategoriQuery = mysqli_query($conn, "SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
while ($row = mysqli_fetch_assoc($kategoriQuery)) { $kategori[] = $row; }

/* Dropdown reseller (tabel reseller join users) */
$resellerQuery = mysqli_query($conn, "
    SELECT r.id, r.nama_toko, r.status, IFNULL(u.is_blocked,0) AS is_blocked
    FROM reseller r
    LEFT JOIN users u ON u.id = r.user_id
    WHERE r.status='aktif' AND (u.is_blocked IS NULL OR u.is_blocked = 0)
    ORDER BY r.nama_toko ASC
");
$available = [];
while ($row = mysqli_fetch_assoc($resellerQuery)) { $available[] = $row; }

/* Reseller saat ini (jika ada) */
$currentRes = null;
if (!empty($produk['reseller_id'])) {
    $crq = mysqli_query($conn, "
        SELECT r.id, r.nama_toko, r.status, IFNULL(u.is_blocked,0) AS is_blocked
        FROM reseller r
        LEFT JOIN users u ON u.id = r.user_id
        WHERE r.id = ".(int)$produk['reseller_id']." LIMIT 1
    ");
    if ($crq && mysqli_num_rows($crq)) {
        $currentRes = mysqli_fetch_assoc($crq);
        // keluarkan dari list agar tidak dobel
        $available = array_filter($available, function($r) use ($currentRes){
            return (int)$r['id'] !== (int)$currentRes['id'];
        });
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = $_POST['nama_produk'];
    $deskripsi   = $_POST['deskripsi'];

    // ✅ Normalisasi harga (terima: 15.000, 15.000,00, 15000.00, dll)
    $hargaRaw    = $_POST['harga'];
    $harga       = (float) str_replace(['.', ','], ['', '.'], $hargaRaw);

    $stok        = (int) $_POST['stok'];
    $id_kategori = (int) $_POST['kategori'];
    $satuan      = $_POST['satuan'];
    $status      = $_POST['status'];

    // reseller baru dari form
    $id_reseller = isset($_POST['reseller']) && $_POST['reseller'] !== '' ? (int)$_POST['reseller'] : 0;
    $new_reseller_id = ($id_reseller > 0) ? $id_reseller : null;

    /* ✅ Validasi bentrok hanya jika reseller BERUBAH */
    if ($new_reseller_id !== null && $new_reseller_id !== $old_reseller_id) {
        $cekBentrok = mysqli_query($conn, "SELECT id FROM produk WHERE reseller_id={$new_reseller_id} AND id <> {$id} LIMIT 1");
        if ($cekBentrok && mysqli_num_rows($cekBentrok) > 0) {
            $_SESSION['error'] = "Reseller terpilih sudah digunakan produk lain.";
            header("Location: edit.php?id=".$id);
            exit;
        }
    }

    // Upload gambar (opsional) ke uploads/products
    $gambar = $produk['gambar'];
    if (!empty($_FILES['gambar']['name'])) {
        $targetDir = __DIR__ . "/../../uploads/products/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $gambarBaru = time() . "_" . basename($_FILES['gambar']['name']);
        $targetFile = $targetDir . $gambarBaru;

        if (move_uploaded_file($_FILES['gambar']['tmp_name'], $targetFile)) {
            if (!empty($produk['gambar']) && file_exists($targetDir . $produk['gambar'])) {
                @unlink($targetDir . $produk['gambar']);
            }
            $gambar = $gambarBaru;
        }
    }

    // Siapkan query UPDATE
    if ($new_reseller_id !== null) {
        $sql = "UPDATE produk SET 
                    nama_produk=?, deskripsi=?, harga=?, stok=?, kategori_id=?, reseller_id=?, satuan=?, status=?, gambar=?
                WHERE id=?";
        $stp = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stp, "ssdiiisssi",
            $nama, $deskripsi, $harga, $stok, $id_kategori,
            $new_reseller_id, $satuan, $status, $gambar, $id
        );
    } else {
        $sql = "UPDATE produk SET 
                    nama_produk=?, deskripsi=?, harga=?, stok=?, kategori_id=?, reseller_id=NULL, satuan=?, status=?, gambar=?
                WHERE id=?";
        $stp = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stp, "ssdiisssi",
            $nama, $deskripsi, $harga, $stok, $id_kategori,
            $satuan, $status, $gambar, $id
        );
    }

    if ($stp && mysqli_stmt_execute($stp)) {
        $_SESSION['success'] = "Produk berhasil diperbarui!";
        header("Location: detail.php?id=$id");
        exit;
    } else {
        $_SESSION['error'] = "Gagal memperbarui produk: ".mysqli_error($conn);
        header("Location: edit.php?id=$id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Produk</title>
    <link rel="stylesheet" href="../includes/style.css">
    <style>
        .card { background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.08); max-width:700px; margin:20px auto;}
        h2 { margin-bottom:20px; color:#333;}
        label { font-weight:600; margin-top:12px; display:block; color:#444;}
        input, select, textarea { width:100%; padding:10px; margin-top:6px; border-radius:8px; border:1px solid #ccc; font-size:15px;}
        .btn { padding:10px 18px; border-radius:8px; border:none; cursor:pointer; margin-top:18px; font-weight:600;}
        .btn-primary { background:#007bff; color:#fff;}
        .btn-secondary, .btn-back { background:#6c757d; color:#fff; text-decoration:none; padding:10px 18px; display:inline-block; margin-left:8px; border-radius:8px;}
        .btn-back { background:#17a2b8;}
        .flash-ok{padding:10px;background:#d4edda;color:#155724;border-radius:6px;margin-bottom:12px}
        .flash-err{padding:10px;background:#f8d7da;color:#721c24;border-radius:6px;margin-bottom:12px}
        .hint { font-size:.9em; color:#666; margin-top:4px;}
        /* sembunyikan spinner number di beberapa browser (jaga2 kalau ada) */
        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button{ -webkit-appearance: none; margin: 0; }
        input[type=number]{ -moz-appearance: textfield; }
    </style>
</head>
<body>
<div class="content">
    <div class="card">
        <h2>✏️ Edit Produk</h2>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="flash-ok">✅ <?= $_SESSION['success']; ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php elseif(isset($_SESSION['error'])): ?>
            <div class="flash-err">❌ <?= $_SESSION['error']; ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>Nama Produk</label>
            <input type="text" name="nama_produk" value="<?= htmlspecialchars($produk['nama_produk']); ?>" required>

            <label>Deskripsi</label>
            <textarea name="deskripsi" rows="4" required><?= htmlspecialchars($produk['deskripsi']); ?></textarea>

            <label>Harga</label>
            <!-- ✅ pakai text + format Indonesia, tidak berubah saat scroll -->
            <input
              type="text"
              name="harga"
              id="harga"
              inputmode="decimal"
              pattern="[0-9.,]+"
              value="<?= htmlspecialchars(number_format((float)$produk['harga'], 0, ',', '.')) ?>"
              placeholder="cth: 15.000 atau 15.000,00"
              required
            >
            <div class="hint">Ketik dengan titik sebagai pemisah ribuan (mis: 15.000). Desimal pakai koma (mis: 15.000,50).</div>

            <label>Stok</label>
            <input type="number" name="stok" value="<?= (int)$produk['stok']; ?>" required>

            <label>Kategori</label>
            <select name="kategori" required>
                <?php foreach ($kategori as $row) { ?>
                    <option value="<?= $row['id'] ?>" <?= $produk['kategori_id'] == $row['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['nama_kategori']) ?>
                    </option>
                <?php } ?>
            </select>

            <label>Reseller</label>
            <?php $needRequired = (!empty($available) || !empty($currentRes)) ? 'required' : ''; ?>
            <select name="reseller" <?= $needRequired ?>>
                <option value="">-- Pilih Reseller --</option>
                <?php if ($currentRes): ?>
                    <option value="<?= (int)$currentRes['id'] ?>" selected>
                        <?= htmlspecialchars($currentRes['nama_toko']) ?>
                        <?php if ($currentRes['status'] !== 'aktif' || $currentRes['is_blocked'] == '1') echo " (Tidak Aktif/Blokir)"; ?>
                    </option>
                <?php endif; ?>
                <?php if (!empty($available)): foreach ($available as $row): ?>
                    <option value="<?= (int)$row['id'] ?>" <?= ($produk['reseller_id'] == $row['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($row['nama_toko']) ?>
                    </option>
                <?php endforeach; elseif (!$currentRes): ?>
                    <option value="" disabled>(Tidak ada reseller tersedia)</option>
                <?php endif; ?>
            </select>
            <div class="hint">Data diambil dari tabel <b>reseller</b>. Toko yang aktif & tidak diblokir saja yang muncul.</div>

            <label>Satuan</label>
            <input type="text" name="satuan" value="<?= htmlspecialchars($produk['satuan']); ?>">

            <label>Status</label>
            <select name="status" required>
                <option value="aktif" <?= $produk['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $produk['status'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select>

            <label>Gambar Produk</label><br>
            <?php if (!empty($produk['gambar'])) { ?>
                <img src="../../uploads/products/<?= htmlspecialchars($produk['gambar']); ?>" alt="gambar produk" width="120"><br>
            <?php } ?>
            <input type="file" name="gambar" accept="image/*">

            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="index.php" class="btn-secondary">Batal</a>
            <a href="index.php" class="btn-back">⬅ Kembali ke Daftar Produk</a>
        </form>
    </div>
</div>

<script>
// Format ribuan Indonesia saat mengetik di field harga
const hargaEl = document.getElementById('harga');
if (hargaEl) {
  hargaEl.addEventListener('input', () => {
    // ambil angka saja
    let v = hargaEl.value.replace(/[^\d,]/g,'');
    // pisah desimal jika ada koma
    let parts = v.split(',');
    let intPart = parts[0].replace(/\D/g,'');
    let decPart = parts[1] ? ',' + parts[1] : '';
    // format ribuan dengan titik
    intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    hargaEl.value = intPart + decPart;
  });
}
</script>
</body>
</html>
