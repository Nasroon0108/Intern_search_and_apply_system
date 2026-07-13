<script>
(function () {
    var theme = localStorage.getItem('ic-theme');
    var root = document.documentElement;
    if (root.classList.contains('ic-app')) {
        if (theme === 'dark') {
            root.classList.add('ic-content-dark');
        }
    } else if (theme === 'dark') {
        root.setAttribute('data-bs-theme', 'dark');
    }
})();
</script>
