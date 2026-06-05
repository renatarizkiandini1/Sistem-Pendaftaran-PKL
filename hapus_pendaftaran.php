<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: index.html"); exit(); }
include('db.php');
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM pendaftaran WHERE user_id=? AND status='Menunggu'");
$stmt->bind_param("i", $user_id); $stmt->execute();
$row = $stmt->get_result()->fetch_assoc(); $stmt->close();
if ($row) {
    if ($row['surat_permohonan'] && file_exists($row['surat_permohonan'])) unlink($row['surat_permohonan']);
    $stmt = $conn->prepare("DELETE FROM pendaftaran WHERE user_id=? AND status='Menunggu'");
    $stmt->bind_param("i", $user_id); $stmt->execute(); $stmt->close();
}
header("Location: dashboard_siswa.php"); exit();
?>
