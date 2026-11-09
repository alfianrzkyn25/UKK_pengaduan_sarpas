<?php
// =====================================================================
// buat_pengaduan.php
// Tampilan form buat pengaduan baru untuk siswa.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../../config/database.php';
// Pastikan yang membuka halaman ini adalah siswa yang login
require_role('siswa');
$user = current_user();
$error = '';

// Proses hanya kalau form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form: kategori, lokasi, dan keterangan
    $idk = (int)($_POST['id_kategori'] ?? 0);
    $lokasi = trim($_POST['lokasi'] ?? '');
    $ket = trim($_POST['keterangan'] ?? '');

    $allowedExt = ['jpg' => 1, 'jpeg' => 1, 'png' => 1, 'gif' => 1, 'webp' => 1];
    $maxSize = 5 * 1024 * 1024; // 5MB
    // Cek apakah siswa mengunggah foto
    $hasFile = !empty($_FILES['foto']['name']);
    // Ambil ekstensi foto dalam huruf kecil
    $fotoExt = $hasFile ? strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION)) : '';

    // Validasi: semua data wajib diisi
    if (!$idk || $lokasi === '' || $ket === '') {
        $error = 'Semua data wajib diisi.';
    } elseif ($hasFile && $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Gagal mengunggah foto. Silakan coba lagi.';
    } elseif ($hasFile && !isset($allowedExt[$fotoExt])) {
        $error = 'Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.';
    } elseif ($hasFile && $_FILES['foto']['size'] > $maxSize) {
        $error = 'Ukuran foto maksimal 5MB.';
    } elseif ($hasFile && !@getimagesize($_FILES['foto']['tmp_name'])) {
        $error = 'File yang diunggah bukan gambar yang valid.';
    } else {
        $admin = default_admin_id();
        // Pastikan ada admin penanggung jawab di database
        if (!$admin) {
            $error = 'Belum ada data admin di database.';
        } else {
            mysqli_begin_transaction($conn);
            // Simpan laporan baru dengan status Menunggu
            $st = mysqli_prepare($conn, "INSERT INTO aspirasi (nis,id_kategori,id_admin,lokasi,keterangan,status) VALUES (?,?,?,?,?,'Menunggu')");
            mysqli_stmt_bind_param($st, 'siiss', $user['nis'], $idk, $admin, $lokasi, $ket);
            // Cek apakah laporan berhasil disimpan
            $ok = mysqli_stmt_execute($st);
            $id = mysqli_insert_id($conn);
            mysqli_stmt_close($st);

            // Kalau laporan dan riwayat tersimpan, lanjut ke penyimpanan foto
            if ($ok) {
                // Catat riwayat awal: status Menunggu, dibuat oleh siswa
                $hs = mysqli_prepare($conn, "INSERT INTO histori (id_aspirasi,id_admin,status_lama,status_baru,catatan,diubah_oleh) VALUES (?, ?, NULL,'Menunggu','Aspirasi baru masuk.','siswa')");
                mysqli_stmt_bind_param($hs, 'ii', $id, $admin);
                $ok = mysqli_stmt_execute($hs);
                mysqli_stmt_close($hs);
            }

            if ($ok) {
                mysqli_commit($conn);
                // Kalau ada foto, simpan ke folder laporan
                if ($hasFile) {
                    $dir = __DIR__ . '/../../assets/uploads/aspirasi/' . $id;
                    // Buat folder khusus laporan ini kalau belum ada
                    if (!is_dir($dir)) mkdir($dir, 0777, true);
                    $filename = 'foto-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $fotoExt;
                    // Pindahkan foto dari folder sementara ke folder tujuan
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . '/' . $filename)) {
                        $urlFile = 'assets/uploads/aspirasi/' . $id . '/' . $filename;
                        $origName = $_FILES['foto']['name'];
                        // Catat alamat foto di tabel lampiran
                        $lp = mysqli_prepare($conn, 'INSERT INTO lampiran (id_aspirasi, url_file, nama_file) VALUES (?,?,?)');
                        mysqli_stmt_bind_param($lp, 'iss', $id, $urlFile, $origName);
                        mysqli_stmt_execute($lp);
                        mysqli_stmt_close($lp);
                    }
                }
                // Berhasil: buka halaman detail laporan
                redirect(url('user/detail_laporan') . '?id=' . $id . '&saved=1');
            }
            mysqli_rollback($conn);
            $error = 'Pengaduan gagal disimpan: ' . mysqli_error($conn);
        }
    }
}

// Ambil daftar kategori untuk pilihan di form
$cats = mysqli_query($conn, 'SELECT id_kategori,nama_kategori,ket_kategori FROM kategori ORDER BY nama_kategori ASC');
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Buat Pengaduan</title>
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
            <li><a href="<?= e(url('user/buat_pengaduan')) ?>" class="active">Buat Pengaduan</a></li>
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
            <h1>Buat Pengaduan</h1>
        </header>
        <main class="content-area">
            <?php // Tampilkan pesan error kalau ada ?>
            <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
            <section class="panel">
                <h2 style="margin-top:0">Formulir Pengaduan Baru</h2>
                <div class="reporter-card">
                    <?= avatar($user['foto'] ?? null, $user['nama'] ?? 'Siswa', 'lg') ?>
                    <div class="reporter-info">
                        <small>Dilaporkan oleh</small>
                        <strong><?= e($user['nama'] ?? 'Siswa') ?></strong>
                        <span class="reporter-meta">NIS <?= e($user['nis'] ?? '') ?> · <?= e($user['kelas'] ?? '') ?></span>
                    </div>
                    <a class="reporter-edit" href="<?= e(url('user/profil')) ?>">Ubah profil</a>
                </div>
                <?php // Form buat pengaduan: kategori, lokasi, keterangan, dan foto ?>
                <form method="post" class="admin-form" enctype="multipart/form-data">
                    <label>Kategori<select name="id_kategori" required>
                            <?php // Isi pilihan kategori dari database ?>
                            <option value="">Pilih kategori</option><?php while ($c = mysqli_fetch_assoc($cats)): ?><option value="<?= $c['id_kategori'] ?>"><?= e($c['nama_kategori']) ?> — <?= e($c['ket_kategori']) ?></option><?php endwhile; ?>
                        </select></label>
                    <label>Lokasi<input name="lokasi" maxlength="50" required placeholder="Contoh: Ruang XII RPL 1"></label>
                    <label>Keterangan<textarea name="keterangan" maxlength="255" required placeholder="Jelaskan kerusakan atau masalah..."></textarea></label>
                    <label>Foto Lampiran (opsional)<input type="file" name="foto" accept="image/jpeg,image/png,image/gif,image/webp"><small class="form-help">Format JPG/PNG/GIF/WEBP, maksimal 5MB.</small></label>
                    <div><button class="btn" type="submit">Kirim Pengaduan</button> <a class="btn secondary" href="<?= e(url('user/dashboard')) ?>">Batal</a></div>
                </form>
            </section>
        </main>
    </div>
</body>

</html>