<?php
// =====================================================================
// m_aspirasi.php
// Model untuk tabel `aspirasi`: laporan/pengaduan sarpas dari siswa.
// Semua query yang menyentuh tabel aspirasi ada di file ini.
// Status laporan: Menunggu -> Proses -> Selesai (atau Ditolak).
// =====================================================================

// Menambah laporan baru. Status otomatis "Menunggu".
// Hasil: id laporan yang baru dibuat, atau 0 kalau gagal.
function aspirasi_tambah(mysqli $conn, string $nis, int $idKategori, int $idAdmin, string $lokasi, string $keterangan): int {
    $st = mysqli_prepare($conn, "INSERT INTO aspirasi (nis, id_kategori, id_admin, lokasi, keterangan, status) VALUES (?,?,?,?,?,'Menunggu')");
    mysqli_stmt_bind_param($st, 'siiss', $nis, $idKategori, $idAdmin, $lokasi, $keterangan);
    $ok = mysqli_stmt_execute($st);
    $idBaru = $ok ? (int)mysqli_insert_id($conn) : 0;
    mysqli_stmt_close($st);
    return $idBaru;
}

// Mengambil status satu laporan. Hasil: array (status), atau null kalau tidak ada.
function aspirasi_cari_status(mysqli $conn, int $idAspirasi): ?array {
    $st = mysqli_prepare($conn, 'SELECT status FROM aspirasi WHERE id_aspirasi=?');
    mysqli_stmt_bind_param($st, 'i', $idAspirasi);
    mysqli_stmt_execute($st);
    $hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $hasil ?: null;
}

// Mencari satu laporan HANYA kalau milik siswa yang login.
// Mencegah siswa membuka atau mengubah laporan milik siswa lain.
// Hasil: array (id_aspirasi, status), atau null.
function aspirasi_cari_milik_siswa(mysqli $conn, int $idAspirasi, string $nis): ?array {
    $st = mysqli_prepare($conn, 'SELECT id_aspirasi, status FROM aspirasi WHERE id_aspirasi=? AND nis=?');
    mysqli_stmt_bind_param($st, 'is', $idAspirasi, $nis);
    mysqli_stmt_execute($st);
    $hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $hasil ?: null;
}

// Detail satu laporan untuk ADMIN, lengkap dengan nama, kelas, foto siswa, dan kategori.
// Hasil: array, atau null kalau tidak ada.
function aspirasi_detail_admin(mysqli $conn, int $idAspirasi): ?array {
    $sql = "SELECT a.*, s.nama AS nama_siswa, s.kelas, s.foto AS foto_siswa, k.nama_kategori
            FROM aspirasi a
            JOIN siswa s ON s.nis = a.nis
            LEFT JOIN kategori k ON k.id_kategori = a.id_kategori
            WHERE a.id_aspirasi=?";
    $st = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($st, 'i', $idAspirasi);
    mysqli_stmt_execute($st);
    $hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $hasil ?: null;
}

// Detail satu laporan milik SISWA. Hanya bisa diakses pemiliknya (cek NIS).
// Hasil: array (data laporan + nama, kelas, foto, nama_kategori), atau null.
function aspirasi_detail_siswa(mysqli $conn, int $idAspirasi, string $nis): ?array {
    $sql = "SELECT a.*, s.nama, s.kelas, s.foto, k.nama_kategori
            FROM aspirasi a
            JOIN siswa s ON s.nis = a.nis
            LEFT JOIN kategori k ON k.id_kategori = a.id_kategori
            WHERE a.id_aspirasi=? AND a.nis=?";
    $st = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($st, 'is', $idAspirasi, $nis);
    mysqli_stmt_execute($st);
    $hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $hasil ?: null;
}

