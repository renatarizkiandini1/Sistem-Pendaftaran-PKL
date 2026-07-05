<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'pembimbing') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id    = $_SESSION['user_id'];
$res        = $conn->query("SELECT * FROM pembimbing WHERE user_id=$user_id");
$pembimbing = $res ? $res->fetch_assoc() : null;
$pb_id      = $pembimbing['id'] ?? 0;

$filterStatus = $_GET['status'] ?? '';
$search       = $_GET['search'] ?? '';

// Handle ubah status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ubah_status') {
    $pkl_id = (int)$_POST['pkl_id'];
    $status = $_POST['status'];
    $allowed = ['Sedang PKL', 'Menunggu Penilaian', 'Selesai'];
    
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE pendaftaran SET status=? WHERE id=? AND pembimbing_id=?");
        $stmt->bind_param("sii", $status, $pkl_id, $pb_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: siswa_bimbingan.php");
    exit();
}

$where = ["p.pembimbing_id = $pb_id", "p.status IN ('Diterima','Sedang PKL','Menunggu Penilaian','Selesai')"];
if ($filterStatus) $where[] = "p.status = '" . $conn->real_escape_string($filterStatus) . "'";
if ($search) {
    $s = $conn->real_escape_string($search);
    $where[] = "(s.nama_lengkap LIKE '%$s%' OR s.nisn LIKE '%$s%' OR pr.nama_perusahaan LIKE '%$s%')";
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$list = $conn->query("SELECT p.*, u.username, s.nama_lengkap, s.kelas, s.jurusan, s.nisn, pr.nama_perusahaan,
    (SELECT COUNT(*) FROM logbook WHERE pendaftaran_id=p.id) as total_logbook,
    (SELECT COUNT(*) FROM logbook WHERE pendaftaran_id=p.id AND status_verifikasi='Menunggu') as pending_logbook,
    n.nilai_akhir, n.status as status_nilai
    FROM pendaftaran p JOIN user u ON p.user_id=u.id LEFT JOIN siswa s ON s.user_id=p.user_id
    JOIN perusahaan pr ON p.perusahaan_id=pr.id LEFT JOIN penilaian n ON n.pendaftaran_id=p.id
    $whereSQL ORDER BY s.nama_lengkap");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siswa Bimbingan</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .toolbar { display:flex; gap:12px; align-items:center; margin-top:20px; flex-wrap:wrap; }
        .search-box { flex:1; min-width:220px; position:relative; }
        .search-box input { width:100%; padding:10px 40px 10px 40px; border:2px solid var(--border); border-radius:10px; font-size:13px; }
        .search-box input:focus { border-color:var(--blue); outline:none; }
        .search-box i.icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--dark-grey); }
        .filter-bar { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
        .filter-btn { padding:7px 16px; border-radius:20px; border:none; cursor:pointer; font-size:12px; font-weight:600; background:var(--grey); color:var(--dark); text-decoration:none; transition:all 0.2s; }
        .filter-btn.active { background:var(--blue); color:white; }
        .table-card { background:var(--white); border-radius:16px; padding:24px; margin-top:16px; overflow-x:auto; border:1px solid var(--border); }
        table { width:100%; border-collapse:collapse; min-width:900px; }
        th { padding:12px 8px; font-size:12px; text-align:left; border-bottom:2px solid var(--border); color:var(--dark-grey); text-transform:uppercase; }
        td { padding:14px 8px; font-size:13px; border-bottom:1px solid var(--border); vertical-align:middle; }
        tr:hover td { background:var(--light); }
        .status-badge { padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; }
        .status-badge.diterima           { background:#EBF4FF; color:var(--blue); }
        .status-badge.sedang-pkl         { background:#D1FAE5; color:#065F46; }
        .status-badge.menunggu-penilaian { background:#FEF3C7; color:#92400E; }
        .status-badge.selesai            { background:#E0E7FF; color:#3730A3; }
        .nilai-chip { padding:4px 10px; border-radius:8px; font-size:12px; font-weight:700; }
        .nilai-chip.lulus  { background:#D1FAE5; color:#065F46; }
        .nilai-chip.tidak  { background:#FEE2E2; color:#991B1B; }
        .nilai-chip.draft  { background:#FEF3C7; color:#92400E; }
        .btn-act { padding:6px 14px; border-radius:8px; border:none; cursor:pointer; font-size:12px; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px; }
        .btn-log  { background:var(--light-blue); color:var(--blue); }
        .btn-nilai { background:var(--light-green); color:var(--green); }
        .btn-status { background:var(--light-orange); color:var(--orange); }
        .catatan-admin { font-size:11px; color:var(--orange); margin-top:4px; font-style:italic; }
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; }
        .modal-overlay.show { display:flex; }
        .modal { background:white; border-radius:16px; padding:28px; width:100%; max-width:420px; }
        .modal h3 { margin-bottom:16px; font-size:16px; }
        .form-group { margin-bottom:14px; }
        .form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; }
        .form-group select { width:100%; padding:10px 12px; border:2px solid var(--border); border-radius:8px; font-size:13px; }
        .form-group select:focus { border-color:var(--blue); outline:none; }
        .modal-footer { display:flex; gap:8px; justify-content:flex-end; margin-top:16px; }
        .btn-save { background:var(--blue); color:white; padding:8px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
        .btn-cancel { background:var(--grey); color:var(--dark); padding:8px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
    </style>
</head>
<body>
<?php sidebarPembimbing('siswa_bimbingan'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Siswa Bimbingan</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Siswa Bimbingan</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_pembimbing.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Siswa Bimbingan</a></li>
                </ul>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="toolbar">
            <div class="search-box">
                <i class='bx bx-search icon'></i>
                <input type="text" id="searchInput" placeholder="Cari nama, NISN, atau perusahaan..."
                    value="<?= htmlspecialchars($search) ?>"
                    onkeypress="if(event.key==='Enter') doSearch()">
            </div>
            <button onclick="doSearch()" style="padding:10px 20px;background:var(--blue);color:white;border:none;border-radius:10px;cursor:pointer;font-size:13px;font-weight:600;">
                <i class='bx bx-search'></i> Cari
            </button>
        </div>
        <div class="filter-bar">
            <a href="siswa_bimbingan.php" class="filter-btn <?= !$filterStatus ? 'active' : '' ?>">Semua</a>
            <a href="?status=Diterima" class="filter-btn <?= $filterStatus==='Diterima' ? 'active' : '' ?>">Diterima</a>
            <a href="?status=Sedang PKL" class="filter-btn <?= $filterStatus==='Sedang PKL' ? 'active' : '' ?>">Sedang PKL</a>
            <a href="?status=Menunggu Penilaian" class="filter-btn <?= $filterStatus==='Menunggu Penilaian' ? 'active' : '' ?>">Menunggu Penilaian</a>
            <a href="?status=Selesai" class="filter-btn <?= $filterStatus==='Selesai' ? 'active' : '' ?>">Selesai</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr><th>Nama Siswa</th><th>NISN</th><th>Kelas</th><th>Perusahaan</th><th>Periode PKL</th><th>Status</th><th>Logbook</th><th>Nilai</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    <?php if ($list && $list->num_rows > 0): ?>
                        <?php while ($row = $list->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['nama_lengkap'] ?? $row['username']) ?></strong><br>
                                <small style="color:var(--dark-grey);"><?= htmlspecialchars($row['jurusan'] ?? '') ?></small>
                                <?php if ($row['catatan']): ?>
                                <div class="catatan-admin"><i class='bx bxs-comment'></i> <?= htmlspecialchars($row['catatan']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['nisn'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['kelas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                            <td style="white-space:nowrap;font-size:12px;">
                                <?= $row['tanggal_mulai'] ? date('d M Y', strtotime($row['tanggal_mulai'])) : '-' ?><br>
                                <?= $row['tanggal_selesai'] ? date('d M Y', strtotime($row['tanggal_selesai'])) : '' ?>
                            </td>
                            <td><span class="status-badge <?= strtolower(str_replace(' ','-',$row['status'])) ?>"><?= $row['status'] ?></span></td>
                            <td>
                                <span><?= $row['total_logbook'] ?> entri</span>
                                <?php if ($row['pending_logbook'] > 0): ?>
                                <br><span style="color:var(--orange);font-size:11px;font-weight:700;"><?= $row['pending_logbook'] ?> pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['nilai_akhir']): ?>
                                    <?php $lulus = $row['nilai_akhir'] >= 75; ?>
                                    <span class="nilai-chip <?= $row['status_nilai']==='Final' ? ($lulus ? 'lulus' : 'tidak') : 'draft' ?>">
                                        <?= $row['nilai_akhir'] ?> <?= $row['status_nilai']==='Draft' ? '(Draft)' : '' ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--dark-grey);font-size:12px;">Belum dinilai</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="detail_logbook.php?id=<?= $row['id'] ?>" class="btn-act btn-log">
                                    <i class='bx bxs-book-alt'></i> Logbook
                                </a>
                                <a href="beri_nilai.php?id=<?= $row['id'] ?>" class="btn-act btn-nilai" style="margin-top:4px;display:inline-flex;">
                                    <i class='bx bxs-star'></i> Nilai
                                </a>
                                <?php if ($row['status'] !== 'Selesai'): ?>
                                <button class="btn-act btn-status" onclick="openStatusModal(<?= $row['id'] ?>, '<?= $row['status'] ?>')" style="margin-top:4px;display:inline-flex;">
                                    <i class='bx bxs-edit'></i> Status
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--dark-grey)">
                            <i class='bx bxs-group' style="font-size:48px;opacity:0.3;"></i><br>
                            Tidak ada data siswa ditemukan.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>

<!-- Modal Ubah Status -->
<div class="modal-overlay" id="modalStatus">
    <div class="modal">
        <h3><i class='bx bxs-edit'></i> Ubah Status PKL</h3>
        <form method="POST">
            <input type="hidden" name="action" value="ubah_status">
            <input type="hidden" name="pkl_id" id="m-pkl-id">
            <div class="form-group">
                <label>Status Baru</label>
                <select name="status" id="m-status" required>
                    <option value="">-- Pilih Status --</option>
                    <option value="Sedang PKL">Sedang PKL</option>
                    <option value="Menunggu Penilaian">Menunggu Penilaian</option>
                    <option value="Selesai">Selesai</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeStatusModal()">Batal</button>
                <button type="submit" class="btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script src="script.js"></script>
<script>
function doSearch() {
    const q = document.getElementById('searchInput').value;
    const p = new URLSearchParams(window.location.search);
    q ? p.set('search', q) : p.delete('search');
    window.location.href = '?' + p.toString();
}

function openStatusModal(pklId, currentStatus) {
    document.getElementById('m-pkl-id').value = pklId;
    const statusMap = {'Diterima': 'Sedang PKL', 'Sedang PKL': 'Menunggu Penilaian', 'Menunggu Penilaian': 'Selesai'};
    const nextStatus = statusMap[currentStatus] || '';
    document.getElementById('m-status').value = nextStatus;
    document.getElementById('modalStatus').classList.add('show');
}

function closeStatusModal() {
    document.getElementById('modalStatus').classList.remove('show');
}

document.getElementById('modalStatus').addEventListener('click', (e) => {
    if (e.target === document.getElementById('modalStatus')) closeStatusModal();
});
</script>
</body>
</html>
