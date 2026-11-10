<?php
// =====================================================================
// kategori.php
// Tampilan Kategori Sarpras untuk admin: lihat, tambah, edit, dan hapus kategori.
// Kategori yang masih dipakai laporan (aspirasi) tidak bisa dihapus.
// =====================================================================
// Sambungkan database, fungsi bantuan, dan model kategori
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/m_kategori.php';
// Pastikan yang membuka halaman ini adalah admin yang login
require_role('admin');

// Data admin yang login (dipakai untuk nama di sidebar)
$user = current_user();

// Token CSRF supaya form tidak bisa dikirim dari situs lain
if (empty($_SESSION['kategori_csrf'])) {
    $_SESSION['kategori_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['kategori_csrf'];

$error = '';
$success = '';
$editing = null;                    // data kategori yang sedang diedit (null = bukan mode edit)
$old = ['nama' => '', 'ket' => ''];  // isian form tambah, dikembalikan lagi kalau ada error

// Tentukan aksi dari form: tambah, edit, atau hapus
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Proses hanya kalau form dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';

    } elseif ($action === 'tambah') {
        // Ambil isian form tambah kategori
        $nama = trim($_POST['nama'] ?? '');
        $ket  = trim($_POST['ket'] ?? '');
        $old  = ['nama' => $nama, 'ket' => $ket];

        if ($nama === '') {
            $error = 'Nama kategori wajib diisi.';
        } elseif (mb_strlen($nama) > 50) {
            $error = 'Nama kategori maksimal 50 karakter.';
        } elseif ($ket === '') {
            $error = 'Keterangan wajib diisi.';
        } elseif (mb_strlen($ket) > 50) {
            $error = 'Keterangan maksimal 50 karakter.';
        } elseif (kategori_nama_dipakai($conn, $nama)) {
            $error = 'Nama kategori tersebut sudah ada.';
        } elseif (kategori_ket_dipakai($conn, $ket)) {
            $error = 'Keterangan tersebut sudah dipakai kategori lain. Gunakan keterangan yang berbeda.';
        } else {
            try {
                if (kategori_tambah($conn, $nama, $ket)) {
                    redirect(url('admin/kategori?msg=added'));
                }
                $error = 'Gagal menambah kategori.';
            } catch (Throwable $e) {
                // Jaga-jaga kalau database menolak data (misalnya bentrok data baru yang masuk bersamaan)
                $error = 'Gagal menambah kategori: ' . $e->getMessage();
            }
        }

    } elseif ($action === 'edit') {
        // Ambil isian form edit kategori
        $id   = (int)($_POST['id_kategori'] ?? 0);
        $nama = trim($_POST['nama'] ?? '');
        $ket  = trim($_POST['ket'] ?? '');
        $editing = ['id_kategori' => $id, 'nama_kategori' => $nama, 'ket_kategori' => $ket];

        if (!kategori_ambil($conn, $id)) {
            $error = 'Data kategori yang diedit tidak ditemukan.';
        } elseif ($nama === '') {
            $error = 'Nama kategori wajib diisi.';
        } elseif (mb_strlen($nama) > 50) {
            $error = 'Nama kategori maksimal 50 karakter.';
        } elseif ($ket === '') {
            $error = 'Keterangan wajib diisi.';
        } elseif (mb_strlen($ket) > 50) {
            $error = 'Keterangan maksimal 50 karakter.';
        } elseif (kategori_nama_dipakai($conn, $nama, $id)) {
            $error = 'Nama kategori tersebut sudah dipakai kategori lain.';
        } elseif (kategori_ket_dipakai($conn, $ket, $id)) {
            $error = 'Keterangan tersebut sudah dipakai kategori lain. Gunakan keterangan yang berbeda.';
        } else {
            try {
                if (kategori_update($conn, $id, $nama, $ket)) {
                    redirect(url('admin/kategori?msg=updated'));
                }
                $error = 'Gagal memperbarui kategori.';
            } catch (Throwable $e) {
                $error = 'Gagal memperbarui kategori: ' . $e->getMessage();
            }
        }

    } elseif ($action === 'hapus') {
        $id = (int)($_POST['id_kategori'] ?? 0);
        $kat = kategori_ambil($conn, $id);

        if (!$kat) {
            $error = 'Data kategori sudah tidak ada.';
        } elseif (($jumlah = kategori_jumlah_aspirasi($conn, $id)) > 0) {
            // Kategori yang masih dipakai laporan tidak boleh dihapus (relasi di database)
            $error = 'Kategori "' . $kat['nama_kategori'] . '" tidak dapat dihapus karena masih dipakai oleh ' . $jumlah . ' aspirasi.';
        } else {
            try {
                if (kategori_hapus($conn, $id)) {
                    redirect(url('admin/kategori?msg=deleted'));
                }
                $error = 'Data kategori gagal dihapus.';
            } catch (Throwable $e) {
                // Jaga-jaga kalau ada laporan baru yang memakai kategori ini di antara cek dan hapus
                $error = 'Kategori tidak dapat dihapus: ' . $e->getMessage();
            }
        }
    }
}

