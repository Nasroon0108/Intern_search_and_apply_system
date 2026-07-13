<?php
$pageTitle = 'Internship Details';
$currentPage = 'internships';
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
$internshipId = (int)($_GET['id'] ?? 0);

if (!$company) {
    die('Company profile not found.');
}

if (!$internshipId) {
    redirect(app_url('company/internships.php'));
}

// Get internship details and verify it belongs to this company
$stmt = $mysqli->prepare('SELECT * FROM internships WHERE id = ? AND company_id = ?');
$stmt->bind_param('ii', $internshipId, $company['id']);
$stmt->execute();
$internship = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$internship) {
    redirect(app_url('company/internships.php'));
}

// Get skills
$skills = [];
$stmt = $mysqli->prepare(
    'SELECT s.name FROM skills s
     JOIN internship_skills isk ON isk.skill_id = s.id
     WHERE isk.internship_id = ?'
);
$stmt->bind_param('i', $internshipId);
$stmt->execute();
$skillsResult = $stmt->get_result();
while ($row = $skillsResult->fetch_assoc()) {
    $skills[] = $row['name'];
}
$stmt->close();

// Get applications summary
$stmt = $mysqli->prepare(
    'SELECT status, COUNT(*) as count FROM applications 
     WHERE internship_id = ?
     GROUP BY status'
);
$stmt->bind_param('i', $internshipId);
$stmt->execute();
$statsResult = $stmt->get_result();
$applicationStats = [];
while ($row = $statsResult->fetch_assoc()) {
    $applicationStats[$row['status']] = $row['count'];
}
$stmt->close();

// Get recent applications
$stmt = $mysqli->prepare(
    'SELECT a.id, a.status, a.applied_at, s.full_name, u.email
     FROM applications a
     JOIN students s ON s.id = a.student_id
     JOIN users u ON u.id = s.user_id
     WHERE a.internship_id = ?
     ORDER BY a.applied_at DESC
     LIMIT 10'
);
$stmt->bind_param('i', $internshipId);
$stmt->execute();
$recentApplications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php require_once dirname(__DIR__) . '/includes/portal-layout.php'; ?>

<div>
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><?= e($internship['title']) ?></h2>
            <p class="text-muted">Posted: <?= e(date('M j, Y', strtotime($internship['created_at']))) ?></p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?= e(app_url('company/post-internship.php?id=' . $internship['id'])) ?>" class="btn btn-primary">Edit</a>
            <a href="<?= e(app_url('company/internships.php')) ?>" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-primary mb-2"><?= e($applicationStats['pending'] ?? 0) ?></div>
                    <p class="text-muted mb-0">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-info mb-2"><?= e($applicationStats['shortlisted'] ?? 0) ?></div>
                    <p class="text-muted mb-0">Shortlisted</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-warning mb-2"><?= e($applicationStats['interview'] ?? 0) ?></div>
                    <p class="text-muted mb-0">Interview</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-success mb-2"><?= e($applicationStats['accepted'] ?? 0) ?></div>
                    <p class="text-muted mb-0">Accepted</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="mb-0">Job Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Work Type:</strong> <?= e($internship['work_type']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Vacancies:</strong> <?= e($internship['vacancies']) ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Duration:</strong> <?= e($internship['duration_months']) ?> months
                        </div>
                        <div class="col-md-6">
                            <strong>Stipend:</strong> Rs. <?= e(number_format($internship['stipend'], 0)) ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Location:</strong> <?= e($internship['district']) ?>, <?= e($internship['province']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Deadline:</strong> <?= e($internship['application_deadline'] ? date('M j, Y', strtotime($internship['application_deadline'])) : 'Ongoing') ?>
                        </div>
                    </div>

                    <hr>

                    <h6>Responsibilities</h6>
                    <p><?= nl2br(e($internship['responsibilities'])) ?></p>

                    <h6 class="mt-4">Requirements</h6>
                    <p><?= nl2br(e($internship['requirements'])) ?></p>

                    <?php if (count($skills) > 0): ?>
                        <h6 class="mt-4">Required Skills</h6>
                        <div class="mb-3">
                            <?php foreach ($skills as $skill): ?>
                                <span class="badge bg-primary me-2 mb-2"><?= e($skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($internship['benefits']): ?>
                        <h6 class="mt-4">Benefits</h6>
                        <p><?= nl2br(e($internship['benefits'])) ?></p>
                    <?php endif; ?>

                    <div class="alert alert-light mt-4">
                        <strong>Status:</strong> 
                        <span class="badge bg-<?= e($internship['status'] === 'active' ? 'success' : ($internship['status'] === 'pending' ? 'warning' : 'secondary')) ?> text-capitalize">
                            <?= e($internship['status']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="mb-0">Applications</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Total: <?php 
                        $total = 0;
                        foreach ($applicationStats as $count) {
                            $total += $count;
                        }
                        echo $total;
                    ?></p>
                    <a href="<?= e(app_url('company/applications.php?internship_id=' . $internship['id'])) ?>" class="btn btn-sm btn-primary w-100">View All Applications</a>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header border-bottom">
                    <h5 class="mb-0">Contact Information</h5>
                </div>
                <div class="card-body">
                    <p class="small mb-1"><strong>Email:</strong> <a href="mailto:<?= e($internship['contact_email']) ?>"><?= e($internship['contact_email']) ?></a></p>
                    <?php if ($internship['contact_phone']): ?>
                        <p class="small mb-0"><strong>Phone:</strong> <?= e($internship['contact_phone']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="mb-0">Recent Applications</h5>
                </div>
                <div class="card-body">
                    <?php if (count($recentApplications) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($recentApplications as $app): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= e($app['full_name']) ?></h6>
                                            <small class="text-muted"><?= e($app['email']) ?> • <?= e(date('M j, Y', strtotime($app['applied_at']))) ?></small>
                                        </div>
                                        <div>
                                            <span class="badge bg-<?= e($app['status'] === 'pending' ? 'warning' : ($app['status'] === 'shortlisted' ? 'info' : 'success')) ?> text-capitalize mb-2"><?= e($app['status']) ?></span>
                                            <br>
                                            <a href="<?= e(app_url('company/applications.php?internship_id=' . $internship['id'])) ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No applications yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/portal-layout-end.php'; ?>
