<nav class="navbar">
  <div class="navbar-left">
    🌾 <span class="brand">AgroMart Admin</span>
  </div>
  <div class="navbar-right">
    <span class="user-info">
      Halo, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?> 
      (<?= htmlspecialchars($_SESSION['role'] ?? '-') ?>)
    </span>
    <a class="logout-link" href="<?= $BASE ?>/logout.php">Logout</a>
  </div>
</nav>
