<?php
// =====================================================================
// c_user_edit.php
// Memproses form edit laporan siswa (hanya saat status masih Menunggu), termasuk tambah lampiran.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../config/database.php';
// Pastikan yang membuka halaman ini adalah siswa yang login
require_role('siswa');
$user = current_user();

// Ambil id laporan dari URL (GET) atau dari form (POST)
$id   = (int)($_GET['id'] ?? $_POST['id_aspirasi'] ?? 0);
// Variabel pesan error
$error = '';

// Ambil data aspirasi milik siswa ini, hanya boleh edit jika Menunggu
$st = mysqli_prepare($conn, 'SELECT a.*,k.nama_kategori FROM aspirasi a LEFT JOIN kategori k ON k.id_kategori=a.id_kategori WHERE a.id_aspirasi=? AND a.nis=?');
mysqli_stmt_bind_param($st, 'is', $id, $user['nis']);
mysqli_stmt_execute($st);
$a = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

// Kalau laporan tidak ditemukan atau bukan milik siswa ini, kembali ke Laporan Saya
if (!$a) {
    redirect('c_user_laporan.php');
}
// Laporan yang sudah diproses admin tidak boleh diedit
if ($a['status'] !== 'Menunggu') {
    redirect('c_user_laporan.php?error_edit=1');
}

// Handle POST (simpan perubahan)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form edit
    $idk    = (int)($_POST['id_kategori'] ?? 0);
    $lokasi = trim($_POST['lokasi'] ?? '');
    $ket    = trim($_POST['keterangan'] ?? '');

    // Validasi foto sama seperti saat membuat pengaduan
    $allowedExt = ['jpg'=>1,'jpeg'=>1,'png'=>1,'gif'=>1,'webp'=>1];
    $maxSize    = 5 * 1024 * 1024;
    $hasFile    = !empty($_FILES['foto']['name']);
    $fotoExt    = $hasFile ? strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION)) : '';

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
        // Ubah isi laporan; hanya berhasil kalau status masih Menunggu
        $st = mysqli_prepare($conn, 'UPDATE aspirasi SET id_kategori=?,lokasi=?,keterangan=? WHERE id_aspirasi=? AND nis=? AND status="Menunggu"');
        mysqli_stmt_bind_param($st, 'issis', $idk, $lokasi, $ket, $id, $user['nis']);
        $ok = mysqli_stmt_execute($st);
        mysqli_stmt_close($st);

        if ($ok) {
            // Kalau ada foto baru, simpan dan catat ke lampiran
            if ($hasFile) {
                // Siapkan folder dan nama file untuk foto baru
                $dir = __DIR__ . '/../assets/uploads/aspirasi/' . $id;
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $filename = 'foto-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $fotoExt;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . '/' . $filename)) {
                    $urlFile  = 'assets/uploads/aspirasi/' . $id . '/' . $filename;
                    $origName = $_FILES['foto']['name'];
                    $lp = mysqli_prepare($conn, 'INSERT INTO lampiran (id_aspirasi,url_file,nama_file) VALUES (?,?,?)');
                    mysqli_stmt_bind_param($lp, 'iss', $id, $urlFile, $origName);
                    mysqli_stmt_execute($lp);
                    mysqli_stmt_close($lp);
                }
            }
            // Berhasil: kembali ke daftar laporan dengan notifikasi
            redirect('c_user_laporan.php?edited=1');
        }
        // Kalau gagal, tampilkan pesan error
        $error = 'Gagal menyimpan perubahan: ' . mysqli_error($conn);
    }

    // Refresh $a setelah gagal supaya form tetap terisi
    $st = mysqli_prepare($conn, 'SELECT a.*,k.nama_kategori FROM aspirasi a LEFT JOIN kategori k ON k.id_kategori=a.id_kategori WHERE a.id_aspirasi=? AND a.nis=?');
    mysqli_stmt_bind_param($st, 'is', $id, $user['nis']);
    mysqli_stmt_execute($st);
    $a = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
}

// Ambil daftar kategori
// Ambil daftar kategori untuk pilihan di form
$cats = mysqli_query($conn, 'SELECT id_kategori,nama_kategori,ket_kategori FROM kategori ORDER BY nama_kategori ASC');

// Lampiran yang sudah ada
// Siapkan daftar lampiran yang akan ditampilkan
$lampiran = [];
$st = mysqli_prepare($conn, 'SELECT id_lampiran,url_file,nama_file FROM lampiran WHERE id_aspirasi=? ORDER BY id_lampiran ASC');
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
$lr = mysqli_stmt_get_result($st);
while ($lf = mysqli_fetch_assoc($lr)) {
    // Lewati file feedback dari admin; yang ditampilkan hanya foto yang diunggah siswa
    if (strpos(basename($lf['url_file']), 'feedback-') !== 0) $lampiran[] = $lf;
}
mysqli_stmt_close($st);

// Tampilkan form edit laporan
include __DIR__ . '/../view/user/edit_laporan.php';
