<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'pembimbing') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id    = $_SESSION['user_id'];
$res        = $conn->query("SELECT * FROM pembimbing WHERE user_id = $user_id");
$pembimbing = $res ? $res->fetch_assoc() : null;
$pb_id      = $pembimbing['id'] ?? 0;
$pkl_id     = (int)($_GET['id'] ?? 0);

// Validasi akses
$stmt = $conn->prepare("SELECT p.*, u.username, s.nama_lengkap, s.kelas, s.jurusan, pr.nama_perusahaan
    FROM pendaftaran p JOIN user u ON p.user_id = u.id
    LEFT JOIN siswa s ON s.user_id = p.user_id
    JOIN perusahaan pr ON p.perusahaan_id = pr.id
    WHERE p.id = ? AND p.pembimbing_id = ?");
$stmt->bind_param("ii", $pkl_id, $pb_id);
$stmt->execute();
$pkl = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pkl) { header("Location: siswa_bimbingan.php"); exit(); }

// Verifikasi semua sekaligus
if (isset($_GET['verif_all'])) {
    $conn->query("UPDATE logbook SET status_verifikasi='Diverifikasi' WHERE pendaftaran_id=$pkl_id AND status_verifikasi='Menunggu'");
    header("Location: detail_logbook.php?id=$pkl_id&success=1");
    exit();
}

// Verifikasi satu logbook
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $log_id  = (int)$_POST['log_id'];
    $catatan = $_POST['catatan'];
    $stmt    = $conn->prepare("UPDATE logbook SET status_verifikasi='Diverifikasi', catatan_pembimbing=? WHERE id=? AND pendaftaran_id=?");
    $stmt->bind_param("sii", $catatan, $log_id, $pkl_id);
    $stmt->execute();
    $stmt->close();
    header("Location: detail_logbook.php?id=$pkl_id");
    exit();
}

// Filter
$filterStatus = $_GET['filter'] ?? '';
$whereFilter  = $filterStatus ? "AND status_verifikasi = '" . $conn->real_escape_string($filterStatus) . "'" : '';
$logbooks = $conn->query("SELECT * FROM logbook WHERE pendaftaran_id = $pkl_id $whereFilter ORDER BY tanggal DESC");

