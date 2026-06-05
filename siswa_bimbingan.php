<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'pembimbing') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');
$user_id    = $_SESSION['user_id'];
$pembimbing = $conn->query("SELECT * FROM pembimbing WHERE user_id=$user_id")->fetch_assoc();
$pb_id      = $pembimbing['id'] ?? 0;
$list = $conn->query("SELECT p.*, u.username, s.nama_lengkap, s.kelas, s.jurusan, s.nisn, pr.nama_perusahaan,
    (SELECT COUNT(*) FROM logbook WHERE pendaftaran_id=p.id) as total_logbook,
    (SELECT COUNT(*) FROM logbook WHERE pendaftaran_id=p.id AND status_verifikasi='Menunggu') as pending_logbook,
    n.nilai_akhir, n.status as status_nilai
    FROM pendaftaran p JOIN user u ON p.user_id=u.id LEFT JOIN siswa s ON s.user_id=p.user_id
    JOIN perusahaan pr ON p.perusahaan_id=pr.id LEFT JOIN penilaian n ON n.pendaftaran_id=p.id
    WHERE p.pembimbing_id=$pb_id AND p.status='Diterima' ORDER BY s.nama_lengkap");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siswa Bimbingan</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .table-card { background:var(--light); border-radius:16px; padding:24px; margin-top:24px; overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        th { padding-bottom:10px; font-size:12px; text-align:left; border-bottom:1px solid var(--grey); color:var(--dark-grey); }
        td { padding:10px 0; font-size:13px; border-bottom:1px solid var(--grey); }
        .btn { padding:4px 12px; border-radius:6px; border:none; cursor:pointer; font-size:12px; font-weight:600; text-decoration:none; display:inline-block; }
        .btn-blue { background:var(--light-blue); color:var(--blue); }
        .badge-pending { background:var(--orange); color:white; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; }
    </style>
</head>
<body>
<?php sidebarPembimbing('siswa_bimbingan'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Siswa Bimbingan</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Siswa Bimbingan</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_pembimbing.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Siswa Bimbingan</a></li>
                </ul>
            </div>
        </div>
        <div class="table-card">
            <table>
                <thead><tr><th>No</th><th>Nama Siswa</th><th>NISN</th><th>Kelas</th><th>Perusahaan</th><th>Logbook</th><th>Nilai</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if ($list && $list->num_rows > 0): $no=1; ?>
                        <?php while ($row = $list->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap'] ?? $row['username']) ?></td>
                            <td><?= htmlspecialchars($row['nisn'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['kelas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                            <td>
                                <?= $row['total_logbook'] ?> entri
                                <?php if ($row['pending_logbook'] > 0): ?>
                                <span class="badge-pending"><?= $row['pending_logbook'] ?> pending</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $row['nilai_akhir'] ? $row['nilai_akhir'].' ('.$row['status_nilai'].')' : '-' ?></td>
                            <td>
                                <a href="detail_logbook.php?id=<?= $row['id'] ?>" class="btn btn-blue">Logbook</a>
                                <a href="beri_nilai.php?id=<?= $row['id'] ?>" class="btn btn-blue" style="margin-left:4px">Nilai</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center;padding:20px;color:var(--dark-grey)">Belum ada siswa bimbingan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
