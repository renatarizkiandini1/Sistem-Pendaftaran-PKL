<?php
session_start();
if (!isset($_SESSION['username'])) { header("Location: index.html"); exit(); }
include('db.php');

$user_id = $_SESSION['user_id'];

// Cek profil sudah diisi
$siswa = $conn->query("SELECT id FROM siswa WHERE user_id = $user_id")->fetch_assoc();
if (!$siswa) { header("Location: profil_siswa.php"); exit(); }

// Ambil pendaftaran existing
$stmt = $conn->prepare("SELECT * FROM pendaftaran WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Jika sudah daftar dan bukan mode edit, redirect ke dashboard
$isEdit = isset($_GET['edit']) && $existing && $existing['status'] === 'Menunggu';
if ($existing && !$isEdit) { header("Location: dashboard_siswa.php"); exit(); }

$pesan = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $perusahaan_id   = $_POST['perusahaan_id'];
    $tanggal_mulai   = $_POST['tanggal_mulai'];
    $tanggal_selesai = $_POST['tanggal_selesai'];
    $dokumen_path    = $existing['dokumen'] ?? '';

    // Upload dokumen
    if (!empty($_FILES['dokumen']['name'])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $ext      = pathinfo($_FILES['dokumen']['name'], PATHINFO_EXTENSION);
        $filename = 'dok_' . $user_id . '_' . time() . '.' . $ext;
        $target   = $uploadDir . $filename;

        if ($_FILES['dokumen']['size'] > 10 * 1024 * 1024) {
            $error = 'Ukuran file maksimal 10MB.';
        } elseif (!move_uploaded_file($_FILES['dokumen']['tmp_name'], $target)) {
            $error = 'Gagal upload file.';
        } else {
            // Hapus file lama
            if ($dokumen_path && file_exists($dokumen_path)) unlink($dokumen_path);
            $dokumen_path = $target;
        }
    }

    if (!$error) {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE pendaftaran SET perusahaan_id=?, tanggal_mulai=?, tanggal_selesai=?, dokumen=? WHERE user_id=?");
            $stmt->bind_param("isssi", $perusahaan_id, $tanggal_mulai, $tanggal_selesai, $dokumen_path, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO pendaftaran (user_id, perusahaan_id, tanggal_mulai, tanggal_selesai, dokumen) VALUES (?,?,?,?,?)");
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
        .form-card { background: var(--light); border-radius: 20px; padding: 32px; margin-top: 24px; max-width: 600px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--dark); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; outline: none;
        }
        .form-group input:focus, .form-group select:focus { border-color: var(--blue); }
        .btn-submit { background: var(--blue); color: white; border: none; padding: 10px 28px; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .alert-error { background: #fde8e8; color: #c0392b; padding: 12px 20px; border-radius: 10px; margin-bottom: 16px; }
        .perusahaan-info { background: var(--grey); border-radius: 8px; padding: 12px; margin-top: 8px; font-size: 13px; display: none; }
    </style>
</head>
<body>
<section id="sidebar">
    <a href="#" class="brand"><i class='bx bxs-smile'></i><span class="text"><?= htmlspecialchars($_SESSION['username']) ?></span></a>
    <ul class="side-menu top">
        <li><a href="dashboard_siswa.php"><i class='bx bxs-dashboard'></i><span class="text">Dashboard</span></a></li>
        <li><a href="profil_siswa.php"><i class='bx bxs-user'></i><span class="text">Profil Saya</span></a></li>
        <li class="active"><a href="daftar_pkl.php"><i class='bx bxs-file-plus'></i><span class="text">Daftar PKL</span></a></li>
    </ul>
    <ul class="side-menu">
        <li><a href="logout.php" class="logout"><i class='bx bxs-log-out-circle'></i><span class="text">Logout</span></a></li>
    </ul>
</section>

<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600"><?= $isEdit ? 'Edit Pendaftaran PKL' : 'Daftar PKL' ?></span>
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

        <div class="form-card">
            <?php if ($error): ?>
            <div class="alert-error"><i class='bx bxs-error'></i> <?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Pilih Perusahaan</label>
                    <select name="perusahaan_id" id="perusahaan_id" required onchange="showInfo(this)">
                        <option value="">-- Pilih Perusahaan --</option>
                        <?php
                        $perusahaanData = [];
                        while ($p = $perusahaan->fetch_assoc()):
                            $perusahaanData[$p['id']] = $p;
                            $selected = ($existing && $existing['perusahaan_id'] == $p['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= $p['id'] ?>" <?= $selected ?>
                            data-alamat="<?= htmlspecialchars($p['alamat']) ?>"
                            data-bidang="<?= htmlspecialchars($p['bidang_usaha']) ?>"
                            data-kuota="<?= $p['kuota'] ?>">
                            <?= htmlspecialchars($p['nama_perusahaan']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <div class="perusahaan-info" id="perusahaan-info">
                        <p><b>Alamat:</b> <span id="info-alamat"></span></p>
                        <p><b>Bidang:</b> <span id="info-bidang"></span></p>
                        <p><b>Kuota:</b> <span id="info-kuota"></span> orang</p>
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
                    <label>Upload Dokumen <?= $isEdit ? '(kosongkan jika tidak ingin mengganti)' : '' ?></label>
                    <input type="file" name="dokumen" <?= !$isEdit ? 'required' : '' ?>>
                    <?php if ($existing && $existing['dokumen']): ?>
                    <small>File saat ini: <a href="<?= $existing['dokumen'] ?>" target="_blank">Lihat Dokumen</a></small>
                    <?php endif; ?>
                    <small style="display:block; color:var(--dark-grey); margin-top:4px;">Format: PDF, DOC, JPG, PNG (maks. 10MB)</small>
                </div>
                <button type="submit" class="btn-submit"><?= $isEdit ? 'Update Pendaftaran' : 'Kirim Pendaftaran' ?></button>
            </form>
        </div>
    </main>
</section>
<script src="script.js"></script>
<script>
function showInfo(sel) {
    const opt = sel.options[sel.selectedIndex];
    const info = document.getElementById('perusahaan-info');
    if (opt.value) {
        document.getElementById('info-alamat').textContent = opt.dataset.alamat;
        document.getElementById('info-bidang').textContent = opt.dataset.bidang;
        document.getElementById('info-kuota').textContent  = opt.dataset.kuota;
        info.style.display = 'block';
    } else {
        info.style.display = 'none';
    }
}
window.onload = () => showInfo(document.getElementById('perusahaan_id'));
</script>
</body>
</html>
