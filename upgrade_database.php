<?php
include('db.php');

echo "<h2>🔧 Upgrading Database...</h2>";

// 1. Tabel Activity Log
$sql = "CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "✅ Tabel activity_log berhasil dibuat<br>";
} else {
    echo "⚠️ activity_log: " . $conn->error . "<br>";
}

// 2. Update tabel pendaftaran - tambah status lebih detail
$conn->query("ALTER TABLE pendaftaran MODIFY COLUMN status ENUM('Menunggu Verifikasi','Diterima','Ditolak','Sedang PKL','Menunggu Penilaian','Selesai') DEFAULT 'Menunggu Verifikasi'");
echo "✅ Status pendaftaran diperluas<br>";

// 3. Update existing data status
$conn->query("UPDATE pendaftaran SET status = 'Menunggu Verifikasi' WHERE status = 'Menunggu'");
$conn->query("UPDATE pendaftaran SET status = 'Sedang PKL' WHERE status = 'Diterima'");
echo "✅ Data status diupdate<br>";

// 4. Tambah kolom bidang keahlian pembimbing (jika belum ada)
$result = $conn->query("SHOW COLUMNS FROM pembimbing LIKE 'spesialisasi'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE pembimbing ADD COLUMN spesialisasi VARCHAR(100) AFTER keahlian");
    echo "✅ Kolom spesialisasi pembimbing ditambahkan<br>";
}

// 5. Tambah kolom untuk tracking upload sertifikat
$result = $conn->query("SHOW COLUMNS FROM pendaftaran LIKE 'sertifikat_uploaded_at'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE pendaftaran ADD COLUMN sertifikat_uploaded_at TIMESTAMP NULL AFTER sertifikat");
    echo "✅ Kolom tracking sertifikat ditambahkan<br>";
}

// 6. Tambah kolom kategori pengumuman
$result = $conn->query("SHOW COLUMNS FROM pengumuman LIKE 'kategori'");
if ($result->num_rows == 0) {
    $conn->query("ALTER TABLE pengumuman ADD COLUMN kategori ENUM('Umum','Penting','Info','Pengingat') DEFAULT 'Umum' AFTER judul");
    $conn->query("ALTER TABLE pengumuman ADD COLUMN target_role ENUM('all','siswa','pembimbing','admin') DEFAULT 'all' AFTER kategori");
    $conn->query("ALTER TABLE pengumuman ADD COLUMN expired_at DATE NULL AFTER isi");
    echo "✅ Fitur pengumuman ditingkatkan<br>";
}

// 7. Index untuk performance
$conn->query("CREATE INDEX idx_pendaftaran_status ON pendaftaran(status)");
$conn->query("CREATE INDEX idx_pendaftaran_created ON pendaftaran(created_at)");
$conn->query("CREATE INDEX idx_activity_log_user ON activity_log(user_id)");
$conn->query("CREATE INDEX idx_activity_log_created ON activity_log(created_at)");
echo "✅ Database indexes ditambahkan<br>";

echo "<br><h3>✅ Database Upgrade Selesai!</h3>";
echo "<p><a href='dashboard_admin.php'>← Kembali ke Dashboard</a></p>";
?>
