<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

// Generate sertifikat
if (isset($_GET['generate']) && isset($_GET['id'])) {
    $pkl_id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT p.*, s.nama_lengkap, pr.nama_perusahaan FROM pendaftaran p 
        LEFT JOIN siswa s ON s.user_id = p.user_id 
        JOIN perusahaan pr ON p.perusahaan_id = pr.id 
        WHERE p.id = ? AND p.status = 'Selesai'");
    $stmt->bind_param("i", $pkl_id);
    $stmt->execute();
    $pkl = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($pkl) {
        $nomor_sertifikat = 'SERT-' . date('Y') . '-' . str_pad($pkl_id, 5, '0', STR_PAD_LEFT);
        $tanggal_terbit = date('Y-m-d');
        
        $stmt = $conn->prepare("INSERT INTO sertifikat (pendaftaran_id, nomor_sertifikat, tanggal_terbit) VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE nomor_sertifikat = ?, tanggal_terbit = ?");
        $stmt->bind_param("issss", $pkl_id, $nomor_sertifikat, $tanggal_terbit, $nomor_sertifikat, $tanggal_terbit);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_sertifikat.php");
    exit();
}

// Hapus sertifikat
if (isset($_GET['hapus']) && isset($_GET['id'])) {
    $pkl_id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM sertifikat WHERE pendaftaran_id = ?");
    $stmt->bind_param("i", $pkl_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_sertifikat.php");
    exit();
}

$list = $conn->query("SELECT p.id, s.nama_lengkap, pr.nama_perusahaan, p.tanggal_selesai, p.status,
    CASE WHEN sr.id IS NOT NULL THEN 'Ada' ELSE 'Belum' END as status_sertifikat,
    sr.nomor_sertifikat, sr.tanggal_terbit
    FROM pendaftaran p
    LEFT JOIN siswa s ON s.user_id = p.user_id
    JOIN perusahaan pr ON p.perusahaan_id = pr.id
    LEFT JOIN sertifikat sr ON sr.pendaftaran_id = p.id
    WHERE p.status = 'Selesai'
    ORDER BY p.tanggal_selesai DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Sertifikat</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .table-card { background:var(--white); border-radius:16px; padding:24px; margin-top:20px; overflow-x:auto; border:1px solid var(--border); }
        table { width:100%; border-collapse:collapse; min-width:800px; }
        th { padding:12px 8px; font-size:12px; text-align:left; border-bottom:2px solid var(--border); color:var(--dark-grey); text-transform:uppercase; }
        td { padding:12px 8px; font-size:13px; border-bottom:1px solid var(--border); vertical-align:middle; }
        tr:hover td { background:var(--light); }
        .status-badge { padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .status-badge.ada { background:var(--light-green); color:var(--green); }
        .status-badge.belum { background:var(--light-orange); color:var(--orange); }
        .btn { padding:6px 12px; border-radius:8px; border:none; cursor:pointer; font-size:12px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
        .btn-blue { background:var(--light-blue); color:var(--blue); }
        .btn-green { background:var(--light-green); color:var(--green); }
        .btn-red { background:var(--light-red); color:var(--red); }
    </style>
</head>
<body>
<?php sidebarAdmin('admin_sertifikat'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Kelola Sertifikat</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Kelola Sertifikat</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_admin.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Sertifikat</a></li>
                </ul>
            </div>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>No</th><th>Nama Siswa</th><th>Perusahaan</th><th>Tanggal Selesai</th>
                        <th>Status Sertifikat</th><th>Nomor Sertifikat</th><th>Tanggal Terbit</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($list && $list->num_rows > 0):
                        $no = 1;
                    ?>
                        <?php while ($row = $list->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong></td>
                            <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                            <td><?= $row['tanggal_selesai'] ? date('d M Y', strtotime($row['tanggal_selesai'])) : '-' ?></td>
                            <td><span class="status-badge <?= strtolower($row['status_sertifikat']) ?>"><?= $row['status_sertifikat'] ?></span></td>
                            <td><?= htmlspecialchars($row['nomor_sertifikat'] ?? '-') ?></td>
                            <td><?= $row['tanggal_terbit'] ? date('d M Y', strtotime($row['tanggal_terbit'])) : '-' ?></td>
                            <td>
                                <?php if ($row['status_sertifikat'] === 'Belum'): ?>
                                <a href="?generate=1&id=<?= $row['id'] ?>" class="btn btn-green" onclick="return confirm('Generate sertifikat untuk siswa ini?')">
                                    <i class='bx bxs-file-pdf'></i> Generate
                                </a>
                                <?php else: ?>
                                <a href="?hapus=1&id=<?= $row['id'] ?>" class="btn btn-red" onclick="return confirm('Hapus sertifikat ini?')">
                                    <i class='bx bxs-trash'></i> Hapus
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--dark-grey)">
                            <i class='bx bxs-award' style="font-size:48px;opacity:0.3;"></i><br>
                            Belum ada PKL yang selesai.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
