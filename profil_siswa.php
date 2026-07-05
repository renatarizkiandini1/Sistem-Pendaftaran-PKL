<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'siswa') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id = $_SESSION['user_id'];
$pesan   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = $_POST['nama_lengkap'];
    $nisn    = $_POST['nisn'];
    $kelas   = $_POST['kelas'];
    $jurusan = $_POST['jurusan'];
    $no_telp = $_POST['no_telp'];
    $alamat  = $_POST['alamat'];

    $cek = $conn->query("SELECT id FROM siswa WHERE user_id = $user_id")->num_rows;
    if ($cek > 0) {
        $stmt = $conn->prepare("UPDATE siswa SET nama_lengkap=?,nisn=?,kelas=?,jurusan=?,no_telp=?,alamat=? WHERE user_id=?");
        $stmt->bind_param("ssssssi", $nama, $nisn, $kelas, $jurusan, $no_telp, $alamat, $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO siswa (user_id,nama_lengkap,nisn,kelas,jurusan,no_telp,alamat) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss", $user_id, $nama, $nisn, $kelas, $jurusan, $no_telp, $alamat);
    }
    $stmt->execute();
    $stmt->close();
    $pesan = 'success';
}

$siswa = $conn->query("SELECT * FROM siswa WHERE user_id = $user_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Siswa</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-card { max-width: 560px; }
    </style>
</head>
<body>
<?php sidebarSiswa('profil_siswa'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span class="nav-title">Profil Saya</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Profil Saya</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_siswa.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Profil</a></li>
                </ul>
            </div>
        </div>
        <div class="form-card card">
            <?php if ($pesan === 'success'): ?>
            <div class="alert alert-success"><i class='bx bxs-check-circle'></i> Profil berhasil disimpan!</div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" value="<?= htmlspecialchars($siswa['nama_lengkap'] ?? '') ?>" required></div>
                <div class="form-group"><label>NISN</label><input type="text" name="nisn" value="<?= htmlspecialchars($siswa['nisn'] ?? '') ?>" required></div>
                <div class="form-group"><label>Kelas</label><input type="text" name="kelas" value="<?= htmlspecialchars($siswa['kelas'] ?? '') ?>" required></div>
                <div class="form-group"><label>Jurusan</label><input type="text" name="jurusan" value="<?= htmlspecialchars($siswa['jurusan'] ?? '') ?>" required></div>
                <div class="form-group"><label>No. Telepon</label><input type="text" name="no_telp" value="<?= htmlspecialchars($siswa['no_telp'] ?? '') ?>"></div>
                <div class="form-group"><label>Alamat</label><textarea name="alamat" rows="3"><?= htmlspecialchars($siswa['alamat'] ?? '') ?></textarea></div>
                <button type="submit" class="btn btn-primary">Simpan Profil</button>
            </form>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
