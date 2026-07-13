<?php
$pageTitle = 'Company Details';
$currentPage = 'companies';
$portalType = 'admin';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
require_role(ROLE_ADMIN);

$companyId = (int)($_GET['id'] ?? 0);

if (!$companyId) {
    redirect(app_url('admin/companies.php'));
}

// Get company details
$stmt = $mysqli->prepare('SELECT c.*, u.email, u.status, u.created_at, u.email_verified FROM companies c JOIN users u ON u.id = c.user_id WHERE c.id = ?');
$stmt->bind_param('i', $companyId);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$company) {
    redirect(app_url('admin/companies.php'));
}

// Get company internships
$stmt = $mysqli->prepare('SELECT id, title, status, created_at FROM internships WHERE company_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->bind_param('i', $companyId);
$stmt->execute();
$internships = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total applications
$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM applications a JOIN internships i ON i.id = a.internship_id WHERE i.company_id = ?');
$stmt->bind_param('i', $companyId);
$stmt->execute();
$totalApplications = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Handle verification action
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $userId = $company['user_id'];

    if ($action === 'verify') {
        $newStatus = 'active';
        $stmt = $mysqli->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $newStatus, $userId);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare('UPDATE companies SET verified = 1 WHERE id = ?');
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Company verified');
        redirect(app_url("admin/company-detail.php?id=$companyId"));
    } elseif ($action === 'reject') {
        $newStatus = 'rejected';
        $stmt = $mysqli->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $newStatus, $userId);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Company rejected');
        redirect(app_url('admin/companies.php'));
    }
}

// Refresh company data
$stmt = $mysqli->prepare('SELECT c.*, u.email, u.status, u.created_at, u.email_verified FROM companies c JOIN users u ON u.id = c.user_id WHERE c.id = ?');
$stmt->bind_param('i', $companyId);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<?php require_once dirname(__DIR__) . '/includes/portal-layout.php'; ?>

<div>
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><?= e($company['company_name']) ?></h2>
            <p class="text-muted">Registered: <?= e(date('M j, Y', strtotime($company['created_at']))) ?></p>
        </div>
        <div class="col-md-4 text-md-end">
            <?php if ($company['status'] === 'pending'): ?>
                <a href="?action=verify" class="btn btn-success" onclick="return confirm('Verify this company?')">Verify</a>
                <a href="?action=reject" class="btn btn-danger" onclick="return confirm('Reject this company?')">Reject</a>
            <?php endif; ?>
            <a href="<?= e(app_url('admin/companies.php')) ?>" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Company Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Email:</strong><br>
                            <a href="mailto:<?= e($company['email']) ?>"><?= e($company['email']) ?></a>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong><br>
                            <span class="badge bg-<?= $company['status'] === 'active' && $company['verified'] ? 'success' : ($company['status'] === 'pending' ? 'warning' : 'danger') ?> text-capitalize">
                                <?= e($company['status']) ?>
                            </span>
                            <?php if (!$company['verified']): ?>
                                <span class="badge bg-warning text-dark">Unverified</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Industry:</strong><br>
                            <?= e($company['industry'] ?? 'Not provided') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Phone:</strong><br>
                            <?= e($company['phone'] ?? 'Not provided') ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <strong>Address:</strong><br>
                            <?= e($company['address'] ?? 'Not provided') ?>, <?= e($company['district']) ?>, <?= e($company['province']) ?>
                        </div>
                    </div>

                    <?php if ($company['website']): ?>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <strong>Website:</strong><br>
                                <a href="<?= e($company['website']) ?>" target="_blank"><?= e($company['website']) ?></a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <strong>Description:</strong><br>
                            <?= e($company['description'] ?? 'No description provided') ?>
                        </div>
                    </div>

                    <?php if ($company['logo']): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <strong>Logo:</strong><br>
                                <img src="<?= e(app_url('uploads/logos/' . $company['logo'])) ?>" alt="Logo" style="max-width: 200px; max-height: 200px; object-fit: contain;">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="display-6 text-primary"><?= count($internships) ?></div>
                            <p class="text-muted small">Internships</p>
                        </div>
                        <div class="col-6">
                            <div class="display-6 text-info"><?= e($totalApplications) ?></div>
                            <p class="text-muted small">Applications</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Recent Internships</h5>
                </div>
                <div class="card-body">
                    <?php if (count($internships) > 0): ?>
                        <div class="list-group list-group-sm">
                            <?php foreach ($internships as $int): ?>
                                <a href="<?= e(app_url('admin/internship-detail.php?id=' . $int['id'])) ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center small">
                                        <div>
                                            <strong><?= e($int['title']) ?></strong><br>
                                            <small class="text-muted"><?= e(date('M j', strtotime($int['created_at']))) ?></small>
                                        </div>
                                        <span class="badge bg-<?= e($int['status'] === 'active' ? 'success' : 'warning') ?>"><?= e($int['status']) ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 small">No internships posted</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/portal-layout-end.php'; ?>