// Ubah kode notifikasi dari URL menjadi pesan yang bisa dibaca
if (!empty($_GET['msg'])) {
    $messages = [
        'added'   => 'Kategori berhasil ditambahkan.',
        'updated' => 'Kategori berhasil diperbarui.',
        'deleted' => 'Kategori berhasil dihapus.'
    ];
    $success = $messages[$_GET['msg']] ?? '';
}

// Kalau ada ?edit=ID di URL, ambil data kategori itu untuk form edit
if ($editing === null && isset($_GET['edit']) && $_GET['edit'] !== '') {
    $editing = kategori_ambil($conn, (int)$_GET['edit']);
    if (!$editing) {
        $error = 'Data kategori yang ingin diedit tidak ditemukan.';
    }
}

// Form ditampilkan saat mode tambah atau edit
$tampilkanForm = $editing !== null || $action === 'tambah' || ($_GET['action'] ?? '') === 'baru';

// Daftar kategori beserta jumlah laporan tiap kategori
$rows = kategori_semua_dengan_jumlah($conn);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kategori Sarpras</title>
  <link rel="stylesheet" href="<?= e($base) ?>/assets/admin.css">
  <script src="<?= e($base) ?>/assets/ui.js"></script>
</head>

<body>
  <?php // Checkbox untuk membuka dan menutup sidebar di layar kecil ?>
  <input type="checkbox" id="menuToggle" class="menu-toggle">
  <?php // Sidebar menu navigasi admin ?>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">Pengaduan Sarpras</div>
    <ul>
      <li><a href="<?= e(url('admin/dashboard')) ?>">Dashboard</a></li>
      <li><a href="<?= e(url('admin/aspirasi')) ?>">Data Aspirasi</a></li>
      <li><a href="<?= e(url('admin/histori')) ?>">Histori Laporan</a></li>
      <li><a href="<?= e(url('admin/kategori')) ?>" class="active">Kategori Sarpras</a></li>
      <li><a href="<?= e(url('admin/siswa')) ?>">Kelola Siswa</a></li>
      <li><a href="<?= e(url('auth/logout')) ?>">Keluar</a></li>
    </ul>
    <?php // Nama admin yang login di bagian bawah sidebar ?>
    <div class="admin-user"><strong><?= e($user['nama'] ?? 'Admin') ?></strong>Administrator</div>
  </aside>
  <label for="menuToggle" class="sidebar-overlay"></label>

  <div class="main-content">
    <?php // Bagian atas halaman ?>
    <header class="topbar">
      <label for="menuToggle" class="menu-icon">&#9776;</label>
      <h1>Kategori Sarpras</h1>
    </header>

    <main class="content-area">
      <?php // Judul halaman dan tombol tambah kategori ?>
      <div class="heading">
        <div>
          <p class="eyebrow">Manajemen data</p>
          <h1>Kategori Sarpras</h1>
          <p class="muted">Tambah, ubah, dan hapus jenis sarpas yang bisa dipilih siswa saat membuat pengaduan.</p>
        </div>
        <a class="btn" href="<?= e(url('admin/kategori')) ?>?action=baru">+ Tambah Kategori</a>
      </div>

      <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>

      <?php // Form tambah atau edit kategori; hanya tampil saat dibutuhkan ?>
      <?php if ($tampilkanForm): ?>
        <section class="panel">
          <div class="panel-head">
            <h2><?= $editing ? 'Edit Kategori' : 'Tambah Kategori' ?></h2>
            <a href="<?= e(url('admin/kategori')) ?>">Tutup</a>
          </div>
          <?php // Form tambah atau edit kategori, dengan token CSRF ?>
          <form method="post" action="<?= e(url('admin/kategori')) ?>" class="admin-form">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="<?= $editing ? 'edit' : 'tambah' ?>">
            <?php if ($editing): ?><input type="hidden" name="id_kategori" value="<?= (int)$editing['id_kategori'] ?>"><?php endif; ?>

            <div class="detail form-grid-2">
              <label>Nama Kategori
                <input name="nama" required maxlength="50" value="<?= e($editing['nama_kategori'] ?? $old['nama']) ?>" placeholder="Contoh: Lapangan">
              </label>
              <label>Keterangan
                <input name="ket" required maxlength="50" value="<?= e($editing['ket_kategori'] ?? $old['ket']) ?>" placeholder="Contoh: bola/net/tiang">
              </label>
            </div>
            <div class="form-actions">
              <a class="btn secondary" href="<?= e(url('admin/kategori')) ?>">Batal</a>
              <button class="btn" type="submit"><?= $editing ? 'Simpan Perubahan' : 'Tambah Kategori' ?></button>
            </div>
          </form>
        </section>
      <?php endif; ?>

      <?php // Kotak daftar kategori ?>
      <section class="panel">
        <div class="table-title-row">
          <div>
            <h2>Daftar Kategori &amp; Jumlah Aspirasi</h2>
            <p class="muted">Kategori yang masih dipakai laporan tidak bisa dihapus.</p>
          </div>
          <span class="count-pill"><?= count($rows) ?> kategori</span>
        </div>

        <div style="overflow:auto">
          <?php // Tabel daftar kategori ?>
          <table class="table">
            <thead>
              <tr>
                <th>No</th>
                <th>Kategori</th>
                <th>Keterangan</th>
                <th>Jumlah Aspirasi</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php // Pesan kalau belum ada kategori ?>
              <?php if (!$rows): ?>
                <tr>
                  <td colspan="5" class="empty-state">Belum ada kategori.</td>
                </tr>
              <?php else: ?>
                <?php // Tampilkan satu baris per kategori ?>
                <?php foreach ($rows as $i => $k): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= e($k['nama_kategori']) ?></strong></td>
                    <td><?= e($k['ket_kategori']) ?></td>
                    <td><span class="count-pill"><?= (int)$k['jumlah'] ?></span></td>
                    <td>
                      <div class="action-group">
                        <a class="btn secondary small" href="<?= e(url('admin/kategori')) ?>?edit=<?= (int)$k['id_kategori'] ?>">Edit</a>
                        <?php // Tombol ke daftar aspirasi yang difilter sesuai nama kategori ?>
                        <a class="btn small" href="<?= e(url('admin/aspirasi')) ?>?q=<?= urlencode($k['nama_kategori']) ?>">Lihat Aspirasi</a>
                        <?php // Form hapus; dinonaktifkan kalau kategori masih dipakai laporan ?>
                        <form method="post" action="<?= e(url('admin/kategori')) ?>" onsubmit="return confirm(<?= e(json_encode('Yakin ingin menghapus kategori ' . $k['nama_kategori'] . '?', JSON_UNESCAPED_UNICODE)) ?>);">
                          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                          <input type="hidden" name="action" value="hapus">
                          <input type="hidden" name="id_kategori" value="<?= (int)$k['id_kategori'] ?>">
                          <?php if ((int)$k['jumlah'] > 0): ?>
                          <?php endif; ?>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</body>

</html>
