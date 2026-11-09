<?php
// =====================================================================
// m_histori.php
// Model untuk tabel `histori`: riwayat perubahan status setiap laporan.
// Setiap perubahan (Menunggu -> Proses -> Selesai, dst) dicatat di sini.
// Semua query yang menyentuh tabel histori ada di file ini.
// =====================================================================

// Mencatat satu riwayat perubahan status.
// $statusLama boleh null (untuk laporan baru yang belum punya status sebelumnya).
// $catatan boleh null (kalau admin tidak mengisi catatan).
// $diubahOleh berisi 'admin' / 'siswa' / nama admin, sebagai penanda siapa yang mengubah.
function histori_tambah(mysqli $conn, int $idAspirasi, int $idAdmin, ?string $statusLama, string $statusBaru, ?string $catatan, string $diubahOleh): bool {
    $st = mysqli_prepare($conn, 'INSERT INTO histori (id_aspirasi, id_admin, status_lama, status_baru, catatan, diubah_oleh) VALUES (?,?,?,?,?,?)');
    mysqli_stmt_bind_param($st, 'iissss', $idAspirasi, $idAdmin, $statusLama, $statusBaru, $catatan, $diubahOleh);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Riwayat satu laporan untuk tampilan ADMIN.
// Nama yang tampil adalah nama admin (kalau ada), kalau tidak ada dipakai teks diubah_oleh.
// Hasil: array baris (status_lama, status_baru, catatan, waktu_ubah, diubah_oleh), urut waktu.
function histori_daftar_admin_per_aspirasi(mysqli $conn, int $idAspirasi): array {
    $st = mysqli_prepare($conn, 'SELECT h.status_lama, h.status_baru, h.catatan, h.waktu_ubah, COALESCE(ad.nama, h.diubah_oleh) AS diubah_oleh
                                 FROM histori h
                                 LEFT JOIN admin ad ON ad.id_admin = h.id_admin
                                 WHERE h.id_aspirasi=?
                                 ORDER BY h.waktu_ubah ASC, h.id_histori ASC');
    mysqli_stmt_bind_param($st, 'i', $idAspirasi);
    mysqli_stmt_execute($st);
    $r = mysqli_stmt_get_result($st);
    $hasil = [];
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    mysqli_stmt_close($st);
    return $hasil;
}

// Riwayat satu laporan untuk tampilan SISWA (nama diubah_oleh ditampilkan apa adanya).
// Hasil: array baris (status_lama, status_baru, catatan, diubah_oleh, waktu_ubah), urut waktu.
function histori_daftar_siswa_per_aspirasi(mysqli $conn, int $idAspirasi): array {
    $st = mysqli_prepare($conn, 'SELECT status_lama, status_baru, catatan, diubah_oleh, waktu_ubah
                                 FROM histori
                                 WHERE id_aspirasi=?
                                 ORDER BY waktu_ubah ASC, id_histori ASC');
    mysqli_stmt_bind_param($st, 'i', $idAspirasi);
    mysqli_stmt_execute($st);
    $r = mysqli_stmt_get_result($st);
    $hasil = [];
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    mysqli_stmt_close($st);
    return $hasil;
}

// Seluruh riwayat dari semua laporan untuk halaman Riwayat admin, terbaru di atas.
// Sudah digabung dengan nama siswa dan nama admin.
function histori_daftar_semua(mysqli $conn): array {
    $sql = "SELECT h.*, s.nama, s.nis, COALESCE(ad.nama, h.diubah_oleh) AS diubah_oleh
            FROM histori h
            LEFT JOIN aspirasi a ON a.id_aspirasi = h.id_aspirasi
            LEFT JOIN siswa s ON s.nis = a.nis
            LEFT JOIN admin ad ON ad.id_admin = h.id_admin
            ORDER BY h.waktu_ubah DESC, h.id_histori DESC";
    $r = mysqli_query($conn, $sql);
    $hasil = [];
    if (!$r) return $hasil;
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    return $hasil;
}

// Jumlah riwayat per status tujuan (status_baru). Hasil: array baris (status_baru, jumlah).
function histori_hitung_per_status(mysqli $conn): array {
    $r = mysqli_query($conn, 'SELECT status_baru, COUNT(*) AS jumlah FROM histori GROUP BY status_baru');
    $hasil = [];
    if (!$r) return $hasil;
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    return $hasil;
}

// Menghapus semua riwayat milik satu laporan. Dipakai saat laporan dihapus.
function histori_hapus_by_aspirasi(mysqli $conn, int $idAspirasi): bool {
    $st = mysqli_prepare($conn, 'DELETE FROM histori WHERE id_aspirasi=?');
    mysqli_stmt_bind_param($st, 'i', $idAspirasi);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Menghapus SATU baris riwayat berdasarkan id_histori (dari halaman Riwayat admin).
function histori_hapus(mysqli $conn, int $idHistori): bool {
    $st = mysqli_prepare($conn, 'DELETE FROM histori WHERE id_histori=?');
    mysqli_stmt_bind_param($st, 'i', $idHistori);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}
