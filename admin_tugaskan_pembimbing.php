<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');
include('functions.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pkl_id  = $_POST['pkl_id'];
    $pb_id   = $_POST['pembimbing_id'];

    // Cek kuota pembimbing
    $pb      = $conn->query("SELECT nama_lengkap, kuota FROM pembimbing WHERE id = $pb_id")->fetch_assoc();
    $terpakai = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE pembimbing_id = $pb_id AND status IN ('Sedang PKL', 'Menunggu Penilaian')")->fetch_assoc()['t'];

    if ($pb && $terpakai >= $pb['kuota']) {
        $error = "Kuota pembimbing {$pb['nama_lengkap']} sudah penuh ({$pb['kuota']} siswa).";
    } else {
        $stmt = $conn->prepare("UPDATE pendaftaran SET pembimbing_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $pb_id, $pkl_id);
        $stmt->execute();
        $stmt->close();
        
        // Log activity
        logActivity($conn, $_SESSION['user_id'], 'Tugaskan Pembimbing', 'pendaftaran', $pkl_id, "Pembimbing ditugaskan: {$pb['nama_lengkap']}");
        
        header("Location: admin_tugaskan_pembimbing.php?success=1");
        exit();
    }
}

$siswaAktif = $conn->query("SELECT p.*, s.nama_lengkap, u.username, pr.nama_perusahaan, pr.bidang_usaha, pb.nama_lengkap as nama_pembimbing
    FROM pendaftaran p
    JOIN user u ON p.user_id = u.id
    LEFT JOIN siswa s ON s.user_id = p.user_id
    JOIN perusahaan pr ON p.perusahaan_id = pr.id
    LEFT JOIN pembimbing pb ON p.pembimbing_id = pb.id
    WHERE p.status IN ('Diterima', 'Sedang PKL', 'Menunggu Penilaian')
    ORDER BY p.created_at DESC");

// Get all pembimbing dengan info beban kerja
$pembimbingData = $conn->query("SELECT pb.*, u.username,
    (SELECT COUNT(*) FROM pendaftaran WHERE pembimbing_id = pb.id AND status IN ('Sedang PKL', 'Menunggu Penilaian')) as beban_kerja
    FROM pembimbing pb 
    JOIN user u ON pb.user_id = u.id 
    ORDER BY beban_kerja ASC, pb.nama_lengkap ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tugaskan Pembimbing</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .info-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-top: 20px; }
        .info-card { background: var(--white); border-radius: 12px; padding: 20px; border: 1px solid var(--border); }
        .info-card h4 { font-size: 14px; color: var(--dark-grey); margin-bottom: 8px; }
        .info-card .value { font-size: 28px; font-weight: 700; color: var(--blue); }
        .pembimbing-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-top: 20px; margin-bottom: 24px; }
        .pb-card { background: var(--white); border-radius: 12px; padding: 20px; border: 1px solid var(--border); transition: all 0.3s; }
        .pb-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .pb-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .pb-avatar { width: 48px; height: 48px; background: var(--light-blue); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--blue); flex-shrink: 0; }
        .pb-info h4 { font-size: 15px; font-weight: 700; color: var(--dark); margin-bottom: 2px; }
        .pb-info p { font-size: 12px; color: var(--dark-grey); }
        .pb-stats { display: flex; gap: 12px; margin-top: 12px; }
        .pb-stat { flex: 1; text-align: center; padding: 8px; background: var(--light); border-radius: 8px; }
        .pb-stat-value { font-size: 18px; font-weight: 700; color: var(--dark); }
        .pb-stat-label { font-size: 10px; color: var(--dark-grey); margin-top: 2px; }
        .workload-bar { width: 100%; height: 6px; background: var(--border); border-radius: 10px; overflow: hidden; margin-top: 12px; }
        .workload-fill { height: 100%; transition: width 0.3s; }
        .workload-fill.low { background: var(--green); }
        .workload-fill.medium { background: var(--yellow); }
        .workload-fill.high { background: var(--red); }
        .table-card { background:var(--white); border-radius:16px; padding:24px; margin-top:20px; overflow-x:auto; border: 1px solid var(--border); }
        table { width:100%; border-collapse:collapse; min-width: 800px; }
        th { padding: 12px 8px; font-size:12px; text-align:left; border-bottom:2px solid var(--border); color:var(--dark-grey); text-transform: uppercase; }
        td { padding:12px 8px; font-size:13px; border-bottom:1px solid var(--border); vertical-align: middle; }
        tr:hover td { background: var(--light); }
        select.sel-pb { padding:8px 12px; border-radius:8px; border:2px solid var(--border); font-size:13px; font-family: 'Poppins', sans-serif; min-width: 200px; }
        select.sel-pb:focus { border-color: var(--blue); outline: none; }
        .btn-save { background:var(--blue); color:white; padding:8px 18px; border-radius:8px; border:none; cursor:pointer; font-size:13px; font-weight:600; transition: all 0.3s; }
        .btn-save:hover { background: var(--blue-dark); transform: translateY(-1px); }
        .kuota-badge { font-size:11px; padding:3px 10px; border-radius:12px; font-weight:600; }
        .kuota-badge.available { background:var(--light-green); color:var(--green); }
        .kuota-badge.full { background:var(--light-red); color:var(--red); }
        .alert-error { background:var(--light-red); color:var(--red); padding:12px 16px; border-radius:10px; margin-top:16px; font-size:13px; display: flex; align-items: center; gap: 8px; border-left: 4px solid var(--red); }
        .alert-success { background:var(--light-green); color:var(--green); padding:12px 16px; border-radius:10px; margin-top:16px; font-size:13px; display: flex; align-items: center; gap: 8px; border-left: 4px solid var(--green); }
        .recommend-badge { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: 700; margin-left: 8px; }
    </style>
