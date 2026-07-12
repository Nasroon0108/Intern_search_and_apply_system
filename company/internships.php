<?php
$pageTitle = 'Manage Internships';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_COMPANY);

$userId = current_user_id();
$company = get_company_by_user_id($mysqli, $userId);

if (!$company) {
    die('Company profile not found.');
}

$page = max(1, (int)($_GET['page'] ?? 1));
$statusFilter = $_GET['status'] ?? 'all';
$perPage = 10;

// Build query
$where = 'company_id = ?';
$params = [$company['id']];
$types = 'i';

if ($statusFilter !== 'all') {
    $where .= ' AND status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM internships WHERE $where";
$stmt = $mysqli->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalRows / $perPage);
$offset = ($page - 1) * $perPage;

// Get internships
$query = "
    SELECT id, title, status, created_at, vacancies,
           (SELECT COUNT(*) FROM applications WHERE internship_id = internships.id) as applications_count
    FROM internships
    WHERE $where
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $mysqli->prepare($query);
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$internships = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    $stmt = $mysqli->prepare('SELECT company_id FROM internships WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $int = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($int && $int['company_id'] === $company['id']) {
        if ($action === 'close') {
            $newStatus = 'closed';
            $stmt = $mysqli->prepare('UPDATE internships SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $newStatus, $id);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Internship closed.');
        } elseif ($action === 'activate') {
            $newStatus = 'active';
            $stmt = $mysqli->prepare('UPDATE internships SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $newStatus, $id);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Internship activated.');
        } elseif ($action === 'delete') {
            $stmt = $mysqli->prepare('DELETE FROM internships WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Internship deleted.');
        }
    }

    redirect(app_url("company/internships.php?status=$statusFilter"));
}
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Manage Internships</h2>
            <p class="text-muted">View and manage your posted internships</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?= e(app_url('company/post-internship.php')) ?>" class="btn btn-primary">Post New Internship</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="btn-group" role="group">
                <a href="<?= e(app_url('company/internships.php')) ?>" class="btn btn-outline-primary <?= ($statusFilter === 'all') ? 'active' : '' ?>">
                    All
                </a>
                <a href="?status=active" class="btn btn-outline-primary <?= ($statusFilter === 'active') ? 'active' : '' ?>">
                    Active
                </a>
                <a href="?status=pending" class="btn btn-outline-primary <?= ($statusFilter === 'pending') ? 'active' : '' ?>">
                    Pending Approval
                </a>
                <a href="?status=draft" class="btn btn-outline-primary <?= ($statusFilter === 'draft') ? 'active' : '' ?>">
                    Drafts
                </a>
                <a href="?status=closed" class="btn btn-outline-primary <?= ($statusFilter === 'closed') ? 'active' : '' ?>">
                    Closed
                </a>
            </div>
        </div>
    </div>

    <?php if (count($internships) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Vacancies</th>
                        <th>Applications</th>
                        <th>Status</th>
                        <th>Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($internships as $int): ?>
                        <tr>
                            <td>
                                <strong><?= e($int['title']) ?></strong>
                            </td>
                            <td><?= e($int['vacancies']) ?></td>
                            <td>
                                <a href="<?= e(app_url('company/applications.php?internship_id=' . $int['id'])) ?>">
                                    <?= e($int['applications_count']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge bg-<?= e($int['status'] === 'active' ? 'success' : ($int['status'] === 'pending' ? 'warning' : 'secondary')) ?> text-capitalize">
                                    <?= e($int['status']) ?>
                                </span>
                            </td>
                            <td><?= e(date('M j, Y', strtotime($int['created_at']))) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= e(app_url('company/post-internship.php?id=' . $int['id'])) ?>" class="btn btn-outline-primary">Edit</a>
                                    <?php if ($int['status'] === 'active'): ?>
                                        <a href="?action=close&id=<?= e($int['id']) ?>" class="btn btn-outline-warning" onclick="return confirm('Close this internship?')">Close</a>
                                    <?php elseif ($int['status'] === 'draft' || $int['status'] === 'closed'): ?>
                                        <a href="?action=activate&id=<?= e($int['id']) ?>" class="btn btn-outline-success">Activate</a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?= e($int['id']) ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this internship?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
            <i class="bi bi-briefcase fs-1"></i>
            <p class="mt-3">No internships posted yet. <a href="<?= e(app_url('company/post-internship.php')) ?>">Post your first internship</a></p>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
