<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'pembimbing') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');
include('functions.php');

$user_id    = $_SESSION['user_id'];
$res        = $conn->query("SELECT * FROM pembimbing WHERE user_id = $user_id");
$pembimbing = $res ? $res->fetch_assoc() : null;
$pb_id      = $pembimbing['id'] ?? 0;

// Status aktif mencakup semua status PKL berjalan
$statusAktif = "('Diterima','Sedang PKL','Menunggu Penilaian','Selesai')";

$q1 = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE pembimbing_id = $pb_id AND status IN $statusAktif");
$totalSiswa = $q1 ? (int)$q1->fetch_assoc()['t'] : 0;

$q2 = $conn->query("SELECT COUNT(*) as t FROM logbook l JOIN pendaftaran p ON l.pendaftaran_id = p.id WHERE p.pembimbing_id = $pb_id AND l.status_verifikasi = 'Menunggu'");
$logbookPending = $q2 ? (int)$q2->fetch_assoc()['t'] : 0;

$q3 = $conn->query("SELECT COUNT(*) as t FROM pendaftaran p LEFT JOIN penilaian n ON p.id = n.pendaftaran_id WHERE p.pembimbing_id = $pb_id AND p.status IN ('Sedang PKL','Menunggu Penilaian') AND (n.id IS NULL OR n.status = 'Draft')");
$nilaiPending = $q3 ? (int)$q3->fetch_assoc()['t'] : 0;

$q4 = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE pembimbing_id = $pb_id AND status = 'Selesai'");
$totalSelesai = $q4 ? (int)$q4->fetch_assoc()['t'] : 0;

// Sisa kuota
$kuota     = $pembimbing['kuota'] ?? 0;
$sisaKuota = max(0, $kuota - $totalSiswa);

