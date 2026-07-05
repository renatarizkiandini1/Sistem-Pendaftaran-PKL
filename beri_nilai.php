<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'pembimbing') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');

$user_id    = $_SESSION['user_id'];
$res        = $conn->query("SELECT * FROM pembimbing WHERE user_id = $user_id");
$pembimbing = $res ? $res->fetch_assoc() : null;
$pb_id      = $pembimbing['id'] ?? 0;
$pkl_id     = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT p.*, u.username, s.nama_lengkap, s.kelas, pr.nama_perusahaan
    FROM pendaftaran p
    JOIN user u ON p.user_id = u.id
    LEFT JOIN siswa s ON s.user_id = p.user_id
    JOIN perusahaan pr ON p.perusahaan_id = pr.id
    WHERE p.id = ? AND p.pembimbing_id = ?");
$stmt->bind_param("ii", $pkl_id, $pb_id);
$stmt->execute();
$pkl = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$pkl) { header("Location: siswa_bimbingan.php"); exit(); }

// Null-safe nilai query
$qNilai = $conn->query("SELECT * FROM penilaian WHERE pendaftaran_id = $pkl_id");
$nilai  = $qNilai ? $qNilai->fetch_assoc() : null;

// Ringkasan logbook
$qLog   = $conn->query("SELECT COUNT(*) as total, SUM(status_verifikasi='Diverifikasi') as verified FROM logbook WHERE pendaftaran_id = $pkl_id");
$logStat = $qLog ? $qLog->fetch_assoc() : ['total' => 0, 'verified' => 0];

