<?php
// =====================================================================
// c_user_dashboard.php
// Menyiapkan statistik dan laporan terbaru untuk dashboard siswa.
// =====================================================================
require_once __DIR__.'/../config/database.php'; require_role('siswa'); $user=current_user(); $stats=['total'=>0,'menunggu'=>0,'proses'=>0,'selesai'=>0,'ditolak'=>0];
// Hitung statistik laporan milik siswa ini per status
$st=mysqli_prepare($conn,'SELECT status,COUNT(*) jumlah FROM aspirasi WHERE nis=? GROUP BY status'); mysqli_stmt_bind_param($st,'s',$user['nis']); mysqli_stmt_execute($st); $r=mysqli_stmt_get_result($st); while($x=mysqli_fetch_assoc($r)){ $stats['total']+=(int)$x['jumlah']; $k=strtolower($x['status']); if(isset($stats[$k]))$stats[$k]=(int)$x['jumlah']; } mysqli_stmt_close($st);
// Ambil 5 laporan terbaru milik siswa ini
$st=mysqli_prepare($conn,'SELECT a.id_aspirasi,a.lokasi,a.keterangan,a.status,a.tanggal,k.nama_kategori FROM aspirasi a LEFT JOIN kategori k ON k.id_kategori=a.id_kategori WHERE a.nis=? ORDER BY a.tanggal DESC LIMIT 5'); mysqli_stmt_bind_param($st,'s',$user['nis']); mysqli_stmt_execute($st); $r=mysqli_stmt_get_result($st);
// Tampilkan dashboard siswa
include __DIR__.'/../view/user/dashboard.php';
