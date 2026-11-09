<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Daftar Akun Siswa - Pengaduan Sarpras</title>
<link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<?php // Pembungkus halaman pendaftaran ?>
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
        <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
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
              <?php // Input NIS: angka saja, 4 sampai 10 digit ?>
              <input id="nis" name="nis" required inputmode="numeric" pattern="[0-9]{4,10}" maxlength="10" value="<?=e($old['nis'])?>" placeholder="Contoh: 2425007">
            </div>
          </div>
          <div class="field">
            <label for="kelas">Kelas</label>
            <div class="input-group">
              <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10 12 4 2 10l10 6 10-6Z"/><path d="M6 12v5c0 1.1 2.7 2 6 2s6-.9 6-2v-5"/></svg></span>
              <?php // Input kelas ?>
              <input id="kelas" name="kelas" required maxlength="10" value="<?=e($old['kelas'])?>" placeholder="Contoh: XII RPL 2">
            </div>
          </div>
        </div>

        <div class="field">
          <label for="nama">Nama Lengkap</label>
          <div class="input-group">
            <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            <?php // Input nama lengkap ?>
            <input id="nama" name="nama" required maxlength="50" value="<?=e($old['nama'])?>" placeholder="Nama sesuai data sekolah">
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label for="password">Password</label>
            <div class="input-group has-toggle">
              <span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
              <?php // Input password (minimal 4 karakter) ?>
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
              <?php // Input ulangi password untuk dicocokkan ?>
              <input id="konfirmasi" type="password" name="konfirmasi" required minlength="4" maxlength="25" autocomplete="new-password" placeholder="Ulangi password">
              <button type="button" class="toggle-eye" data-target="konfirmasi" aria-label="Tampilkan password">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              </button>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-primary">Daftar Sekarang</button>
      </form>

      <div class="role-hint">
        <b>Khusus untuk Siswa.</b> Akun Admin dibuat langsung oleh pengelola sistem dan tidak bisa didaftarkan lewat halaman ini.
      </div>
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
