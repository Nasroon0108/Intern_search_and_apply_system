<?php
/**
 * Student portal layout — shared sidebar + topbar.
 * Include at the top of each student page AFTER setting $pageTitle and $currentPage.
 * Requires session, auth, config, and database to already be loaded.
 */
$flash    = get_flash();
$initials = strtoupper(substr($student['full_name'] ?? 'S', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; background: #f3f4f8; }
        *, *::before, *::after { box-sizing: border-box; }

        .ds-shell   { display: flex; min-height: 100vh; }
        .ds-sidebar {
            width: 210px; flex-shrink: 0; background: #fff;
            border-right: 1px solid #e8eaf0; display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; overflow-y: auto;
        }
        .ds-main { margin-left: 210px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* Brand */
        .sb-brand {
            padding: 1.25rem 1.25rem .75rem; display: flex; align-items: center;
            gap: .55rem; text-decoration: none;
        }
        .sb-brand .sb-icon {
            width: 34px; height: 34px; border-radius: 8px; background: #1349cc;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1rem; flex-shrink: 0;
        }
        .sb-brand .sb-name { font-weight: 700; font-size: .95rem; color: #111827; line-height: 1.1; }
        .sb-brand .sb-sub  { font-size: .68rem; color: #9ca3af; }

        /* Nav links */
        .sb-nav { flex: 1; padding: .5rem 0; }
        .sb-nav a {
            display: flex; align-items: center; gap: .65rem; padding: .6rem 1.25rem;
            font-size: .875rem; font-weight: 500; color: #6b7280; text-decoration: none;
            transition: background .15s, color .15s; position: relative;
        }
        .sb-nav a:hover  { background: #f3f4f8; color: #111827; }
        .sb-nav a.active { background: #eff3ff; color: #1349cc; font-weight: 600; }
        .sb-nav a.active::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 3px; background: #1349cc; border-radius: 0 2px 2px 0;
        }
        .sb-nav i { font-size: 1rem; }

        /* Topbar */
        .ds-topbar {
            background: #fff; border-bottom: 1px solid #e8eaf0; padding: .75rem 2rem;
            display: flex; align-items: center; gap: 1rem;
            position: sticky; top: 0; z-index: 90;
        }
        .ds-topbar .search-box { flex: 1; max-width: 400px; position: relative; }
        .ds-topbar .search-box input {
            width: 100%; border: 1.5px solid #e8eaf0; border-radius: 2rem;
            padding: .45rem 1rem .45rem 2.5rem; font-size: .85rem; color: #374151;
            background: #f8f9fc; outline: none; transition: border-color .2s;
        }
        .ds-topbar .search-box input:focus { border-color: #1349cc; background: #fff; }
        .ds-topbar .search-box i {
            position: absolute; left: .85rem; top: 50%; transform: translateY(-50%);
            color: #9ca3af; font-size: .9rem;
        }
        .ds-topbar .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 1rem; }
        .notif-btn { position: relative; background: none; border: none; padding: 0; color: #6b7280; font-size: 1.2rem; cursor: pointer; }
        .notif-dot { position: absolute; top: 0; right: 0; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; border: 1.5px solid #fff; }
        .user-chip { display: flex; align-items: center; gap: .6rem; text-decoration: none; color: #111827; }
        .user-chip .avatar {
            width: 36px; height: 36px; border-radius: 50%; background: #1349cc;
            color: #fff; font-weight: 700; font-size: .9rem;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; flex-shrink: 0;
        }
        .user-chip .avatar img {
            width: 100%; height: 100%; object-fit: cover;
        }
        .user-chip .u-name { font-size: .85rem; font-weight: 600; line-height: 1.2; }
        .user-chip .u-sub  { font-size: .72rem; color: #9ca3af; }
        .logout-btn {
            background: none; border: none; padding: .35rem;
            color: #9ca3af; font-size: 1.1rem; cursor: pointer;
            text-decoration: none; display: flex; align-items: center;
        }
        .logout-btn:hover { color: #374151; }

        /* Page body */
        .ds-body { padding: 2rem; flex: 1; }
        .ds-body .page-title { font-size: 1.4rem; font-weight: 800; color: #111827; margin-bottom: .2rem; }
        .ds-body .page-sub   { color: #6b7280; font-size: .875rem; margin-bottom: 1.5rem; }

        /* Cards */
        .ds-card { background: #fff; border: 1px solid #e8eaf0; border-radius: .75rem; }

        /* Footer */
        .ds-footer {
            border-top: 1px solid #e8eaf0; padding: .9rem 2rem; background: #fff;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .5rem;
        }
        .ds-footer span, .ds-footer a { font-size: .75rem; color: #9ca3af; text-decoration: none; }
        .ds-footer a:hover { color: #374151; }

        @media (max-width: 767px) {
            .ds-sidebar { transform: translateX(-100%); transition: transform .25s; }
            .ds-sidebar.open { transform: translateX(0); }
            .ds-main { margin-left: 0; }
        }
    </style>
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>
<div class="ds-shell">

<?php require_once __DIR__ . '/student-sidebar.php'; ?>

<!-- ── Main ── -->
<div class="ds-main">
    <!-- Topbar -->
    <div class="ds-topbar">
        <?php if (($currentPage ?? '') !== 'explore'): ?>
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search internships, companies…"
                   onkeydown="if(event.key==='Enter'){window.location='<?= e(app_url('internships.php')) ?>?keyword='+encodeURIComponent(this.value)}">
        </div>
        <?php endif; ?>
        <div class="topbar-right">
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

    <!-- Flash -->
    <?php if ($flash): ?>
    <div class="px-4 pt-3">
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show py-2 px-3 small" role="alert">
            <?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Page content starts here -->
    <div class="ds-body">
