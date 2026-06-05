<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username     = trim($_POST['username']);
    $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama_lengkap = $_POST['nama_lengkap'];
    $nisn         = $_POST['nisn'];
    $kelas        = $_POST['kelas'];
    $jurusan      = $_POST['jurusan'];
    $no_telp      = $_POST['no_telp'] ?? '';

    // Cek username
    $cek = $conn->prepare("SELECT id FROM user WHERE username = ?");
    $cek->bind_param("s", $username);
    $cek->execute();
    $cek->store_result();
    if ($cek->num_rows > 0) {
        header("Location: register.html?error=username_taken");
        exit();
    }
    $cek->close();

    // Insert user
    $stmt = $conn->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, 'siswa')");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $user_id = $conn->insert_id;
    $stmt->close();

    // Insert profil siswa
    $stmt = $conn->prepare("INSERT INTO siswa (user_id, nama_lengkap, nisn, kelas, jurusan, no_telp) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss", $user_id, $nama_lengkap, $nisn, $kelas, $jurusan, $no_telp);
    $stmt->execute();
    $stmt->close();

    header("Location: index.html");
    exit();
}
?>
