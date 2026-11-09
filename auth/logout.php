<?php
// =====================================================================
// logout.php
// Logout: menghapus session login lalu kembali ke halaman login.
// =====================================================================
require_once __DIR__ . '/../config/database.php';
// Kosongkan semua data session (data login)
$_SESSION = [];
// Kalau session memakai cookie, hapus juga cookie-nya di browser
if (ini_get('session.use_cookies')) {
    // Ambil pengaturan cookie session yang sedang dipakai
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
// Hapus session dari server
session_destroy();
// Kembali ke halaman login
redirect(url('auth/login'));
