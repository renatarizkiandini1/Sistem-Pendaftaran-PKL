<?php
include('db.php');

$username = 'pembimbing';
$password = 'pembimbing123';
$role = 'pembimbing';
$nama = 'Pembimbing Test';

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Cek apakah username sudah ada
$stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<h2 style='color: red;'>❌ Akun pembimbing sudah ada!</h2>";
    echo "<p>Username: <strong>$username</strong></p>";
} else {
    // Insert akun baru
    $stmt = $conn->prepare("INSERT INTO user (username, password, role, nama) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $hashed_password, $role, $nama);
    
    if ($stmt->execute()) {
        echo "<h2 style='color: green;'>✅ Akun pembimbing berhasil dibuat!</h2>";
        echo "<p><strong>Username:</strong> $username</p>";
        echo "<p><strong>Password:</strong> $password</p>";
        echo "<p><strong>Role:</strong> $role</p>";
        echo "<p><br><a href='index.html'>Kembali ke Login</a></p>";
    } else {
        echo "<h2 style='color: red;'>❌ Gagal membuat akun: " . $stmt->error . "</h2>";
    }
}

$stmt->close();
$conn->close();
?>
