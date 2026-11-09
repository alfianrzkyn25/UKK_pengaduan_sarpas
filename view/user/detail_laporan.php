<?php
// =====================================================================
// detail_laporan.php
// Tampilan detail laporan siswa beserta riwayat status dan lampiran.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../../config/database.php';
// Pastikan yang membuka halaman ini adalah siswa yang login
require_role('siswa');
$user = current_user();
$id = (int)($_GET['id'] ?? 0);
$saved = isset($_GET['saved']);

// Ambil detail laporan, hanya kalau milik siswa yang login
$st = mysqli_prepare($conn, 'SELECT a.*,s.nama,s.kelas,s.foto,k.nama_kategori FROM aspirasi a JOIN siswa s ON s.nis=a.nis LEFT JOIN kategori k ON k.id_kategori=a.id_kategori WHERE a.id_aspirasi=? AND a.nis=?');
mysqli_stmt_bind_param($st, 'is', $id, $user['nis']);
mysqli_stmt_execute($st);
// Simpan hasil; kosong kalau laporan tidak ada atau bukan miliknya
$a = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

$hist = [];
if ($a) {
    // Ambil riwayat perubahan status laporan ini
    $st = mysqli_prepare($conn, 'SELECT status_lama,status_baru,catatan,diubah_oleh,waktu_ubah FROM histori WHERE id_aspirasi=? ORDER BY waktu_ubah ASC,id_histori ASC');
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $rr = mysqli_stmt_get_result($st);
    // Kumpulkan riwayat ke array $hist
    while ($h = mysqli_fetch_assoc($rr)) $hist[] = $h;
    mysqli_stmt_close($st);
}

$fotoPengaduan = [];
$fotoFeedback = [];
if ($a) {
    // Ambil lampiran (foto) laporan ini
    $st = mysqli_prepare($conn, 'SELECT url_file,nama_file FROM lampiran WHERE id_aspirasi=? ORDER BY id_lampiran ASC');
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $lr = mysqli_stmt_get_result($st);
    // Pisahkan foto pengaduan dari foto bukti perbaikan
    while ($lf = mysqli_fetch_assoc($lr)) {
        if (strpos(basename($lf['url_file']), 'feedback-') === 0) {
            $fotoFeedback[] = $lf;
        } else {
            $fotoPengaduan[] = $lf;
        }
    }
    mysqli_stmt_close($st);
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Detail Laporan</title>
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
            <li><a href="<?= e(url('user/dashboard')) ?>" class="">Beranda</a></li>
            <li><a href="<?= e(url('user/buat_pengaduan')) ?>" class="">Buat Pengaduan</a></li>
            <li><a href="<?= e(url('user/laporan_saya')) ?>" class="active">Laporan Saya</a></li>
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
            <h1>Detail Laporan</h1>
        </header>
        <main class="content-area">
            <?php // Notifikasi setelah laporan berhasil dikirim ?>
            <?php if ($saved): ?><div class="alert success">Pengaduan berhasil dikirim.</div><?php endif; ?>
            <?php // Kalau laporan tidak ditemukan, tampilkan pesan dan tombol kembali ?>
            <?php if (!$a): ?><div class="alert error">Laporan tidak ditemukan.</div><a class="btn" href="<?= e(url('user/laporan_saya')) ?>">Kembali</a><?php else: ?>
                <?php // Judul laporan dan statusnya ?>
                <div class="heading">
                    <div>
                        <p class="eyebrow">Laporan #<?= (int)$a['id_aspirasi'] ?></p>
                        <h1><?= e($a['nama_kategori']) ?></h1>
                        <p class="muted"><?= e($a['tanggal']) ?></p>
                    </div><span class="badge <?= strtolower($a['status']) ?>"><?= e($a['status']) ?></span>
                </div>
                <?php // Isi detail laporan (lokasi, kategori, keterangan) ?>
                <div class="detail">
                    <section class="panel">
                        <h2>Informasi Pengaduan</h2>
                        <p><b>Pelapor</b><span class="reporter-inline"><?= avatar($a['foto'] ?? null, $a['nama'], 'sm') ?><span><?= e($a['nama']) ?> · <?= e($a['kelas']) ?></span></span></p>
                        <p><b>Lokasi</b><?= e($a['lokasi']) ?></p>
                        <p><b>Keterangan</b><?= e($a['keterangan']) ?></p>
                        <?php // Tampilkan foto pengaduan dari siswa ?>
                        <?php if ($fotoPengaduan): ?><div style="margin-top:10px"><b>Foto Pengaduan</b>
                                <div class="lampiran-grid"><?php foreach ($fotoPengaduan as $lf): ?><a href="<?= e($base) ?>/<?= e($lf['url_file']) ?>" target="_blank"><img src="<?= e($base) ?>/<?= e($lf['url_file']) ?>" alt="<?= e($lf['nama_file']) ?>"></a><?php endforeach; ?></div>
                            </div><?php endif; ?>
                    </section>
                    <section class="panel">
                        <h2>Perkembangan</h2>
                        <?php // Riwayat perubahan status dalam bentuk timeline ?>
                        <div class="timeline"><?php foreach ($hist as $h): ?><div class="timeline-item"><b><?= e($h['status_baru']) ?></b><small><?= e($h['waktu_ubah']) ?> · <?= e($h['diubah_oleh']) ?></small>
                                    <div><?= e($h['catatan'] ?: '-') ?></div>
                                </div><?php endforeach; ?></div>
                    </section>
                </div>
                <section class="panel">
                    <h2>Feedback dari Admin</h2>
                    <?php // Tampilkan tanggapan admin; kalau belum ada, tulis "belum ada tanggapan" ?>
                    <?php if ($a['feedback']): ?>
                        <div class="alert <?= strtolower($a['status']) === 'selesai' ? 'success' : 'info' ?>" style="margin-bottom:<?= $fotoFeedback ? '14px' : '0' ?>"><?= e($a['feedback']) ?></div>
                    <?php else: ?>
                        <p class="muted" style="margin:0 0 <?= $fotoFeedback ? '14px' : '0' ?>">Belum ada feedback dari admin.</p>
                    <?php endif; ?>
                    <?php // Tampilkan foto bukti perbaikan dari admin ?>
                    <?php if ($fotoFeedback): ?><div><b>Foto Bukti Perbaikan</b>
                            <div class="lampiran-grid"><?php foreach ($fotoFeedback as $lf): ?><a href="<?= e($base) ?>/<?= e($lf['url_file']) ?>" target="_blank"><img src="<?= e($base) ?>/<?= e($lf['url_file']) ?>" alt="<?= e($lf['nama_file']) ?>"></a><?php endforeach; ?></div>
                        </div><?php endif; ?>
                </section>
                <a class="btn secondary" href="<?= e(url('user/laporan_saya')) ?>">← Kembali</a>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>