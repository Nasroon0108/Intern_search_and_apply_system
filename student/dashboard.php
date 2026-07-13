<?php
$pageTitle = 'Dashboard';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
require_role(ROLE_STUDENT);

$userId  = current_user_id();
$student = get_student_by_user_id($mysqli, $userId);

// Stats
$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM applications WHERE student_id = ?');
$stmt->bind_param('i', $student['id']); $stmt->execute();
$totalApplications = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

$s1 = 'shortlisted'; $s2 = 'interview';
$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM applications WHERE student_id = ? AND status IN (?,?)');
$stmt->bind_param('iss', $student['id'], $s1, $s2); $stmt->execute();
$shortlisted = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

$s3 = 'accepted';
$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM applications WHERE student_id = ? AND status = ?');
$stmt->bind_param('is', $student['id'], $s3); $stmt->execute();
$accepted = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM favorites WHERE student_id = ?');
$stmt->bind_param('i', $student['id']); $stmt->execute();
$savedCount = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

// Recent applications
$stmt = $mysqli->prepare(
    'SELECT a.*, i.title, i.work_type, c.company_name
     FROM applications a
     JOIN internships i ON i.id = a.internship_id
     JOIN companies   c ON c.id  = i.company_id
     WHERE a.student_id = ?
     ORDER BY a.applied_at DESC LIMIT 5'
);
$stmt->bind_param('i', $student['id']); $stmt->execute();
$recentApps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

