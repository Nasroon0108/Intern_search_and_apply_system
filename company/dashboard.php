<?php
$pageTitle = 'Company Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

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

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><?= e($company['company_name']) ?></h2>
            <p class="text-muted">Company Dashboard</p>
        </div>
        <div class="col-md-4 text-md-end">
            <?php if (!$user['email_verified']): ?>
                <div class="alert alert-warning alert-dismissible fade show mb-0">
                    Please verify your email to fully access the platform. <a href="<?= e(app_url('auth/forgot-password.php')) ?>">Resend verification email</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-primary mb-2"><?= e($totalInternships) ?></div>
                    <p class="text-muted mb-0">Total Internships</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-info mb-2"><?= e($totalApplications) ?></div>
                    <p class="text-muted mb-0">Applications</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-success mb-2"><?= e($shortlisted) ?></div>
                    <p class="text-muted mb-0">Shortlisted</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 <?= $company['verified'] ? 'text-success' : 'text-warning' ?> mb-2">
                        <?= $company['verified'] ? '✓' : '⏳' ?>
                    </div>
                    <p class="text-muted mb-0"><?= $company['verified'] ? 'Verified' : 'Pending Verification' ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Your Internships</h5>
                    <a href="<?= e(app_url('company/post-internship.php')) ?>" class="btn btn-sm btn-primary">Post New</a>
                </div>
                <div class="card-body">
                    <?php if (count($recentInternships) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($recentInternships as $int): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= e($int['title']) ?></h6>
                                            <small class="text-muted">Posted: <?= e(date('M j, Y', strtotime($int['created_at']))) ?></small>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?= e($int['status'] === 'active' ? 'success' : ($int['status'] === 'pending' ? 'warning' : 'secondary')) ?> text-capitalize mb-2"><?= e($int['status']) ?></span>
                                            <br>
                                            <a href="<?= e(app_url('company/internship-detail.php?id=' . $int['id'])) ?>" class="btn btn-sm btn-outline-primary">Manage</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?= e(app_url('company/internships.php')) ?>" class="btn btn-sm btn-outline-primary w-100 mt-3">View All</a>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No internships posted yet. <a href="<?= e(app_url('company/post-internship.php')) ?>">Post your first internship</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?= e(app_url('company/post-internship.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-plus-circle"></i> Post Internship
                    </a>
                    <a href="<?= e(app_url('company/internships.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-briefcase"></i> Manage Internships
                    </a>
                    <a href="<?= e(app_url('company/applications.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-inbox"></i> View Applications
                    </a>
                    <a href="<?= e(app_url('company/profile.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-building"></i> Company Profile
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Recent Applications</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recentApplications) > 0): ?>
                        <div class="list-group list-group-sm">
                            <?php foreach ($recentApplications as $app): ?>
                                <a href="<?= e(app_url('company/applications.php')) ?>" class="list-group-item list-group-item-action list-group-item-sm">
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
                        <p class="text-muted small text-center py-3">No applications yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
