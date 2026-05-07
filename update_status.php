<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.html");
    exit();
}
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = $_POST['id'];
    $status = $_POST['status'];

    $allowed = ['Menunggu', 'Diterima', 'Ditolak'];
    if (!in_array($status, $allowed)) {
        header("Location: dashboard_admin.php");
        exit();
    }

    $stmt = $conn->prepare("UPDATE pendaftaran_pkl SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
header("Location: dashboard_admin.php");
exit();
?>
