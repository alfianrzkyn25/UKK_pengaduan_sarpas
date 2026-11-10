<?php
// =====================================================================
// dashboard.php
// Tampilan dashboard admin: kartu statistik dan tabel laporan terbaru.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../../config/database.php';
// Pastikan yang membuka halaman ini adalah admin yang login
require_role('admin');
// Data admin yang login
$user = current_user();
// Wadah statistik dashboard
$stats = ['total' => 0, 'menunggu' => 0, 'proses' => 0, 'selesai' => 0, 'ditolak' => 0];
// Hitung jumlah laporan per status
$r = mysqli_query($conn, "SELECT status,COUNT(*) jumlah FROM aspirasi GROUP BY status");
while ($x = mysqli_fetch_assoc($r)) {
    // Jumlahkan total dari semua status
    $stats['total'] += (int)$x['jumlah'];
    $key = strtolower($x['status']);
    if (isset($stats[$key])) $stats[$key] = (int)$x['jumlah'];
}
// Ambil 5 laporan terbaru untuk tabel di bawah
$r = mysqli_query($conn, "SELECT a.id_aspirasi,a.nis,s.nama,k.nama_kategori,a.lokasi,a.keterangan,a.status,a.tanggal FROM aspirasi a LEFT JOIN siswa s ON s.nis=a.nis LEFT JOIN kategori k ON k.id_kategori=a.id_kategori ORDER BY a.tanggal DESC,a.id_aspirasi DESC LIMIT 5");
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard Utama</title>
    <link rel="stylesheet" href="<?= e($base) ?>/assets/admin.css">
    <script src="<?= e($base) ?>/assets/ui.js"></script>
</head>

<body>
    <input type="checkbox" id="menuToggle" class="menu-toggle">
    <?php // Sidebar menu navigasi admin ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">Pengaduan Sarpras</div>
        <ul>
            <li><a href="<?= e(url('admin/dashboard')) ?>" class="active">Dashboard</a></li>
            <li><a href="<?= e(url('admin/aspirasi')) ?>" class="">Data Aspirasi</a></li>
            <li><a href="<?= e(url('admin/histori')) ?>" class="">Histori Laporan</a></li>
            <li><a href="<?= e(url('admin/kategori')) ?>" class="">Kategori Sarpras</a></li>
            <li><a href="<?= e(url('admin/siswa')) ?>">Kelola Siswa</a></li>
            <li><a href="<?= e(url('auth/logout')) ?>">Keluar</a></li>
        </ul>
        <div class="admin-user"><strong><?= e($user["nama"] ?? "Admin") ?></strong>Administrator</div>
    </aside>
    <label for="menuToggle" class="sidebar-overlay"></label>
    <div class="main-content">
        <header class="topbar"><label for="menuToggle" class="menu-icon">&#9776;</label>
            <h1>Dashboard Utama</h1>
        </header>
        <main class="content-area">
            <?php // Judul halaman dan tombol menuju kelola aspirasi ?>
            <div class="heading">
                <div>
                    <h1>Pengaduan sarana &amp; prasarana</h1>
                    <p class="muted" data-today>Pantau status laporan yang masuk.</p>
                </div>
                <a class="btn" href="<?= e(url('admin/aspirasi')) ?>">Kelola aspirasi</a>
            </div>
            <?php // Kartu statistik: total dan jumlah per status ?>
            <div class="grid">
                <div class="card stat stat-total"><span>Total Pengaduan</span><strong data-count="<?= $stats['total'] ?>"><?= $stats['total'] ?></strong></div>
                <div class="card stat stat-menunggu"><span>Menunggu</span><strong data-count="<?= $stats['menunggu'] ?>"><?= $stats['menunggu'] ?></strong></div>
                <div class="card stat stat-proses"><span>Proses</span><strong data-count="<?= $stats['proses'] ?>"><?= $stats['proses'] ?></strong></div>
                <div class="card stat stat-selesai"><span>Selesai</span><strong data-count="<?= $stats['selesai'] ?>"><?= $stats['selesai'] ?></strong></div>
                <div class="card stat stat-ditolak"><span>Ditolak</span><strong data-count="<?= $stats['ditolak'] ?>"><?= $stats['ditolak'] ?></strong></div>
            </div>
            <?php // Panel grafik komposisi status (digambar oleh ui.js) ?>
            <section class="panel" data-status-chart>
                <div class="panel-head">
                    <h2>Komposisi status</h2>
                </div>
                <div class="chart-body">
                    <div class="donut" data-donut></div>
                    <?php // Keterangan warna grafik, berisi jumlah tiap status ?>
                    <ul class="legend">
                        <li data-key="menunggu" data-value="<?= $stats['menunggu'] ?>"><span class="dot"></span><span class="name">Menunggu</span><span class="num"><?= $stats['menunggu'] ?></span><span class="pct"></span></li>
                        <li data-key="proses" data-value="<?= $stats['proses'] ?>"><span class="dot"></span><span class="name">Proses</span><span class="num"><?= $stats['proses'] ?></span><span class="pct"></span></li>
                        <li data-key="selesai" data-value="<?= $stats['selesai'] ?>"><span class="dot"></span><span class="name">Selesai</span><span class="num"><?= $stats['selesai'] ?></span><span class="pct"></span></li>
                        <li data-key="ditolak" data-value="<?= $stats['ditolak'] ?>"><span class="dot"></span><span class="name">Ditolak</span><span class="num"><?= $stats['ditolak'] ?></span><span class="pct"></span></li>
                    </ul>
                </div>
                <p class="chart-note" data-completion></p>
            </section>
            <section class="panel">
                <div class="panel-head">
                    <?php // Judul tabel 5 laporan terbaru ?>
                    <h2>5 Aspirasi Terbaru</h2><a href="<?= e(url('admin/aspirasi')) ?>">Kelola semua</a>
                </div>
                <div style="overflow:auto">
                    <table class="table">
                        <tr>
                            <th>ID</th>
                            <th>Siswa</th>
                            <th>Kategori</th>
                            <th>Lokasi</th>
                            <th>Laporan</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                        <?php // Tampilkan satu baris per laporan terbaru ?>
                        <?php while ($a = mysqli_fetch_assoc($r)): ?>
                            <tr>
                                <td>#<?= $a['id_aspirasi'] ?></td>
                                <td><?= e($a['nama']) ?></td>
                                <td><?= e($a['nama_kategori']) ?></td>
                                <td><?= e($a['lokasi']) ?></td>
                                <td><?= e($a['keterangan']) ?></td>
                                <td><span class="badge <?= strtolower($a['status']) ?>"><?= e($a['status']) ?></span></td>
                                <td><?= e(date('d-m-Y H:i', strtotime($a['tanggal']))) ?></td>
                                <?php // Tombol menuju halaman detail untuk memberi feedback ?>
                                <td><a class="btn small" href="<?= e(url('admin/detail_aspirasi')) ?>?id=<?= $a['id_aspirasi'] ?>">Beri Feedback</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>

</html>