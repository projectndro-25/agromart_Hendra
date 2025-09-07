<?php
// /user/index.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth_user.php'; // set $user_id

// Helper lokal non-duplikat
function buildFotoUrl(array $p): ?string{
  $candidates = ['gambar','foto','image','thumbnail'];
  $val = null;
  foreach($candidates as $c){ if(!empty($p[$c])){ $val = trim((string)$p[$c]); break; } }
  if(!$val) return null;
  if(preg_match('~^https?://~i',$val)) return $val;
  if(strpos($val,'/')===0) return $val;
  $base = '/agromart';
  if(stripos($val,'uploads/')===0){ $url = $base.'/'.$val; }
  else { $url = $base.'/uploads/products/'.$val; }
  return $url;
}

// Query parameter
$kat   = isset($_GET['kat']) ? (int)$_GET['kat'] : 0;
$q     = trim($_GET['q'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 9;
$offset= ($page-1)*$limit;

// Ambil kategori
$kategori = [];
if ($st = mysqli_prepare($conn, "SELECT id,nama_kategori FROM kategori WHERE (status='aktif' OR status IS NULL) ORDER BY nama_kategori")) {
  mysqli_stmt_execute($st);
  $rs = mysqli_stmt_get_result($st);
  while($row = mysqli_fetch_assoc($rs)) $kategori[] = $row;
  mysqli_stmt_close($st);
}

// Build query produk (count + list)
$where = "p.status='aktif'";
$params = []; $types = '';
if ($kat > 0){ $where .= " AND p.kategori_id=?"; $types.='i'; $params[]=$kat; }
if ($q !== ''){ $where .= " AND p.nama_produk LIKE CONCAT('%',?,'%')"; $types.='s'; $params[]=$q; }

// Count total
$total = 0;
$sqlCount = "SELECT COUNT(*) AS c FROM produk p WHERE $where";
if($st = mysqli_prepare($conn,$sqlCount)){
  if($types!=='') mysqli_stmt_bind_param($st,$types,...$params);
  mysqli_stmt_execute($st);
  $rs = mysqli_stmt_get_result($st);
  if($row = mysqli_fetch_assoc($rs)) $total = (int)$row['c'];
  mysqli_stmt_close($st);
}

// Data list — FIX: hanya ambil kolom yang ada (hapus p.foto, p.image, p.thumbnail)
$sql = "
SELECT p.id, p.nama_produk, p.harga, p.stok, p.gambar,
       k.nama_kategori, r.nama_toko
FROM produk p
LEFT JOIN kategori k ON k.id=p.kategori_id
JOIN reseller r ON r.id=p.reseller_id
WHERE $where
ORDER BY p.created_at DESC
LIMIT ? OFFSET ?";
$types2 = $types.'ii'; $params2 = $params; $params2[] = $limit; $params2[] = $offset;

$produk = [];
if($st = mysqli_prepare($conn,$sql)){
  mysqli_stmt_bind_param($st,$types2,...$params2);
  mysqli_stmt_execute($st);
  $rs = mysqli_stmt_get_result($st);
  while($row = mysqli_fetch_assoc($rs)) $produk[] = $row;
  mysqli_stmt_close($st);
}

include __DIR__ . '/includes/header.php';
?>

<h1>Produk Pilihan</h1>
<div class="muted">Temukan kebutuhan tani & sembako favoritmu ✨</div>

<form class="filters" method="get" action="index.php">
  <select name="kat">
    <option value="0">Semua Kategori</option>
    <?php foreach($kategori as $k): ?>
      <option value="<?= (int)$k['id'] ?>" <?= $kat===$k['id']?'selected':'' ?>>
        <?= htmlspecialchars($k['nama_kategori']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="q" placeholder="Cari…" value="<?= htmlspecialchars($q) ?>">
  <button class="btn btn-primary" type="submit">Cari</button>
</form>

<div class="grid">
  <?php if(!$produk): ?>
    <div class="card p16" style="grid-column:1/-1;">Belum ada produk yang cocok.</div>
  <?php else: foreach($produk as $p): 
    $img = buildFotoUrl($p) ?: 'https://placehold.co/600x400?text=AgroMart';
  ?>
    <div class="card">
      <a href="produk.php?id=<?= (int)$p['id'] ?>">
        <img class="product-img" src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>">
      </a>
      <div class="p16">
        <a class="title" href="produk.php?id=<?= (int)$p['id'] ?>">
          <?= htmlspecialchars($p['nama_produk']) ?>
        </a>
        <div class="sub"><?= htmlspecialchars($p['nama_kategori'] ?: '-') ?> • <?= htmlspecialchars($p['nama_toko']) ?></div>
        <div class="price"><?= rupiah($p['harga']) ?></div>
        <form class="row" action="keranjang/add.php" method="post">
          <input type="hidden" name="produk_id" value="<?= (int)$p['id'] ?>">
          <input class="qty" type="number" name="qty" min="1" value="1">
          <button class="btn btn-primary" type="submit">+ Keranjang</button>
        </form>
      </div>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php
// Pagination
$pages = max(1, (int)ceil($total / $limit));
if ($pages>1):
  echo '<div class="pagination">';
  for($i=1;$i<=$pages;$i++){
    $cls = 'page'.($i===$page?' active':'');
    $qs = http_build_query(['kat'=>$kat,'q'=>$q,'page'=>$i]);
    echo '<a class="'.$cls.'" href="?'.$qs.'">'.$i.'</a>';
  }
  echo '</div>';
endif;

include __DIR__ . '/includes/footer.php';
