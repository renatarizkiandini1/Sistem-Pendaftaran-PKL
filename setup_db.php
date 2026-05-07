<?php
$conn = new mysqli("localhost", "root", "");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$sql = file_get_contents(__DIR__ . '/db_pkl.sql');
$conn->multi_query($sql);
while ($conn->next_result()) {}

$conn2 = new mysqli("localhost", "root", "", "db_pkl");

$check = $conn2->query("SELECT COUNT(*) as total FROM user")->fetch_assoc()['total'];
if ($check == 0) {
    $adminPass = password_hash("admin123", PASSWORD_DEFAULT);
    $siswaPass = password_hash("siswa123", PASSWORD_DEFAULT);
    $conn2->query("INSERT INTO user (username, password, role) VALUES ('admin', '$adminPass', 'admin')");
    $conn2->query("INSERT INTO user (username, password, role) VALUES ('siswa', '$siswaPass', 'siswa')");
}

echo "<h2 style='color:green'>✅ Database berhasil dibuat!</h2>";
echo "<p>Tabel: <b>user, siswa, perusahaan, pendaftaran</b></p>";
echo "<p>Akun default: <b>admin/admin123</b> dan <b>siswa/siswa123</b></p>";
echo "<a href='index.html'><button style='margin-top:10px;padding:10px 20px;background:#007bff;color:white;border:none;border-radius:4px;cursor:pointer'>Ke Halaman Login</button></a>";
?>
