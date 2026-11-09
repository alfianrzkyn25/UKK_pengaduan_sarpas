<?php
// =====================================================================
// c_user_detail.php
// Menyiapkan detail satu laporan milik siswa beserta riwayat dan lampirannya.
// =====================================================================
require_once __DIR__ . '/../config/database.php'; require_role('siswa'); $user = current_user();
$id = (int)($_GET['id'] ?? 0); $saved = isset($_GET['saved']);

// Ambil detail laporan, hanya kalau milik siswa yang sedang login
$st = mysqli_prepare($conn, 'SELECT a.*,s.nama,s.kelas,k.nama_kategori FROM aspirasi a JOIN siswa s ON s.nis=a.nis LEFT JOIN kategori k ON k.id_kategori=a.id_kategori WHERE a.id_aspirasi=? AND a.nis=?');
mysqli_stmt_bind_param($st, 'is', $id, $user['nis']);
mysqli_stmt_execute($st);
$a = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

$hist = [];
if ($a) {
    // Ambil riwayat perubahan status laporan ini
    $st = mysqli_prepare($conn, 'SELECT status_lama,status_baru,catatan,diubah_oleh,waktu_ubah FROM histori WHERE id_aspirasi=? ORDER BY waktu_ubah ASC,id_histori ASC');
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $rr = mysqli_stmt_get_result($st);
    while ($h = mysqli_fetch_assoc($rr)) $hist[] = $h;
    mysqli_stmt_close($st);
}

$fotoPengaduan = [];
$fotoFeedback = [];
if ($a) {
    // Ambil lampiran (foto/file) laporan ini
    $st = mysqli_prepare($conn, 'SELECT url_file,nama_file FROM lampiran WHERE id_aspirasi=? ORDER BY id_lampiran ASC');
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $lr = mysqli_stmt_get_result($st);
    while ($lf = mysqli_fetch_assoc($lr)) {
        if (strpos(basename($lf['url_file']), 'feedback-') === 0) {
            $fotoFeedback[] = $lf;
        } else {
            $fotoPengaduan[] = $lf;
        }
    }
    mysqli_stmt_close($st);
}

include __DIR__ . '/../view/user/detail_laporan.php';
