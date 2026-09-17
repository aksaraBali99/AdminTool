
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `absensi_guru`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `absensi_guru` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_guru` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `total_jam` decimal(4,2) NOT NULL,
  `status_hadir` enum('Hadir','Izin','Alpha') NOT NULL DEFAULT 'Hadir',
  `id_jadwal_kelas` int DEFAULT NULL,
  `id_branch` int NOT NULL,
  `tipe_kelas` enum('anak','dewasa') DEFAULT 'anak',
  `tarif_per_jam` decimal(12,2) DEFAULT '0.00',
  `biaya_transport` decimal(12,2) DEFAULT '0.00',
  `jumlah_kedatangan` int DEFAULT '1',
  `keterangan` text,
  `is_deleted` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_guru` (`id_guru`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_jadwal` (`id_jadwal_kelas`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `absensi_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `absensi_siswa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal` date NOT NULL,
  `status_hadir` enum('Hadir','Izin','Alpha') NOT NULL,
  `keterangan` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_absensi` (`id_siswa`,`tanggal`),
  KEY `idx_siswa` (`id_siswa`),
  KEY `idx_tanggal` (`tanggal`),
  CONSTRAINT `fk_absensi_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `peserta` (`id_peserta`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=507 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_log` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `tabel` varchar(50) NOT NULL,
  `id_record` int NOT NULL,
  `aksi` enum('INSERT','UPDATE','DELETE','APPROVE','PAID') NOT NULL,
  `data_lama` text,
  `data_baru` text,
  `id_user` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tabel_record` (`tabel`,`id_record`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_branch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_branch` (
  `id_branch` int NOT NULL AUTO_INCREMENT,
  `nama_branch` varchar(100) NOT NULL,
  `alamat` text,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_branch`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_jenis_kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_jenis_kelas` (
  `id_jenis_kelas` int NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(50) NOT NULL,
  `gender` varchar(5) NOT NULL,
  `usia` varchar(20) NOT NULL,
  `tipe` varchar(20) NOT NULL,
  `biaya` int NOT NULL,
  `is_partnership` enum('0','1') DEFAULT '0',
  `nama_organisasi` varchar(255) DEFAULT NULL,
  `kontak_partnership` varchar(100) DEFAULT NULL,
  `alamat_partnership` text,
  `no_telp_partnership` varchar(20) DEFAULT NULL,
  `jumlah_siswa_partnership` int DEFAULT NULL,
  `biaya_regis` int NOT NULL,
  `biaya_buku` int NOT NULL,
  `nama_buku` varchar(50) NOT NULL,
  PRIMARY KEY (`id_jenis_kelas`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_konfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_konfig` (
  `id_konfig` int NOT NULL,
  `nama_apk` varchar(50) NOT NULL,
  `token_wa` text NOT NULL,
  `logo` varchar(255) NOT NULL,
  PRIMARY KEY (`id_konfig`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dokumen_karyawan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dokumen_karyawan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_karyawan` int NOT NULL,
  `jenis_dokumen` enum('KTP','NPWP','Offer Letter','Kontrak','Resign','BPJS','Ijazah','Lainnya') NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_karyawan` (`id_karyawan`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jadwal_kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jadwal_kelas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_kelas` int NOT NULL,
  `id_guru` int NOT NULL,
  `hari` tinyint(1) NOT NULL COMMENT '1=Senin, 7=Minggu',
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `id_branch` int NOT NULL,
  `tipe_kelas` enum('anak','dewasa') DEFAULT 'anak',
  `ruangan` varchar(50) DEFAULT NULL,
  `jenis_jadwal` enum('Regular','Trial Class','Placement Test') DEFAULT 'Regular',
  `keterangan` text,
  `max_pertemuan_bulan` int DEFAULT '8',
  `is_aktif` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_guru` (`id_guru`),
  KEY `idx_kelas` (`id_kelas`),
  KEY `idx_branch` (`id_branch`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `karyawan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `karyawan` (
  `id_karyawan` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `no_ktp` varchar(20) DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `alamat` text,
  `npwp` varchar(30) DEFAULT NULL,
  `status_ktp` enum('Menikah','Belum Menikah','Cerai') DEFAULT 'Belum Menikah',
  `tanggal_mulai_kerja` date NOT NULL,
  `posisi` varchar(100) DEFAULT NULL,
  `jenis_karyawan` enum('Freelance','Full Time','Part Time') DEFAULT 'Full Time',
  `status_karyawan` enum('Aktif','Dipecat','Mengundurkan Diri') DEFAULT 'Aktif',
  `tanggal_keluar` date DEFAULT NULL,
  `gaji_pokok` decimal(14,2) DEFAULT '0.00',
  `tunjangan` decimal(14,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_karyawan`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lead_kontak_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_kontak_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_peserta` int NOT NULL,
  `tgl_kontak` date NOT NULL,
  `catatan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `tgl_update` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_peserta` (`id_peserta`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lead_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_status_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_peserta` int NOT NULL,
  `status_lama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_baru` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tgl_update` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_peserta` (`id_peserta`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `libur_nasional`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `libur_nasional` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `keterangan` varchar(200) NOT NULL,
  `jenis_libur` enum('Nasional','Cuti Bersama','Libur Khusus') DEFAULT 'Nasional',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tanggal` (`tanggal`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mst_level_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mst_level_siswa` (
  `id_level` int NOT NULL AUTO_INCREMENT,
  `nama_level` varchar(50) NOT NULL,
  `deskripsi` text,
  `urutan_level` int NOT NULL DEFAULT '0',
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_level`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payroll_guru`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_guru` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_guru` int NOT NULL,
  `bulan` tinyint NOT NULL,
  `tahun` year NOT NULL,
  `total_jam_ajar_anak` decimal(6,2) DEFAULT '0.00',
  `total_jam_ajar_dewasa` decimal(6,2) DEFAULT '0.00',
  `tarif_per_jam_anak` decimal(12,2) DEFAULT '0.00',
  `tarif_per_jam_dewasa` decimal(12,2) DEFAULT '0.00',
  `total_honor_anak` decimal(14,2) DEFAULT '0.00',
  `total_honor_dewasa` decimal(14,2) DEFAULT '0.00',
  `total_honor` decimal(14,2) DEFAULT '0.00',
  `total_hari_hadir` int DEFAULT '0',
  `total_kedatangan` int DEFAULT '0',
  `biaya_transport_per_hari` decimal(12,2) DEFAULT '0.00',
  `total_transport` decimal(14,2) DEFAULT '0.00',
  `subtotal` decimal(14,2) DEFAULT '0.00',
  `pph21_persen` decimal(5,2) DEFAULT '0.00',
  `pph21_nominal` decimal(14,2) DEFAULT '0.00',
  `total_potongan` decimal(14,2) DEFAULT '0.00',
  `total_gaji_bersih` decimal(14,2) DEFAULT '0.00',
  `status` enum('draft','approved','paid') DEFAULT 'draft',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `id_pengeluaran` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_guru_bulan` (`id_guru`,`bulan`,`tahun`),
  KEY `idx_guru` (`id_guru`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payroll_potongan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_potongan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payroll_type` enum('guru','staff') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_payroll` int NOT NULL,
  `nominal` decimal(14,2) NOT NULL DEFAULT '0.00',
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payroll` (`payroll_type`,`id_payroll`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payroll_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_karyawan` int NOT NULL,
  `bulan` tinyint NOT NULL,
  `tahun` year NOT NULL,
  `gaji_pokok` decimal(14,2) DEFAULT '0.00',
  `tunjangan` decimal(14,2) DEFAULT '0.00',
  `potongan` decimal(14,2) DEFAULT '0.00',
  `subtotal` decimal(14,2) DEFAULT '0.00',
  `pph21_persen` decimal(5,2) DEFAULT '0.00',
  `pph21_nominal` decimal(14,2) DEFAULT '0.00',
  `total_potongan` decimal(14,2) DEFAULT '0.00',
  `total_gaji_bersih` decimal(14,2) DEFAULT '0.00',
  `keterangan` text,
  `status` enum('draft','approved','paid') DEFAULT 'draft',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `id_pengeluaran` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_karyawan_bulan` (`id_karyawan`,`bulan`,`tahun`),
  KEY `idx_karyawan` (`id_karyawan`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `penempatan_guru`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penempatan_guru` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_guru` int NOT NULL,
  `id_kelas` int NOT NULL,
  `id_branch` int NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'aktif',
  `catatan` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_guru` (`id_guru`),
  KEY `idx_kelas` (`id_kelas`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pengajar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengajar` (
  `id_pengajar` int NOT NULL AUTO_INCREMENT,
  `id_karyawan` int DEFAULT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `jk` enum('L','P') DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat` text,
  `no_rek` varchar(40) NOT NULL,
  `tarif_per_jam_anak` decimal(12,2) DEFAULT '50000.00',
  `tarif_per_jam_dewasa` decimal(12,2) DEFAULT '75000.00',
  `biaya_transport` decimal(12,2) DEFAULT '25000.00',
  PRIMARY KEY (`id_pengajar`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pengajar_jadwal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengajar_jadwal` (
  `id_jadwal_pengajar` int NOT NULL AUTO_INCREMENT,
  `id_pengajar` int DEFAULT NULL,
  `hari` tinyint NOT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  PRIMARY KEY (`id_jadwal_pengajar`),
  KEY `pengajar_id` (`id_pengajar`),
  CONSTRAINT `pengajar_jadwal_ibfk_1` FOREIGN KEY (`id_pengajar`) REFERENCES `pengajar` (`id_pengajar`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=180 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pengeluaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengeluaran` (
  `id_pengeluaran` int NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `keterangan` text,
  `jumlah` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `referensi_tabel` varchar(50) DEFAULT NULL,
  `referensi_id` int DEFAULT NULL,
  PRIMARY KEY (`id_pengeluaran`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pertemuan_kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pertemuan_kelas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_jadwal_kelas` int NOT NULL,
  `tanggal` date NOT NULL,
  `bulan` tinyint NOT NULL,
  `tahun` year NOT NULL,
  `status` enum('terlaksana','batal','reschedule') DEFAULT 'terlaksana',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jadwal` (`id_jadwal_kelas`),
  KEY `idx_bulan_tahun` (`bulan`,`tahun`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `peserta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `peserta` (
  `id_peserta` int NOT NULL AUTO_INCREMENT,
  `nama_ortu` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) NOT NULL,
  `alamat_ortu` varchar(50) DEFAULT NULL,
  `nama_anak` varchar(50) NOT NULL,
  `tgl_lahir_anak` date DEFAULT NULL,
  `alamat_anak` varchar(50) DEFAULT NULL,
  `email` varchar(30) DEFAULT NULL,
  `tgl_terakhir_dihubungi` date DEFAULT NULL,
  `src` varchar(50) DEFAULT NULL,
  `status` varchar(40) NOT NULL,
  `catatan` varchar(50) NOT NULL,
  `jk` enum('L','P') NOT NULL,
  `level_sekolah` enum('TK','SD','SMP','SMA') DEFAULT NULL,
  `nama_sekolah` varchar(100) DEFAULT NULL,
  `id_jenis_kelas` int DEFAULT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `tgl_non_aktif` date NOT NULL,
  `status_siswa` varchar(30) NOT NULL DEFAULT 'Aktif',
  `jenis_siswa` enum('regular','partnership') DEFAULT 'regular',
  `alasan_nonaktif` varchar(50) DEFAULT NULL,
  `alasan_lainnya` text,
  `tanggal_nonaktif` date DEFAULT NULL,
  `input_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_referral` int DEFAULT NULL COMMENT 'ID siswa yang mereferensikan',
  `referral_name` varchar(100) DEFAULT NULL COMMENT 'Nama siswa yang mereferensikan',
  `tgl_konversi_siswa` datetime DEFAULT NULL,
  PRIMARY KEY (`id_peserta`),
  KEY `id_jenis_kelas` (`id_jenis_kelas`),
  KEY `idx_referral` (`id_referral`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `peserta_jadwal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `peserta_jadwal` (
  `id_jadwal_peserta` int NOT NULL AUTO_INCREMENT,
  `id_peserta` int NOT NULL,
  `id_jadwal_pengajar` int NOT NULL,
  `id_jadwal_kelas` int DEFAULT NULL,
  PRIMARY KEY (`id_jadwal_peserta`),
  KEY `id_peserta` (`id_peserta`),
  CONSTRAINT `peserta_jadwal_ibfk_1` FOREIGN KEY (`id_peserta`) REFERENCES `peserta` (`id_peserta`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pph21_komponen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pph21_komponen` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_komponen` varchar(100) NOT NULL,
  `batas_bawah` decimal(14,2) DEFAULT '0.00',
  `batas_atas` decimal(14,2) DEFAULT '0.00',
  `persentase` decimal(5,2) NOT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reminder_belajar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reminder_belajar` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `no_hp` varchar(20) NOT NULL,
  `id_peserta` int NOT NULL,
  `tahun` int NOT NULL,
  `bulan` int NOT NULL,
  `pesan` text NOT NULL,
  `response` varchar(30) NOT NULL,
  `waktu_kirim` datetime DEFAULT CURRENT_TIMESTAMP,
  `keterangan` text,
  PRIMARY KEY (`id_log`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reminder_tagihan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reminder_tagihan` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `no_hp` varchar(20) NOT NULL,
  `id_peserta` int NOT NULL,
  `tahun` int NOT NULL,
  `bulan` int NOT NULL,
  `pesan` text NOT NULL,
  `response` varchar(30) NOT NULL,
  `waktu_kirim` datetime DEFAULT CURRENT_TIMESTAMP,
  `keterangan` text,
  PRIMARY KEY (`id_log`)
) ENGINE=InnoDB AUTO_INCREMENT=940 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reschedule_kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reschedule_kelas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_jadwal_kelas` int NOT NULL,
  `tanggal_lama` date NOT NULL,
  `jam_lama_mulai` time NOT NULL,
  `jam_lama_selesai` time NOT NULL,
  `tanggal_baru` date NOT NULL,
  `jam_baru_mulai` time NOT NULL,
  `jam_baru_selesai` time NOT NULL,
  `alasan` text NOT NULL,
  `jenis_jadwal` enum('Regular','Trial Class','Placement Test') DEFAULT 'Regular',
  `keterangan` text,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jadwal` (`id_jadwal_kelas`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `riwayat_level_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `riwayat_level_siswa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `id_level` int NOT NULL,
  `tanggal_kenaikan_level` date NOT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `is_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `catatan` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_siswa` (`id_siswa`),
  KEY `idx_level` (`id_level`),
  CONSTRAINT `fk_riwayat_level` FOREIGN KEY (`id_level`) REFERENCES `mst_level_siswa` (`id_level`),
  CONSTRAINT `fk_riwayat_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `peserta` (`id_peserta`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=197 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sertifikat_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sertifikat_siswa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `id_level` int NOT NULL,
  `id_guru` int DEFAULT NULL,
  `nomor_sertifikat` varchar(50) NOT NULL,
  `tanggal_terbit` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sertifikat` (`nomor_sertifikat`),
  KEY `idx_siswa` (`id_siswa`),
  KEY `fk_sertifikat_level` (`id_level`),
  CONSTRAINT `fk_sertifikat_level` FOREIGN KEY (`id_level`) REFERENCES `mst_level_siswa` (`id_level`),
  CONSTRAINT `fk_sertifikat_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `peserta` (`id_peserta`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tagihan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tagihan` (
  `id_tagihan` int NOT NULL AUTO_INCREMENT,
  `id_peserta` int NOT NULL,
  `bulan` int NOT NULL,
  `tahun` int NOT NULL,
  `jumlah` decimal(10,2) NOT NULL,
  `status_bayar` enum('Pending','Paid','Late','Refund') DEFAULT 'Pending',
  `is_prorata` enum('0','1') DEFAULT '0',
  `jml_pertemuan` int DEFAULT '8',
  `metode_pembayaran` enum('Cash','Qris','Bank Transfer') DEFAULT NULL,
  `tgl_bayar` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  `tipe` enum('Biaya Kelas','Biaya Registrasi','Biaya Buku','') NOT NULL,
  PRIMARY KEY (`id_tagihan`),
  KEY `id_peserta` (`id_peserta`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tagihan_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tagihan_detail` (
  `id_tagihan_detail` int NOT NULL AUTO_INCREMENT,
  `id_tagihan` int NOT NULL,
  `id_peserta` int NOT NULL,
  `nama_siswa` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `tipe_biaya` enum('Biaya Registrasi','Biaya Kelas','Biaya Buku') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nilai_biaya` decimal(15,2) NOT NULL DEFAULT '0.00',
  `tipe_diskon` enum('','Diskon Cuti','Diskon Registrasi','Diskon Buku','Diskon Referal','Diskon Umum') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '',
  `nilai_diskon` decimal(15,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tagihan_detail`),
  KEY `fk_tagihan_detail_tagihan` (`id_tagihan`),
  KEY `fk_tagihan_detail_peserta` (`id_peserta`),
  CONSTRAINT `fk_tagihan_detail_peserta` FOREIGN KEY (`id_peserta`) REFERENCES `peserta` (`id_peserta`) ON DELETE CASCADE,
  CONSTRAINT `fk_tagihan_detail_tagihan` FOREIGN KEY (`id_tagihan`) REFERENCES `tagihan` (`id_tagihan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=864 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ujian_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ujian_siswa` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_siswa` int NOT NULL,
  `tanggal_ujian` date NOT NULL,
  `jenis_ujian` enum('Placement Test','Ujian Naik Level') NOT NULL,
  `nilai_vocabulary` decimal(5,2) DEFAULT NULL,
  `nilai_grammar` decimal(5,2) DEFAULT NULL,
  `nilai_speaking` decimal(5,2) DEFAULT NULL,
  `nilai_writing` decimal(5,2) DEFAULT NULL,
  `nilai_listening` decimal(5,2) DEFAULT NULL,
  `nilai_ujian` decimal(5,2) NOT NULL,
  `catatan_ujian` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_siswa` (`id_siswa`),
  CONSTRAINT `fk_ujian_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `peserta` (`id_peserta`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `jabatan` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_tps` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_kel` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_kec` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jk` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `usia` int NOT NULL,
  `no_hp` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `id_relawan` int DEFAULT NULL,
  `id_karyawan` int DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

