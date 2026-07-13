<?php
/**
 * Company / Admin portal layout.
 * Requires: $pageTitle, $currentPage, $portalType ('company'|'admin')
 * Company pages also need $company array.
 */
$flash = get_flash();
$portalType = $portalType ?? 'company';
$portalName = $portalType === 'admin'
    ? 'Administrator'
    : ($company['company_name'] ?? 'Company');
$portalSub = $portalType === 'admin' ? 'Admin Panel' : 'Company Account';
$initials = strtoupper(substr($portalName, 0, 1));
$profileUrl = $portalType === 'admin'
    ? app_url('admin/dashboard.php')
    : app_url('company/profile.php');
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

<?php
if ($portalType === 'admin') {
    require_once __DIR__ . '/admin-sidebar.php';
} else {
    require_once __DIR__ . '/company-sidebar.php';
}
?>

<div class="ds-main">
    <div class="ds-topbar">
        <button type="button" class="ds-menu-btn" id="sidebarToggle" aria-label="Open menu">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title d-none d-md-block"><?= e($pageTitle) ?></div>
        <div class="topbar-right">
            <?php require __DIR__ . '/theme-toggle.php'; ?>
            <a href="<?= e($profileUrl) ?>" class="user-chip" title="Account">
                <div class="avatar">
                    <?php if ($portalType === 'company' && !empty($company['logo'])): ?>
                        <img src="<?= e(app_url('uploads/logos/' . $company['logo'])) ?>" alt="Logo">
                    <?php else: ?>
                        <?= e($initials) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="u-name"><?= e($portalName) ?></div>
                    <div class="u-sub"><?= e($portalSub) ?></div>
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
