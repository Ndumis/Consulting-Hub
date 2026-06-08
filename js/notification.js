function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!sidebar) return;
    const isOpen = sidebar.classList.toggle('sidebar-open');
    if (overlay) overlay.style.display = (isOpen && window.innerWidth <= 768) ? 'block' : 'none';
}

function toggleNotifications() {
    const d = document.getElementById('notificationsDropdown');
    if (!d) return;
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
}

document.addEventListener('click', function(e) {
    const sidebar  = document.getElementById('sidebar');
    const toggle   = document.querySelector('.sidebar-toggle');
    const dropdown = document.getElementById('notificationsDropdown');
    const notifWrap = document.querySelector('.header-notif-wrap');

    if (window.innerWidth <= 768 && sidebar && toggle &&
        !sidebar.contains(e.target) && !toggle.contains(e.target) &&
        sidebar.classList.contains('sidebar-open')) {
        sidebar.classList.remove('sidebar-open');
        const overlay = document.getElementById('sidebarOverlay');
        if (overlay) overlay.style.display = 'none';
    }

    if (dropdown && notifWrap && !notifWrap.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});
