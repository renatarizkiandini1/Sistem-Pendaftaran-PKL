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
</head>
<body>
<?php sidebarSiswa('dashboard_siswa'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span class="nav-title">Halo, <?= htmlspecialchars($siswa['nama_lengkap'] ?? $_SESSION['username']) ?>!</span>
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
            <div class="card-header"><h3><i class='bx bxs-file-doc'></i> Status PKL</h3></div>
            <?php if ($pkl): ?>
            <div class="info-grid">
                <div class="info-item"><label>Perusahaan</label><span><?= htmlspecialchars($pkl['nama_perusahaan']) ?></span></div>
                <div class="info-item"><label>Pembimbing</label><span><?= htmlspecialchars($pkl['nama_pembimbing'] ?? 'Belum ditentukan') ?></span></div>
                <div class="info-item"><label>Periode</label><span><?= $pkl['tanggal_mulai'] ?> s/d <?= $pkl['tanggal_selesai'] ?></span></div>
                <div class="info-item"><label>Status</label><span class="status-badge <?= strtolower($pkl['status']) ?>"><?= $pkl['status'] ?></span></div>
            </div>
            <?php if ($pkl['catatan']): ?>
            <div class="alert alert-warning" style="margin-top:16px"><i class='bx bxs-info-circle'></i><span><b>Catatan Admin:</b> <?= htmlspecialchars($pkl['catatan']) ?></span></div>
            <?php endif; ?>
            <?php if ($pkl['status'] === 'Menunggu'): ?>
            <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
                <a href="daftar_pkl.php?edit=1" class="btn btn-primary"><i class='bx bxs-edit'></i> Edit</a>
                <a href="hapus_pendaftaran.php" class="btn btn-danger" onclick="return confirm('Yakin hapus pendaftaran?')"><i class='bx bxs-trash'></i> Hapus</a>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <p style="color:var(--dark-grey);font-size:14px">Kamu belum mendaftar PKL.</p>
            <a href="daftar_pkl.php" class="btn btn-primary" style="margin-top:16px"><i class='bx bxs-file-plus'></i> Daftar PKL Sekarang</a>
            <?php endif; ?>
        </div>

        <!-- PROGRESS LOGBOOK -->
        <?php if ($pkl && $pkl['status'] === 'Diterima'): ?>
        <div class="card">
            <div class="card-header"><h3><i class='bx bxs-book-alt'></i> Progress Logbook</h3><a href="logbook.php">Lihat semua →</a></div>
            <?php
            $mulai   = new DateTime($pkl['tanggal_mulai']);
            $selesai = new DateTime($pkl['tanggal_selesai']);
            $totalHari = max(1, $mulai->diff($selesai)->days);
            $persen  = min(100, round(($totalLogbook / $totalHari) * 100));
            ?>
            <p style="font-size:13px;color:var(--dark-grey);margin-bottom:8px"><?= $totalLogbook ?> entri logbook dari estimasi <?= $totalHari ?> hari kerja</p>
            <div class="progress-bar"><div class="progress-fill" style="width:<?= $persen ?>%"></div></div>
            <p style="font-size:12px;color:var(--dark-grey);margin-top:6px"><?= $persen ?>% selesai</p>
            <a href="logbook.php" class="btn btn-primary" style="margin-top:16px"><i class='bx bxs-edit'></i> Isi Logbook</a>
        </div>

        <!-- NILAI -->
        <div class="card">
            <div class="card-header"><h3><i class='bx bxs-star'></i> Nilai Sementara</h3><a href="nilai_siswa.php">Detail →</a></div>
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
            <div class="card-header"><h3><i class='bx bxs-bell'></i> Pengumuman Terbaru</h3><a href="pengumuman_siswa.php">Lihat semua →</a></div>
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
