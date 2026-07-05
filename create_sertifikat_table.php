<?php
include('db.php');

$sql = "CREATE TABLE IF NOT EXISTS sertifikat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pendaftaran_id INT NOT NULL UNIQUE,
    nomor_sertifikat VARCHAR(50) NOT NULL UNIQUE,
    tanggal_terbit DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pendaftaran_id) REFERENCES pendaftaran(id) ON DELETE CASCADE,
    INDEX idx_pendaftaran (pendaftaran_id)
)";

if ($conn->query($sql)) {
    echo "<h2 style='color:green;'>✓ Tabel sertifikat berhasil dibuat/sudah ada</h2>";
} else {
    echo "<h2 style='color:red;'>✗ Error: " . $conn->error . "</h2>";
}

$conn->close();
?>
