<?php
// =====================================================================
// c_tambah_aspirasi.php
// Memproses form tambah aspirasi oleh admin atas nama siswa.
// =====================================================================
// Cek login admin dan siapkan variabel awal
require_once __DIR__ . '/../config/database.php'; require_role('admin'); $user=current_user(); $error='';
// Hanya proses kalau form dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form: siswa, kategori, lokasi, dan keterangan
    $nis = trim($_POST['nis'] ?? '');
    $idk = (int)($_POST['id_kategori'] ?? 0);
    $lokasi = trim($_POST['lokasi'] ?? '');
    $ket = trim($_POST['keterangan'] ?? '');
    // Admin yang mencatat laporan ini
    $admin = (int)$user['id_admin'];

    // Daftar format foto yang diizinkan
    $allowedExt = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
    // Batas ukuran foto: 5 MB
    $maxSize = 5 * 1024 * 1024; // 5MB
    // Cek apakah ada foto yang diunggah
    $hasFile = !empty($_FILES['foto']['name']);
    $fotoExt = $hasFile ? strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION)) : '';

    // Validasi: semua data wajib diisi dan foto harus valid
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
        // Simpan laporan baru dengan status Menunggu
        $st = mysqli_prepare($conn, "INSERT INTO aspirasi (nis,id_kategori,id_admin,lokasi,keterangan,status) VALUES (?,?,?,?,?,'Menunggu')");
        mysqli_stmt_bind_param($st, 'siiss', $nis, $idk, $admin, $lokasi, $ket);
        if (mysqli_stmt_execute($st)) {
            // Ambil id laporan yang baru dibuat
            $id = mysqli_insert_id($conn);
            mysqli_stmt_close($st);

            // Catat riwayat awal laporan, atas nama admin
            $hs = mysqli_prepare($conn, "INSERT INTO histori (id_aspirasi,id_admin,status_lama,status_baru,catatan,diubah_oleh) VALUES (?, ?, NULL,'Menunggu','Aspirasi baru masuk.',?)");
            // Nama admin yang tercatat di riwayat
            $by = $user['nama'] ?? 'admin';
            mysqli_stmt_bind_param($hs, 'iis', $id, $admin, $by);
            mysqli_stmt_execute($hs);
            mysqli_stmt_close($hs);

            if ($hasFile) {
                // Simpan foto ke folder khusus laporan ini
                $dir = __DIR__ . '/../assets/uploads/aspirasi/' . $id;
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $filename = 'foto-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $fotoExt;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . '/' . $filename)) {
                    $urlFile = 'assets/uploads/aspirasi/' . $id . '/' . $filename;
                    $origName = $_FILES['foto']['name'];
                    $lp = mysqli_prepare($conn, 'INSERT INTO lampiran (id_aspirasi, url_file, nama_file) VALUES (?,?,?)');
                    mysqli_stmt_bind_param($lp, 'iss', $id, $urlFile, $origName);
                    mysqli_stmt_execute($lp);
                    mysqli_stmt_close($lp);
                }
            }

            // Berhasil: buka halaman detail laporan
            redirect('c_detail_aspirasi.php?id=' . $id . '&saved=1');
        }
        // Kalau gagal, tampilkan pesan error
        $error = 'Data gagal disimpan: ' . mysqli_error($conn);
    }
}
// Ambil daftar siswa dan kategori untuk pilihan di form
$students = mysqli_query($conn, 'SELECT nis,nama,kelas FROM siswa ORDER BY nama ASC');
$categories = mysqli_query($conn, 'SELECT id_kategori,nama_kategori,ket_kategori FROM kategori ORDER BY nama_kategori ASC');
// Tampilkan form tambah aspirasi
include __DIR__ . '/../view/admin/form_aspirasi.php';
