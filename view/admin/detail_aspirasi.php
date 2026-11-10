<?php
// =====================================================================
// detail_aspirasi.php
// Tampilan detail laporan untuk admin. Di sini admin bisa mengubah status, menulis feedback, dan menambah lampiran.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../../config/database.php';
// Pastikan yang membuka halaman ini adalah admin yang login
require_role('admin');
$user = current_user();
// Ambil id laporan dari URL
$id = (int)($_GET['id'] ?? 0);
$error = '';
$ok = '';

// Urutan status yang wajib diikuti (tidak boleh mundur/meloncat).
$urutanStatus = ['Menunggu', 'Proses', 'Selesai'];
$statusBerikut = ['Menunggu' => 'Proses', 'Proses' => 'Selesai', 'Selesai' => null];

// Proses hanya kalau form perbarui status dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil status baru, feedback, dan catatan dari form
    $status = trim($_POST['status'] ?? '');
    $feedback = trim($_POST['feedback'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    $allowedExt = ['jpg' => 1, 'jpeg' => 1, 'png' => 1, 'gif' => 1, 'webp' => 1];
    $maxSize = 5 * 1024 * 1024; // 5MB
    $hasFile = !empty($_FILES['foto_feedback']['name']);
    $fotoExt = $hasFile ? strtolower(pathinfo($_FILES['foto_feedback']['name'], PATHINFO_EXTENSION)) : '';

    // Ambil status lama sebelum diubah
    $st = mysqli_prepare($conn, 'SELECT status FROM aspirasi WHERE id_aspirasi=?');
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    // Simpan status lama
    $old = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);

    // Ditolak hanya boleh kalau status masih Menunggu (belum diproses admin).
    $bolehTolak = $old && $old['status'] === 'Menunggu';
    $statusValid = $old && (
        $status === $old['status'] ||
        $status === ($statusBerikut[$old['status']] ?? null) ||
        ($status === 'Ditolak' && $bolehTolak)
    );

    // Validasi: laporan harus ada
    if (!$old) {
        $error = 'Data aspirasi tidak ditemukan.';
    } elseif ($old['status'] === 'Ditolak') {
        $error = 'Aspirasi ini sudah berstatus Ditolak dan terkunci, tidak dapat diubah lagi.';
    } elseif ($status === 'Ditolak' && $old['status'] !== 'Menunggu') {
        $error = 'Aspirasi yang sudah diproses tidak dapat ditolak.';
    // Validasi: status baru harus salah satu dari 4 pilihan
    } elseif (!in_array($status, ['Menunggu', 'Proses', 'Selesai', 'Ditolak'], true)) {
        $error = 'Status tidak valid.';
    } elseif (!$statusValid) {
        $error = 'Status harus berurutan (Menunggu → Proses → Selesai), tidak boleh mundur atau meloncat.';
    } elseif ($hasFile && $_FILES['foto_feedback']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Gagal mengunggah foto. Silakan coba lagi.';
    } elseif ($hasFile && !isset($allowedExt[$fotoExt])) {
        $error = 'Format foto tidak didukung. Gunakan JPG, PNG, GIF, atau WEBP.';
    } elseif ($hasFile && $_FILES['foto_feedback']['size'] > $maxSize) {
        $error = 'Ukuran foto maksimal 5MB.';
    } elseif ($hasFile && !@getimagesize($_FILES['foto_feedback']['tmp_name'])) {
        $error = 'File yang diunggah bukan gambar yang valid.';
    } else {
        mysqli_begin_transaction($conn);
        // Ubah status dan feedback laporan
        $st = mysqli_prepare($conn, 'UPDATE aspirasi SET status=?, feedback=? WHERE id_aspirasi=?');
        mysqli_stmt_bind_param($st, 'ssi', $status, $feedback, $id);
        // Cek apakah update berhasil
        $okq = mysqli_stmt_execute($st);
        if (!$okq) {
            $error = 'Gagal memperbarui status aspirasi: ' . mysqli_stmt_error($st);
        }
        mysqli_stmt_close($st);

        if ($okq) {
            // Pastikan nilai status benar-benar tersimpan sesuai yang dipilih.
            // Cek ulang status di database untuk memastikan perubahan tersimpan
            $chk = mysqli_prepare($conn, 'SELECT status FROM aspirasi WHERE id_aspirasi=?');
            mysqli_stmt_bind_param($chk, 'i', $id);
            mysqli_stmt_execute($chk);
            $chkRow = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
            mysqli_stmt_close($chk);
            // Kalau status belum berubah, anggap gagal
            if (!$chkRow || $chkRow['status'] !== $status) {
                $okq = false;
                $error = "Status \"$status\" belum dikenali oleh database (kolom status di tabel aspirasi belum memuat nilai ini). Jalankan dulu ALTER TABLE untuk menambahkan status baru ke kolom ENUM.";
            }
        }

        if ($okq) {
            if ($catatan !== '') {
                $note = $catatan;
            } elseif ($status === 'Ditolak') {
                $note = $feedback !== '' ? ('Aspirasi ditolak: ' . $feedback) : 'Aspirasi ditolak.';
            } else {
                $note = 'Status aspirasi diperbarui.';
            }
            // Catat perubahan status ke riwayat
            $st = mysqli_prepare($conn, 'INSERT INTO histori (id_aspirasi,id_admin,status_lama,status_baru,catatan,diubah_oleh) VALUES (?,?,?,?,?,?)');
            $adminId = (int)$user['id_admin'];
            $by = $user['nama'] ?? 'admin';
            mysqli_stmt_bind_param($st, 'iissss', $id, $adminId, $old['status'], $status, $note, $by);
            $okq = mysqli_stmt_execute($st);
            if (!$okq) {
                $error = 'Gagal menyimpan riwayat status: ' . mysqli_stmt_error($st);
            }
            mysqli_stmt_close($st);
        }

        if ($okq) {
            mysqli_commit($conn);
            if ($hasFile) {
                $dir = __DIR__ . '/../../assets/uploads/aspirasi/' . $id;
                // Buat folder untuk foto bukti perbaikan kalau belum ada
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $filename = 'feedback-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $fotoExt;
                // Pindahkan foto bukti perbaikan dari admin ke folder tujuan
                if (move_uploaded_file($_FILES['foto_feedback']['tmp_name'], $dir . '/' . $filename)) {
                    $urlFile = 'assets/uploads/aspirasi/' . $id . '/' . $filename;
                    $origName = $_FILES['foto_feedback']['name'];
                    // Catat foto bukti di tabel lampiran
                    $lp = mysqli_prepare($conn, 'INSERT INTO lampiran (id_aspirasi, url_file, nama_file) VALUES (?,?,?)');
                    mysqli_stmt_bind_param($lp, 'iss', $id, $urlFile, $origName);
                    mysqli_stmt_execute($lp);
                    mysqli_stmt_close($lp);
                }
            }
            // Berhasil: muat ulang halaman dengan notifikasi
            redirect(url('admin/detail_aspirasi') . '?id=' . $id . '&updated=1');
        }
        mysqli_rollback($conn);
        if ($error === '') {
            $error = 'Data gagal diperbarui.';
        }
    }
}

