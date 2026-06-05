<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'pembimbing') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id    = $_SESSION['user_id'];
$pembimbing = $conn->query("SELECT * FROM pembimbing WHERE user_id = $user_id")->fetch_assoc();
$pb_id      = $pembimbing['id'] ?? 0;
$pkl_id     = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT p.*, u.username, s.nama_lengkap, s.kelas, pr.nama_perusahaan FROM pendaftaran p JOIN user u ON p.user_id = u.id LEFT JOIN siswa s ON s.user_id = p.user_id JOIN perusahaan pr ON p.perusahaan_id = pr.id WHERE p.id = ? AND p.pembimbing_id = ?");
$stmt->bind_param("ii", $pkl_id, $pb_id);
$stmt->execute();
$pkl = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pkl) { header("Location: siswa_bimbingan.php"); exit(); }

$nilai = $conn->query("SELECT * FROM penilaian WHERE pendaftaran_id = $pkl_id")->fetch_assoc();
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $k       = (int)$_POST['nilai_kedisiplinan'];
    $kt      = (int)$_POST['nilai_keterampilan'];
    $s       = (int)$_POST['nilai_sikap'];
    $l       = (int)$_POST['nilai_laporan'];
    $akhir   = round(($k + $kt + $s + $l) / 4, 2);
    $catatan = $_POST['catatan'];
    $status  = $_POST['status'];

    if ($nilai) {
        $stmt = $conn->prepare("UPDATE penilaian SET nilai_kedisiplinan=?,nilai_keterampilan=?,nilai_sikap=?,nilai_laporan=?,nilai_akhir=?,catatan=?,status=? WHERE pendaftaran_id=?");
        $stmt->bind_param("iiiidssi", $k, $kt, $s, $l, $akhir, $catatan, $status, $pkl_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO penilaian (pendaftaran_id,nilai_kedisiplinan,nilai_keterampilan,nilai_sikap,nilai_laporan,nilai_akhir,catatan,status) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iiiidsss", $pkl_id, $k, $kt, $s, $l, $akhir, $catatan, $status);
    }
    $stmt->execute();
    $stmt->close();
    $nilai = $conn->query("SELECT * FROM penilaian WHERE pendaftaran_id = $pkl_id")->fetch_assoc();
    $pesan = 'success';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beri Nilai</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-card { background: var(--light); border-radius: 16px; padding: 32px; margin-top: 24px; max-width: 560px; }
        .info-bar { background: var(--light); border-radius: 12px; padding: 16px 24px; margin-top: 24px; display: flex; gap: 32px; flex-wrap: wrap; }
        .info-bar .item label { font-size: 11px; color: var(--dark-grey); display: block; }
        .info-bar .item span { font-size: 14px; font-weight: 600; color: var(--dark); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: var(--dark); }
        .form-group input[type=range] { width: 100%; }
        .form-group input[type=number], .form-group textarea, .form-group select { width: 100%; padding: 9px 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; outline: none; }
        .nilai-preview { font-size: 28px; font-weight: 700; color: var(--blue); text-align: center; padding: 16px; background: var(--grey); border-radius: 10px; margin-bottom: 16px; }
        .btn-submit { background: var(--blue); color: white; border: none; padding: 10px 28px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .alert-success { background: var(--light-blue); color: #0c5460; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13px; }
        .row-nilai { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    </style>
</head>
<body>
<?php sidebarPembimbing('beri_nilai'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Penilaian Siswa</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Beri Nilai</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_pembimbing.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Penilaian</a></li>
                </ul>
            </div>
        </div>

        <div class="info-bar">
            <div class="item"><label>Siswa</label><span><?= htmlspecialchars($pkl['nama_lengkap'] ?? $pkl['username']) ?></span></div>
            <div class="item"><label>Kelas</label><span><?= htmlspecialchars($pkl['kelas'] ?? '-') ?></span></div>
            <div class="item"><label>Perusahaan</label><span><?= htmlspecialchars($pkl['nama_perusahaan']) ?></span></div>
        </div>

        <div class="form-card">
            <?php if ($pesan === 'success'): ?>
            <div class="alert-success"><i class='bx bxs-check-circle'></i> Nilai berhasil disimpan!</div>
            <?php endif; ?>

            <div class="nilai-preview">Nilai Akhir: <span id="preview-nilai"><?= $nilai['nilai_akhir'] ?? 0 ?></span></div>

            <form method="POST" id="form-nilai">
                <div class="row-nilai">
                    <div class="form-group">
                        <label>Kedisiplinan (0-100)</label>
                        <input type="number" name="nilai_kedisiplinan" id="n1" min="0" max="100" value="<?= $nilai['nilai_kedisiplinan'] ?? 0 ?>" required oninput="hitungNilai()">
                    </div>
                    <div class="form-group">
                        <label>Keterampilan (0-100)</label>
                        <input type="number" name="nilai_keterampilan" id="n2" min="0" max="100" value="<?= $nilai['nilai_keterampilan'] ?? 0 ?>" required oninput="hitungNilai()">
                    </div>
                    <div class="form-group">
                        <label>Sikap (0-100)</label>
                        <input type="number" name="nilai_sikap" id="n3" min="0" max="100" value="<?= $nilai['nilai_sikap'] ?? 0 ?>" required oninput="hitungNilai()">
                    </div>
                    <div class="form-group">
                        <label>Laporan (0-100)</label>
                        <input type="number" name="nilai_laporan" id="n4" min="0" max="100" value="<?= $nilai['nilai_laporan'] ?? 0 ?>" required oninput="hitungNilai()">
                    </div>
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="catatan" rows="3" placeholder="Catatan untuk siswa..."><?= htmlspecialchars($nilai['catatan'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Status Penilaian</label>
                    <select name="status">
                        <option value="Draft" <?= ($nilai['status'] ?? '') === 'Draft' ? 'selected' : '' ?>>Draft (belum final)</option>
                        <option value="Final" <?= ($nilai['status'] ?? '') === 'Final' ? 'selected' : '' ?>>Final</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit">Simpan Nilai</button>
            </form>
        </div>
    </main>
</section>
<script src="script.js"></script>
<script>
function hitungNilai() {
    const n1 = parseFloat(document.getElementById('n1').value) || 0;
    const n2 = parseFloat(document.getElementById('n2').value) || 0;
    const n3 = parseFloat(document.getElementById('n3').value) || 0;
    const n4 = parseFloat(document.getElementById('n4').value) || 0;
    document.getElementById('preview-nilai').textContent = ((n1+n2+n3+n4)/4).toFixed(2);
}
</script>
</body>
</html>
