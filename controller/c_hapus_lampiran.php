<?php
// =====================================================================
// c_hapus_lampiran.php
// Fungsi: menghapus satu lampiran (foto/file) dari sebuah laporan.
// Hanya bisa dilakukan siswa pemilik laporan, dan saat laporan masih "Menunggu".
// Yang dihapus: file fisik di folder uploads, lalu datanya di tabel lampiran.
// =====================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/m_lampiran.php';
require_role('siswa');
$user = current_user();

// Id lampiran dan id laporan induknya, dikirim dari form
$id_lampiran = (int)($_POST['id_lampiran'] ?? 0);
$id_aspirasi = (int)($_POST['id_aspirasi'] ?? 0);

if ($id_lampiran > 0 && $id_aspirasi > 0) {
    // Cek dulu: lampiran ini memang milik laporan si siswa dan masih boleh diubah
    $lampiran = lampiran_cari_milik_siswa($conn, $id_lampiran, $id_aspirasi, $user['nis']);

    if ($lampiran) {
        // Hapus file fisiknya dari folder (kalau ada)
        $path = __DIR__ . '/../' . $lampiran['url_file'];
        if (file_exists($path)) @unlink($path);

        // Hapus datanya dari tabel lampiran
        lampiran_hapus($conn, $id_lampiran);
    }
}

// Kembali ke halaman edit laporan
redirect('../view/user/edit_laporan.php?id=' . $id_aspirasi);