// Tampilkan pesan sukses kalau baru saja diperbarui
if (isset($_GET['updated'])) $ok = 'Status dan feedback berhasil diperbarui.';

$a = null;
$hist = [];
// Ambil detail laporan beserta nama siswa dan kategori
$st = mysqli_prepare($conn, 'SELECT a.*,s.nama AS nama_siswa,s.kelas,s.foto AS foto_siswa,k.nama_kategori FROM aspirasi a JOIN siswa s ON s.nis=a.nis LEFT JOIN kategori k ON k.id_kategori=a.id_kategori WHERE a.id_aspirasi=?');
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
// Simpan detail laporan
$a = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if ($a) {
    // Ambil riwayat perubahan status laporan ini
    $st = mysqli_prepare($conn, 'SELECT h.status_lama,h.status_baru,h.catatan,h.waktu_ubah,COALESCE(ad.nama,h.diubah_oleh) diubah_oleh FROM histori h LEFT JOIN admin ad ON ad.id_admin=h.id_admin WHERE h.id_aspirasi=? ORDER BY h.waktu_ubah ASC,h.id_histori ASC');
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $rr = mysqli_stmt_get_result($st);
    // Kumpulkan riwayat ke array $hist
    while ($h = mysqli_fetch_assoc($rr)) $hist[] = $h;
    mysqli_stmt_close($st);
}

