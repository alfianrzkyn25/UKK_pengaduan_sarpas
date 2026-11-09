<?php
// =====================================================================
// histori.php
// Tampilan riwayat perubahan status semua laporan untuk admin.
// =====================================================================
// Sambungkan database dan fungsi bantuan
require_once __DIR__ . '/../../config/database.php';
// Pastikan yang membuka halaman ini adalah admin yang login
require_role('admin');
// Data admin yang login
$user = current_user();
// Wadah hitungan riwayat per status
$stats = ['menunggu' => 0, 'proses' => 0, 'selesai' => 0, 'ditolak' => 0];
// Hitung riwayat per status tujuan
$rs = mysqli_query($conn, "SELECT status_baru,COUNT(*) jumlah FROM histori GROUP BY status_baru");
// Masukkan hitungan ke wadah stats
while ($x = mysqli_fetch_assoc($rs)) {
  $key = strtolower($x['status_baru']);
  if (isset($stats[$key])) $stats[$key] = (int)$x['jumlah'];
}
// Ambil semua riwayat (digabung dengan nama siswa dan admin), terbaru di atas
$r = mysqli_query($conn, "SELECT h.*,s.nama,s.nis,COALESCE(ad.nama,h.diubah_oleh) diubah_oleh FROM histori h LEFT JOIN aspirasi a ON a.id_aspirasi=h.id_aspirasi LEFT JOIN siswa s ON s.nis=a.nis LEFT JOIN admin ad ON ad.id_admin=h.id_admin ORDER BY h.waktu_ubah DESC,h.id_histori DESC");
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Histori Laporan</title>
  <link rel="stylesheet" href="<?= e($base) ?>/assets/admin.css">
  <script src="<?= e($base) ?>/assets/ui.js"></script>
</head>

<body>
  <input type="checkbox" id="menuToggle" class="menu-toggle">
  <?php // Sidebar menu navigasi admin ?>
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">Pengaduan Sarpras</div>
    <ul>
      <li><a href="<?= e(url('admin/dashboard')) ?>" class="">Dashboard</a></li>
      <li><a href="<?= e(url('admin/aspirasi')) ?>" class="">Data Aspirasi</a></li>
      <li><a href="<?= e(url('admin/form_aspirasi')) ?>" class="">form aspirasi</a></li>
      <li><a href="<?= e(url('admin/histori')) ?>" class="active">Histori Laporan</a></li>
      <li><a href="<?= e(url('admin/kategori')) ?>" class="">Kategori Sarpras</a></li>
      <li><a href="<?= e(url('admin/siswa')) ?>">Kelola Siswa</a></li>
      <li><a href="<?= e(url('auth/logout')) ?>">Keluar</a></li>
    </ul>
    <div class="admin-user"><strong><?= e($user["nama"] ?? "Admin") ?></strong>Administrator</div>
  </aside>
  <label for="menuToggle" class="sidebar-overlay"></label>
  <div class="main-content">
    <header class="topbar"><label for="menuToggle" class="menu-icon">&#9776;</label>
      <h1>Histori Laporan</h1>
    </header>
    <main class="content-area">

      <?php // Notifikasi setelah riwayat berhasil dihapus (dari ?deleted=1) ?>
      <?php if (isset($_GET['deleted'])): ?>
        <div class="alert-success">&#10003; Histori berhasil dihapus.</div>
      <?php endif; ?>

      <?php // Empat kartu hitungan per status ?>
      <div class="grid">
        <div class="card stat stat-menunggu"><span>Menunggu</span><strong data-count="<?= $stats['menunggu'] ?>"><?= $stats['menunggu'] ?></strong></div>
        <div class="card stat stat-proses"><span>Proses</span><strong data-count="<?= $stats['proses'] ?>"><?= $stats['proses'] ?></strong></div>
        <div class="card stat stat-selesai"><span>Selesai</span><strong data-count="<?= $stats['selesai'] ?>"><?= $stats['selesai'] ?></strong></div>
        <div class="card stat stat-ditolak"><span>Ditolak</span><strong data-count="<?= $stats['ditolak'] ?>"><?= $stats['ditolak'] ?></strong></div>
      </div>
      <section class="panel">
        <h2 style="margin-top:0">Riwayat Perubahan Status</h2>
        <div style="overflow:auto">
          <?php // Tabel riwayat perubahan status ?>
          <table class="table">
            <tr>
              <th>ID</th>
              <th>Aspirasi</th>
              <th>Status Lama</th>
              <th>Status Baru</th>
              <th>Catatan</th>
              <th>Oleh</th>
              <th>Waktu</th>
              <th>Aksi</th>
            </tr>
            <?php // Tampilkan satu baris per riwayat ?>
            <?php while ($h = mysqli_fetch_assoc($r)): ?>
              <tr>
                <td><?= $h['id_histori'] ?></td>
                <td><a class="btn secondary small" href="<?= e(url('admin/detail_aspirasi')) ?>?id=<?= $h['id_aspirasi'] ?>">#<?= $h['id_aspirasi'] ?></a></td>
                <td><?= e($h['status_lama'] ?: '-') ?></td>
                <td><span class="badge <?= strtolower($h['status_baru']) ?>"><?= e($h['status_baru']) ?></span></td>
                <td><?= e($h['catatan'] ?: '-') ?></td>
                <td><?= e($h['diubah_oleh']) ?></td>
                <td><?= e(date('d-m-Y H:i', strtotime($h['waktu_ubah']))) ?></td>
              </tr>
            <?php endwhile; ?>
          </table>
        </div>
      </section>
    </main>
  </div>

  <?php // Modal Konfirmasi Hapus ?>
  <?php // Popup konfirmasi sebelum menghapus riwayat ?>
  <div class="modal-overlay" id="modalHapus">
    <div class="modal-box">
      <h3>Hapus Histori?</h3>
      <p>Entri histori ini akan dihapus permanen dan tidak dapat dikembalikan.</p>
      <div class="modal-actions">
        <button class="btn-batal" onclick="tutupModal()">Batal</button>
        <?php // Form hapus: mengirim id riwayat ke controller ?>
        <form method="POST" action="<?= e($base) ?>/controller/c_hapus_histori.php" style="display:inline">
          <input type="hidden" name="id_histori" id="inputIdHistori" value="">
          <input type="hidden" name="back" value="../view/admin/histori.php">
          <button type="submit" class="btn-konfirm">Ya, Hapus</button>
        </form>
      </div>
    </div>
  </div>

  <?php // Script untuk membuka dan menutup popup hapus ?>
  <script>
    function konfirmasiHapus(id) {
      document.getElementById('inputIdHistori').value = id;
      document.getElementById('modalHapus').classList.add('active');
    }

    function tutupModal() {
      document.getElementById('modalHapus').classList.remove('active');
    }
    document.getElementById('modalHapus').addEventListener('click', function(e) {
      if (e.target === this) tutupModal();
    });
  </script>
</body>

</html>