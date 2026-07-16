-- Dummy external SIAKAD schema for local development.
--
-- This SQL follows the table names requested by stakeholders:
-- user, mahasiswa, prodi, tagihan, dosen.
--
-- The Moodle plugin in this branch uses local_siakad_* tables inside the Moodle
-- database to avoid collisions with Moodle core tables. This external schema is
-- provided as a reference for future integration with the real SIAKAD database.

CREATE DATABASE IF NOT EXISTS siakad_dummy
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE siakad_dummy;

CREATE TABLE IF NOT EXISTS `user` (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  fullname VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  role ENUM('mahasiswa', 'dosen', 'admin') NOT NULL DEFAULT 'mahasiswa',
  moodle_user_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY user_username_uix (username),
  KEY user_moodle_user_id_ix (moodle_user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prodi (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(50) NOT NULL,
  nama VARCHAR(255) NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY prodi_kode_uix (kode)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mahasiswa (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  moodle_user_id BIGINT UNSIGNED NULL,
  nim VARCHAR(50) NOT NULL,
  nama VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  prodi_id BIGINT UNSIGNED NOT NULL,
  status ENUM('aktif', 'cuti', 'lulus', 'nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY mahasiswa_nim_uix (nim),
  KEY mahasiswa_moodle_user_id_ix (moodle_user_id),
  KEY mahasiswa_prodi_id_ix (prodi_id),
  CONSTRAINT mahasiswa_user_fk FOREIGN KEY (user_id) REFERENCES `user` (id),
  CONSTRAINT mahasiswa_prodi_fk FOREIGN KEY (prodi_id) REFERENCES prodi (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dosen (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  moodle_user_id BIGINT UNSIGNED NULL,
  nidn VARCHAR(50) NOT NULL,
  nama VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  prodi_id BIGINT UNSIGNED NULL,
  status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY dosen_nidn_uix (nidn),
  KEY dosen_moodle_user_id_ix (moodle_user_id),
  KEY dosen_prodi_id_ix (prodi_id),
  CONSTRAINT dosen_user_fk FOREIGN KEY (user_id) REFERENCES `user` (id),
  CONSTRAINT dosen_prodi_fk FOREIGN KEY (prodi_id) REFERENCES prodi (id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tagihan (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mahasiswa_id BIGINT UNSIGNED NOT NULL,
  kode_tagihan VARCHAR(100) NOT NULL,
  tahun_ajaran VARCHAR(20) NOT NULL,
  semester VARCHAR(20) NOT NULL,
  nominal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('belum_lunas', 'lunas', 'dibatalkan') NOT NULL DEFAULT 'belum_lunas',
  paid_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY tagihan_kode_uix (kode_tagihan),
  KEY tagihan_mahasiswa_status_ix (mahasiswa_id, status),
  CONSTRAINT tagihan_mahasiswa_fk FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa (id)
) ENGINE=InnoDB;

INSERT INTO prodi (kode, nama, aktif) VALUES
  ('TI', 'Teknologi Informasi', 1),
  ('SI', 'Sistem Informasi', 1),
  ('MNJ', 'Manajemen', 1)
ON DUPLICATE KEY UPDATE nama = VALUES(nama), aktif = VALUES(aktif);

INSERT INTO `user` (username, fullname, email, role, moodle_user_id) VALUES
  ('mhs001', 'Mahasiswa Lunas TI', 'mhs001@example.test', 'mahasiswa', NULL),
  ('mhs002', 'Mahasiswa Belum Lunas TI', 'mhs002@example.test', 'mahasiswa', NULL),
  ('mhs003', 'Mahasiswa Lunas SI', 'mhs003@example.test', 'mahasiswa', NULL),
  ('dsn001', 'Dosen TI', 'dsn001@example.test', 'dosen', NULL)
ON DUPLICATE KEY UPDATE fullname = VALUES(fullname), email = VALUES(email), role = VALUES(role);

INSERT INTO mahasiswa (user_id, moodle_user_id, nim, nama, email, prodi_id, status)
SELECT u.id, NULL, '240001', 'Mahasiswa Lunas TI', 'mhs001@example.test', p.id, 'aktif'
  FROM `user` u JOIN prodi p ON p.kode = 'TI'
 WHERE u.username = 'mhs001'
ON DUPLICATE KEY UPDATE nama = VALUES(nama), email = VALUES(email), prodi_id = VALUES(prodi_id), status = VALUES(status);

INSERT INTO mahasiswa (user_id, moodle_user_id, nim, nama, email, prodi_id, status)
SELECT u.id, NULL, '240002', 'Mahasiswa Belum Lunas TI', 'mhs002@example.test', p.id, 'aktif'
  FROM `user` u JOIN prodi p ON p.kode = 'TI'
 WHERE u.username = 'mhs002'
ON DUPLICATE KEY UPDATE nama = VALUES(nama), email = VALUES(email), prodi_id = VALUES(prodi_id), status = VALUES(status);

INSERT INTO mahasiswa (user_id, moodle_user_id, nim, nama, email, prodi_id, status)
SELECT u.id, NULL, '240003', 'Mahasiswa Lunas SI', 'mhs003@example.test', p.id, 'aktif'
  FROM `user` u JOIN prodi p ON p.kode = 'SI'
 WHERE u.username = 'mhs003'
ON DUPLICATE KEY UPDATE nama = VALUES(nama), email = VALUES(email), prodi_id = VALUES(prodi_id), status = VALUES(status);

INSERT INTO dosen (user_id, moodle_user_id, nidn, nama, email, prodi_id, status)
SELECT u.id, NULL, '001001', 'Dosen TI', 'dsn001@example.test', p.id, 'aktif'
  FROM `user` u JOIN prodi p ON p.kode = 'TI'
 WHERE u.username = 'dsn001'
ON DUPLICATE KEY UPDATE nama = VALUES(nama), email = VALUES(email), prodi_id = VALUES(prodi_id), status = VALUES(status);

INSERT INTO tagihan (mahasiswa_id, kode_tagihan, tahun_ajaran, semester, nominal, status, paid_at)
SELECT m.id, 'UKT-2026-GENAP-240001', '2026/2027', 'genap', 2500000, 'lunas', NOW()
  FROM mahasiswa m
 WHERE m.nim = '240001'
ON DUPLICATE KEY UPDATE nominal = VALUES(nominal), status = VALUES(status), paid_at = VALUES(paid_at);

INSERT INTO tagihan (mahasiswa_id, kode_tagihan, tahun_ajaran, semester, nominal, status, paid_at)
SELECT m.id, 'UKT-2026-GENAP-240002', '2026/2027', 'genap', 2500000, 'belum_lunas', NULL
  FROM mahasiswa m
 WHERE m.nim = '240002'
ON DUPLICATE KEY UPDATE nominal = VALUES(nominal), status = VALUES(status), paid_at = VALUES(paid_at);

INSERT INTO tagihan (mahasiswa_id, kode_tagihan, tahun_ajaran, semester, nominal, status, paid_at)
SELECT m.id, 'UKT-2026-GENAP-240003', '2026/2027', 'genap', 2500000, 'lunas', NOW()
  FROM mahasiswa m
 WHERE m.nim = '240003'
ON DUPLICATE KEY UPDATE nominal = VALUES(nominal), status = VALUES(status), paid_at = VALUES(paid_at);
