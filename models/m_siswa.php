<?php
// =====================================================================
// m_siswa.php
// Model untuk tabel `siswa` (NIS, nama, kelas, password, foto).
// Semua query yang menyentuh tabel siswa ada di file ini.
// Catatan: current_user() ada di config/database.php, bukan di sini.
// =====================================================================

// Mencari data siswa berdasarkan NIS (tanpa password).
// Hasil: array (nis, nama, kelas, foto), atau null kalau tidak ada.
function siswa_cari(mysqli $conn, string $nis): ?array {
    $st = mysqli_prepare($conn, 'SELECT nis, nama, kelas, foto FROM siswa WHERE nis=?');
    mysqli_stmt_bind_param($st, 's', $nis);
    mysqli_stmt_execute($st);
    $hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $hasil ?: null;
}

// Mencari data siswa lengkap dengan password, khusus untuk proses login.
// Hasil: array (nis, nama, kelas, password), atau null kalau tidak ada.
function siswa_cari_login(mysqli $conn, string $nis): ?array {
    $st = mysqli_prepare($conn, 'SELECT nis, nama, kelas, password FROM siswa WHERE nis=? LIMIT 1');
    mysqli_stmt_bind_param($st, 's', $nis);
    mysqli_stmt_execute($st);
    $hasil = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    return $hasil ?: null;
}

// Mengecek apakah NIS sudah terdaftar. Hasil: true / false.
function siswa_ada(mysqli $conn, string $nis): bool {
    $st = mysqli_prepare($conn, 'SELECT nis FROM siswa WHERE nis=? LIMIT 1');
    mysqli_stmt_bind_param($st, 's', $nis);
    mysqli_stmt_execute($st);
    mysqli_stmt_store_result($st);
    $ada = mysqli_stmt_num_rows($st) > 0;
    mysqli_stmt_close($st);
    return $ada;
}

// Menambah siswa baru (dari pendaftaran atau dari halaman admin).
function siswa_tambah(mysqli $conn, string $nis, string $nama, string $kelas, string $password): bool {
    $st = mysqli_prepare($conn, 'INSERT INTO siswa (nis, nama, kelas, password) VALUES (?,?,?,?)');
    mysqli_stmt_bind_param($st, 'ssss', $nis, $nama, $kelas, $password);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Mengubah nama dan kelas siswa (dipakai saat siswa mengedit profilnya sendiri, tanpa foto).
function siswa_update_data(mysqli $conn, string $nis, string $nama, string $kelas): bool {
    $st = mysqli_prepare($conn, 'UPDATE siswa SET nama=?, kelas=? WHERE nis=?');
    mysqli_stmt_bind_param($st, 'sss', $nama, $kelas, $nis);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Mengubah nama, kelas, dan foto profil siswa sekaligus.
// $foto berupa alamat file foto (atau null untuk mengosongkannya).
function siswa_update_data_foto(mysqli $conn, string $nis, string $nama, string $kelas, ?string $foto): bool {
    $st = mysqli_prepare($conn, 'UPDATE siswa SET nama=?, kelas=?, foto=? WHERE nis=?');
    mysqli_stmt_bind_param($st, 'ssss', $nama, $kelas, $foto, $nis);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Mengubah data siswa dari halaman admin.
// Password hanya ikut diubah kalau $password diisi (tidak null).
function siswa_update_admin(mysqli $conn, string $nis, string $nama, string $kelas, ?string $password = null): bool {
    if ($password !== null) {
        $st = mysqli_prepare($conn, 'UPDATE siswa SET nama=?, kelas=?, password=? WHERE nis=? LIMIT 1');
        mysqli_stmt_bind_param($st, 'ssss', $nama, $kelas, $password, $nis);
    } else {
        $st = mysqli_prepare($conn, 'UPDATE siswa SET nama=?, kelas=? WHERE nis=? LIMIT 1');
        mysqli_stmt_bind_param($st, 'sss', $nama, $kelas, $nis);
    }
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Menghapus foto profil siswa (kolom foto dikosongkan).
function siswa_hapus_foto(mysqli $conn, string $nis): bool {
    $st = mysqli_prepare($conn, 'UPDATE siswa SET foto=NULL WHERE nis=?');
    mysqli_stmt_bind_param($st, 's', $nis);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Menghapus data siswa.
// Siswa yang masih punya laporan TIDAK boleh dihapus (aturan ON DELETE RESTRICT di database),
// jadi fungsi ini mengecek dulu dan mengembalikan false kalau masih ada laporan.
function siswa_hapus(mysqli $conn, string $nis): bool {
    if (siswa_hitung_aspirasi($conn, $nis) > 0) return false;
    $st = mysqli_prepare($conn, 'DELETE FROM siswa WHERE nis=? LIMIT 1');
    mysqli_stmt_bind_param($st, 's', $nis);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

// Menghitung jumlah laporan milik satu siswa. Hasil: angka.
function siswa_hitung_aspirasi(mysqli $conn, string $nis): int {
    $st = mysqli_prepare($conn, 'SELECT COUNT(*) FROM aspirasi WHERE nis=?');
    mysqli_stmt_bind_param($st, 's', $nis);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $jumlah);
    mysqli_stmt_fetch($st);
    mysqli_stmt_close($st);
    return (int)$jumlah;
}

// Daftar semua siswa beserta jumlah laporannya, urut nama.
// $keyword kosong = tampilkan semua. Kalau diisi, dicari di NIS, nama, dan kelas.
// Hasil: array baris (nis, nama, kelas, jumlah_laporan).
function siswa_daftar_dengan_jumlah(mysqli $conn, string $keyword = ''): array {
    $sql = "SELECT s.nis, s.nama, s.kelas, COUNT(a.id_aspirasi) AS jumlah_laporan
            FROM siswa s
            LEFT JOIN aspirasi a ON a.nis = s.nis";
    $urutan = " GROUP BY s.nis, s.nama, s.kelas ORDER BY s.nama ASC";

    if ($keyword === '') {
        $r = mysqli_query($conn, $sql . $urutan);
        $hasil = [];
        while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
        return $hasil;
    }

    $like = '%' . $keyword . '%';
    $st = mysqli_prepare($conn, $sql . " WHERE s.nis LIKE ? OR s.nama LIKE ? OR s.kelas LIKE ?" . $urutan);
    mysqli_stmt_bind_param($st, 'sss', $like, $like, $like);
    mysqli_stmt_execute($st);
    $r = mysqli_stmt_get_result($st);
    $hasil = [];
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    mysqli_stmt_close($st);
    return $hasil;
}

// Semua siswa (nis, nama, kelas) urut nama. Dipakai untuk pilihan dropdown di form.
function siswa_semua_untuk_dropdown(mysqli $conn): array {
    $r = mysqli_query($conn, 'SELECT nis, nama, kelas FROM siswa ORDER BY nama ASC');
    $hasil = [];
    while ($baris = mysqli_fetch_assoc($r)) $hasil[] = $baris;
    return $hasil;
}
