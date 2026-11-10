<?php
// =====================================================================
// c_detail_aspirasi.php
// Memproses perubahan status/feedback laporan oleh admin, lalu menyiapkan data detail laporan.
// =====================================================================
require_once __DIR__ . '/../config/database.php'; require_role('admin'); $user=current_user(); $id=(int)($_GET['id']??0); $error=''; $ok='';

// Urutan status yang wajib diikuti (tidak boleh mundur/meloncat).
$urutanStatus = ['Menunggu', 'Proses', 'Selesai'];
$statusBerikut = ['Menunggu' => 'Proses', 'Proses' => 'Selesai', 'Selesai' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = trim($_POST['status'] ?? '');
    $feedback = trim($_POST['feedback'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    $allowedExt = ['jpg' => 1, 'jpeg' => 1, 'png' => 1, 'gif' => 1, 'webp' => 1];
    $maxSize = 5 * 1024 * 1024; // 5MB
    $hasFile = !empty($_FILES['foto_feedback']['name']);
    $fotoExt = $hasFile ? strtolower(pathinfo($_FILES['foto_feedback']['name'], PATHINFO_EXTENSION)) : '';

    $st = mysqli_prepare($conn, 'SELECT status FROM aspirasi WHERE id_aspirasi=?');
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $old = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);

    // Ditolak hanya boleh kalau status masih Menunggu (belum diproses admin).
    $bolehTolak = $old && $old['status'] === 'Menunggu';
    $statusValid = $old && (
        $status === $old['status'] ||
        $status === ($statusBerikut[$old['status']] ?? null) ||
        ($status === 'Ditolak' && $bolehTolak)
    );

    if (!$old) {
        $error = 'Data aspirasi tidak ditemukan.';
    } elseif ($old['status'] === 'Ditolak') {
        $error = 'Aspirasi ini sudah berstatus Ditolak dan terkunci, tidak dapat diubah lagi.';
    } elseif ($status === 'Ditolak' && $old['status'] !== 'Menunggu') {
        $error = 'Aspirasi yang sudah diproses tidak dapat ditolak.';
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
        $st = mysqli_prepare($conn, 'UPDATE aspirasi SET status=?, feedback=? WHERE id_aspirasi=?');
        mysqli_stmt_bind_param($st, 'ssi', $status, $feedback, $id);
        $okq = mysqli_stmt_execute($st);
        if (!$okq) { $error = 'Gagal memperbarui status aspirasi: ' . mysqli_stmt_error($st); }
        mysqli_stmt_close($st);

        if ($okq) {
            // Pastikan nilai status benar-benar tersimpan sesuai yang dipilih.
            $chk = mysqli_prepare($conn, 'SELECT status FROM aspirasi WHERE id_aspirasi=?');
            mysqli_stmt_bind_param($chk, 'i', $id);
            mysqli_stmt_execute($chk);
            $chkRow = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
            mysqli_stmt_close($chk);
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
            $st = mysqli_prepare($conn, 'INSERT INTO histori (id_aspirasi,id_admin,status_lama,status_baru,catatan,diubah_oleh) VALUES (?,?,?,?,?,?)');
            $adminId = (int)$user['id_admin'];
            $by = $user['nama'] ?? 'admin';
            mysqli_stmt_bind_param($st, 'iissss', $id, $adminId, $old['status'], $status, $note, $by);
            $okq = mysqli_stmt_execute($st);
            if (!$okq) { $error = 'Gagal menyimpan riwayat status: ' . mysqli_stmt_error($st); }
            mysqli_stmt_close($st);
        }

        if ($okq) {
            mysqli_commit($conn);
            if ($hasFile) {
                $dir = __DIR__ . '/../assets/uploads/aspirasi/' . $id;
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $filename = 'feedback-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $fotoExt;
                if (move_uploaded_file($_FILES['foto_feedback']['tmp_name'], $dir . '/' . $filename)) {
                    $urlFile = 'assets/uploads/aspirasi/' . $id . '/' . $filename;
                    $origName = $_FILES['foto_feedback']['name'];
                    $lp = mysqli_prepare($conn, 'INSERT INTO lampiran (id_aspirasi, url_file, nama_file) VALUES (?,?,?)');
                    mysqli_stmt_bind_param($lp, 'iss', $id, $urlFile, $origName);
                    mysqli_stmt_execute($lp);
                    mysqli_stmt_close($lp);
                }
            }
            redirect('c_detail_aspirasi.php?id=' . $id . '&updated=1');
        }
        mysqli_rollback($conn);
        if ($error === '') {
            $error = 'Data gagal diperbarui.';
        }
    }
}

if (isset($_GET['updated'])) $ok = 'Status dan feedback berhasil diperbarui.';

$a = null; $hist = [];
$st = mysqli_prepare($conn, 'SELECT a.*,s.nama AS nama_siswa,s.kelas,k.nama_kategori FROM aspirasi a JOIN siswa s ON s.nis=a.nis LEFT JOIN kategori k ON k.id_kategori=a.id_kategori WHERE a.id_aspirasi=?');
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
$a = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

if ($a) {
    $st = mysqli_prepare($conn, 'SELECT h.status_lama,h.status_baru,h.catatan,h.waktu_ubah,COALESCE(ad.nama,h.diubah_oleh) diubah_oleh FROM histori h LEFT JOIN admin ad ON ad.id_admin=h.id_admin WHERE h.id_aspirasi=? ORDER BY h.waktu_ubah ASC,h.id_histori ASC');
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $rr = mysqli_stmt_get_result($st);
    while ($h = mysqli_fetch_assoc($rr)) $hist[] = $h;
    mysqli_stmt_close($st);
}

$lampiran = [];
if ($a) {
    $st = mysqli_prepare($conn, 'SELECT url_file,nama_file FROM lampiran WHERE id_aspirasi=? ORDER BY id_lampiran ASC');
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $lr = mysqli_stmt_get_result($st);
    while ($lf = mysqli_fetch_assoc($lr)) $lampiran[] = $lf;
    mysqli_stmt_close($st);
}

// Opsi status yang boleh dipilih dari status saat ini.
$opsiStatus = [];
if ($a && $a['status'] !== 'Ditolak') {
    $opsiStatus[] = $a['status'];
    if (!empty($statusBerikut[$a['status']])) $opsiStatus[] = $statusBerikut[$a['status']];
    if ($a['status'] === 'Menunggu') $opsiStatus[] = 'Ditolak';
}

include __DIR__ . '/../view/admin/detail_aspirasi.php';
