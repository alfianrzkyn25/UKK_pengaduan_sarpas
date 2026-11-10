<?php
// =====================================================================
// siswa.php
// Tampilan Kelola Siswa: cari, tambah, edit, dan hapus data siswa.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../../config/database.php';
// Pastikan yang membuka halaman ini adalah admin yang login
require_role('admin');

$user = current_user();

if (empty($_SESSION['siswa_csrf'])) {
    $_SESSION['siswa_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['siswa_csrf'];

$error = '';
$success = '';
$editing = null;
$old = ['nis' => '', 'nama' => '', 'kelas' => ''];

// Hitung jumlah laporan milik satu siswa
function siswa_count_laporan(mysqli $conn, string $nis): int
{
    $st = mysqli_prepare($conn, 'SELECT COUNT(*) FROM aspirasi WHERE nis=?');
    if (!$st) return 0;
    mysqli_stmt_bind_param($st, 's', $nis);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $count);
    mysqli_stmt_fetch($st);
    mysqli_stmt_close($st);
    return (int)$count;
}

// Cek apakah username sudah dipakai admin (mencegah bentrok dengan NIS)
function admin_username_exists(mysqli $conn, string $nis): bool
{
    $st = mysqli_prepare($conn, 'SELECT id_admin FROM admin WHERE username=? LIMIT 1');
    if (!$st) return false;
    mysqli_stmt_bind_param($st, 's', $nis);
    mysqli_stmt_execute($st);
    mysqli_stmt_store_result($st);
    $exists = mysqli_stmt_num_rows($st) > 0;
    mysqli_stmt_close($st);
    return $exists;
}

// Cek apakah NIS siswa sudah terdaftar
function siswa_exists(mysqli $conn, string $nis): bool
{
    $st = mysqli_prepare($conn, 'SELECT nis FROM siswa WHERE nis=? LIMIT 1');
    if (!$st) return false;
    mysqli_stmt_bind_param($st, 's', $nis);
    mysqli_stmt_execute($st);
    mysqli_stmt_store_result($st);
    $exists = mysqli_stmt_num_rows($st) > 0;
    mysqli_stmt_close($st);
    return $exists;
}

// Ambil data satu siswa (nis, nama, kelas) untuk form edit
function ambil_siswa(mysqli $conn, string $nis): ?array
{
    $st = mysqli_prepare($conn, 'SELECT nis,nama,kelas FROM siswa WHERE nis=? LIMIT 1');
    if (!$st) return null;
    mysqli_stmt_bind_param($st, 's', $nis);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $rowNis, $nama, $kelas);
    $found = mysqli_stmt_fetch($st);
    mysqli_stmt_close($st);

    if (!$found) return null;
    return ['nis' => $rowNis, 'nama' => $nama, 'kelas' => $kelas, 'old_nis' => $rowNis];
}

