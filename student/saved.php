<?php
$pageTitle   = 'Saved Internships';
$currentPage = 'saved';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
require_role(ROLE_STUDENT);

$userId  = current_user_id();
$student = get_student_by_user_id($mysqli, $userId);

// Handle unsave before output
if (isset($_GET['unsave'])) {
    $internshipId = (int)$_GET['unsave'];
    $stmt = $mysqli->prepare('DELETE FROM favorites WHERE student_id = ? AND internship_id = ?');
    $stmt->bind_param('ii', $student['id'], $internshipId);
    $stmt->execute(); $stmt->close();
    set_flash('success', 'Removed from saved.');
    redirect(app_url('student/saved.php'));
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM favorites WHERE student_id = ?');
$stmt->bind_param('i', $student['id']); $stmt->execute();
$totalRows  = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();
$totalPages = (int)ceil($totalRows / $perPage);
$offset     = ($page - 1) * $perPage;

$stmt = $mysqli->prepare(
    'SELECT i.*, c.company_name, c.logo, f.saved_at
     FROM favorites f
     JOIN internships i ON i.id = f.internship_id
     JOIN companies   c ON c.id = i.company_id
     WHERE f.student_id = ?
     ORDER BY f.saved_at DESC LIMIT ? OFFSET ?'
);
$stmt->bind_param('iii', $student['id'], $perPage, $offset);
$stmt->execute();
$saved = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once dirname(__DIR__) . '/includes/student-layout.php';
?>

<h1 class="page-title">Saved Internships</h1>
<p class="page-sub">Internships you've bookmarked for later</p>

<?php if (count($saved) > 0): ?>
    <div class="sp-list-stack">
    <?php foreach ($saved as $i): $co1 = strtoupper(substr($i['company_name'], 0, 1)); ?>
        <div class="ds-card p-4 sp-stack-row">
            <div class="sp-avatar sp-avatar-lg">
                <?php if ($i['logo']): ?>
                    <img src="<?= e(app_url('uploads/logos/'.$i['logo'])) ?>" style="width:48px;height:48px;object-fit:contain;border-radius:.6rem;" alt="">
                <?php else: ?><?= e($co1) ?><?php endif; ?>
            </div>
            <div class="sp-flex-grow">
                <div class="sp-title-lg"><?= e($i['title']) ?></div>
                <div class="sp-item-sub"><?= e($i['company_name']) ?> · <?= e($i['district'] ?? '') ?></div>
                <div class="sp-chip-row">
                    <?php if ($i['work_type']): ?><span class="sp-chip sp-chip--muted"><?= e($i['work_type']) ?></span><?php endif; ?>
                    <?php if ($i['stipend']): ?><span class="sp-chip sp-chip--green">Rs. <?= e(number_format($i['stipend'],0)) ?></span><?php endif; ?>
                    <?php if ($i['duration_months']): ?><span class="sp-chip sp-chip--blue"><?= e($i['duration_months']) ?> months</span><?php endif; ?>
                </div>
                <div class="sp-muted">
                    Saved: <?= e(date('M j, Y', strtotime($i['saved_at']))) ?>
                    <?php if ($i['application_deadline']): ?> · Deadline: <?= e(date('M j, Y', strtotime($i['application_deadline']))) ?><?php endif; ?>
                </div>
            </div>
            <div class="sp-action-row">
                <a href="<?= e(app_url('internship-detail.php?id='.$i['id'])) ?>" class="sp-btn-primary">View</a>
                <a href="?unsave=<?= e($i['id']) ?>" onclick="return confirm('Remove from saved?')" class="sp-btn-remove">Remove</a>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="sp-pager">
        <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>">← Prev</a><?php endif; ?>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?= $p ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="?page=<?= $page+1 ?>">Next →</a><?php endif; ?>
    </div>
    <?php endif; ?>

<?php else: ?>
<div class="ds-card sp-empty" style="padding:4rem 2rem;">
    <i class="bi bi-bookmark"></i>
    <p style="margin-bottom:.5rem;">You haven't saved any internships yet.</p>
    <a href="<?= e(app_url('internships.php')) ?>" class="sp-btn-link">Browse internships →</a>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
