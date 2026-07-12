<?php
$pageTitle = 'Admin Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_ADMIN);

$admin = get_admin_by_user_id($mysqli, current_user_id());

$counts = [
    'students' => 0,
    'companies' => 0,
    'internships' => 0,
    'applications' => 0,
    'pending_companies' => 0,
];

$queries = [
    'students' => "SELECT COUNT(*) AS c FROM users WHERE role = 'student'",
    'companies' => "SELECT COUNT(*) AS c FROM users WHERE role = 'company'",
    'internships' => 'SELECT COUNT(*) AS c FROM internships',
    'applications' => 'SELECT COUNT(*) AS c FROM applications',
    'pending_companies' => "SELECT COUNT(*) AS c FROM companies WHERE verification_status = 'pending'",
];

foreach ($queries as $key => $sql) {
    $result = $mysqli->query($sql);
    if ($result) {
        $counts[$key] = (int) $result->fetch_assoc()['c'];
    }
}
?>

<div class="container py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1">Admin Dashboard</h1>
        <p class="text-muted mb-0">Welcome, <?= e($admin['full_name'] ?? 'Administrator') ?></p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm stat-widget">
                <div class="card-body text-center">
                    <div class="h3 mb-0 text-primary"><?= $counts['students'] ?></div>
                    <div class="text-muted small">Students</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm stat-widget">
                <div class="card-body text-center">
                    <div class="h3 mb-0 text-primary"><?= $counts['companies'] ?></div>
                    <div class="text-muted small">Companies</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm stat-widget">
                <div class="card-body text-center">
                    <div class="h3 mb-0 text-warning"><?= $counts['pending_companies'] ?></div>
                    <div class="text-muted small">Pending Verify</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm stat-widget">
                <div class="card-body text-center">
                    <div class="h3 mb-0 text-success"><?= $counts['internships'] ?></div>
                    <div class="text-muted small">Internships</div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm stat-widget">
                <div class="card-body text-center">
                    <div class="h3 mb-0 text-info"><?= $counts['applications'] ?></div>
                    <div class="text-muted small">Applications</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Admin Actions</h2>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <button class="btn btn-outline-danger w-100" disabled>Verify Companies (Phase 3)</button>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-danger w-100" disabled>Manage Internships (Phase 3)</button>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-outline-danger w-100" disabled>View Reports (Phase 3)</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
