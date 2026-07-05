<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'siswa') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id = $_SESSION['user_id'];

$result = $conn->query("SELECT * FROM siswa WHERE user_id = $user_id");
$siswa = $result ? $result->fetch_assoc() : null;

$stmt = $conn->prepare("SELECT p.*, pr.nama_perusahaan, pr.bidang_usaha, pr.alamat as alamat_perusahaan,
    pr.no_telp as telp_perusahaan,
    pb.nama_lengkap as nama_pembimbing, pb.no_telp as telp_pembimbing, pb.keahlian
    FROM pendaftaran p
    JOIN perusahaan pr ON p.perusahaan_id = pr.id
    LEFT JOIN pembimbing pb ON p.pembimbing_id = pb.id
    WHERE p.user_id = ?");
if (!$stmt) die("Query error: " . $conn->error);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pkl = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalLogbook      = 0;
$logbookHariIni    = 0;
$logbookDiverifikasi = 0;
$logbookMenunggu   = 0;
$logbookTerakhir   = null;
$nilai             = null;
$totalHari         = 0;
$persen            = 0;

if ($pkl) {
    $pid = (int)$pkl['id'];
    
    $res = $conn->query("SELECT COUNT(*) as t FROM logbook WHERE pendaftaran_id = $pid");
    $totalLogbook = $res ? (int)$res->fetch_assoc()['t'] : 0;

    $today = $conn->real_escape_string(date('Y-m-d'));
    $res2  = $conn->query("SELECT COUNT(*) as t FROM logbook WHERE pendaftaran_id = $pid AND tanggal = '$today'");
    $logbookHariIni = $res2 ? (int)$res2->fetch_assoc()['t'] : 0;

    $res3 = $conn->query("SELECT COUNT(*) as t FROM logbook WHERE pendaftaran_id = $pid AND status_verifikasi = 'Diverifikasi'");
    $logbookDiverifikasi = $res3 ? (int)$res3->fetch_assoc()['t'] : 0;

    $logbookMenunggu = $totalLogbook - $logbookDiverifikasi;

    $res4 = $conn->query("SELECT tanggal FROM logbook WHERE pendaftaran_id = $pid ORDER BY tanggal DESC LIMIT 1");
    $logbookTerakhir = $res4 ? $res4->fetch_assoc() : null;

    $res5 = $conn->query("SELECT * FROM penilaian WHERE pendaftaran_id = $pid");
    $nilai = $res5 ? $res5->fetch_assoc() : null;

    if (!empty($pkl['tanggal_mulai']) && !empty($pkl['tanggal_selesai'])) {
        $mulai     = new DateTime($pkl['tanggal_mulai']);
        $selesai   = new DateTime($pkl['tanggal_selesai']);
        $totalHari = max(1, $mulai->diff($selesai)->days);
        $persen    = min(100, round(($totalLogbook / $totalHari) * 100));
    }
}

$pengumuman = $conn->query("SELECT * FROM pengumuman ORDER BY created_at DESC LIMIT 4");
$totalPengumuman = $pengumuman ? $pengumuman->num_rows : 0;

// Hitung step progress PKL
$step = 0;
if ($pkl) {
    $step = 1;
    if (in_array($pkl['status'], ['Diterima', 'Sedang PKL', 'Menunggu Penilaian', 'Selesai'])) $step = 2;
    if ($step >= 2 && $pkl['pembimbing_id'])                           $step = 3;
    if ($step >= 3 && $totalLogbook > 0)                               $step = 4;
    if ($step >= 4 && $nilai)                                          $step = 5;
    if ($pkl['status'] === 'Selesai' && $pkl['sertifikat'])            $step = 6;
}

$namaUser = htmlspecialchars($siswa['nama_lengkap'] ?? $_SESSION['username']);
$jam = (int)date('H');
$sapa = $jam < 11 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php sidebarSiswa('dashboard_siswa'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span class="nav-title"><?= $sapa ?>, <?= $namaUser ?>! 👋</span>
        <span class="nav-date"><i class='bx bx-calendar'></i> <?= date('d F Y') ?></span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>

    <main>
        <!-- HEAD -->
        <div class="head-title">
            <div class="left">
                <h1>Dashboard</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Home</a></li>
                </ul>
            </div>
        </div>

        <!-- ALERTS -->
        <?php if (!$siswa): ?>
        <div class="alert alert-warning"><i class='bx bxs-error'></i><span>Profil belum lengkap. <a href="profil_siswa.php"><b>Lengkapi sekarang →</b></a></span></div>
        <?php endif; ?>
        <?php if ($pkl && in_array($pkl['status'], ['Diterima', 'Sedang PKL', 'Menunggu Penilaian']) && !$logbookHariIni): ?>
        <div class="alert alert-info"><i class='bx bxs-bell-ring'></i><span>Kamu belum mengisi logbook hari ini. <a href="logbook.php"><b>Isi sekarang →</b></a></span></div>
        <?php endif; ?>
        <?php if ($pkl && !empty($pkl['catatan'])): ?>
        <div class="alert alert-warning"><i class='bx bxs-info-circle'></i><span><b>Catatan Admin:</b> <?= htmlspecialchars($pkl['catatan']) ?></span></div>
        <?php endif; ?>

        <!-- STATISTIK CARDS -->
        <ul class="box-info">
            <li>
                <i class='bx bxs-file-doc'></i>
                <span class="text">
                    <h3><?= $pkl
                        ? '<span class="badge-stat '.strtolower($pkl['status']).'">'.$pkl['status'].'</span>'
                        : '<span class="badge-stat pending">Belum Daftar</span>' ?></h3>
                    <p>Status PKL</p>
                </span>
            </li>
            <li>
                <i class='bx bxs-book-alt'></i>
                <span class="text">
                    <h3><?= $totalLogbook ?></h3>
                    <p>Total Logbook</p>
                </span>
            </li>
            <li>
                <i class='bx bxs-check-circle'></i>
                <span class="text">
                    <h3><?= $logbookDiverifikasi ?></h3>
                    <p>Logbook Diverifikasi</p>
                </span>
            </li>
            <li>
                <i class='bx bxs-star'></i>
                <span class="text">
                    <h3><?= $nilai ? $nilai['nilai_akhir'] : '-' ?></h3>
                    <p>Nilai Akhir</p>
                </span>
            </li>
        </ul>

        <!-- QUICK ACTION -->
        <div class="card">
            <div class="card-header"><h3><i class='bx bxs-zap'></i> Aksi Cepat</h3></div>
            <div class="quick-action-grid">
                <a href="daftar_pkl.php" class="qa-item qa-blue">
                    <div class="qa-icon"><i class='bx bxs-file-plus'></i></div>
                    <span><?= $pkl ? 'Lihat Pendaftaran' : 'Daftar PKL' ?></span>
                </a>
                <a href="logbook.php" class="qa-item qa-green">
                    <div class="qa-icon"><i class='bx bxs-book-alt'></i></div>
                    <span>Logbook</span>
                </a>
                <a href="nilai_siswa.php" class="qa-item qa-yellow">
                    <div class="qa-icon"><i class='bx bxs-star'></i></div>
                    <span>Nilai PKL</span>
                </a>
                <a href="profil_siswa.php" class="qa-item qa-purple">
                    <div class="qa-icon"><i class='bx bxs-user'></i></div>
                    <span>Profil Saya</span>
                </a>
                <a href="pengumuman_siswa.php" class="qa-item qa-orange">
                    <div class="qa-icon">
                        <i class='bx bxs-bell'></i>
                        <?php if ($totalPengumuman > 0): ?><span class="qa-badge"><?= $totalPengumuman ?></span><?php endif; ?>
                    </div>
                    <span>Pengumuman</span>
                </a>
            </div>
        </div>

        <!-- PROGRESS TAHAPAN PKL -->
        <div class="card">
            <div class="card-header">
                <h3><i class='bx bxs-flag-checkered'></i> Progress Tahapan PKL</h3>
                <?php if ($step > 0): ?>
                <span class="badge diterima">Langkah <?= $step ?> dari 6</span>
                <?php endif; ?>
            </div>
            <div class="pkl-steps">
                <?php
                $steps = [
                    ['icon' => 'bxs-file-plus',    'label' => 'Pendaftaran',      'desc' => 'Ajukan pendaftaran PKL'],
                    ['icon' => 'bxs-check-shield', 'label' => 'Verifikasi',       'desc' => 'Admin memverifikasi'],
                    ['icon' => 'bxs-user-badge',   'label' => 'Penempatan',       'desc' => 'Pembimbing ditugaskan'],
                    ['icon' => 'bxs-book-alt',     'label' => 'Pelaksanaan',      'desc' => 'Isi logbook harian'],
                    ['icon' => 'bxs-star',         'label' => 'Penilaian',        'desc' => 'Pembimbing menilai'],
                    ['icon' => 'bxs-award',        'label' => 'Sertifikat',       'desc' => 'PKL selesai'],
                ];
                foreach ($steps as $i => $s):
                    $num   = $i + 1;
                    $state = $num < $step ? 'done' : ($num === $step ? 'active' : 'pending');
                ?>
                <div class="pkl-step <?= $state ?>">
                    <div class="step-circle">
                        <?php if ($state === 'done'): ?>
                            <i class='bx bxs-check'></i>
                        <?php else: ?>
                            <i class='bx <?= $s['icon'] ?>'></i>
                        <?php endif; ?>
                    </div>
                    <div class="step-info">
                        <span class="step-label"><?= $s['label'] ?></span>
                        <span class="step-desc"><?= $s['desc'] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($pkl): ?>

        <!-- INFO PERUSAHAAN & PEMBIMBING -->
        <div class="two-col-grid">
            <div class="card info-card">
                <div class="card-header">
                    <h3><i class='bx bxs-buildings'></i> Perusahaan PKL</h3>
                    <span class="badge <?= strtolower($pkl['status']) ?>"><?= $pkl['status'] ?></span>
                </div>
                <div class="info-detail-list">
                    <div class="info-detail-item">
                        <div class="idi-icon idi-blue"><i class='bx bxs-buildings'></i></div>
                        <div><label>Nama Perusahaan</label><span><?= htmlspecialchars($pkl['nama_perusahaan']) ?></span></div>
                    </div>
                    <div class="info-detail-item">
                        <div class="idi-icon idi-orange"><i class='bx bxs-briefcase'></i></div>
                        <div><label>Bidang Usaha</label><span><?= htmlspecialchars($pkl['bidang_usaha'] ?? '-') ?></span></div>
                    </div>
                    <div class="info-detail-item">
                        <div class="idi-icon idi-green"><i class='bx bxs-phone'></i></div>
                        <div><label>Telepon</label><span><?= htmlspecialchars($pkl['telp_perusahaan'] ?? '-') ?></span></div>
                    </div>
                    <div class="info-detail-item">
                        <div class="idi-icon idi-purple"><i class='bx bxs-calendar'></i></div>
                        <div>
                            <label>Periode PKL</label>
                            <span><?= date('d M Y', strtotime($pkl['tanggal_mulai'])) ?> &ndash; <?= date('d M Y', strtotime($pkl['tanggal_selesai'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card info-card">
                <div class="card-header">
                    <h3><i class='bx bxs-user-badge'></i> Pembimbing PKL</h3>
                </div>
                <?php if ($pkl['nama_pembimbing']): ?>
                <div class="pembimbing-avatar">
                    <div class="avatar-circle"><i class='bx bxs-user'></i></div>
                    <div class="avatar-info">
                        <strong><?= htmlspecialchars($pkl['nama_pembimbing']) ?></strong>
                        <span><?= htmlspecialchars($pkl['keahlian'] ?? 'Pembimbing PKL') ?></span>
                    </div>
                </div>
                <div class="info-detail-list" style="margin-top:16px">
                    <div class="info-detail-item">
                        <div class="idi-icon idi-blue"><i class='bx bxs-phone'></i></div>
                        <div><label>Telepon</label><span><?= htmlspecialchars($pkl['telp_pembimbing'] ?? '-') ?></span></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="empty-state-sm">
                    <div class="empty-icon-sm"><i class='bx bxs-user-x'></i></div>
                    <p>Pembimbing belum ditugaskan</p>
                    <small>Admin akan menugaskan pembimbing setelah pendaftaran diverifikasi</small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (in_array($pkl['status'], ['Diterima', 'Sedang PKL', 'Menunggu Penilaian', 'Selesai'])): ?>

        <!-- RINGKASAN LOGBOOK -->
        <div class="card">
            <div class="card-header">
                <h3><i class='bx bxs-book-alt'></i> Ringkasan Logbook</h3>
                <a href="logbook.php" class="btn btn-primary btn-sm"><i class='bx bxs-plus-circle'></i> Isi Logbook</a>
            </div>
            <div class="logbook-summary-grid">
                <div class="lb-stat lb-total">
                    <div class="lb-icon"><i class='bx bxs-book-alt'></i></div>
                    <div class="lb-num"><?= $totalLogbook ?></div>
                    <div class="lb-label">Total Logbook</div>
                </div>
                <div class="lb-stat lb-done">
                    <div class="lb-icon"><i class='bx bxs-check-shield'></i></div>
                    <div class="lb-num"><?= $logbookDiverifikasi ?></div>
                    <div class="lb-label">Diverifikasi</div>
                </div>
                <div class="lb-stat lb-pending">
                    <div class="lb-icon"><i class='bx bxs-time'></i></div>
                    <div class="lb-num"><?= $logbookMenunggu ?></div>
                    <div class="lb-label">Menunggu</div>
                </div>
                <div class="lb-stat lb-days">
                    <div class="lb-icon"><i class='bx bxs-calendar-check'></i></div>
                    <div class="lb-num"><?= $totalHari ?></div>
                    <div class="lb-label">Hari Kerja</div>
                </div>
            </div>
            <div class="logbook-progress">
                <div class="lp-header">
                    <span><i class='bx bx-trending-up'></i> Progress Pengisian</span>
                    <span class="lp-pct"><?= $persen ?>%</span>
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width:<?= $persen ?>%"></div></div>
                <div class="lp-footer">
                    <span><?= $totalLogbook ?> entri dari <?= $totalHari ?> hari kerja</span>
                    <?php if ($logbookTerakhir): ?>
                    <span><i class='bx bx-time-five'></i> Terakhir: <?= date('d M Y', strtotime($logbookTerakhir['tanggal'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!$logbookHariIni): ?>
            <div class="alert alert-warning" style="margin-top:0;margin-bottom:0"><i class='bx bxs-error'></i><span>Belum isi logbook hari ini. <a href="logbook.php"><b>Isi sekarang →</b></a></span></div>
            <?php else: ?>
            <div class="alert alert-success" style="margin-top:0;margin-bottom:0"><i class='bx bxs-check-circle'></i><span>Logbook hari ini sudah diisi. Bagus!</span></div>
            <?php endif; ?>
        </div>

        <!-- NILAI PKL -->
        <div class="card">
            <div class="card-header">
                <h3><i class='bx bxs-star'></i> Nilai PKL</h3>
                <a href="nilai_siswa.php" class="card-link">Lihat detail →</a>
            </div>
            <?php if ($nilai): ?>
            <div class="nilai-grid">
                <div class="nilai-item">
                    <div class="angka"><?= $nilai['nilai_kedisiplinan'] ?></div>
                    <div class="label">Kedisiplinan</div>
                </div>
                <div class="nilai-item">
                    <div class="angka"><?= $nilai['nilai_keterampilan'] ?></div>
                    <div class="label">Keterampilan</div>
                </div>
                <div class="nilai-item">
                    <div class="angka"><?= $nilai['nilai_sikap'] ?></div>
                    <div class="label">Sikap</div>
                </div>
                <div class="nilai-item">
                    <div class="angka"><?= $nilai['nilai_laporan'] ?></div>
                    <div class="label">Laporan</div>
                </div>
                <div class="nilai-item nilai-akhir-item">
                    <div class="angka" style="color:<?= $nilai['nilai_akhir'] >= 75 ? 'var(--green)' : 'var(--red)' ?>"><?= $nilai['nilai_akhir'] ?></div>
                    <div class="label">Nilai Akhir</div>
                    <span class="badge <?= strtolower($nilai['status']) ?>" style="margin-top:6px"><?= $nilai['status'] ?></span>
                </div>
            </div>
            <?php if ($nilai['catatan']): ?>
            <div class="catatan-box"><i class='bx bxs-comment-detail'></i> <b>Catatan:</b> <?= htmlspecialchars($nilai['catatan']) ?></div>
            <?php endif; ?>
            <?php else: ?>
            <div class="empty-state-sm">
                <div class="empty-icon-sm"><i class='bx bxs-star'></i></div>
                <p>Belum ada penilaian dari pembimbing</p>
                <small>Penilaian akan diberikan setelah PKL berlangsung</small>
            </div>
            <?php endif; ?>
        </div>

        <?php endif; ?>

        <?php if (in_array($pkl['status'], ['Menunggu', 'Menunggu Verifikasi'])): ?>
        <div class="card">
            <div class="card-header"><h3><i class='bx bxs-edit'></i> Kelola Pendaftaran</h3></div>
            <div class="alert alert-warning" style="margin-bottom:16px"><i class='bx bxs-time-five'></i><span>Pendaftaran kamu sedang menunggu review dari admin.</span></div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <a href="daftar_pkl.php?edit=1" class="btn btn-primary"><i class='bx bxs-edit'></i> Edit Pendaftaran</a>
                <a href="hapus_pendaftaran.php" class="btn btn-danger" onclick="return confirm('Yakin hapus pendaftaran?')"><i class='bx bxs-trash'></i> Hapus</a>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- EMPTY STATE -->
        <div class="card">
            <div class="empty-state">
                <div class="empty-icon"><i class='bx bxs-file-plus'></i></div>
                <h3>Belum Ada Pendaftaran PKL</h3>
                <p>Kamu belum mendaftarkan diri untuk PKL. Segera daftar untuk memulai prosesnya.</p>
                <a href="daftar_pkl.php" class="btn btn-primary" style="margin-top:20px"><i class='bx bxs-file-plus'></i> Daftar PKL Sekarang</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- BOTTOM GRID: PENGUMUMAN + AKTIVITAS -->
        <div class="bottom-grid">

            <!-- PENGUMUMAN -->
            <div class="card" style="margin-bottom:0">
                <div class="card-header">
                    <h3>
                        <i class='bx bxs-bell'></i> Pengumuman
                        <?php if ($totalPengumuman > 0): ?><span class="notif-dot"></span><?php endif; ?>
                    </h3>
                    <a href="pengumuman_siswa.php" class="card-link">Lihat semua →</a>
                </div>
                <?php if ($totalPengumuman > 0): ?>
                    <?php $pengumuman->data_seek(0); while ($row = $pengumuman->fetch_assoc()): ?>
                    <div class="pengumuman-item">
                        <div class="peng-date"><i class='bx bxs-calendar'></i> <?= date('d M Y', strtotime($row['created_at'])) ?></div>
                        <h4><?= htmlspecialchars($row['judul']) ?></h4>
                        <p><?= htmlspecialchars(substr($row['isi'], 0, 100)) ?>...</p>
                        <a href="pengumuman_siswa.php" class="peng-read">Baca selengkapnya →</a>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                <div class="empty-state-sm">
                    <div class="empty-icon-sm"><i class='bx bxs-bell-off'></i></div>
                    <p>Belum ada pengumuman</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- AKTIVITAS TERAKHIR -->
            <div class="card" style="margin-bottom:0">
                <div class="card-header"><h3><i class='bx bxs-time-five'></i> Aktivitas Terakhir</h3></div>
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="act-icon act-blue"><i class='bx bxs-log-in'></i></div>
                        <div class="act-info">
                            <span>Login ke sistem</span>
                            <small><?= date('d M Y, H:i') ?></small>
                        </div>
                        <div class="act-dot act-dot-blue"></div>
                    </div>
                    <?php if ($logbookTerakhir): ?>
                    <div class="activity-item">
                        <div class="act-icon act-green"><i class='bx bxs-book-alt'></i></div>
                        <div class="act-info">
                            <span>Logbook terakhir diisi</span>
                            <small><?= date('d M Y', strtotime($logbookTerakhir['tanggal'])) ?></small>
                        </div>
                        <div class="act-dot act-dot-green"></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($pkl): ?>
                    <div class="activity-item">
                        <div class="act-icon act-orange"><i class='bx bxs-file-doc'></i></div>
                        <div class="act-info">
                            <span>Status PKL: <b><?= $pkl['status'] ?></b></span>
                            <small><?= date('d M Y', strtotime($pkl['created_at'])) ?></small>
                        </div>
                        <div class="act-dot act-dot-orange"></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($nilai): ?>
                    <div class="activity-item">
                        <div class="act-icon act-yellow"><i class='bx bxs-star'></i></div>
                        <div class="act-info">
                            <span>Nilai akhir diterima: <b><?= $nilai['nilai_akhir'] ?></b></span>
                            <small><?= date('d M Y', strtotime($nilai['created_at'])) ?></small>
                        </div>
                        <div class="act-dot act-dot-yellow"></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!$pkl && !$logbookTerakhir && !$nilai): ?>
                    <div class="empty-state-sm">
                        <div class="empty-icon-sm"><i class='bx bxs-time'></i></div>
                        <p>Belum ada aktivitas tercatat</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- end bottom-grid -->

    </main>
</section>
<script src="script.js"></script>
</body>
</html>
