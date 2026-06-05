<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

// Generate sertifikat (tandai sudah digenerate)
if (isset($_GET['generate'])) {
    $id = $_GET['generate'];
    $conn->query("UPDATE pendaftaran SET sertifikat='generated' WHERE id=$id AND status='Selesai'");
    header("Location: admin_sertifikat.php?preview=$id");
    exit();
}

$list = $conn->query("SELECT p.*, s.nama_lengkap, s.nisn, pr.nama_perusahaan, pb.nama_lengkap as nama_pembimbing,
    n.nilai_akhir
    FROM pendaftaran p
    LEFT JOIN siswa s ON s.user_id = p.user_id
    JOIN perusahaan pr ON p.perusahaan_id = pr.id
    LEFT JOIN pembimbing pb ON p.pembimbing_id = pb.id
    LEFT JOIN penilaian n ON n.pendaftaran_id = p.id
    WHERE p.status = 'Selesai'
    ORDER BY p.created_at DESC");

// Preview sertifikat
$preview = null;
if (isset($_GET['preview'])) {
    $pid  = $_GET['preview'];
    $preview = $conn->query("SELECT p.*, s.nama_lengkap, s.nisn, pr.nama_perusahaan, pb.nama_lengkap as nama_pembimbing, n.nilai_akhir
        FROM pendaftaran p
        LEFT JOIN siswa s ON s.user_id = p.user_id
        JOIN perusahaan pr ON p.perusahaan_id = pr.id
        LEFT JOIN pembimbing pb ON p.pembimbing_id = pb.id
        LEFT JOIN penilaian n ON n.pendaftaran_id = p.id
        WHERE p.id = $pid")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat PKL</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .table-card { background:var(--light); border-radius:16px; padding:24px; margin-top:24px; overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        th { padding-bottom:10px; font-size:12px; text-align:left; border-bottom:1px solid var(--grey); color:var(--dark-grey); }
        td { padding:10px 0; font-size:13px; border-bottom:1px solid var(--grey); }
        .btn-gen { background:var(--blue); color:white; padding:5px 14px; border-radius:6px; border:none; cursor:pointer; font-size:12px; font-weight:600; text-decoration:none; display:inline-block; }
        .btn-preview { background:var(--light-blue); color:var(--blue); padding:5px 14px; border-radius:6px; border:none; cursor:pointer; font-size:12px; font-weight:600; text-decoration:none; display:inline-block; margin-left:4px; }

        /* Sertifikat */
        .sertifikat-wrap { margin-top:24px; }
        .sertifikat-actions { display:flex; gap:8px; margin-bottom:16px; }
        .btn-print { background:#27ae60; color:white; padding:8px 20px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; }
        .btn-back  { background:var(--grey); color:var(--dark); padding:8px 20px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; text-decoration:none; display:inline-block; }

        @media print {
            #sidebar, #content nav, .head-title, .sertifikat-actions { display:none !important; }
            #content { left:0 !important; width:100% !important; }
            #content main { padding:0 !important; }
        }

        .sertif { border:8px double #1a5fa8; padding:60px; max-width:800px; margin:0 auto; text-align:center; font-family:'Times New Roman',serif; background:white; position:relative; }
        .sertif::before { content:''; position:absolute; top:12px; left:12px; right:12px; bottom:12px; border:2px solid #3C91E6; pointer-events:none; }
        .sertif .logo-sertif { font-size:48px; color:#1a5fa8; margin-bottom:8px; }
        .sertif h1 { font-size:36px; color:#1a5fa8; letter-spacing:4px; margin-bottom:4px; }
        .sertif h2 { font-size:18px; color:#555; font-weight:normal; margin-bottom:32px; }
        .sertif p  { font-size:15px; color:#333; line-height:1.8; margin-bottom:8px; }
        .sertif .nama-siswa { font-size:32px; font-weight:bold; color:#1a5fa8; margin:16px 0; border-bottom:2px solid #1a5fa8; padding-bottom:8px; display:inline-block; }
        .sertif .nilai-box { display:inline-block; background:#1a5fa8; color:white; padding:10px 32px; border-radius:8px; font-size:24px; font-weight:bold; margin:16px 0; }
        .sertif .ttd { display:flex; justify-content:space-around; margin-top:48px; }
        .sertif .ttd .pihak { text-align:center; }
        .sertif .ttd .pihak .garis { border-top:1px solid #333; margin-top:48px; padding-top:8px; font-size:13px; min-width:160px; }
    </style>
</head>
<body>
<?php sidebarAdmin('admin_sertifikat'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Sertifikat PKL</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Sertifikat PKL</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_admin.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Sertifikat</a></li>
                </ul>
            </div>
        </div>

        <?php if ($preview): ?>
        <div class="sertifikat-wrap">
            <div class="sertifikat-actions">
                <a href="admin_sertifikat.php" class="btn-back"><i class='bx bx-arrow-back'></i> Kembali</a>
                <button class="btn-print" onclick="window.print()"><i class='bx bxs-printer'></i> Cetak / Simpan PDF</button>
            </div>

            <div class="sertif" id="sertifikat">
                <div class="logo-sertif"><i class='bx bxs-graduation'></i></div>
                <h1>SERTIFIKAT</h1>
                <h2>Praktek Kerja Lapangan (PKL)</h2>
                <p>Diberikan kepada:</p>
                <div class="nama-siswa"><?= htmlspecialchars($preview['nama_lengkap'] ?? 'Nama Siswa') ?></div>
                <p>Telah berhasil menyelesaikan Praktek Kerja Lapangan di</p>
                <p><strong><?= htmlspecialchars($preview['nama_perusahaan']) ?></strong></p>
                <p>Periode: <strong><?= date('d M Y', strtotime($preview['tanggal_mulai'])) ?></strong> s/d <strong><?= date('d M Y', strtotime($preview['tanggal_selesai'])) ?></strong></p>
                <p>Dengan nilai akhir:</p>
                <div class="nilai-box"><?= $preview['nilai_akhir'] ?? '-' ?></div>

                <div class="ttd">
                    <div class="pihak">
                        <div class="garis">Pembimbing<br><strong><?= htmlspecialchars($preview['nama_pembimbing'] ?? '-') ?></strong></div>
                    </div>
                    <div class="pihak">
                        <div class="garis">Admin PKL<br><strong><?= htmlspecialchars($_SESSION['username']) ?></strong></div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <div class="table-card">
            <table>
                <thead><tr><th>No</th><th>Siswa</th><th>Perusahaan</th><th>Periode</th><th>Nilai</th><th>Pembimbing</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if ($list && $list->num_rows > 0): $no=1; ?>
                        <?php while ($row = $list->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                            <td><?= date('d M Y', strtotime($row['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($row['tanggal_selesai'])) ?></td>
                            <td><?= $row['nilai_akhir'] ?? '-' ?></td>
                            <td><?= htmlspecialchars($row['nama_pembimbing'] ?? '-') ?></td>
                            <td>
                                <a href="?generate=<?= $row['id'] ?>" class="btn-gen"><i class='bx bxs-award'></i> Generate</a>
                                <?php if ($row['sertifikat']): ?>
                                <a href="?preview=<?= $row['id'] ?>" class="btn-preview"><i class='bx bx-show'></i> Preview</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--dark-grey)">Belum ada siswa yang menyelesaikan PKL.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
