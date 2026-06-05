<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pkl_id  = $_POST['pkl_id'];
    $pb_id   = $_POST['pembimbing_id'];

    // Cek kuota pembimbing
    $pb      = $conn->query("SELECT kuota FROM pembimbing WHERE id = $pb_id")->fetch_assoc();
    $terpakai = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE pembimbing_id = $pb_id AND status = 'Diterima'")->fetch_assoc()['t'];

    if ($pb && $terpakai >= $pb['kuota']) {
        $error = "Kuota pembimbing ini sudah penuh ({$pb['kuota']} siswa).";
    } else {
        $stmt = $conn->prepare("UPDATE pendaftaran SET pembimbing_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $pb_id, $pkl_id);
        $stmt->execute();
        $stmt->close();
        header("Location: admin_tugaskan_pembimbing.php");
        exit();
    }
}

$siswaAktif = $conn->query("SELECT p.*, s.nama_lengkap, u.username, pr.nama_perusahaan, pb.nama_lengkap as nama_pembimbing
    FROM pendaftaran p
    JOIN user u ON p.user_id = u.id
    LEFT JOIN siswa s ON s.user_id = p.user_id
    JOIN perusahaan pr ON p.perusahaan_id = pr.id
    LEFT JOIN pembimbing pb ON p.pembimbing_id = pb.id
    WHERE p.status = 'Diterima'
    ORDER BY p.created_at DESC");

$pembimbingList = $conn->query("SELECT pb.*, u.username,
    (SELECT COUNT(*) FROM pendaftaran WHERE pembimbing_id = pb.id AND status = 'Diterima') as terpakai
    FROM pembimbing pb JOIN user u ON pb.user_id = u.id ORDER BY pb.nama_lengkap");
$pbArr = [];
while ($pb = $pembimbingList->fetch_assoc()) $pbArr[] = $pb;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugaskan Pembimbing</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .table-card { background:var(--light); border-radius:16px; padding:24px; margin-top:24px; overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        th { padding-bottom:10px; font-size:12px; text-align:left; border-bottom:1px solid var(--grey); color:var(--dark-grey); }
        td { padding:10px 0; font-size:13px; border-bottom:1px solid var(--grey); }
        select.sel-pb { padding:5px 10px; border-radius:6px; border:1px solid #ddd; font-size:13px; }
        .btn-save { background:var(--blue); color:white; padding:5px 14px; border-radius:6px; border:none; cursor:pointer; font-size:12px; font-weight:600; margin-left:6px; }
        .kuota-badge { font-size:11px; padding:2px 8px; border-radius:10px; background:var(--light-blue); color:var(--blue); font-weight:600; }
        .kuota-penuh { background:#fde8e8; color:var(--red); }
        .alert-error { background:#fde8e8; color:#c0392b; padding:12px 16px; border-radius:10px; margin-top:16px; font-size:13px; }
    </style>
</head>
<body>
<?php sidebarAdmin('admin_tugaskan_pembimbing'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Tugaskan Pembimbing</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Tugaskan Pembimbing</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_admin.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Tugaskan Pembimbing</a></li>
                </ul>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert-error"><i class='bx bxs-error'></i> <?= $error ?></div>
        <?php endif; ?>

        <div class="table-card">
            <table>
                <thead>
                    <tr><th>No</th><th>Siswa</th><th>Perusahaan</th><th>Pembimbing Saat Ini</th><th>Tugaskan Pembimbing</th></tr>
                </thead>
                <tbody>
                    <?php if ($siswaAktif && $siswaAktif->num_rows > 0): $no=1; ?>
                        <?php while ($row = $siswaAktif->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap'] ?? $row['username']) ?></td>
                            <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pembimbing'] ?? '<span style="color:var(--dark-grey)">Belum ditentukan</span>') ?></td>
                            <td>
                                <form method="POST" style="display:flex;align-items:center;gap:6px">
                                    <input type="hidden" name="pkl_id" value="<?= $row['id'] ?>">
                                    <select name="pembimbing_id" class="sel-pb">
                                        <option value="">-- Pilih --</option>
                                        <?php foreach ($pbArr as $pb):
                                            $penuh   = $pb['terpakai'] >= $pb['kuota'];
                                            $sel     = $row['pembimbing_id'] == $pb['id'] ? 'selected' : '';
                                            $disabled = $penuh && $row['pembimbing_id'] != $pb['id'] ? 'disabled' : '';
                                        ?>
                                        <option value="<?= $pb['id'] ?>" <?= $sel ?> <?= $disabled ?>>
                                            <?= htmlspecialchars($pb['nama_lengkap']) ?> (<?= $pb['terpakai'] ?>/<?= $pb['kuota'] ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-save">Simpan</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--dark-grey)">Belum ada siswa aktif PKL.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
