<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

// Tambah siswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'tambah') {
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $cek = $conn->prepare("SELECT id FROM user WHERE username=?");
    $cek->bind_param("s", $username);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
        $error = "Username sudah digunakan.";
    } else {
        $cek->close();
        $stmt = $conn->prepare("INSERT INTO user (username, password, role) VALUES (?, ?, 'siswa')");
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $newId = $conn->insert_id;
        $stmt->close();

        // Insert profil siswa jika ada
        if (!empty($_POST['nama_lengkap'])) {
            $nama   = $_POST['nama_lengkap'];
            $nisn   = $_POST['nisn'];
            $kelas  = $_POST['kelas'];
            $jurusan = $_POST['jurusan'];
            $no_telp = $_POST['no_telp'];
            $alamat  = $_POST['alamat'];
            $stmt2 = $conn->prepare("INSERT INTO siswa (user_id, nama_lengkap, nisn, kelas, jurusan, no_telp, alamat) VALUES (?,?,?,?,?,?,?)");
            $stmt2->bind_param("issssss", $newId, $nama, $nisn, $kelas, $jurusan, $no_telp, $alamat);
            $stmt2->execute();
            $stmt2->close();
        }
    }
    header("Location: admin_siswa.php");
    exit();
}

// Edit siswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'edit') {
    $id      = $_POST['id'];
    $username = trim($_POST['username']);

    $stmt = $conn->prepare("UPDATE user SET username=? WHERE id=?");
    $stmt->bind_param("si", $username, $id);
    $stmt->execute();
    $stmt->close();

    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE user SET password=? WHERE id=?");
        $stmt->bind_param("si", $password, $id);
        $stmt->execute();
        $stmt->close();
    }

    $nama    = $_POST['nama_lengkap'];
    $nisn    = $_POST['nisn'];
    $kelas   = $_POST['kelas'];
    $jurusan = $_POST['jurusan'];
    $no_telp = $_POST['no_telp'];
    $alamat  = $_POST['alamat'];

    $cekSiswa = $conn->query("SELECT id FROM siswa WHERE user_id=$id")->num_rows;
    if ($cekSiswa > 0) {
        $stmt = $conn->prepare("UPDATE siswa SET nama_lengkap=?, nisn=?, kelas=?, jurusan=?, no_telp=?, alamat=? WHERE user_id=?");
        $stmt->bind_param("ssssssi", $nama, $nisn, $kelas, $jurusan, $no_telp, $alamat, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO siswa (user_id, nama_lengkap, nisn, kelas, jurusan, no_telp, alamat) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss", $id, $nama, $nisn, $kelas, $jurusan, $no_telp, $alamat);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: admin_siswa.php");
    exit();
}

// Hapus
if (isset($_GET['hapus'])) {
    $id   = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM user WHERE id=? AND role='siswa'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_siswa.php");
    exit();
}

