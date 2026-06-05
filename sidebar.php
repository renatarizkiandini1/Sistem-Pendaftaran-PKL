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
    echo renderSidebar('bxs-graduation', 'Siswa', $menu, $active);
}

function sidebarPembimbing($active = '') {
    $menu = [
        'dashboard_pembimbing' => ['icon' => 'bxs-dashboard', 'label' => 'Dashboard'],
        'siswa_bimbingan'      => ['icon' => 'bxs-group',     'label' => 'Siswa Bimbingan'],
        'detail_logbook'       => ['icon' => 'bxs-book-alt',  'label' => 'Logbook'],
        'beri_nilai'           => ['icon' => 'bxs-star',      'label' => 'Penilaian'],
    ];
    echo renderSidebar('bxs-user-badge', 'Pembimbing', $menu, $active);
}

function sidebarAdmin($active = '') {
    $menu = [
        'dashboard_admin'            => ['icon' => 'bxs-dashboard',  'label' => 'Dashboard'],
        'admin_pendaftaran'          => ['icon' => 'bxs-file-doc',   'label' => 'Pendaftaran'],
        'admin_perusahaan'           => ['icon' => 'bxs-buildings',  'label' => 'Perusahaan'],
        'admin_siswa'                => ['icon' => 'bxs-group',      'label' => 'Siswa'],
        'admin_pembimbing'           => ['icon' => 'bxs-user-badge', 'label' => 'Pembimbing'],
        'admin_tugaskan_pembimbing'  => ['icon' => 'bxs-user-check', 'label' => 'Tugaskan Pembimbing'],
        'admin_pengumuman'           => ['icon' => 'bxs-bell',       'label' => 'Pengumuman'],
        'admin_sertifikat'           => ['icon' => 'bxs-award',      'label' => 'Sertifikat'],
    ];
    echo renderSidebar('bxs-shield-alt-2', 'Admin', $menu, $active);
}

function renderSidebar($brandIcon, $brandLabel, $menu, $active) {
    $html = "<section id='sidebar'>";
    $html .= "<a href='#' class='brand'><i class='bx {$brandIcon}'></i><span class='text'>{$brandLabel}</span></a>";
    $html .= "<ul class='side-menu top'>";
    foreach ($menu as $file => $item) {
        $isActive = $active === $file ? 'class="active"' : '';
        $html .= "<li {$isActive}><a href='{$file}.php'><i class='bx {$item['icon']}'></i><span class='text'>{$item['label']}</span></a></li>";
    }
    $html .= "</ul><ul class='side-menu'>";
    $html .= "<li><a href='logout.php' class='logout'><i class='bx bxs-log-out-circle'></i><span class='text'>Logout</span></a></li>";
    $html .= "</ul></section>";
    return $html;
}
?>
