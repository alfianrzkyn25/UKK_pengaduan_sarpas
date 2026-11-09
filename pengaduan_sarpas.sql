-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 22, 2026 at 09:26 AM
-- Server version: 8.4.3
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pengaduan_sarpas`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(50) NOT NULL DEFAULT 'Admin'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`, `nama`) VALUES
(2, 'maul', '1234', 'Admin'),
(3, 'rzq', '12345', 'Admin'),
(4, 'rez', '123456', 'Admin'),
(5, 'punn', '1234567', 'Admin'),
(98765, 'all', '123', 'Admin');

-- --------------------------------------------------------

--
-- Table structure for table `aspirasi`
--

CREATE TABLE `aspirasi` (
  `id_aspirasi` int NOT NULL,
  `nis` varchar(10) NOT NULL,
  `id_kategori` int NOT NULL,
  `id_admin` int NOT NULL,
  `lokasi` varchar(50) NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `status` enum('Menunggu','Proses','Selesai','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `feedback` varchar(255) DEFAULT NULL,
  `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `aspirasi`
--

INSERT INTO `aspirasi` (`id_aspirasi`, `nis`, `id_kategori`, `id_admin`, `lokasi`, `keterangan`, `status`, `feedback`, `tanggal`, `updated_at`) VALUES
(1, '2425001', 1, 98765, 'Ruang Kelas XII RPL 1', 'Lampu ruang kelas mati sebelah', 'Proses', 'Sudah dijadwalkan perbaikan oleh teknisi listrik.', '2026-07-10 08:15:00', '2026-09-01 11:09:55'),
(2, '2425002', 2, 98765, 'Toilet Lantai 2', 'Keran air toilet rusak dan bocor', 'Selesai', 'Sudah diperbaiki oleh petugas kebersihan.', '2026-07-15 09:40:00', '2026-09-01 11:09:55'),
(3, '2425003', 5, 98765, 'Lab Komputer', 'Salah satu PC tidak menyala', 'Menunggu', NULL, '2026-07-20 10:05:00', '2026-09-01 11:09:55'),
(4, '2425004', 3, 98765, 'Lorong Kelas Lantai 1', 'Sampah menumpuk di dekat tangga', 'Selesai', 'Area sudah dibersihkan oleh petugas kebersihan.', '2026-07-25 07:30:00', '2026-09-01 11:09:55'),
(5, '2425005', 4, 98765, 'Ruang Kelas XII RPL 2', 'Kursi patah bagian sandaran', 'Proses', 'Menunggu kiriman kursi pengganti dari gudang.', '2026-08-05 11:00:00', '2026-09-01 11:09:55'),
(6, '2425006', 1, 98765, 'Lapangan Upacara', 'Lampu sorot lapangan tidak menyala malam hari', 'Menunggu', NULL, '2026-08-08 13:20:00', '2026-09-01 11:09:55'),
(7, '2425001', 5, 98765, 'Lab Komputer', 'Proyektor tidak bisa menampilkan gambar', 'Selesai', 'Kabel VGA sudah diganti dengan yang baru.', '2026-08-12 09:00:00', '2026-09-01 11:09:55'),
(8, '2425002', 6, 98765, 'Kantin Sekolah', 'Meja kantin goyang karena kaki patah', 'Selesai', 'uda ye', '2026-08-18 12:10:00', '2026-09-08 12:51:23'),
(9, '2425003', 2, 98765, 'Toilet Lantai 1', 'Bau tidak sedap dan ventilasi kurang baik', 'Proses', 'ye', '2026-08-22 08:50:00', '2026-09-02 08:51:05'),
(10, '2425004', 1, 98765, 'Ruang Guru', 'Stop kontak longgar dan memercikkan api kecil', 'Proses', 'Sudah dilaporkan ke teknisi, diprioritaskan karena berisiko.', '2026-08-25 14:00:00', '2026-09-01 11:09:55'),
(11, '2425005', 5, 98765, 'ruang lab rpl', 'mati ga bisa jalan', 'Selesai', 'udh beres', '2026-09-04 08:23:22', '2026-09-04 10:40:32'),
(12, '2425003', 1, 98765, 'kelas tercinta 12 rpl 2', 'TAMBAHKAN ACCCCCCCCCCCCCC', 'Selesai', 'dh y', '2026-09-08 12:52:58', '2026-09-08 12:54:03'),
(13, '2425001', 4, 2, 'kelas  12 rpl 2', 'PATAH', 'Proses', 'ini lagi mw di ganti', '2026-09-09 10:42:29', '2026-09-09 10:44:51'),
(14, '2425002', 5, 98765, 'ruang lab rpl', 'mmmm', 'Proses', 'klm', '2026-09-11 09:50:06', '2026-09-11 09:50:29'),
(15, '2425001', 2, 2, 'kelas  12 rpl 2', 'closet retak', 'Menunggu', NULL, '2026-09-22 10:50:24', '2026-09-22 10:50:24'),
(16, '2425001', 1, 2, 'kelas  12 rpl 2', 'konslet', 'Menunggu', NULL, '2026-09-22 10:54:29', '2026-09-22 10:54:29'),
(17, '2425001', 3, 2, 'kelas  12 rpl 2', 'sampah tercecer', 'Menunggu', NULL, '2026-09-22 10:55:21', '2026-09-22 10:55:21'),
(18, '2425001', 4, 2, 'ruang lab rpl', 'meja patah', 'Menunggu', NULL, '2026-09-22 10:55:59', '2026-09-22 10:55:59'),
(19, '2425001', 5, 2, 'ruang lab rpl', 'pc mati', 'Menunggu', NULL, '2026-09-22 11:00:37', '2026-09-22 11:00:37');

-- --------------------------------------------------------

--
-- Table structure for table `histori`
--

CREATE TABLE `histori` (
  `id_histori` int NOT NULL,
  `id_aspirasi` int NOT NULL,
  `id_admin` int NOT NULL,
  `status_lama` enum('Menunggu','Proses','Selesai','Ditolak') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_baru` enum('Menunggu','Proses','Selesai','Ditolak') COLLATE utf8mb4_general_ci NOT NULL,
  `catatan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `diubah_oleh` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'admin',
  `waktu_ubah` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `histori`
--

INSERT INTO `histori` (`id_histori`, `id_aspirasi`, `id_admin`, `status_lama`, `status_baru`, `catatan`, `diubah_oleh`, `waktu_ubah`) VALUES
(2, 1, 98765, 'Menunggu', 'Proses', 'Sudah dijadwalkan perbaikan oleh teknisi listrik.', 'admin', '2026-07-11 09:00:00'),
(4, 2, 98765, 'Menunggu', 'Proses', 'Petugas kebersihan sudah ditugaskan.', 'admin', '2026-07-16 08:00:00'),
(5, 2, 98765, 'Proses', 'Selesai', 'Sudah diperbaiki oleh petugas kebersihan.', 'admin', '2026-07-17 10:30:00'),
(7, 4, 98765, 'Menunggu', 'Proses', 'Petugas kebersihan menuju lokasi.', 'admin', '2026-07-25 09:00:00'),
(8, 4, 98765, 'Proses', 'Selesai', 'Area sudah dibersihkan oleh petugas kebersihan.', 'admin', '2026-07-25 11:15:00'),
(10, 7, 98765, 'Menunggu', 'Proses', 'Teknisi sedang mengecek kabel proyektor.', 'admin', '2026-08-13 10:00:00'),
(12, 9, 98765, 'Menunggu', 'Proses', 'kalem', 'admin', '2026-09-02 08:51:05'),
(14, 10, 98765, 'Proses', 'Proses', 'Sudah dilaporkan ke teknisi, diprioritaskan karena berisiko.', 'admin', '2026-09-04 09:43:19'),
(15, 11, 98765, 'Menunggu', 'Selesai', 'wes', 'admin', '2026-09-04 10:40:32'),
(16, 8, 98765, 'Proses', 'Selesai', 'mantap siswaku', 'admin', '2026-09-08 12:51:23'),
(18, 12, 98765, 'Menunggu', 'Selesai', 'dh y', 'admin', '2026-09-08 12:54:03'),
(26, 14, 98765, 'Proses', 'Proses', '0', 'Admin', '2026-09-18 10:13:40'),
(27, 15, 2, NULL, 'Menunggu', 'Aspirasi baru masuk.', 'siswa', '2026-09-22 10:50:24'),
(28, 16, 2, NULL, 'Menunggu', 'Aspirasi baru masuk.', 'siswa', '2026-09-22 10:54:29'),
(29, 17, 2, NULL, 'Menunggu', 'Aspirasi baru masuk.', 'siswa', '2026-09-22 10:55:21'),
(30, 18, 2, NULL, 'Menunggu', 'Aspirasi baru masuk.', 'siswa', '2026-09-22 10:55:59'),
(31, 19, 2, NULL, 'Menunggu', 'Aspirasi baru masuk.', 'siswa', '2026-09-22 11:00:37');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int NOT NULL,
  `nama_kategori` varchar(50) NOT NULL,
  `ket_kategori` varchar(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `ket_kategori`) VALUES
(1, 'listrik', 'lampu/kipas/spiker/stop kontak'),
(2, 'Toilet', 'kloset/keran/wastafel'),
(3, 'kebersihan', 'sampah/lantai/kamar mandi'),
(4, 'Furnitur', ' (Meja/Kursi)'),
(5, 'Elektronik', 'Laptop/pc/monitor/mouse/tv/proyector');

-- --------------------------------------------------------

--
-- Table structure for table `lampiran`
--

CREATE TABLE `lampiran` (
  `id_lampiran` int NOT NULL,
  `id_aspirasi` int NOT NULL,
  `url_file` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_file` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lampiran`
--

INSERT INTO `lampiran` (`id_lampiran`, `id_aspirasi`, `url_file`, `nama_file`, `uploaded_at`) VALUES
(1, 1, '/uploads/aspirasi/1/lampu-kelas-1.jpg', 'lampu-kelas-1.jpg', '2026-07-10 08:16:00'),
(2, 3, '/uploads/aspirasi/3/pc-lab-1.jpg', 'pc-lab-1.jpg', '2026-07-20 10:06:00'),
(3, 5, '/uploads/aspirasi/5/kursi-patah-1.jpg', 'kursi-patah-1.jpg', '2026-08-05 11:01:00'),
(4, 5, '/uploads/aspirasi/5/kursi-patah-2.jpg', 'kursi-patah-2.jpg', '2026-08-05 11:02:00'),
(5, 9, '/uploads/aspirasi/9/toilet-1.jpg', 'toilet-1.jpg', '2026-08-22 08:51:00'),
(6, 11, '/assets/uploads/aspirasi/11/foto-20260904032322-8474ea.jpg', 'foto-20260904032322-8474ea.jpg', '2026-09-04 08:23:22'),
(7, 13, 'assets/uploads/aspirasi/13/foto-20260909054229-8ffeb1.jpg', 'Yx8XrH.jpg', '2026-09-09 10:42:29'),
(8, 13, 'assets/uploads/aspirasi/13/feedback-20260909054451-46611b.png', 'Screenshot 2026-09-07 084509.png', '2026-09-09 10:44:51'),
(9, 14, 'assets/uploads/aspirasi/14/foto-20260911045006-234c86.png', 'Screenshot 2026-09-11 091741.png', '2026-09-11 09:50:06'),
(10, 14, 'assets/uploads/aspirasi/14/feedback-20260911045029-af07a1.jpg', 'Yx8XrH.jpg', '2026-09-11 09:50:29'),
(11, 14, 'assets/uploads/aspirasi/14/feedback-20260911045726-fc25d6.png', 'images__2_-removebg-preview.png', '2026-09-11 09:57:26'),
(12, 15, 'assets/uploads/aspirasi/15/foto-20260922055024-3ddc6e.jpeg', 'WhatsApp Image 2026-09-22 at 07.08.11.jpeg', '2026-09-22 10:50:24'),
(13, 16, 'assets/uploads/aspirasi/16/foto-20260922055429-dd1d2d.jpeg', 'WhatsApp Image 2026-09-22 at 07.08.11.jpeg', '2026-09-22 10:54:29'),
(14, 17, 'assets/uploads/aspirasi/17/foto-20260922055521-301f01.png', 'WhatsApp_Image_2026-09-22_at_07.08.11-removebg-preview.png', '2026-09-22 10:55:21'),
(15, 18, 'assets/uploads/aspirasi/18/foto-20260922055559-66380a.png', 'WhatsApp_Image_2026-09-22_at_07.08.11-removebg-preview.png', '2026-09-22 10:55:59'),
(16, 19, 'assets/uploads/aspirasi/19/foto-20260922060037-5e0ff6.png', 'WhatsApp_Image_2026-09-22_at_07.08.11-removebg-preview.png', '2026-09-22 11:00:37');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `nis` varchar(10) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `kelas` varchar(10) NOT NULL,
  `password` varchar(25) NOT NULL,
  `foto` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`nis`, `nama`, `kelas`, `password`) VALUES
('0084364', 'dav', 'xi rpl 1', '1234'),
('2425001', 'Fajar', 'XII RPL 2', '123'),
('2425002', 'razaq', 'XII RPL 2', '1234'),
('2425003', 'maul', 'XI RPL 2', '12345'),
('2425004', 'titiw', 'XI RPL 2', '123456'),
('2425005', 'eka', 'XII RPL 2', '1234567'),
('2425006', 'punn', 'X RPL 2', '12345678');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `aspirasi`
--
ALTER TABLE `aspirasi`
  ADD PRIMARY KEY (`id_aspirasi`),
  ADD KEY `fk_aspirasi_siswa` (`nis`),
  ADD KEY `fk_aspirasi_kategori` (`id_kategori`),
  ADD KEY `id_admin` (`id_admin`) USING BTREE;

--
-- Indexes for table `histori`
--
ALTER TABLE `histori`
  ADD PRIMARY KEY (`id_histori`),
  ADD KEY `fk_histori_aspirasi` (`id_aspirasi`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `ket_kategori` (`ket_kategori`),
  ADD UNIQUE KEY `ket_kategori_3` (`ket_kategori`),
  ADD KEY `nama_kategori` (`nama_kategori`),
  ADD KEY `ket_kategori_2` (`ket_kategori`);

--
-- Indexes for table `lampiran`
--
ALTER TABLE `lampiran`
  ADD PRIMARY KEY (`id_lampiran`),
  ADD KEY `fk_lampiran_aspirasi` (`id_aspirasi`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`nis`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98767;

--
-- AUTO_INCREMENT for table `aspirasi`
--
ALTER TABLE `aspirasi`
  MODIFY `id_aspirasi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `histori`
--
ALTER TABLE `histori`
  MODIFY `id_histori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lampiran`
--
ALTER TABLE `lampiran`
  MODIFY `id_lampiran` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
-- --------------------------------------------------------
--
-- RELASI ANTAR TABEL (FOREIGN KEY)
--
-- Sebelumnya tabel hanya punya INDEX (fk_...) tanpa constraint FOREIGN KEY,
-- jadi relasinya hanya ada di kolom "id_..." saja. Blok ini menambahkan constraint-nya.
--
-- Diagram relasi (1 = induk, N = anak):
--   siswa    1 --< N aspirasi   lewat aspirasi.nis          (siswa membuat laporan)
--   kategori 1 --< N aspirasi   lewat aspirasi.id_kategori  (jenis kerusakan)
--   admin    1 --< N aspirasi   lewat aspirasi.id_admin     (admin penanggung jawab)
--   aspirasi 1 --< N histori    lewat histori.id_aspirasi   (riwayat perubahan status)
--   admin    1 --< N histori    lewat histori.id_admin      (siapa yang mengubah status)
--   aspirasi 1 --< N lampiran   lewat lampiran.id_aspirasi  (foto pengaduan & feedback)
--
-- Aturan hapus:
--   RESTRICT : data induk tidak bisa dihapus selama masih dipakai
--              (siswa yang punya laporan tidak bisa dihapus, sesuai view/admin/siswa.php)
--   CASCADE  : data anak ikut terhapus (histori & lampiran ikut hilang saat laporan dihapus)
--
-- Perbaikan data yang wajib dilakukan dulu, karena ada id yang tidak punya induknya:
--   aspirasi id 8 memakai id_kategori 6, padahal kategori 6 tidak ada di tabel kategori.
--   Dipindah ke kategori 4 (Furnitur) karena keterangannya "meja kantin".
--   Silakan ganti ke kategori lain jika tidak sesuai.
--
UPDATE `aspirasi` SET `id_kategori` = 4 WHERE `id_kategori` = 6;

-- aspirasi -> siswa, kategori, admin
ALTER TABLE `aspirasi`
  ADD CONSTRAINT `fk_asp_siswa` FOREIGN KEY (`nis`) REFERENCES `siswa` (`nis`)
      ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_asp_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`)
      ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_asp_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`)
      ON UPDATE CASCADE ON DELETE RESTRICT;

-- histori -> aspirasi, admin
ALTER TABLE `histori`
  ADD CONSTRAINT `fk_his_aspirasi` FOREIGN KEY (`id_aspirasi`) REFERENCES `aspirasi` (`id_aspirasi`)
      ON UPDATE CASCADE ON DELETE CASCADE,
  ADD CONSTRAINT `fk_his_admin` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`)
      ON UPDATE CASCADE ON DELETE RESTRICT;

-- lampiran -> aspirasi
ALTER TABLE `lampiran`
  ADD CONSTRAINT `fk_lamp_aspirasi` FOREIGN KEY (`id_aspirasi`) REFERENCES `aspirasi` (`id_aspirasi`)
      ON UPDATE CASCADE ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
