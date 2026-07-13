<?php
$pageTitle = 'Company Dashboard';
$currentPage = 'dashboard';
$portalType = 'company';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
require_role(ROLE_COMPANY);

$userId = current_user_id();
$company = get_company_by_user_id($mysqli, $userId);

if (!$company) {
    die('Company profile not found.');
}

// Get user account status
$stmt = $mysqli->prepare('SELECT status, email_verified FROM users WHERE id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get statistics
$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM internships WHERE company_id = ? AND status IN (?, ?, ?)');
$status1 = 'active';
$status2 = 'pending';
$status3 = 'draft';
$stmt->bind_param('isss', $company['id'], $status1, $status2, $status3);
$stmt->execute();
$totalInternships = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $mysqli->prepare(
    'SELECT COUNT(*) as total FROM applications a
     JOIN internships i ON i.id = a.internship_id
     WHERE i.company_id = ?'
);
$stmt->bind_param('i', $company['id']);
$stmt->execute();
$totalApplications = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $mysqli->prepare(
    'SELECT COUNT(*) as total FROM applications a
     JOIN internships i ON i.id = a.internship_id
     WHERE i.company_id = ? AND a.status = ?'
);
$shortlistStatus = 'shortlisted';
$stmt->bind_param('is', $company['id'], $shortlistStatus);
$stmt->execute();
$shortlisted = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get recent internships
$stmt = $mysqli->prepare(
    'SELECT id, title, status, created_at FROM internships WHERE company_id = ? ORDER BY created_at DESC LIMIT 5'
);
$stmt->bind_param('i', $company['id']);
$stmt->execute();
$recentInternships = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get recent applications
$stmt = $mysqli->prepare(
    'SELECT a.id, a.status, a.applied_at, i.title, s.full_name
     FROM applications a
     JOIN internships i ON i.id = a.internship_id
     JOIN students s ON s.id = a.student_id
     WHERE i.company_id = ?
     ORDER BY a.applied_at DESC
     LIMIT 5'
);
$stmt->bind_param('i', $company['id']);
$stmt->execute();
$recentApplications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php require_once dirname(__DIR__) . '/includes/portal-layout.php'; ?>

<div class="ic-page-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h1><?= e($company['company_name']) ?></h1>
        <p>Company Dashboard</p>
    </div>
    <?php if (!$user['email_verified']): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-0 py-2 px-3 small">
            Please verify your email. <a href="<?= e(app_url('auth/forgot-password.php')) ?>">Resend verification</a>
        </div>
    <?php endif; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="ic-stat-card">
            <div class="stat-value"><?= e($totalInternships) ?></div>
            <div class="stat-label">Total Internships</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ic-stat-card">
            <div class="stat-value"><?= e($totalApplications) ?></div>
            <div class="stat-label">Applications</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ic-stat-card">
            <div class="stat-value"><?= e($shortlisted) ?></div>
            <div class="stat-label">Shortlisted</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="ic-stat-card">
            <div class="stat-value"><?= $company['verified'] ? '✓' : '⏳' ?></div>
            <div class="stat-label"><?= $company['verified'] ? 'Verified' : 'Pending Verification' ?></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="ic-card">
            <div class="ic-card-header d-flex justify-content-between align-items-center">
                <span>Your Internships</span>
                <a href="<?= e(app_url('company/post-internship.php')) ?>" class="btn btn-sm btn-primary">Post New</a>
            </div>
            <div class="ic-card-body">
                <?php if (count($recentInternships) > 0): ?>
                    <div class="list-group">
                        <?php foreach ($recentInternships as $int): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= e($int['title']) ?></h6>
                                        <small class="text-muted">Posted: <?= e(date('M j, Y', strtotime($int['created_at']))) ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-<?= e($int['status'] === 'active' ? 'success' : ($int['status'] === 'pending' ? 'warning' : 'secondary')) ?> text-capitalize mb-2"><?= e($int['status']) ?></span><br>
                                        <a href="<?= e(app_url('company/internship-detail.php?id=' . $int['id'])) ?>" class="btn btn-sm btn-outline-primary">Manage</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= e(app_url('company/internships.php')) ?>" class="btn btn-sm btn-outline-primary w-100 mt-3">View All</a>
                <?php else: ?>
                    <p class="text-muted text-center py-4 mb-0">No internships posted yet. <a href="<?= e(app_url('company/post-internship.php')) ?>">Post your first internship</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="ic-card">
            <div class="ic-card-header">Recent Applications</div>
            <div class="ic-card-body">
                <?php if (count($recentApplications) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentApplications as $app): ?>
                            <a href="<?= e(app_url('company/applications.php')) ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center small">
                                    <div>
                                        <strong><?= e($app['full_name']) ?></strong><br>
                                        <small class="text-muted"><?= e($app['title']) ?></small>
                                    </div>
                                    <span class="badge bg-primary"><?= e($app['status']) ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= e(app_url('company/applications.php')) ?>" class="btn btn-sm btn-outline-primary w-100 mt-3">View All</a>
                <?php else: ?>
                    <p class="text-muted small text-center py-3 mb-0">No applications yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/portal-layout-end.php'; ?>
