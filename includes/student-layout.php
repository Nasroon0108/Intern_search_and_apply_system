<?php
/**
 * Student portal layout — shared sidebar + topbar.
 * Include at the top of each student page AFTER setting $pageTitle and $currentPage.
 */
$flash    = get_flash();
$initials = strtoupper(substr($student['full_name'] ?? 'S', 0, 1));
?>
<!DOCTYPE html>
<html lang="en" class="ic-app">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require __DIR__ . '/theme-head.php'; ?>
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset_url('assets/css/style.css')) ?>" rel="stylesheet">
    <?php require __DIR__ . '/app-shell-styles.php'; ?>
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="ic-app">
<div class="ds-sidebar-overlay" id="sidebarOverlay"></div>
<div class="ds-shell">

<?php require_once __DIR__ . '/student-sidebar.php'; ?>

<div class="ds-main">
    <div class="ds-topbar">
        <button type="button" class="ds-menu-btn" id="sidebarToggle" aria-label="Open menu">
            <i class="bi bi-list"></i>
        </button>
        <?php if (($currentPage ?? '') !== 'explore'): ?>
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search internships, companies…"
                   onkeydown="if(event.key==='Enter'){window.location='<?= e(app_url('internships.php')) ?>?keyword='+encodeURIComponent(this.value)}">
        </div>
        <?php endif; ?>
        <div class="topbar-right">
            <?php require __DIR__ . '/theme-toggle.php'; ?>
            <button class="notif-btn" aria-label="Notifications"><i class="bi bi-bell"></i><span class="notif-dot"></span></button>
            <a href="<?= e(app_url('student/profile.php')) ?>" class="user-chip" title="My Profile">
                <div class="avatar">
                    <?php if (!empty($student['profile_photo'])): ?>
                        <img src="<?= e(app_url('uploads/photos/' . $student['profile_photo'])) ?>" alt="Profile">
                    <?php else: ?>
                        <?= e($initials) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="u-name"><?= e($student['full_name'] ?? 'Student') ?></div>
                    <div class="u-sub"><?= e($student['university'] ?? 'Student') ?></div>
                </div>
            </a>
            <a href="<?= e(app_url('auth/logout.php')) ?>" class="logout-btn" title="Logout" aria-label="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>

    <div class="ds-body" id="dsBody">
    <?php if ($flash): ?>
        <div class="portal-flash">
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show py-2 px-3 small" role="alert">
                <?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
