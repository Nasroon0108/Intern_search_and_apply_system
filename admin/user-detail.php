<?php
$pageTitle = 'User Details';
$currentPage = 'users';
$portalType = 'admin';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
require_role(ROLE_ADMIN);

$userId = (int)($_GET['id'] ?? 0);

if (!$userId) {
    redirect(app_url('admin/users.php'));
}

$stmt = $mysqli->prepare(
    'SELECT id, email, role, status, email_verified, last_login, created_at, updated_at FROM users WHERE id = ?'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    set_flash('error', 'User not found.');
    redirect(app_url('admin/users.php'));
}

// Handle status actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'activate') {
        $newStatus = STATUS_ACTIVE;
        $stmt = $mysqli->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $newStatus, $userId);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'User activated.');
        redirect(app_url('admin/user-detail.php?id=' . $userId));
    }

    if ($action === 'block') {
        $newStatus = STATUS_BLOCKED;
        $stmt = $mysqli->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $newStatus, $userId);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'User blocked.');
        redirect(app_url('admin/user-detail.php?id=' . $userId));
    }

    if ($action === 'verify_email' && !(int)$user['email_verified']) {
        if (mark_email_verified($mysqli, $userId)) {
            set_flash('success', 'Email marked as verified.');
        } else {
            set_flash('error', 'Could not update email verification.');
        }
        redirect(app_url('admin/user-detail.php?id=' . $userId));
    }
}

