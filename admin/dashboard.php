<?php
$pageTitle = 'Admin Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_ADMIN);

// Get statistics
$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM users WHERE role = ?');
$studentRole = ROLE_STUDENT;
$stmt->bind_param('s', $studentRole);
$stmt->execute();
$totalStudents = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM users WHERE role = ?');
$companyRole = ROLE_COMPANY;
$stmt->bind_param('s', $companyRole);
$stmt->execute();
$totalCompanies = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM users WHERE role = ? AND status = ?');
$pendingStatus = 'pending';
$stmt->bind_param('ss', $companyRole, $pendingStatus);
$stmt->execute();
$pendingCompanies = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM internships');
$stmt->execute();
$totalInternships = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM applications');
$stmt->execute();
$totalApplications = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM users WHERE email_verified = 0');
$stmt->execute();
$unverifiedEmails = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Get recent registrations
$stmt = $mysqli->prepare(
    'SELECT u.id, u.email, u.role, u.created_at, COALESCE(c.company_name, s.full_name) as name
     FROM users u
     LEFT JOIN companies c ON c.user_id = u.id
     LEFT JOIN students s ON s.user_id = u.id
     ORDER BY u.created_at DESC
     LIMIT 10'
);
$stmt->execute();
$recentUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get pending companies
$stmt = $mysqli->prepare(
    'SELECT c.id, c.company_name, c.created_at, u.email
     FROM companies c
     JOIN users u ON u.id = c.user_id
     WHERE u.status = ? OR c.verified = 0
     LIMIT 10'
);
$stmt->bind_param('s', $pendingStatus);
$stmt->execute();
$pendingVerifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="container-fluid py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Admin Dashboard</h2>
            <p class="text-muted">Platform Overview & Management</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-primary mb-2"><?= e($totalStudents) ?></div>
                    <p class="text-muted mb-0">Students</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-info mb-2"><?= e($totalCompanies) ?></div>
                    <p class="text-muted mb-0">Companies</p>
                    <?php if ($pendingCompanies > 0): ?>
                        <small class="text-danger"><?= e($pendingCompanies) ?> pending</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-success mb-2"><?= e($totalInternships) ?></div>
                    <p class="text-muted mb-0">Internships</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-6 text-warning mb-2"><?= e($totalApplications) ?></div>
                    <p class="text-muted mb-0">Applications</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-5 text-danger mb-2"><?= e($unverifiedEmails) ?></div>
                    <p class="text-muted mb-0">Unverified Emails</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="display-5 text-warning mb-2"><?= count($pendingVerifications) ?></div>
                    <p class="text-muted mb-0">Pending Company Verifications</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Registrations</h5>
                    <a href="<?= e(app_url('admin/users.php')) ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php foreach ($recentUsers as $user): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= e($user['name'] ?? $user['email']) ?></h6>
                                        <small class="text-muted"><?= e($user['email']) ?> • <?= ucfirst(e($user['role'])) ?></small>
                                    </div>
                                    <small class="text-muted"><?= e(date('M j', strtotime($user['created_at']))) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Pending Verifications</h5>
                    <a href="<?= e(app_url('admin/companies.php')) ?>" class="btn btn-sm btn-outline-primary">Manage</a>
                </div>
                <div class="card-body">
                    <?php if (count($pendingVerifications) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($pendingVerifications as $company): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= e($company['company_name']) ?></h6>
                                            <small class="text-muted"><?= e($company['email']) ?></small>
                                        </div>
                                        <a href="<?= e(app_url('admin/company-detail.php?id=' . $company['id'])) ?>" class="btn btn-sm btn-outline-primary">Review</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">All companies verified!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="list-group list-group-horizontal list-group-flush">
                    <a href="<?= e(app_url('admin/users.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-people"></i> Manage Users
                    </a>
                    <a href="<?= e(app_url('admin/companies.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-building"></i> Verify Companies
                    </a>
                    <a href="<?= e(app_url('admin/internships.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-briefcase"></i> Moderate Internships
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
