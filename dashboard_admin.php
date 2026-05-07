<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');

$totalPendaftaran = $conn->query("SELECT COUNT(*) as t FROM pendaftaran")->fetch_assoc()['t'];
$totalMenunggu    = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE status='Menunggu'")->fetch_assoc()['t'];
$totalDiterima    = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE status='Diterima'")->fetch_assoc()['t'];
$totalDitolak     = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE status='Ditolak'")->fetch_assoc()['t'];
$totalSiswa       = $conn->query("SELECT COUNT(*) as t FROM user WHERE role='siswa'")->fetch_assoc()['t'];
$totalPerusahaan  = $conn->query("SELECT COUNT(*) as t FROM perusahaan")->fetch_assoc()['t'];

$recentPendaftaran = $conn->query("SELECT p.*, u.username, pr.nama_perusahaan FROM pendaftaran p JOIN user u ON p.user_id = u.id JOIN perusahaan pr ON p.perusahaan_id = pr.id ORDER BY p.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .status-badge { padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 11px; color: white; }
        .status-badge.menunggu { background: var(--orange); }
        .status-badge.diterima { background: var(--blue); }
        .status-badge.ditolak  { background: var(--red); }
        .nav-admin { display: flex; gap: 8px; margin-top: 24px; flex-wrap: wrap; }
        .nav-card { background: var(--light); border-radius: 12px; padding: 16px 24px; display: flex; align-items: center; gap: 12px; text-decoration: none; color: var(--dark); font-weight: 600; font-size: 14px; transition: .2s; }
        .nav-card:hover { background: var(--light-blue); color: var(--blue); }
        .nav-card i { font-size: 22px; color: var(--blue); }
    </style>
</head>
<body>
<section id="sidebar">
    <a href="#" class="brand"><i class='bx bxs-shield-alt-2'></i><span class="text">Admin</span></a>
    <ul class="side-menu top">
        <li class="active"><a href="dashboard_admin.php"><i class='bx bxs-dashboard'></i><span class="text">Dashboard</span></a></li>
        <li><a href="admin_pendaftaran.php"><i class='bx bxs-file-doc'></i><span class="text">Pendaftaran</span></a></li>
        <li><a href="admin_perusahaan.php"><i class='bx bxs-buildings'></i><span class="text">Perusahaan</span></a></li>
        <li><a href="admin_siswa.php"><i class='bx bxs-group'></i><span class="text">Siswa</span></a></li>
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
                <h1>Dashboard Admin</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Home</a></li>
                </ul>
            </div>
        </div>

        <ul class="box-info">
            <li><i class='bx bxs-file-doc'></i><span class="text"><h3><?= $totalPendaftaran ?></h3><p>Total Pendaftaran</p></span></li>
            <li><i class='bx bxs-time'></i><span class="text"><h3><?= $totalMenunggu ?></h3><p>Menunggu</p></span></li>
            <li><i class='bx bxs-check-circle'></i><span class="text"><h3><?= $totalDiterima ?></h3><p>Diterima</p></span></li>
            <li><i class='bx bxs-x-circle'></i><span class="text"><h3><?= $totalDitolak ?></h3><p>Ditolak</p></span></li>
            <li><i class='bx bxs-group'></i><span class="text"><h3><?= $totalSiswa ?></h3><p>Total Siswa</p></span></li>
            <li><i class='bx bxs-buildings'></i><span class="text"><h3><?= $totalPerusahaan ?></h3><p>Perusahaan</p></span></li>
        </ul>

        <div class="nav-admin">
            <a href="admin_pendaftaran.php" class="nav-card"><i class='bx bxs-file-doc'></i> Kelola Pendaftaran</a>
            <a href="admin_perusahaan.php" class="nav-card"><i class='bx bxs-buildings'></i> Kelola Perusahaan</a>
            <a href="admin_siswa.php" class="nav-card"><i class='bx bxs-group'></i> Kelola Siswa</a>
        </div>

        <div class="table-data" style="margin-top:24px;">
            <div class="order" style="width:100%">
                <div class="head"><h3>Pendaftaran Terbaru</h3><a href="admin_pendaftaran.php" style="font-size:13px;color:var(--blue)">Lihat semua</a></div>
                <table>
                    <thead>
                        <tr>
                            <th>Siswa</th>
                            <th>Perusahaan</th>
                            <th>Tanggal Mulai</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recentPendaftaran && $recentPendaftaran->num_rows > 0): ?>
                            <?php while ($row = $recentPendaftaran->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['username']) ?></td>
                                <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                                <td><?= htmlspecialchars($row['tanggal_mulai']) ?></td>
                                <td><span class="status-badge <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;padding:20px">Belum ada pendaftaran.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
