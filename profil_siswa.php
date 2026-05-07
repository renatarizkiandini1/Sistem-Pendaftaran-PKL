<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: index.html"); exit(); }
include('db.php');

$user_id = $_SESSION['user_id'];
$pesan = '';

// Simpan / Update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'];
    $nisn         = $_POST['nisn'];
    $kelas        = $_POST['kelas'];
    $jurusan      = $_POST['jurusan'];
    $no_telp      = $_POST['no_telp'];
    $alamat       = $_POST['alamat'];

    $cek = $conn->query("SELECT id FROM siswa WHERE user_id = $user_id")->num_rows;

    if ($cek > 0) {
        $stmt = $conn->prepare("UPDATE siswa SET nama_lengkap=?, nisn=?, kelas=?, jurusan=?, no_telp=?, alamat=? WHERE user_id=?");
        $stmt->bind_param("ssssssi", $nama_lengkap, $nisn, $kelas, $jurusan, $no_telp, $alamat, $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO siswa (user_id, nama_lengkap, nisn, kelas, jurusan, no_telp, alamat) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss", $user_id, $nama_lengkap, $nisn, $kelas, $jurusan, $no_telp, $alamat);
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
        .form-card { background: var(--light); border-radius: 20px; padding: 32px; margin-top: 24px; max-width: 600px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--dark); }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; outline: none;
        }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--blue); }
        .btn-submit { background: var(--blue); color: white; border: none; padding: 10px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .alert-success { background: var(--light-blue); color: #0c5460; padding: 12px 20px; border-radius: 10px; margin-bottom: 16px; }
    </style>
</head>
<body>
<section id="sidebar">
    <a href="#" class="brand"><i class='bx bxs-smile'></i><span class="text"><?= htmlspecialchars($_SESSION['username']) ?></span></a>
    <ul class="side-menu top">
        <li><a href="dashboard_siswa.php"><i class='bx bxs-dashboard'></i><span class="text">Dashboard</span></a></li>
        <li class="active"><a href="profil_siswa.php"><i class='bx bxs-user'></i><span class="text">Profil Saya</span></a></li>
        <li><a href="daftar_pkl.php"><i class='bx bxs-file-plus'></i><span class="text">Daftar PKL</span></a></li>
    </ul>
    <ul class="side-menu">
        <li><a href="logout.php" class="logout"><i class='bx bxs-log-out-circle'></i><span class="text">Logout</span></a></li>
    </ul>
</section>

<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Profil Saya</span>
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

        <div class="form-card">
            <?php if ($pesan === 'success'): ?>
            <div class="alert-success"><i class='bx bxs-check-circle'></i> Profil berhasil disimpan!</div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($siswa['nama_lengkap'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>NISN</label>
                    <input type="text" name="nisn" value="<?= htmlspecialchars($siswa['nisn'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Kelas</label>
                    <input type="text" name="kelas" value="<?= htmlspecialchars($siswa['kelas'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Jurusan</label>
                    <input type="text" name="jurusan" value="<?= htmlspecialchars($siswa['jurusan'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="text" name="no_telp" value="<?= htmlspecialchars($siswa['no_telp'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="alamat" rows="3" required><?= htmlspecialchars($siswa['alamat'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn-submit">Simpan Profil</button>
            </form>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