// Lock jika sudah Final
$isLocked = ($nilai['status'] ?? '') === 'Final';
$pesan    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isLocked) {
        $pesan = 'locked';
    } else {
        $k      = min(100, max(0, (int)$_POST['nilai_kedisiplinan']));
        $kt     = min(100, max(0, (int)$_POST['nilai_keterampilan']));
        $s      = min(100, max(0, (int)$_POST['nilai_sikap']));
        $l      = min(100, max(0, (int)$_POST['nilai_laporan']));
        $akhir  = round(($k + $kt + $s + $l) / 4, 2);
        $catatan = $_POST['catatan'];
        $status  = in_array($_POST['status'], ['Draft','Final']) ? $_POST['status'] : 'Draft';

        if ($nilai) {
            $stmt = $conn->prepare("UPDATE penilaian SET nilai_kedisiplinan=?,nilai_keterampilan=?,nilai_sikap=?,nilai_laporan=?,nilai_akhir=?,catatan=?,status=? WHERE pendaftaran_id=?");
            $stmt->bind_param("iiiidssi", $k, $kt, $s, $l, $akhir, $catatan, $status, $pkl_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO penilaian (pendaftaran_id,nilai_kedisiplinan,nilai_keterampilan,nilai_sikap,nilai_laporan,nilai_akhir,catatan,status) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("iiiidsss", $pkl_id, $k, $kt, $s, $l, $akhir, $catatan, $status);
        }
        $stmt->execute();
        $stmt->close();

        $qNilai2 = $conn->query("SELECT * FROM penilaian WHERE pendaftaran_id = $pkl_id");
        $nilai   = $qNilai2 ? $qNilai2->fetch_assoc() : null;
        $isLocked = ($nilai['status'] ?? '') === 'Final';
        $pesan   = 'success';
    }
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
        .info-bar { background:var(--light); border-radius:12px; padding:16px 24px; margin-top:20px; display:flex; gap:32px; flex-wrap:wrap; border:1px solid var(--border); }
        .info-bar .item label { font-size:11px; color:var(--dark-grey); display:block; text-transform:uppercase; }
        .info-bar .item span  { font-size:14px; font-weight:600; color:var(--dark); }

        .logbook-bar { background:var(--white); border-radius:12px; padding:16px 20px; margin-top:16px; border:1px solid var(--border); display:flex; align-items:center; gap:20px; flex-wrap:wrap; }
        .logbook-bar .lb-stat { text-align:center; }
        .logbook-bar .lb-stat .num { font-size:22px; font-weight:700; }
        .logbook-bar .lb-stat .lbl { font-size:11px; color:var(--dark-grey); }
        .lb-progress { flex:1; min-width:150px; }
        .lb-progress .bar { background:var(--border); height:8px; border-radius:10px; overflow:hidden; margin-top:6px; }
        .lb-progress .fill { height:100%; background:var(--blue); border-radius:10px; }

        .form-wrap { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:20px; }
        .form-card { background:var(--white); border-radius:16px; padding:28px; border:1px solid var(--border); }
        .form-card.full { grid-column: 1 / -1; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--dark); }
        .form-group input[type=number], .form-group textarea, .form-group select { width:100%; padding:10px 12px; border:2px solid var(--border); border-radius:8px; font-size:14px; outline:none; transition:border 0.2s; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color:var(--blue); }
        .form-group input:disabled { background:var(--light); cursor:not-allowed; }

        .nilai-preview-card { background:var(--white); border-radius:16px; padding:28px; border:1px solid var(--border); text-align:center; }
        .nilai-big { font-size:56px; font-weight:700; transition:color 0.3s; }
        .nilai-label { font-size:13px; color:var(--dark-grey); margin-top:4px; }
        .predikat { display:inline-block; padding:6px 18px; border-radius:20px; font-size:13px; font-weight:700; margin-top:12px; }
        .predikat.A { background:#D1FAE5; color:#065F46; }
        .predikat.B { background:#DBEAFE; color:#1E40AF; }
        .predikat.C { background:#FEF3C7; color:#92400E; }
        .predikat.D { background:#FEE2E2; color:#991B1B; }

        .komponen-list { margin-top:16px; }
        .komponen-item { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border); font-size:13px; }
        .komponen-item:last-child { border-bottom:none; }
        .komponen-bar { width:80px; height:6px; background:var(--border); border-radius:10px; overflow:hidden; display:inline-block; margin: 0 8px; }
        .komponen-bar-fill { height:100%; background:var(--blue); border-radius:10px; }

        .btn-submit { background:var(--blue); color:white; border:none; padding:12px 28px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; width:100%; margin-top:8px; }
        .btn-submit:hover { opacity:0.9; }
        .btn-submit:disabled { background:var(--dark-grey); cursor:not-allowed; }

        .locked-banner { background:#FEF3C7; border:2px solid var(--orange); color:#92400E; padding:14px 18px; border-radius:12px; margin-bottom:16px; display:flex; align-items:center; gap:10px; font-size:13px; }

        @media (max-width:768px) { .form-wrap { grid-template-columns:1fr; } }
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
                    <li><a href="siswa_bimbingan.php">Siswa Bimbingan</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Penilaian</a></li>
                </ul>
            </div>
        </div>

        <!-- Info Siswa -->
        <div class="info-bar">
            <div class="item"><label>Siswa</label><span><?= htmlspecialchars($pkl['nama_lengkap'] ?? $pkl['username']) ?></span></div>
            <div class="item"><label>Kelas</label><span><?= htmlspecialchars($pkl['kelas'] ?? '-') ?></span></div>
            <div class="item"><label>Perusahaan</label><span><?= htmlspecialchars($pkl['nama_perusahaan']) ?></span></div>
            <div class="item"><label>Status PKL</label><span><?= $pkl['status'] ?></span></div>
        </div>

        <!-- Progres Logbook -->
        <?php $logPct = $logStat['total'] > 0 ? round(($logStat['verified'] / $logStat['total']) * 100) : 0; ?>
        <div class="logbook-bar">
            <div class="lb-stat">
                <div class="num"><?= $logStat['total'] ?></div>
                <div class="lbl">Total Logbook</div>
            </div>
            <div class="lb-stat">
                <div class="num" style="color:var(--green);"><?= $logStat['verified'] ?></div>
                <div class="lbl">Diverifikasi</div>
            </div>
            <div class="lb-stat">
                <div class="num" style="color:var(--orange);"><?= $logStat['total'] - $logStat['verified'] ?></div>
                <div class="lbl">Pending</div>
            </div>
            <div class="lb-progress">
                <div style="font-size:12px;color:var(--dark-grey);">Progress Verifikasi Logbook</div>
                <div class="bar"><div class="fill" style="width:<?= $logPct ?>%;"></div></div>
                <div style="font-size:11px;color:var(--dark-grey);margin-top:4px;"><?= $logPct ?>% terverifikasi</div>
            </div>
            <a href="detail_logbook.php?id=<?= $pkl_id ?>" style="padding:8px 16px;background:var(--light-blue);color:var(--blue);border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;">
                <i class='bx bxs-book-alt'></i> Lihat Logbook
            </a>
        </div>

        <!-- Alert -->
        <?php if ($pesan === 'success'): ?>
        <div style="background:var(--light-green);color:var(--green);padding:12px 16px;border-radius:10px;margin-top:16px;font-size:13px;">
            <i class='bx bxs-check-circle'></i> Nilai berhasil disimpan!
        </div>
        <?php endif; ?>
        <?php if ($pesan === 'locked'): ?>
        <div class="locked-banner" style="margin-top:16px;">
            <i class='bx bxs-lock'></i> Nilai sudah berstatus Final dan tidak dapat diubah.
        </div>
        <?php endif; ?>

        <div class="form-wrap">
            <!-- Form Input Nilai -->
            <div class="form-card">
                <h3 style="margin-bottom:20px;font-size:16px;"><i class='bx bxs-edit'></i> Input Nilai</h3>

                <?php if ($isLocked): ?>
                <div class="locked-banner">
                    <i class='bx bxs-lock'></i> Nilai sudah Final — tidak dapat diubah.
                </div>
                <?php endif; ?>

                <form method="POST" id="form-nilai">
                    <div class="form-group">
                        <label>Kedisiplinan (0–100)</label>
                        <input type="number" name="nilai_kedisiplinan" id="n1" min="0" max="100"
                            value="<?= $nilai['nilai_kedisiplinan'] ?? 0 ?>"
                            <?= $isLocked ? 'disabled' : '' ?> required oninput="hitungNilai()">
                    </div>
                    <div class="form-group">
                        <label>Keterampilan (0–100)</label>
                        <input type="number" name="nilai_keterampilan" id="n2" min="0" max="100"
                            value="<?= $nilai['nilai_keterampilan'] ?? 0 ?>"
                            <?= $isLocked ? 'disabled' : '' ?> required oninput="hitungNilai()">
                    </div>
                    <div class="form-group">
                        <label>Sikap (0–100)</label>
                        <input type="number" name="nilai_sikap" id="n3" min="0" max="100"
                            value="<?= $nilai['nilai_sikap'] ?? 0 ?>"
                            <?= $isLocked ? 'disabled' : '' ?> required oninput="hitungNilai()">
                    </div>
                    <div class="form-group">
                        <label>Laporan (0–100)</label>
                        <input type="number" name="nilai_laporan" id="n4" min="0" max="100"
                            value="<?= $nilai['nilai_laporan'] ?? 0 ?>"
                            <?= $isLocked ? 'disabled' : '' ?> required oninput="hitungNilai()">
                    </div>
                    <div class="form-group">
                        <label>Catatan untuk Siswa</label>
                        <textarea name="catatan" rows="3" placeholder="Catatan evaluasi..."
                            <?= $isLocked ? 'disabled' : '' ?>><?= htmlspecialchars($nilai['catatan'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Status Penilaian</label>
                        <select name="status" <?= $isLocked ? 'disabled' : '' ?>>
                            <option value="Draft" <?= ($nilai['status'] ?? '') === 'Draft' ? 'selected' : '' ?>>Draft (masih bisa diubah)</option>
                            <option value="Final" <?= ($nilai['status'] ?? '') === 'Final' ? 'selected' : '' ?>>Final (tidak bisa diubah)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-submit" <?= $isLocked ? 'disabled' : '' ?>>
                        <i class='bx bxs-save'></i> <?= $nilai ? 'Update Nilai' : 'Simpan Nilai' ?>
                    </button>
                </form>
            </div>

            <!-- Preview Nilai -->
            <div class="nilai-preview-card">
                <div style="font-size:13px;color:var(--dark-grey);margin-bottom:8px;">Nilai Akhir</div>
                <div class="nilai-big" id="preview-nilai"><?= $nilai['nilai_akhir'] ?? 0 ?></div>
                <div id="preview-predikat" class="predikat <?= $nilai ? ($nilai['nilai_akhir'] >= 90 ? 'A' : ($nilai['nilai_akhir'] >= 75 ? 'B' : ($nilai['nilai_akhir'] >= 60 ? 'C' : 'D'))) : 'D' ?>">
                    <?php
                    $na = $nilai['nilai_akhir'] ?? 0;
                    echo $na >= 90 ? 'A — Sangat Baik' : ($na >= 75 ? 'B — Baik' : ($na >= 60 ? 'C — Cukup' : 'D — Kurang'));
                    ?>
                </div>
                <div style="margin-top:12px;font-size:12px;color:var(--dark-grey);">
                    <?= ($nilai['status'] ?? 'Belum ada') === 'Final' ? '<span style="color:var(--green);font-weight:700;"><i class=\'bx bxs-lock\'></i> Final</span>' : '<span style="color:var(--orange);">Draft</span>' ?>
                </div>

                <!-- Rincian per komponen -->
                <div class="komponen-list">
                    <?php
                    $komponen = [
                        'Kedisiplinan' => $nilai['nilai_kedisiplinan'] ?? 0,
                        'Keterampilan' => $nilai['nilai_keterampilan'] ?? 0,
                        'Sikap'        => $nilai['nilai_sikap'] ?? 0,
                        'Laporan'      => $nilai['nilai_laporan'] ?? 0,
                    ];
                    foreach ($komponen as $label => $val):
                    ?>
                    <div class="komponen-item">
                        <span><?= $label ?></span>
                        <div style="display:flex;align-items:center;">
                            <div class="komponen-bar">
                                <div class="komponen-bar-fill" id="bar-<?= strtolower($label) ?>" style="width:<?= $val ?>%;"></div>
                            </div>
                            <span style="font-weight:700;min-width:32px;text-align:right;"><?= $val ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</section>
<script src="script.js"></script>
<script>
function hitungNilai() {
    const n1 = Math.min(100, Math.max(0, parseFloat(document.getElementById('n1').value) || 0));
    const n2 = Math.min(100, Math.max(0, parseFloat(document.getElementById('n2').value) || 0));
    const n3 = Math.min(100, Math.max(0, parseFloat(document.getElementById('n3').value) || 0));
    const n4 = Math.min(100, Math.max(0, parseFloat(document.getElementById('n4').value) || 0));
    const avg = ((n1 + n2 + n3 + n4) / 4).toFixed(2);

    // Update preview nilai
    const el = document.getElementById('preview-nilai');
    el.textContent = avg;
    el.style.color = avg >= 75 ? 'var(--green)' : (avg >= 60 ? 'var(--orange)' : 'var(--red)');

    // Update predikat
    const pp = document.getElementById('preview-predikat');
    if (avg >= 90)      { pp.textContent = 'A — Sangat Baik'; pp.className = 'predikat A'; }
    else if (avg >= 75) { pp.textContent = 'B — Baik';        pp.className = 'predikat B'; }
    else if (avg >= 60) { pp.textContent = 'C — Cukup';       pp.className = 'predikat C'; }
    else                { pp.textContent = 'D — Kurang';      pp.className = 'predikat D'; }

    // Update bar komponen
    const bars = { 'kedisiplinan': n1, 'keterampilan': n2, 'sikap': n3, 'laporan': n4 };
    for (const [key, val] of Object.entries(bars)) {
        const bar = document.getElementById('bar-' + key);
        if (bar) bar.style.width = val + '%';
    }
}
</script>
</body>
</html>