$flash = get_flash();
$profilePct = (int)($student['profile_completion'] ?? 0);
$initials   = strtoupper(substr($student['full_name'] ?? 'S', 0, 1));
$currentPage = 'dashboard';
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
        /* ── Reset & base ── */
        html, body { height: 100%; margin: 0; background: #f3f4f8; }
        *, *::before, *::after { box-sizing: border-box; }

        /* ── Layout shell ── */
        .ds-shell   { display: flex; min-height: 100vh; }
        .ds-sidebar {
            width: 210px; flex-shrink: 0;
            background: #fff;
            border-right: 1px solid #e8eaf0;
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; height: 100vh;
            z-index: 100; overflow-y: auto;
        }
        .ds-main {
            margin-left: 210px; flex: 1;
            display: flex; flex-direction: column; min-height: 100vh;
        }

        /* ── Sidebar brand ── */
        .sb-brand {
            padding: 1.25rem 1.25rem .75rem;
            display: flex; align-items: center; gap: .55rem;
            text-decoration: none;
        }
        .sb-brand .sb-icon {
            width: 34px; height: 34px; border-radius: 8px;
            background: #1349cc;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1rem; flex-shrink: 0;
        }
        .sb-brand .sb-name  { font-weight: 700; font-size: .95rem; color: #111827; line-height: 1.1; }
        .sb-brand .sb-sub   { font-size: .68rem; color: #9ca3af; }

        /* ── Sidebar nav ── */
        .sb-nav { flex: 1; padding: .5rem 0; }
        .sb-nav a {
            display: flex; align-items: center; gap: .65rem;
            padding: .6rem 1.25rem; font-size: .875rem; font-weight: 500;
            color: #6b7280; text-decoration: none; border-radius: 0;
            transition: background .15s, color .15s;
            position: relative;
        }
        .sb-nav a:hover  { background: #f3f4f8; color: #111827; }
        .sb-nav a.active {
            background: #eff3ff; color: #1349cc; font-weight: 600;
        }
        .sb-nav a.active::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0;
            width: 3px; background: #1349cc; border-radius: 0 2px 2px 0;
        }
        .sb-nav i { font-size: 1rem; }

        /* ── Sidebar footer ── */
        .sb-footer { padding: 1rem 1.25rem; border-top: 1px solid #f0f1f5; }
        .sb-footer a {
            display: flex; align-items: center; gap: .6rem;
            font-size: .825rem; color: #9ca3af; text-decoration: none; padding: .35rem 0;
        }
        .sb-footer a:hover { color: #374151; }

        /* ── New Application CTA ── */
        .sb-cta {
            margin: .75rem 1rem 1rem;
            background: #1349cc; color: #fff; border: none;
            border-radius: .6rem; padding: .65rem 1rem;
            font-size: .85rem; font-weight: 600; width: calc(100% - 2rem);
            display: flex; align-items: center; justify-content: center; gap: .4rem;
            cursor: pointer; text-decoration: none; transition: background .2s;
        }
        .sb-cta:hover { background: #1038a8; color: #fff; }
    </style>
</head>
<body>
<div class="ds-shell">

<!-- ════════════════ SIDEBAR ════════════════ -->
<aside class="ds-sidebar">
    <a href="<?= e(app_url('index.php')) ?>" class="sb-brand">
        <div class="sb-icon"><i class="bi bi-briefcase-fill"></i></div>
        <div>
            <div class="sb-name">InternConnect</div>
            <div class="sb-sub">Insights Hub</div>
        </div>
    </a>

    <nav class="sb-nav">
        <a href="<?= e(app_url('internships.php')) ?>"><i class="bi bi-compass"></i> Explore</a>
        <a href="<?= e(app_url('student/dashboard.php')) ?>" class="active"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a href="<?= e(app_url('student/applications.php')) ?>"><i class="bi bi-send"></i> Applications</a>
        <a href="<?= e(app_url('student/saved.php')) ?>"><i class="bi bi-bookmark"></i> Saved</a>
        <a href="<?= e(app_url('student/profile.php')) ?>"><i class="bi bi-person"></i> Profile</a>
        <a href="<?= e(app_url('student/skills.php')) ?>"><i class="bi bi-star"></i> Skills</a>
        <a href="<?= e(app_url('student/cvs.php')) ?>"><i class="bi bi-file-earmark-text"></i> My CVs</a>
    </nav>

    <a href="<?= e(app_url('internships.php')) ?>" class="sb-cta">
        <i class="bi bi-plus-lg"></i> New Application
    </a>

    <div class="sb-footer">
        <a href="<?= e(app_url('student/profile.php')) ?>"><i class="bi bi-gear"></i> Settings</a>
        <a href="<?= e(app_url('auth/logout.php')) ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</aside>
<!-- ════════════════ END SIDEBAR ════════════════ -->


<!-- ════════════════ MAIN CONTENT ════════════════ -->
<div class="ds-main">

    <!-- Top bar -->
    <style>
        .ds-topbar {
            background: #fff; border-bottom: 1px solid #e8eaf0;
            padding: .75rem 2rem; display: flex; align-items: center; gap: 1rem;
            position: sticky; top: 0; z-index: 90;
        }
        .ds-topbar .search-box {
            flex: 1; max-width: 400px;
            position: relative;
        }
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
        .ds-topbar .topbar-right {
            margin-left: auto; display: flex; align-items: center; gap: 1rem;
        }
        .ds-topbar .notif-btn {
            position: relative; background: none; border: none; padding: 0;
            color: #6b7280; font-size: 1.2rem; cursor: pointer;
        }
        .ds-topbar .notif-dot {
            position: absolute; top: 0; right: 0; width: 8px; height: 8px;
            background: #ef4444; border-radius: 50%; border: 1.5px solid #fff;
        }
        .ds-topbar .user-chip {
            display: flex; align-items: center; gap: .6rem;
            text-decoration: none; color: #111827;
        }
        .ds-topbar .user-chip .avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: #1349cc; color: #fff; font-weight: 700;
            font-size: .9rem; display: flex; align-items: center; justify-content: center;
        }
        .ds-topbar .user-chip .u-name { font-size: .85rem; font-weight: 600; line-height: 1.2; }
        .ds-topbar .user-chip .u-sub  { font-size: .72rem; color: #9ca3af; }
    </style>

    <div class="ds-topbar">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Search internships, companies…">
        </div>
        <div class="topbar-right">
            <button class="notif-btn" aria-label="Notifications">
                <i class="bi bi-bell"></i>
                <span class="notif-dot"></span>
            </button>
            <a href="<?= e(app_url('student/profile.php')) ?>" class="user-chip">
                <div class="avatar"><?= e($initials) ?></div>
                <div>
                    <div class="u-name"><?= e($student['full_name'] ?? 'Student') ?></div>
                    <div class="u-sub"><?= e($student['university'] ?? 'Student') ?></div>
                </div>
            </a>
        </div>
    </div>


    <!-- Flash message -->
    <?php if ($flash): ?>
    <div class="px-4 pt-3">
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show py-2 px-3 small" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Page body -->
    <style>
        .ds-body { padding: 2rem; flex: 1; }

        /* ── Page header ── */
        .page-hd { margin-bottom: 1.75rem; }
        .page-hd h1 { font-size: 1.6rem; font-weight: 800; color: #111827; margin-bottom: .2rem; }
        .page-hd p  { color: #6b7280; font-size: .875rem; margin: 0; }
        .page-hd .hd-actions { display: flex; gap: .75rem; margin-top: 1rem; flex-wrap: wrap; }
        .btn-export {
            border: 1.5px solid #d1d5db; background: #fff; border-radius: .55rem;
            padding: .45rem 1rem; font-size: .825rem; font-weight: 500; color: #374151;
            display: flex; align-items: center; gap: .4rem; cursor: pointer;
            text-decoration: none; transition: border-color .2s;
        }
        .btn-export:hover { border-color: #1349cc; color: #1349cc; }
        .btn-new-entry {
            background: #1349cc; color: #fff; border: none;
            border-radius: .55rem; padding: .45rem 1.1rem;
            font-size: .825rem; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: .4rem; text-decoration: none;
            transition: background .2s;
        }
        .btn-new-entry:hover { background: #1038a8; color: #fff; }

        /* ── Stat cards ── */
        .stat-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 1.5rem; }
        @media(max-width:900px){ .stat-grid { grid-template-columns: repeat(2,1fr); } }
        @media(max-width:540px){ .stat-grid { grid-template-columns: 1fr; } }

        .stat-card {
            background: #fff; border: 1px solid #e8eaf0; border-radius: .75rem;
            padding: 1.25rem 1.25rem 1rem; position: relative; overflow: hidden;
        }
        .stat-card .sc-label { font-size: .72rem; font-weight: 600; letter-spacing: .07em;
            text-transform: uppercase; color: #9ca3af; margin-bottom: .5rem; }
        .stat-card .sc-value { font-size: 1.9rem; font-weight: 800; color: #111827; line-height: 1; margin-bottom: .3rem; }
        .stat-card .sc-sub   { font-size: .75rem; color: #9ca3af; margin-bottom: .6rem; }
        .stat-card .sc-bar   { height: 4px; border-radius: 2px; background: #e8eaf0; overflow: hidden; }
        .stat-card .sc-bar-fill { height: 100%; border-radius: 2px; }
        .stat-card .sc-badge {
            position: absolute; top: 1rem; right: 1rem;
            font-size: .7rem; font-weight: 600; padding: .15rem .55rem;
            border-radius: 2rem;
        }
        .sc-icon {
            width: 36px; height: 36px; border-radius: .5rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; margin-bottom: .75rem;
        }
    </style>

    <div class="ds-body">
        <!-- Header -->
        <div class="page-hd d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                <h1>Application Analytics</h1>
                <p>Real-time tracking and competitive insights for your internship journey.</p>
            </div>
            <div class="hd-actions">
                <a href="<?= e(app_url('student/applications.php')) ?>" class="btn-export">
                    <i class="bi bi-arrow-up-right-circle"></i> View All
                </a>
                <a href="<?= e(app_url('internships.php')) ?>" class="btn-new-entry">
                    <i class="bi bi-plus-lg"></i> New Entry
                </a>
            </div>
        </div>


        <!-- ── Stat cards ── -->
        <?php
        $pendingStatus = 'pending';
        $stmtPending = $mysqli->prepare('SELECT COUNT(*) as total FROM applications WHERE student_id = ? AND status = ?');
        $stmtPending->bind_param('is', $student['id'], $pendingStatus); $stmtPending->execute();
        $pending = $stmtPending->get_result()->fetch_assoc()['total']; $stmtPending->close();
        $successRate = $totalApplications > 0 ? round(($accepted / $totalApplications) * 100) : 0;
        ?>
        <div class="stat-grid">
            <!-- Total Applications -->
            <div class="stat-card">
                <span class="sc-badge" style="background:#eff6ff;color:#1349cc;">
                    +<?= e($totalApplications) ?>
                </span>
                <div class="sc-icon" style="background:#eff6ff;color:#1349cc;">
                    <i class="bi bi-send-check"></i>
                </div>
                <div class="sc-label">Total Applications</div>
                <div class="sc-value"><?= e($totalApplications) ?></div>
                <div class="sc-sub">All submitted applications</div>
                <div class="sc-bar">
                    <div class="sc-bar-fill" style="width:<?= min(100, $totalApplications * 10) ?>%;background:#1349cc;"></div>
                </div>
            </div>
            <!-- Shortlisted -->
            <div class="stat-card">
                <span class="sc-badge" style="background:#f0fdf4;color:#16a34a;">
                    <?= $totalApplications > 0 ? round(($shortlisted/$totalApplications)*100) : 0 ?>%
                </span>
                <div class="sc-icon" style="background:#f0fdf4;color:#16a34a;">
                    <i class="bi bi-trophy"></i>
                </div>
                <div class="sc-label">Shortlisted</div>
                <div class="sc-value"><?= e($shortlisted) ?></div>
                <div class="sc-sub">Interview &amp; shortlist stage</div>
                <div class="sc-bar">
                    <div class="sc-bar-fill" style="width:<?= $totalApplications > 0 ? round(($shortlisted/$totalApplications)*100) : 0 ?>%;background:#16a34a;"></div>
                </div>
            </div>
            <!-- Profile Health -->
            <div class="stat-card">
                <span class="sc-badge" style="background:#f0fdf4;color:#16a34a;">OPTIMAL</span>
                <div class="sc-icon" style="background:#fff7ed;color:#ea580c;">
                    <i class="bi bi-person-check"></i>
                </div>
                <div class="sc-label">Profile Health</div>
                <div class="sc-value"><?= e($profilePct) ?>%</div>
                <div class="sc-sub">Based on profile completeness</div>
                <div class="sc-bar">
                    <div class="sc-bar-fill" style="width:<?= e($profilePct) ?>%;background:<?= $profilePct >= 70 ? '#16a34a' : ($profilePct >= 40 ? '#ea580c' : '#ef4444') ?>;"></div>
                </div>
            </div>
            <!-- Offers -->
            <div class="stat-card">
                <span class="sc-badge" style="background:#fef9c3;color:#ca8a04;">Avg</span>
                <div class="sc-icon" style="background:#fef2f2;color:#ef4444;">
                    <i class="bi bi-patch-check"></i>
                </div>
                <div class="sc-label">Offers Received</div>
                <div class="sc-value"><?= e($accepted) ?></div>
                <div class="sc-sub">Accepted / total applied</div>
                <div class="sc-bar">
                    <div class="sc-bar-fill" style="width:<?= $successRate ?>%;background:#ef4444;"></div>
                </div>
            </div>
        </div>


        <!-- ── Middle row: Activity + Profile panel ── -->
        <style>
            .mid-grid { display: grid; grid-template-columns: 1fr 300px; gap: 1rem; margin-bottom: 1.5rem; }
            @media(max-width:900px){ .mid-grid { grid-template-columns: 1fr; } }

            .ds-card {
                background: #fff; border: 1px solid #e8eaf0;
                border-radius: .75rem; padding: 1.5rem;
            }
            .ds-card .card-title { font-size: 1rem; font-weight: 700; color: #111827; margin-bottom: .2rem; }
            .ds-card .card-sub   { font-size: .8rem; color: #9ca3af; margin-bottom: 1.25rem; }

            /* Activity placeholder chart */
            .activity-bars {
                display: flex; align-items: flex-end; gap: .5rem;
                height: 120px; padding-top: 1rem;
            }
            .activity-bars .bar-group { display: flex; gap: 3px; align-items: flex-end; flex: 1; }
            .activity-bars .bar {
                flex: 1; border-radius: 3px 3px 0 0;
                min-width: 8px;
            }
            .bar-labels { display: flex; justify-content: space-between; margin-top: .5rem; }
            .bar-labels span { font-size: .7rem; color: #9ca3af; flex: 1; text-align: center; }

            /* Profile completion card */
            .profile-ring-wrap { text-align: center; margin-bottom: 1.25rem; }
            .profile-ring {
                width: 90px; height: 90px; border-radius: 50%;
                background: conic-gradient(#1349cc <?= $profilePct * 3.6 ?>deg, #e8eaf0 0deg);
                display: flex; align-items: center; justify-content: center;
                margin: 0 auto .5rem;
            }
            .profile-ring-inner {
                width: 68px; height: 68px; background: #fff; border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                font-weight: 800; font-size: 1rem; color: #111827;
            }
            .profile-item {
                display: flex; justify-content: space-between; align-items: center;
                font-size: .8rem; color: #374151; margin-bottom: .6rem;
            }
            .profile-item .pi-label { display: flex; align-items: center; gap: .4rem; }
            .profile-item .pi-pct   { font-weight: 600; color: #1349cc; font-size: .8rem; }
            .pi-bar { flex: 1; height: 4px; background: #e8eaf0; border-radius: 2px; margin: 0 .75rem; overflow: hidden; }
            .pi-bar-fill { height: 100%; background: #1349cc; border-radius: 2px; }
        </style>

        <div class="mid-grid">
            <!-- Application Activity -->
            <div class="ds-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="card-title">Application Activity</div>
                        <div class="card-sub">Monthly volume of applications vs. interview invites</div>
                    </div>
                    <span style="font-size:.75rem;background:#f3f4f8;border-radius:.4rem;padding:.25rem .65rem;color:#374151;font-weight:500;">Last 6 Months</span>
                </div>
                <?php
                // Build 6-month bar data
                $months = []; $appCounts = []; $ivCounts = [];
                for ($i = 5; $i >= 0; $i--) {
                    $ts = strtotime("-$i months");
                    $months[]   = date('M', $ts);
                    $ym         = date('Y-m', $ts);
                    $stmtM = $mysqli->prepare("SELECT COUNT(*) as c FROM applications a WHERE a.student_id = ? AND DATE_FORMAT(a.applied_at,'%Y-%m') = ?");
                    $stmtM->bind_param('is', $student['id'], $ym); $stmtM->execute();
                    $appCounts[] = (int)$stmtM->get_result()->fetch_assoc()['c']; $stmtM->close();

                    $sIV = 'interview';
                    $stmtI = $mysqli->prepare("SELECT COUNT(*) as c FROM applications a WHERE a.student_id = ? AND a.status = ? AND DATE_FORMAT(a.applied_at,'%Y-%m') = ?");
                    $stmtI->bind_param('iss', $student['id'], $sIV, $ym); $stmtI->execute();
                    $ivCounts[] = (int)$stmtI->get_result()->fetch_assoc()['c']; $stmtI->close();
                }
                $maxBar = max(max($appCounts), 1);
                ?>
                <div class="activity-bars">
                    <?php for ($i = 0; $i < 6; $i++):
                        $appH = round(($appCounts[$i] / $maxBar) * 100);
                        $ivH  = round(($ivCounts[$i]  / $maxBar) * 100);
                    ?>
                    <div class="bar-group">
                        <div class="bar" style="height:<?= max(6, $appH) ?>%;background:#1349cc;opacity:.85;" title="Applications: <?= $appCounts[$i] ?>"></div>
                        <div class="bar" style="height:<?= max(4, $ivH) ?>%;background:#93c5fd;" title="Interviews: <?= $ivCounts[$i] ?>"></div>
                    </div>
                    <?php endfor; ?>
                </div>
                <div class="bar-labels">
                    <?php foreach ($months as $m): ?>
                        <span><?= e($m) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex gap-3 mt-2">
                    <span style="font-size:.72rem;color:#374151;display:flex;align-items:center;gap:.3rem;">
                        <span style="width:10px;height:10px;background:#1349cc;border-radius:2px;display:inline-block;"></span> Applications
                    </span>
                    <span style="font-size:.72rem;color:#374151;display:flex;align-items:center;gap:.3rem;">
                        <span style="width:10px;height:10px;background:#93c5fd;border-radius:2px;display:inline-block;"></span> Interviews
                    </span>
                </div>
            </div>

            <!-- Profile Health panel -->
            <div class="ds-card">
                <div class="card-title">Profile Health</div>
                <div class="card-sub">Current completion vs. market requirements</div>
                <div class="profile-ring-wrap">
                    <div class="profile-ring"><div class="profile-ring-inner"><?= e($profilePct) ?>%</div></div>
                    <div style="font-size:.8rem;color:#6b7280;"><?= $profilePct >= 70 ? 'Looking great!' : 'Needs attention' ?></div>
                </div>
                <?php
                $sections = [
                    ['label' => 'Basic Info',     'icon' => 'bi-person',            'pct' => $student['full_name']  ? 100 : 0],
                    ['label' => 'Education',       'icon' => 'bi-mortarboard',        'pct' => $student['university'] ? 100 : 0],
                    ['label' => 'Skills',          'icon' => 'bi-star',               'pct' => 0],
                    ['label' => 'CV Uploaded',     'icon' => 'bi-file-earmark-text',  'pct' => 0],
                ];
                // Check skills
                $stmtSk = $mysqli->prepare('SELECT COUNT(*) as c FROM student_skills WHERE student_id = ?');
                $stmtSk->bind_param('i', $student['id']); $stmtSk->execute();
                $skCount = (int)$stmtSk->get_result()->fetch_assoc()['c']; $stmtSk->close();
                $sections[2]['pct'] = $skCount > 0 ? min(100, $skCount * 20) : 0;
                // Check CVs
                $stmtCv = $mysqli->prepare('SELECT COUNT(*) as c FROM student_cvs WHERE student_id = ?');
                $stmtCv->bind_param('i', $student['id']); $stmtCv->execute();
                $cvCount = (int)$stmtCv->get_result()->fetch_assoc()['c']; $stmtCv->close();
                $sections[3]['pct'] = $cvCount > 0 ? 100 : 0;
                ?>
                <?php foreach ($sections as $sec): ?>
                <div class="profile-item">
                    <div class="pi-label"><i class="bi <?= e($sec['icon']) ?>" style="color:#6b7280;"></i> <?= e($sec['label']) ?></div>
                    <div class="pi-bar"><div class="pi-bar-fill" style="width:<?= $sec['pct'] ?>%;"></div></div>
                    <div class="pi-pct"><?= $sec['pct'] ?>%</div>
                </div>
                <?php endforeach; ?>
                <a href="<?= e(app_url('student/profile.php')) ?>"
                   style="display:block;text-align:center;margin-top:1rem;font-size:.82rem;font-weight:600;color:#1349cc;background:#eff3ff;border-radius:.5rem;padding:.55rem;text-decoration:none;">
                    View Full Profile
                </a>
            </div>
        </div>


        <!-- ── Live Application Tracker ── -->
        <style>
            .tracker-card {
                background: #fff; border: 1px solid #e8eaf0;
                border-radius: .75rem; padding: 1.5rem;
            }
            .tracker-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; flex-wrap: wrap; gap: .75rem; }
            .tracker-header h2 { font-size: 1rem; font-weight: 700; color: #111827; margin: 0 0 .15rem; }
            .tracker-header p  { font-size: .8rem; color: #9ca3af; margin: 0; }
            .tracker-tabs { display: flex; align-items: center; gap: .5rem; }
            .tracker-tabs .ttab {
                font-size: .78rem; font-weight: 500; padding: .3rem .8rem;
                border-radius: 2rem; border: 1.5px solid #e8eaf0; background: #fff;
                cursor: pointer; color: #6b7280; text-decoration: none; transition: all .15s;
            }
            .tracker-tabs .ttab.active,
            .tracker-tabs .ttab:hover { background: #1349cc; color: #fff; border-color: #1349cc; }
            .tracker-tabs .ttab-link {
                font-size: .78rem; color: #1349cc; text-decoration: none; font-weight: 500;
                padding: .3rem .6rem;
            }

            /* Table */
            .app-table { width: 100%; border-collapse: collapse; }
            .app-table th {
                font-size: .72rem; font-weight: 600; color: #9ca3af; text-transform: uppercase;
                letter-spacing: .06em; padding: .6rem .75rem; border-bottom: 1px solid #e8eaf0;
                text-align: left; white-space: nowrap;
            }
            .app-table td { padding: 1rem .75rem; border-bottom: 1px solid #f3f4f8; vertical-align: middle; }
            .app-table tr:last-child td { border-bottom: none; }
            .app-table tr:hover td { background: #fafbff; }

            .co-avatar {
                width: 34px; height: 34px; border-radius: .5rem;
                background: #eff3ff; color: #1349cc; font-weight: 700;
                font-size: .85rem; display: flex; align-items: center; justify-content: center;
                flex-shrink: 0;
            }
            .co-name    { font-size: .875rem; font-weight: 600; color: #111827; }
            .co-sub     { font-size: .75rem; color: #9ca3af; margin-top: .1rem; }

            .status-badge {
                font-size: .7rem; font-weight: 700; padding: .25rem .65rem;
                border-radius: .35rem; text-transform: uppercase; letter-spacing: .04em;
                white-space: nowrap;
            }
            .st-pending    { background: #fef9c3; color: #854d0e; }
            .st-shortlisted{ background: #dbeafe; color: #1d4ed8; }
            .st-interview  { background: #dcfce7; color: #166534; }
            .st-accepted   { background: #d1fae5; color: #065f46; }
            .st-rejected   { background: #fee2e2; color: #991b1b; }

            .empty-state { text-align: center; padding: 3rem 1rem; color: #9ca3af; }
            .empty-state i { font-size: 2.5rem; display: block; margin-bottom: .75rem; color: #d1d5db; }
        </style>

        <div class="tracker-card">
            <div class="tracker-header">
                <div>
                    <h2>Live Application Tracker</h2>
                    <p>Detailed status of your recent applications</p>
                </div>
                <div class="tracker-tabs">
                    <span class="ttab active">All</span>
                    <a href="<?= e(app_url('student/applications.php')) ?>" class="ttab">Active</a>
                    <a href="<?= e(app_url('student/applications.php')) ?>" class="ttab-link">View Historical →</a>
                </div>
            </div>

            <?php if (count($recentApps) > 0): ?>
            <div style="overflow-x:auto;">
            <table class="app-table">
                <thead>
                    <tr>
                        <th>Company &amp; Role</th>
                        <th>Type</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentApps as $app):
                        $stClass = match($app['status']) {
                            'pending'     => 'st-pending',
                            'shortlisted' => 'st-shortlisted',
                            'interview'   => 'st-interview',
                            'accepted'    => 'st-accepted',
                            'rejected'    => 'st-rejected',
                            default       => 'st-pending'
                        };
                        $stLabel = match($app['status']) {
                            'pending'     => 'Under Review',
                            'shortlisted' => 'Shortlisted',
                            'interview'   => 'Interview Scheduled',
                            'accepted'    => 'Offer Received',
                            'rejected'    => 'Not Selected',
                            default       => ucfirst($app['status'])
                        };
                        $co1 = strtoupper(substr($app['company_name'], 0, 1));
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:.75rem;">
                                <div class="co-avatar"><?= e($co1) ?></div>
                                <div>
                                    <div class="co-name"><?= e($app['company_name']) ?></div>
                                    <div class="co-sub"><?= e($app['title']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span style="font-size:.75rem;color:#6b7280;"><?= e($app['work_type'] ?? '—') ?></span></td>
                        <td><span style="font-size:.78rem;color:#6b7280;"><?= e(date('M j, Y', strtotime($app['applied_at']))) ?></span></td>
                        <td><span class="status-badge <?= e($stClass) ?>"><?= e($stLabel) ?></span></td>
                        <td>
                            <a href="<?= e(app_url('student/applications.php')) ?>"
                               style="color:#9ca3af;font-size:1rem;text-decoration:none;" title="View">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-send"></i>
                <p class="mb-1">No applications yet.</p>
                <a href="<?= e(app_url('internships.php')) ?>" style="color:#1349cc;font-size:.875rem;font-weight:600;">Browse internships →</a>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- ds-body -->


    <!-- Footer -->
    <footer style="border-top:1px solid #e8eaf0;padding:.9rem 2rem;background:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.5rem;">
        <span style="font-size:.75rem;color:#9ca3af;">
            &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.
        </span>
        <div style="display:flex;gap:1.25rem;">
            <a href="#" style="font-size:.75rem;color:#9ca3af;text-decoration:none;">Privacy &amp; Data Policy</a>
            <a href="#" style="font-size:.75rem;color:#9ca3af;text-decoration:none;">Technical Support</a>
        </div>
    </footer>

</div><!-- ds-main -->
</div><!-- ds-shell -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(app_url('assets/js/main.js')) ?>"></script>
</body>
</html>
