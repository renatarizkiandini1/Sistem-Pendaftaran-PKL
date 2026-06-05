<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'pembimbing') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id    = $_SESSION['user_id'];
$pembimbing = $conn->query("SELECT * FROM pembimbing WHERE user_id = $user_id")->fetch_assoc();
$pb_id      = $pembimbing['id'] ?? 0;

$totalSiswa    = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE pembimbing_id = $pb_id AND status = 'Diterima'")->fetch_assoc()['t'];
$logbookPending = $conn->query("SELECT COUNT(*) as t FROM logbook l JOIN pendaftaran p ON l.pendaftaran_id = p.id WHERE p.pembimbing_id = $pb_id AND l.status_verifikasi = 'Menunggu'")->fetch_assoc()['t'];
$nilaiPending  = $conn->query("SELECT COUNT(*) as t FROM pendaftaran p LEFT JOIN penilaian n ON p.id = n.pendaftaran_id WHERE p.pembimbing_id = $pb_id AND p.status = 'Diterima' AND (n.id IS NULL OR n.status = 'Draft')")->fetch_assoc()['t'];

$siswaBimbingan = $conn->query("SELECT p.*, u.username, s.nama_lengkap, s.kelas, s.jurusan, pr.nama_perusahaan 
    FROM pendaftaran p 
    JOIN user u ON p.user_id = u.id 
    LEFT JOIN siswa s ON s.user_id = p.user_id 
    JOIN perusahaan pr ON p.perusahaan_id = pr.id 
    WHERE p.pembimbing_id = $pb_id AND p.status = 'Diterima'
    LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembimbing</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php sidebarPembimbing('dashboard_pembimbing'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span class="nav-title">Halo, <?= htmlspecialchars($pembimbing['nama_lengkap'] ?? $_SESSION['username']) ?>!</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Dashboard Pembimbing</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Home</a></li>
                </ul>
            </div>
        </div>

        <ul class="box-info">
            <li><i class='bx bxs-group'></i><span class="text"><h3><?= $totalSiswa ?></h3><p>Siswa Bimbingan</p></span></li>
            <li><i class='bx bxs-book-alt'></i><span class="text"><h3><?= $logbookPending ?></h3><p>Logbook Menunggu Verifikasi</p></span></li>
            <li><i class='bx bxs-star'></i><span class="text"><h3><?= $nilaiPending ?></h3><p>Penilaian Belum Selesai</p></span></li>
        </ul>

        <div class="card">
            <div class="card-header">
                <h3>Siswa Bimbingan</h3>
                <a href="siswa_bimbingan.php">Lihat semua →</a>
            </div>
            <div class="table-wrap">
                <thead><tr><th>Nama Siswa</th><th>Kelas</th><th>Perusahaan</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if ($siswaBimbingan && $siswaBimbingan->num_rows > 0): ?>
                        <?php while ($row = $siswaBimbingan->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama_lengkap'] ?? $row['username']) ?></td>
                            <td><?= htmlspecialchars($row['kelas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                            <td>
                                <a href="detail_logbook.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-ghost">Logbook</a>
                                <a href="beri_nilai.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary" style="margin-left:4px">Nilai</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--dark-grey)">Belum ada siswa bimbingan.</td></tr>
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
