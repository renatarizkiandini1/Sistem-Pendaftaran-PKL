<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');
include('functions.php');

// Statistik Utama — null-safe
$q1 = $conn->query("SELECT COUNT(*) as t FROM pendaftaran");
$totalPendaftar  = $q1 ? (int)$q1->fetch_assoc()['t'] : 0;

$q2 = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE status IN ('Sedang PKL', 'Menunggu Penilaian')");
$totalAktif      = $q2 ? (int)$q2->fetch_assoc()['t'] : 0;

$q3 = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE status='Menunggu Verifikasi'");
$totalMenunggu   = $q3 ? (int)$q3->fetch_assoc()['t'] : 0;

$q4 = $conn->query("SELECT COUNT(*) as t FROM pendaftaran WHERE status='Selesai'");
$totalSelesai    = $q4 ? (int)$q4->fetch_assoc()['t'] : 0;

$q5 = $conn->query("SELECT COUNT(*) as t FROM pembimbing");
$totalPembimbing = $q5 ? (int)$q5->fetch_assoc()['t'] : 0;

$q6 = $conn->query("SELECT COUNT(*) as t FROM user WHERE role='siswa'");
$totalSiswa      = $q6 ? (int)$q6->fetch_assoc()['t'] : 0;

// Task List
$tasks = getAdminTasks($conn);

// Statistics untuk chart
$stats = getStatisticsData($conn);

// Recent Activities
$activities = getRecentActivities($conn, 5);