$search = $_GET['search'] ?? '';
$whereSQL = '';
if ($search) {
    $s = $conn->real_escape_string($search);
    $whereSQL = "WHERE u.role='siswa' AND (u.username LIKE '%$s%' OR s.nama_lengkap LIKE '%$s%' OR s.nisn LIKE '%$s%' OR s.kelas LIKE '%$s%')";
} else {
    $whereSQL = "WHERE u.role='siswa'";
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$totalQ  = $conn->query("SELECT COUNT(*) as t FROM user u LEFT JOIN siswa s ON s.user_id = u.id $whereSQL");
$total   = $totalQ ? (int)$totalQ->fetch_assoc()['t'] : 0;
$pages   = ceil($total / $perPage);

$siswaList = $conn->query("SELECT u.*, s.nama_lengkap, s.nisn, s.kelas, s.jurusan, s.no_telp, s.alamat FROM user u LEFT JOIN siswa s ON s.user_id = u.id $whereSQL ORDER BY u.id DESC LIMIT $perPage OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Siswa</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .table-wrap { background: var(--light); border-radius: 20px; padding: 24px; margin-top: 24px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { padding-bottom: 12px; font-size: 13px; text-align: left; border-bottom: 1px solid var(--grey); }
        td { padding: 10px 0; font-size: 13px; }
        tr:hover td { background: var(--grey); }
        .btn-tambah { background: var(--blue); color: white; padding: 8px 20px; border-radius: 8px; border: none; cursor: pointer; font-size: 14px; font-weight: 600; }
        .btn-edit  { background: var(--light-yellow); color: #856404; padding: 4px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 12px; font-weight: 600; }
        .btn-hapus { background: #fde8e8; color: var(--red); padding: 4px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 12px; font-weight: 600; margin-left: 4px; }
        .modal-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; }
        .modal-overlay.show { display:flex; }
        .modal { background:white; border-radius:16px; padding:32px; width:100%; max-width:500px; max-height:90vh; overflow-y:auto; }
        .modal h3 { margin-bottom:20px; font-size:18px; }
        .form-group { margin-bottom:12px; }
        .form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:5px; }
        .form-group input, .form-group textarea { width:100%; padding:9px 12px; border:1px solid #ddd; border-radius:8px; font-size:13px; outline:none; }
        .modal-footer { display:flex; gap:8px; justify-content:flex-end; margin-top:16px; }
        .btn-save   { background:var(--blue); color:white; padding:8px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
        .btn-cancel { background:var(--grey); color:var(--dark); padding:8px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; }
        .head-bar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
        h3 { font-size:16px; font-weight:700; color:var(--dark); }
    </style>
</head>
<body>
<?php sidebarAdmin('admin_siswa'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Kelola Siswa</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Kelola Siswa</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_admin.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Siswa</a></li>
                </ul>
            </div>
        </div>

        <div class="table-wrap">
            <div class="head-bar">
                <h3>Daftar Siswa (<?= $total ?>)</h3>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <form method="GET" style="display:flex;gap:8px;">
                        <input type="text" name="search" placeholder="Cari nama, NISN, kelas..." value="<?= htmlspecialchars($search) ?>" style="padding:8px 12px;border:1px solid var(--grey);border-radius:8px;font-size:13px;min-width:220px;">
                        <button type="submit" style="padding:8px 16px;background:var(--blue);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;">Cari</button>
                        <?php if($search): ?><a href="admin_siswa.php" style="padding:8px 12px;background:var(--grey);border-radius:8px;font-size:13px;text-decoration:none;color:var(--dark);">Reset</a><?php endif; ?>
                    </form>
                    <button class="btn-tambah" onclick="openTambah()"><i class='bx bx-plus'></i> Tambah Siswa</button>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>NISN</th>
                        <th>Kelas</th>
                        <th>Jurusan</th>
                        <th>No. Telp</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($siswaList && $siswaList->num_rows > 0): $no = 1; ?>
                        <?php while ($row = $siswaList->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['nama_lengkap'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['nisn'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['kelas'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['jurusan'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['no_telp'] ?? '-') ?></td>
                            <td>
                                <button class="btn-edit" onclick='openEdit(<?= json_encode($row) ?>)'><i class='bx bxs-edit'></i> Edit</button>
                                <a href="?hapus=<?= $row['id'] ?>" class="btn-hapus" onclick="return confirm('Hapus siswa ini?')"><i class='bx bxs-trash'></i> Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center;padding:20px">Belum ada data siswa.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($pages > 1): ?>
            <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;flex-wrap:wrap;">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" style="padding:6px 14px;border:1px solid var(--grey);border-radius:8px;text-decoration:none;color:var(--dark);font-size:13px;">← Prev</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" style="padding:6px 14px;border:1px solid <?= $i==$page ? 'var(--blue)' : 'var(--grey)' ?>;background:<?= $i==$page ? 'var(--blue)' : 'white' ?>;color:<?= $i==$page ? 'white' : 'var(--dark)' ?>;border-radius:8px;text-decoration:none;font-size:13px;"><?= $i ?></a>
                <?php endfor; ?>
                <?php if ($page < $pages): ?>
                    <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" style="padding:6px 14px;border:1px solid var(--grey);border-radius:8px;text-decoration:none;color:var(--dark);font-size:13px;">Next →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</section>

<!-- MODAL TAMBAH -->
<div class="modal-overlay" id="modalTambah">
    <div class="modal">
        <h3>Tambah Siswa</h3>
        <form method="POST">
            <input type="hidden" name="action" value="tambah">
            <div class="form-group"><label>Username *</label><input type="text" name="username" required></div>
            <div class="form-group"><label>Password *</label><input type="password" name="password" required></div>
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_lengkap"></div>
            <div class="form-group"><label>NISN</label><input type="text" name="nisn"></div>
            <div class="form-group"><label>Kelas</label><input type="text" name="kelas"></div>
            <div class="form-group"><label>Jurusan</label><input type="text" name="jurusan"></div>
            <div class="form-group"><label>No. Telepon</label><input type="text" name="no_telp"></div>
            <div class="form-group"><label>Alamat</label><textarea name="alamat" rows="2"></textarea></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalTambah')">Batal</button>
                <button type="submit" class="btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal">
        <h3>Edit Siswa</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="form-group"><label>Username *</label><input type="text" name="username" id="edit-username" required></div>
            <div class="form-group"><label>Password Baru (kosongkan jika tidak diubah)</label><input type="password" name="password"></div>
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" id="edit-nama"></div>
            <div class="form-group"><label>NISN</label><input type="text" name="nisn" id="edit-nisn"></div>
            <div class="form-group"><label>Kelas</label><input type="text" name="kelas" id="edit-kelas"></div>
            <div class="form-group"><label>Jurusan</label><input type="text" name="jurusan" id="edit-jurusan"></div>
            <div class="form-group"><label>No. Telepon</label><input type="text" name="no_telp" id="edit-notelp"></div>
            <div class="form-group"><label>Alamat</label><textarea name="alamat" id="edit-alamat" rows="2"></textarea></div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modalEdit')">Batal</button>
                <button type="submit" class="btn-save">Update</button>
            </div>
        </form>
    </div>
</div>

<script src="script.js"></script>
<script>
function openTambah() { document.getElementById('modalTambah').classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
function openEdit(data) {
    document.getElementById('edit-id').value       = data.id;
    document.getElementById('edit-username').value = data.username;
    document.getElementById('edit-nama').value     = data.nama_lengkap || '';
    document.getElementById('edit-nisn').value     = data.nisn || '';
    document.getElementById('edit-kelas').value    = data.kelas || '';
    document.getElementById('edit-jurusan').value  = data.jurusan || '';
    document.getElementById('edit-notelp').value   = data.no_telp || '';
    document.getElementById('edit-alamat').value   = data.alamat || '';
    document.getElementById('modalEdit').classList.add('show');
}
</script>
</body>
</html>
