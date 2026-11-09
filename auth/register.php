<?php
// =====================================================================
// register.php
// Halaman pendaftaran akun siswa baru.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../config/database.php';

// Kalau sudah login, langsung pindah ke dashboard
if (!empty($_SESSION['role'])) {
    redirect($_SESSION['role'] === 'admin' ? url('admin/dashboard') : url('user/dashboard'));
}

// Variabel pesan error dan isian form
$error = '';
$old = ['nis' => '', 'nama' => '', 'kelas' => ''];

// Proses hanya kalau form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil isian dari form
    $nis         = trim($_POST['nis'] ?? '');
    $nama        = trim($_POST['nama'] ?? '');
    $kelas       = trim($_POST['kelas'] ?? '');
    $password    = $_POST['password'] ?? '';
    $konfirmasi  = $_POST['konfirmasi'] ?? '';
    // Simpan isian supaya tidak hilang kalau ada error
    $old = ['nis' => $nis, 'nama' => $nama, 'kelas' => $kelas];

    // Validasi: semua kolom wajib diisi
    if ($nis === '' || $nama === '' || $kelas === '' || $password === '' || $konfirmasi === '') {
        $error = 'Semua data wajib diisi.';
    // Format NIS: angka 4 sampai 10 digit
    } elseif (!preg_match('/^[0-9]{4,10}$/', $nis)) {
        $error = 'NIS harus berupa angka (4-10 digit).';
    // Panjang nama minimal 3 karakter
    } elseif (mb_strlen($nama) < 3) {
        $error = 'Nama lengkap minimal 3 karakter.';
    // Panjang password minimal 4 karakter
    } elseif (strlen($password) < 4) {
        $error = 'Password minimal 4 karakter.';
    // Password dan konfirmasinya harus sama
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama dengan password.';
    } else {
        // Cek apakah NIS sudah terdaftar
        $st = mysqli_prepare($conn, 'SELECT nis FROM siswa WHERE nis=? LIMIT 1');
        mysqli_stmt_bind_param($st, 's', $nis);
        mysqli_stmt_execute($st);
        mysqli_stmt_store_result($st);
        $exists = mysqli_stmt_num_rows($st) > 0;
        mysqli_stmt_close($st);

        // Cek apakah NIS bentrok dengan username admin
        $adminClash = false;
        if (!$exists) {
            // Cari username admin yang sama dengan NIS
            $sa = mysqli_prepare($conn, 'SELECT id_admin FROM admin WHERE username=? LIMIT 1');
            mysqli_stmt_bind_param($sa, 's', $nis);
            mysqli_stmt_execute($sa);
            mysqli_stmt_store_result($sa);
            $adminClash = mysqli_stmt_num_rows($sa) > 0;
            mysqli_stmt_close($sa);
        }

        // Tolak kalau NIS sudah terdaftar atau bentrok dengan admin
        if ($exists) {
            $error = 'NIS ini sudah terdaftar. Silakan login.';
        } elseif ($adminClash) {
            $error = 'NIS ini tidak dapat digunakan. Silakan hubungi admin.';
        } else {
            // Simpan akun siswa baru
            $stmt = mysqli_prepare($conn, 'INSERT INTO siswa (nis, nama, kelas, password) VALUES (?,?,?,?)');
            mysqli_stmt_bind_param($stmt, 'ssss', $nis, $nama, $kelas, $password);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            if ($ok) {
                // Berhasil: pindah ke halaman login dengan notifikasi
                redirect(url('auth/login') . '?registered=1');
            }
            // Kalau gagal disimpan, tampilkan pesan error
            $error = 'Registrasi gagal disimpan: ' . mysqli_error($conn);
        }
    }
}

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar Akun Siswa - Pengaduan Sarpras</title>
<link rel="stylesheet" href="<?= e($base) ?>/assets/auth.css">
<script src="<?= e($base) ?>/assets/ui.js"></script>
</head>
<body>
<main class="auth-wrap">
  <section class="auth-card">

    <?php // Bagian kiri: penjelasan manfaat mendaftar ?>
    <div class="auth-side">
      <div class="side-top">
        <div class="side-logo">PS</div>
        <h1>Bergabung sebagai Siswa</h1>
        <p>Buat akun untuk mulai melaporkan kerusakan fasilitas sekolah dan memantau progres perbaikannya.</p>
      </div>
      <div class="side-features">
        <div class="side-feature">
          <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10M18 20V4M6 20v-4"/></svg></span>
          <span>Ajukan pengaduan lengkap dengan foto bukti</span>
        </div>
        <div class="side-feature">
          <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg></span>
          <span>Pantau status: Menunggu, Proses, hingga Selesai</span>
        </div>
        <div class="side-feature">
          <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
          <span>Proses pendaftaran cepat, tanpa ribet</span>
        </div>
      </div>
      <div class="side-bottom">&copy; <?=date('Y')?> Pengaduan Sarpras · Sistem Internal Sekolah</div>
    </div>

    <?php // Bagian kanan: form pendaftaran ?>
    <div class="auth-form-panel">
      <div class="form-head">
        <h2>Daftar Akun Siswa</h2>
        <p>Sudah punya akun? <a href="<?= e(url('auth/login')) ?>">Masuk di sini</a></p>
      </div>

      <?php // Tampilkan pesan error kalau ada ?>
      <?php if ($error): ?>
        <div class="alert error">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v5M12 16h.01"/></svg>
          <span><?=e($error)?></span>
        </div>
      <?php endif; ?>

      <?php // Form pendaftaran akun siswa ?>
      <form method="post" class="auth-form">
        <div class="field-row">
          <div class="field">
            <label for="nis">NIS</label>
            <div class="input-group">
              <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9h6M7 13h10M7 17h4"/></svg></span>
              <input id="nis" name="nis" required inputmode="numeric" pattern="[0-9]{4,10}" maxlength="10" value="<?=e($old['nis'])?>" placeholder="Contoh: 2425007">
            </div>
          </div>
          <div class="field">
            <label for="kelas">Kelas</label>
            <div class="input-group">
              <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10 12 4 2 10l10 6 10-6Z"/><path d="M6 12v5c0 1.1 2.7 2 6 2s6-.9 6-2v-5"/></svg></span>
              <input id="kelas" name="kelas" required maxlength="10" value="<?=e($old['kelas'])?>" placeholder="Contoh: XII RPL 2">
            </div>
          </div>
        </div>

        <div class="field">
          <label for="nama">Nama Lengkap</label>
          <div class="input-group">
            <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            <input id="nama" name="nama" required maxlength="50" value="<?=e($old['nama'])?>" placeholder="Nama sesuai data sekolah">
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label for="password">Password</label>
            <div class="input-group has-toggle">
              <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
              <input id="password" type="password" name="password" required minlength="4" maxlength="25" autocomplete="new-password" placeholder="Minimal 4 karakter">
              <button type="button" class="toggle-eye" data-target="password" aria-label="Tampilkan password">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>
          <div class="field">
            <label for="konfirmasi">Konfirmasi Password</label>
            <div class="input-group has-toggle">
              <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
              <input id="konfirmasi" type="password" name="konfirmasi" required minlength="4" maxlength="25" autocomplete="new-password" placeholder="Ulangi password">
              <button type="button" class="toggle-eye" data-target="konfirmasi" aria-label="Tampilkan password">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-primary">Daftar Sekarang</button>
      </form>

    </div>

  </section>
</main>

<?php // Tombol mata untuk menampilkan atau menyembunyikan password ?>
<script>
document.querySelectorAll('.toggle-eye').forEach(function(btn){
  btn.addEventListener('click', function(){
    var input = document.getElementById(btn.getAttribute('data-target'));
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
  });
});
</script>
</body>
</html>
