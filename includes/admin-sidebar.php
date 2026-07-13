<?php
$currentPage = $currentPage ?? '';
?>
<aside class="ds-sidebar" id="appSidebar">
    <a href="<?= e(app_url('index.php')) ?>" class="sb-brand">
        <div class="sb-icon"><i class="bi bi-shield-check"></i></div>
        <div><div class="sb-name">InternConnect</div><div class="sb-sub">Admin Panel</div></div>
    </a>
    <nav class="sb-nav">
        <a href="<?= e(app_url('admin/dashboard.php')) ?>"        class="<?= ($currentPage === 'dashboard') ? 'active' : '' ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a href="<?= e(app_url('admin/users.php')) ?>"            class="<?= ($currentPage === 'users') ? 'active' : '' ?>"><i class="bi bi-people"></i> Users</a>
        <a href="<?= e(app_url('admin/companies.php')) ?>"        class="<?= ($currentPage === 'companies') ? 'active' : '' ?>"><i class="bi bi-building"></i> Companies</a>
        <a href="<?= e(app_url('admin/internships.php')) ?>"      class="<?= ($currentPage === 'internships') ? 'active' : '' ?>"><i class="bi bi-briefcase"></i> Internships</a>
    </nav>
</aside>