// Tentukan aksi dari form: tambah, edit, atau hapus
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Proses hanya kalau form dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cek token CSRF supaya form tidak bisa dikirim dari situs lain
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $error = 'Permintaan tidak valid. Silakan muat ulang halaman.';
    } elseif ($action === 'tambah') {
        // Ambil isian form tambah siswa
        $nis = trim($_POST['nis'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $password = $_POST['password'] ?? '';
        $old = compact('nis', 'nama', 'kelas');

        if ($nis === '' || $nama === '' || $kelas === '' || $password === '') {
            $error = 'Semua data wajib diisi untuk menambah siswa.';
        // Validasi format NIS: angka 4 sampai 10 digit
        } elseif (!preg_match('/^[0-9]{4,10}$/', $nis)) {
            $error = 'NIS harus berupa angka 4-10 digit.';
        } elseif (mb_strlen($nama) < 3) {
            $error = 'Nama siswa minimal 3 karakter.';
        } elseif (mb_strlen($kelas) > 10) {
            $error = 'Kelas maksimal 10 karakter.';
        } elseif (strlen($password) < 4) {
            $error = 'Password minimal 4 karakter.';
        } elseif (siswa_exists($conn, $nis)) {
            $error = 'NIS tersebut sudah terdaftar.';
        } elseif (admin_username_exists($conn, $nis)) {
            $error = 'NIS tersebut tidak dapat digunakan karena sama dengan username admin.';
        } else {
            // Simpan siswa baru
            $st = mysqli_prepare($conn, 'INSERT INTO siswa (nis,nama,kelas,password) VALUES (?,?,?,?)');
            if (!$st) {
                $error = 'Gagal menyiapkan proses tambah siswa: ' . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($st, 'ssss', $nis, $nama, $kelas, $password);
                $ok = mysqli_stmt_execute($st);
                $dbError = mysqli_stmt_error($st);
                mysqli_stmt_close($st);

                if ($ok) {
                    // Berhasil tambah: kembali dengan notifikasi
                    redirect(url('admin/siswa?msg=added'));
                }
                $error = 'Gagal menambah siswa: ' . $dbError;
            }
        }
    } elseif ($action === 'edit') {
        // NIS dijadikan identitas tetap. Yang diubah hanya nama, kelas, dan password.
        // Ambil NIS lama, dipakai untuk mencari data yang diedit
        $oldNis = trim($_POST['old_nis'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $kelas = trim($_POST['kelas'] ?? '');
        $password = $_POST['password'] ?? '';

        $editing = ['nis' => $oldNis, 'old_nis' => $oldNis, 'nama' => $nama, 'kelas' => $kelas];

        if ($oldNis === '' || $nama === '' || $kelas === '') {
            $error = 'Nama dan kelas wajib diisi.';
        // Validasi NIS lama dan pastikan siswa itu ada
        } elseif (!preg_match('/^[0-9]{4,10}$/', $oldNis) || !siswa_exists($conn, $oldNis)) {
            $error = 'Data siswa yang diedit tidak ditemukan.';
        } elseif (mb_strlen($nama) < 3) {
            $error = 'Nama siswa minimal 3 karakter.';
        } elseif (mb_strlen($kelas) > 10) {
            $error = 'Kelas maksimal 10 karakter.';
        } elseif ($password !== '' && strlen($password) < 4) {
            $error = 'Password baru minimal 4 karakter.';
        } else {
            if ($password !== '') {
                // Edit siswa; password hanya diubah kalau diisi
                $st = mysqli_prepare($conn, 'UPDATE siswa SET nama=?,kelas=?,password=? WHERE nis=? LIMIT 1');
                if ($st) {
                    mysqli_stmt_bind_param($st, 'ssss', $nama, $kelas, $password, $oldNis);
                }
            } else {
                $st = mysqli_prepare($conn, 'UPDATE siswa SET nama=?,kelas=? WHERE nis=? LIMIT 1');
                if ($st) {
                    mysqli_stmt_bind_param($st, 'sss', $nama, $kelas, $oldNis);
                }
            }

            if (!$st) {
                $error = 'Gagal menyiapkan proses edit siswa: ' . mysqli_error($conn);
            } else {
                $ok = mysqli_stmt_execute($st);
                $dbError = mysqli_stmt_error($st);
                $affected = mysqli_stmt_affected_rows($st);
                mysqli_stmt_close($st);

                // affected_rows = 0 tetap dianggap berhasil apabila data memang sama.
                if ($ok) {
                    // Berhasil edit: kembali dengan notifikasi
                    redirect(url('admin/siswa?msg=updated'));
                }
                $error = 'Gagal memperbarui siswa: ' . $dbError;
            }
        }
    } elseif ($action === 'hapus') {
        $nis = trim($_POST['nis'] ?? '');

        if ($nis === '') {
            $error = 'NIS siswa tidak ditemukan.';
        } elseif (!siswa_exists($conn, $nis)) {
            $error = 'Data siswa sudah tidak ada.';
        } elseif (siswa_count_laporan($conn, $nis) > 0) {
            $error = 'Siswa tidak dapat dihapus karena sudah memiliki data pengaduan.';
        } else {
            // Hapus siswa. Siswa yang masih punya laporan sudah dicegah di atas (ON DELETE RESTRICT)
            $st = mysqli_prepare($conn, 'DELETE FROM siswa WHERE nis=? LIMIT 1');
            if (!$st) {
                $error = 'Gagal menyiapkan proses hapus siswa: ' . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($st, 's', $nis);
                $ok = mysqli_stmt_execute($st);
                $affected = mysqli_stmt_affected_rows($st);
                $dbError = mysqli_stmt_error($st);
                mysqli_stmt_close($st);

                // Pastikan memang ada baris yang terhapus
                if ($ok && $affected > 0) {
                    // Berhasil hapus: kembali dengan notifikasi
                    redirect(url('admin/siswa?msg=deleted'));
                }
                $error = 'Data siswa gagal dihapus' . ($dbError ? ': ' . $dbError : '.');
            }
        }
    }
}

// Ubah kode notifikasi dari URL menjadi pesan yang bisa dibaca
if (!empty($_GET['msg'])) {
    $messages = [
        'added' => 'Siswa berhasil ditambahkan.',
        'updated' => 'Data siswa berhasil diperbarui.',
        'deleted' => 'Data siswa berhasil dihapus.'
    ];
    $success = $messages[$_GET['msg']] ?? '';
}

// Kalau ada ?edit=NIS di URL, ambil data siswa itu untuk form edit
if ($editing === null && isset($_GET['edit']) && $_GET['edit'] !== '') {
    $editing = ambil_siswa($conn, trim($_GET['edit']));
    if (!$editing) {
        $error = 'Data siswa yang ingin diedit tidak ditemukan.';
    }
}

// Kata kunci pencarian dari URL
$q = trim($_GET['q'] ?? '');
$rows = [];

if ($q !== '') {
    $like = '%' . $q . '%';
    // Daftar siswa hasil pencarian (LIKE), beserta jumlah laporannya
    $st = mysqli_prepare($conn, "SELECT s.nis,s.nama,s.kelas,COUNT(a.id_aspirasi) AS jumlah_laporan
        FROM siswa s
        LEFT JOIN aspirasi a ON a.nis=s.nis
        WHERE s.nis LIKE ? OR s.nama LIKE ? OR s.kelas LIKE ?
        GROUP BY s.nis,s.nama,s.kelas
        ORDER BY s.nama ASC");
    mysqli_stmt_bind_param($st, 'sss', $like, $like, $like);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
} else {
    // Daftar semua siswa kalau tidak ada pencarian
    $res = mysqli_query($conn, "SELECT s.nis,s.nama,s.kelas,COUNT(a.id_aspirasi) AS jumlah_laporan
        FROM siswa s
        LEFT JOIN aspirasi a ON a.nis=s.nis
        GROUP BY s.nis,s.nama,s.kelas
        ORDER BY s.nama ASC");
}

// Kumpulkan hasil ke array $rows
while ($row = mysqli_fetch_assoc($res)) {
    $rows[] = $row;
}
if (isset($st) && $st instanceof mysqli_stmt) mysqli_stmt_close($st);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Kelola Siswa</title>
  <link rel="stylesheet" href="<?= e($base) ?>/assets/admin.css">
  <script src="<?= e($base) ?>/assets/ui.js"></script>
</head>

<body>
  <input type="checkbox" id="menuToggle" class="menu-toggle">
  <?php // Sidebar menu navigasi admin ?>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">Pengaduan Sarpras</div>
    <ul>
      <li><a href="<?= e(url('admin/dashboard')) ?>">Dashboard</a></li>
      <li><a href="<?= e(url('admin/aspirasi')) ?>">Data Aspirasi</a></li>
      <li><a href="<?= e(url('admin/histori')) ?>">Histori Laporan</a></li>
      <li><a href="<?= e(url('admin/kategori')) ?>">Kategori Sarpras</a></li>
      <li><a href="<?= e(url('admin/siswa')) ?>" class="active">Kelola Siswa</a></li>
      <li><a href="<?= e(url('auth/logout')) ?>">Keluar</a></li>
    </ul>
    <div class="admin-user"><strong><?= e($user['nama'] ?? 'Admin') ?></strong>Administrator</div>
  </aside>
  <label for="menuToggle" class="sidebar-overlay"></label>

  <div class="main-content">
    <?php // Bagian atas halaman ?>
    <header class="topbar">
      <label for="menuToggle" class="menu-icon">&#9776;</label>
      <h1>Kelola Siswa</h1>
    </header>

    <main class="content-area">
      <?php // Judul halaman dan tombol tambah siswa ?>
      <div class="heading">
        <div>
          <p class="eyebrow">Manajemen pengguna</p>
          <h1>Data Siswa</h1>
          <p class="muted">Tambah, ubah, dan hapus akun siswa dari satu halaman.</p>
        </div>
        <a class="btn" href="<?= e(url('admin/siswa')) ?>?action=baru">+ Tambah Siswa</a>
      </div>

      <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="alert success"><?= e($success) ?></div><?php endif; ?>

      <?php // Form tambah atau edit siswa; hanya tampil saat dibutuhkan ?>
      <?php if ($editing !== null || $action === 'tambah' || $action === 'edit' || ($_GET['action'] ?? '') === 'baru'): ?>
        <section class="panel siswa-form-panel">
          <div class="panel-head">
            <h2><?= $editing ? 'Edit Data Siswa' : 'Tambah Siswa' ?></h2>
            <a href="<?= e(url('admin/siswa')) ?>">Tutup</a>
          </div>
          <?php // Form tambah atau edit siswa, dengan token CSRF ?>
          <form method="post" class="admin-form">
            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="action" value="<?= $editing ? 'edit' : 'tambah' ?>">

            <div class="detail form-grid-2">
              <label>NIS
                <input name="nis" required maxlength="10" inputmode="numeric" pattern="[0-9]{4,10}" value="<?= e($editing['nis'] ?? $old['nis']) ?>" placeholder="Contoh: 2425001" <?= $editing ? 'readonly' : '' ?>>
                <?php if ($editing): ?><input type="hidden" name="old_nis" value="<?= e($editing['old_nis'] ?? $editing['nis']) ?>"><small class="form-help">NIS merupakan identitas siswa dan tidak diubah saat edit.</small><?php endif; ?>
              </label>
              <label>Nama Lengkap
                <input name="nama" required maxlength="50" value="<?= e($editing['nama'] ?? $old['nama']) ?>" placeholder="Nama siswa">
              </label>
              <label>Kelas
                <input name="kelas" required maxlength="10" value="<?= e($editing['kelas'] ?? $old['kelas']) ?>" placeholder="Contoh: XII RPL 2">
              </label>
              <label>Password <?= $editing ? '<span class="muted-inline">(kosongkan jika tidak diubah)</span>' : '' ?>
                <input type="password" name="password" <?= $editing ? '' : 'required' ?> minlength="4" autocomplete="new-password" placeholder="Minimal 4 karakter">
              </label>
            </div>
            <div class="form-actions">
              <a class="btn secondary" href="<?= e(url('admin/siswa')) ?>">Batal</a>
              <button class="btn" type="submit"><?= $editing ? 'Simpan Perubahan' : 'Tambah Siswa' ?></button>
            </div>
          </form>
        </section>
      <?php endif; ?>

      <?php // Kotak daftar siswa dengan pencarian ?>
      <section class="panel">
        <div class="toolbar">
          <?php // Form pencarian siswa ?>
          <form class="search" method="get">
            <input name="q" value="<?= e($q) ?>" placeholder="Cari NIS, nama, atau kelas...">
            <button class="btn" type="submit">Cari</button>
            <?php if ($q !== ''): ?><a class="btn secondary" href="<?= e(url('admin/siswa')) ?>">Reset</a><?php endif; ?>
          </form>
          <div class="siswa-total"><strong><?= count($rows) ?></strong><span>siswa ditampilkan</span></div>
        </div>

        <div class="table-title-row">
          <div>
            <h2>Daftar Siswa</h2>
            <p class="muted">Akun siswa yang dapat digunakan untuk masuk ke sistem pengaduan.</p>
          </div>
        </div>

        <div style="overflow:auto">
          <?php // Tabel daftar siswa ?>
          <table class="table siswa-table">
            <thead>
              <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Jumlah Pengaduan</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php // Pesan kalau tidak ada data siswa ?>
              <?php if (!$rows): ?>
                <tr>
                  <td colspan="6" class="empty-state">Belum ada data siswa.</td>
                </tr>
              <?php else: ?>
                <?php // Tampilkan satu baris per siswa ?>
                <?php foreach ($rows as $i => $s): ?>
                  <tr>
                    <td><?= ($i + 1) ?></td>
                    <td><strong><?= e($s['nis']) ?></strong></td>
                    <td><?= e($s['nama']) ?></td>
                    <td><span class="class-pill"><?= e($s['kelas']) ?></span></td>
                    <td><span class="count-pill"><?= e($s['jumlah_laporan']) ?></span></td>
                    <td>
                      <div class="action-group">
                        <a class="btn small" href="<?= e(url('admin/siswa')) ?>?edit=<?= urlencode($s['nis']) ?>">Edit</a>
                        <?php // Form hapus siswa; ada konfirmasi dulu sebelum dikirim ?>
                        <form method="post" action="<?= e(url('admin/siswa')) ?>" onsubmit="return confirm('Yakin ingin menghapus siswa <?= e($s['nama']) ?> (<?= e($s['nis']) ?>)?');">
                          <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                          <input type="hidden" name="action" value="hapus">
                          <input type="hidden" name="nis" value="<?= e($s['nis']) ?>">
                          <button class="btn danger small" type="submit">Hapus</button>
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