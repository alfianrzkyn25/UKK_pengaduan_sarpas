<?php
// =====================================================================
// c_siswa.php
// Dulu halaman Kelola Siswa. Sekarang hanya meneruskan ke view/admin/siswa.php agar link lama tetap jalan.
// =====================================================================
// Halaman Kelola Siswa sekarang berada langsung di view/admin/siswa.php.
// File ini hanya menjaga kompatibilitas untuk tautan/bookmark lama.
require_once __DIR__ . '/../config/database.php';
// Simpan query string dari URL asli (misalnya ?q=...)
$query = $_SERVER['QUERY_STRING'] ?? '';
// Pindahkan ke halaman Kelola Siswa yang baru sambil membawa query string
redirect(url('admin/siswa') . ($query !== '' ? '?' . $query : ''));
