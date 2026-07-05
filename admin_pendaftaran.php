<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');
include('functions.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = $_POST['id'];
    $status  = $_POST['status'];
    $catatan = $_POST['catatan'];
    $allowed = ['Menunggu Verifikasi','Diterima','Ditolak','Sedang PKL','Menunggu Penilaian','Selesai'];
    
    if (in_array($status, $allowed)) {
        $stmt = $conn->prepare("SELECT status FROM pendaftaran WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $statusOrder = ['Menunggu Verifikasi'=>0, 'Diterima'=>1, 'Ditolak'=>2, 'Sedang PKL'=>3, 'Menunggu Penilaian'=>4, 'Selesai'=>5];
        $currentOrder = $statusOrder[$current['status']] ?? -1;
        $newOrder = $statusOrder[$status] ?? -1;
        
        $canUpdate = false;
        if ($status === 'Ditolak' && $current['status'] === 'Menunggu Verifikasi') $canUpdate = true;
        elseif ($newOrder > $currentOrder && $current['status'] !== 'Ditolak') $canUpdate = true;
        
        if ($canUpdate) {
            $stmt = $conn->prepare("UPDATE pendaftaran SET status=?, catatan=? WHERE id=?");
            $stmt->bind_param("ssi", $status, $catatan, $id);
            $stmt->execute();
            $stmt->close();
            logActivity($conn, $_SESSION['user_id'], 'Update Status Pendaftaran', 'pendaftaran', $id, "Status diubah dari {$current['status']} menjadi: $status");
        }
    }
    header("Location: admin_pendaftaran.php");
    exit();
}

// Quick Action
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'terima') {
        $conn->query("UPDATE pendaftaran SET status='Diterima' WHERE id=$id");
        logActivity($conn, $_SESSION['user_id'], 'Terima Pendaftaran', 'pendaftaran', $id, "Pendaftaran diterima");
    } elseif ($action === 'tolak') {
        $conn->query("UPDATE pendaftaran SET status='Ditolak' WHERE id=$id");
        logActivity($conn, $_SESSION['user_id'], 'Tolak Pendaftaran', 'pendaftaran', $id, "Pendaftaran ditolak");
    }
    header("Location: admin_pendaftaran.php");
    exit();
}

// Filters & Search
$filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page   = $_GET['page'] ?? 1;
$perPage = 15;

$where = [];
if ($filter) $where[] = "p.status = '" . $conn->real_escape_string($filter) . "'";
if ($search) {
    $s = $conn->real_escape_string($search);
    $where[] = "(s.nama_lengkap LIKE '%$s%' OR s.nisn LIKE '%$s%' OR pr.nama_perusahaan LIKE '%$s%')";
}
$whereSQL = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT p.*, u.username, s.nama_lengkap, s.nisn, s.kelas, pr.nama_perusahaan, pb.nama_lengkap as nama_pembimbing
    FROM pendaftaran p
    JOIN user u ON p.user_id = u.id
    JOIN perusahaan pr ON p.perusahaan_id = pr.id
    LEFT JOIN siswa s ON s.user_id = p.user_id
    LEFT JOIN pembimbing pb ON p.pembimbing_id = pb.id
    $whereSQL
    ORDER BY p.created_at DESC";

