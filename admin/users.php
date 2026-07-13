<?php
$pageTitle = 'Manage Users';
$currentPage = 'users';
$portalType = 'admin';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
require_role(ROLE_ADMIN);

$page = max(1, (int)($_GET['page'] ?? 1));
$roleFilter = $_GET['role'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$perPage = 15;

// Build query
$where = '1=1';
$params = [];
$types = '';

if ($roleFilter !== 'all') {
    $where .= ' AND role = ?';
    $params[] = $roleFilter;
    $types .= 's';
}

if ($statusFilter !== 'all') {
    $where .= ' AND status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

// Count total
$countQuery = "SELECT COUNT(*) as total FROM users WHERE $where";
$stmt = $mysqli->prepare($countQuery);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalRows / $perPage);
$offset = ($page - 1) * $perPage;

// Get users
$query = "
    SELECT u.id, u.email, u.role, u.status, u.email_verified, u.created_at,
           COALESCE(c.company_name, s.full_name) as display_name
    FROM users u
    LEFT JOIN companies c ON c.user_id = u.id
    LEFT JOIN students s ON s.user_id = u.id
    WHERE $where
    ORDER BY u.created_at DESC
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
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php require_once dirname(__DIR__) . '/includes/portal-layout.php'; ?>

<div>
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Manage Users</h2>
            <p class="text-muted">View and manage all user accounts</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <form method="GET" class="input-group">
                <input type="text" class="form-control" placeholder="Search by email" name="search" value="<?= e($_GET['search'] ?? '') ?>">
                <button class="btn btn-outline-secondary" type="submit">Search</button>
            </form>
        </div>
        <div class="col-md-3">
            <select class="form-select" onchange="window.location='?role='+this.value">
                <option value="all" <?= ($roleFilter === 'all') ? 'selected' : '' ?>>All Roles</option>
                <option value="<?= ROLE_STUDENT ?>" <?= ($roleFilter === ROLE_STUDENT) ? 'selected' : '' ?>>Students</option>
                <option value="<?= ROLE_COMPANY ?>" <?= ($roleFilter === ROLE_COMPANY) ? 'selected' : '' ?>>Companies</option>
                <option value="<?= ROLE_ADMIN ?>" <?= ($roleFilter === ROLE_ADMIN) ? 'selected' : '' ?>>Admins</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" onchange="window.location='?status='+this.value">
                <option value="all" <?= ($statusFilter === 'all') ? 'selected' : '' ?>>All Status</option>
                <option value="active" <?= ($statusFilter === 'active') ? 'selected' : '' ?>>Active</option>
                <option value="pending" <?= ($statusFilter === 'pending') ? 'selected' : '' ?>>Pending</option>
                <option value="inactive" <?= ($statusFilter === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                <option value="rejected" <?= ($statusFilter === 'rejected') ? 'selected' : '' ?>>Rejected</option>
            </select>
        </div>
    </div>

    <?php if (count($users) > 0): ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Email Verified</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= e($user['email']) ?></td>
                            <td><?= e($user['display_name'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= e($user['role'] === ROLE_STUDENT ? 'primary' : ($user['role'] === ROLE_COMPANY ? 'info' : 'warning')) ?>">
                                    <?= ucfirst(e($user['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= e($user['status'] === 'active' ? 'success' : ($user['status'] === 'pending' ? 'warning' : 'danger')) ?> text-capitalize">
                                    <?= e($user['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['email_verified']): ?>
                                    <i class="bi bi-check-circle text-success"></i> Yes
                                <?php else: ?>
                                    <i class="bi bi-x-circle text-danger"></i> No
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('M j, Y', strtotime($user['created_at']))) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= e(app_url('admin/user-detail.php?id=' . $user['id'])) ?>" class="btn btn-outline-primary">View</a>
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
                            <a class="page-link" href="?page=<?= e($page - 1) ?>&role=<?= e($roleFilter) ?>&status=<?= e($statusFilter) ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= min($totalPages, 5); $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= e($i) ?>&role=<?= e($roleFilter) ?>&status=<?= e($statusFilter) ?>"><?= e($i) ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($totalPages > 5): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= e($page + 1) ?>&role=<?= e($roleFilter) ?>&status=<?= e($statusFilter) ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-people fs-1"></i>
            <p class="mt-3">No users found</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/portal-layout-end.php'; ?>
