<?php
// =====================================================================
// c_hapus_histori.php
// Fungsi: menghapus SATU baris riwayat status (histori).
// Hanya admin yang boleh. Setelah berhasil, halaman dikembalikan ke $back
// dengan tambahan ?deleted=1 supaya muncul notifikasi.
// =====================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/m_histori.php';
require_role('admin');

// Ambil id riwayat yang akan dihapus dan halaman asal untuk kembali
$id   = (int)($_POST['id_histori'] ?? 0);
$back = $_POST['back'] ?? '../view/admin/histori.php';

// Hapus hanya kalau id-nya valid (lebih dari 0)
if ($id > 0) {
    histori_hapus($conn, $id);
}

// Pakai '&' kalau URL sudah punya '?', selain itu pakai '?'
$sep = (strpos($back, '?') !== false) ? '&' : '?';
redirect($back . $sep . 'deleted=1');
