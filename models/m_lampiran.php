<?php
// =====================================================================
// m_lampiran.php
// Model untuk tabel `lampiran`: file/foto pendukung yang dilampirkan pada laporan.
// Tabel ini hanya menyimpan alamat file (url_file). File aslinya ada di assets/uploads.
// Semua query yang menyentuh tabel lampiran ada di file ini.
// =====================================================================

// Menambah satu lampiran ke sebuah laporan.
function lampiran_tambah(mysqli $conn, int $idAspirasi, string $urlFile, string $namaFile): bool {
    $st = mysqli_prepare($conn, 'INSERT INTO lampiran (id_aspirasi, url_file, nama_file) VALUES (?,?,?)');
    mysqli_stmt_bind_param($st, 'iss', $idAspirasi, $urlFile, $namaFile);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Semua lampiran milik satu laporan, urut dari yang pertama diunggah.
// Hasil: array baris (id_lampiran, url_file, nama_file).
function lampiran_daftar(mysqli $conn, int $idAspirasi): array {
    $st = mysqli_prepare($conn, 'SELECT id_lampiran, url_file, nama_file FROM lampiran WHERE id_aspirasi=? ORDER BY id_lampiran ASC');
    mysqli_stmt_bind_param($st, 'i', $idAspirasi);
    mysqli_stmt_execute($st);
    $r = mysqli_stmt_get_result($st);
    $hasil = [];
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    mysqli_stmt_close($st);
    return $hasil;
}

// Mencari satu lampiran, tapi HANYA kalau milik laporan siswa yang login
// dan laporannya masih "Menunggu" (belum diproses admin).
// Hasil: array (url_file), atau null kalau tidak boleh / tidak ketemu.
function lampiran_cari_milik_siswa(mysqli $conn, int $idLampiran, int $idAspirasi, string $nis): ?array {
    $sql = "SELECT l.url_file FROM lampiran l
            JOIN aspirasi a ON a.id_aspirasi = l.id_aspirasi
            WHERE l.id_lampiran = ? AND l.id_aspirasi = ? AND a.nis = ? AND a.status = 'Menunggu'";
    $st = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($st, 'iis', $idLampiran, $idAspirasi, $nis);
    mysqli_stmt_execute($st);
    $hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $hasil ?: null;
}

// Menghapus satu baris lampiran berdasarkan id_lampiran.
// (File fisiknya dihapus terpisah di controller.)
function lampiran_hapus(mysqli $conn, int $idLampiran): bool {
    $st = mysqli_prepare($conn, 'DELETE FROM lampiran WHERE id_lampiran=?');
    mysqli_stmt_bind_param($st, 'i', $idLampiran);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Menghapus semua lampiran milik satu laporan. Dipakai saat laporan dihapus.
function lampiran_hapus_by_aspirasi(mysqli $conn, int $idAspirasi): bool {
    $st = mysqli_prepare($conn, 'DELETE FROM lampiran WHERE id_aspirasi=?');
    mysqli_stmt_bind_param($st, 'i', $idAspirasi);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}
