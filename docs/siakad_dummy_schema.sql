-- Reference schema for an external dummy SIAKAD database.
CREATE DATABASE IF NOT EXISTS siakad_dummy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE siakad_dummy;

CREATE TABLE IF NOT EXISTS `user` (
  id VARCHAR(100) PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  fullname VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  role ENUM('mahasiswa','dosen','admin') NOT NULL,
  moodle_user_id BIGINT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prodi (
  id VARCHAR(100) PRIMARY KEY,
  kode VARCHAR(50) NOT NULL UNIQUE,
  nama VARCHAR(255) NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  moodle_category_id BIGINT UNSIGNED NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mahasiswa (
  id VARCHAR(100) PRIMARY KEY,
  user_id VARCHAR(100) NOT NULL,
  nim VARCHAR(50) NOT NULL UNIQUE,
  nama VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  prodi_id VARCHAR(100) NOT NULL,
  status ENUM('aktif','cuti','lulus','nonaktif') NOT NULL DEFAULT 'aktif',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT mahasiswa_user_fk FOREIGN KEY (user_id) REFERENCES `user`(id),
  CONSTRAINT mahasiswa_prodi_fk FOREIGN KEY (prodi_id) REFERENCES prodi(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dosen (
  id VARCHAR(100) PRIMARY KEY,
  user_id VARCHAR(100) NOT NULL,
  nidn VARCHAR(50) NOT NULL UNIQUE,
  nama VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  prodi_id VARCHAR(100) NOT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT dosen_user_fk FOREIGN KEY (user_id) REFERENCES `user`(id),
  CONSTRAINT dosen_prodi_fk FOREIGN KEY (prodi_id) REFERENCES prodi(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tagihan (
  id VARCHAR(100) PRIMARY KEY,
  mahasiswa_id VARCHAR(100) NOT NULL,
  kode_tagihan VARCHAR(100) NOT NULL UNIQUE,
  tahun_ajaran VARCHAR(20) NOT NULL,
  semester ENUM('ganjil','genap') NOT NULL,
  jenis VARCHAR(100) NOT NULL DEFAULT 'UKT',
  nominal BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('belum_lunas','lunas','dibatalkan') NOT NULL DEFAULT 'belum_lunas',
  wajib TINYINT(1) NOT NULL DEFAULT 1,
  paid_at DATETIME NULL,
  due_date DATETIME NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY tagihan_gate_ix (mahasiswa_id,tahun_ajaran,semester,wajib,status),
  CONSTRAINT tagihan_mahasiswa_fk FOREIGN KEY (mahasiswa_id) REFERENCES mahasiswa(id)
) ENGINE=InnoDB;
