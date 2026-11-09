<?php
// =====================================================================
// profil.php
// Tampilan profil siswa: ubah nama, kelas, foto, dan lihat ringkasan laporan.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../../config/database.php';
// Pastikan yang membuka halaman ini adalah siswa yang login
require_role('siswa');
$user = current_user();
if (!$user) redirect(url('auth/logout')); // akun siswa sudah tidak ada di database

// ---------------------------------------------------------------------------
// Helper foto profil
// ---------------------------------------------------------------------------
const FOTO_PROFIL_DIR_REL = 'assets/uploads/profil';
const FOTO_PROFIL_MAX = 5 * 1024 * 1024; // 5MB

// Hapus file foto profil lama (hanya yang berada di folder profil).
// Hapus file foto profil lama dari folder (kalau ada)
function hapus_file_foto_profil($foto)
{
    $foto = (string)$foto;
    if (strpos($foto, FOTO_PROFIL_DIR_REL . '/') !== 0) return;
    $path = dirname(__DIR__, 2) . '/' . FOTO_PROFIL_DIR_REL . '/' . basename($foto);
    if (is_file($path)) @unlink($path);
}

// Validasi + simpan foto profil. Return path relatif (mis. assets/uploads/profil/xxx.jpg)
// atau null kalau gagal (pesan ada di $error).
// Kalau ekstensi GD tersedia, foto di-crop kotak (tengah) & dikecilkan maks 480px
// supaya ringan dimuat di setiap halaman. Kalau tidak, file disimpan apa adanya.
// Validasi lalu simpan foto profil baru (jenis, ukuran, dan isi gambar)
function simpan_foto_profil(array $file, $nis, &$error)
{
    $jenis = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];

    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $error = 'Ukuran foto terlalu besar. Maksimal 5MB.';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $error = 'Gagal mengunggah foto. Silakan coba lagi.';
        return null;
    }
    if ($file['size'] > FOTO_PROFIL_MAX) {
        $error = 'Ukuran foto maksimal 5MB.';
        return null;
    }
    $info = @getimagesize($file['tmp_name']);
    // Pastikan file benar-benar gambar dengan jenis yang diizinkan
    if (!$info || !isset($jenis[$info[2]])) {
        $error = 'Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.';
        return null;
    }

    $dir = dirname(__DIR__, 2) . '/' . FOTO_PROFIL_DIR_REL;
    // Buat folder foto profil kalau belum ada
    if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
        $error = 'Folder penyimpanan foto tidak bisa dibuat.';
        return null;
    }

    $nisAman = preg_replace('/[^A-Za-z0-9]/', '', (string)$nis);
    $nama = 'profil-' . $nisAman . '-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

    // Coba kecilkan & crop kotak dengan GD (dilewati untuk gambar sangat besar agar tidak kehabisan memori).
    $piksel = (int)$info[0] * (int)$info[1];
    if ($piksel > 0 && $piksel <= 16000000 && function_exists('imagecreatefromstring') && function_exists('imagejpeg')) {
        $src = @imagecreatefromstring((string)file_get_contents($file['tmp_name']));
        if ($src) {
            // Perbaiki orientasi foto dari HP (EXIF).
            if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $exif = @exif_read_data($file['tmp_name']);
                $o = is_array($exif) ? (int)($exif['Orientation'] ?? 1) : 1;
                $sudut = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
                if ($sudut) {
                    $rot = imagerotate($src, $sudut, 0);
                    if ($rot) {
                        imagedestroy($src);
                        $src = $rot;
                    }
                }
            }
            $w = imagesx($src);
            $h = imagesy($src);
            $sisi = min($w, $h);
            $sx = intdiv($w - $sisi, 2);
            $sy = intdiv($h - $sisi, 2);
            $out = min(480, $sisi);
            $dst = imagecreatetruecolor($out, $out);
            imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // latar putih utk PNG/WEBP transparan
            imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $out, $out, $sisi, $sisi);
            $ok = imagejpeg($dst, $dir . '/' . $nama . '.jpg', 88);
            imagedestroy($src);
            imagedestroy($dst);
            if ($ok) return FOTO_PROFIL_DIR_REL . '/' . $nama . '.jpg';
        }
    }

    // Cadangan: simpan file asli (ekstensi ditentukan dari isi file, bukan nama file).
    $namaFile = $nama . '.' . $jenis[$info[2]];
    // Pindahkan foto dari folder sementara ke folder tujuan
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $namaFile)) {
        $error = 'Foto gagal disimpan di server.';
        return null;
    }
    return FOTO_PROFIL_DIR_REL . '/' . $namaFile;
}

