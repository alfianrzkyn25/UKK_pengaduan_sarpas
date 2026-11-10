<?php
// =====================================================================
// aspirasi.php
// Tampilan daftar semua laporan untuk admin, lengkap dengan pencarian.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../../config/database.php';
// Pastikan yang membuka halaman ini adalah admin yang login
require_role('admin');
$user = current_user();
$q = trim($_GET['q'] ?? '');
$rows = [];
// Kalau ada kata kunci pencarian, pakai query dengan LIKE
if ($q !== '') {
    $like = '%' . $q . '%';
    $st = mysqli_prepare($conn, "SELECT a.id_aspirasi,a.nis,s.nama,s.foto,k.nama_kategori,a.lokasi,a.keterangan,a.feedback,a.status,a.tanggal FROM aspirasi a LEFT JOIN siswa s ON s.nis=a.nis LEFT JOIN kategori k ON k.id_kategori=a.id_kategori WHERE a.nis LIKE ? OR s.nama LIKE ? OR k.nama_kategori LIKE ? OR a.lokasi LIKE ? OR a.keterangan LIKE ? OR a.status LIKE ? ORDER BY a.tanggal DESC,a.id_aspirasi DESC");
    mysqli_stmt_bind_param($st, 'ssssss', $like, $like, $like, $like, $like, $like);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
} else {
    // Kalau tidak ada pencarian, tampilkan semua laporan
    $res = mysqli_query($conn, "SELECT a.id_aspirasi,a.nis,s.nama,s.foto,k.nama_kategori,a.lokasi,a.keterangan,a.feedback,a.status,a.tanggal FROM aspirasi a LEFT JOIN siswa s ON s.nis=a.nis LEFT JOIN kategori k ON k.id_kategori=a.id_kategori ORDER BY a.tanggal DESC,a.id_aspirasi DESC");
}
// Kumpulkan hasil ke array $rows
while ($x = mysqli_fetch_assoc($res)) $rows[] = $x;
if (isset($st)) mysqli_stmt_close($st);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Data Aspirasi</title>
    <link rel="stylesheet" href="<?= e($base) ?>/assets/admin.css">
    <link rel="stylesheet" href="<?= e($base) ?>/assets/profil.css">
    <script src="<?= e($base) ?>/assets/ui.js"></script>
</head>

<body>
    <input type="checkbox" id="menuToggle" class="menu-toggle">
    <?php // Sidebar menu navigasi admin ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">Pengaduan Sarpras</div>
        <ul>
            <li><a href="<?= e(url('admin/dashboard')) ?>" class="">Dashboard</a></li>
            <li><a href="<?= e(url('admin/aspirasi')) ?>" class="active">Data Aspirasi</a></li>
            <li><a href="<?= e(url('admin/histori')) ?>" class="">Histori Laporan</a></li>
            <li><a href="<?= e(url('admin/kategori')) ?>" class="">Kategori Sarpras</a></li>
            <li><a href="<?= e(url('admin/siswa')) ?>">Kelola Siswa</a></li>
            <li><a href="<?= e(url('auth/logout')) ?>">Keluar</a></li>
        </ul>
        <div class="admin-user"><strong><?= e($user["nama"] ?? "Admin") ?></strong>Administrator</div>
    </aside>
    <label for="menuToggle" class="sidebar-overlay"></label>
    <div class="main-content">
        <?php // Bagian atas halaman ?>
        <header class="topbar"><label for="menuToggle" class="menu-icon">&#9776;</label>
            <h1>Data Aspirasi</h1>
        </header>
        <main class="content-area">
            <?php // Kotak daftar laporan dengan kolom pencarian ?>
            <section class="panel">
                <div class="toolbar">
                    <?php // Form pencarian (dikirim dengan GET ?q=...) ?>
                    <form class="search" method="get"><input name="q" value="<?= e($q) ?>" placeholder="Cari NIS, nama, lokasi, status..."><button class="btn" type="submit">Cari</button></form>
                    <a class="btn" href="<?= e(url('admin/form_aspirasi')) ?>">+ Tambah Aspirasi</a>
                </div>
                <h2 style="margin-top:0">Daftar Aspirasi</h2>
                <div style="overflow:auto">
                    <?php // Tabel daftar laporan ?>
                    <table class="table">
                        <tr>
                            <th>ID</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Laporan</th>
                            <th>Status</th>
                            <th>Feedback Admin</th>
                            <th>Aksi</th>
                        </tr>
                        <?php // Tampilkan satu baris per laporan ?>
                        <?php foreach ($rows as $a): ?>
                            <tr>
                                <td>#<?= $a['id_aspirasi'] ?></td>
                                <td><?= e($a['nis']) ?></td>
                                <td>
                                    <div class="reporter-inline"><?= avatar($a['foto'] ?? null, $a['nama'] ?? '', 'sm') ?><span class="reporter-name"><?= e($a['nama']) ?></span></div>
                                </td>
                                <td><?= e($a['nama_kategori']) ?></td>
                                <td><?= e($a['lokasi']) ?></td>
                                <td><?= e($a['keterangan']) ?></td>
                                <td><span class="badge <?= strtolower($a['status']) ?>"><?= e($a['status']) ?></span></td>
                                <td><?= e($a['feedback'] ?: '-') ?></td>
                                <td><a class="btn small" href="<?= e(url('admin/detail_aspirasi')) ?>?id=<?= $a['id_aspirasi'] ?>">Beri Feedback</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>

</html>