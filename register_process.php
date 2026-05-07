<?php
session_start();
include('db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Cek apakah username sudah ada
    $stmt = $conn->prepare("SELECT id FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Username sudah digunakan!');window.location.href='register.html';</script>";
    } else {
        $stmt->close();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = 'siswa';

        $stmt = $conn->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashedPassword, $role);

        if ($stmt->execute()) {
            echo "<script>alert('Registrasi berhasil! Silakan login.');window.location.href='index.html';</script>";
        } else {
            echo "<script>alert('Registrasi gagal, coba lagi.');window.location.href='register.html';</script>";
        }
    }
    $stmt->close();
}
$conn->close();
?>
