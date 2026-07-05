<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'];
    $username = trim($_POST['username']);
    $nama    = $_POST['nama_lengkap'];
    $nip     = $_POST['nip'];
    $no_telp = $_POST['no_telp'];
    $keahlian = $_POST['keahlian'];
    $kuota   = (int)$_POST['kuota'];

    if ($action === 'tambah') {
        $cek = $conn->prepare("SELECT id FROM user WHERE username=?");
        $cek->bind_param("s", $username);
        $cek->execute(); $cek->store_result();
        if ($cek->num_rows > 0) { $error = "Username sudah digunakan."; }
        else {
            $cek->close();
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO user (username,password,role) VALUES (?,'pembimbing'='pembimbing',?)");
            // fix insert
            $stmt = $conn->prepare("INSERT INTO user (username,password,role) VALUES (?,?,'pembimbing')");
            $stmt->bind_param("ss", $username, $pass);
            $stmt->execute();
            $uid = $conn->insert_id; $stmt->close();
            $stmt = $conn->prepare("INSERT INTO pembimbing (user_id,nama_lengkap,nip,no_telp,keahlian,kuota) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("issssi", $uid, $nama, $nip, $no_telp, $keahlian, $kuota);
            $stmt->execute(); $stmt->close();
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE pembimbing SET nama_lengkap=?,nip=?,no_telp=?,keahlian=?,kuota=? WHERE id=?");
        $stmt->bind_param("ssssi i", $nama, $nip, $no_telp, $keahlian, $kuota, $id);
        // fix
        $stmt = $conn->prepare("UPDATE pembimbing SET nama_lengkap=?,nip=?,no_telp=?,keahlian=?,kuota=? WHERE id=?");
        $stmt->bind_param("ssssii", $nama, $nip, $no_telp, $keahlian, $kuota, $id);
        $stmt->execute(); $stmt->close();
        if (!empty($_POST['password'])) {
            $uid  = $_POST['user_id'];
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE user SET password=? WHERE id=?");
            $stmt->bind_param("si", $pass, $uid);
            $stmt->execute(); $stmt->close();
        }
    }
    header("Location: admin_pembimbing.php"); exit();
}

if (isset($_GET['hapus'])) {
    $id  = $_GET['hapus'];
    $uid = $conn->query("SELECT user_id FROM pembimbing WHERE id=$id")->fetch_assoc()['user_id'];
    $conn->query("DELETE FROM user WHERE id=$uid");
    header("Location: admin_pembimbing.php"); exit();
}

$search = $_GET['search'] ?? '';
$whereSQL = $search ? "WHERE pb.nama_lengkap LIKE '%" . $conn->real_escape_string($search) . "%' OR pb.keahlian LIKE '%" . $conn->real_escape_string($search) . "%'" : '';

$list = $conn->query("SELECT pb.*, u.username,
    (SELECT COUNT(*) FROM pendaftaran WHERE pembimbing_id=pb.id AND status='Diterima') as terpakai
    FROM pembimbing pb JOIN user u ON pb.user_id=u.id $whereSQL ORDER BY pb.nama_lengkap");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pembimbing</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .table-card { background:var(--light); border-radius:16px; padding:24px; margin-top:24px; overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        th { padding-bottom:10px; font-size:12px; text-align:left; border-bottom:1px solid var(--grey); color:var(--dark-grey); }
        td { padding:10px 0; font-size:13px; border-bottom:1px solid var(--grey); }
        .btn-tambah { background:var(--blue); color:white; padding:8px 20px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; }
        .btn-edit  { background:var(--light-yellow); color:#856404; padding:4px 10px; border-radius:6px; border:none; cursor:pointer; font-size:12px; font-weight:600; }
        .btn-hapus { background:#fde8e8; color:var(--red); padding:4px 10px; border-radius:6px; border:none; cursor:pointer; font-size:12px; font-weight:600; margin-left:4px; text-decoration:none; }
        .head-bar  { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
        .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; }
        .modal-overlay.show { display:flex; }
        .modal { background:white; border-radius:16px; padding:32px; width:100%; max-width:480px; max-height:90vh; overflow-y:auto; }
        .modal h3 { margin-bottom:16px; }
        .form-group { margin-bottom:12px; }
        .form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:5px; }
        .form-group input { width:100%; padding:9px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px; }
        .modal-footer { display:flex; gap:8px; justify-content:flex-end; margin-top:14px; }
        .btn-save   { background:var(--blue); color:white; padding:8px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
        .btn-cancel { background:var(--grey); color:var(--dark); padding:8px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
        .kuota-bar  { font-size:12px; color:var(--dark-grey); }
    </style>
</head>
<body>
<?php sidebarAdmin('admin_pembimbing'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Kelola Pembimbing</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Kelola Pembimbing</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_admin.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Pembimbing</a></li>
                </ul>
            </div>
        </div>

        <div class="table-card">
            <div class="head-bar">
                <h3 style="font-size:15px;font-weight:700">Daftar Pembimbing (<?= $list ? $list->num_rows : 0 ?>)</h3>
                <div style="display:flex;gap:8px;align-items:center;">
                    <form method="GET" style="display:flex;gap:8px;">
                        <input type="text" name="search" placeholder="Cari nama atau keahlian..." value="<?= htmlspecialchars($search) ?>" style="padding:7px 12px;border:1px solid #ddd;border-radius:8px;font-size:13px;min-width:200px;">
                        <button type="submit" style="padding:7px 14px;background:var(--blue);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;">Cari</button>
                        <?php if($search): ?><a href="admin_pembimbing.php" style="padding:7px 12px;background:var(--grey);border-radius:8px;text-decoration:none;color:var(--dark);font-size:13px;">Reset</a><?php endif; ?>
                    </form>
                    <button class="btn-tambah" onclick="document.getElementById('modalTambah').classList.add('show')"><i class='bx bx-plus'></i> Tambah</button>
                </div>
            </div>
            <table>
                <thead><tr><th>No</th><th>Nama</th><th>NIP</th><th>Keahlian</th><th>Kuota</th><th>Username</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if ($list && $list->num_rows > 0): $no=1; ?>
                        <?php while ($row = $list->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                            <td><?= htmlspecialchars($row['nip'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['keahlian'] ?? '-') ?></td>
                            <td><span class="kuota-bar"><?= $row['terpakai'] ?>/<?= $row['kuota'] ?> siswa</span></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td>
                                <button class="btn-edit" onclick='openEdit(<?= json_encode($row) ?>)'>Edit</button>
                                <a href="?hapus=<?= $row['id'] ?>" class="btn-hapus" onclick="return confirm('Hapus pembimbing ini?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center;padding:20px;color:var(--dark-grey)">Belum ada data pembimbing.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>

<!-- MODAL TAMBAH -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal">
        <h3>Tambah Pembimbing</h3>
        <form method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="form-group"><label>Username *</label><input type="text" name="username" required></div>
            <div class="form-group"><label>Password *</label><input type="password" name="password" required></div>
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_lengkap"></div>
            <div class="form-group"><label>NIP</label><input type="text" name="nip"></div>
            <div class="form-group"><label>No. Telepon</label><input type="text" name="no_telp"></div>
            <div class="form-group"><label>Keahlian</label><input type="text" name="keahlian"></div>
            <div class="form-group"><label>Kuota Siswa</label><input type="number" name="kuota" value="10" min="1"></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalTambah').classList.remove('show')">Batal</button>
                <button type="submit" class="btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal">
        <h3>Edit Pembimbing</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="e-id">
            <input type="hidden" name="user_id" id="e-uid">
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" id="e-nama"></div>
            <div class="form-group"><label>NIP</label><input type="text" name="nip" id="e-nip"></div>
            <div class="form-group"><label>No. Telepon</label><input type="text" name="no_telp" id="e-telp"></div>
            <div class="form-group"><label>Keahlian</label><input type="text" name="keahlian" id="e-keahlian"></div>
            <div class="form-group"><label>Kuota Siswa</label><input type="number" name="kuota" id="e-kuota" min="1"></div>
            <div class="form-group"><label>Password Baru (kosongkan jika tidak diubah)</label><input type="password" name="password"></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="document.getElementById('modalEdit').classList.remove('show')">Batal</button>
                <button type="submit" class="btn-save">Update</button>
            </div>
        </form>
    </div>
</div>

<script src="script.js"></script>
<script>
function openEdit(d) {
    document.getElementById('e-id').value       = d.id;
    document.getElementById('e-uid').value      = d.user_id;
    document.getElementById('e-nama').value     = d.nama_lengkap || '';
    document.getElementById('e-nip').value      = d.nip || '';
    document.getElementById('e-telp').value     = d.no_telp || '';
    document.getElementById('e-keahlian').value = d.keahlian || '';
    document.getElementById('e-kuota').value    = d.kuota || 10;
    document.getElementById('modalEdit').classList.add('show');
}
</script>
</body>
</html>