$lampiran = [];
if ($a) {
    // Ambil lampiran (foto) laporan
    $st = mysqli_prepare($conn, 'SELECT url_file,nama_file FROM lampiran WHERE id_aspirasi=? ORDER BY id_lampiran ASC');
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $lr = mysqli_stmt_get_result($st);
    // Kumpulkan lampiran ke array $lampiran
    while ($lf = mysqli_fetch_assoc($lr)) $lampiran[] = $lf;
    mysqli_stmt_close($st);
}

// Opsi status yang boleh dipilih dari status saat ini: tetap di status sekarang,
// maju satu langkah sesuai urutan, atau Ditolak (hanya kalau masih Menunggu).
$opsiStatus = [];
if ($a && $a['status'] !== 'Ditolak') {
    $opsiStatus[] = $a['status'];
    // Tentukan pilihan status berikutnya sesuai status sekarang
    if (!empty($statusBerikut[$a['status']])) $opsiStatus[] = $statusBerikut[$a['status']];
    if ($a['status'] === 'Menunggu') $opsiStatus[] = 'Ditolak';
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Detail Aspirasi</title>
    <link rel="stylesheet" href="<?= e($base) ?>/assets/admin.css">
    <link rel="stylesheet" href="<?= e($base) ?>/assets/profil.css">
    <script src="<?= e($base) ?>/assets/ui.js"></script>
</head>

<body><input type="checkbox" id="menuToggle" class="menu-toggle">
    <?php // Sidebar menu navigasi admin ?>
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">Pengaduan Sarpras</div>
        <ul>
            <li><a href="<?= e(url('admin/dashboard')) ?>" class="">Dashboard</a></li>
            <li><a href="<?= e(url('admin/aspirasi')) ?>" class="active">Data Aspirasi</a></li>
            <li><a href="<?= e(url('admin/histori')) ?>" class="">Histori Laporan</a></li>
            <li><a href="<?= e(url('admin/kategori')) ?>" class="">Kategori Sarpras</a></li>
            <li><a href="<?= e(url('admin/siswa')) ?>">Kelola Siswa</a></li>
            <li><a href="<?= e(url('auth/logout')) ?>">Keluar</a></li>
        </ul>
        <div class="admin-user"><strong><?= e($user["nama"] ?? "Admin") ?></strong>Administrator</div>
    </aside><label for="menuToggle" class="sidebar-overlay"></label>
    <div class="main-content">
        <?php // Bagian atas halaman ?>
        <header class="topbar"><label for="menuToggle" class="menu-icon">&#9776;</label>
            <h1>Detail Aspirasi</h1>
        </header>
        <?php // Bagian utama: notifikasi, detail laporan, dan form perbarui status ?>
        <main class="content-area"><?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?><?php if ($ok): ?><div class="alert success"><?= e($ok) ?></div><?php endif; ?><?php if (!$a): ?><div class="alert error">Data aspirasi tidak ditemukan.</div><a class="btn" href="<?= e(url('admin/aspirasi')) ?>">Kembali</a><?php else: ?><div class="heading">
                    <div>
                        <p class="eyebrow">Aspirasi #<?= $a['id_aspirasi'] ?></p>
                        <h1><?= e($a['nama_kategori']) ?></h1>
                        <p class="muted"><?= e($a['nama_siswa']) ?> · <?= e($a['nis']) ?></p>
                    </div><span class="badge <?= strtolower($a['status']) ?>"><?= e($a['status']) ?></span>
                <?php // Peringatan kalau laporan sudah ditolak; status jadi terkunci ?>
                </div><?php if ($a['status'] === 'Ditolak'): ?><div class="alert error" style="margin-top:-8px">Aspirasi ini telah <b>ditolak</b> dan statusnya terkunci (tidak dapat diubah lagi). Alasan: <?= e($a['feedback'] ?: '-') ?></div><?php endif; ?><div class="detail">
                    <?php // Kotak detail laporan ?>
                    <section class="panel">
                        <h2>Detail Laporan</h2>
                        <p><b>Pelapor</b><span class="reporter-inline"><?= avatar($a['foto_siswa'] ?? null, $a['nama_siswa'], 'sm') ?><span><?= e($a['nama_siswa']) ?> · <?= e($a['kelas']) ?></span></span></p>
                        <p><b>Lokasi</b><?= e($a['lokasi']) ?></p>
                        <p><b>Keterangan</b><?= e($a['keterangan']) ?></p>
                        <p><b>Feedback</b><?= e($a['feedback'] ?: '-') ?></p>
                        <?php // Tampilkan foto lampiran dari siswa ?>
                        <p><b>Dibuat</b><?= e($a['tanggal']) ?></p><?php if ($lampiran): ?><div style="margin-top:10px"><b>Foto Lampiran</b>
                                <div class="lampiran-grid"><?php foreach ($lampiran as $lf): ?><a href="<?= e($base) ?>/<?= e($lf['url_file']) ?>" target="_blank"><img src="<?= e($base) ?>/<?= e($lf['url_file']) ?>" alt="<?= e($lf['nama_file']) ?>"></a><?php endforeach; ?></div>
                            </div><?php endif; ?>
                    </section>
                    <section class="panel">
                        <?php // Form admin untuk mengubah status dan menulis feedback ?>
                        <h2>Perbarui Status</h2><?php if ($a['status'] === 'Ditolak'): ?><p class="muted">Aspirasi ini sudah ditolak. Status terkunci dan tidak bisa diubah lagi.</p><?php else: ?><form method="post" class="admin-form" enctype="multipart/form-data"><label>Status <span class="muted-inline">(urut: Menunggu → Proses → Selesai)</span><select name="status" id="statusSelect"><?php foreach ($opsiStatus as $opt): ?><option value="<?= $opt ?>" <?= $a['status'] === $opt ? 'selected' : '' ?>><?= $opt ?></option><?php endforeach; ?></select></label><label>Feedback <span class="muted-inline">(opsional)</span><textarea name="feedback" id="feedbackInput" maxlength="255" placeholder="Contoh: Laporan ditolak karena sudah pernah dilaporkan / bukan aset sekolah / data kurang jelas."><?= e($a['feedback'] ?? '') ?></textarea></label><label>Catatan Histori<textarea name="catatan" maxlength="255" placeholder="Catatan perubahan..."></textarea></label><label>Foto Bukti Perbaikan (opsional)<input type="file" name="foto_feedback" accept="image/jpeg,image/png,image/gif,image/webp"><small class="form-help">Format JPG/PNG/GIF/WEBP, maksimal 5MB.</small></label><button class="btn" type="submit">Simpan Perubahan</button></form><?php endif; ?>
                    </section>
                </div>
                <section class="panel">
                    <h2>Riwayat</h2>
                    <?php // Riwayat status dalam bentuk timeline ?>
                    <div class="timeline"><?php foreach ($hist as $h): ?><div class="timeline-item"><b><?= e($h['status_baru']) ?></b><small><?= e($h['waktu_ubah']) ?> · <?= e($h['diubah_oleh']) ?></small>
                                <div><?= e($h['catatan'] ?: '-') ?></div>
                            </div><?php endforeach; ?></div>
                </section><a class="btn secondary" href="<?= e(url('admin/aspirasi')) ?>">← Kembali</a><?php endif; ?>
        </main>
    </div>
</body>

</html>