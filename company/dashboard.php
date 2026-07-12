<?php
$pageTitle = 'Company Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_COMPANY);

$company = get_company_by_user_id($mysqli, current_user_id());
if (!$company) {
    die('Company profile not found.');
}

$userStatus = $_SESSION['user_status'] ?? null;
$stmt = $mysqli->prepare('SELECT status FROM users WHERE id = ?');
$userId = current_user_id();
$stmt->bind_param('i', $userId);
$stmt->execute();
$userRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$accountStatus = $userRow['status'] ?? 'pending';

$internshipCount = 0;
$applicantCount = 0;

$stmt = $mysqli->prepare('SELECT COUNT(*) AS cnt FROM internships WHERE company_id = ?');
$stmt->bind_param('i', $company['id']);
$stmt->execute();
$internshipCount = (int) $stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$stmt = $mysqli->prepare(
    'SELECT COUNT(*) AS cnt FROM applications a
     JOIN internships i ON i.id = a.internship_id
     WHERE i.company_id = ?'
);
$stmt->bind_param('i', $company['id']);
$stmt->execute();
$applicantCount = (int) $stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1"><?= e($company['company_name']) ?></h1>
            <p class="text-muted mb-0">Company Dashboard</p>
        </div>
        <?php if ($company['verified']): ?>
            <span class="badge bg-success"><i class="bi bi-patch-check me-1"></i> Verified</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i> Pending Verification</span>
        <?php endif; ?>
    </div>

    <?php if ($accountStatus === STATUS_PENDING || !$company['verified']): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Your company account is awaiting admin verification. You can view your dashboard, but posting internships will be enabled after approval.
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-widget">
                <div class="card-body">
                    <div class="text-muted small">Posted Internships</div>
                    <div class="h3 mb-0"><?= $internshipCount ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-widget">
                <div class="card-body">
                    <div class="text-muted small">Total Applicants</div>
                    <div class="h3 mb-0"><?= $applicantCount ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm stat-widget">
                <div class="card-body">
                    <div class="text-muted small">Verification</div>
                    <div class="h5 mb-0 text-capitalize"><?= e($company['verification_status']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Quick Actions</h2>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-sm-6 col-md-4">
                    <button class="btn btn-outline-primary w-100" disabled>
                        <i class="bi bi-plus-circle me-1"></i> Post Internship <small>(Phase 2)</small>
                    </button>
                </div>
                <div class="col-sm-6 col-md-4">
                    <button class="btn btn-outline-primary w-100" disabled>
                        <i class="bi bi-people me-1"></i> View Applicants <small>(Phase 2)</small>
                    </button>
                </div>
                <div class="col-sm-6 col-md-4">
                    <button class="btn btn-outline-primary w-100" disabled>
                        <i class="bi bi-building me-1"></i> Edit Profile <small>(Phase 2)</small>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
