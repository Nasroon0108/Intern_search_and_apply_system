<?php
$pageTitle   = 'My Applications';
$currentPage = 'applications';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
require_role(ROLE_STUDENT);

$userId  = current_user_id();
$student = get_student_by_user_id($mysqli, $userId);

// Handle withdraw before any output
if (isset($_GET['withdraw'])) {
    $appId     = (int)$_GET['withdraw'];
    $newStatus = 'withdrawn';
    $stmt = $mysqli->prepare('UPDATE applications SET status = ? WHERE id = ? AND student_id = ?');
    $stmt->bind_param('sii', $newStatus, $appId, $student['id']);
    $stmt->execute();
    $stmt->close();
    set_flash('success', 'Application withdrawn.');
    redirect(app_url('student/applications.php'));
}

$statusFilter = $_GET['status'] ?? 'all';
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$where  = 'a.student_id = ?';
$params = [$student['id']];
$types  = 'i';

if ($statusFilter !== 'all') {
    $where   .= ' AND a.status = ?';
    $params[] = $statusFilter;
    $types   .= 's';
}

// Count total
$stmt = $mysqli->prepare("SELECT COUNT(*) as total FROM applications a WHERE $where");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRows  = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = (int)ceil($totalRows / $perPage);
$offset     = ($page - 1) * $perPage;

// Fetch page
$params[] = $perPage;
$params[] = $offset;
$types   .= 'ii';
$stmt = $mysqli->prepare(
    "SELECT a.*, i.title, i.district, i.work_type, i.stipend, c.company_name, c.logo
     FROM applications a
     JOIN internships i ON i.id = a.internship_id
     JOIN companies   c ON c.id = i.company_id
     WHERE $where ORDER BY a.applied_at DESC LIMIT ? OFFSET ?"
);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$applications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Tab counts
$statusCounts = [];
$stmtC = $mysqli->prepare(
    'SELECT status, COUNT(*) as cnt FROM applications WHERE student_id = ? GROUP BY status'
);
$stmtC->bind_param('i', $student['id']); $stmtC->execute();
$res = $stmtC->get_result();
while ($r = $res->fetch_assoc()) $statusCounts[$r['status']] = $r['cnt'];
$stmtC->close();
$totalAll = array_sum($statusCounts);

require_once dirname(__DIR__) . '/includes/student-layout.php';
?>

<h1 class="page-title">My Applications</h1>
<p class="page-sub">Track and manage all your internship applications</p>

<!-- Status filter tabs -->
<div class="ds-card p-3 mb-4 sp-tabs">
    <?php
    $tabs = [
        'all'         => 'All',
        'pending'     => 'Pending',
        'shortlisted' => 'Shortlisted',
        'interview'   => 'Interview',
        'accepted'    => 'Accepted',
        'rejected'    => 'Rejected',
    ];
    foreach ($tabs as $key => $label):
        $cnt     = $key === 'all' ? $totalAll : ($statusCounts[$key] ?? 0);
        $isActive = $statusFilter === $key;
    ?>
    <a href="?status=<?= e($key) ?>" class="sp-tab sp-tab--<?= e($key) ?><?= $isActive ? ' active' : '' ?>">
        <?= e($label) ?> <span style="opacity:.8;">(<?= $cnt ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<?php if (count($applications) > 0): ?>
<div class="ds-card sp-card-overflow">
    <table class="sp-table" style="width:100%;border-collapse:collapse;">
        <thead>
            <tr>
                <th style="padding-left:1.25rem;">Company &amp; Role</th>
                <th>Type</th>
                <th>Applied</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($applications as $app):
            $stClass = match($app['status']) {
                'pending'     => 'st-pending',
                'shortlisted' => 'st-shortlisted',
                'interview'   => 'st-interview',
                'accepted'    => 'st-accepted',
                'rejected'    => 'st-rejected',
                'withdrawn'   => 'st-withdrawn',
                default       => 'st-withdrawn',
            };
            $stLabel = match($app['status']) {
                'pending'     => 'Under Review',
                'shortlisted' => 'Shortlisted',
                'interview'   => 'Interview',
                'accepted'    => 'Offer Received',
                'rejected'    => 'Not Selected',
                'withdrawn'   => 'Withdrawn',
                default       => ucfirst($app['status']),
            };
            $co1 = strtoupper(substr($app['company_name'], 0, 1));
        ?>
        <tr>
            <td style="padding-left:1.25rem;">
                <div class="d-flex align-items-center gap-2">
                    <div class="sp-avatar">
                        <?php if ($app['logo']): ?>
                            <img src="<?= e(app_url('uploads/logos/' . $app['logo'])) ?>" style="width:36px;height:36px;object-fit:contain;border-radius:.5rem;" alt="">
                        <?php else: ?>
                            <?= e($co1) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="sp-item-title"><?= e($app['company_name']) ?></div>
                        <div class="sp-muted"><?= e($app['title']) ?> · <?= e($app['district'] ?? '') ?></div>
                    </div>
                </div>
            </td>
            <td><?= e($app['work_type'] ?? '—') ?></td>
            <td><?= e(date('M j, Y', strtotime($app['applied_at']))) ?></td>
            <td>
                <span class="sp-status <?= $stClass ?>"><?= e($stLabel) ?></span>
            </td>
            <td style="text-align:right;">
                <?php if ($app['status'] === 'pending'): ?>
                <a href="?withdraw=<?= e($app['id']) ?>"
                   onclick="return confirm('Withdraw this application?')"
                   class="sp-text-danger">Withdraw</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="sp-pager">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?>&status=<?= e($statusFilter) ?>">← Prev</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&status=<?= e($statusFilter) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page+1 ?>&status=<?= e($statusFilter) ?>">Next →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php else: ?>
<div class="ds-card sp-empty" style="padding:4rem 2rem;">
    <i class="bi bi-send"></i>
    <p style="margin-bottom:.5rem;">No applications <?= $statusFilter !== 'all' ? 'with status "'.e($statusFilter).'"' : 'yet' ?>.</p>
    <a href="<?= e(app_url('internships.php')) ?>" class="sp-btn-link">Browse internships →</a>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