// ---------------------------------------------------------------------------
// Proses form
// ---------------------------------------------------------------------------
$error = '';
$form = ['nama' => $user['nama'], 'kelas' => $user['kelas']];

// Proses hanya kalau form dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tentukan aksi: simpan profil atau hapus foto
    $aksi = $_POST['aksi'] ?? 'simpan';

    if ($aksi === 'hapus_foto') {
        if (!empty($user['foto'])) {
            // Hapus foto profil dari database
            $st = mysqli_prepare($conn, 'UPDATE siswa SET foto=NULL WHERE nis=?');
            mysqli_stmt_bind_param($st, 's', $user['nis']);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);
            hapus_file_foto_profil($user['foto']);
        }
        // Berhasil hapus foto: kembali dengan notifikasi
        redirect(url('user/profil') . '?fotodihapus=1');
    }

    // Ambil nama dan kelas baru dari form
    $nama = trim($_POST['nama'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');
    $form = ['nama' => $nama, 'kelas' => $kelas];
    // Cek apakah siswa mengunggah foto baru
    $hasFile = !empty($_FILES['foto']['name']);

    if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) {
        $error = 'Ukuran foto terlalu besar. Maksimal 5MB.'; // melebihi post_max_size PHP
    } elseif ($nama === '' || $kelas === '') {
        $error = 'Nama dan kelas wajib diisi.';
    } elseif (mb_strlen($nama) < 3) {
        $error = 'Nama lengkap minimal 3 karakter.';
    } elseif (mb_strlen($nama) > 50) {
        $error = 'Nama lengkap maksimal 50 karakter.';
    } elseif (mb_strlen($kelas) > 10) {
        $error = 'Kelas maksimal 10 karakter.';
    } else {
        // Kalau ada foto baru, simpan dan dapatkan alamatnya
        $fotoBaru = $hasFile ? simpan_foto_profil($_FILES['foto'], $user['nis'], $error) : null;

        if ($error === '') {
            try {
                if ($fotoBaru !== null) {
                    // Simpan nama, kelas, dan foto baru
                    $st = mysqli_prepare($conn, 'UPDATE siswa SET nama=?,kelas=?,foto=? WHERE nis=?');
                    mysqli_stmt_bind_param($st, 'ssss', $nama, $kelas, $fotoBaru, $user['nis']);
                } else {
                    // Simpan nama dan kelas saja (foto tidak diubah)
                    $st = mysqli_prepare($conn, 'UPDATE siswa SET nama=?,kelas=? WHERE nis=?');
                    mysqli_stmt_bind_param($st, 'sss', $nama, $kelas, $user['nis']);
                }
                $ok = mysqli_stmt_execute($st);
                mysqli_stmt_close($st);
            } catch (Throwable $ex) {
                $ok = false;
            }

            if ($ok) {
                if ($fotoBaru !== null) hapus_file_foto_profil($user['foto']); // buang foto lama
                $_SESSION['nama'] = $nama;
                // Berhasil simpan: kembali dengan notifikasi
                redirect(url('user/profil') . '?saved=1');
            }
            if ($fotoBaru !== null) hapus_file_foto_profil($fotoBaru);
            $error = 'Profil gagal disimpan. Silakan coba lagi.';
        }
    }
}

$pesan = '';
if (isset($_GET['saved'])) $pesan = 'Profil berhasil diperbarui.';
if (isset($_GET['fotodihapus'])) $pesan = 'Foto profil berhasil dihapus.';
$punyaFoto = foto_profil_url($user['foto'] ?? '') !== '';

