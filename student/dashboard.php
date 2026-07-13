<?php
$pageTitle = 'Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_STUDENT);

$userId = current_user_id();
$student = get_student_by_user_id($mysqli, $userId);

// Get statistics
$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM applications WHERE student_id = ?');
$stmt->bind_param('i', $student['id']);
$stmt->execute();
$totalApplications = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$status1 = 'shortlisted';
$status2 = 'interview';
$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM applications WHERE student_id = ? AND status IN (?, ?)');
$stmt->bind_param('iss', $student['id'], $status1, $status2);
$stmt->execute();
$shortlisted = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$status = 'accepted';
$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM applications WHERE student_id = ? AND status = ?');
$stmt->bind_param('is', $student['id'], $status);
$stmt->execute();
$accepted = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM favorites WHERE student_id = ?');
$stmt->bind_param('i', $student['id']);
$stmt->execute();
$savedInternships = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get recent applications
$stmt = $mysqli->prepare(
    'SELECT a.*, i.title, c.company_name 
     FROM applications a
     JOIN internships i ON i.id = a.internship_id
     JOIN companies c ON c.id = i.company_id
     WHERE a.student_id = ?
     ORDER BY a.applied_at DESC
     LIMIT 5'
);
$stmt->bind_param('i', $student['id']);
$stmt->execute();
$recentApplications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Welcome, <?= e($student['full_name']) ?>!</h2>
            <p class="text-muted">Here's your internship application overview</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?= e(app_url('internships.php')) ?>" class="btn btn-primary">Find Internships</a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-primary mb-2"><?= e($totalApplications) ?></div>
                    <p class="text-muted mb-0">Total Applications</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-info mb-2"><?= e($shortlisted) ?></div>
                    <p class="text-muted mb-0">Shortlisted</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-success mb-2"><?= e($accepted) ?></div>
                    <p class="text-muted mb-0">Offers</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-warning mb-2"><?= e($savedInternships) ?></div>
                    <p class="text-muted mb-0">Saved Internships</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Applications</h5>
                    <a href="<?= e(app_url('student/applications.php')) ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (count($recentApplications) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($recentApplications as $app): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= e($app['title']) ?></h6>
                                            <p class="text-muted small mb-1"><?= e($app['company_name']) ?></p>
                                            <small class="text-muted">
                                                Applied: <?= e(date('M j, Y', strtotime($app['applied_at']))) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <?php 
                                                $statusClass = match($app['status']) {
                                                    'pending' => 'warning',
                                                    'shortlisted' => 'info',
                                                    'interview' => 'primary',
                                                    'accepted' => 'success',
                                                    'rejected' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>
                                            <span class="badge bg-<?= e($statusClass) ?> text-capitalize"><?= e($app['status']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No applications yet. <a href="<?= e(app_url('internships.php')) ?>">Start applying</a></p>
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
                    <a href="<?= e(app_url('student/profile.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-person"></i> Complete Profile
                    </a>
                    <a href="<?= e(app_url('internships.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-search"></i> Search Internships
                    </a>
                    <a href="<?= e(app_url('student/applications.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-briefcase"></i> View Applications
                    </a>
                    <a href="<?= e(app_url('student/saved.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-heart"></i> Saved Internships
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Profile Completion</h5>
                </div>
                <div class="card-body">
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar" role="progressbar" style="width: <?= e($student['profile_completion']) ?>%;" aria-valuenow="<?= e($student['profile_completion']) ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= e($student['profile_completion']) ?>%
                        </div>
                    </div>
                    <p class="small text-muted mb-2">Complete your profile to attract more internship opportunities.</p>
                    <a href="<?= e(app_url('student/profile.php')) ?>" class="btn btn-sm btn-outline-primary w-100">Edit Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
