<?php
// buat hash password baru
$hash = password_hash("admin123", PASSWORD_DEFAULT);
echo $hash;
?>