// Ringkasan status laporan milik siswa, untuk kartu statistik di halaman ini.
$ringkasan = ['Menunggu' => 0, 'Proses' => 0, 'Selesai' => 0, 'Ditolak' => 0];
// Hitung laporan per status untuk ringkasan profil
$st = mysqli_prepare($conn, 'SELECT status,COUNT(*) AS jml FROM aspirasi WHERE nis=? GROUP BY status');
mysqli_stmt_bind_param($st, 's', $user['nis']);
mysqli_stmt_execute($st);
$rs = mysqli_stmt_get_result($st);
while ($row = mysqli_fetch_assoc($rs)) {
    if (isset($ringkasan[$row['status']])) $ringkasan[$row['status']] = (int)$row['jml'];
}
mysqli_stmt_close($st);
$totalLaporan = array_sum($ringkasan);
$selesaiPct = $totalLaporan ? (int)round($ringkasan['Selesai'] / $totalLaporan * 100) : 0;
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Profil Saya</title>
    <link rel="stylesheet" href="<?= e($base) ?>/assets/admin.css">
    <link rel="stylesheet" href="<?= e($base) ?>/assets/profil.css">
    <script src="<?= e($base) ?>/assets/ui.js"></script>
</head>

<body class="profile-page">
    <input type="checkbox" id="menuToggle" class="menu-toggle">
    <?php // Sidebar menu navigasi siswa ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">Pengaduan Sarpras</div>
        <div class="sidebar-sub">Portal Siswa</div>
        <ul>
            <li><a href="<?= e(url('user/dashboard')) ?>" class="">Beranda</a></li>
            <li><a href="<?= e(url('user/buat_pengaduan')) ?>" class="">Buat Pengaduan</a></li>
            <li><a href="<?= e(url('user/laporan_saya')) ?>" class="">Laporan Saya</a></li>
            <li><a href="<?= e(url('user/profil')) ?>" class="active">Profil Saya</a></li>
            <li><a href="<?= e(url('auth/logout')) ?>">Keluar</a></li>
        </ul>
        <div class="admin-user has-avatar">
            <a class="user-chip" href="<?= e(url('user/profil')) ?>" title="Lihat profil">
                <?= avatar($user['foto'] ?? null, $user['nama'] ?? 'Siswa', 'md') ?>
                <div class="who"><strong><?= e($user['nama'] ?? 'Siswa') ?></strong>Siswa · <?= e($user['kelas'] ?? '') ?></div>
            </a>
        </div>
    </aside>
    <label for="menuToggle" class="sidebar-overlay"></label>
    <div class="main-content">
        <?php // Bagian atas halaman ?>
        <header class="topbar"><label for="menuToggle" class="menu-icon">&#9776;</label>
            <h1>Profil Saya</h1>
        </header>
        <main class="content-area">
            <?php // Judul halaman profil ?>
            <div class="heading">
                <div>
                    <p class="eyebrow">Portal Siswa</p>
                    <h1>Profil Saya</h1>
                    <p class="muted">Kelola foto dan data dirimu. Foto dan nama ini tampil di setiap pengaduan yang kamu buat.</p>
                </div>
            </div>

            <?php // Tampilkan pesan error kalau ada ?>
            <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
            <?php // Notifikasi sukses (simpan atau hapus foto) ?>
            <?php if ($pesan): ?>
                <div class="alert success js-flash">
                    <span class="flash-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5"></path>
                        </svg></span>
                    <span><?= e($pesan) ?></span>
                </div>
            <?php endif; ?>

            <?php // Kartu utama: foto, nama, dan kelas siswa ?>
            <section class="panel hero-card">
                <div class="hero-banner">
                    <span class="orb o1"></span><span class="orb o2"></span><span class="orb o3"></span>
                </div>
                <div class="hero-body">
                    <div class="avatar-ring">
                        <span id="heroAvatar"><?= avatar($user['foto'] ?? null, $user['nama'], 'xl') ?></span>
                        <label class="cam-btn" for="foto" aria-label="Ganti foto profil" title="Ganti foto profil">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 8h3l1.6-2.4A2 2 0 0 1 10.3 4.6h3.4a2 2 0 0 1 1.7 1L17 8h3a1.5 1.5 0 0 1 1.5 1.5v9A1.5 1.5 0 0 1 20 20H4a1.5 1.5 0 0 1-1.5-1.5v-9A1.5 1.5 0 0 1 4 8Z"></path>
                                <circle cx="12" cy="13.5" r="3.5"></circle>
                            </svg>
                        </label>
                    </div>
                    <div class="hero-info">
                        <h2><?= e($user['nama']) ?></h2>
                        <div class="profile-meta">
                            <span class="profile-chip">NIS · <?= e($user['nis']) ?></span>
                            <span class="profile-chip soft">Kelas · <?= e($user['kelas']) ?></span>
                            <span class="pending-tag" <?= $ringkasan['Menunggu'] ? '' : 'hidden' ?>><?= (int)$ringkasan['Menunggu'] ?> laporan menunggu</span>
                        </div>
                        <?php // Tampilkan tombol hapus foto hanya kalau ada foto ?>
                        <?php if ($punyaFoto): ?>
                            <button type="button" class="btn secondary small" id="btnHapusFoto">Hapus Foto</button>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="panel">
                <h2 style="margin-top:0;margin-bottom:16px">Laporan Saya</h2>
                <?php // Kalau belum ada laporan, tampilkan pesan kosong ?>
                <?php if ($totalLaporan === 0): ?>
                    <p class="pstat-empty">Kamu belum pernah membuat pengaduan. <a href="<?= e(url('user/buat_pengaduan')) ?>">Buat laporan pertamamu →</a></p>
                <?php else: ?>
                    <div class="pstats">
                        <div class="pstat" style="--c:#f59e0b;--i:0">
                            <span class="pstat-num"><?= (int)$ringkasan['Menunggu'] ?></span>
                            <span class="pstat-label">Menunggu</span>
                        </div>
                        <div class="pstat" style="--c:#2563eb;--i:1">
                            <span class="pstat-num"><?= (int)$ringkasan['Proses'] ?></span>
                            <span class="pstat-label">Diproses</span>
                        </div>
                        <div class="pstat" style="--c:#16a34a;--i:2">
                            <span class="pstat-num"><?= (int)$ringkasan['Selesai'] ?></span>
                            <span class="pstat-label">Selesai</span>
                        </div>
                        <div class="pstat" style="--c:#dc2626;--i:3">
                            <span class="pstat-num"><?= (int)$ringkasan['Ditolak'] ?></span>
                            <span class="pstat-label">Ditolak</span>
                        </div>
                    </div>
                    <div class="progress-wrap">
                        <div class="progress-head"><span>Tingkat penyelesaian</span><b><?= $selesaiPct ?>%</b></div>
                        <div class="progress"><span class="progress-fill" style="--w:<?= $selesaiPct ?>%"></span></div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel">
                <h2 style="margin-top:0;margin-bottom:16px">Ubah Profil</h2>
                <?php // Form ubah profil: nama, kelas, dan foto ?>
                <form method="post" class="admin-form profile-form" enctype="multipart/form-data" id="formProfil">
                    <input type="hidden" name="aksi" value="simpan">

                    <div>
                        <label for="foto">Foto Profil</label>
                        <div class="photo-field" style="margin-top:7px" id="dropZone">
                            <span id="fotoPreview"><?= avatar($user['foto'] ?? null, $form['nama'] ?: $user['nama'], 'lg') ?></span>
                            <div class="photo-input">
                                <p class="drop-title">Tarik &amp; lepas foto di sini, atau pilih file</p>
                                <input id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/gif,image/webp">
                                <span class="form-help">Format JPG/PNG/GIF/WEBP, maksimal 5MB. Foto otomatis dipotong kotak agar pas jadi foto profil.</span>
                                <span class="photo-error" id="fotoError"></span>
                            </div>
                        </div>
                    </div>

                    <label>Nama Lengkap
                        <input name="nama" maxlength="50" required value="<?= e($form['nama']) ?>" placeholder="Nama lengkap">
                    </label>

                    <div class="form-grid-2">
                        <label>NIS
                            <input value="<?= e($user['nis']) ?>" readonly tabindex="-1">
                            <span class="form-help">NIS tidak dapat diubah.</span>
                        </label>
                        <label>Kelas
                            <input name="kelas" maxlength="10" required value="<?= e($form['kelas']) ?>" placeholder="Contoh: XII RPL 2">
                        </label>
                    </div>

                    <div class="form-actions">
                        <a class="btn secondary" href="<?= e(url('user/dashboard')) ?>">Batal</a>
                        <button class="btn" type="submit" id="btnSimpan">Simpan Perubahan</button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <?php if ($punyaFoto): ?>
        <?php // Form tersembunyi untuk menghapus foto (dikirim lewat tombol) ?>
        <form method="post" id="formHapusFoto" hidden>
            <input type="hidden" name="aksi" value="hapus_foto">
        </form>
        <div class="pf-modal" id="pfModal" role="dialog" aria-modal="true" aria-labelledby="pfModalTitle">
            <div class="pf-modal-box">
                <h3 id="pfModalTitle">Hapus foto profil?</h3>
                <p>Foto akan diganti dengan inisial namamu di semua halaman.</p>
                <div class="pf-modal-actions">
                    <button type="button" class="btn secondary" id="pfModalCancel">Batal</button>
                    <button type="button" class="btn danger" id="pfModalConfirm">Ya, Hapus</button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php // Script untuk memilih foto, pratinjau, dan popup ?>
    <script>
        // Pratinjau foto sebelum disimpan (dipicu dari pilih file, drag & drop, atau ikon kamera).
        (function() {
            var input = document.getElementById('foto');
            var preview = document.getElementById('fotoPreview');
            var heroAvatar = document.getElementById('heroAvatar');
            var dropZone = document.getElementById('dropZone');
            var err = document.getElementById('fotoError');
            var maxBytes = 5 * 1024 * 1024;
            var semulaForm = preview.innerHTML;
            var semulaHero = heroAvatar.innerHTML;

            function terapkan(f) {
                err.style.display = 'none';
                if (!f) return;
                if (!/^image\/(jpeg|png|gif|webp)$/.test(f.type)) {
                    err.textContent = 'Format foto tidak didukung.';
                    err.style.display = 'block';
                    input.value = '';
                    return;
                }
                if (f.size > maxBytes) {
                    err.textContent = 'Ukuran foto maksimal 5MB.';
                    err.style.display = 'block';
                    input.value = '';
                    return;
                }
                var reader = new FileReader();
                reader.onload = function(ev) {
                    ['avatar avatar-lg', 'avatar avatar-xl'].forEach(function(cls, i) {
                        var span = document.createElement('span');
                        span.className = cls;
                        var img = document.createElement('img');
                        img.src = ev.target.result;
                        img.alt = 'Pratinjau foto profil';
                        span.appendChild(img);
                        (i === 0 ? preview : heroAvatar).innerHTML = '';
                        (i === 0 ? preview : heroAvatar).appendChild(span);
                    });
                };
                reader.readAsDataURL(f);
            }

            input.addEventListener('change', function() {
                var f = input.files && input.files[0];
                if (!f) {
                    preview.innerHTML = semulaForm;
                    heroAvatar.innerHTML = semulaHero;
                    return;
                }
                terapkan(f);
            });

            ['dragenter', 'dragover'].forEach(function(evt) {
                dropZone.addEventListener(evt, function(e) {
                    e.preventDefault();
                    dropZone.classList.add('is-drag');
                });
            });
            ['dragleave', 'dragend', 'drop'].forEach(function(evt) {
                dropZone.addEventListener(evt, function(e) {
                    e.preventDefault();
                    dropZone.classList.remove('is-drag');
                });
            });
            dropZone.addEventListener('drop', function(e) {
                var f = e.dataTransfer.files && e.dataTransfer.files[0];
                if (!f) return;
                try {
                    input.files = e.dataTransfer.files;
                } catch (ex) {}
                terapkan(f);
            });
        })();

        // Tombol simpan menampilkan indikator memuat saat form dikirim.
        (function() {
            var form = document.getElementById('formProfil');
            var btn = document.getElementById('btnSimpan');
            form.addEventListener('submit', function() {
                btn.classList.add('is-loading');
                btn.textContent = ' Menyimpan…';
            });
        })();

        // Notifikasi sukses hilang otomatis.
        (function() {
            var flash = document.querySelector('.js-flash');
            if (!flash) return;
            setTimeout(function() {
                flash.classList.add('is-leaving');
            }, 3200);
        })();

        // Modal konfirmasi hapus foto.
        (function() {
            var modal = document.getElementById('pfModal');
            var btnOpen = document.getElementById('btnHapusFoto');
            if (!modal || !btnOpen) return;
            var cancel = document.getElementById('pfModalCancel');
            var confirmBtn = document.getElementById('pfModalConfirm');

            function tutup() {
                modal.classList.remove('open');
            }
            btnOpen.addEventListener('click', function() {
                modal.classList.add('open');
            });
            cancel.addEventListener('click', tutup);
            modal.addEventListener('click', function(e) {
                if (e.target === modal) tutup();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') tutup();
            });
            confirmBtn.addEventListener('click', function() {
                confirmBtn.classList.add('is-loading');
                document.getElementById('formHapusFoto').submit();
            });
        })();
    </script>
</body>

</html>
