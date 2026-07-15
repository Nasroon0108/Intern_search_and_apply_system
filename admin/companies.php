<?php
$pageTitle = 'Manage Companies';
$currentPage = 'companies';
$portalType = 'admin';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
require_role(ROLE_ADMIN);

$page = max(1, (int)($_GET['page'] ?? 1));
$statusFilter = $_GET['status'] ?? 'all';
$perPage = 10;

// Build query
$where = '1=1';
$params = [];
$types = '';

if ($statusFilter === 'pending') {
    $where .= ' AND (u.status = ? OR c.verified = 0)';
    $params[] = 'pending';
    $types .= 's';
} elseif ($statusFilter === 'verified') {
    $where .= ' AND u.status = ? AND c.verified = 1';
    $params[] = 'active';
    $types .= 's';
} elseif ($statusFilter === 'rejected') {
    $where .= ' AND u.status = ?';
    $params[] = 'rejected';
    $types .= 's';
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM companies c JOIN users u ON u.id = c.user_id WHERE $where";
$stmt = $mysqli->prepare($countQuery);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalRows / $perPage);
$offset = ($page - 1) * $perPage;

// Get companies
$query = "
    SELECT c.id, c.company_name, c.industry, c.verified, u.email, u.status as user_status, u.created_at
    FROM companies c
    JOIN users u ON u.id = c.user_id
    WHERE $where
    ORDER BY c.created_at DESC
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
$companies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle actions
if (isset($_GET['action'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    $stmt = $mysqli->prepare('SELECT user_id FROM companies WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($company) {
        if ($action === 'verify') {
            $verificationStatus = 'approved';
            $stmt = $mysqli->prepare('UPDATE companies SET verified = 1, verification_status = ? WHERE id = ?');
            $stmt->bind_param('si', $verificationStatus, $id);
            $stmt->execute();
            $stmt->close();

            $userId = $company['user_id'];
            $newStatus = STATUS_ACTIVE;
            $stmt = $mysqli->prepare('UPDATE users SET status = ?, email_verified = 1 WHERE id = ?');
            $stmt->bind_param('si', $newStatus, $userId);
            $stmt->execute();
            $stmt->close();

            set_flash('success', 'Company verified successfully.');
        } elseif ($action === 'reject') {
            $userId = $company['user_id'];
            $newStatus = STATUS_BLOCKED;
            $stmt = $mysqli->prepare('UPDATE users SET status = ? WHERE id = ?');
            $stmt->bind_param('si', $newStatus, $userId);
            $stmt->execute();
            $stmt->close();

            $verificationStatus = 'rejected';
            $stmt = $mysqli->prepare('UPDATE companies SET verified = 0, verification_status = ? WHERE id = ?');
            $stmt->bind_param('si', $verificationStatus, $id);
            $stmt->execute();
            $stmt->close();

            set_flash('success', 'Company rejected.');
        }
    }

    redirect(app_url("admin/companies.php?status=$statusFilter"));
}
?>

<?php require_once dirname(__DIR__) . '/includes/portal-layout.php'; ?>

<div>
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Manage Companies</h2>
            <p class="text-muted">Review and verify company profiles</p>
        </div>
    </div>

    <div class="btn-group mb-4" role="group">
        <a href="<?= e(app_url('admin/companies.php')) ?>" class="btn btn-outline-primary <?= ($statusFilter === 'all') ? 'active' : '' ?>">
            All
        </a>
        <a href="?status=pending" class="btn btn-outline-primary <?= ($statusFilter === 'pending') ? 'active' : '' ?>">
            Pending
        </a>
        <a href="?status=verified" class="btn btn-outline-primary <?= ($statusFilter === 'verified') ? 'active' : '' ?>">
            Verified
        </a>
        <a href="?status=rejected" class="btn btn-outline-primary <?= ($statusFilter === 'rejected') ? 'active' : '' ?>">
            Rejected
        </a>
    </div>

    <?php if (count($companies) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Email</th>
                        <th>Industry</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td>
                                <strong><?= e($company['company_name']) ?></strong>
                            </td>
                            <td><?= e($company['email']) ?></td>
                            <td><?= e($company['industry'] ?? '-') ?></td>
                            <td>
                                <?php if ($company['user_status'] === 'active' && $company['verified']): ?>
                                    <span class="badge bg-success">Verified</span>
                                <?php elseif ($company['user_status'] === 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php elseif ($company['user_status'] === 'rejected'): ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Unknown</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('M j, Y', strtotime($company['created_at']))) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= e(app_url('admin/company-detail.php?id=' . $company['id'])) ?>" class="btn btn-outline-primary">View</a>
                                    <?php if ($company['user_status'] === 'pending'): ?>
                                        <a href="?action=verify&id=<?= e($company['id']) ?>" class="btn btn-outline-success" onclick="return confirm('Verify this company?')">Verify</a>
                                        <a href="?action=reject&id=<?= e($company['id']) ?>" class="btn btn-outline-danger" onclick="return confirm('Reject this company?')">Reject</a>
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
            <i class="bi bi-building fs-1"></i>
            <p class="mt-3">No companies to display</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/portal-layout-end.php'; ?>
