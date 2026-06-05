const sidebar  = document.getElementById('sidebar');
const menuBtn  = document.querySelector('#content nav .bx-menu');
const overlay  = document.querySelector('.sidebar-overlay');
const switchMode = document.getElementById('switch-mode');

// ── SIDEBAR TOGGLE ──
if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            // Mobile: slide in/out
            sidebar.classList.toggle('show');
            if (overlay) overlay.classList.toggle('show');
        } else {
            // Desktop: collapse/expand
            sidebar.classList.toggle('hide');
            localStorage.setItem('sidebar', sidebar.classList.contains('hide') ? 'hide' : 'show');
        }
    });
}

// Close sidebar saat klik overlay (mobile)
if (overlay) {
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    });
}

// Restore sidebar state (desktop)
if (window.innerWidth > 768) {
    const saved = localStorage.getItem('sidebar');
    if (saved === 'hide' && sidebar) sidebar.classList.add('hide');
}

// Auto hide sidebar di mobile
if (window.innerWidth <= 768 && sidebar) {
    sidebar.classList.remove('hide');
}

// Resize handler
window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        if (overlay) overlay.classList.remove('show');
        if (sidebar) sidebar.classList.remove('show');
    }
});

// ── DARK MODE ──
if (switchMode) {
    // Restore dark mode
    if (localStorage.getItem('dark') === '1') {
        document.body.classList.add('dark');
        switchMode.checked = true;
    }
    switchMode.addEventListener('change', () => {
        if (switchMode.checked) {
            document.body.classList.add('dark');
            localStorage.setItem('dark', '1');
        } else {
            document.body.classList.remove('dark');
            localStorage.setItem('dark', '0');
        }
    });
}

// ── ACTIVE MENU ──
const currentPage = location.pathname.split('/').pop();
document.querySelectorAll('#sidebar .side-menu li a').forEach(link => {
    const href = link.getAttribute('href');
    if (href && href === currentPage) {
        link.closest('li').classList.add('active');
    }
    // Tambah tooltip untuk collapsed sidebar
    const label = link.querySelector('.text');
    if (label) link.setAttribute('data-tooltip', label.textContent.trim());
});

// ── CLOSE MODAL saat klik di luar ──
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) overlay.classList.remove('show');
    });
});

// ── ESC tutup modal ──
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show'));
    }
});
