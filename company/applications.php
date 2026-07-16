<?php
$pageTitle = 'Manage Applications';
$currentPage = 'applications';
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

$page = max(1, (int)($_GET['page'] ?? 1));
$statusFilter = $_GET['status'] ?? 'all';
$internshipFilter = (int)($_GET['internship_id'] ?? 0);
$perPage = 15;

// Build query
$where = 'i.company_id = ?';
$params = [$company['id']];
$types = 'i';

if ($statusFilter !== 'all') {
    $where .= ' AND a.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

if ($internshipFilter > 0) {
    $where .= ' AND i.id = ?';
    $params[] = $internshipFilter;
    $types .= 'i';
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM applications a JOIN internships i ON i.id = a.internship_id WHERE $where";
$stmt = $mysqli->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalRows / $perPage);
$offset = ($page - 1) * $perPage;

// Get applications (include submitted CV when available)
$query = "
    SELECT a.id, a.status, a.applied_at, a.cover_letter, a.cv_id,
           i.title, s.full_name, u.email,
           cv.title AS cv_title, cv.file_path AS cv_file
    FROM applications a
    JOIN internships i ON i.id = a.internship_id
    JOIN students s ON s.id = a.student_id
    JOIN users u ON u.id = s.user_id
    LEFT JOIN student_cvs cv ON cv.id = a.cv_id
    WHERE $where
    ORDER BY a.applied_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $mysqli->prepare($query);
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle status update
if (isset($_POST['_action'])) {
    require_valid_csrf();

    $appId = (int)$_POST['app_id'];
    $newStatus = $_POST['_action'];

    // Verify this application belongs to this company's internship
    $stmt = $mysqli->prepare(
        'SELECT a.id FROM applications a
         JOIN internships i ON i.id = a.internship_id
         WHERE a.id = ? AND i.company_id = ?'
    );
    $stmt->bind_param('ii', $appId, $company['id']);
    $stmt->execute();
    $app = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($app) {
        $stmt = $mysqli->prepare('UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->bind_param('si', $newStatus, $appId);
        $stmt->execute();
        $stmt->close();

        // TODO: Send email notification to student
        set_flash('success', 'Application status updated.');
    }

    redirect(app_url("company/applications.php?status=$statusFilter&internship_id=$internshipFilter"));
}

// Get internship list for filter
$stmt = $mysqli->prepare('SELECT id, title FROM internships WHERE company_id = ? ORDER BY title');
$stmt->bind_param('i', $company['id']);
$stmt->execute();
$internshipsList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php require_once dirname(__DIR__) . '/includes/portal-layout.php'; ?>

<div>
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Manage Applications</h2>
            <p class="text-muted">Review and manage student applications</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="btn-group" role="group">
                <a href="<?= e(app_url('company/applications.php')) ?>" class="btn btn-outline-primary <?= ($statusFilter === 'all') ? 'active' : '' ?>">
                    All
                </a>
                <a href="?status=pending" class="btn btn-outline-primary <?= ($statusFilter === 'pending') ? 'active' : '' ?>">
                    Pending
                </a>
                <a href="?status=shortlisted" class="btn btn-outline-primary <?= ($statusFilter === 'shortlisted') ? 'active' : '' ?>">
                    Shortlisted
                </a>
                <a href="?status=interview" class="btn btn-outline-primary <?= ($statusFilter === 'interview') ? 'active' : '' ?>">
                    Interview
                </a>
                <a href="?status=accepted" class="btn btn-outline-primary <?= ($statusFilter === 'accepted') ? 'active' : '' ?>">
                    Accepted
                </a>
                <a href="?status=rejected" class="btn btn-outline-primary <?= ($statusFilter === 'rejected') ? 'active' : '' ?>">
                    Rejected
                </a>
            </div>
        </div>
        <div class="col-md-6">
            <form method="GET" class="form-inline">
                <select class="form-select" name="internship_id" onchange="this.form.submit()">
                    <option value="">All Internships</option>
                    <?php foreach ($internshipsList as $int): ?>
                        <option value="<?= e($int['id']) ?>" <?= ($internshipFilter === $int['id']) ? 'selected' : '' ?>>
                            <?= e($int['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <?php if (count($applications) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Internship</th>
                        <th>CV</th>
                        <th>Applied</th>
                        <th>Current Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>
                                <strong><?= e($app['full_name']) ?></strong><br>
                                <small class="text-muted"><?= e($app['email']) ?></small>
                                <?php if (!empty($app['cover_letter'])): ?>
                                    <br>
                                    <button type="button"
                                            class="btn btn-link btn-sm p-0 mt-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#coverLetterModal<?= (int)$app['id'] ?>">
                                        View cover letter
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td><?= e($app['title']) ?></td>
                            <td>
                                <?php if (!empty($app['cv_id']) && !empty($app['cv_file'])): ?>
                                    <a href="<?= e(app_url('company/download-cv.php?application_id=' . (int)$app['id'])) ?>"
                                       class="btn btn-sm btn-outline-primary"
                                       target="_blank"
                                       rel="noopener">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                        <?= e($app['cv_title'] ?: 'View CV') ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">No CV</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('M j, Y', strtotime($app['applied_at']))) ?></td>
                            <td>
                                <span class="badge bg-<?= e($app['status'] === 'pending' ? 'warning' : ($app['status'] === 'shortlisted' ? 'info' : ($app['status'] === 'interview' ? 'primary' : ($app['status'] === 'accepted' ? 'success' : 'danger')))) ?> text-capitalize">
                                    <?= e($app['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Change Status
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="_action" value="pending">
                                                <input type="hidden" name="app_id" value="<?= e($app['id']) ?>">
                                                <button type="submit" class="dropdown-item">Pending</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="_action" value="shortlisted">
                                                <input type="hidden" name="app_id" value="<?= e($app['id']) ?>">
                                                <button type="submit" class="dropdown-item">Shortlist</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="_action" value="interview">
                                                <input type="hidden" name="app_id" value="<?= e($app['id']) ?>">
                                                <button type="submit" class="dropdown-item">Interview</button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="_action" value="accepted">
                                                <input type="hidden" name="app_id" value="<?= e($app['id']) ?>">
                                                <button type="submit" class="dropdown-item text-success">Accept</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="_action" value="rejected">
                                                <input type="hidden" name="app_id" value="<?= e($app['id']) ?>">
                                                <button type="submit" class="dropdown-item text-danger">Reject</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php foreach ($applications as $app): ?>
            <?php if (!empty($app['cover_letter'])): ?>
            <div class="modal fade" id="coverLetterModal<?= (int)$app['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Cover letter — <?= e($app['full_name']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0" style="white-space: pre-wrap;"><?= e($app['cover_letter']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= e($page - 1) ?>&status=<?= e($statusFilter) ?>&internship_id=<?= e($internshipFilter) ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= min($totalPages, 5); $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= e($i) ?>&status=<?= e($statusFilter) ?>&internship_id=<?= e($internshipFilter) ?>"><?= e($i) ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= e($page + 1) ?>&status=<?= e($statusFilter) ?>&internship_id=<?= e($internshipFilter) ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-inbox fs-1"></i>
            <p class="mt-3">No applications yet. Check back after students start applying!</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/portal-layout-end.php'; ?>
