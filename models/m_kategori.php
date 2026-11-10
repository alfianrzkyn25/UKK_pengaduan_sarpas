<?php
// =====================================================================
// m_kategori.php
// Model untuk tabel `kategori` (jenis sarpas: listrik, toilet, kebersihan, dll).
// Semua query yang menyentuh tabel kategori ada di file ini.
// =====================================================================

// Semua kategori beserta jumlah laporan di tiap kategori, urut nama.
// Hasil: array baris (id_kategori, nama_kategori, ket_kategori, jumlah).
function kategori_semua_dengan_jumlah(mysqli $conn): array {
    $sql = "SELECT k.id_kategori, k.nama_kategori, k.ket_kategori, COUNT(a.id_aspirasi) AS jumlah
            FROM kategori k
            LEFT JOIN aspirasi a ON a.id_kategori = k.id_kategori
            GROUP BY k.id_kategori, k.nama_kategori, k.ket_kategori
            ORDER BY k.nama_kategori ASC";
    $r = mysqli_query($conn, $sql);
    $hasil = [];
    if (!$r) return $hasil;
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    return $hasil;
}

// Semua kategori (id, nama, keterangan) urut nama. Dipakai untuk pilihan dropdown di form.
function kategori_semua(mysqli $conn): array {
    $r = mysqli_query($conn, 'SELECT id_kategori, nama_kategori, ket_kategori FROM kategori ORDER BY nama_kategori ASC');
    $hasil = [];
    if (!$r) return $hasil;
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    return $hasil;
}

// Ambil satu kategori berdasarkan id. Hasil: array (id_kategori, nama_kategori, ket_kategori), atau null.
function kategori_ambil(mysqli $conn, int $idKategori): ?array {
    $st = mysqli_prepare($conn, 'SELECT id_kategori, nama_kategori, ket_kategori FROM kategori WHERE id_kategori=? LIMIT 1');
    mysqli_stmt_bind_param($st, 'i', $idKategori);
    mysqli_stmt_execute($st);
    $hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $hasil ?: null;
}

// Cek apakah nama kategori sudah dipakai kategori lain.
// $kecualiId = id kategori yang sedang diedit, supaya namanya sendiri tidak dihitung bentrok.
// Pencocokan mengikuti collation kolom (default tidak membedakan huruf besar/kecil).
function kategori_nama_dipakai(mysqli $conn, string $nama, int $kecualiId = 0): bool {
    $st = mysqli_prepare($conn, 'SELECT id_kategori FROM kategori WHERE nama_kategori=? AND id_kategori<>? LIMIT 1');
    mysqli_stmt_bind_param($st, 'si', $nama, $kecualiId);
    mysqli_stmt_execute($st);
    mysqli_stmt_store_result($st);
    $ada = mysqli_stmt_num_rows($st) > 0;
    mysqli_stmt_close($st);
    return $ada;
}

// Cek apakah keterangan kategori sudah dipakai kategori lain.
// Kolom ket_kategori di database UNIQUE, jadi keterangan yang sama tidak boleh dobel.
function kategori_ket_dipakai(mysqli $conn, string $ket, int $kecualiId = 0): bool {
    $st = mysqli_prepare($conn, 'SELECT id_kategori FROM kategori WHERE ket_kategori=? AND id_kategori<>? LIMIT 1');
    mysqli_stmt_bind_param($st, 'si', $ket, $kecualiId);
    mysqli_stmt_execute($st);
    mysqli_stmt_store_result($st);
    $ada = mysqli_stmt_num_rows($st) > 0;
    mysqli_stmt_close($st);
    return $ada;
}

// Jumlah laporan (aspirasi) yang memakai satu kategori.
// Dipakai untuk mencegah penghapusan kategori yang masih dipakai.
function kategori_jumlah_aspirasi(mysqli $conn, int $idKategori): int {
    $st = mysqli_prepare($conn, 'SELECT COUNT(*) FROM aspirasi WHERE id_kategori=?');
    mysqli_stmt_bind_param($st, 'i', $idKategori);
    mysqli_stmt_execute($st);
    $baris = mysqli_fetch_row(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return (int)($baris[0] ?? 0);
}

// Menambah kategori baru. Hasil: true kalau berhasil.
function kategori_tambah(mysqli $conn, string $nama, string $ket): bool {
    $st = mysqli_prepare($conn, 'INSERT INTO kategori (nama_kategori, ket_kategori) VALUES (?,?)');
    mysqli_stmt_bind_param($st, 'ss', $nama, $ket);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Mengubah nama dan keterangan kategori. Hasil: true kalau query berhasil.
function kategori_update(mysqli $conn, int $idKategori, string $nama, string $ket): bool {
    $st = mysqli_prepare($conn, 'UPDATE kategori SET nama_kategori=?, ket_kategori=? WHERE id_kategori=? LIMIT 1');
    mysqli_stmt_bind_param($st, 'ssi', $nama, $ket, $idKategori);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Menghapus kategori. Pemanggil wajib cek kategori_jumlah_aspirasi() dulu (FK tanpa ON DELETE).
function kategori_hapus(mysqli $conn, int $idKategori): bool {
    $st = mysqli_prepare($conn, 'DELETE FROM kategori WHERE id_kategori=? LIMIT 1');
    mysqli_stmt_bind_param($st, 'i', $idKategori);
    $ok = mysqli_stmt_execute($st);
    $terhapus = $ok && mysqli_stmt_affected_rows($st) > 0;
    mysqli_stmt_close($st);
    return $terhapus;
}
