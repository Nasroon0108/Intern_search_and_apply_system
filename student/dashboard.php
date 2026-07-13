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

$profilePct = (int)($student['profile_completion'] ?? 0);
$currentPage = 'dashboard';
$extraHead = <<<'HTML'
<style>
        .page-hd { margin-bottom: 1.75rem; }
        .page-hd h1 { font-size: 1.6rem; font-weight: 800; color: var(--ic-text); margin-bottom: .2rem; }
        .page-hd p  { color: var(--ic-text-muted); font-size: .875rem; margin: 0; }
        .page-hd .hd-actions { display: flex; gap: .75rem; margin-top: 1rem; flex-wrap: wrap; }
        .btn-export {
            border: 1.5px solid var(--ic-border); background: var(--ic-surface); border-radius: .55rem;
            padding: .45rem 1rem; font-size: .825rem; font-weight: 500; color: var(--ic-text-muted);
            display: flex; align-items: center; gap: .4rem; cursor: pointer;
            text-decoration: none; transition: border-color .2s;
        }
        .btn-export:hover { border-color: var(--ic-primary); color: var(--ic-primary); }
        .stat-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 1.5rem; }
        @media(max-width:900px){ .stat-grid { grid-template-columns: repeat(2,1fr); } }
        @media(max-width:540px){ .stat-grid { grid-template-columns: 1fr; } }
        .stat-card {
            background: var(--ic-surface); border: 1px solid var(--ic-border); border-radius: .75rem;
            padding: 1.25rem 1.25rem 1rem; position: relative; overflow: hidden;
        }
        .stat-card .sc-label { font-size: .72rem; font-weight: 600; letter-spacing: .07em;
            text-transform: uppercase; color: var(--ic-text-muted); margin-bottom: .5rem; }
        .stat-card .sc-value { font-size: 1.9rem; font-weight: 800; color: var(--ic-text); line-height: 1; margin-bottom: .3rem; }
        .stat-card .sc-sub   { font-size: .75rem; color: var(--ic-text-light); margin-bottom: .6rem; }
        .stat-card .sc-bar   { height: 4px; border-radius: 2px; background: var(--ic-border); overflow: hidden; }
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
HTML;
require_once dirname(__DIR__) . '/includes/student-layout.php';
?>

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
                <span class="sc-badge sc-badge-blue">
                    +<?= e($totalApplications) ?>
                </span>
                <div class="sc-icon sc-icon-blue">
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
                <span class="sc-badge sc-badge-green">
                    <?= $totalApplications > 0 ? round(($shortlisted/$totalApplications)*100) : 0 ?>%
                </span>
                <div class="sc-icon sc-icon-green">
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
                <span class="sc-badge sc-badge-green">OPTIMAL</span>
                <div class="sc-icon sc-icon-orange">
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
                <span class="sc-badge sc-badge-amber">Avg</span>
                <div class="sc-icon sc-icon-red">
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
                background: var(--ic-surface); border: 1px solid var(--ic-border);
                border-radius: .75rem; padding: 1.5rem;
            }
            .ds-card .card-title { font-size: 1rem; font-weight: 700; color: var(--ic-text); margin-bottom: .2rem; }
            .ds-card .card-sub   { font-size: .8rem; color: var(--ic-text-light); margin-bottom: 1.25rem; }

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
            .bar-labels span { font-size: .7rem; color: var(--ic-text-light); flex: 1; text-align: center; }

            /* Profile completion card */
            .profile-ring-wrap { text-align: center; margin-bottom: 1.25rem; }
            .profile-ring {
                width: 90px; height: 90px; border-radius: 50%;
                background: conic-gradient(var(--ic-primary) <?= $profilePct * 3.6 ?>deg, var(--ic-border) 0deg);
                display: flex; align-items: center; justify-content: center;
                margin: 0 auto .5rem;
            }
            .profile-ring-inner {
                width: 68px; height: 68px; background: var(--ic-surface); border-radius: 50%;
                display: flex; align-items: center; justify-content: center;
                font-weight: 800; font-size: 1rem; color: var(--ic-text);
            }
            .profile-item {
                display: flex; justify-content: space-between; align-items: center;
                font-size: .8rem; color: var(--ic-text-muted); margin-bottom: .6rem;
            }
            .profile-item .pi-label { display: flex; align-items: center; gap: .4rem; }
            .profile-item .pi-pct   { font-weight: 600; color: var(--ic-primary); font-size: .8rem; }
            .pi-bar { flex: 1; height: 4px; background: var(--ic-border); border-radius: 2px; margin: 0 .75rem; overflow: hidden; }
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
                    <span style="font-size:.75rem;background:var(--ic-bg);border-radius:.4rem;padding:.25rem .65rem;color:var(--ic-text-muted);font-weight:500;">Last 6 Months</span>
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
                    <span style="font-size:.72rem;color:var(--ic-text-muted);display:flex;align-items:center;gap:.3rem;">
                        <span style="width:10px;height:10px;background:#1349cc;border-radius:2px;display:inline-block;"></span> Applications
                    </span>
                    <span style="font-size:.72rem;color:var(--ic-text-muted);display:flex;align-items:center;gap:.3rem;">
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
                    <div style="font-size:.8rem;color:var(--ic-text-muted);"><?= $profilePct >= 70 ? 'Looking great!' : 'Needs attention' ?></div>
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
                    <div class="pi-label"><i class="bi <?= e($sec['icon']) ?>" style="color:var(--ic-text-muted);"></i> <?= e($sec['label']) ?></div>
                    <div class="pi-bar"><div class="pi-bar-fill" style="width:<?= $sec['pct'] ?>%;"></div></div>
                    <div class="pi-pct"><?= $sec['pct'] ?>%</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>


        <!-- ── Live Application Tracker ── -->
        <style>
            .tracker-card {
                background: var(--ic-surface); border: 1px solid var(--ic-border);
                border-radius: .75rem; padding: 1.5rem;
            }
            .tracker-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; flex-wrap: wrap; gap: .75rem; }
            .tracker-header h2 { font-size: 1rem; font-weight: 700; color: var(--ic-text); margin: 0 0 .15rem; }
            .tracker-header p  { font-size: .8rem; color: var(--ic-text-light); margin: 0; }
            .tracker-tabs { display: flex; align-items: center; gap: .5rem; }
            .tracker-tabs .ttab {
                font-size: .78rem; font-weight: 500; padding: .3rem .8rem;
                border-radius: 2rem; border: 1.5px solid var(--ic-border); background: var(--ic-surface);
                cursor: pointer; color: var(--ic-text-muted); text-decoration: none; transition: all .15s;
            }
            .tracker-tabs .ttab.active,
            .tracker-tabs .ttab:hover { background: var(--ic-primary); color: #fff; border-color: var(--ic-primary); }
            .tracker-tabs .ttab-link {
                font-size: .78rem; color: var(--ic-primary); text-decoration: none; font-weight: 500;
                padding: .3rem .6rem;
            }

            /* Table */
            .app-table { width: 100%; border-collapse: collapse; }
            .app-table th {
                font-size: .72rem; font-weight: 600; color: var(--ic-text-light); text-transform: uppercase;
                letter-spacing: .06em; padding: .6rem .75rem; border-bottom: 1px solid var(--ic-border);
                text-align: left; white-space: nowrap;
            }
            .app-table td { padding: 1rem .75rem; border-bottom: 1px solid var(--ic-border); vertical-align: middle; color: var(--ic-text); }
            .app-table tr:last-child td { border-bottom: none; }
            .app-table tbody tr:hover td { background: var(--ic-bg); }

            .co-avatar {
                width: 34px; height: 34px; border-radius: .5rem;
                background: var(--ic-primary-light); color: var(--ic-primary); font-weight: 700;
                font-size: .85rem; display: flex; align-items: center; justify-content: center;
                flex-shrink: 0;
            }
            .co-name    { font-size: .875rem; font-weight: 600; color: var(--ic-text); }
            .co-sub     { font-size: .75rem; color: var(--ic-text-light); margin-top: .1rem; }

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

            .empty-state { text-align: center; padding: 3rem 1rem; color: var(--ic-text-light); }
            .empty-state i { font-size: 2.5rem; display: block; margin-bottom: .75rem; color: var(--ic-border); }
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
                        <td><span style="font-size:.75rem;color:var(--ic-text-muted);"><?= e($app['work_type'] ?? '—') ?></span></td>
                        <td><span style="font-size:.78rem;color:var(--ic-text-muted);"><?= e(date('M j, Y', strtotime($app['applied_at']))) ?></span></td>
                        <td><span class="status-badge <?= e($stClass) ?>"><?= e($stLabel) ?></span></td>
                        <td>
                            <a href="<?= e(app_url('student/applications.php')) ?>"
                               style="color:var(--ic-text-light);font-size:1rem;text-decoration:none;" title="View">
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

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