</head>
<body>
<?php sidebarAdmin('admin_tugaskan_pembimbing'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Tugaskan Pembimbing</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Tugaskan Pembimbing</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_admin.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Tugaskan Pembimbing</a></li>
                </ul>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert-error"><i class='bx bxs-error-circle'></i> <?= $error ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
        <div class="alert-success"><i class='bx bxs-check-circle'></i> Pembimbing berhasil ditugaskan!</div>
        <?php endif; ?>
        
        <!-- Info Cards -->
        <div class="info-cards">
            <div class="info-card">
                <h4>Total Siswa Aktif</h4>
                <div class="value"><?= $siswaAktif->num_rows ?></div>
            </div>
            <div class="info-card">
                <h4>Belum Dapat Pembimbing</h4>
                <?php 
                    $belumDapat = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE status IN ('Diterima', 'Sedang PKL') AND pembimbing_id IS NULL")->fetch_assoc()['t'];
                ?>
                <div class="value" style="color:<?= $belumDapat > 0 ? 'var(--orange)' : 'var(--green)' ?>"><?= $belumDapat ?></div>
            </div>
            <div class="info-card">
                <h4>Total Pembimbing</h4>
                <div class="value"><?= $pembimbingData->num_rows ?></div>
            </div>
            <div class="info-card">
                <h4>Rata-rata Beban Kerja</h4>
                <?php
                    $totalBeban = 0;
                    $pembimbingData->data_seek(0);
                    while($pb = $pembimbingData->fetch_assoc()) {
                        $totalBeban += $pb['beban_kerja'];
                    }
                    $rataBeban = $pembimbingData->num_rows > 0 ? round($totalBeban / $pembimbingData->num_rows, 1) : 0;
                ?>
                <div class="value"><?= $rataBeban ?></div>
            </div>
        </div>
        
        <!-- Pembimbing Cards -->
        <h3 style="margin-top:32px;margin-bottom:4px;font-size:18px;"><i class='bx bxs-user-badge'></i> Status Pembimbing</h3>
        <p style="color:var(--dark-grey);font-size:13px;margin-bottom:16px;">Beban kerja dan kapasitas pembimbing saat ini</p>
        
        <div class="pembimbing-grid">
            <?php $pembimbingData->data_seek(0); ?>
            <?php while($pb = $pembimbingData->fetch_assoc()): 
                $workloadPercent = $pb['kuota'] > 0 ? round(($pb['beban_kerja'] / $pb['kuota']) * 100) : 0;
                $workloadClass = $workloadPercent < 50 ? 'low' : ($workloadPercent < 80 ? 'medium' : 'high');
                $available = $pb['kuota'] - $pb['beban_kerja'];
            ?>
            <div class="pb-card">
                <div class="pb-header">
                    <div class="pb-avatar">
                        <i class='bx bxs-user'></i>
                    </div>
                    <div class="pb-info">
                        <h4><?= htmlspecialchars($pb['nama_lengkap']) ?></h4>
                        <p><?= htmlspecialchars($pb['keahlian'] ?? 'Pembimbing PKL') ?></p>
                    </div>
                </div>
                
                <div class="pb-stats">
                    <div class="pb-stat">
                        <div class="pb-stat-value"><?= $pb['beban_kerja'] ?></div>
                        <div class="pb-stat-label">Dibimbing</div>
                    </div>
                    <div class="pb-stat">
                        <div class="pb-stat-value"><?= $available ?></div>
                        <div class="pb-stat-label">Tersedia</div>
                    </div>
                    <div class="pb-stat">
                        <div class="pb-stat-value"><?= $pb['kuota'] ?></div>
                        <div class="pb-stat-label">Kuota</div>
                    </div>
                </div>
                
                <div class="workload-bar">
                    <div class="workload-fill <?= $workloadClass ?>" style="width:<?= $workloadPercent ?>%"></div>
                </div>
                <div style="text-align:center;margin-top:8px;font-size:12px;color:var(--dark-grey);">
                    <?= $workloadPercent ?>% Terisi
                    <?php if($available > 0): ?>
                        <span class="kuota-badge available" style="margin-left:8px;">Tersedia</span>
                    <?php else: ?>
                        <span class="kuota-badge full" style="margin-left:8px;">Penuh</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Tabel Penugasan -->
        <h3 style="margin-top:32px;margin-bottom:4px;font-size:18px;"><i class='bx bxs-edit'></i> Tugaskan Pembimbing</h3>
        <p style="color:var(--dark-grey);font-size:13px;margin-bottom:16px;">Pilih pembimbing yang sesuai untuk setiap siswa</p>
        
        <div class="table-card">
            <table>
                <thead>
                    <tr><th>No</th><th>Siswa</th><th>Perusahaan</th><th>Bidang</th><th>Pembimbing Saat Ini</th><th>Pilih Pembimbing</th></tr>
                </thead>
                <tbody>
                    <?php if ($siswaAktif && $siswaAktif->num_rows > 0): $no=1; ?>
                        <?php 
                            $siswaAktif->data_seek(0);
                            $pembimbingData->data_seek(0);
                            $pbArr = [];
                            while($pb = $pembimbingData->fetch_assoc()) $pbArr[] = $pb;
                        ?>
                        <?php while ($row = $siswaAktif->fetch_assoc()): 
                            $recommendations = getPembimbingRecommendations($conn, $row['user_id']);
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($row['nama_lengkap'] ?? $row['username']) ?></strong></td>
                            <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                            <td><?= htmlspecialchars($row['bidang_usaha'] ?? '-') ?></td>
                            <td>
                                <?php if($row['nama_pembimbing']): ?>
                                    <span style="color:var(--blue);font-weight:600;"><?= htmlspecialchars($row['nama_pembimbing']) ?></span>
                                <?php else: ?>
                                    <span style="color:var(--dark-grey);">Belum ditentukan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <input type="hidden" name="pkl_id" value="<?= $row['id'] ?>">
                                    <select name="pembimbing_id" class="sel-pb" required>
                                        <option value="">-- Pilih Pembimbing --</option>
                                        <?php foreach ($recommendations as $pb):
                                            $sel = $row['pembimbing_id'] == $pb['id'] ? 'selected' : '';
                                            $disabled = $pb['available_slots'] <= 0 && $row['pembimbing_id'] != $pb['id'] ? 'disabled' : '';
                                            $recommend = $pb['priority'] === 'high' ? '⭐ ' : '';
                                        ?>
                                        <option value="<?= $pb['id'] ?>" <?= $sel ?> <?= $disabled ?>>
                                            <?= $recommend ?><?= htmlspecialchars($pb['nama_lengkap']) ?> (<?= $pb['current_load'] ?>/<?= $pb['kuota'] ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-save">
                                        <i class='bx bxs-save'></i> Simpan
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--dark-grey)">
                            <i class='bx bxs-inbox' style="font-size:48px;opacity:0.3;"></i><br>
                            Belum ada siswa yang perlu ditugaskan pembimbing.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
