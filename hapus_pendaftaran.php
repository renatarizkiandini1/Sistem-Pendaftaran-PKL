<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: index.html"); exit(); }
include('db.php');

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM pendaftaran WHERE user_id = ? AND status = 'Menunggu'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pendaftaran = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($pendaftaran) {
    if ($pendaftaran['dokumen'] && file_exists($pendaftaran['dokumen'])) {
        unlink($pendaftaran['dokumen']);
    }
    $stmt = $conn->prepare("DELETE FROM pendaftaran WHERE user_id = ? AND status = 'Menunggu'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
header("Location: dashboard_siswa.php");
exit();
?>
