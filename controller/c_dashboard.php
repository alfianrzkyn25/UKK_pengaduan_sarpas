<?php
// =====================================================================
// c_dashboard.php
// Menyiapkan statistik dan 5 laporan terbaru untuk dashboard admin.
// =====================================================================
require_once __DIR__ . '/../config/database.php';
require_role('admin');
// Data admin yang sedang login
$user = current_user();
// Wadah statistik dashboard
$stats=['total'=>0,'menunggu'=>0,'proses'=>0,'selesai'=>0,'ditolak'=>0];
// Hitung jumlah laporan per status
$r=mysqli_query($conn,"SELECT status,COUNT(*) jumlah FROM aspirasi GROUP BY status");
// Jumlahkan tiap status; total dihitung dari semua status
while($x=mysqli_fetch_assoc($r)){ $stats['total']+=(int)$x['jumlah']; $key=strtolower($x['status']); if(isset($stats[$key])) $stats[$key]=(int)$x['jumlah']; }
// Ambil 5 laporan terbaru untuk ditampilkan di dashboard
$r=mysqli_query($conn,"SELECT a.id_aspirasi,a.nis,s.nama,k.nama_kategori,a.lokasi,a.keterangan,a.status,a.tanggal FROM aspirasi a LEFT JOIN siswa s ON s.nis=a.nis LEFT JOIN kategori k ON k.id_kategori=a.id_kategori ORDER BY a.tanggal DESC,a.id_aspirasi DESC LIMIT 5");
// Tampilkan dashboard admin
include __DIR__.'/../view/admin/dashboard.php';
