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
<div class="ds-card p-3 mb-4" style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
    <?php
    $tabs = [
        'all'         => ['label' => 'All',         'color' => '#1349cc'],
        'pending'     => ['label' => 'Pending',      'color' => '#b45309'],
        'shortlisted' => ['label' => 'Shortlisted',  'color' => '#0369a1'],
        'interview'   => ['label' => 'Interview',    'color' => '#166534'],
        'accepted'    => ['label' => 'Accepted',     'color' => '#065f46'],
        'rejected'    => ['label' => 'Rejected',     'color' => '#991b1b'],
    ];
    foreach ($tabs as $key => $tab):
        $cnt     = $key === 'all' ? $totalAll : ($statusCounts[$key] ?? 0);
        $isActive = $statusFilter === $key;
    ?>
    <a href="?status=<?= e($key) ?>"
       style="font-size:.8rem;font-weight:600;padding:.35rem .85rem;border-radius:2rem;text-decoration:none;
              border:1.5px solid <?= $isActive ? $tab['color'] : '#e8eaf0' ?>;
              background:<?= $isActive ? $tab['color'] : '#fff' ?>;
              color:<?= $isActive ? '#fff' : '#6b7280' ?>;">
        <?= e($tab['label']) ?> <span style="opacity:.8;">(<?= $cnt ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<?php if (count($applications) > 0): ?>
<div class="ds-card" style="overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="background:#f8f9fc;">
                <th style="padding:.75rem 1.25rem;font-size:.72rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #e8eaf0;text-align:left;">Company &amp; Role</th>
                <th style="padding:.75rem 1rem;font-size:.72rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #e8eaf0;">Type</th>
                <th style="padding:.75rem 1rem;font-size:.72rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #e8eaf0;">Applied</th>
                <th style="padding:.75rem 1rem;font-size:.72rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #e8eaf0;">Status</th>
                <th style="padding:.75rem 1rem;font-size:.72rem;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid #e8eaf0;"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($applications as $app):
            $stClass = match($app['status']) {
                'pending'     => 'background:#fef9c3;color:#854d0e;',
                'shortlisted' => 'background:#dbeafe;color:#1d4ed8;',
                'interview'   => 'background:#dcfce7;color:#166534;',
                'accepted'    => 'background:#d1fae5;color:#065f46;',
                'rejected'    => 'background:#fee2e2;color:#991b1b;',
                'withdrawn'   => 'background:#f3f4f6;color:#6b7280;',
                default       => 'background:#f3f4f6;color:#6b7280;',
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
        <tr style="border-bottom:1px solid #f3f4f8;" onmouseover="this.style.background='#fafbff'" onmouseout="this.style.background=''">
            <td style="padding:1rem 1.25rem;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <div style="width:36px;height:36px;border-radius:.5rem;background:#eff3ff;color:#1349cc;font-weight:700;font-size:.9rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <?php if ($app['logo']): ?>
                            <img src="<?= e(app_url('uploads/logos/' . $app['logo'])) ?>" style="width:36px;height:36px;object-fit:contain;border-radius:.5rem;" alt="">
                        <?php else: ?>
                            <?= e($co1) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div style="font-size:.875rem;font-weight:600;color:#111827;"><?= e($app['company_name']) ?></div>
                        <div style="font-size:.75rem;color:#9ca3af;"><?= e($app['title']) ?> · <?= e($app['district'] ?? '') ?></div>
                    </div>
                </div>
            </td>
            <td style="padding:1rem;font-size:.78rem;color:#6b7280;"><?= e($app['work_type'] ?? '—') ?></td>
            <td style="padding:1rem;font-size:.78rem;color:#6b7280;"><?= e(date('M j, Y', strtotime($app['applied_at']))) ?></td>
            <td style="padding:1rem;">
                <span style="font-size:.7rem;font-weight:700;padding:.25rem .65rem;border-radius:.35rem;text-transform:uppercase;letter-spacing:.04em;<?= $stClass ?>"><?= e($stLabel) ?></span>
            </td>
            <td style="padding:1rem;text-align:right;">
                <?php if ($app['status'] === 'pending'): ?>
                <a href="?withdraw=<?= e($app['id']) ?>"
                   onclick="return confirm('Withdraw this application?')"
                   style="font-size:.78rem;color:#ef4444;text-decoration:none;font-weight:500;">Withdraw</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div style="display:flex;justify-content:center;gap:.5rem;margin-top:1.25rem;flex-wrap:wrap;">
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page-1 ?>&status=<?= e($statusFilter) ?>" style="padding:.4rem .85rem;border:1.5px solid #e8eaf0;border-radius:.5rem;font-size:.8rem;text-decoration:none;color:#374151;">← Prev</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?= $i ?>&status=<?= e($statusFilter) ?>"
           style="padding:.4rem .85rem;border:1.5px solid <?= $i===$page?'#1349cc':'#e8eaf0' ?>;border-radius:.5rem;font-size:.8rem;text-decoration:none;
                  background:<?= $i===$page?'#1349cc':'#fff' ?>;color:<?= $i===$page?'#fff':'#374151' ?>;"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page+1 ?>&status=<?= e($statusFilter) ?>" style="padding:.4rem .85rem;border:1.5px solid #e8eaf0;border-radius:.5rem;font-size:.8rem;text-decoration:none;color:#374151;">Next →</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php else: ?>
<div class="ds-card" style="text-align:center;padding:4rem 2rem;">
    <i class="bi bi-send" style="font-size:2.5rem;color:#d1d5db;display:block;margin-bottom:.75rem;"></i>
    <p style="color:#9ca3af;margin-bottom:.5rem;">No applications <?= $statusFilter !== 'all' ? 'with status "'.e($statusFilter).'"' : 'yet' ?>.</p>
    <a href="<?= e(app_url('internships.php')) ?>" style="color:#1349cc;font-weight:600;font-size:.875rem;">Browse internships →</a>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
