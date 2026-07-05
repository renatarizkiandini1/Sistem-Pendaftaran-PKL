<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'siswa') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM pendaftaran WHERE user_id = ? AND status IN ('Diterima','Sedang PKL','Menunggu Penilaian')");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pkl = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pkl) {
    $stmt2 = $conn->prepare("SELECT status FROM pendaftaran WHERE user_id = ?");
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $pendaftaran = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
    if (!$pendaftaran) { header("Location: dashboard_siswa.php"); exit(); }
    $pesanRedirect = 'Status PKL kamu saat ini <b>'.$pendaftaran['status'].'</b>. Logbook hanya bisa diisi saat status <b>Sedang PKL</b>.';
}

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
        .logbook-layout { display: grid; grid-template-columns: 340px 1fr; gap: 20px; align-items: start; }
        .catatan-pembimbing { font-size: 12px; color: var(--blue); margin-top: 4px; }
        @media (max-width: 900px) { .logbook-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<?php sidebarSiswa('logbook'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span class="nav-title">Logbook PKL</span>
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

        <div class="logbook-layout">
            <div class="card">
                <div class="card-header"><h3><i class='bx bxs-edit'></i> Tambah Aktivitas</h3></div>
                <?php if ($pesan === 'success'): ?><div class="alert alert-success"><i class='bx bxs-check-circle'></i> Logbook berhasil disimpan!</div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-error"><i class='bx bxs-error'></i> <?= $error ?></div><?php endif; ?>
                <?php if (!empty($pesanRedirect)): ?>
                <div class="alert alert-warning"><i class='bx bxs-error'></i> <span><?= $pesanRedirect ?></span></div>
                <?php elseif ($pkl['status'] !== 'Sedang PKL'): ?>
                <div class="alert alert-warning"><i class='bx bxs-error'></i> <span>Logbook hanya bisa diisi saat status <b>Sedang PKL</b>. Status kamu saat ini: <b><?= $pkl['status'] ?></b></span></div>
                <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="tambah">
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>"
                            min="<?= $pkl['tanggal_mulai'] ?>" max="<?= $pkl['tanggal_selesai'] ?>" 
                            <?= $pkl['status'] !== 'Sedang PKL' ? 'disabled' : '' ?> required>
                    </div>
                    <div class="form-group">
                        <label>Aktivitas</label>
                        <textarea name="aktivitas" rows="5" placeholder="Ceritakan aktivitas hari ini..." 
                            <?= $pkl['status'] !== 'Sedang PKL' ? 'disabled' : '' ?> required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%" 
                        <?= $pkl['status'] !== 'Sedang PKL' ? 'disabled' : '' ?>>Simpan</button>
                </form>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header"><h3><i class='bx bxs-book-alt'></i> Riwayat Logbook</h3></div>
                <div class="table-wrap">
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
                                <td><span class="badge <?= strtolower($row['status_verifikasi']) ?>"><?= $row['status_verifikasi'] ?></span></td>
                                <td>
                                    <?php if ($row['status_verifikasi'] === 'Menunggu'): ?>
                                    <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm btn-icon" onclick="return confirm('Hapus logbook ini?')"><i class='bx bxs-trash'></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;padding:28px;color:var(--dark-grey)">Belum ada logbook.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
