<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}
include('db.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama_lengkap   = $_POST["nama_lengkap"];
    $jenis_kelamin  = $_POST["jenis_kelamin"];
    $nomor_telepon  = $_POST["nomor_telepon"];
    $alamat         = $_POST["alamat"];
    $nama_sekolah   = $_POST["nama_sekolah"];
    $program_studi  = $_POST["program_studi"];

    if (isset($_POST["id"]) && !empty($_POST["id"])) {
        $id = $_POST["id"];
        $stmt = $conn->prepare("UPDATE profile SET nama_lengkap=?, jenis_kelamin=?, nomor_telepon=?, alamat=?, nama_sekolah=?, program_studi=? WHERE id=?");
        $stmt->bind_param("ssssssi", $nama_lengkap, $jenis_kelamin, $nomor_telepon, $alamat, $nama_sekolah, $program_studi, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO profile (nama_lengkap, jenis_kelamin, nomor_telepon, alamat, nama_sekolah, program_studi) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nama_lengkap, $jenis_kelamin, $nomor_telepon, $alamat, $nama_sekolah, $program_studi);
    }

    if ($stmt->execute()) {
        echo "<h2>Data berhasil disimpan!</h2>";
    } else {
        echo "Error: " . $conn->error;
    }
    $stmt->close();
}

$conn->close();
echo "<a href='profile.php'><button>Kembali ke Form</button></a>";
?>
