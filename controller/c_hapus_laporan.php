<?php
// =====================================================================
// c_hapus_laporan.php
// Fungsi: menghapus laporan (aspirasi) milik siswa yang sedang login.
// Syarat: laporan itu miliknya DAN statusnya masih "Menunggu".
// Yang dihapus: riwayat (histori), lampiran, lalu laporannya sendiri.
// Ketiganya dibungkus transaksi supaya kalau satu gagal, semuanya batal.
// =====================================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/m_aspirasi.php';
require_once __DIR__ . '/../models/m_histori.php';
require_once __DIR__ . '/../models/m_lampiran.php';
require_role('siswa');
$user = current_user();

// Id laporan yang mau dihapus, dikirim dari form
$id = (int)($_POST['id_aspirasi'] ?? 0);

if ($id > 0) {
    // Cek dulu: laporan ini benar milik siswa ini dan belum diproses
    $laporan = aspirasi_cari_milik_siswa($conn, $id, $user['nis']);

    if ($laporan && $laporan['status'] === 'Menunggu') {
        mysqli_begin_transaction($conn);

        histori_hapus_by_aspirasi($conn, $id);   // 1. hapus riwayat
        lampiran_hapus_by_aspirasi($conn, $id);  // 2. hapus lampiran
        aspirasi_hapus($conn, $id, $user['nis']); // 3. hapus laporan utamanya

        mysqli_commit($conn);
        redirect('../view/user/laporan_saya.php?deleted=1');
    }
}

// Kalau tidak memenuhi syarat di atas, kembali dengan pesan error
redirect('../view/user/laporan_saya.php?error=1');
