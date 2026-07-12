<?php
$pageTitle = 'My Applications';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_STUDENT);

$userId = current_user_id();
$student = get_student_by_user_id($mysqli, $userId);

$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

// Build query
$where = 'a.student_id = ?';
$params = [$student['id']];
$types = 'i';

if ($statusFilter !== 'all') {
    $where .= ' AND a.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM applications a WHERE $where";
$stmt = $mysqli->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalRows / $perPage);
$offset = ($page - 1) * $perPage;

// Get applications
$query = "
    SELECT a.*, i.title, i.district, i.work_type, i.stipend, c.company_name, c.logo
    FROM applications a
    JOIN internships i ON i.id = a.internship_id
    JOIN companies c ON c.id = i.company_id
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

// Handle withdraw
if (isset($_GET['withdraw'])) {
    $appId = (int)$_GET['withdraw'];
    $stmt = $mysqli->prepare('UPDATE applications SET status = ? WHERE id = ? AND student_id = ?');
    $newStatus = 'withdrawn';
    $stmt->bind_param('sii', $newStatus, $appId, $student['id']);
    $stmt->execute();
    $stmt->close();
    redirect(app_url('student/applications.php'));
}
?>

<div class="container py-5">
    <h2 class="mb-4">My Applications</h2>

    <div class="row mb-4">
        <div class="col-md-8">
            <div class="btn-group" role="group">
                <a href="<?= e(app_url('student/applications.php')) ?>" class="btn btn-outline-primary <?= ($statusFilter === 'all') ? 'active' : '' ?>">
                    All <span class="badge bg-primary ms-1"><?php 
                        $stmt = $mysqli->prepare('SELECT COUNT(*) as cnt FROM applications WHERE student_id = ?');
                        $stmt->bind_param('i', $student['id']);
                        $stmt->execute();
                        echo $stmt->get_result()->fetch_assoc()['cnt'];
                        $stmt->close();
                    ?></span>
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
    </div>

    <?php if (count($applications) > 0): ?>
        <div class="list-group">
            <?php foreach ($applications as $app): ?>
                <div class="list-group-item">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <?php if ($app['logo']): ?>
                                <img src="<?= e(app_url('uploads/logos/' . $app['logo'])) ?>" alt="Logo" style="width: 50px; height: 50px; object-fit: contain;">
                            <?php else: ?>
                                <div class="bg-secondary-subtle d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                    <i class="bi bi-briefcase"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col">
                            <h6 class="mb-1"><?= e($app['title']) ?></h6>
                            <p class="text-muted small mb-1"><?= e($app['company_name']) ?> • <?= e($app['district']) ?> • <?= e($app['work_type']) ?></p>
                            <small class="text-muted">
                                Applied: <?= e(date('M j, Y', strtotime($app['applied_at']))) ?>
                                <?php if ($app['stipend']): ?>
                                    | Rs. <?= e(number_format($app['stipend'], 0)) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <div class="col-auto text-end">
                            <?php 
                                $statusClass = match($app['status']) {
                                    'pending' => 'warning',
                                    'shortlisted' => 'info',
                                    'interview' => 'primary',
                                    'accepted' => 'success',
                                    'rejected' => 'danger',
                                    'withdrawn' => 'secondary',
                                    default => 'secondary'
                                };
                            ?>
                            <span class="badge bg-<?= e($statusClass) ?> text-capitalize mb-2"><?= e($app['status']) ?></span>
                            <?php if ($app['status'] === 'pending'): ?>
                                <br><a href="?withdraw=<?= e($app['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Withdraw this application?')">Withdraw</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= e($page - 1) ?>&status=<?= e($statusFilter) ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= e($i) ?>&status=<?= e($statusFilter) ?>"><?= e($i) ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= e($page + 1) ?>&status=<?= e($statusFilter) ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-inbox fs-1"></i>
            <p class="mt-3">No applications yet. <a href="<?= e(app_url('internships.php')) ?>">Start applying for internships</a></p>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
