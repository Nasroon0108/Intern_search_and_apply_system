<?php
$pageTitle = 'Moderate Internships';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_ADMIN);

$page = max(1, (int)($_GET['page'] ?? 1));
$statusFilter = $_GET['status'] ?? 'all';
$perPage = 10;

// Build query
$where = '1=1';
$params = [];
$types = '';

if ($statusFilter !== 'all') {
    $where .= ' AND i.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM internships i WHERE $where";
$stmt = $mysqli->prepare($countQuery);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalRows / $perPage);
$offset = ($page - 1) * $perPage;

// Get internships
$query = "
    SELECT i.id, i.title, i.status, i.created_at, i.vacancies, c.company_name,
           (SELECT COUNT(*) FROM applications WHERE internship_id = i.id) as applications_count
    FROM internships i
    JOIN companies c ON c.id = i.company_id
    WHERE $where
    ORDER BY i.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $mysqli->prepare($query);
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';
if (count($params) > 2) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$internships = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle actions
if (isset($_GET['action'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    $stmt = $mysqli->prepare('SELECT status FROM internships WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $internship = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($internship) {
        if ($action === 'approve') {
            $newStatus = 'active';
            $stmt = $mysqli->prepare('UPDATE internships SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $newStatus, $id);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Internship approved.');
        } elseif ($action === 'reject') {
            $newStatus = 'rejected';
            $stmt = $mysqli->prepare('UPDATE internships SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $newStatus, $id);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Internship rejected.');
        } elseif ($action === 'close') {
            $newStatus = 'closed';
            $stmt = $mysqli->prepare('UPDATE internships SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $newStatus, $id);
            $stmt->execute();
            $stmt->close();
            flash('success', 'Internship closed.');
        }
    }

    redirect(app_url("admin/internships.php?status=$statusFilter"));
}
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Moderate Internships</h2>
            <p class="text-muted">Review and approve internship postings</p>
        </div>
    </div>

    <div class="btn-group mb-4" role="group">
        <a href="<?= e(app_url('admin/internships.php')) ?>" class="btn btn-outline-primary <?= ($statusFilter === 'all') ? 'active' : '' ?>">
            All
        </a>
        <a href="?status=pending" class="btn btn-outline-primary <?= ($statusFilter === 'pending') ? 'active' : '' ?>">
            Pending Approval
        </a>
        <a href="?status=active" class="btn btn-outline-primary <?= ($statusFilter === 'active') ? 'active' : '' ?>">
            Active
        </a>
        <a href="?status=rejected" class="btn btn-outline-primary <?= ($statusFilter === 'rejected') ? 'active' : '' ?>">
            Rejected
        </a>
        <a href="?status=closed" class="btn btn-outline-primary <?= ($statusFilter === 'closed') ? 'active' : '' ?>">
            Closed
        </a>
    </div>

    <?php if (count($internships) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Company</th>
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
                            <td><strong><?= e($int['title']) ?></strong></td>
                            <td><?= e($int['company_name']) ?></td>
                            <td><?= e($int['vacancies']) ?></td>
                            <td><?= e($int['applications_count']) ?></td>
                            <td>
                                <span class="badge bg-<?= e($int['status'] === 'active' ? 'success' : ($int['status'] === 'pending' ? 'warning' : ($int['status'] === 'rejected' ? 'danger' : 'secondary'))) ?> text-capitalize">
                                    <?= e($int['status']) ?>
                                </span>
                            </td>
                            <td><?= e(date('M j, Y', strtotime($int['created_at']))) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= e(app_url('admin/internship-detail.php?id=' . $int['id'])) ?>" class="btn btn-outline-primary">View</a>
                                    <?php if ($int['status'] === 'pending'): ?>
                                        <a href="?action=approve&id=<?= e($int['id']) ?>" class="btn btn-outline-success" onclick="return confirm('Approve this internship?')">Approve</a>
                                        <a href="?action=reject&id=<?= e($int['id']) ?>" class="btn btn-outline-danger" onclick="return confirm('Reject this internship?')">Reject</a>
                                    <?php elseif ($int['status'] === 'active'): ?>
                                        <a href="?action=close&id=<?= e($int['id']) ?>" class="btn btn-outline-warning" onclick="return confirm('Close this internship?')">Close</a>
                                    <?php endif; ?>
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
            <p class="mt-3">No internships to display</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
