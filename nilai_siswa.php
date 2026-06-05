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
        .card { background:var(--light); border-radius:16px; padding:32px; margin-top:24px; max-width:560px; }
        .nilai-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:16px; margin-bottom:16px; }
        .nilai-item { background:var(--grey); border-radius:12px; padding:16px; text-align:center; }
        .nilai-item .angka { font-size:32px; font-weight:700; color:var(--blue); }
        .nilai-item .label { font-size:12px; color:var(--dark-grey); margin-top:4px; }
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
        <span style="font-weight:600">Nilai PKL</span>
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
        <div class="card">
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
            <p style="color:var(--dark-grey);font-size:14px">Belum ada penilaian dari pembimbing.</p>
            <?php endif; ?>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
