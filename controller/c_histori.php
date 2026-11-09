<?php
// =====================================================================
// c_histori.php
// Menyiapkan data riwayat perubahan status untuk admin lalu menampilkan view admin/histori.php.
// =====================================================================
require_once __DIR__ . '/../config/database.php'; require_role('admin'); $user=current_user();
// Wadah hitungan per status (Menunggu, Proses, Selesai, Ditolak)
$stats=['menunggu'=>0,'proses'=>0,'selesai'=>0,'ditolak'=>0];
// Hitung berapa riwayat untuk tiap status tujuan
$rs=mysqli_query($conn,"SELECT status_baru,COUNT(*) jumlah FROM histori GROUP BY status_baru");
// Masukkan hitungan ke wadah, status yang tidak dikenal diabaikan
while($x=mysqli_fetch_assoc($rs)){ $key=strtolower($x['status_baru']); if(isset($stats[$key])) $stats[$key]=(int)$x['jumlah']; }
// Ambil semua riwayat, digabung dengan nama siswa dan admin, terbaru di atas
$r=mysqli_query($conn,"SELECT h.*,s.nama,s.nis,COALESCE(ad.nama,h.diubah_oleh) diubah_oleh FROM histori h LEFT JOIN aspirasi a ON a.id_aspirasi=h.id_aspirasi LEFT JOIN siswa s ON s.nis=a.nis LEFT JOIN admin ad ON ad.id_admin=h.id_admin ORDER BY h.waktu_ubah DESC,h.id_histori DESC");
// Tampilkan halaman riwayat
include __DIR__.'/../view/admin/histori.php';
