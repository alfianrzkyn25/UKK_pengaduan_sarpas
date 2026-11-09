<?php
// =====================================================================
// c_user_tambah.php
// Memproses form buat pengaduan baru dari siswa, termasuk upload lampiran.
// =====================================================================
// Cek login siswa dan siapkan variabel awal (pesan error kosong)
require_once __DIR__ . '/../config/database.php'; require_role('siswa'); $user = current_user(); $error = '';

// Hanya proses kalau form dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form: kategori, lokasi, dan keterangan
    $idk = (int)($_POST['id_kategori'] ?? 0);
    $lokasi = trim($_POST['lokasi'] ?? '');
    $ket = trim($_POST['keterangan'] ?? '');

    // Daftar format foto yang diizinkan
    $allowedExt = ['jpg' => 1, 'jpeg' => 1, 'png' => 1, 'gif' => 1, 'webp' => 1];
    // Batas ukuran foto: 5 MB
    $maxSize = 5 * 1024 * 1024; // 5MB
    // Cek apakah siswa mengunggah foto
    $hasFile = !empty($_FILES['foto']['name']);
    // Ambil ekstensi foto dalam huruf kecil
    $fotoExt = $hasFile ? strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION)) : '';

    // Validasi: semua data wajib diisi, foto harus lolos pengecekan format, ukuran, dan isi gambar
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
        // Ambil id admin penanggung jawab (admin pertama)
        $admin = default_admin_id();
        if (!$admin) {
            $error = 'Belum ada data admin di database.';
        } else {
            // Mulai transaksi: laporan dan riwayat harus tersimpan bersama
            mysqli_begin_transaction($conn);
            $st = mysqli_prepare($conn, "INSERT INTO aspirasi (nis,id_kategori,id_admin,lokasi,keterangan,status) VALUES (?,?,?,?,?,'Menunggu')");
            mysqli_stmt_bind_param($st, 'siiss', $user['nis'], $idk, $admin, $lokasi, $ket);
            $ok = mysqli_stmt_execute($st);
            // Ambil id laporan yang baru dibuat
            $id = mysqli_insert_id($conn);
            mysqli_stmt_close($st);

            if ($ok) {
                // Catat riwayat awal: status Menunggu, dibuat oleh siswa
                $hs = mysqli_prepare($conn, "INSERT INTO histori (id_aspirasi,id_admin,status_lama,status_baru,catatan,diubah_oleh) VALUES (?, ?, NULL,'Menunggu','Aspirasi baru masuk.','siswa')");
                mysqli_stmt_bind_param($hs, 'ii', $id, $admin);
                $ok = mysqli_stmt_execute($hs);
                mysqli_stmt_close($hs);
            }

            if ($ok) {
                // Simpan permanen semua perubahan
                mysqli_commit($conn);
                if ($hasFile) {
                    // Siapkan folder khusus untuk lampiran laporan ini
                    $dir = __DIR__ . '/../assets/uploads/aspirasi/' . $id;
                    if (!is_dir($dir)) mkdir($dir, 0777, true);
                    // Beri nama file unik supaya tidak menimpa file lain
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
                redirect('c_user_detail.php?id=' . $id . '&saved=1');
            }
            // Kalau gagal, batalkan semua perubahan
            mysqli_rollback($conn);
            $error = 'Pengaduan gagal disimpan: ' . mysqli_error($conn);
        }
    }
}

// Ambil daftar kategori untuk pilihan di form
$cats = mysqli_query($conn, 'SELECT id_kategori,nama_kategori,ket_kategori FROM kategori ORDER BY nama_kategori ASC');
// Tampilkan form buat pengaduan (beserta pesan error jika ada)
include __DIR__ . '/../view/user/buat_pengaduan.php';
