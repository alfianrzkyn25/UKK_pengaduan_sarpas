<?php
/**
 * Helper schema compatibility untuk database versi lama/baru.
 * Beberapa database lama memakai kolom siswa.nama, sedangkan versi baru memakai siswa.nama_siswa.
 */
// Ambil daftar nama kolom dari sebuah tabel (SHOW COLUMNS)
function table_columns(mysqli $conn, string $table): array {
    // Wadah hasil daftar kolom
    $columns = [];
    $tableSafe = mysqli_real_escape_string($conn, $table);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `{$tableSafe}`");
    if (!$result) return $columns;
    while ($row = mysqli_fetch_assoc($result)) $columns[] = $row['Field'];
    return $columns;
}

// Cari kolom pertama yang ada dari daftar kandidat nama
function first_existing_column(array $columns, array $candidates): ?string {
    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) return $candidate;
    }
    return null;
}

// Bungkus nama kolom atau tabel dengan backtick supaya aman dipakai di SQL
function qident(string $name): string {
    return '`' . str_replace('`', '``', $name) . '`';
}

// Tentukan nama kolom NIS, nama, dan kelas di tabel siswa (menyesuaikan versi database)
function siswa_schema(mysqli $conn): array {
    // Ambil semua kolom tabel siswa
    $columns = table_columns($conn, 'siswa');
    // Cari nama kolom NIS, nama, dan kelas yang cocok
    $nis = first_existing_column($columns, ['nis','NIS','id_siswa']);
    $name = first_existing_column($columns, ['nama_siswa','nama','nama_lengkap','name']);
    $class = first_existing_column($columns, ['kelas','class','rombel']);
    // Kalau kolom wajib tidak ditemukan, hentikan dengan pesan error
    if (!$nis || !$name) {
        throw new RuntimeException('Struktur tabel siswa tidak sesuai. Dibutuhkan kolom NIS dan nama siswa (nama_siswa atau nama).');
    }
    // Kembalikan nama kolom yang sudah ketemu
    return ['nis'=>$nis, 'name'=>$name, 'class'=>$class];
}
