<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'pembimbing') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id    = $_SESSION['user_id'];
$pembimbing = $conn->query("SELECT * FROM pembimbing WHERE user_id = $user_id")->fetch_assoc();
$pb_id      = $pembimbing['id'] ?? 0;
$pkl_id     = $_GET['id'] ?? 0;

// Validasi akses
$stmt = $conn->prepare("SELECT p.*, u.username, s.nama_lengkap, s.kelas, s.jurusan, pr.nama_perusahaan FROM pendaftaran p JOIN user u ON p.user_id = u.id LEFT JOIN siswa s ON s.user_id = p.user_id JOIN perusahaan pr ON p.perusahaan_id = pr.id WHERE p.id = ? AND p.pembimbing_id = ?");
$stmt->bind_param("ii", $pkl_id, $pb_id);
$stmt->execute();
$pkl = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pkl) { header("Location: siswa_bimbingan.php"); exit(); }

// Verifikasi logbook + tambah catatan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_id  = $_POST['log_id'];
    $catatan = $_POST['catatan'];
    $stmt    = $conn->prepare("UPDATE logbook SET status_verifikasi='Diverifikasi', catatan_pembimbing=? WHERE id=? AND pendaftaran_id=?");
    $stmt->bind_param("sii", $catatan, $log_id, $pkl_id);
    $stmt->execute();
    $stmt->close();
    header("Location: detail_logbook.php?id=$pkl_id");
    exit();
}

$logbooks = $conn->query("SELECT * FROM logbook WHERE pendaftaran_id = $pkl_id ORDER BY tanggal DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Logbook</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .info-bar { background: var(--light); border-radius: 12px; padding: 16px 24px; margin-top: 24px; display: flex; gap: 32px; flex-wrap: wrap; }
        .info-bar .item label { font-size: 11px; color: var(--dark-grey); display: block; text-transform: uppercase; }
        .info-bar .item span { font-size: 14px; font-weight: 600; color: var(--dark); }
        .table-card { background: var(--light); border-radius: 16px; padding: 24px; margin-top: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { padding-bottom: 10px; font-size: 12px; text-align: left; border-bottom: 1px solid var(--grey); color: var(--dark-grey); }
        td { padding: 12px 0; font-size: 13px; border-bottom: 1px solid var(--grey); vertical-align: top; }
        .badge { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; color: white; }
        .badge.menunggu { background: var(--orange); }
        .badge.diverifikasi { background: #27ae60; }
        .btn-verif { background: var(--blue); color: white; padding: 5px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 12px; font-weight: 600; }
        .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; }
        .modal-overlay.show { display:flex; }
        .modal { background:white; border-radius:16px; padding:32px; width:100%; max-width:460px; }
        .modal h3 { margin-bottom:16px; }
        .form-group { margin-bottom:14px; }
        .form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:5px; }
        .form-group textarea { width:100%; padding:9px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px; }
        .modal-footer { display:flex; gap:8px; justify-content:flex-end; margin-top:14px; }
        .btn-save { background:var(--blue); color:white; padding:8px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
        .btn-cancel { background:var(--grey); color:var(--dark); padding:8px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
    </style>
</head>
<body>
<?php sidebarPembimbing('detail_logbook'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Detail Logbook</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Detail Logbook</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_pembimbing.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Logbook</a></li>
                </ul>
            </div>
        </div>

        <div class="info-bar">
            <div class="item"><label>Siswa</label><span><?= htmlspecialchars($pkl['nama_lengkap'] ?? $pkl['username']) ?></span></div>
            <div class="item"><label>Kelas</label><span><?= htmlspecialchars($pkl['kelas'] ?? '-') ?></span></div>
            <div class="item"><label>Perusahaan</label><span><?= htmlspecialchars($pkl['nama_perusahaan']) ?></span></div>
            <div class="item"><label>Periode</label><span><?= $pkl['tanggal_mulai'] ?> s/d <?= $pkl['tanggal_selesai'] ?></span></div>
        </div>

        <div class="table-card">
            <table>
                <thead><tr><th>Tanggal</th><th>Aktivitas</th><th>Catatan</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if ($logbooks && $logbooks->num_rows > 0): ?>
                        <?php while ($row = $logbooks->fetch_assoc()): ?>
                        <tr>
                            <td style="white-space:nowrap"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['aktivitas'])) ?></td>
                            <td style="color:var(--blue)"><?= htmlspecialchars($row['catatan_pembimbing'] ?? '-') ?></td>
                            <td><span class="badge <?= strtolower($row['status_verifikasi']) ?>"><?= $row['status_verifikasi'] ?></span></td>
                            <td>
                                <?php if ($row['status_verifikasi'] === 'Menunggu'): ?>
                                <button class="btn-verif" onclick="openModal(<?= $row['id'] ?>, `<?= addslashes($row['catatan_pembimbing'] ?? '') ?>`)">Verifikasi</button>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;padding:20px;color:var(--dark-grey)">Belum ada logbook.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>

<div class="modal-overlay" id="modalVerif">
    <div class="modal">
        <h3>Verifikasi Logbook</h3>
        <form method="POST">
            <input type="hidden" name="log_id" id="log-id">
            <div class="form-group">
                <label>Catatan (opsional)</label>
                <textarea name="catatan" id="log-catatan" rows="3" placeholder="Tambahkan catatan..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalVerif').classList.remove('show')">Batal</button>
                <button type="submit" class="btn-save">Verifikasi</button>
            </div>
        </form>
    </div>
</div>
<script src="script.js"></script>
<script>
function openModal(id, catatan) {
    document.getElementById('log-id').value     = id;
    document.getElementById('log-catatan').value = catatan;
    document.getElementById('modalVerif').classList.add('show');
}
</script>
</body>
</html>
