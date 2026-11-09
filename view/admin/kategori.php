<?php
// =====================================================================
// kategori.php
// Tampilan daftar kategori sarpas beserta jumlah laporan.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../../config/database.php';
// Pastikan yang membuka halaman ini adalah admin yang login
require_role('admin');
// Data admin yang login (dipakai untuk nama di sidebar)
$user = current_user();
// Ambil kategori beserta jumlah laporan tiap kategori
$r = mysqli_query($conn, "SELECT k.id_kategori,k.nama_kategori,k.ket_kategori,COUNT(a.id_aspirasi) jumlah FROM kategori k LEFT JOIN aspirasi a ON a.id_kategori=k.id_kategori GROUP BY k.id_kategori,k.nama_kategori,k.ket_kategori ORDER BY k.nama_kategori ASC");
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kategori Sarpras</title>
    <link rel="stylesheet" href="<?= e($base) ?>/assets/admin.css">
    <script src="<?= e($base) ?>/assets/ui.js"></script>
</head>

<body>
    <?php // Checkbox untuk membuka dan menutup sidebar di layar kecil ?>
    <input type="checkbox" id="menuToggle" class="menu-toggle">
    <?php // Sidebar menu navigasi admin ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">Pengaduan Sarpras</div>
        <ul>
            <li><a href="<?= e(url('admin/dashboard')) ?>" class="">Dashboard</a></li>
            <li><a href="<?= e(url('admin/aspirasi')) ?>" class="">Data Aspirasi</a></li>
            <li><a href="<?= e(url('admin/form_aspirasi')) ?>" class="">form aspirasi</a></li>
            <li><a href="<?= e(url('admin/histori')) ?>" class="">Histori Laporan</a></li>
            <li><a href="<?= e(url('admin/kategori')) ?>" class="active">Kategori Sarpras</a></li>
            <li><a href="<?= e(url('admin/siswa')) ?>">Kelola Siswa</a></li>
            <li><a href="<?= e(url('auth/logout')) ?>">Keluar</a></li>
        </ul>
        <?php // Nama admin yang login di bagian bawah sidebar ?>
        <div class="admin-user"><strong><?= e($user["nama"] ?? "Admin") ?></strong>Administrator</div>
    </aside>
    <label for="menuToggle" class="sidebar-overlay"></label>
    <div class="main-content">
        <?php // Bagian atas halaman: judul halaman ?>
        <header class="topbar"><label for="menuToggle" class="menu-icon">&#9776;</label>
            <h1>Kategori Sarpras</h1>
        </header>
        <main class="content-area">
            <?php // Kotak tempat tabel daftar kategori ?>
            <section class="panel">
                <h2 style="margin-top:0">Daftar Kategori & Jumlah Aspirasi</h2>
                <div style="overflow:auto">
                    <?php // Tabel daftar kategori ?>
                    <table class="table">
                        <tr>
                            <th>ID</th>
                            <th>Kategori</th>
                            <th>Jumlah Aspirasi</th>
                            <th>Aksi</th>
                        </tr>
                        <?php // Tampilkan satu baris per kategori ?>
                        <?php while ($k = mysqli_fetch_assoc($r)): ?>
                            <tr>
                                <td><?= $k['id_kategori'] ?></td>
                                <td><?= e($k['nama_kategori']) ?></td>
                                <td><?= $k['jumlah'] ?></td>
                                <?php // Tombol ini membuka daftar aspirasi yang difilter sesuai nama kategori ?>
                                <td><a class="btn small" href="<?= e(url('admin/aspirasi')) ?>?q=<?= urlencode($k['nama_kategori']) ?>">Lihat Aspirasi</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>

</html>