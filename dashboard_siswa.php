<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: index.html"); exit(); }
include('db.php');

$user_id = $_SESSION['user_id'];

$siswa = $conn->query("SELECT * FROM siswa WHERE user_id = $user_id")->fetch_assoc();

$stmt = $conn->prepare("SELECT p.*, pr.nama_perusahaan FROM pendaftaran p JOIN perusahaan pr ON p.perusahaan_id = pr.id WHERE p.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pendaftaran = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .card { background: var(--light); border-radius: 20px; padding: 24px; margin-top: 24px; }
        .card h3 { font-size: 18px; font-weight: 600; margin-bottom: 16px; color: var(--dark); }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
        .info-item label { font-size: 12px; color: var(--dark-grey); display: block; }
        .info-item span { font-size: 14px; font-weight: 600; color: var(--dark); }
        .status-badge { padding: 6px 16px; border-radius: 20px; font-weight: 700; font-size: 13px; color: white; display: inline-block; }
        .status-badge.menunggu { background: var(--orange); }
        .status-badge.diterima { background: var(--blue); }
        .status-badge.ditolak  { background: var(--red); }
        .btn { padding: 8px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; margin-top: 12px; }
        .btn-primary { background: var(--blue); color: white; }
        .btn-danger  { background: var(--red); color: white; margin-left: 8px; }
        .alert { padding: 12px 20px; border-radius: 10px; margin-bottom: 16px; font-size: 14px; }
        .alert-warning { background: var(--light-yellow); color: #856404; }
        .alert-success { background: var(--light-blue); color: #0c5460; }
    </style>
</head>
<body>
<section id="sidebar">
    <a href="#" class="brand"><i class='bx bxs-smile'></i><span class="text"><?= htmlspecialchars($_SESSION['username']) ?></span></a>
    <ul class="side-menu top">
        <li class="active"><a href="dashboard_siswa.php"><i class='bx bxs-dashboard'></i><span class="text">Dashboard</span></a></li>
        <li><a href="profil_siswa.php"><i class='bx bxs-user'></i><span class="text">Profil Saya</span></a></li>
        <li><a href="daftar_pkl.php"><i class='bx bxs-file-plus'></i><span class="text">Daftar PKL</span></a></li>
    </ul>
    <ul class="side-menu">
        <li><a href="logout.php" class="logout"><i class='bx bxs-log-out-circle'></i><span class="text">Logout</span></a></li>
    </ul>
</section>

<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Dashboard</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Home</a></li>
                </ul>
            </div>
        </div>

        <?php if (!$siswa): ?>
        <div class="alert alert-warning" style="margin-top:24px;">
            <i class='bx bxs-error'></i> Profil kamu belum lengkap. <a href="profil_siswa.php"><b>Lengkapi sekarang</b></a> sebelum mendaftar PKL.
        </div>
        <?php endif; ?>

        <!-- INFO PROFIL -->
        <div class="card">
            <h3><i class='bx bxs-user'></i> Profil Saya</h3>
            <?php if ($siswa): ?>
            <div class="info-grid">
                <div class="info-item"><label>Nama Lengkap</label><span><?= htmlspecialchars($siswa['nama_lengkap']) ?></span></div>
                <div class="info-item"><label>NISN</label><span><?= htmlspecialchars($siswa['nisn']) ?></span></div>
                <div class="info-item"><label>Kelas</label><span><?= htmlspecialchars($siswa['kelas']) ?></span></div>
                <div class="info-item"><label>Jurusan</label><span><?= htmlspecialchars($siswa['jurusan']) ?></span></div>
                <div class="info-item"><label>No. Telp</label><span><?= htmlspecialchars($siswa['no_telp']) ?></span></div>
            </div>
            <a href="profil_siswa.php" class="btn btn-primary" style="margin-top:16px;">Edit Profil</a>
            <?php else: ?>
            <p style="color:var(--dark-grey)">Belum ada data profil.</p>
            <a href="profil_siswa.php" class="btn btn-primary">Lengkapi Profil</a>
            <?php endif; ?>
        </div>

        <!-- STATUS PENDAFTARAN -->
        <div class="card">
            <h3><i class='bx bxs-file-doc'></i> Status Pendaftaran PKL</h3>
            <?php if ($pendaftaran): ?>
            <div class="info-grid">
                <div class="info-item"><label>Perusahaan</label><span><?= htmlspecialchars($pendaftaran['nama_perusahaan']) ?></span></div>
                <div class="info-item"><label>Tanggal Mulai</label><span><?= htmlspecialchars($pendaftaran['tanggal_mulai']) ?></span></div>
                <div class="info-item"><label>Tanggal Selesai</label><span><?= htmlspecialchars($pendaftaran['tanggal_selesai']) ?></span></div>
                <div class="info-item"><label>Status</label><span class="status-badge <?= strtolower($pendaftaran['status']) ?>"><?= $pendaftaran['status'] ?></span></div>
                <?php if ($pendaftaran['catatan']): ?>
                <div class="info-item"><label>Catatan Admin</label><span><?= htmlspecialchars($pendaftaran['catatan']) ?></span></div>
                <?php endif; ?>
            </div>
            <?php if ($pendaftaran['status'] === 'Menunggu'): ?>
            <a href="daftar_pkl.php?edit=1" class="btn btn-primary">Edit Pendaftaran</a>
            <a href="hapus_pendaftaran.php" class="btn btn-danger" onclick="return confirm('Yakin ingin hapus pendaftaran?')">Hapus</a>
            <?php endif; ?>
            <?php else: ?>
            <p style="color:var(--dark-grey)">Kamu belum mendaftar PKL.</p>
            <a href="daftar_pkl.php" class="btn btn-primary">Daftar PKL Sekarang</a>
            <?php endif; ?>
        </div>

    </main>
</section>
<script src="script.js"></script>
</body>
</html>