// Statistik logbook
$qStat = $conn->query("SELECT
    COUNT(*) as total,
    SUM(status_verifikasi='Diverifikasi') as verified,
    SUM(status_verifikasi='Menunggu') as pending
    FROM logbook WHERE pendaftaran_id = $pkl_id");
$stat = $qStat ? $qStat->fetch_assoc() : ['total'=>0,'verified'=>0,'pending'=>0];
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
        .info-bar { background:var(--light); border-radius:12px; padding:16px 24px; margin-top:20px; display:flex; gap:32px; flex-wrap:wrap; border:1px solid var(--border); }
        .info-bar .item label { font-size:11px; color:var(--dark-grey); display:block; text-transform:uppercase; letter-spacing:0.5px; }
        .info-bar .item span  { font-size:14px; font-weight:600; color:var(--dark); }
        .stat-row { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-top:16px; }
        .stat-box { background:var(--white); border-radius:12px; padding:16px; text-align:center; border:1px solid var(--border); }
        .stat-box .num { font-size:26px; font-weight:700; }
        .stat-box .lbl { font-size:12px; color:var(--dark-grey); margin-top:2px; }
        .toolbar { display:flex; gap:10px; align-items:center; margin-top:20px; flex-wrap:wrap; justify-content:space-between; }
        .filter-bar { display:flex; gap:8px; }
        .filter-btn { padding:7px 16px; border-radius:20px; font-size:12px; font-weight:600; text-decoration:none; background:var(--grey); color:var(--dark); border:none; cursor:pointer; }
        .filter-btn.active { background:var(--blue); color:white; }
        .table-card { background:var(--white); border-radius:16px; padding:24px; margin-top:12px; overflow-x:auto; border:1px solid var(--border); }
        table { width:100%; border-collapse:collapse; }
        th { padding:12px 8px; font-size:12px; text-align:left; border-bottom:2px solid var(--border); color:var(--dark-grey); text-transform:uppercase; }
        td { padding:12px 8px; font-size:13px; border-bottom:1px solid var(--border); vertical-align:top; }
        tr:hover td { background:var(--light); }
        .badge { padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .badge.menunggu    { background:var(--light-orange); color:var(--orange); }
        .badge.diverifikasi { background:var(--light-green); color:var(--green); }
        .btn-verif { background:var(--blue); color:white; padding:6px 14px; border-radius:8px; border:none; cursor:pointer; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
        .btn-verif-all { background:var(--green); color:white; padding:8px 18px; border-radius:10px; border:none; cursor:pointer; font-size:13px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; }
        .modal-overlay.show { display:flex; }
        .modal { background:white; border-radius:16px; padding:32px; width:90%; max-width:460px; }
        .form-group { margin-bottom:14px; }
        .form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:5px; }
        .form-group textarea { width:100%; padding:9px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px; }
        .modal-footer { display:flex; gap:8px; justify-content:flex-end; margin-top:14px; }
        .btn-save   { background:var(--blue); color:white; padding:8px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
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
                    <li><a href="siswa_bimbingan.php">Siswa Bimbingan</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Logbook</a></li>
                </ul>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div style="background:var(--light-green);color:var(--green);padding:12px 16px;border-radius:10px;margin-top:12px;font-size:13px;">
            <i class='bx bxs-check-circle'></i> Semua logbook pending berhasil diverifikasi!
        </div>
        <?php endif; ?>

        <!-- Info Siswa -->
        <div class="info-bar">
            <div class="item"><label>Siswa</label><span><?= htmlspecialchars($pkl['nama_lengkap'] ?? $pkl['username']) ?></span></div>
            <div class="item"><label>Kelas</label><span><?= htmlspecialchars($pkl['kelas'] ?? '-') ?></span></div>
            <div class="item"><label>Perusahaan</label><span><?= htmlspecialchars($pkl['nama_perusahaan']) ?></span></div>
            <div class="item"><label>Periode PKL</label>
                <span><?= $pkl['tanggal_mulai'] ? date('d M Y', strtotime($pkl['tanggal_mulai'])) : '-' ?>
                s/d <?= $pkl['tanggal_selesai'] ? date('d M Y', strtotime($pkl['tanggal_selesai'])) : '-' ?></span>
            </div>
        </div>

        <!-- Statistik Logbook -->
        <div class="stat-row">
            <div class="stat-box">
                <div class="num"><?= $stat['total'] ?></div>
                <div class="lbl">Total Logbook</div>
            </div>
            <div class="stat-box">
                <div class="num" style="color:var(--green);"><?= $stat['verified'] ?></div>
                <div class="lbl">Diverifikasi</div>
            </div>
            <div class="stat-box">
                <div class="num" style="color:<?= $stat['pending'] > 0 ? 'var(--orange)' : 'var(--green)' ?>;"><?= $stat['pending'] ?></div>
                <div class="lbl">Menunggu</div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="filter-bar">
                <a href="?id=<?= $pkl_id ?>" class="filter-btn <?= !$filterStatus ? 'active' : '' ?>">Semua</a>
                <a href="?id=<?= $pkl_id ?>&filter=Menunggu" class="filter-btn <?= $filterStatus==='Menunggu' ? 'active' : '' ?>">Menunggu</a>
                <a href="?id=<?= $pkl_id ?>&filter=Diverifikasi" class="filter-btn <?= $filterStatus==='Diverifikasi' ? 'active' : '' ?>">Diverifikasi</a>
            </div>
            <?php if ($stat['pending'] > 0): ?>
            <a href="?id=<?= $pkl_id ?>&verif_all=1" class="btn-verif-all" onclick="return confirm('Verifikasi semua <?= $stat['pending'] ?> logbook pending?')">
                <i class='bx bxs-check-circle'></i> Verifikasi Semua (<?= $stat['pending'] ?>)
            </a>
            <?php endif; ?>
        </div>

        <!-- Tabel Logbook -->
        <div class="table-card">
            <table>
                <thead><tr><th>Tanggal</th><th>Aktivitas</th><th>Catatan Pembimbing</th><th>Status</th><th>Aksi</th></tr></thead>
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
                                <button class="btn-verif" onclick="openModal(<?= $row['id'] ?>, `<?= addslashes($row['catatan_pembimbing'] ?? '') ?>`)">
                                    <i class='bx bx-check'></i> Verifikasi
                                </button>
                                <?php else: ?>
                                <span style="color:var(--green);font-size:12px;"><i class='bx bxs-check-circle'></i> Terverifikasi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--dark-grey)">
                            <i class='bx bxs-book' style="font-size:40px;opacity:0.3;"></i><br>
                            Tidak ada logbook ditemukan.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>

<div class="modal-overlay" id="modalVerif">
    <div class="modal">
        <h3><i class='bx bxs-check-shield'></i> Verifikasi Logbook</h3>
        <form method="POST">
            <input type="hidden" name="log_id" id="log-id">
            <div class="form-group">
                <label>Catatan Pembimbing (opsional)</label>
                <textarea name="catatan" id="log-catatan" rows="3" placeholder="Tambahkan catatan atau feedback..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalVerif').classList.remove('show')">Batal</button>
                <button type="submit" class="btn-save"><i class='bx bx-check'></i> Verifikasi</button>
            </div>
        </form>
    </div>
</div>

<script src="script.js"></script>
<script>
function openModal(id, catatan) {
    document.getElementById('log-id').value      = id;
    document.getElementById('log-catatan').value = catatan;
    document.getElementById('modalVerif').classList.add('show');
}
document.getElementById('modalVerif').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});
</script>
</body>
</html>
