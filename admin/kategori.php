<?php
include('../config/db.php');
include('includes/header.php');
include('includes/navbar.php');
include('includes/sidebar.php');

// Ambil semua kategori
$result = mysqli_query($conn, "SELECT * FROM kategori ORDER BY id DESC");
?>

<div class="container p-4">
    <h2 class="mb-4">Manajemen Kategori</h2>
    <a href="kategori/kategori_tambah.php" class="btn btn-success mb-3">+ Tambah Kategori</a>
    
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th style="width: 50px;">No</th>
                <th>Nama Kategori</th>
                <th style="width: 250px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                        <td>".$no++."</td>
                        <td>".$row['nama_kategori']."</td>
                        <td>
                            <a href='kategori/kategori_detail.php?id=".$row['id']."' class='btn btn-info btn-sm'>👁️ Lihat Produk</a>
                            <a href='kategori/kategori_edit.php?id=".$row['id']."' class='btn btn-warning btn-sm'>✏️ Edit</a>
                            <a href='kategori/kategori_hapus.php?id=".$row['id']."' 
                               onclick=\"return confirm('Yakin ingin hapus kategori ini?')\" 
                               class='btn btn-danger btn-sm'>🗑️ Hapus</a>
                        </td>
                      </tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php
include('includes/footer.php');
?>
