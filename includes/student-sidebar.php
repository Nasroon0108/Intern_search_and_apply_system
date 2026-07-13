<?php
/**
 * Shared student sidebar — fixed nav items on every page.
 * Requires $currentPage to be set before include.
 */
$currentPage = $currentPage ?? '';
?>
<aside class="ds-sidebar" id="appSidebar">
    <a href="<?= e(app_url('index.php')) ?>" class="sb-brand">
        <div class="sb-icon"><i class="bi bi-briefcase-fill"></i></div>
        <div><div class="sb-name">InternConnect</div><div class="sb-sub">Insights Hub</div></div>
    </a>
    <nav class="sb-nav">
        <a href="<?= e(app_url('student/dashboard.php')) ?>"    class="<?= ($currentPage==='dashboard')    ? 'active':'' ?>"><i class="bi bi-grid-1x2"></i>             Dashboard</a>
        <a href="<?= e(app_url('internships.php')) ?>"          class="<?= ($currentPage==='explore')      ? 'active':'' ?>"><i class="bi bi-compass"></i>              Explore</a>
        <a href="<?= e(app_url('student/applications.php')) ?>" class="<?= ($currentPage==='applications') ? 'active':'' ?>"><i class="bi bi-send"></i>                 Applications</a>
        <a href="<?= e(app_url('student/saved.php')) ?>"        class="<?= ($currentPage==='saved')        ? 'active':'' ?>"><i class="bi bi-bookmark"></i>             Saved</a>
        <a href="<?= e(app_url('student/profile.php')) ?>"      class="<?= ($currentPage==='profile')      ? 'active':'' ?>"><i class="bi bi-person"></i>               Profile</a>
        <a href="<?= e(app_url('student/skills.php')) ?>"       class="<?= ($currentPage==='skills')       ? 'active':'' ?>"><i class="bi bi-star"></i>                 Skills</a>
        <a href="<?= e(app_url('student/cvs.php')) ?>"          class="<?= ($currentPage==='cvs')          ? 'active':'' ?>"><i class="bi bi-file-earmark-text"></i>    My CVs</a>
    </nav>
</aside>
