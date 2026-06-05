<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'siswa') { header("Location: index.html"); exit(); }
include('db.php');
include('sidebar.php');
$list = $conn->query("SELECT * FROM pengumuman ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengumuman</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <style>
        .card { background:var(--light); border-radius:16px; padding:24px; margin-top:24px; }
        .item { border-bottom:1px solid var(--grey); padding:16px 0; }
        .item:last-child { border-bottom:none; }
        .item h4 { font-size:15px; font-weight:700; color:var(--dark); margin-bottom:6px; }
        .item p  { font-size:14px; color:var(--dark); white-space:pre-wrap; line-height:1.7; }
        .item small { font-size:12px; color:var(--dark-grey); display:block; margin-top:8px; }
    </style>
</head>
<body>
<?php sidebarSiswa('pengumuman_siswa'); ?>
<section id="content">
    <nav>
        <i class='bx bx-menu'></i>
        <span style="font-weight:600">Pengumuman</span>
        <input type="checkbox" id="switch-mode" hidden>
        <label for="switch-mode" class="switch-mode"></label>
    </nav>
    <main>
        <div class="head-title">
            <div class="left">
                <h1>Pengumuman</h1>
                <ul class="breadcrumb">
                    <li><a href="dashboard_siswa.php">Dashboard</a></li>
                    <li><i class='bx bx-chevron-right'></i></li>
                    <li><a class="active" href="#">Pengumuman</a></li>
                </ul>
            </div>
        </div>
        <div class="card">
            <?php if ($list && $list->num_rows > 0): ?>
                <?php while ($row = $list->fetch_assoc()): ?>
                <div class="item">
                    <h4><?= htmlspecialchars($row['judul']) ?></h4>
                    <p><?= htmlspecialchars($row['isi']) ?></p>
                    <small><i class='bx bxs-calendar'></i> <?= date('d M Y H:i', strtotime($row['created_at'])) ?></small>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--dark-grey);font-size:14px">Belum ada pengumuman.</p>
            <?php endif; ?>
        </div>
    </main>
</section>
<script src="script.js"></script>
</body>
</html>
