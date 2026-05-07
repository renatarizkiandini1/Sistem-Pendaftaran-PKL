CREATE DATABASE IF NOT EXISTS db_pkl;
USE db_pkl;

CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'siswa') NOT NULL DEFAULT 'siswa'
);

CREATE TABLE IF NOT EXISTS siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nama_lengkap VARCHAR(100),
    nisn VARCHAR(20),
    kelas VARCHAR(20),
    jurusan VARCHAR(100),
    no_telp VARCHAR(20),
    alamat TEXT,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS perusahaan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_perusahaan VARCHAR(100) NOT NULL,
    alamat TEXT,
    bidang_usaha VARCHAR(100),
    kuota INT DEFAULT 0,
    no_telp VARCHAR(20),
    email VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS pendaftaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    perusahaan_id INT NOT NULL,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    dokumen VARCHAR(255),
    status ENUM('Menunggu', 'Diterima', 'Ditolak') DEFAULT 'Menunggu',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (perusahaan_id) REFERENCES perusahaan(id) ON DELETE CASCADE
);
