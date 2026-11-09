<?php
// =====================================================================
// m_koneksi.php
// Tempat mengatur koneksi ke database MySQL.
// Sudah disetting untuk dijalankan di PC/laptop (XAMPP / Laragon).
// Kalau nanti dipindah ke hosting, cukup ubah 4 variabel di bawah ini.
// =====================================================================

$server   = 'localhost';          // alamat database; di PC sendiri pakai localhost
$username = 'root';              // user default XAMPP/Laragon
$pass     = '';                  // password default XAMPP/Laragon masih kosong
$database = 'pengaduan_sarpas';  // nama database (sesuai hasil import pengaduan_sarpas.sql)

// Buka koneksi ke MySQL
$conn = mysqli_connect($server, $username, $pass, $database);

// Kalau gagal konek, program dihentikan dan pesan errornya ditampilkan
if (!$conn) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

// Atur encoding ke utf8mb4 supaya huruf khusus dan emoji tidak rusak
mysqli_set_charset($conn, 'utf8mb4');
