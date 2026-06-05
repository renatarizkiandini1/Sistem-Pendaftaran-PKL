<?php
session_start();
include('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        header("Location: index.html?error=user_not_found");
        exit();
    }

    if (!password_verify($password, $row['password'])) {
        header("Location: index.html?error=wrong_password");
        exit();
    }

    $_SESSION['user_id']  = $row['id'];
    $_SESSION['username'] = $row['username'];
    $_SESSION['role']     = $row['role'];

    if ($row['role'] === 'admin')        header("Location: dashboard_admin.php");
    elseif ($row['role'] === 'pembimbing') header("Location: dashboard_pembimbing.php");
    else                                  header("Location: dashboard_siswa.php");
    exit();
}
?>
