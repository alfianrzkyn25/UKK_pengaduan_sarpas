<?php
// =====================================================================
// index.php
// Halaman awal. Mengarahkan user ke halaman yang sesuai (login atau dashboard).
// =====================================================================
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['role'])) {
    redirect($_SESSION['role'] === 'admin' ? url('admin/dashboard') : url('user/dashboard'));
}

redirect(url('auth/login'));
