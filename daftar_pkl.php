<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'siswa') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id = $_SESSION['user_id'];
$siswa   = $conn->query("SELECT id FROM siswa WHERE user_id = $user_id")->fetch_assoc();
if (!$siswa) { header("Location: profil_siswa.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM pendaftaran WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

$isEdit = isset($_GET['edit']) && $existing && $existing['status'] === 'Menunggu';
if ($existing && !$isEdit) { header("Location: dashboard_siswa.php"); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $perusahaan_id   = $_POST['perusahaan_id'];
    $tanggal_mulai   = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $dokumen_path    = $existing['surat_permohonan'] ?? '';

    if (!empty($_FILES['surat_permohonan']['name'])) {
        $uploadDir = 'uploads/surat/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext      = pathinfo($_FILES['surat_permohonan']['name'], PATHINFO_EXTENSION);
        $filename = 'surat_' . $user_id . '_' . time() . '.' . $ext;
        $target   = $uploadDir . $filename;
        if ($_FILES['surat_permohonan']['size'] > 10 * 1024 * 1024) {
            $error = 'Ukuran file maksimal 10MB.';
        } elseif (!move_uploaded_file($_FILES['surat_permohonan']['tmp_name'], $target)) {
            $error = 'Gagal upload file.';
        } else {
            if ($dokumen_path && file_exists($dokumen_path)) unlink($dokumen_path);
            $dokumen_path = $target;
        }
    }

    if (!$error) {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE pendaftaran SET perusahaan_id=?,tanggal_mulai=?,tanggal_selesai=?,surat_permohonan=? WHERE user_id=?");
            $stmt->bind_param("isssi", $perusahaan_id, $tanggal_mulai, $tanggal_selesai, $dokumen_path, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO pendaftaran (user_id,perusahaan_id,tanggal_mulai,tanggal_selesai,surat_permohonan) VALUES (?,?,?,?,?)");
            $stmt->bind_param("iisss", $user_id, $perusahaan_id, $tanggal_mulai, $tanggal_selesai, $dokumen_path);
        }
        $stmt->execute();
        $stmt->close();
        header("Location: dashboard_siswa.php");
        exit();
    }
}

$perusahaan = $conn->query("SELECT * FROM perusahaan ORDER BY nama_perusahaan");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar PKL</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-card { max-width: 560px; }
        .info-box { background: var(--grey); border-radius: 8px; padding: 12px; margin-top: 8px; font-size: 13px; display: none; color: var(--dark); }
    </style>
</head>
<body>
<?php sidebarSiswa('daftar_pkl'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span class="nav-title"><?= $isEdit ? 'Edit Pendaftaran PKL' : 'Daftar PKL' ?></span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1><?= $isEdit ? 'Edit Pendaftaran' : 'Daftar PKL' ?></h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_siswa.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Daftar PKL</a></li>
                </ul>
            </div>
        </div>
        <div class="form-card card">
            <?php if ($error): ?>
            <div class="alert alert-error"><i class='bx bxs-error'></i> <?= $error ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Pilih Perusahaan</label>
                    <select name="perusahaan_id" id="sel-perusahaan" required onchange="showInfo(this)">
                        <option value="">-- Pilih Perusahaan --</option>
                        <?php while ($p = $perusahaan->fetch_assoc()): $sel = ($existing && $existing['perusahaan_id'] == $p['id']) ? 'selected' : ''; ?>
                        <option value="<?= $p['id'] ?>" <?= $sel ?>
                            data-alamat="<?= htmlspecialchars($p['alamat']) ?>"
                            data-bidang="<?= htmlspecialchars($p['bidang_usaha']) ?>"
                            data-kuota="<?= $p['kuota'] ?>">
                            <?= htmlspecialchars($p['nama_perusahaan']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <div class="info-box" id="info-box">
                        <b>Alamat:</b> <span id="i-alamat"></span><br>
                        <b>Bidang:</b> <span id="i-bidang"></span><br>
                        <b>Kuota:</b> <span id="i-kuota"></span> orang
                    </div>
                </div>
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" value="<?= $existing['tanggal_mulai'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" value="<?= $existing['tanggal_selesai'] ?? '' ?>" required>
                </div>
                <div class="form-group">
                    <label>Surat Permohonan <?= $isEdit ? '<small style="font-weight:400;color:var(--dark-grey)">(kosongkan jika tidak diganti)</small>' : '' ?></label>
                    <input type="file" name="surat_permohonan" <?= !$isEdit ? 'required' : '' ?>>
                    <?php if ($existing && $existing['surat_permohonan']): ?>
                    <small style="margin-top:4px;display:block">File saat ini: <a href="<?= $existing['surat_permohonan'] ?>" target="_blank" style="color:var(--blue)">Lihat File</a></small>
                    <?php endif; ?>
                    <small style="display:block;color:var(--dark-grey);margin-top:4px">Format: PDF, DOC, JPG, PNG (maks. 10MB)</small>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Pendaftaran' : 'Kirim Pendaftaran' ?></button>
                    <?php if ($isEdit): ?>
                    <a href="hapus_pendaftaran.php" class="btn btn-danger" onclick="return confirm('Yakin hapus pendaftaran ini?')">Hapus Pendaftaran</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </main>
</section>
<script src="script.js"></script>
<script>
function showInfo(sel) {
    const opt = sel.options[sel.selectedIndex];
    const box = document.getElementById('info-box');
    if (opt.value) {
        document.getElementById('i-alamat').textContent = opt.dataset.alamat;
        document.getElementById('i-bidang').textContent = opt.dataset.bidang;
        document.getElementById('i-kuota').textContent  = opt.dataset.kuota;
        box.style.display = 'block';
    } else { box.style.display = 'none'; }
}
window.onload = () => showInfo(document.getElementById('sel-perusahaan'));
</script>
</body>
</html>
