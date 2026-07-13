<script>
(function () {
    var theme = localStorage.getItem('ic-theme');
    var root = document.documentElement;
    if (theme === 'dark') {
        root.setAttribute('data-bs-theme', 'dark');
        if (root.classList.contains('ic-app')) {
            root.classList.add('ic-content-dark');
        }
    }
})();
</script>