// Pendaftaran Terbaru
$recent = $conn->query("SELECT p.*, u.username, s.nama_lengkap, pr.nama_perusahaan FROM pendaftaran p JOIN user u ON p.user_id = u.id LEFT JOIN siswa s ON s.user_id = p.user_id JOIN perusahaan pr ON p.perusahaan_id = pr.id ORDER BY p.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
        .tasks-card { background: var(--white); border-radius: var(--radius); padding: 24px; border: 1px solid var(--border); }
        .task-item { display: flex; align-items: center; gap: 14px; padding: 14px; background: var(--light); border-radius: 10px; margin-bottom: 12px; cursor: pointer; transition: all 0.3s; text-decoration: none; }
        .task-item:hover { transform: translateX(4px); box-shadow: var(--shadow); }
        .task-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
        .task-icon.orange { background: var(--light-orange); color: var(--orange); }
        .task-icon.blue { background: var(--light-blue); color: var(--blue); }
        .task-icon.green { background: var(--light-green); color: var(--green); }
        .task-info { flex: 1; }
        .task-title { font-size: 14px; font-weight: 600; color: var(--dark); margin-bottom: 2px; }
        .task-count { font-size: 12px; color: var(--dark-grey); }
        .task-badge { background: var(--red); color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .chart-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px; }
        .chart-card { background: var(--white); border-radius: var(--radius); padding: 24px; border: 1px solid var(--border); }
        .chart-card h4 { font-size: 15px; font-weight: 700; margin-bottom: 16px; color: var(--dark); }
        .activity-timeline { max-height: 400px; overflow-y: auto; }
        .activity-item { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { width: 36px; height: 36px; border-radius: 8px; background: var(--light-blue); color: var(--blue); display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .activity-content { flex: 1; min-width: 0; }
        .activity-action { font-size: 13px; font-weight: 600; color: var(--dark); }
        .activity-desc { font-size: 12px; color: var(--dark-grey); margin-top: 2px; }
        .activity-time { font-size: 11px; color: var(--dark-grey); margin-top: 4px; }
        .empty-state-small { text-align: center; padding: 32px; color: var(--dark-grey); }
        .empty-state-small i { font-size: 48px; opacity: 0.3; margin-bottom: 8px; }
        @media (max-width: 1024px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .chart-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<?php sidebarAdmin('dashboard_admin'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span class="nav-title">Selamat datang, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Dashboard Admin</h1>
                <ul class="breadcrumb">
                    <li><a href="#">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Home</a></li>
                </ul>
            </div>
        </div>

        <!-- Stats Cards -->
        <ul class="box-info">
            <li onclick="location.href='admin_pendaftaran.php'" style="cursor:pointer;">
                <i class='bx bxs-file-doc'></i>
                <span class="text"><h3><?= $totalPendaftar ?></h3><p>Total Pendaftar</p></span>
            </li>
            <li onclick="location.href='admin_pendaftaran.php?status=Sedang+PKL'" style="cursor:pointer;">
                <i class='bx bxs-check-circle'></i>
                <span class="text"><h3><?= $totalAktif ?></h3><p>Peserta Aktif</p></span>
            </li>
            <li onclick="location.href='admin_pendaftaran.php?status=Menunggu+Verifikasi'" style="cursor:pointer;">
                <i class='bx bxs-time'></i>
                <span class="text">
                    <h3><?= $totalMenunggu ?><?php if($totalMenunggu > 0): ?><span class="notif-dot"></span><?php endif; ?></h3>
                    <p>Menunggu Review</p>
                </span>
            </li>
            <li onclick="location.href='admin_pendaftaran.php?status=Selesai'" style="cursor:pointer;">
                <i class='bx bxs-flag-checkered'></i>
                <span class="text"><h3><?= $totalSelesai ?></h3><p>PKL Selesai</p></span>
            </li>
            <li onclick="location.href='admin_pembimbing.php'" style="cursor:pointer;">
                <i class='bx bxs-user-badge'></i>
                <span class="text"><h3><?= $totalPembimbing ?></h3><p>Total Pembimbing</p></span>
            </li>
            <li onclick="location.href='admin_siswa.php'" style="cursor:pointer;">
                <i class='bx bxs-group'></i>
                <span class="text"><h3><?= $totalSiswa ?></h3><p>Total Siswa</p></span>
            </li>
        </ul>
        
        <!-- Task List & Activity -->
        <div class="dashboard-grid">
            <!-- Tasks Card -->
            <div class="tasks-card">
                <h3 style="margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                    <i class='bx bxs-check-square'></i> Tugas Menunggu
                    <?php if(count($tasks) > 0): ?>
                    <span class="task-badge"><?= count($tasks) ?></span>
                    <?php endif; ?>
                </h3>
                
                <?php if(count($tasks) > 0): ?>
                    <?php foreach($tasks as $task): ?>
                    <a href="<?= $task['link'] ?>" class="task-item">
                        <div class="task-icon <?= $task['color'] ?>">
                            <i class='bx <?= $task['icon'] ?>'></i>
                        </div>
                        <div class="task-info">
                            <div class="task-title"><?= $task['title'] ?></div>
                            <div class="task-count"><?= $task['count'] ?> item perlu ditangani</div>
                        </div>
                        <i class='bx bx-chevron-right' style="font-size:20px;color:var(--dark-grey);"></i>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-small">
                        <i class='bx bxs-check-circle'></i>
                        <p>Tidak ada tugas menunggu<br>Semua pekerjaan sudah selesai! 🎉</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Recent Activity -->
            <div class="tasks-card">
                <h3 style="margin-bottom:20px;display:flex;align-items:center;gap:8px;">
                    <i class='bx bxs-time-five'></i> Aktivitas Terkini
                </h3>
                
                <div class="activity-timeline">
                    <?php if(count($activities) > 0): ?>
                        <?php foreach($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class='bx bxs-user'></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-action"><?= htmlspecialchars($activity['username']) ?></div>
                                <div class="activity-desc"><?= htmlspecialchars($activity['description'] ?? $activity['action']) ?></div>
                                <div class="activity-time"><?= timeAgo($activity['created_at']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state-small">
                            <i class='bx bxs-time'></i>
                            <p>Belum ada aktivitas</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="chart-grid">
            <!-- Status Chart -->
            <div class="chart-card">
                <h4><i class='bx bxs-pie-chart-alt-2'></i> Distribusi Status PKL</h4>
                <canvas id="statusChart" height="250"></canvas>
            </div>
            
            <!-- Monthly Chart -->
            <div class="chart-card">
                <h4><i class='bx bxs-bar-chart-alt-2'></i> Pendaftaran 6 Bulan Terakhir</h4>
                <canvas id="monthlyChart" height="250"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Pendaftaran Terbaru</h3>
                <a href="admin_pendaftaran.php">Lihat semua →</a>
            </div>
            <div class="table-wrap">
            <table>
                <thead><tr><th>Siswa</th><th>Perusahaan</th><th>Tanggal Daftar</th><th>Status</th></tr></thead>
                <tbody>
                    <?php if ($recent && $recent->num_rows > 0): ?>
                        <?php while ($row = $recent->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nama_lengkap'] ?? $row['username']) ?></td>
                            <td><?= htmlspecialchars($row['nama_perusahaan']) ?></td>
                            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td><span class="badge <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;padding:20px;color:var(--dark-grey)">Belum ada pendaftaran.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</section>
<script src="script.js"></script>
<script>
// Status Chart
const statusData = <?= json_encode($stats['status']) ?>;
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(statusData),
        datasets: [{
            data: Object.values(statusData),
            backgroundColor: ['#F97316', '#3C91E6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 15, font: { size: 11 } }
            }
        }
    }
});

// Monthly Chart
const monthlyData = <?= json_encode($stats['monthly']) ?>;
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: Object.keys(monthlyData),
        datasets: [{
            label: 'Pendaftaran',
            data: Object.values(monthlyData),
            backgroundColor: '#3C91E6',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
</script>
</body>
</html>
