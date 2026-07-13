<?php
$currentPage = $currentPage ?? '';
?>
<aside class="ds-sidebar" id="appSidebar">
    <a href="<?= e(app_url('index.php')) ?>" class="sb-brand">
        <div class="sb-icon"><i class="bi bi-building"></i></div>
        <div><div class="sb-name">InternConnect</div><div class="sb-sub">Company Portal</div></div>
    </a>
    <nav class="sb-nav">
        <a href="<?= e(app_url('company/dashboard.php')) ?>"     class="<?= ($currentPage === 'dashboard') ? 'active' : '' ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a href="<?= e(app_url('company/internships.php')) ?>"    class="<?= ($currentPage === 'internships') ? 'active' : '' ?>"><i class="bi bi-briefcase"></i> My Internships</a>
        <a href="<?= e(app_url('company/post-internship.php')) ?>" class="<?= ($currentPage === 'post') ? 'active' : '' ?>"><i class="bi bi-plus-circle"></i> Post Internship</a>
        <a href="<?= e(app_url('company/applications.php')) ?>"   class="<?= ($currentPage === 'applications') ? 'active' : '' ?>"><i class="bi bi-inbox"></i> Applications</a>
        <a href="<?= e(app_url('company/profile.php')) ?>"       class="<?= ($currentPage === 'profile') ? 'active' : '' ?>"><i class="bi bi-building-gear"></i> Company Profile</a>
    </nav>
</aside>
