<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'siswa') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM pendaftaran WHERE user_id = ? AND status = 'Diterima'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pkl = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pkl) { header("Location: dashboard_siswa.php"); exit(); }

$pesan = '';
$error = '';

// Tambah logbook
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'tambah') {
    $tanggal   = $_POST['tanggal'];
    $aktivitas = $_POST['aktivitas'];

    $cek = $conn->prepare("SELECT id FROM logbook WHERE pendaftaran_id = ? AND tanggal = ?");
    $cek->bind_param("is", $pkl['id'], $tanggal);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
        $error = 'Logbook untuk tanggal ini sudah ada.';
    } else {
        $cek->close();
        $stmt = $conn->prepare("INSERT INTO logbook (pendaftaran_id, tanggal, aktivitas) VALUES (?,?,?)");
        $stmt->bind_param("iss", $pkl['id'], $tanggal, $aktivitas);
        $stmt->execute();
        $stmt->close();
        $pesan = 'success';
    }
}

// Hapus logbook
if (isset($_GET['hapus'])) {
    $id   = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM logbook WHERE id = ? AND pendaftaran_id = ? AND status_verifikasi = 'Menunggu'");
    $stmt->bind_param("ii", $id, $pkl['id']);
    $stmt->execute();
    $stmt->close();
    header("Location: logbook.php");
    exit();
}

$logbooks = $conn->query("SELECT * FROM logbook WHERE pendaftaran_id = {$pkl['id']} ORDER BY tanggal DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logbook PKL</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .layout { display: flex; gap: 24px; margin-top: 24px; flex-wrap: wrap; }
        .form-card { background: var(--light); border-radius: 16px; padding: 24px; flex: 1; min-width: 280px; max-width: 360px; }
        .table-card { background: var(--light); border-radius: 16px; padding: 24px; flex: 2; min-width: 300px; overflow-x: auto; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: var(--dark); }
        .form-group input, .form-group textarea { width: 100%; padding: 9px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; outline: none; }
        .form-group input:focus, .form-group textarea:focus { border-color: var(--blue); }
        .btn-submit { background: var(--blue); color: white; border: none; padding: 9px 24px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; width: 100%; }
        table { width: 100%; border-collapse: collapse; }
        th { padding-bottom: 12px; font-size: 12px; text-align: left; border-bottom: 1px solid var(--grey); color: var(--dark-grey); text-transform: uppercase; }
        td { padding: 12px 0; font-size: 13px; vertical-align: top; border-bottom: 1px solid var(--grey); }
        .badge-verif { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; color: white; }
        .badge-verif.menunggu { background: var(--orange); }
        .badge-verif.diverifikasi { background: #27ae60; }
        .btn-hapus { background: #fde8e8; color: var(--red); padding: 3px 10px; border-radius: 6px; border: none; cursor: pointer; font-size: 11px; font-weight: 600; }
        .alert-success { background: var(--light-blue); color: #0c5460; padding: 10px 14px; border-radius: 8px; margin-bottom: 12px; font-size: 13px; }
        .alert-error { background: #fde8e8; color: #c0392b; padding: 10px 14px; border-radius: 8px; margin-bottom: 12px; font-size: 13px; }
        h3 { font-size: 15px; font-weight: 700; margin-bottom: 16px; color: var(--dark); }
        .catatan-pembimbing { font-size: 12px; color: var(--blue); margin-top: 4px; }
    </style>
</head>
<body>
<?php sidebarSiswa('logbook'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Logbook PKL</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Logbook PKL</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_siswa.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Logbook</a></li>
                </ul>
            </div>
        </div>

        <div class="layout">
            <div class="form-card">
                <h3>Tambah Aktivitas</h3>
                <?php if ($pesan === 'success'): ?><div class="alert-success"><i class='bx bxs-check-circle'></i> Logbook berhasil disimpan!</div><?php endif; ?>
                <?php if ($error): ?><div class="alert-error"><i class='bx bxs-error'></i> <?= $error ?></div><?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="tambah">
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>"
                            min="<?= $pkl['tanggal_mulai'] ?>" max="<?= $pkl['tanggal_selesai'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Aktivitas</label>
                        <textarea name="aktivitas" rows="5" placeholder="Ceritakan aktivitas hari ini..." required></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Simpan</button>
                </form>
            </div>

            <div class="table-card">
                <h3>Riwayat Logbook</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Aktivitas</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($logbooks && $logbooks->num_rows > 0): ?>
                            <?php while ($row = $logbooks->fetch_assoc()): ?>
                            <tr>
                                <td style="white-space:nowrap"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                <td>
                                    <?= nl2br(htmlspecialchars($row['aktivitas'])) ?>
                                    <?php if ($row['catatan_pembimbing']): ?>
                                    <div class="catatan-pembimbing"><i class='bx bxs-comment'></i> <?= htmlspecialchars($row['catatan_pembimbing']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge-verif <?= strtolower($row['status_verifikasi']) ?>"><?= $row['status_verifikasi'] ?></span></td>
                                <td>
                                    <?php if ($row['status_verifikasi'] === 'Menunggu'): ?>
                                    <a href="?hapus=<?= $row['id'] ?>" class="btn-hapus" onclick="return confirm('Hapus logbook ini?')"><i class='bx bxs-trash'></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--dark-grey)">Belum ada logbook.</td></tr>
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
