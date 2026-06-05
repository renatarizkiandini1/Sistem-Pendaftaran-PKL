<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'siswa') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id = $_SESSION['user_id'];

$siswa = $conn->query("SELECT * FROM siswa WHERE user_id = $user_id")->fetch_assoc();

$stmt = $conn->prepare("SELECT p.*, pr.nama_perusahaan, pb.nama_lengkap as nama_pembimbing 
    FROM pendaftaran p 
    JOIN perusahaan pr ON p.perusahaan_id = pr.id 
    LEFT JOIN pembimbing pb ON p.pembimbing_id = pb.id
    WHERE p.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pkl = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalLogbook = 0;
$nilai = null;
if ($pkl) {
    $totalLogbook = $conn->query("SELECT COUNT(*) as t FROM logbook WHERE pendaftaran_id = {$pkl['id']}")->fetch_assoc()['t'];
    $nilai = $conn->query("SELECT * FROM penilaian WHERE pendaftaran_id = {$pkl['id']}")->fetch_assoc();
}

$pengumuman = $conn->query("SELECT * FROM pengumuman ORDER BY created_at DESC LIMIT 5");
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
        .card { background: var(--light); border-radius: 16px; padding: 24px; margin-top: 20px; }
        .card h3 { font-size: 16px; font-weight: 700; margin-bottom: 16px; color: var(--dark); display: flex; align-items: center; gap: 8px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
        .info-item label { font-size: 11px; color: var(--dark-grey); display: block; text-transform: uppercase; }
        .info-item span { font-size: 14px; font-weight: 600; color: var(--dark); }
        .status-badge { padding: 4px 14px; border-radius: 20px; font-weight: 700; font-size: 12px; color: white; display: inline-block; }
        .status-badge.menunggu { background: var(--orange); }
        .status-badge.diterima { background: var(--blue); }
        .status-badge.ditolak  { background: var(--red); }
        .status-badge.selesai  { background: #27ae60; }
        .btn { padding: 8px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background: var(--blue); color: white; }
        .btn-danger  { background: var(--red); color: white; margin-left: 6px; }
        .alert { padding: 12px 16px; border-radius: 10px; margin-top: 16px; font-size: 13px; }
        .alert-warning { background: var(--light-yellow); color: #856404; }
        .progress-bar { background: var(--grey); border-radius: 20px; height: 10px; margin-top: 6px; }
        .progress-fill { background: var(--blue); border-radius: 20px; height: 100%; transition: width .3s; }
        .pengumuman-item { padding: 12px 0; border-bottom: 1px solid var(--grey); }
        .pengumuman-item:last-child { border-bottom: none; }
        .pengumuman-item h4 { font-size: 14px; font-weight: 600; color: var(--dark); }
        .pengumuman-item small { color: var(--dark-grey); font-size: 12px; }
        .nilai-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
        .nilai-item { background: var(--grey); border-radius: 10px; padding: 14px; text-align: center; }
        .nilai-item .angka { font-size: 28px; font-weight: 700; color: var(--blue); }
        .nilai-item .label { font-size: 11px; color: var(--dark-grey); margin-top: 4px; }
    </style>
</head>
<body>
<?php sidebarSiswa('dashboard_siswa'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Halo, <?= htmlspecialchars($siswa['nama_lengkap'] ?? $_SESSION['username']) ?>!</span>
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
        <div class="alert alert-warning"><i class='bx bxs-error'></i> Profil belum lengkap. <a href="profil_siswa.php"><b>Lengkapi sekarang</b></a></div>
        <?php endif; ?>

        <!-- STATUS PKL -->
        <div class="card">
            <h3><i class='bx bxs-file-doc'></i> Status PKL</h3>
            <?php if ($pkl): ?>
            <div class="info-grid">
                <div class="info-item"><label>Perusahaan</label><span><?= htmlspecialchars($pkl['nama_perusahaan']) ?></span></div>
                <div class="info-item"><label>Pembimbing</label><span><?= htmlspecialchars($pkl['nama_pembimbing'] ?? 'Belum ditentukan') ?></span></div>
                <div class="info-item"><label>Periode</label><span><?= $pkl['tanggal_mulai'] ?> s/d <?= $pkl['tanggal_selesai'] ?></span></div>
                <div class="info-item"><label>Status</label><span class="status-badge <?= strtolower($pkl['status']) ?>"><?= $pkl['status'] ?></span></div>
            </div>
            <?php if ($pkl['catatan']): ?>
            <div class="alert alert-warning" style="margin-top:12px"><b>Catatan Admin:</b> <?= htmlspecialchars($pkl['catatan']) ?></div>
            <?php endif; ?>
            <?php if ($pkl['status'] === 'Menunggu'): ?>
            <div style="margin-top:12px">
                <a href="daftar_pkl.php?edit=1" class="btn btn-primary">Edit Pendaftaran</a>
                <a href="hapus_pendaftaran.php" class="btn btn-danger" onclick="return confirm('Yakin hapus pendaftaran?')">Hapus</a>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <p style="color:var(--dark-grey);font-size:14px">Kamu belum mendaftar PKL.</p>
            <a href="daftar_pkl.php" class="btn btn-primary" style="margin-top:12px">Daftar PKL Sekarang</a>
            <?php endif; ?>
        </div>

        <!-- PROGRESS LOGBOOK -->
        <?php if ($pkl && $pkl['status'] === 'Diterima'): ?>
        <div class="card">
            <h3><i class='bx bxs-book-alt'></i> Progress Logbook</h3>
            <?php
            $mulai   = new DateTime($pkl['tanggal_mulai']);
            $selesai = new DateTime($pkl['tanggal_selesai']);
            $totalHari = max(1, $mulai->diff($selesai)->days);
            $persen  = min(100, round(($totalLogbook / $totalHari) * 100));
            ?>
            <p style="font-size:13px;color:var(--dark-grey);margin-bottom:8px"><?= $totalLogbook ?> entri logbook dari estimasi <?= $totalHari ?> hari kerja</p>
            <div class="progress-bar"><div class="progress-fill" style="width:<?= $persen ?>%"></div></div>
            <p style="font-size:12px;color:var(--dark-grey);margin-top:6px"><?= $persen ?>% selesai</p>
            <a href="logbook.php" class="btn btn-primary" style="margin-top:12px">Isi Logbook</a>
        </div>

        <!-- NILAI -->
        <div class="card">
            <h3><i class='bx bxs-star'></i> Nilai Sementara</h3>
            <?php if ($nilai): ?>
            <div class="nilai-grid">
                <div class="nilai-item"><div class="angka"><?= $nilai['nilai_kedisiplinan'] ?></div><div class="label">Kedisiplinan</div></div>
                <div class="nilai-item"><div class="angka"><?= $nilai['nilai_keterampilan'] ?></div><div class="label">Keterampilan</div></div>
                <div class="nilai-item"><div class="angka"><?= $nilai['nilai_sikap'] ?></div><div class="label">Sikap</div></div>
                <div class="nilai-item"><div class="angka"><?= $nilai['nilai_laporan'] ?></div><div class="label">Laporan</div></div>
                <div class="nilai-item"><div class="angka" style="color:<?= $nilai['nilai_akhir'] >= 75 ? 'var(--blue)' : 'var(--red)' ?>"><?= $nilai['nilai_akhir'] ?></div><div class="label">Nilai Akhir</div></div>
            </div>
            <?php else: ?>
            <p style="color:var(--dark-grey);font-size:14px">Belum ada penilaian dari pembimbing.</p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- PENGUMUMAN -->
        <div class="card">
            <h3><i class='bx bxs-bell'></i> Pengumuman Terbaru</h3>
            <?php if ($pengumuman && $pengumuman->num_rows > 0): ?>
                <?php while ($row = $pengumuman->fetch_assoc()): ?>
                <div class="pengumuman-item">
                    <h4><?= htmlspecialchars($row['judul']) ?></h4>
                    <p style="font-size:13px;color:var(--dark);margin:4px 0"><?= nl2br(htmlspecialchars(substr($row['isi'], 0, 100))) ?>...</p>
                    <small><?= date('d M Y', strtotime($row['created_at'])) ?></small>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
            <p style="color:var(--dark-grey);font-size:14px">Belum ada pengumuman.</p>
            <?php endif; ?>
        </div>

    </main>
</section>
<script src="script.js"></script>
</body>
</html>
