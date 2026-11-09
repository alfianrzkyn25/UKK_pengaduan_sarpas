<?php
// =====================================================================
// dashboard.php
// Tampilan dashboard siswa: statistik laporan dan laporan terbaru.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../../config/database.php';
// Pastikan yang membuka halaman ini adalah siswa yang login
require_role('siswa');
$user = current_user();
$stats = ['total' => 0, 'menunggu' => 0, 'proses' => 0, 'selesai' => 0, 'ditolak' => 0];
// Hitung statistik laporan milik siswa ini per status
$st = mysqli_prepare($conn, 'SELECT status,COUNT(*) jumlah FROM aspirasi WHERE nis=? GROUP BY status');
mysqli_stmt_bind_param($st, 's', $user['nis']);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
// Masukkan hitungan ke wadah $stats
while ($x = mysqli_fetch_assoc($r)) {
    $stats['total'] += (int)$x['jumlah'];
    $k = strtolower($x['status']);
    if (isset($stats[$k])) $stats[$k] = (int)$x['jumlah'];
}
mysqli_stmt_close($st);
// Ambil 5 laporan terbaru milik siswa ini
$st = mysqli_prepare($conn, 'SELECT a.id_aspirasi,a.lokasi,a.keterangan,a.status,a.tanggal,k.nama_kategori FROM aspirasi a LEFT JOIN kategori k ON k.id_kategori=a.id_kategori WHERE a.nis=? ORDER BY a.tanggal DESC LIMIT 5');
mysqli_stmt_bind_param($st, 's', $user['nis']);
mysqli_stmt_execute($st);
$r = mysqli_stmt_get_result($st);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard Siswa</title>
    <link rel="stylesheet" href="<?= e($base) ?>/assets/admin.css">
    <link rel="stylesheet" href="<?= e($base) ?>/assets/profil.css">
    <script src="<?= e($base) ?>/assets/ui.js"></script>
</head>

<body>
    <input type="checkbox" id="menuToggle" class="menu-toggle">
    <?php // Sidebar menu navigasi siswa ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">Pengaduan Sarpras</div>
        <div class="sidebar-sub">Portal Siswa</div>
        <ul>
            <li><a href="<?= e(url('user/dashboard')) ?>" class="active">Beranda</a></li>
            <li><a href="<?= e(url('user/buat_pengaduan')) ?>" class="">Buat Pengaduan</a></li>
            <li><a href="<?= e(url('user/laporan_saya')) ?>" class="">Laporan Saya</a></li>
            <li><a href="<?= e(url('user/profil')) ?>">Profil Saya</a></li>
            <li><a href="<?= e(url('auth/logout')) ?>">Keluar</a></li>
        </ul>
        <div class="admin-user has-avatar">
            <a class="user-chip" href="<?= e(url('user/profil')) ?>" title="Lihat profil">
                <?= avatar($user['foto'] ?? null, $user['nama'] ?? 'Siswa', 'md') ?>
                <div class="who"><strong><?= e($user['nama'] ?? 'Siswa') ?></strong>Siswa · <?= e($user['kelas'] ?? '') ?></div>
            </a>
        </div>
    </aside>
    <label for="menuToggle" class="sidebar-overlay"></label>
    <div class="main-content">
        <?php // Bagian atas halaman ?>
        <header class="topbar"><label for="menuToggle" class="menu-icon">&#9776;</label>
            <h1>Dashboard Siswa</h1>
        </header>
        <main class="content-area">
            <?php // Judul halaman dan sapaan ?>
            <div class="heading">
                <div>
                    <p class="eyebrow">Portal Siswa</p>
                    <h1>Halo, <?= e($user['nama']) ?> 👋</h1>
                    <p class="muted">Kelola pengaduan sarana dan lihat perkembangan laporanmu.</p>
                </div><a class="btn" href="<?= e(url('user/buat_pengaduan')) ?>">+ Buat Pengaduan</a>
            </div>
            <?php // Kartu statistik laporan milik siswa ?>
            <div class="grid">
                <div class="card stat stat-total"><span>Total Laporan</span><strong data-count="<?= $stats['total'] ?>"><?= $stats['total'] ?></strong></div>
                <div class="card stat stat-menunggu"><span>Menunggu</span><strong data-count="<?= $stats['menunggu'] ?>"><?= $stats['menunggu'] ?></strong></div>
                <div class="card stat stat-proses"><span>Diproses</span><strong data-count="<?= $stats['proses'] ?>"><?= $stats['proses'] ?></strong></div>
                <div class="card stat stat-selesai"><span>Selesai</span><strong data-count="<?= $stats['selesai'] ?>"><?= $stats['selesai'] ?></strong></div>
                <div class="card stat stat-ditolak"><span>Ditolak</span><strong data-count="<?= $stats['ditolak'] ?>"><?= $stats['ditolak'] ?></strong></div>
            </div>
            <?php // Kotak daftar laporan terbaru ?>
            <section class="panel">
                <div class="panel-head">
                    <h2>Laporan Terbaru</h2><a href="<?= e(url('user/laporan_saya')) ?>">Lihat semua</a>
                </div>
                <?php // Daftar laporan terbaru; tiap item bisa diklik untuk melihat detail ?>
                <div class="list"><?php while ($a = mysqli_fetch_assoc($r)): ?><a class="item" href="<?= e(url('user/detail_laporan')) ?>?id=<?= (int)$a['id_aspirasi'] ?>">
                            <div><b>#<?= (int)$a['id_aspirasi'] ?> · <?= e($a['nama_kategori']) ?></b><span><?= e($a['lokasi']) ?></span><small><?= e($a['keterangan']) ?></small></div><span class="badge <?= strtolower($a['status']) ?>"><?= e($a['status']) ?></span>
                        </a><?php endwhile;
                                    mysqli_stmt_close($st); ?></div>
            </section>
        </main>
    </div>
</body>

</html>