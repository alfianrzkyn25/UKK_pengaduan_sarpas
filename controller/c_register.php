<?php
// =====================================================================
// c_register.php
// Memproses pendaftaran akun siswa baru (validasi lalu simpan ke database).
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../config/database.php';

// Kalau sudah login, langsung pindah ke dashboard masing-masing
if (!empty($_SESSION['role'])) {
    redirect($_SESSION['role'] === 'admin' ? 'controller/c_dashboard.php' : 'controller/c_user_dashboard.php');
}

// Variabel pesan error dan isian form (supaya isian tidak hilang saat gagal)
$error = '';
$old = ['nis' => '', 'nama' => '', 'kelas' => ''];

// Proses hanya kalau form dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil isian dari form
    $nis         = trim($_POST['nis'] ?? '');
    $nama        = trim($_POST['nama'] ?? '');
    $kelas       = trim($_POST['kelas'] ?? '');
    $password    = $_POST['password'] ?? '';
    $konfirmasi  = $_POST['konfirmasi'] ?? '';
    // Simpan isian agar bisa ditampilkan lagi kalau gagal
    $old = ['nis' => $nis, 'nama' => $nama, 'kelas' => $kelas];

    // Validasi: semua kolom wajib diisi
    if ($nis === '' || $nama === '' || $kelas === '' || $password === '' || $konfirmasi === '') {
        $error = 'Semua data wajib diisi.';
    // Validasi format NIS: angka 4 sampai 10 digit
    } elseif (!preg_match('/^[0-9]{4,10}$/', $nis)) {
        $error = 'NIS harus berupa angka (4-10 digit).';
    // Validasi panjang nama dan password
    } elseif (mb_strlen($nama) < 3) {
        $error = 'Nama lengkap minimal 3 karakter.';
    } elseif (strlen($password) < 4) {
        $error = 'Password minimal 4 karakter.';
    // Password dan konfirmasinya harus sama
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama dengan password.';
    } else {
        // Cek apakah NIS sudah terdaftar sebagai siswa
        $st = mysqli_prepare($conn, 'SELECT nis FROM siswa WHERE nis=? LIMIT 1');
        mysqli_stmt_bind_param($st, 's', $nis);
        mysqli_stmt_execute($st);
        mysqli_stmt_store_result($st);
        $exists = mysqli_stmt_num_rows($st) > 0;
        mysqli_stmt_close($st);

        // Cek juga apakah NIS bentrok dengan username admin
        $adminClash = false;
        if (!$exists) {
            // Cari username admin yang sama dengan NIS
            $sa = mysqli_prepare($conn, 'SELECT id_admin FROM admin WHERE username=? LIMIT 1');
            mysqli_stmt_bind_param($sa, 's', $nis);
            mysqli_stmt_execute($sa);
            mysqli_stmt_store_result($sa);
            $adminClash = mysqli_stmt_num_rows($sa) > 0;
            mysqli_stmt_close($sa);
        }

        // Tolak kalau NIS sudah terdaftar atau bentrok dengan admin
        if ($exists) {
            $error = 'NIS ini sudah terdaftar. Silakan login.';
        } elseif ($adminClash) {
            $error = 'NIS ini tidak dapat digunakan. Silakan hubungi admin.';
        } else {
            // Simpan akun siswa baru
            $stmt = mysqli_prepare($conn, 'INSERT INTO siswa (nis, nama, kelas, password) VALUES (?,?,?,?)');
            mysqli_stmt_bind_param($stmt, 'ssss', $nis, $nama, $kelas, $password);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($ok) {
                // Berhasil daftar: pindah ke halaman login
                redirect('login.php?registered=1');
            }
            $error = 'Registrasi gagal disimpan: ' . mysqli_error($conn);
        }
    }
}

// Tampilkan lagi form pendaftaran (dengan pesan error kalau ada)
include __DIR__ . '/../auth/register.php';
