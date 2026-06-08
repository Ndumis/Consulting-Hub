// Mobile sidebar toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}

// Toggle notifications dropdown
function toggleNotifications(){
    const d=document.getElementById('notificationsDropdown');
    if(!d) return;
    const cur=getComputedStyle(d).display;
    d.style.display = (cur==='none') ? 'block' : 'none';
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.querySelector('.menu-toggle');
    const dropdown = document.getElementById('notificationsDropdown');
    const notificationIcon = document.querySelector('.notification-icon');
    
    if (window.innerWidth <= 768 && 
        !sidebar.contains(event.target) && 
        !menuToggle.contains(event.target) && 
        sidebar.classList.contains('active')) {
        sidebar.classList.remove('active');
    }
    
    // Close notifications if clicked outside
    if (dropdown && !dropdown.contains(event.target) && !notificationIcon.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});
