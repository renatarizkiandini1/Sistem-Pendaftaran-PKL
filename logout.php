<?php
session_start(); // Memulai sesi
session_unset();
session_destroy(); 

// Mengarahkan pengguna kembali ke halaman login
header("Location: index.html");
exit();
?>
