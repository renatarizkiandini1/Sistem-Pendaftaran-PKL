<?php
$host = "localhost";
$user = "root"; // atau username MySQL Anda
$password = ""; // kosong jika Anda tidak mengatur password MySQL
$database = "db_pkl"; // pastikan nama database sesuai

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
