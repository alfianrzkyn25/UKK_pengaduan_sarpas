<?php
// =====================================================================
// c_aspirasi.php
// Memuat daftar laporan untuk admin (dengan fitur pencarian) lalu menampilkan view admin/aspirasi.php.
// =====================================================================
require_once __DIR__ . '/../config/database.php'; require_role('admin'); $user=current_user();
// Ambil kata kunci pencarian dari URL (?q=...)
$q=trim($_GET['q']??''); $rows=[];
// Kalau ada kata kunci, cari di beberapa kolom sekaligus (pakai LIKE)
if($q!==''){ $like='%'.$q.'%'; $st=mysqli_prepare($conn,"SELECT a.id_aspirasi,a.nis,s.nama,k.nama_kategori,a.lokasi,a.keterangan,a.feedback,a.status,a.tanggal FROM aspirasi a LEFT JOIN siswa s ON s.nis=a.nis LEFT JOIN kategori k ON k.id_kategori=a.id_kategori WHERE a.nis LIKE ? OR s.nama LIKE ? OR k.nama_kategori LIKE ? OR a.lokasi LIKE ? OR a.keterangan LIKE ? OR a.status LIKE ? ORDER BY a.tanggal DESC,a.id_aspirasi DESC"); mysqli_stmt_bind_param($st,'ssssss',$like,$like,$like,$like,$like,$like); mysqli_stmt_execute($st); $res=mysqli_stmt_get_result($st); } else {
    // Tidak ada kata kunci: ambil semua laporan tanpa filter
    $res=mysqli_query($conn,"SELECT a.id_aspirasi,a.nis,s.nama,k.nama_kategori,a.lokasi,a.keterangan,a.feedback,a.status,a.tanggal FROM aspirasi a LEFT JOIN siswa s ON s.nis=a.nis LEFT JOIN kategori k ON k.id_kategori=a.id_kategori ORDER BY a.tanggal DESC,a.id_aspirasi DESC"); }
// Kumpulkan hasil ke array $rows
while($x=mysqli_fetch_assoc($res)) $rows[]=$x; if(isset($st)) mysqli_stmt_close($st);
// Tampilkan halaman daftar laporan admin
include __DIR__.'/../view/admin/aspirasi.php';
