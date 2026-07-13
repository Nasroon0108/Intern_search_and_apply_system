// InternConnect Sri Lanka - main JS

document.addEventListener('DOMContentLoaded', function () {
    initThemeToggle();

    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        }, 6000);
    });

    // Mobile sidebar toggle
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggle = document.getElementById('sidebarToggle');

    if (sidebar && overlay && toggle) {
        function openSidebar() {
            sidebar.classList.add('open');
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }

        toggle.addEventListener('click', function () {
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
        overlay.addEventListener('click', closeSidebar);
        sidebar.querySelectorAll('.sb-nav a').forEach(function (link) {
            link.addEventListener('click', closeSidebar);
        });
    }
});

function initThemeToggle() {
    var root = document.documentElement;
    var contentEl = document.querySelector('.ds-body');
    var isPortal = root.classList.contains('ic-app');
    var buttons = document.querySelectorAll('#themeToggle, .theme-toggle');

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var next;

            if (isPortal && contentEl) {
                var isDark = root.getAttribute('data-bs-theme') === 'dark';
                next = isDark ? 'light' : 'dark';

                if (next === 'dark') {
                    root.setAttribute('data-bs-theme', 'dark');
                    root.classList.add('ic-content-dark');
                    contentEl.removeAttribute('data-bs-theme');
                } else {
                    root.removeAttribute('data-bs-theme');
                    root.classList.remove('ic-content-dark');
                    contentEl.removeAttribute('data-bs-theme');
                }
            } else {
                var isDarkRoot = root.getAttribute('data-bs-theme') === 'dark';
                next = isDarkRoot ? 'light' : 'dark';

                if (next === 'dark') {
                    root.setAttribute('data-bs-theme', 'dark');
                } else {
                    root.removeAttribute('data-bs-theme');
                }
            }

            localStorage.setItem('ic-theme', next);
        });
    });
}
