CREATE DATABASE IF NOT EXISTS db_pkl;
USE db_pkl;

CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pembimbing', 'siswa') NOT NULL DEFAULT 'siswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    nama_lengkap VARCHAR(100),
    nisn VARCHAR(20),
    kelas VARCHAR(20),
    jurusan VARCHAR(100),
    no_telp VARCHAR(20),
    alamat TEXT,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pembimbing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    nama_lengkap VARCHAR(100),
    nip VARCHAR(30),
    no_telp VARCHAR(20),
    keahlian VARCHAR(100),
    kuota INT DEFAULT 10,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS perusahaan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_perusahaan VARCHAR(100) NOT NULL,
    alamat TEXT,
    bidang_usaha VARCHAR(100),
    kuota INT DEFAULT 5,
    no_telp VARCHAR(20),
    email VARCHAR(100),
    kontak_person VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS pendaftaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    perusahaan_id INT NOT NULL,
    pembimbing_id INT DEFAULT NULL,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    surat_permohonan VARCHAR(255),
    surat_penerimaan VARCHAR(255),
    sertifikat VARCHAR(255),
    status ENUM('Menunggu','Diterima','Ditolak','Selesai') DEFAULT 'Menunggu',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (perusahaan_id) REFERENCES perusahaan(id) ON DELETE CASCADE,
    FOREIGN KEY (pembimbing_id) REFERENCES pembimbing(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS logbook (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pendaftaran_id INT NOT NULL,
    tanggal DATE NOT NULL,
    aktivitas TEXT NOT NULL,
    status_verifikasi ENUM('Menunggu','Diverifikasi') DEFAULT 'Menunggu',
    catatan_pembimbing TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pendaftaran_id) REFERENCES pendaftaran(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS penilaian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pendaftaran_id INT NOT NULL UNIQUE,
    nilai_kedisiplinan INT DEFAULT 0,
    nilai_keterampilan INT DEFAULT 0,
    nilai_sikap INT DEFAULT 0,
    nilai_laporan INT DEFAULT 0,
    nilai_akhir DECIMAL(5,2) DEFAULT 0,
    catatan TEXT,
    status ENUM('Draft','Final') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pendaftaran_id) REFERENCES pendaftaran(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pengumuman (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    isi TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES user(id) ON DELETE CASCADE
);
