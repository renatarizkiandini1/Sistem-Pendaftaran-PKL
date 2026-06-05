<?php
$host     = "localhost";
$user     = "root";
$password = "";
$database = "db_pkl";

$conn = new mysqli($host, $user, $password, $database);
mysqli_report(MYSQLI_REPORT_OFF);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>
