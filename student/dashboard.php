<?php
$pageTitle = 'Student Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_STUDENT);

$student = get_student_by_user_id($mysqli, current_user_id());
if (!$student) {
    die('Student profile not found.');
}

// Application stats (Phase 2 will populate)
$stats = ['total' => 0, 'pending' => 0, 'shortlisted' => 0, 'accepted' => 0];
$stmt = $mysqli->prepare(
    'SELECT status, COUNT(*) AS cnt FROM applications WHERE student_id = ? GROUP BY status'
);
$stmt->bind_param('i', $student['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $stats['total'] += (int) $row['cnt'];
    $key = $row['status'];
    if (isset($stats[$key])) {
        $stats[$key] = (int) $row['cnt'];
    }
}
$stmt->close();

$completion = (int) ($student['profile_completion'] ?? 0);
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Welcome, <?= e($student['full_name']) ?></h1>
            <p class="text-muted mb-0">Student Dashboard</p>
        </div>
        <span class="badge bg-primary fs-6">Profile <?= $completion ?>% complete</span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-widget">
                <div class="card-body">
                    <div class="text-muted small">Total Applications</div>
                    <div class="h3 mb-0"><?= $stats['total'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-widget">
                <div class="card-body">
                    <div class="text-muted small">Pending</div>
                    <div class="h3 mb-0 text-warning"><?= $stats['pending'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-widget">
                <div class="card-body">
                    <div class="text-muted small">Shortlisted</div>
                    <div class="h3 mb-0 text-info"><?= $stats['shortlisted'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-widget">
                <div class="card-body">
                    <div class="text-muted small">Accepted</div>
                    <div class="h3 mb-0 text-success"><?= $stats['accepted'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Quick Actions</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <a href="#" class="btn btn-outline-primary w-100 disabled">
                                <i class="bi bi-search me-1"></i> Browse Internships <small>(Phase 2)</small>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="#" class="btn btn-outline-primary w-100 disabled">
                                <i class="bi bi-person me-1"></i> Edit Profile <small>(Phase 2)</small>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="#" class="btn btn-outline-primary w-100 disabled">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Manage CVs <small>(Phase 2)</small>
                            </a>
                        </div>
                        <div class="col-sm-6">
                            <a href="#" class="btn btn-outline-primary w-100 disabled">
                                <i class="bi bi-heart me-1"></i> Saved Internships <small>(Phase 2)</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Your Profile</h2>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small mb-0">
                        <li class="mb-2"><strong>University:</strong> <?= e($student['university'] ?: 'Not set') ?></li>
                        <li class="mb-2"><strong>District:</strong> <?= e($student['district'] ?: 'Not set') ?></li>
                        <li class="mb-2"><strong>Phone:</strong> <?= e($student['phone'] ?: 'Not set') ?></li>
                        <li><strong>Email:</strong> <?= e($_SESSION['user_email']) ?></li>
                    </ul>
                    <div class="progress mt-3" style="height: 8px;">
                        <div class="progress-bar" style="width: <?= $completion ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
