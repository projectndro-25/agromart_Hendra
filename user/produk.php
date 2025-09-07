<?php
// /user/produk.php
require_once __DIR__ . '/includes/auth_user.php';
include __DIR__ . '/includes/header.php';
?>
<style>
  .video-box{ max-width:780px; margin:8px auto; }
  @media (min-width:1200px){ .video-box{ max-width:860px; } }
  .video-ratio{ position:relative; width:100%; aspect-ratio:16/9; border-radius:12px; overflow:hidden; background:#000; }
  .video-ratio iframe, .video-ratio video{ position:absolute; inset:0; width:100%; height:100%; object-fit:contain; background:#000; }
  .video-open{ margin-top:6px; color:#6b7280; }
</style>
<?php
/* ==== helpers ringkas ==== */
if (!function_exists('rupiah')) { function rupiah($n){ return 'Rp ' . number_format((float)$n,0,',','.'); } }
if (!function_exists('buildFotoUrl')) {
  function buildFotoUrl(array $p): ?string{
    foreach(['gambar','foto','image','thumbnail'] as $c){ if(!empty($p[$c])){ $v=trim($p[$c]); break; } }
    if(empty($v)) return null;
    if(preg_match('~^https?://~i',$v) || strpos($v,'/')===0) return $v;
    return '/agromart/'.(stripos($v,'uploads/')===0 ? $v : 'uploads/products/'.$v);
  }
}
if (!function_exists('buildLogoUrl')) {
  function buildLogoUrl(array $r): ?string{
    foreach(['logo','logo_path','foto','image','avatar','thumbnail'] as $c){ if(!empty($r[$c])){ $v=trim($r[$c]); break; } }
    if(empty($v)) return null;
    if(preg_match('~^https?://~i',$v) || strpos($v,'/')===0) return $v;
    return '/agromart/'.(stripos($v,'uploads/')===0 ? $v : 'uploads/resellers/'.$v);
  }
}
if (!function_exists('stars_html')) {
  function stars_html($r){ $r=max(0,min(5,(float)$r)); $f=floor($r); $h=($r-$f>=0.5)?1:0; return str_repeat('★',$f).($h?'☆':'').str_repeat('☆',5-$f-$h); }
}

/* ==== embed video (YouTube & file lokal) + wrapper ==== */
if (!function_exists('embedVideoHtml')) {
  function embedVideoHtml(string $url): string{
    $u = trim($url);
    $allowed = ['mp4','m4v','webm','ogv','ogg','mov','mkv','avi','3gp'];
    $mimeMap = [
      'mp4'=>'video/mp4','m4v'=>'video/mp4','webm'=>'video/webm',
      'ogv'=>'video/ogg','ogg'=>'video/ogg','mov'=>'video/quicktime',
      'mkv'=>'video/x-matroska','avi'=>'video/x-msvideo','3gp'=>'video/3gpp'
    ];

    // YouTube
    if (preg_match('~(youtube\.com|youtu\.be)~i',$u)) {
      $id=null;
      if (preg_match('~youtu\.be/([A-Za-z0-9_\-]{6,})~',$u,$m)) $id=$m[1];
      if (!$id) { parse_str((string)parse_url($u,PHP_URL_QUERY),$q); $id=$q['v']??null; }
      if (!$id && preg_match('~embed/([A-Za-z0-9_\-]{6,})~',$u,$m2)) $id=$m2[1];
      if ($id) {
        $iframe = '<iframe src="https://www.youtube.com/embed/'.htmlspecialchars($id).'" title="YouTube video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>';
        return '<div class="video-box"><div class="video-ratio">'.$iframe.'</div></div>';
      }
    }

    // File path / lokal
    $isAbs = preg_match('~^https?://~i',$u);
    $web   = $isAbs || strpos($u,'/')===0 ? $u : '/agromart/'.$u;
    $parts = explode('/', $web); foreach($parts as &$seg){ if($seg!=='' && $seg!=='.') $seg = rawurlencode($seg); }
    $src   = (strpos($web,'/')===0 || $isAbs) ? implode('/',$parts) : '/'.implode('/',$parts);
    $ext   = strtolower(pathinfo($web,PATHINFO_EXTENSION));
    $mime  = $mimeMap[$ext] ?? 'video/mp4';

    if (!$isAbs) {
      $docroot = rtrim($_SERVER['DOCUMENT_ROOT'],'/\\');
      $norm = str_replace(['/','\\'], DIRECTORY_SEPARATOR, $web);
      if ($norm[0]===DIRECTORY_SEPARATOR) $norm = ltrim($norm, DIRECTORY_SEPARATOR);
      $fs = $docroot . DIRECTORY_SEPARATOR . $norm;
      if (!file_exists($fs)) {
        $slug = fn($s)=>preg_replace('/[^a-z0-9]+/','', strtolower($s));
        $scan = function(array $dirs, string $want, array $exts) use($slug){
          foreach($dirs as $dir){
            if(!is_dir($dir)) continue;
            if($dh=opendir($dir)){ while(false!==($f=readdir($dh))){
              if($f==='.'||$f==='..') continue;
              $e = strtolower(pathinfo($f,PATHINFO_EXTENSION));
              if($e && !in_array($e,$exts,true)) continue;
              if(strpos($slug(pathinfo($f,PATHINFO_FILENAME)),$want)!==false || strpos($want,$slug(pathinfo($f,PATHINFO_FILENAME)))!==false){
                closedir($dh); return rtrim($dir,'/\\').DIRECTORY_SEPARATOR.$f;
              }
            } closedir($dh); }
          } return null;
        };
        $dirs = [
          dirname($fs),
          $docroot.DIRECTORY_SEPARATOR.'agromart'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'resellers'.DIRECTORY_SEPARATOR.'videos',
          $docroot.DIRECTORY_SEPARATOR.'agromart'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'resellers'.DIRECTORY_SEPARATOR.'video',
        ];
        $found = $scan($dirs, $slug(pathinfo($fs,PATHINFO_FILENAME)), $allowed);
        if ($found && file_exists($found)) {
          $rel = str_replace('\\','/',$found);
          $rel = str_replace($docroot,'',$rel);
          if ($rel==='' || $rel[0] !== '/') $rel = '/'.$rel;
          $src = $rel;
          $extReal = strtolower(pathinfo($found,PATHINFO_EXTENSION));
          $mime = $mimeMap[$extReal] ?? $mime;
        }
      }
    }

    $video = '<video controls preload="metadata" playsinline>'.
               '<source src="'.htmlspecialchars($src).'" type="'.$mime.'">'.
               'Browser kamu tidak mendukung video HTML5.'.
             '</video>';
    $open = '<div class="video-open"><a href="'.htmlspecialchars($src).'" target="_blank" rel="noopener">🔗 Buka video</a> (jika pemutar tidak mendukung format ini)</div>';
    return '<div class="video-box"><div class="video-ratio">'.$video.'</div></div>'.$open;
  }
}

/* ===== Ambil data produk ===== */
$id = (int)($_GET['id'] ?? 0);
$st = mysqli_prepare($conn, "SELECT p.*, k.nama_kategori, r.nama_toko, r.id AS rid
                             FROM produk p
                             LEFT JOIN kategori k ON k.id=p.kategori_id
                             JOIN reseller r ON r.id=p.reseller_id
                             WHERE p.id=? AND p.status='aktif' LIMIT 1");
mysqli_stmt_bind_param($st,'i',$id);
mysqli_stmt_execute($st);
$res = mysqli_stmt_get_result($st);
$p = $res ? mysqli_fetch_assoc($res) : null;
if (!$p){ echo '<div class="card p16">Produk tidak ditemukan.</div>'; include __DIR__.'/includes/footer.php'; exit; }

/* Reseller (+ alamat) */
$rs = mysqli_prepare($conn,"SELECT * FROM reseller WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($rs,'i',$p['rid']);
mysqli_stmt_execute($rs);
$rR = mysqli_stmt_get_result($rs);
$R = $rR? mysqli_fetch_assoc($rR):null;

$alamat_parts=[];
foreach(['alamat','address','alamat_toko'] as $f) if(!empty($R[$f]??null)) $alamat_parts[]=$R[$f];
foreach(['kecamatan','district'] as $f)        if(!empty($R[$f]??null)) $alamat_parts[]=$R[$f];
foreach(['kabupaten','kota','city'] as $f)     if(!empty($R[$f]??null)) $alamat_parts[]=$R[$f];
foreach(['provinsi','province'] as $f)         if(!empty($R[$f]??null)) $alamat_parts[]=$R[$f];
foreach(['kode_pos','postal_code'] as $f)      if(!empty($R[$f]??null)) $alamat_parts[]=$R[$f];
$toko_alamat = $alamat_parts ? implode(', ', $alamat_parts) : 'Alamat belum diisi';

/* Rating produk */
$avg=0.0; $total=0;
$ag = mysqli_prepare($conn,"SELECT ROUND(AVG(rating),1) avg_rating, COUNT(*) total FROM reviews WHERE produk_id=?");
mysqli_stmt_bind_param($ag,'i',$id);
mysqli_stmt_execute($ag);
$agR = mysqli_stmt_get_result($ag);
if($agR && ($ar=mysqli_fetch_assoc($agR))){ $avg=(float)$ar['avg_rating']; $total=(int)$ar['total']; }
$dist=[5=>0,4=>0,3=>0,2=>0,1=>0];
$ds = mysqli_prepare($conn,"SELECT rating, COUNT(*) c FROM reviews WHERE produk_id=? GROUP BY rating");
mysqli_stmt_bind_param($ds,'i',$id);
mysqli_stmt_execute($ds);
$dsR = mysqli_stmt_get_result($ds);
if($dsR){ while($row=mysqli_fetch_assoc($dsR)){ $rt=(int)$row['rating']; if($rt>=1&&$rt<=5) $dist[$rt]=(int)$row['c']; } }

/* Rating toko */
$avgToko=0.0; $totToko=0;
$at = mysqli_prepare($conn,"SELECT ROUND(AVG(r.rating),1) avg_rating, COUNT(*) total
                            FROM reviews r JOIN produk p2 ON p2.id=r.produk_id
                            WHERE p2.reseller_id=?");
mysqli_stmt_bind_param($at,'i',$p['rid']);
mysqli_stmt_execute($at);
$atR = mysqli_stmt_get_result($at);
if($atR && ($tr=mysqli_fetch_assoc($atR))){ $avgToko=(float)$tr['avg_rating']; $totToko=(int)$tr['total']; }

/* Ulasan */
$ulasan=[];
$uq = mysqli_prepare($conn,"SELECT r.id, r.rating, r.komentar, r.created_at, u.nama, u.email
                            FROM reviews r JOIN users u ON u.id=r.user_id
                            WHERE r.produk_id=? ORDER BY r.created_at DESC LIMIT 20");
mysqli_stmt_bind_param($uq,'i',$id);
mysqli_stmt_execute($uq);
$uR = mysqli_stmt_get_result($uq);
if($uR){ while($r=mysqli_fetch_assoc($uR)){ $r['media']=[]; $ulasan[$r['id']]=$r; } }
if($ulasan){
  $mq = mysqli_prepare($conn,"SELECT m.review_id, m.media_type, m.media_path
                              FROM review_media m JOIN reviews r ON r.id=m.review_id
                              WHERE r.produk_id=?");
  mysqli_stmt_bind_param($mq,'i',$id);
  mysqli_stmt_execute($mq);
  $mR = mysqli_stmt_get_result($mq);
  if($mR){ while($m=mysqli_fetch_assoc($mR)){ if(isset($ulasan[$m['review_id']])) $ulasan[$m['review_id']]['media'][]=$m; } }
}

/* Video produk */
$videos=[];
$vidQ = mysqli_prepare($conn,"SELECT id,title,video_url FROM produk_videos WHERE produk_id=? ORDER BY created_at DESC LIMIT 6");
mysqli_stmt_bind_param($vidQ,'i',$id);
mysqli_stmt_execute($vidQ);
$vidR = mysqli_stmt_get_result($vidQ);
if($vidR){ while($v=mysqli_fetch_assoc($vidR)){ $videos[]=$v; } }

/* Tampilan */
$imgProduk = buildFotoUrl($p) ?: 'https://placehold.co/800x500?text=AgroMart';
$logoToko  = ($R ? buildLogoUrl($R) : null) ?: 'https://placehold.co/96x96?text=Toko';
$tokoNama  = htmlspecialchars($p['nama_toko'] ?? ($R['nama_toko'] ?? 'Toko'));
$kategori  = htmlspecialchars($p['nama_kategori'] ?? '-');
$deskripsi = nl2br(htmlspecialchars($p['deskripsi'] ?? '-'));
$stokUnit  = htmlspecialchars($p['satuan'] ?? 'pcs');
?>
<div class="card" style="display:grid;grid-template-columns:1fr;gap:18px;padding:16px">
  <img src="<?= htmlspecialchars($imgProduk) ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>"
       style="display:block;width:100%;height:auto;max-height:480px;object-fit:contain;background:#f3f4f6;border-radius:10px">
  <div>
    <h1 style="margin:0"><?= htmlspecialchars($p['nama_produk']) ?></h1>
    <div class="muted" style="margin-top:4px"><?= $kategori ?> • <?= $tokoNama ?></div>

    <div style="margin:14px 0;padding:12px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;display:flex;gap:12px;align-items:center">
      <img src="<?= htmlspecialchars($logoToko) ?>" alt="Logo Toko" style="width:48px;height:48px;border-radius:999px;object-fit:cover;background:#eef2f7">
      <div style="flex:1">
        <div style="font-weight:700;line-height:1"><?= $tokoNama ?></div>
        <div class="muted" style="font-size:13px;margin-top:4px">📍 <?= htmlspecialchars($toko_alamat) ?></div>
        <div class="muted" style="font-size:13px;margin-top:4px">⭐ <?= number_format($avgToko,1) ?> (<?= $totToko ?> penilaian toko)</div>
      </div>
    </div>

    <div class="price" style="margin:10px 0"><?= rupiah($p['harga']) ?></div>
    <div class="muted">Stok: <?= (int)$p['stok'] ?> <?= $stokUnit ?></div>

    <div style="margin-top:16px;padding:12px;border:1px solid #e5e7eb;border-radius:12px;background:#fff">
      <div class="row" style="justify-content:space-between;align-items:center">
        <div><b>Penilaian Produk</b></div>
        <div class="muted">⭐ <?= number_format($avg,1) ?> / 5 (<?= $total ?> ulasan)</div>
      </div>
      <div style="display:grid;grid-template-columns:1fr;gap:6px;margin-top:8px">
        <?php for($s=5;$s>=1;$s--): $cnt=$dist[$s]??0; $pct=$total?round($cnt*100/$total):0; ?>
          <div class="row" style="gap:10px;align-items:center">
            <div style="width:72px" class="muted"><?= $s ?> ★</div>
            <div style="flex:1;height:8px;background:#eef2f7;border-radius:999px;overflow:hidden">
              <div style="width:<?= $pct ?>%;height:100%;background:#2563eb"></div>
            </div>
            <div class="muted" style="width:46px;text-align:right"><?= $cnt ?></div>
          </div>
        <?php endfor; ?>
      </div>
    </div>

    <div style="margin-top:16px">
      <div style="font-weight:700;margin-bottom:6px">Deskripsi</div>
      <div class="muted" style="white-space:pre-wrap;color:#111827"><?= $deskripsi ?></div>
    </div>

    <form action="/agromart/user/keranjang/add.php" method="post" class="row" style="margin-top:14px">
      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
      <input type="number" name="qty" min="1" value="1" class="btn-outline" style="width:120px;padding:10px 12px;border-radius:10px">
      <button class="btn" type="submit">+ Keranjang</button>
    </form>
  </div>
</div>

<div class="card p16" style="margin-top:14px">
  <div style="font-weight:700;margin-bottom:8px">Video Terkait Produk</div>
  <?php if(!$videos): ?>
    <div class="muted">Belum ada video terkait produk.</div>
  <?php else: ?>
    <div class="grid" style="grid-template-columns:repeat(1,minmax(0,1fr));gap:12px">
      <?php foreach($videos as $v): ?>
        <div class="card p16">
          <div class="muted" style="margin-bottom:8px"><?= htmlspecialchars($v['title'] ?? 'Video') ?></div>
          <?= embedVideoHtml($v['video_url']) ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="card p16" style="margin-top:14px">
  <div style="font-weight:700;margin-bottom:8px">Ulasan Pembeli</div>
  <?php if(!$ulasan): ?>
    <div class="muted">Belum ada ulasan.</div>
  <?php else: foreach($ulasan as $r): ?>
    <div class="card p16" style="margin-bottom:10px">
      <div class="row" style="justify-content:space-between">
        <div style="font-weight:600"><?= htmlspecialchars($r['nama'] ?: $r['email']) ?></div>
        <div class="muted"><?= htmlspecialchars(date('d M Y', strtotime($r['created_at'] ?? 'now'))) ?></div>
      </div>
      <div style="margin:6px 0">⭐ <?= stars_html((float)$r['rating']) ?> (<?= (int)$r['rating'] ?>/5)</div>
      <div class="muted" style="white-space:pre-wrap;color:#111827"><?= nl2br(htmlspecialchars($r['komentar'] ?? '')) ?></div>
      <?php if(!empty($r['media'])): ?>
        <div class="row" style="flex-wrap:wrap;gap:8px;margin-top:8px">
          <?php foreach($r['media'] as $m):
            $src = (preg_match('~^https?://~i',$m['media_path']) || strpos($m['media_path'],'/')===0) ? $m['media_path'] : '/agromart/'.$m['media_path']; ?>
            <?php if($m['media_type']==='image'): ?>
              <a href="<?= htmlspecialchars($src) ?>" target="_blank">
                <img src="<?= htmlspecialchars($src) ?>" style="width:100px;height:100px;object-fit:cover;border-radius:10px;border:1px solid #e5e7eb;background:#f3f4f6">
              </a>
            <?php else: ?>
              <video controls preload="metadata" style="width:160px;height:100px;border-radius:10px;background:#000"><source src="<?= htmlspecialchars($src) ?>"></video>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
