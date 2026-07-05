<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$pesan = '';

// Tambah / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = $_POST['nama_perusahaan'];
    $alamat  = $_POST['alamat'];
    $bidang  = $_POST['bidang_usaha'];
    $kuota   = $_POST['kuota'];
    $no_telp = $_POST['no_telp'];
    $email   = $_POST['email'];

    if (!empty($_POST['id'])) {
        $id   = $_POST['id'];
        $stmt = $conn->prepare("UPDATE perusahaan SET nama_perusahaan=?, alamat=?, bidang_usaha=?, kuota=?, no_telp=?, email=? WHERE id=?");
        $stmt->bind_param("sssissi", $nama, $alamat, $bidang, $kuota, $no_telp, $email, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO perusahaan (nama_perusahaan, alamat, bidang_usaha, kuota, no_telp, email) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("sssiss", $nama, $alamat, $bidang, $kuota, $no_telp, $email);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: admin_perusahaan.php");
    exit();
}

// Hapus
if (isset($_GET['hapus'])) {
    $id   = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM perusahaan WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_perusahaan.php");
    exit();
}

// Edit - ambil data
$editData = null;
if (isset($_GET['edit'])) {
    $id   = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM perusahaan WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$search     = $_GET['search'] ?? '';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 10;
$offset     = ($page - 1) * $perPage;
$whereSQL   = $search ? "WHERE nama_perusahaan LIKE '%" . $conn->real_escape_string($search) . "%' OR bidang_usaha LIKE '%" . $conn->real_escape_string($search) . "%'" : '';
$totalQ     = $conn->query("SELECT COUNT(*) as t FROM perusahaan $whereSQL");
$total      = $totalQ ? (int)$totalQ->fetch_assoc()['t'] : 0;
$pages      = ceil($total / $perPage);
$perusahaan = $conn->query("SELECT * FROM perusahaan $whereSQL ORDER BY nama_perusahaan LIMIT $perPage OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Perusahaan</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .layout { display: flex; gap: 24px; margin-top: 24px; flex-wrap: wrap; }
        .form-card { background: var(--light); border-radius: 20px; padding: 24px; flex: 1; min-width: 280px; max-width: 380px; }
        .table-wrap { background: var(--light); border-radius: 20px; padding: 24px; flex: 2; min-width: 300px; overflow-x: auto; }
        .form-group { margin-bottom: 14px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; }
        .form-group input, .form-group textarea {
            width: 100%; padding: 9px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; outline: none;
        }
        .form-group input:focus { border-color: var(--blue); }
        .btn-submit { background: var(--blue); color: white; border: none; padding: 9px 24px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; }
        table { width: 100%; border-collapse: collapse; }
        th { padding-bottom: 12px; font-size: 13px; text-align: left; border-bottom: 1px solid var(--grey); }
        td { padding: 10px 0; font-size: 13px; }
        tr:hover td { background: var(--grey); }
        .btn-edit { background: var(--light-yellow); color: #856404; padding: 4px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 12px; font-weight: 600; text-decoration: none; }
        .btn-hapus { background: #fde8e8; color: var(--red); padding: 4px 12px; border-radius: 6px; border: none; cursor: pointer; font-size: 12px; font-weight: 600; text-decoration: none; margin-left: 4px; }
        h3 { font-size: 16px; font-weight: 700; margin-bottom: 16px; color: var(--dark); }
    </style>
</head>
<body>
<?php sidebarAdmin('admin_perusahaan'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Kelola Perusahaan</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Kelola Perusahaan</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_admin.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Perusahaan</a></li>
                </ul>
            </div>
        </div>

        <div class="layout">
            <!-- FORM -->
            <div class="form-card">
                <h3><?= $editData ? 'Edit Perusahaan' : 'Tambah Perusahaan' ?></h3>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
                    <div class="form-group">
                        <label>Nama Perusahaan</label>
                        <input type="text" name="nama_perusahaan" value="<?= htmlspecialchars($editData['nama_perusahaan'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea name="alamat" rows="2"><?= htmlspecialchars($editData['alamat'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Bidang Usaha</label>
                        <input type="text" name="bidang_usaha" value="<?= htmlspecialchars($editData['bidang_usaha'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Kuota</label>
                        <input type="number" name="kuota" value="<?= $editData['kuota'] ?? 0 ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="text" name="no_telp" value="<?= htmlspecialchars($editData['no_telp'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($editData['email'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn-submit"><?= $editData ? 'Update' : 'Tambah' ?></button>
                    <?php if ($editData): ?>
                    <a href="admin_perusahaan.php" style="display:block;text-align:center;margin-top:8px;font-size:13px;color:var(--dark-grey)">Batal Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- TABLE -->
            <div class="table-wrap">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px;">
                    <h3>Daftar Perusahaan (<?= $total ?>)</h3>
                    <form method="GET" style="display:flex;gap:8px;">
                        <input type="text" name="search" placeholder="Cari nama atau bidang..." value="<?= htmlspecialchars($search) ?>" style="padding:7px 12px;border:1px solid #ddd;border-radius:8px;font-size:13px;min-width:200px;">
                        <button type="submit" style="padding:7px 16px;background:var(--blue);color:white;border:none;border-radius:8px;cursor:pointer;font-size:13px;">Cari</button>
                        <?php if($search): ?><a href="admin_perusahaan.php" style="padding:7px 12px;background:var(--grey);border-radius:8px;text-decoration:none;color:var(--dark);font-size:13px;">Reset</a><?php endif; ?>
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Perusahaan</th>
                            <th>Bidang</th>
                            <th>Kuota</th>
                            <th>No. Telp</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($perusahaan && $perusahaan->num_rows > 0): $no = 1; ?>
                            <?php while ($row = $perusahaan->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                                <td><?= htmlspecialchars($row['bidang_usaha']) ?></td>
                                <td><?= $row['kuota'] ?></td>
                                <td><?= htmlspecialchars($row['no_telp']) ?></td>
                                <td>
                                    <a href="?edit=<?= $row['id'] ?>" class="btn-edit"><i class='bx bxs-edit'></i> Edit</a>
                                    <a href="?hapus=<?= $row['id'] ?>" class="btn-hapus" onclick="return confirm('Hapus perusahaan ini?')"><i class='bx bxs-trash'></i> Hapus</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;padding:20px">Belum ada data perusahaan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($pages > 1): ?>
                <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;flex-wrap:wrap;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" style="padding:6px 14px;border:1px solid var(--grey);border-radius:8px;text-decoration:none;color:var(--dark);font-size:13px;">← Prev</a>
                    <?php endif; ?>
                    <?php for ($i=1; $i<=$pages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" style="padding:6px 14px;border:1px solid <?= $i==$page?'var(--blue)':'var(--grey)'?>;background:<?= $i==$page?'var(--blue)':'white'?>;color:<?= $i==$page?'white':'var(--dark)'?>;border-radius:8px;text-decoration:none;font-size:13px;"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $pages): ?>
                        <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" style="padding:6px 14px;border:1px solid var(--grey);border-radius:8px;text-decoration:none;color:var(--dark);font-size:13px;">Next →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
