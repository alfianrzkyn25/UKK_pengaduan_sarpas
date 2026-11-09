<?php
// =====================================================================
// m_admin.php
// Model untuk tabel `admin` (akun pengelola sistem pengaduan).
// Semua query yang menyentuh tabel admin ada di file ini.
// =====================================================================

// Mencari admin berdasarkan username, lengkap dengan password.
// Dipakai saat proses login. Hasil: array, atau null kalau tidak ada.
function admin_cari_login(mysqli $conn, string $username): ?array {
    $st = mysqli_prepare($conn, 'SELECT id_admin, username, nama, password FROM admin WHERE username=? LIMIT 1');
    mysqli_stmt_bind_param($st, 's', $username);
    mysqli_stmt_execute($st);
    $hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $hasil ?: null;
}

// Mencari admin berdasarkan id_admin (tanpa password).
// Hasil: array (id_admin, username, nama), atau null kalau tidak ada.
function admin_cari_by_id(mysqli $conn, int $idAdmin): ?array {
    $st = mysqli_prepare($conn, 'SELECT id_admin, username, nama FROM admin WHERE id_admin=?');
    mysqli_stmt_bind_param($st, 'i', $idAdmin);
    mysqli_stmt_execute($st);
    $hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $hasil ?: null;
}

// Mengecek apakah sebuah username sudah dipakai admin.
// Dipakai saat mendaftarkan siswa, supaya username siswa tidak bentrok dengan admin.
function admin_username_ada(mysqli $conn, string $username): bool {
    $st = mysqli_prepare($conn, 'SELECT id_admin FROM admin WHERE username=? LIMIT 1');
    mysqli_stmt_bind_param($st, 's', $username);
    mysqli_stmt_execute($st);
    mysqli_stmt_store_result($st);
    $ada = mysqli_stmt_num_rows($st) > 0;
    mysqli_stmt_close($st);
    return $ada;
}

// Mengambil id admin pertama (urutan id terkecil).
// Dipakai sebagai admin penanggung jawab saat siswa membuat laporan baru.
// Hasil: angka id admin, atau 0 kalau belum ada admin sama sekali.
function admin_pertama_id(mysqli $conn): int {
    $r = mysqli_query($conn, 'SELECT id_admin FROM admin ORDER BY id_admin ASC LIMIT 1');
    $baris = $r ? mysqli_fetch_assoc($r) : null;
    return $baris ? (int)$baris['id_admin'] : 0;
}
