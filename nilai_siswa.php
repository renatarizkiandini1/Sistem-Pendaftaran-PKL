<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'siswa') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT n.*, pr.nama_perusahaan, pb.nama_lengkap as nama_pembimbing FROM pendaftaran p JOIN perusahaan pr ON p.perusahaan_id=pr.id LEFT JOIN pembimbing pb ON p.pembimbing_id=pb.id LEFT JOIN penilaian n ON n.pendaftaran_id=p.id WHERE p.user_id=?");
$stmt->bind_param("i", $user_id); $stmt->execute();
$data = $stmt->get_result()->fetch_assoc(); $stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nilai PKL</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .nilai-akhir { text-align:center; padding:20px; background:var(--light-blue); border-radius:12px; margin-top:16px; }
        .nilai-akhir .angka { font-size:48px; font-weight:700; color:var(--blue); }
        .nilai-akhir .label { font-size:14px; color:var(--dark-grey); }
        .catatan-box { background:var(--grey); border-radius:10px; padding:14px; margin-top:16px; font-size:14px; color:var(--dark); }
    </style>
</head>
<body>
<?php sidebarSiswa('nilai_siswa'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span class="nav-title">Nilai PKL</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Nilai PKL</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_siswa.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Nilai</a></li>
                </ul>
            </div>
        </div>
        <div class="card" style="max-width:600px">
            <?php if ($data && $data['nilai_akhir']): ?>
            <p style="font-size:13px;color:var(--dark-grey);margin-bottom:16px">Pembimbing: <b><?= htmlspecialchars($data['nama_pembimbing'] ?? '-') ?></b> | Status: <b><?= $data['status'] ?></b></p>
            <div class="nilai-grid">
                <div class="nilai-item"><div class="angka"><?= $data['nilai_kedisiplinan'] ?></div><div class="label">Kedisiplinan</div></div>
                <div class="nilai-item"><div class="angka"><?= $data['nilai_keterampilan'] ?></div><div class="label">Keterampilan</div></div>
                <div class="nilai-item"><div class="angka"><?= $data['nilai_sikap'] ?></div><div class="label">Sikap</div></div>
                <div class="nilai-item"><div class="angka"><?= $data['nilai_laporan'] ?></div><div class="label">Laporan</div></div>
            </div>
            <div class="nilai-akhir"><div class="angka"><?= $data['nilai_akhir'] ?></div><div class="label">Nilai Akhir</div></div>
            <?php if ($data['catatan']): ?>
            <div class="catatan-box"><b>Catatan Pembimbing:</b><br><?= nl2br(htmlspecialchars($data['catatan'])) ?></div>
            <?php endif; ?>
            <?php else: ?>
            <div class="empty-state-sm"><i class='bx bxs-star'></i><p>Belum ada penilaian dari pembimbing.</p></div>
            <?php endif; ?>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