// Reload user after possible redirect
$stmt = $mysqli->prepare(
    'SELECT id, email, role, status, email_verified, last_login, created_at, updated_at FROM users WHERE id = ?'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile = null;
$stats = [];
$relatedLink = null;

if ($user['role'] === ROLE_STUDENT) {
    $stmt = $mysqli->prepare(
        'SELECT id, full_name, phone, district, province, university, degree_program, gpa, profile_completion, created_at
         FROM students WHERE user_id = ?'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($profile) {
        $studentId = (int)$profile['id'];

        $stmt = $mysqli->prepare('SELECT COUNT(*) AS total FROM applications WHERE student_id = ?');
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $stats['applications'] = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $stmt = $mysqli->prepare('SELECT COUNT(*) AS total FROM favorites WHERE student_id = ?');
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $stats['saved'] = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $stmt = $mysqli->prepare('SELECT COUNT(*) AS total FROM student_skills WHERE student_id = ?');
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $stats['skills'] = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    }
} elseif ($user['role'] === ROLE_COMPANY) {
    $stmt = $mysqli->prepare(
        'SELECT id, company_name, industry, district, province, phone, website, verified, verification_status, created_at
         FROM companies WHERE user_id = ?'
    );
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($profile) {
        $companyId = (int)$profile['id'];
        $relatedLink = app_url('admin/company-detail.php?id=' . $companyId);

        $stmt = $mysqli->prepare('SELECT COUNT(*) AS total FROM internships WHERE company_id = ?');
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stats['internships'] = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $stmt = $mysqli->prepare(
            'SELECT COUNT(*) AS total FROM applications a
             JOIN internships i ON i.id = a.internship_id WHERE i.company_id = ?'
        );
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $stats['applications'] = (int)$stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();
    }
} elseif ($user['role'] === ROLE_ADMIN) {
    $stmt = $mysqli->prepare('SELECT id, full_name FROM admins WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$displayName = $profile['full_name'] ?? $profile['company_name'] ?? $user['email'];

$statusBadge = match ($user['status']) {
    STATUS_ACTIVE => 'success',
    STATUS_PENDING => 'warning',
    STATUS_BLOCKED => 'danger',
    default => 'secondary',
};

$roleBadge = match ($user['role']) {
    ROLE_STUDENT => 'primary',
    ROLE_COMPANY => 'info',
    ROLE_ADMIN => 'warning',
    default => 'secondary',
};
?>

<?php require_once dirname(__DIR__) . '/includes/portal-layout.php'; ?>

<div>
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><?= e($displayName) ?></h2>
            <p class="text-muted mb-1"><?= e($user['email']) ?></p>
            <p class="text-muted small mb-0">Joined <?= e(date('M j, Y', strtotime($user['created_at']))) ?></p>
        </div>
        <div class="col-md-4 text-md-end">
            <?php if (!(int)$user['email_verified']): ?>
                <a href="?id=<?= e($userId) ?>&action=verify_email" class="btn btn-outline-success btn-sm"
                   onclick="return confirm('Mark this email as verified?')">Verify Email</a>
            <?php endif; ?>
            <?php if ($user['status'] !== STATUS_ACTIVE): ?>
                <a href="?id=<?= e($userId) ?>&action=activate" class="btn btn-success btn-sm"
                   onclick="return confirm('Activate this user account?')">Activate</a>
            <?php endif; ?>
            <?php if ($user['status'] !== STATUS_BLOCKED): ?>
                <a href="?id=<?= e($userId) ?>&action=block" class="btn btn-danger btn-sm"
                   onclick="return confirm('Block this user account?')">Block</a>
            <?php endif; ?>
            <?php if ($relatedLink): ?>
                <a href="<?= e($relatedLink) ?>" class="btn btn-outline-primary btn-sm">Company Profile</a>
            <?php endif; ?>
            <a href="<?= e(app_url('admin/users.php')) ?>" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header border-bottom">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Email:</strong><br>
                            <a href="mailto:<?= e($user['email']) ?>"><?= e($user['email']) ?></a>
                        </div>
                        <div class="col-md-6">
                            <strong>Role:</strong><br>
                            <span class="badge bg-<?= e($roleBadge) ?> text-capitalize"><?= e($user['role']) ?></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Status:</strong><br>
                            <span class="badge bg-<?= e($statusBadge) ?> text-capitalize"><?= e($user['status']) ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Email Verified:</strong><br>
                            <?php if ($user['email_verified']): ?>
                                <span class="text-success"><i class="bi bi-check-circle"></i> Yes</span>
                            <?php else: ?>
                                <span class="text-danger"><i class="bi bi-x-circle"></i> No</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Last Login:</strong><br>
                            <?= $user['last_login'] ? e(date('M j, Y g:i A', strtotime($user['last_login']))) : 'Never' ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Last Updated:</strong><br>
                            <?= e(date('M j, Y g:i A', strtotime($user['updated_at']))) ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($profile): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="mb-0">
                        <?= $user['role'] === ROLE_STUDENT ? 'Student Profile' : ($user['role'] === ROLE_COMPANY ? 'Company Profile' : 'Admin Profile') ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($user['role'] === ROLE_STUDENT): ?>
                        <div class="row mb-3">
                            <div class="col-md-6"><strong>Full Name:</strong><br><?= e($profile['full_name']) ?></div>
                            <div class="col-md-6"><strong>Phone:</strong><br><?= e($profile['phone'] ?? '—') ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6"><strong>University:</strong><br><?= e($profile['university'] ?? '—') ?></div>
                            <div class="col-md-6"><strong>Degree:</strong><br><?= e($profile['degree_program'] ?? '—') ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6"><strong>GPA:</strong><br><?= e($profile['gpa'] ?? '—') ?></div>
                            <div class="col-md-6"><strong>Location:</strong><br><?= e(trim(($profile['district'] ?? '') . ', ' . ($profile['province'] ?? ''), ', ') ?: '—') ?></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><strong>Profile Completion:</strong><br><?= e($profile['profile_completion']) ?>%</div>
                        </div>
                    <?php elseif ($user['role'] === ROLE_COMPANY): ?>
                        <div class="row mb-3">
                            <div class="col-md-6"><strong>Company Name:</strong><br><?= e($profile['company_name']) ?></div>
                            <div class="col-md-6"><strong>Industry:</strong><br><?= e($profile['industry'] ?? '—') ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6"><strong>Phone:</strong><br><?= e($profile['phone'] ?? '—') ?></div>
                            <div class="col-md-6"><strong>Website:</strong><br>
                                <?php if (!empty($profile['website'])): ?>
                                    <a href="<?= e($profile['website']) ?>" target="_blank" rel="noopener"><?= e($profile['website']) ?></a>
                                <?php else: ?>—<?php endif; ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6"><strong>Location:</strong><br><?= e(trim(($profile['district'] ?? '') . ', ' . ($profile['province'] ?? ''), ', ') ?: '—') ?></div>
                            <div class="col-md-6"><strong>Verification:</strong><br>
                                <span class="badge bg-<?= $profile['verified'] ? 'success' : 'warning' ?>">
                                    <?= $profile['verified'] ? 'Verified' : e($profile['verification_status']) ?>
                                </span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-md-6"><strong>Admin Name:</strong><br><?= e($profile['full_name']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">No profile record found for this user.</div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <?php if (!empty($stats)): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <?php foreach ($stats as $label => $count): ?>
                        <div class="col-6">
                            <div class="display-6 text-primary"><?= e($count) ?></div>
                            <p class="text-muted small mb-0 text-capitalize"><?= e($label) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/portal-layout-end.php'; ?>