// Siswa bimbingan terbaru (preview 5)
$siswaBimbingan = $conn->query("SELECT p.*, u.username, s.nama_lengkap, s.kelas, pr.nama_perusahaan,
    (SELECT COUNT(*) FROM logbook WHERE pendaftaran_id=p.id AND status_verifikasi='Menunggu') as pending_log
    FROM pendaftaran p
    JOIN user u ON p.user_id = u.id
    LEFT JOIN siswa s ON s.user_id = p.user_id
    JOIN perusahaan pr ON p.perusahaan_id = pr.id
    WHERE p.pembimbing_id = $pb_id AND p.status IN $statusAktif
    ORDER BY p.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pembimbing</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .profile-card { background: linear-gradient(135deg, var(--blue) 0%, #1a3c6e 100%); color: white; border-radius: 16px; padding: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px; }
        .profile-avatar { width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 28px; flex-shrink: 0; }
        .profile-info h2 { font-size: 20px; font-weight: 700; margin-bottom: 4px; }
        .profile-info p { font-size: 13px; opacity: 0.85; }
        .profile-stats { margin-left: auto; display: flex; gap: 24px; }
        .profile-stat { text-align: center; }
        .profile-stat .val { font-size: 24px; font-weight: 700; }
        .profile-stat .lbl { font-size: 11px; opacity: 0.8; }

        .task-list { background: var(--white); border-radius: 16px; padding: 24px; border: 1px solid var(--border); }
        .task-item { display: flex; align-items: center; gap: 14px; padding: 14px; background: var(--light); border-radius: 12px; margin-bottom: 10px; text-decoration: none; transition: all 0.2s; }
        .task-item:hover { transform: translateX(4px); box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .task-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .task-icon.orange { background: var(--light-orange); color: var(--orange); }
        .task-icon.blue   { background: var(--light-blue);   color: var(--blue); }
        .task-info { flex: 1; }
        .task-title { font-size: 14px; font-weight: 600; color: var(--dark); }
        .task-sub   { font-size: 12px; color: var(--dark-grey); margin-top: 2px; }
        .task-badge { background: var(--red); color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }

        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .status-badge.diterima        { background: #EBF4FF; color: var(--blue); }
        .status-badge.sedang-pkl      { background: #D1FAE5; color: #065F46; }
        .status-badge.menunggu-penilaian { background: #FEF3C7; color: #92400E; }
        .status-badge.selesai         { background: #E0E7FF; color: #3730A3; }

        .dash-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        @media (max-width: 1024px) { .dash-grid { grid-template-columns: 1fr; } .profile-stats { display: none; } }
    </style>
</head>
<body>
<?php sidebarPembimbing('dashboard_pembimbing'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span class="nav-title">Halo, <?= htmlspecialchars($pembimbing['nama_lengkap'] ?? $_SESSION['username']) ?>!</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Dashboard Pembimbing</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Home</a></li>
                </ul>
            </div>
        </div>

        <!-- Profil Card -->
        <div class="profile-card">
            <div class="profile-avatar"><i class='bx bxs-user-badge'></i></div>
            <div class="profile-info">
                <h2><?= htmlspecialchars($pembimbing['nama_lengkap'] ?? $_SESSION['username']) ?></h2>
                <p><i class='bx bxs-briefcase'></i> <?= htmlspecialchars($pembimbing['keahlian'] ?? 'Pembimbing PKL') ?></p>
                <p><i class='bx bxs-id-card'></i> NIP: <?= htmlspecialchars($pembimbing['nip'] ?? '-') ?></p>
            </div>
            <div class="profile-stats">
                <div class="profile-stat">
                    <div class="val"><?= $totalSiswa ?>/<?= $kuota ?></div>
                    <div class="lbl">Siswa / Kuota</div>
                </div>
                <div class="profile-stat">
                    <div class="val"><?= $totalSelesai ?></div>
                    <div class="lbl">Selesai PKL</div>
                </div>
                <div class="profile-stat">
                    <div class="val"><?= $sisaKuota ?></div>
                    <div class="lbl">Slot Tersisa</div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <ul class="box-info">
            <li onclick="location.href='siswa_bimbingan.php'" style="cursor:pointer;">
                <i class='bx bxs-group'></i>
                <span class="text"><h3><?= $totalSiswa ?></h3><p>Siswa Bimbingan</p></span>
            </li>
            <li onclick="location.href='detail_logbook.php'" style="cursor:pointer;">
                <i class='bx bxs-book-alt'></i>
                <span class="text">
                    <h3><?= $logbookPending ?><?php if($logbookPending > 0): ?><span class="notif-dot"></span><?php endif; ?></h3>
                    <p>Logbook Pending</p>
                </span>
            </li>
            <li onclick="location.href='beri_nilai.php'" style="cursor:pointer;">
                <i class='bx bxs-star'></i>
                <span class="text">
                    <h3><?= $nilaiPending ?><?php if($nilaiPending > 0): ?><span class="notif-dot"></span><?php endif; ?></h3>
                    <p>Penilaian Pending</p>
                </span>
            </li>
            <li>
                <i class='bx bxs-flag-checkered'></i>
                <span class="text"><h3><?= $totalSelesai ?></h3><p>PKL Selesai</p></span>
            </li>
        </ul>

        <div class="dash-grid">
            <!-- Task List -->
            <div class="task-list">
                <h3 style="margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                    <i class='bx bxs-check-square'></i> Tugas Perlu Diselesaikan
                    <?php $totalTugas = ($logbookPending > 0 ? 1 : 0) + ($nilaiPending > 0 ? 1 : 0); ?>
                    <?php if($totalTugas > 0): ?>
                    <span class="task-badge"><?= $totalTugas ?></span>
                    <?php endif; ?>
                </h3>

                <?php if ($logbookPending > 0): ?>
                <a href="siswa_bimbingan.php" class="task-item">
                    <div class="task-icon orange"><i class='bx bxs-book-alt'></i></div>
                    <div class="task-info">
                        <div class="task-title">Verifikasi Logbook</div>
                        <div class="task-sub"><?= $logbookPending ?> entri menunggu verifikasi</div>
                    </div>
                    <i class='bx bx-chevron-right' style="color:var(--dark-grey);font-size:20px;"></i>
                </a>
                <?php endif; ?>

                <?php if ($nilaiPending > 0): ?>
                <a href="siswa_bimbingan.php" class="task-item">
                    <div class="task-icon blue"><i class='bx bxs-star'></i></div>
                    <div class="task-info">
                        <div class="task-title">Beri Penilaian</div>
                        <div class="task-sub"><?= $nilaiPending ?> siswa belum dinilai</div>
                    </div>
                    <i class='bx bx-chevron-right' style="color:var(--dark-grey);font-size:20px;"></i>
                </a>
                <?php endif; ?>

                <?php if ($totalTugas === 0): ?>
                <div style="text-align:center;padding:32px;color:var(--dark-grey);">
                    <i class='bx bxs-check-circle' style="font-size:48px;opacity:0.3;"></i>
                    <p style="margin-top:8px;">Semua tugas selesai! 🎉</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Kuota Visual -->
            <div class="task-list">
                <h3 style="margin-bottom:16px;"><i class='bx bxs-bar-chart-alt-2'></i> Kapasitas Bimbingan</h3>
                <?php $persen = $kuota > 0 ? round(($totalSiswa / $kuota) * 100) : 0; ?>
                <div style="text-align:center;margin-bottom:16px;">
                    <div style="font-size:40px;font-weight:700;color:var(--blue);"><?= $totalSiswa ?></div>
                    <div style="font-size:13px;color:var(--dark-grey);">dari <?= $kuota ?> kuota terisi</div>
                </div>
                <div style="background:var(--border);border-radius:10px;height:12px;overflow:hidden;margin-bottom:8px;">
                    <div style="height:100%;width:<?= $persen ?>%;background:<?= $persen >= 90 ? 'var(--red)' : ($persen >= 70 ? 'var(--orange)' : 'var(--green)') ?>;border-radius:10px;transition:width 0.5s;"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--dark-grey);">
                    <span><?= $persen ?>% terisi</span>
                    <span><?= $sisaKuota ?> slot tersisa</span>
                </div>
            </div>
        </div>

        <!-- Tabel Siswa Preview -->
        <div class="card">
            <div class="card-header">
                <h3><i class='bx bxs-group'></i> Siswa Bimbingan</h3>
                <a href="siswa_bimbingan.php">Lihat semua →</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Nama Siswa</th><th>Kelas</th><th>Perusahaan</th><th>Status</th><th>Logbook</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($siswaBimbingan && $siswaBimbingan->num_rows > 0): ?>
                            <?php while ($row = $siswaBimbingan->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['nama_lengkap'] ?? $row['username']) ?></strong></td>
                                <td><?= htmlspecialchars($row['kelas'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                                <td><span class="status-badge <?= strtolower(str_replace(' ','-',$row['status'])) ?>"><?= $row['status'] ?></span></td>
                                <td>
                                    <?php if ($row['pending_log'] > 0): ?>
                                    <span style="color:var(--orange);font-weight:700;"><?= $row['pending_log'] ?> pending</span>
                                    <?php else: ?>
                                    <span style="color:var(--green);">✓ Terkini</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="detail_logbook.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-ghost">Logbook</a>
                                    <a href="beri_nilai.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary" style="margin-left:4px;">Nilai</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--dark-grey);">
                                <i class='bx bxs-group' style="font-size:40px;opacity:0.3;"></i><br>
                                Belum ada siswa bimbingan.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
