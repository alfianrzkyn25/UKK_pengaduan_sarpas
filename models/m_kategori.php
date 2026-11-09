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
