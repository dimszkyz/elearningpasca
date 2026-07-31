/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-13.0.1-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: elearningpasca_new
-- ------------------------------------------------------
-- Server version	13.0.1-MariaDB-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `mdl_local_siakad_user`
--

DROP TABLE IF EXISTS `mdl_local_siakad_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mdl_local_siakad_user` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `sourceid` varchar(100) DEFAULT NULL,
  `username` varchar(100) NOT NULL DEFAULT '',
  `fullname` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'mahasiswa',
  `moodleuserid` bigint(10) NOT NULL DEFAULT 0,
  `timemodified` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_locasiakuser_use_uix` (`username`),
  UNIQUE KEY `mdl_locasiakuser_sou_uix` (`sourceid`),
  KEY `mdl_locasiakuser_moo_ix` (`moodleuserid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='SIAKAD user identities synced to Moodle';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mdl_local_siakad_user`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `mdl_local_siakad_user` WRITE;
/*!40000 ALTER TABLE `mdl_local_siakad_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `mdl_local_siakad_user` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `mdl_local_siakad_prodi`
--

DROP TABLE IF EXISTS `mdl_local_siakad_prodi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mdl_local_siakad_prodi` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `sourceid` varchar(100) DEFAULT NULL,
  `kode` varchar(50) NOT NULL DEFAULT '',
  `nama` varchar(255) NOT NULL DEFAULT '',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `categoryid` bigint(10) NOT NULL DEFAULT 0,
  `timemodified` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_locasiakprod_kod_uix` (`kode`),
  UNIQUE KEY `mdl_locasiakprod_sou_uix` (`sourceid`),
  KEY `mdl_locasiakprod_cat_ix` (`categoryid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='SIAKAD study programs and Moodle category mappings';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mdl_local_siakad_prodi`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `mdl_local_siakad_prodi` WRITE;
/*!40000 ALTER TABLE `mdl_local_siakad_prodi` DISABLE KEYS */;
/*!40000 ALTER TABLE `mdl_local_siakad_prodi` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `mdl_local_siakad_mahasiswa`
--

DROP TABLE IF EXISTS `mdl_local_siakad_mahasiswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mdl_local_siakad_mahasiswa` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `sourceid` varchar(100) DEFAULT NULL,
  `userid` bigint(10) NOT NULL,
  `moodleuserid` bigint(10) NOT NULL DEFAULT 0,
  `nim` varchar(50) NOT NULL DEFAULT '',
  `nama` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) DEFAULT NULL,
  `prodiid` bigint(10) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'aktif',
  `timemodified` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_locasiakmaha_nim_uix` (`nim`),
  UNIQUE KEY `mdl_locasiakmaha_sou_uix` (`sourceid`),
  KEY `mdl_locasiakmaha_moo_ix` (`moodleuserid`),
  KEY `mdl_locasiakmaha_sta_ix` (`status`),
  KEY `mdl_locasiakmaha_use_ix` (`userid`),
  KEY `mdl_locasiakmaha_pro_ix` (`prodiid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='SIAKAD students';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mdl_local_siakad_mahasiswa`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `mdl_local_siakad_mahasiswa` WRITE;
/*!40000 ALTER TABLE `mdl_local_siakad_mahasiswa` DISABLE KEYS */;
/*!40000 ALTER TABLE `mdl_local_siakad_mahasiswa` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `mdl_local_siakad_dosen`
--

DROP TABLE IF EXISTS `mdl_local_siakad_dosen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mdl_local_siakad_dosen` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `sourceid` varchar(100) DEFAULT NULL,
  `userid` bigint(10) NOT NULL,
  `moodleuserid` bigint(10) NOT NULL DEFAULT 0,
  `nidn` varchar(50) NOT NULL DEFAULT '',
  `nama` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) DEFAULT NULL,
  `prodiid` bigint(10) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'aktif',
  `timemodified` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_locasiakdose_nid_uix` (`nidn`),
  UNIQUE KEY `mdl_locasiakdose_sou_uix` (`sourceid`),
  KEY `mdl_locasiakdose_moo_ix` (`moodleuserid`),
  KEY `mdl_locasiakdose_use_ix` (`userid`),
  KEY `mdl_locasiakdose_pro_ix` (`prodiid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='SIAKAD lecturers';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mdl_local_siakad_dosen`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `mdl_local_siakad_dosen` WRITE;
/*!40000 ALTER TABLE `mdl_local_siakad_dosen` DISABLE KEYS */;
/*!40000 ALTER TABLE `mdl_local_siakad_dosen` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `mdl_local_siakad_tagihan`
--

DROP TABLE IF EXISTS `mdl_local_siakad_tagihan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mdl_local_siakad_tagihan` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `sourceid` varchar(100) DEFAULT NULL,
  `mahasiswaid` bigint(10) NOT NULL,
  `kodetagihan` varchar(100) NOT NULL DEFAULT '',
  `tahunajaran` varchar(20) NOT NULL DEFAULT '',
  `semester` varchar(20) NOT NULL DEFAULT '',
  `jenis` varchar(100) NOT NULL DEFAULT 'UKT',
  `nominal` bigint(10) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'belum_lunas',
  `wajib` tinyint(1) NOT NULL DEFAULT 1,
  `paidat` bigint(10) NOT NULL DEFAULT 0,
  `duedate` bigint(10) NOT NULL DEFAULT 0,
  `timemodified` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mdl_locasiaktagi_kod_uix` (`kodetagihan`),
  UNIQUE KEY `mdl_locasiaktagi_sou_uix` (`sourceid`),
  KEY `mdl_locasiaktagi_mahtahsemw_ix` (`mahasiswaid`,`tahunajaran`,`semester`,`wajib`,`status`),
  KEY `mdl_locasiaktagi_mah_ix` (`mahasiswaid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='SIAKAD student bills used by the exam gate';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mdl_local_siakad_tagihan`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `mdl_local_siakad_tagihan` WRITE;
/*!40000 ALTER TABLE `mdl_local_siakad_tagihan` DISABLE KEYS */;
/*!40000 ALTER TABLE `mdl_local_siakad_tagihan` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `mdl_local_siakad_synclog`
--

DROP TABLE IF EXISTS `mdl_local_siakad_synclog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mdl_local_siakad_synclog` (
  `id` bigint(10) NOT NULL AUTO_INCREMENT,
  `source` varchar(30) NOT NULL DEFAULT 'manual',
  `status` varchar(20) NOT NULL DEFAULT 'success',
  `inserted` bigint(10) NOT NULL DEFAULT 0,
  `updated` bigint(10) NOT NULL DEFAULT 0,
  `failed` bigint(10) NOT NULL DEFAULT 0,
  `message` longtext DEFAULT NULL,
  `timecreated` bigint(10) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `mdl_locasiaksync_tim_ix` (`timecreated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=COMPRESSED COMMENT='SIAKAD synchronisation audit log';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mdl_local_siakad_synclog`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `mdl_local_siakad_synclog` WRITE;
/*!40000 ALTER TABLE `mdl_local_siakad_synclog` DISABLE KEYS */;
/*!40000 ALTER TABLE `mdl_local_siakad_synclog` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-07-28  8:27:30
