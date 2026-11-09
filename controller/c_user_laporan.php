<?php
// =====================================================================
// c_user_laporan.php
// Menyiapkan daftar laporan milik siswa yang login (Laporan Saya).
// =====================================================================
require_once __DIR__.'/../config/database.php'; require_role('siswa');
$user=current_user();
// Ambil semua laporan milik siswa ini, terbaru di atas
$st=mysqli_prepare($conn,'SELECT a.id_aspirasi,a.lokasi,a.keterangan,a.status,a.tanggal,k.nama_kategori FROM aspirasi a LEFT JOIN kategori k ON k.id_kategori=a.id_kategori WHERE a.nis=? ORDER BY a.tanggal DESC,a.id_aspirasi DESC'); mysqli_stmt_bind_param($st,'s',$user['nis']); mysqli_stmt_execute($st); $r=mysqli_stmt_get_result($st); // Tampilkan halaman Laporan Saya
include __DIR__.'/../view/user/laporan_saya.php';
