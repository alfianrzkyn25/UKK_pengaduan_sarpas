<?php
// =====================================================================
// database.php
// File pusat yang dipanggil hampir di semua halaman.
// Isinya: mulai session, menyambungkan database (lewat models/m_koneksi.php),
// dan kumpulan fungsi bantuan (e, redirect, url, cek login, avatar, dll).
// =====================================================================

// Mulai session (tempat menyimpan data login user) kalau belum aktif
if (session_status() === PHP_SESSION_NONE) session_start();

// Ambil variabel koneksi ($conn) dari models/m_koneksi.php.
// Cukup atur server/user/password/nama DB di file itu saja, tidak perlu di sini.
require_once __DIR__ . '/../models/m_koneksi.php';

// Membersihkan teks sebelum ditampilkan di HTML, supaya tidak bisa disisipi kode (XSS)
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

// Pindah ke halaman lain lalu langsung menghentikan script
function redirect($path) { header('Location: ' . $path); exit; }

// $base = alamat folder project dari root server, misalnya "/pengaduan_sarana".
// Dihitung otomatis dari DOCUMENT_ROOT, jadi tetap benar dari file manapun
// dan tidak peduli apakah URL sedang "dipercantik" oleh .htaccess.
function base_url() {
    $root = str_replace('\\', '/', dirname(__DIR__)); // lokasi folder project di disk
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/')) : '';
    $rel = ($docRoot !== '' && strpos($root, $docRoot) === 0) ? substr($root, strlen($docRoot)) : '';
    $rel = '/' . trim($rel, '/');
    return $rel === '/' ? '' : $rel;
}
$base = base_url();

// Membuat URL yang rapi (tanpa .php dan tanpa folder view/).
// Contoh: url('admin/dashboard') menghasilkan /pengaduan_sarana/admin/dashboard
function url($path) {
    global $base;
    return $base . '/' . ltrim($path, '/');
}

// Alamat halaman login (dipakai saat user belum login / salah akses)
function login_redirect_path() { return url('auth/login'); }

// Halaman yang butuh login: kalau belum login, dilempar ke halaman login
function require_login() { if (empty($_SESSION['role'])) redirect(login_redirect_path()); }

// Halaman khusus role tertentu (admin atau siswa). Role lain dilempar ke login.
function require_role($role) { require_login(); if ($_SESSION['role'] !== $role) redirect(login_redirect_path()); }

// Mengambil data user yang sedang login dari database.
// Siswa: nis, nama, kelas, foto. Admin: id_admin, username, nama.
function current_user() {
    global $conn;
    if (empty($_SESSION['role'])) return null;

    // Kalau yang login adalah siswa
    if ($_SESSION['role'] === 'siswa' && !empty($_SESSION['nis'])) {
        $st = mysqli_prepare($conn, 'SELECT nis,nama,kelas,foto FROM siswa WHERE nis=?');
        mysqli_stmt_bind_param($st, 's', $_SESSION['nis']);
        mysqli_stmt_execute($st);
        $r = mysqli_stmt_get_result($st);
        $u = mysqli_fetch_assoc($r);
        mysqli_stmt_close($st);
        return $u;
    }

    // Kalau yang login adalah admin
    if ($_SESSION['role'] === 'admin' && !empty($_SESSION['id_admin'])) {
        $id = (int)$_SESSION['id_admin'];
        $st = mysqli_prepare($conn, 'SELECT id_admin,username,nama FROM admin WHERE id_admin=?');
        mysqli_stmt_bind_param($st, 'i', $id);
        mysqli_stmt_execute($st);
        $r = mysqli_stmt_get_result($st);
        $u = mysqli_fetch_assoc($r);
        mysqli_stmt_close($st);
        return $u;
    }
    return null;
}

// Mengambil id admin pertama di tabel admin.
// Dipakai sebagai "admin penanggung jawab" saat siswa membuat laporan baru.
function default_admin_id() {
    global $conn;
    $r = mysqli_query($conn, 'SELECT id_admin FROM admin ORDER BY id_admin ASC LIMIT 1');
    $row = $r ? mysqli_fetch_assoc($r) : null;
    return $row ? (int)$row['id_admin'] : 0;
}

// ---------------------------------------------------------------------------
// FOTO PROFIL SISWA
// ---------------------------------------------------------------------------

// Memastikan kolom siswa.foto ada di database.
// Kalau database lama belum punya kolom ini, kolomnya ditambahkan otomatis (sekali saja).
function ensure_siswa_foto_column() {
    global $conn;
    $ada = false;
    try {
        $r = mysqli_query($conn, "SHOW COLUMNS FROM `siswa` LIKE 'foto'");
        if ($r && mysqli_num_rows($r) === 0) {
            mysqli_query($conn, "ALTER TABLE `siswa` ADD COLUMN `foto` VARCHAR(255) NULL DEFAULT NULL");
            $r = mysqli_query($conn, "SHOW COLUMNS FROM `siswa` LIKE 'foto'");
        }
        $ada = $r && mysqli_num_rows($r) > 0;
    } catch (Throwable $ex) {
        $ada = false;
    }
    if (!$ada) die('Kolom "foto" di tabel siswa belum ada dan gagal dibuat otomatis. Import ulang pengaduan_sarpas.sql atau jalankan: ALTER TABLE siswa ADD COLUMN foto VARCHAR(255) NULL;');
}
ensure_siswa_foto_column();

// Mengambil inisial nama untuk avatar cadangan, misalnya "Fajar Nur" menjadi "FN"
function avatar_initials($nama) {
    $nama = trim((string)$nama);
    if ($nama === '') return '?';
    $parts = preg_split('/\s+/u', $nama);
    $ini = mb_substr($parts[0], 0, 1, 'UTF-8');
    if (count($parts) > 1) $ini .= mb_substr(end($parts), 0, 1, 'UTF-8');
    return mb_strtoupper($ini, 'UTF-8');
}

// Mengembalikan alamat (URL) foto profil.
// Kalau foto belum ada atau file-nya hilang, hasilnya string kosong.
function foto_profil_url($foto) {
    global $base;
    $foto = ltrim((string)$foto, '/');
    if ($foto === '' || strpos($foto, '..') !== false) return '';
    if (!is_file(dirname(__DIR__) . '/' . $foto)) return '';
    return $base . '/' . $foto;
}

// Membuat HTML avatar bulat.
// Kalau ada foto profil, foto yang ditampilkan. Kalau tidak, inisial nama.
// $size pilihan: sm (32px), md (42px), lg (104px), xl (112px). Butuh assets/profil.css.
function avatar($foto, $nama, $size = 'md') {
    $src = foto_profil_url($foto);
    $cls = 'avatar avatar-' . preg_replace('/[^a-z]/', '', (string)$size);
    if ($src !== '') {
        return '<span class="' . $cls . '"><img src="' . e($src) . '" alt="Foto ' . e($nama) . '"></span>';
    }
    return '<span class="' . $cls . ' avatar-fallback" title="' . e($nama) . '">' . e(avatar_initials($nama)) . '</span>';
}
