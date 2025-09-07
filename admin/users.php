<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/auth_admin.php';
require_once __DIR__ . '/../config/db.php';

// Hanya admin & super_admin yang boleh masuk area admin
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['admin','super_admin'])) {
  header("Location: ../login.php"); exit;
}

$currentRole = strtolower($_SESSION['role']);

// Ambil data
$result = mysqli_query($conn, "SELECT id, name, email, role FROM users ORDER BY id ASC");
if (!$result) { die("Query error: " . mysqli_error($conn)); }

// Flash password dari reset (kalau ada)
$resetMessage = '';
if (isset($_GET['pesan']) && $_GET['pesan']==='reset' && isset($_SESSION['flash_password'])) {
  $plain = $_SESSION['flash_password'];
  unset($_SESSION['flash_password']);
  $resetMessage = "Password baru: <b id='newPass'>".htmlspecialchars($plain)."</b>";
}

// Notifikasi umum (tambah/edit/hapus)
$info = isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : '';
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<h2>Manajemen Users</h2>

<?php if ($resetMessage): ?>
  <div class="alert success">
    ✅ <?= $resetMessage ?>
    <button class="btn btn-success" style="margin-left:8px;" onclick="copyPass()">Copy</button>
  </div>
  <script>
    function copyPass(){
      const t = document.getElementById('newPass')?.innerText || '';
      if (!t) return;
      navigator.clipboard.writeText(t).then(()=>alert('Password baru tersalin ✅'));
    }
  </script>
<?php endif; ?>

<?php if ($info): ?>
  <div class="alert success">✅ <?= $info ?></div>
<?php endif; ?>

<a href="users/tambah.php" class="btn btn-success" style="margin-bottom:12px;">+ Tambah User</a>

<table class="table">
  <thead>
    <tr>
      <th style="width:60px;">No</th>
      <th>Nama</th>
      <th>Email</th>
      <th style="width:140px;">Role</th>
      <th style="width:360px;">Aksi</th>
    </tr>
  </thead>
  <tbody>
  <?php $no=1; while($row = mysqli_fetch_assoc($result)): 
      $uid   = (int)$row['id'];
      $name  = htmlspecialchars($row['name'] ?? '');
      $email = htmlspecialchars($row['email'] ?? '');
      $role  = strtolower($row['role'] ?? 'user');

      if     ($role==='super_admin') $badge = '<span class="badge bg-warning">Super Admin</span>';
      elseif ($role==='admin')       $badge = '<span class="badge bg-primary">Admin</span>';
      else                           $badge = '<span class="badge bg-success">User</span>';
  ?>
    <tr>
      <td><?= $no++; ?></td>
      <td><?= $name; ?></td>
      <td><?= $email; ?></td>
      <td><?= $badge; ?></td>
      <td>
        <?php
          // Aturan aksi
          if ($role === 'super_admin') {
            echo '<span class="muted">🔒 Super Admin (proteksi)</span>';
          } elseif ($role === 'admin' && $currentRole !== 'super_admin') {
            echo '<span class="muted">🔒 Hanya Super Admin yang bisa kelola Admin</span>';
          } else {
            echo '<a class="btn btn-warning" href="users/edit.php?id='.$uid.'">Edit</a> ';
            echo '<a class="btn btn-danger" href="users/hapus.php?id='.$uid.'" onclick="return confirm(\'Yakin hapus user ini?\')">Hapus</a> ';
            echo '<a class="btn btn-secondary" href="users/reset_password.php?id='.$uid.'">Reset</a>';
          }
        ?>
      </td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>

<?php include 'includes/footer.php'; ?>
