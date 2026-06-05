<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$totalPendaftar  = $conn->query("SELECT COUNT(*) as t FROM pendaftaran")->fetch_assoc()['t'];
$totalAktif      = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE status='Diterima'")->fetch_assoc()['t'];
$totalMenunggu   = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE status='Menunggu'")->fetch_assoc()['t'];
$totalSelesai    = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE status='Selesai'")->fetch_assoc()['t'];
$totalPembimbing = $conn->query("SELECT COUNT(*) as t FROM pembimbing")->fetch_assoc()['t'];
$totalSiswa      = $conn->query("SELECT COUNT(*) as t FROM user WHERE role='siswa'")->fetch_assoc()['t'];

$recent = $conn->query("SELECT p.*, u.username, s.nama_lengkap, pr.nama_perusahaan FROM pendaftaran p JOIN user u ON p.user_id = u.id LEFT JOIN siswa s ON s.user_id = p.user_id JOIN perusahaan pr ON p.perusahaan_id = pr.id ORDER BY p.created_at DESC LIMIT 5");
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
        .card { background: var(--light); border-radius: 16px; padding: 24px; margin-top: 20px; }
        .card h3 { font-size: 15px; font-weight: 700; margin-bottom: 16px; color: var(--dark); display: flex; justify-content: space-between; align-items: center; }
        .card h3 a { font-size: 12px; font-weight: 600; color: var(--blue); }
        table { width: 100%; border-collapse: collapse; }
        th { padding-bottom: 10px; font-size: 12px; text-align: left; border-bottom: 1px solid var(--grey); color: var(--dark-grey); }
        td { padding: 10px 0; font-size: 13px; border-bottom: 1px solid var(--grey); }
        .status-badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; color: white; }
        .status-badge.menunggu { background: var(--orange); }
        .status-badge.diterima { background: var(--blue); }
        .status-badge.ditolak  { background: var(--red); }
        .status-badge.selesai  { background: #27ae60; }
    </style>
</head>
<body>
<?php sidebarAdmin('dashboard_admin'); ?>
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
            <li><i class='bx bxs-file-doc'></i><span class="text"><h3><?= $totalPendaftar ?></h3><p>Total Pendaftar</p></span></li>
            <li><i class='bx bxs-check-circle'></i><span class="text"><h3><?= $totalAktif ?></h3><p>Peserta Aktif</p></span></li>
            <li><i class='bx bxs-time'></i><span class="text"><h3><?= $totalMenunggu ?></h3><p>Menunggu Review</p></span></li>
            <li><i class='bx bxs-flag-checkered'></i><span class="text"><h3><?= $totalSelesai ?></h3><p>PKL Selesai</p></span></li>
            <li><i class='bx bxs-user-badge'></i><span class="text"><h3><?= $totalPembimbing ?></h3><p>Total Pembimbing</p></span></li>
            <li><i class='bx bxs-group'></i><span class="text"><h3><?= $totalSiswa ?></h3><p>Total Siswa</p></span></li>
        </ul>

        <div class="card">
            <h3>Pendaftaran Terbaru <a href="admin_pendaftaran.php">Lihat semua →</a></h3>
            <table>
                <thead><tr><th>Siswa</th><th>Perusahaan</th><th>Tanggal Daftar</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if ($recent && $recent->num_rows > 0): ?>
                        <?php while ($row = $recent->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama_lengkap'] ?? $row['username']) ?></td>
                            <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td><span class="status-badge <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--dark-grey)">Belum ada pendaftaran.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
