<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul    = $_POST['judul'];
    $isi      = $_POST['isi'];
    $kategori = in_array($_POST['kategori'], ['Semua','Siswa','Pembimbing']) ? $_POST['kategori'] : 'Semua';
    $tgl      = !empty($_POST['tanggal_berlaku']) ? $_POST['tanggal_berlaku'] : null;

    if (!empty($_POST['id'])) {
        $id   = $_POST['id'];
        $stmt = $conn->prepare("UPDATE pengumuman SET judul=?, isi=?, kategori=?, tanggal_berlaku=? WHERE id=?");
        $stmt->bind_param("ssssi", $judul, $isi, $kategori, $tgl, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO pengumuman (judul, isi, kategori, tanggal_berlaku, created_by) VALUES (?,?,?,?,?)");
        $stmt->bind_param("ssssi", $judul, $isi, $kategori, $tgl, $user_id);
    }
    $stmt->execute(); $stmt->close();
    header("Location: admin_pengumuman.php"); exit();
}

if (isset($_GET['hapus'])) {
    $id   = (int)$_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM pengumuman WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute(); $stmt->close();
    header("Location: admin_pengumuman.php"); exit();
}

$filterKat = $_GET['kategori'] ?? '';
$whereKat  = $filterKat ? "WHERE kategori = '" . $conn->real_escape_string($filterKat) . "'" : '';
$list = $conn->query("SELECT * FROM pengumuman $whereKat ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengumuman</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .layout { display:flex; gap:24px; margin-top:24px; flex-wrap:wrap; }
        .form-card { background:var(--light); border-radius:16px; padding:24px; flex:1; min-width:280px; max-width:380px; }
        .list-card { background:var(--light); border-radius:16px; padding:24px; flex:2; min-width:300px; }
        .form-group { margin-bottom:14px; }
        .form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:5px; color:var(--dark); }
        .form-group input, .form-group textarea { width:100%; padding:9px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px; outline:none; }
        .form-group input:focus, .form-group textarea:focus { border-color:var(--blue); }
        .btn-submit { background:var(--blue); color:white; border:none; padding:9px 24px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; width:100%; }
        .item { border-bottom:1px solid var(--grey); padding:14px 0; }
        .item:last-child { border-bottom:none; }
        .item h4 { font-size:14px; font-weight:700; color:var(--dark); margin-bottom:4px; }
        .item p  { font-size:13px; color:var(--dark); margin-bottom:6px; white-space:pre-wrap; }
        .item small { font-size:11px; color:var(--dark-grey); }
        .item-actions { display:flex; gap:8px; margin-top:8px; }
        .btn-edit  { background:var(--light-yellow); color:#856404; padding:4px 12px; border-radius:6px; border:none; cursor:pointer; font-size:12px; font-weight:600; }
        .btn-hapus { background:#fde8e8; color:var(--red); padding:4px 12px; border-radius:6px; border:none; cursor:pointer; font-size:12px; font-weight:600; text-decoration:none; }
        .filter-btn { padding:5px 14px; border-radius:16px; border:1px solid var(--grey); font-size:12px; font-weight:600; text-decoration:none; color:var(--dark); background:var(--grey); }
        .filter-btn.active { background:var(--blue); color:white; border-color:var(--blue); }
        .kat-badge { padding:3px 10px; border-radius:12px; font-size:11px; font-weight:700; }
        .kat-badge.kat-semua { background:var(--light-blue); color:var(--blue); }
        .kat-badge.kat-siswa { background:var(--light-green); color:var(--green); }
        .kat-badge.kat-pembimbing { background:var(--light-orange); color:var(--orange); }
    </style>
</head>
<body>
<?php sidebarAdmin('admin_pengumuman'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Kelola Pengumuman</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Pengumuman</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_admin.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Pengumuman</a></li>
                </ul>
            </div>
        </div>

        <div class="layout">
            <div class="form-card">
                <h3 id="form-title">Buat Pengumuman</h3>
                <form method="POST" id="form-pengumuman">
                    <input type="hidden" name="id" id="p-id">
                    <div class="form-group"><label>Judul</label><input type="text" name="judul" id="p-judul" required></div>
                    <div class="form-group"><label>Isi</label><textarea name="isi" id="p-isi" rows="5" required></textarea></div>
                    <div class="form-group">
                        <label>Kategori Penerima</label>
                        <select name="kategori" id="p-kategori" style="width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:8px;font-size:13px;">
                            <option value="Semua">Semua Pengguna</option>
                            <option value="Siswa">Siswa Saja</option>
                            <option value="Pembimbing">Pembimbing Saja</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Tanggal Berlaku (opsional)</label><input type="date" name="tanggal_berlaku" id="p-tgl"></div>
                    <button type="submit" class="btn-submit">Simpan</button>
                    <button type="button" style="width:100%;margin-top:8px;padding:8px;background:var(--grey);border:none;border-radius:8px;cursor:pointer;font-size:13px" onclick="resetForm()">Reset</button>
                </form>
            </div>

            <div class="list-card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
                    <h3 style="margin-bottom:0;">Daftar Pengumuman</h3>
                    <div style="display:flex;gap:8px;">
                        <a href="?" class="filter-btn <?= !$filterKat ? 'active' : '' ?>">Semua</a>
                        <a href="?kategori=Siswa" class="filter-btn <?= $filterKat==='Siswa' ? 'active' : '' ?>">Siswa</a>
                        <a href="?kategori=Pembimbing" class="filter-btn <?= $filterKat==='Pembimbing' ? 'active' : '' ?>">Pembimbing</a>
                    </div>
                </div>
                <?php if ($list && $list->num_rows > 0): ?>
                    <?php while ($row = $list->fetch_assoc()): ?>
                    <div class="item">
                        <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px;flex-wrap:wrap;">
                            <span class="kat-badge kat-<?= strtolower($row['kategori']) ?>"><?= $row['kategori'] ?></span>
                            <?php if ($row['tanggal_berlaku']): ?>
                            <span style="font-size:11px;color:var(--dark-grey);"><i class='bx bx-calendar'></i> Berlaku: <?= date('d M Y', strtotime($row['tanggal_berlaku'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <h4><?= htmlspecialchars($row['judul']) ?></h4>
                        <p><?= htmlspecialchars($row['isi']) ?></p>
                        <small><?= date('d M Y H:i', strtotime($row['created_at'])) ?></small>
                        <div class="item-actions">
                            <button class="btn-edit" onclick='editPengumuman(<?= json_encode($row) ?>)'>Edit</button>
                            <a href="?hapus=<?= $row['id'] ?>" class="btn-hapus" onclick="return confirm('Hapus pengumuman ini?')">Hapus</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:var(--dark-grey);font-size:14px">Belum ada pengumuman.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</section>
<script src="script.js"></script>
<script>
function editPengumuman(d) {
    document.getElementById('p-id').value       = d.id;
    document.getElementById('p-judul').value    = d.judul;
    document.getElementById('p-isi').value      = d.isi;
    document.getElementById('p-kategori').value = d.kategori || 'Semua';
    document.getElementById('p-tgl').value      = d.tanggal_berlaku || '';
    document.getElementById('form-title').textContent = 'Edit Pengumuman';
    window.scrollTo(0,0);
}
function resetForm() {
    document.getElementById('p-id').value       = '';
    document.getElementById('p-judul').value    = '';
    document.getElementById('p-isi').value      = '';
    document.getElementById('p-kategori').value = 'Semua';
    document.getElementById('p-tgl').value      = '';
    document.getElementById('form-title').textContent = 'Buat Pengumuman';
}
</script>
</body>
</html>
