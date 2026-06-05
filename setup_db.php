<?php
$host = "localhost";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Buat database
$conn->query("CREATE DATABASE IF NOT EXISTS db_pkl");
$conn->select_db("db_pkl");

// Buat tabel
$tables = [
"CREATE TABLE IF NOT EXISTS `user` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','pembimbing','siswa') NOT NULL DEFAULT 'siswa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS `siswa` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    nama_lengkap VARCHAR(100),
    nisn VARCHAR(20),
    kelas VARCHAR(20),
    jurusan VARCHAR(100),
    no_telp VARCHAR(20),
    alamat TEXT,
    FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS `pembimbing` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    nama_lengkap VARCHAR(100),
    nip VARCHAR(30),
    no_telp VARCHAR(20),
    keahlian VARCHAR(100),
    kuota INT DEFAULT 10,
    FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS `perusahaan` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_perusahaan VARCHAR(100) NOT NULL,
    alamat TEXT,
    bidang_usaha VARCHAR(100),
    kuota INT DEFAULT 5,
    no_telp VARCHAR(20),
    email VARCHAR(100),
    kontak_person VARCHAR(100)
)",
"CREATE TABLE IF NOT EXISTS `pendaftaran` (
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
    FOREIGN KEY (user_id) REFERENCES `user`(id) ON DELETE CASCADE,
    FOREIGN KEY (perusahaan_id) REFERENCES `perusahaan`(id) ON DELETE CASCADE,
    FOREIGN KEY (pembimbing_id) REFERENCES `pembimbing`(id) ON DELETE SET NULL
)",
"CREATE TABLE IF NOT EXISTS `logbook` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pendaftaran_id INT NOT NULL,
    tanggal DATE NOT NULL,
    aktivitas TEXT NOT NULL,
    status_verifikasi ENUM('Menunggu','Diverifikasi') DEFAULT 'Menunggu',
    catatan_pembimbing TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pendaftaran_id) REFERENCES `pendaftaran`(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS `penilaian` (
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
    FOREIGN KEY (pendaftaran_id) REFERENCES `pendaftaran`(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS `pengumuman` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(200) NOT NULL,
    isi TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES `user`(id) ON DELETE CASCADE
)"
];

foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        die("Gagal buat tabel: " . $conn->error);
    }
}

// Buat akun default jika belum ada
$akun = [
    ['admin',      'admin123',      'admin'],
    ['pembimbing', 'pembimbing123', 'pembimbing'],
    ['siswa',      'siswa123',      'siswa'],
];

foreach ($akun as [$username, $password, $role]) {
    $cek = $conn->query("SELECT id FROM user WHERE username='$username'")->num_rows;
    if ($cek === 0) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $conn->query("INSERT INTO user (username, password, role) VALUES ('$username', '$hash', '$role')");

        // Buat profil pembimbing default
        if ($role === 'pembimbing') {
            $uid = $conn->insert_id;
            $conn->query("INSERT INTO pembimbing (user_id, nama_lengkap, kuota) VALUES ($uid, 'Pembimbing Default', 10)");
        }
        // Buat profil siswa default
        if ($role === 'siswa') {
            $uid = $conn->insert_id;
            $conn->query("INSERT INTO siswa (user_id, nama_lengkap, nisn, kelas, jurusan) VALUES ($uid, 'Siswa Default', '0000000000', 'XII', 'RPL')");
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Setup Database</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:Arial,sans-serif; background:#f0f4f8; display:flex; justify-content:center; align-items:center; min-height:100vh; }
        .box { background:white; border-radius:16px; padding:40px; max-width:480px; width:100%; box-shadow:0 10px 40px rgba(0,0,0,0.1); text-align:center; }
        .icon { font-size:56px; margin-bottom:16px; }
        h2 { color:#333; margin-bottom:8px; }
        p  { color:#666; font-size:14px; margin-bottom:24px; }
        table { width:100%; border-collapse:collapse; margin-bottom:24px; text-align:left; }
        th { background:#f0f4f8; padding:10px 14px; font-size:13px; color:#555; }
        td { padding:10px 14px; font-size:13px; border-bottom:1px solid #eee; }
        td b { color:#3C91E6; }
        .btn { display:inline-block; padding:12px 32px; background:#3C91E6; color:white; border-radius:8px; text-decoration:none; font-size:15px; font-weight:600; }
        .btn:hover { background:#1a5fa8; }
        .note { font-size:12px; color:#999; margin-top:12px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">✅</div>
        <h2>Database Berhasil Dibuat!</h2>
        <p>Semua tabel dan akun default sudah siap digunakan.</p>

        <table>
            <tr><th>Role</th><th>Username</th><th>Password</th></tr>
            <tr><td>Admin</td><td><b>admin</b></td><td>admin123</td></tr>
            <tr><td>Pembimbing</td><td><b>pembimbing</b></td><td>pembimbing123</td></tr>
            <tr><td>Siswa</td><td><b>siswa</b></td><td>siswa123</td></tr>
        </table>

        <a href="index.html" class="btn">Mulai Login →</a>
        <p class="note">Hapus file setup_db.php setelah setup selesai.</p>
    </div>
</body>
</html>
