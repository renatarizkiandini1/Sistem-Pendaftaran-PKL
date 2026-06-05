<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = $_POST['id'];
    $status  = $_POST['status'];
    $catatan = $_POST['catatan'];
    $allowed = ['Menunggu','Diterima','Ditolak','Selesai'];
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("UPDATE pendaftaran SET status=?, catatan=? WHERE id=?");
        $stmt->bind_param("ssi", $status, $catatan, $id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_pendaftaran.php");
    exit();
}

$filter = $_GET['status'] ?? '';
$where  = $filter ? "WHERE p.status = '$filter'" : '';
$data   = $conn->query("SELECT p.*, u.username, s.nama_lengkap, s.nisn, s.kelas, pr.nama_perusahaan, pb.nama_lengkap as nama_pembimbing
    FROM pendaftaran p
    JOIN user u ON p.user_id = u.id
    JOIN perusahaan pr ON p.perusahaan_id = pr.id
    LEFT JOIN siswa s ON s.user_id = p.user_id
    LEFT JOIN pembimbing pb ON p.pembimbing_id = pb.id
    $where ORDER BY p.created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pendaftaran</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .filter-bar { display:flex; gap:8px; margin-top:24px; flex-wrap:wrap; }
        .filter-btn { padding:6px 18px; border-radius:20px; border:none; cursor:pointer; font-size:13px; font-weight:600; background:var(--grey); color:var(--dark); text-decoration:none; }
        .filter-btn.active { background:var(--blue); color:white; }
        .table-card { background:var(--light); border-radius:16px; padding:24px; margin-top:20px; overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        th { padding-bottom:10px; font-size:12px; text-align:left; border-bottom:1px solid var(--grey); color:var(--dark-grey); }
        td { padding:10px 0; font-size:13px; border-bottom:1px solid var(--grey); }
        .status-badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; color:white; }
        .status-badge.menunggu { background:var(--orange); }
        .status-badge.diterima { background:var(--blue); }
        .status-badge.ditolak  { background:var(--red); }
        .status-badge.selesai  { background:#27ae60; }
        .btn { padding:4px 12px; border-radius:6px; border:none; cursor:pointer; font-size:12px; font-weight:600; }
        .btn-blue { background:var(--light-blue); color:var(--blue); }
        .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; }
        .modal-overlay.show { display:flex; }
        .modal { background:white; border-radius:16px; padding:32px; width:100%; max-width:460px; }
        .modal h3 { margin-bottom:16px; }
        .form-group { margin-bottom:14px; }
        .form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:5px; }
        .form-group select, .form-group textarea { width:100%; padding:9px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px; }
        .modal-footer { display:flex; gap:8px; justify-content:flex-end; margin-top:14px; }
        .btn-save { background:var(--blue); color:white; padding:8px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
        .btn-cancel { background:var(--grey); color:var(--dark); padding:8px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
    </style>
</head>
<body>
<?php sidebarAdmin('admin_pendaftaran'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Kelola Pendaftaran</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Kelola Pendaftaran</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_admin.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Pendaftaran</a></li>
                </ul>
            </div>
        </div>

        <div class="filter-bar">
            <a href="admin_pendaftaran.php" class="filter-btn <?= !$filter ? 'active' : '' ?>">Semua</a>
            <a href="?status=Menunggu" class="filter-btn <?= $filter==='Menunggu' ? 'active' : '' ?>">Menunggu</a>
            <a href="?status=Diterima" class="filter-btn <?= $filter==='Diterima' ? 'active' : '' ?>">Diterima</a>
            <a href="?status=Ditolak"  class="filter-btn <?= $filter==='Ditolak'  ? 'active' : '' ?>">Ditolak</a>
            <a href="?status=Selesai"  class="filter-btn <?= $filter==='Selesai'  ? 'active' : '' ?>">Selesai</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>No</th><th>Siswa</th><th>NISN</th><th>Perusahaan</th>
                        <th>Pembimbing</th><th>Surat</th><th>Status</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data && $data->num_rows > 0): $no=1; ?>
                        <?php while ($row = $data->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap'] ?? $row['username']) ?></td>
                            <td><?= htmlspecialchars($row['nisn'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pembimbing'] ?? '-') ?></td>
                            <td>
                                <?php if ($row['surat_permohonan']): ?>
                                <a href="<?= htmlspecialchars($row['surat_permohonan']) ?>" target="_blank" style="color:var(--blue)"><i class='bx bxs-file'></i> Lihat</a>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td><span class="status-badge <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                            <td>
                                <button class="btn btn-blue" onclick="openModal(<?= $row['id'] ?>, '<?= $row['status'] ?>', `<?= addslashes($row['catatan'] ?? '') ?>`)">
                                    <i class='bx bxs-edit'></i> Update
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center;padding:20px;color:var(--dark-grey)">Belum ada data.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>

<div class="modal-overlay" id="modalUpdate">
    <div class="modal">
        <h3>Update Status Pendaftaran</h3>
        <form method="POST">
            <input type="hidden" name="id" id="m-id">
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="m-status">
                    <option value="Menunggu">Menunggu</option>
                    <option value="Diterima">Diterima</option>
                    <option value="Ditolak">Ditolak</option>
                    <option value="Selesai">Selesai</option>
                </select>
            </div>
            <div class="form-group">
                <label>Catatan</label>
                <textarea name="catatan" id="m-catatan" rows="3" placeholder="Catatan untuk siswa..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>
<script src="script.js"></script>
<script>
function openModal(id, status, catatan) {
    document.getElementById('m-id').value      = id;
    document.getElementById('m-status').value  = status;
    document.getElementById('m-catatan').value = catatan;
    document.getElementById('modalUpdate').classList.add('show');
}
function closeModal() { document.getElementById('modalUpdate').classList.remove('show'); }
</script>
</body>
</html>
