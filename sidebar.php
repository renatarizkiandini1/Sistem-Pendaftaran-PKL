<?php
function sidebarSiswa($active = '') {
    $menu = [
        'dashboard_siswa'  => ['icon' => 'bxs-dashboard',  'label' => 'Dashboard'],
        'profil_siswa'     => ['icon' => 'bxs-user',        'label' => 'Profil Saya'],
        'daftar_pkl'       => ['icon' => 'bxs-file-plus',   'label' => 'Daftar PKL'],
        'logbook'          => ['icon' => 'bxs-book-alt',    'label' => 'Logbook'],
        'nilai_siswa'      => ['icon' => 'bxs-star',        'label' => 'Nilai'],
        'pengumuman_siswa' => ['icon' => 'bxs-bell',        'label' => 'Pengumuman'],
    ];
    echo renderSidebar('bxs-graduation', 'Sistem PKL', $menu, $active);
}

function sidebarPembimbing($active = '') {
    global $conn;
    $pb_id = 0;
    if (isset($_SESSION['user_id'])) {
        $res = $conn->query("SELECT id FROM pembimbing WHERE user_id = {$_SESSION['user_id']}");
        if ($res) { $pb = $res->fetch_assoc(); $pb_id = $pb['id'] ?? 0; }
    }
    $logPending = 0;
    if ($pb_id) {
        $q = $conn->query("SELECT COUNT(*) as t FROM logbook l JOIN pendaftaran p ON l.pendaftaran_id = p.id WHERE p.pembimbing_id = $pb_id AND l.status_verifikasi = 'Menunggu'");
        if ($q) $logPending = (int)$q->fetch_assoc()['t'];
    }
    $menu = [
        'dashboard_pembimbing' => ['icon' => 'bxs-dashboard', 'label' => 'Dashboard'],
        'siswa_bimbingan'      => ['icon' => 'bxs-group',     'label' => 'Siswa Bimbingan'],
        'detail_logbook'       => ['icon' => 'bxs-book-alt',  'label' => 'Logbook', 'badge' => $logPending],
        'beri_nilai'           => ['icon' => 'bxs-star',      'label' => 'Penilaian'],
    ];
    echo renderSidebar('bxs-user-badge', 'Pembimbing', $menu, $active);
}

function sidebarAdmin($active = '') {
    $menu = [
        'dashboard_admin'           => ['icon' => 'bxs-dashboard',  'label' => 'Dashboard'],
        'admin_pendaftaran'         => ['icon' => 'bxs-file-doc',   'label' => 'Pendaftaran'],
        'admin_perusahaan'          => ['icon' => 'bxs-buildings',  'label' => 'Perusahaan'],
        'admin_siswa'               => ['icon' => 'bxs-group',      'label' => 'Siswa'],
        'admin_pembimbing'          => ['icon' => 'bxs-user-badge', 'label' => 'Pembimbing'],
        'admin_tugaskan_pembimbing' => ['icon' => 'bxs-user-check', 'label' => 'Tugaskan Pembimbing'],
        'admin_pengumuman'          => ['icon' => 'bxs-bell',       'label' => 'Pengumuman'],
        'admin_sertifikat'          => ['icon' => 'bxs-award',      'label' => 'Sertifikat'],
    ];
    echo renderSidebar('bxs-shield-alt-2', 'Admin PKL', $menu, $active);
}

function renderSidebar($brandIcon, $brandLabel, $menu, $active) {
    $html  = "<div class='sidebar-overlay'></div>";
    $html .= "<section id='sidebar'>";
    $html .= "<a href='#' class='brand'><i class='bx {$brandIcon}'></i><span class='text'>{$brandLabel}</span></a>";
    $html .= "<ul class='side-menu top'>";
    foreach ($menu as $file => $item) {
        $isActive = $active === $file ? 'class="active"' : '';
        $badge    = isset($item['badge']) && $item['badge'] > 0
                    ? "<span class='menu-badge'>{$item['badge']}</span>"
                    : '';
        $html .= "<li {$isActive}><a href='{$file}.php' data-tooltip='{$item['label']}'><i class='bx {$item['icon']}'></i><span class='text'>{$item['label']}</span>{$badge}</a></li>";
    }
    $html .= "</ul>";
    $html .= "<div style='margin-top: auto; border-top: 1px solid var(--border); padding: 8px 10px;'>";
    $html .= "<a href='logout.php' onclick='return confirm(\"Yakin ingin keluar?\")' style='display: flex; align-items: center; gap: 12px; padding: 11px 12px; border-radius: 12px; color: var(--red); font-size: 14px; font-weight: 500; text-decoration: none; transition: all 0.25s;'><i class='bx bxs-log-out-circle' style='font-size: 20px; min-width: 24px; text-align: center;'></i><span>Logout</span></a>";
    $html .= "</div>";
    $html .= "</section>";
    return $html;
}
?>
