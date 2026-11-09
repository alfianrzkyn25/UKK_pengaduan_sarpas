<?php
// =====================================================================
// form_aspirasi.php
// Form tambah aspirasi oleh admin atas nama siswa.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../../config/database.php';
// Pastikan yang membuka halaman ini adalah admin yang login
require_role('admin');
$user = current_user();
$error = '';
// Proses hanya kalau form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form: siswa, kategori, lokasi, dan keterangan
    $nis = trim($_POST['nis'] ?? '');
    $idk = (int)($_POST['id_kategori'] ?? 0);
    $lokasi = trim($_POST['lokasi'] ?? '');
    $ket = trim($_POST['keterangan'] ?? '');
    $admin = (int)$user['id_admin'];

    $allowedExt = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    $hasFile = !empty($_FILES['foto']['name']);
    $fotoExt = $hasFile ? strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION)) : '';

    // Validasi: semua data wajib diisi
    if ($nis === '' || !$idk || $lokasi === '' || $ket === '') {
        $error = 'Semua data pengaduan wajib diisi.';
    } elseif ($hasFile && $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Gagal mengunggah foto. Silakan coba lagi.';
    } elseif ($hasFile && !isset($allowedExt[$fotoExt])) {
        $error = 'Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.';
    } elseif ($hasFile && $_FILES['foto']['size'] > $maxSize) {
        $error = 'Ukuran foto maksimal 5MB.';
    } elseif ($hasFile && !@getimagesize($_FILES['foto']['tmp_name'])) {
        $error = 'File yang diunggah bukan gambar yang valid.';
    } else {
        // Simpan laporan baru dengan status Menunggu, atas nama admin
        $st = mysqli_prepare($conn, "INSERT INTO aspirasi (nis,id_kategori,id_admin,lokasi,keterangan,status) VALUES (?,?,?,?,?,'Menunggu')");
        mysqli_stmt_bind_param($st, 'siiss', $nis, $idk, $admin, $lokasi, $ket);
        // Kalau berhasil, lanjut simpan riwayat dan foto
        if (mysqli_stmt_execute($st)) {
            $id = mysqli_insert_id($conn);
            mysqli_stmt_close($st);

            // Catat riwayat awal laporan
            $hs = mysqli_prepare($conn, "INSERT INTO histori (id_aspirasi,id_admin,status_lama,status_baru,catatan,diubah_oleh) VALUES (?, ?, NULL,'Menunggu','Aspirasi baru masuk.',?)");
            $by = $user['nama'] ?? 'admin';
            mysqli_stmt_bind_param($hs, 'iis', $id, $admin, $by);
            mysqli_stmt_execute($hs);
            mysqli_stmt_close($hs);

            // Kalau ada foto, simpan ke folder laporan
            if ($hasFile) {
                $dir = __DIR__ . '/../../assets/uploads/aspirasi/' . $id;
                // Buat folder laporan kalau belum ada
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $filename = 'foto-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $fotoExt;
                // Pindahkan foto ke folder tujuan
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . '/' . $filename)) {
                    $urlFile = 'assets/uploads/aspirasi/' . $id . '/' . $filename;
                    $origName = $_FILES['foto']['name'];
                    // Catat foto di tabel lampiran
                    $lp = mysqli_prepare($conn, 'INSERT INTO lampiran (id_aspirasi, url_file, nama_file) VALUES (?,?,?)');
                    mysqli_stmt_bind_param($lp, 'iss', $id, $urlFile, $origName);
                    mysqli_stmt_execute($lp);
                    mysqli_stmt_close($lp);
                }
            }

            // Berhasil: buka halaman detail laporan
            redirect(url('admin/detail_aspirasi') . '?id=' . $id . '&saved=1');
        }
        $error = 'Data gagal disimpan: ' . mysqli_error($conn);
    }
}
// Ambil daftar siswa untuk pilihan di form
$students = mysqli_query($conn, 'SELECT nis,nama,kelas FROM siswa ORDER BY nama ASC');
// Ambil daftar kategori untuk pilihan di form
$categories = mysqli_query($conn, 'SELECT id_kategori,nama_kategori,ket_kategori FROM kategori ORDER BY nama_kategori ASC');
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Form Aspirasi</title>
    <link rel="stylesheet" href="<?= e($base) ?>/assets/admin.css">
    <script src="<?= e($base) ?>/assets/ui.js"></script>
</head>

<body>
    <input type="checkbox" id="menuToggle" class="menu-toggle">
    <?php // Sidebar menu navigasi admin ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">Pengaduan Sarpras</div>
        <ul>
            <li><a href="<?= e(url('admin/dashboard')) ?>" class="">Dashboard</a></li>
            <li><a href="<?= e(url('admin/aspirasi')) ?>" class="">Data Aspirasi</a></li>
            <li><a href="<?= e(url('admin/form_aspirasi')) ?>" class="active">form aspirasi</a></li>
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
            <h1>Form Aspirasi</h1>
        </header>
        <main class="content-area">
            <?php // Tampilkan pesan error kalau ada ?>
            <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
            <section class="panel">
                <h2 style="margin-top:0">Tambah Aspirasi Baru</h2>
                <?php // Form tambah aspirasi atas nama siswa ?>
                <form method="post" class="admin-form" enctype="multipart/form-data">
                    <label>Siswa<select name="nis" required>
                            <?php // Isi pilihan siswa dari database ?>
                            <option value="">Pilih siswa</option><?php while ($s = mysqli_fetch_assoc($students)): ?><option value="<?= e($s['nis']) ?>"><?= e($s['nama']) ?> (<?= e($s['nis']) ?>)</option><?php endwhile; ?>
                        </select></label>
                    <label>Kategori<select name="id_kategori" required>
                            <?php // Isi pilihan kategori dari database ?>
                            <option value="">Pilih kategori</option><?php while ($c = mysqli_fetch_assoc($categories)): ?><option value="<?= $c['id_kategori'] ?>"><?= e($c['nama_kategori']) ?> — <?= e($c['ket_kategori']) ?></option><?php endwhile; ?>
                        </select></label>
                    <label>Lokasi<input name="lokasi" maxlength="50" required placeholder="Contoh: Ruang XII RPL 1"></label>
                    <label>Keterangan<textarea name="keterangan" maxlength="255" required placeholder="Jelaskan kerusakan atau masalah..."></textarea></label>
                    <label>Foto (opsional)<input type="file" name="foto" accept="image/jpeg,image/png,image/gif,image/webp"><small class="form-help">Format JPG/PNG/GIF/WEBP, maksimal 5MB.</small></label>
                    <div><button class="btn" type="submit">Kirim Pengaduan</button> <a class="btn secondary" href="<?= e(url('admin/aspirasi')) ?>">Batal</a></div>
                </form>
            </section>
        </main>
    </div>
</body>

</html>