// Mengubah status dan feedback (tanggapan admin) sebuah laporan.
function aspirasi_update_status(mysqli $conn, int $idAspirasi, string $status, ?string $feedback): bool {
    $st = mysqli_prepare($conn, 'UPDATE aspirasi SET status=?, feedback=? WHERE id_aspirasi=?');
    mysqli_stmt_bind_param($st, 'ssi', $status, $feedback, $idAspirasi);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Siswa mengubah isi laporannya (kategori, lokasi, keterangan).
// Hanya berhasil kalau laporan milik siswa itu DAN statusnya masih "Menunggu".
function aspirasi_update_oleh_siswa(mysqli $conn, int $idAspirasi, string $nis, int $idKategori, string $lokasi, string $keterangan): bool {
    $st = mysqli_prepare($conn, "UPDATE aspirasi SET id_kategori=?, lokasi=?, keterangan=? WHERE id_aspirasi=? AND nis=? AND status='Menunggu'");
    mysqli_stmt_bind_param($st, 'issis', $idKategori, $lokasi, $keterangan, $idAspirasi, $nis);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Menghapus satu laporan milik siswa tertentu.
// Riwayat dan lampiran harus dihapus dulu (lihat c_hapus_laporan.php).
function aspirasi_hapus(mysqli $conn, int $idAspirasi, string $nis): bool {
    $st = mysqli_prepare($conn, 'DELETE FROM aspirasi WHERE id_aspirasi=? AND nis=?');
    mysqli_stmt_bind_param($st, 'is', $idAspirasi, $nis);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Daftar semua laporan untuk halaman admin.
// $keyword kosong = semua laporan. Kalau diisi, dicari di NIS, nama siswa,
// kategori, lokasi, keterangan, dan status.
// Hasil: array baris, urut tanggal terbaru.
function aspirasi_daftar_admin(mysqli $conn, string $keyword = ''): array {
    $sql = "SELECT a.id_aspirasi, a.nis, s.nama, s.foto, k.nama_kategori, a.lokasi, a.keterangan, a.feedback, a.status, a.tanggal
            FROM aspirasi a
            LEFT JOIN siswa s ON s.nis = a.nis
            LEFT JOIN kategori k ON k.id_kategori = a.id_kategori";
    $urutan = " ORDER BY a.tanggal DESC, a.id_aspirasi DESC";

    if ($keyword === '') {
        $r = mysqli_query($conn, $sql . $urutan);
        $hasil = [];
        while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
        return $hasil;
    }

    $like = '%' . $keyword . '%';
    $st = mysqli_prepare($conn, $sql . " WHERE a.nis LIKE ? OR s.nama LIKE ? OR k.nama_kategori LIKE ? OR a.lokasi LIKE ? OR a.keterangan LIKE ? OR a.status LIKE ?" . $urutan);
    mysqli_stmt_bind_param($st, 'ssssss', $like, $like, $like, $like, $like, $like);
    mysqli_stmt_execute($st);
    $r = mysqli_stmt_get_result($st);
    $hasil = [];
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    mysqli_stmt_close($st);
    return $hasil;
}

// Laporan terbaru untuk ringkasan di dashboard admin.
// $limit = jumlah data yang diambil (default 5).
function aspirasi_terbaru_admin(mysqli $conn, int $limit = 5): array {
    $sql = "SELECT a.id_aspirasi, a.nis, s.nama, k.nama_kategori, a.lokasi, a.keterangan, a.status, a.tanggal
            FROM aspirasi a
            LEFT JOIN siswa s ON s.nis = a.nis
            LEFT JOIN kategori k ON k.id_kategori = a.id_kategori
            ORDER BY a.tanggal DESC, a.id_aspirasi DESC
            LIMIT " . (int)$limit;
    $r = mysqli_query($conn, $sql);
    $hasil = [];
    if (!$r) return $hasil;
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    return $hasil;
}

// Jumlah laporan per status untuk semua siswa (dashboard admin).
// Hasil: array baris (status, jumlah).
function aspirasi_hitung_per_status(mysqli $conn): array {
    $r = mysqli_query($conn, 'SELECT status, COUNT(*) AS jumlah FROM aspirasi GROUP BY status');
    $hasil = [];
    if (!$r) return $hasil;
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    return $hasil;
}

// Jumlah laporan per status milik satu siswa (dashboard & profil siswa).
// Hasil: array baris (status, jumlah).
function aspirasi_hitung_per_status_siswa(mysqli $conn, string $nis): array {
    $st = mysqli_prepare($conn, 'SELECT status, COUNT(*) AS jumlah FROM aspirasi WHERE nis=? GROUP BY status');
    mysqli_stmt_bind_param($st, 's', $nis);
    mysqli_stmt_execute($st);
    $r = mysqli_stmt_get_result($st);
    $hasil = [];
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    mysqli_stmt_close($st);
    return $hasil;
}

// Semua laporan milik satu siswa, urut terbaru (halaman Laporan Saya).
// Hasil: array baris (id_aspirasi, lokasi, keterangan, status, tanggal, nama_kategori).
function aspirasi_daftar_siswa(mysqli $conn, string $nis): array {
    $sql = "SELECT a.id_aspirasi, a.lokasi, a.keterangan, a.status, a.tanggal, k.nama_kategori
            FROM aspirasi a
            LEFT JOIN kategori k ON k.id_kategori = a.id_kategori
            WHERE a.nis=?
            ORDER BY a.tanggal DESC, a.id_aspirasi DESC";
    $st = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($st, 's', $nis);
    mysqli_stmt_execute($st);
    $r = mysqli_stmt_get_result($st);
    $hasil = [];
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    mysqli_stmt_close($st);
    return $hasil;
}

// Laporan terbaru milik satu siswa (ringkasan dashboard siswa).
// $limit = jumlah data yang diambil (default 5).
function aspirasi_terbaru_siswa(mysqli $conn, string $nis, int $limit = 5): array {
    $sql = "SELECT a.id_aspirasi, a.lokasi, a.keterangan, a.status, a.tanggal, k.nama_kategori
            FROM aspirasi a
            LEFT JOIN kategori k ON k.id_kategori = a.id_kategori
            WHERE a.nis=?
            ORDER BY a.tanggal DESC
            LIMIT " . (int)$limit;
    $st = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($st, 's', $nis);
    mysqli_stmt_execute($st);
    $r = mysqli_stmt_get_result($st);
    $hasil = [];
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    mysqli_stmt_close($st);
    return $hasil;
}