$result = paginate($conn, $query, $page, $perPage);
$data = $result['data'];
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
        .toolbar { display: flex; gap: 12px; align-items: center; margin-top: 20px; flex-wrap: wrap; }
        .search-box { flex: 1; min-width: 250px; position: relative; }
        .search-box input { width: 100%; padding: 10px 40px 10px 40px; border: 2px solid var(--border); border-radius: 10px; font-size: 14px; }
        .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--dark-grey); }
        .search-box .clear-search { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: var(--dark-grey); cursor: pointer; display: none; }
        .search-box input:not(:placeholder-shown) ~ .clear-search { display: block; }
        .filter-bar { display:flex; gap:8px; flex-wrap:wrap; }
        .filter-btn { padding:8px 18px; border-radius:20px; border:none; cursor:pointer; font-size:13px; font-weight:600; background:var(--grey); color:var(--dark); text-decoration:none; transition: all 0.3s; }
        .filter-btn:hover { background: var(--light-blue); color: var(--blue); }
        .filter-btn.active { background:var(--blue); color:white; }
        .table-card { background:var(--white); border-radius:16px; padding:24px; margin-top:20px; overflow-x:auto; border: 1px solid var(--border); }
        table { width:100%; border-collapse:collapse; min-width: 900px; }
        th { padding: 12px 8px; font-size:12px; text-align:left; border-bottom:2px solid var(--border); color:var(--dark-grey); text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding:12px 8px; font-size:13px; border-bottom:1px solid var(--border); vertical-align: middle; }
        tr:hover td { background:var(--light); }
        .status-badge { padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; white-space: nowrap; }
        .status-badge.menunggu-verifikasi { background:var(--light-orange); color:var(--orange); }
        .status-badge.diterima { background:var(--light-blue); color:var(--blue); }
        .status-badge.ditolak  { background:var(--light-red); color:var(--red); }
        .status-badge.sedang-pkl { background:var(--light-green); color:var(--green); }
        .status-badge.menunggu-penilaian { background:var(--light-yellow); color:var(--yellow); }
        .status-badge.selesai  { background:#D1FAE5; color:#065F46; }
        .btn { padding:6px 12px; border-radius:8px; border:none; cursor:pointer; font-size:12px; font-weight:600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: all 0.3s; }
        .btn:hover { transform: translateY(-1px); }
        .btn-blue { background:var(--light-blue); color:var(--blue); }
        .btn-green { background:var(--light-green); color:var(--green); }
        .btn-red { background:var(--light-red); color:var(--red); }
        .btn-view { background:var(--grey); color:var(--dark); }
        .action-buttons { display: flex; gap: 6px; flex-wrap: wrap; }
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center; backdrop-filter: blur(2px); }
        .modal-overlay.show { display:flex; }
        .modal { background:white; border-radius:16px; padding:32px; width:100%; max-width:520px; max-height: 90vh; overflow-y: auto; animation: modalIn 0.3s ease; }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .modal h3 { margin-bottom:20px; font-size: 18px; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color: var(--dark); }
        .form-group select, .form-group textarea { width:100%; padding:10px 14px; border:2px solid var(--border); border-radius:8px; font-size:13px; font-family: 'Poppins', sans-serif; }
        .form-group select:focus, .form-group textarea:focus { border-color: var(--blue); outline: none; }
        .modal-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:20px; }
        .btn-save { background:var(--blue); color:white; padding:10px 24px; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size: 14px; }
        .btn-save:hover { background: var(--blue-dark); }
        .btn-cancel { background:var(--grey); color:var(--dark); padding:10px 24px; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size: 14px; }
        .pagination { display: flex; gap: 8px; justify-content: center; margin-top: 20px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; border: 1px solid var(--border); }
        .pagination a { background: white; color: var(--dark); }
        .pagination a:hover { background: var(--light-blue); color: var(--blue); border-color: var(--blue); }
        .pagination span.active { background: var(--blue); color: white; border-color: var(--blue); }
        .pagination span.disabled { background: var(--grey); color: var(--dark-grey); cursor: not-allowed; }
        .preview-modal .modal { max-width: 900px; }
        .preview-frame { width: 100%; height: 70vh; border: none; border-radius: 8px; }
        .export-btn { background: var(--green); color: white; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 13px; font-weight: 600; }
        .export-btn:hover { opacity: 0.9; }
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

        <!-- Toolbar: Search & Filter -->
        <div class="toolbar">
            <div class="search-box">
                <i class='bx bx-search'></i>
                <input 
                    type="text" 
                    id="searchInput" 
                    placeholder="Cari siswa, NISN, atau perusahaan..." 
                    value="<?= htmlspecialchars($search) ?>"
                    onkeypress="if(event.key==='Enter') doSearch()"
                >
                <i class='bx bx-x clear-search' onclick="clearSearch()"></i>
            </div>
            <button class="export-btn" onclick="location.href='export_pendaftaran.php?<?= http_build_query($_GET) ?>'">
                <i class='bx bxs-download'></i> Export Excel
            </button>
        </div>

        <div class="filter-bar">
            <a href="admin_pendaftaran.php" class="filter-btn <?= !$filter ? 'active' : '' ?>">Semua</a>
            <a href="?status=Menunggu+Verifikasi" class="filter-btn <?= $filter==='Menunggu Verifikasi' ? 'active' : '' ?>">Menunggu Verifikasi</a>
            <a href="?status=Diterima" class="filter-btn <?= $filter==='Diterima' ? 'active' : '' ?>">Diterima</a>
            <a href="?status=Sedang+PKL" class="filter-btn <?= $filter==='Sedang PKL' ? 'active' : '' ?>">Sedang PKL</a>
            <a href="?status=Menunggu+Penilaian" class="filter-btn <?= $filter==='Menunggu Penilaian' ? 'active' : '' ?>">Menunggu Penilaian</a>
            <a href="?status=Ditolak"  class="filter-btn <?= $filter==='Ditolak'  ? 'active' : '' ?>">Ditolak</a>
            <a href="?status=Selesai"  class="filter-btn <?= $filter==='Selesai'  ? 'active' : '' ?>">Selesai</a>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>No</th><th>Siswa</th><th>NISN</th><th>Perusahaan</th>
                        <th>Pembimbing</th><th>Tanggal</th><th>Status</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($data && $data->num_rows > 0): 
                        $no = ($page - 1) * $perPage + 1; 
                    ?>
                        <?php while ($row = $data->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($row['nama_lengkap'] ?? $row['username']) ?></strong></td>
                            <td><?= htmlspecialchars($row['nisn'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                            <td><?= htmlspecialchars($row['nama_pembimbing'] ?? '-') ?></td>
                            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td><span class="status-badge <?= strtolower(str_replace(' ', '-', $row['status'])) ?>"><?= $row['status'] ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($row['status'] === 'Menunggu Verifikasi'): ?>
                                        <a href="?action=terima&id=<?= $row['id'] ?>" class="btn btn-green" onclick="return confirm('Terima pendaftaran ini?')" title="Terima">
                                            <i class='bx bx-check'></i> Terima
                                        </a>
                                        <a href="?action=tolak&id=<?= $row['id'] ?>" class="btn btn-red" onclick="return confirm('Tolak pendaftaran ini?')" title="Tolak">
                                            <i class='bx bx-x'></i> Tolak
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($row['surat_permohonan']): ?>
                                        <button class="btn btn-view" onclick="previewDoc('<?= htmlspecialchars($row['surat_permohonan']) ?>')" title="Lihat Dokumen">
                                            <i class='bx bxs-file'></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-blue" onclick="openModal(<?= $row['id'] ?>, '<?= $row['status'] ?>', `<?= addslashes($row['catatan'] ?? '') ?>`)" title="Update Status">
                                        <i class='bx bxs-edit'></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--dark-grey)">
                            <i class='bx bxs-inbox' style="font-size:48px;opacity:0.3;"></i><br>
                            Tidak ada data ditemukan.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($result['pages'] > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $filter ? '&status='.$filter : '' ?><?= $search ? '&search='.$search : '' ?>">
                        <i class='bx bx-chevron-left'></i> Prev
                    </a>
                <?php else: ?>
                    <span class="disabled"><i class='bx bx-chevron-left'></i> Prev</span>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?><?= $filter ? '&status='.$filter : '' ?><?= $search ? '&search='.$search : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $result['pages']): ?>
                    <a href="?page=<?= $page + 1 ?><?= $filter ? '&status='.$filter : '' ?><?= $search ? '&search='.$search : '' ?>">
                        Next <i class='bx bx-chevron-right'></i>
                    </a>
                <?php else: ?>
                    <span class="disabled">Next <i class='bx bx-chevron-right'></i></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</section>

<!-- Modal Update Status -->
<div class="modal-overlay" id="modalUpdate">
    <div class="modal">
        <h3><i class='bx bxs-edit'></i> Update Status Pendaftaran</h3>
        <form method="POST">
            <input type="hidden" name="id" id="m-id">
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="m-status">
                    <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
                    <option value="Diterima">Diterima</option>
                    <option value="Ditolak">Ditolak</option>
                    <option value="Sedang PKL">Sedang PKL</option>
                    <option value="Menunggu Penilaian">Menunggu Penilaian</option>
                    <option value="Selesai">Selesai</option>
                </select>
            </div>
            <div class="form-group">
                <label>Catatan</label>
                <textarea name="catatan" id="m-catatan" rows="3" placeholder="Catatan untuk siswa (opsional)..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-save">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Preview Document -->
<div class="modal-overlay preview-modal" id="modalPreview">
    <div class="modal">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;"><i class='bx bxs-file'></i> Preview Dokumen</h3>
            <button onclick="closePreview()" style="background:none;border:none;font-size:24px;cursor:pointer;color:var(--dark-grey);"><i class='bx bx-x'></i></button>
        </div>
        <iframe id="previewFrame" class="preview-frame"></iframe>
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

function closeModal() { 
    document.getElementById('modalUpdate').classList.remove('show'); 
}

function previewDoc(url) {
    document.getElementById('previewFrame').src = url;
    document.getElementById('modalPreview').classList.add('show');
}

function closePreview() {
    document.getElementById('modalPreview').classList.remove('show');
    document.getElementById('previewFrame').src = '';
}

function doSearch() {
    const query = document.getElementById('searchInput').value;
    const params = new URLSearchParams(window.location.search);
    if (query) {
        params.set('search', query);
    } else {
        params.delete('search');
    }
    params.delete('page');
    window.location.href = '?' + params.toString();
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    const params = new URLSearchParams(window.location.search);
    params.delete('search');
    params.delete('page');
    window.location.href = '?' + params.toString();
}

// Close modal on outside click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.classList.remove('show');
        }
    });
});
</script>
</body>
</html>
