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
    <div style="display:flex;flex-direction:column;gap:1rem;">
    <?php foreach ($saved as $i): $co1 = strtoupper(substr($i['company_name'], 0, 1)); ?>
        <div class="ds-card p-4" style="display:flex;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
            <div style="width:48px;height:48px;border-radius:.6rem;background:#eff3ff;color:#1349cc;font-weight:700;font-size:1.1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <?php if ($i['logo']): ?>
                    <img src="<?= e(app_url('uploads/logos/'.$i['logo'])) ?>" style="width:48px;height:48px;object-fit:contain;border-radius:.6rem;" alt="">
                <?php else: ?><?= e($co1) ?><?php endif; ?>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:.15rem;"><?= e($i['title']) ?></div>
                <div style="font-size:.8rem;color:#6b7280;margin-bottom:.5rem;"><?= e($i['company_name']) ?> · <?= e($i['district'] ?? '') ?></div>
                <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.5rem;">
                    <?php if ($i['work_type']): ?><span style="font-size:.72rem;background:#f3f4f8;color:#374151;padding:.2rem .6rem;border-radius:2rem;"><?= e($i['work_type']) ?></span><?php endif; ?>
                    <?php if ($i['stipend']): ?><span style="font-size:.72rem;background:#f0fdf4;color:#166534;padding:.2rem .6rem;border-radius:2rem;">Rs. <?= e(number_format($i['stipend'],0)) ?></span><?php endif; ?>
                    <?php if ($i['duration_months']): ?><span style="font-size:.72rem;background:#eff6ff;color:#1d4ed8;padding:.2rem .6rem;border-radius:2rem;"><?= e($i['duration_months']) ?> months</span><?php endif; ?>
                </div>
                <div style="font-size:.75rem;color:#9ca3af;">
                    Saved: <?= e(date('M j, Y', strtotime($i['saved_at']))) ?>
                    <?php if ($i['application_deadline']): ?> · Deadline: <?= e(date('M j, Y', strtotime($i['application_deadline']))) ?><?php endif; ?>
                </div>
            </div>
            <div style="display:flex;gap:.5rem;flex-shrink:0;align-items:center;">
                <a href="<?= e(app_url('internship-detail.php?id='.$i['id'])) ?>"
                   style="font-size:.8rem;font-weight:600;background:#1349cc;color:#fff;padding:.4rem .9rem;border-radius:.5rem;text-decoration:none;">View</a>
                <a href="?unsave=<?= e($i['id']) ?>" onclick="return confirm('Remove from saved?')"
                   style="font-size:.8rem;font-weight:500;border:1.5px solid #fee2e2;color:#ef4444;padding:.4rem .75rem;border-radius:.5rem;text-decoration:none;">Remove</a>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;justify-content:center;gap:.5rem;margin-top:1.25rem;flex-wrap:wrap;">
        <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>" style="padding:.4rem .85rem;border:1.5px solid #e8eaf0;border-radius:.5rem;font-size:.8rem;text-decoration:none;color:#374151;">← Prev</a><?php endif; ?>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?= $p ?>" style="padding:.4rem .85rem;border:1.5px solid <?= $p===$page?'#1349cc':'#e8eaf0' ?>;border-radius:.5rem;font-size:.8rem;text-decoration:none;background:<?= $p===$page?'#1349cc':'#fff' ?>;color:<?= $p===$page?'#fff':'#374151' ?>;"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="?page=<?= $page+1 ?>" style="padding:.4rem .85rem;border:1.5px solid #e8eaf0;border-radius:.5rem;font-size:.8rem;text-decoration:none;color:#374151;">Next →</a><?php endif; ?>
    </div>
    <?php endif; ?>

<?php else: ?>
<div class="ds-card" style="text-align:center;padding:4rem 2rem;">
    <i class="bi bi-bookmark" style="font-size:2.5rem;color:#d1d5db;display:block;margin-bottom:.75rem;"></i>
    <p style="color:#9ca3af;margin-bottom:.5rem;">You haven't saved any internships yet.</p>
    <a href="<?= e(app_url('internships.php')) ?>" style="color:#1349cc;font-weight:600;font-size:.875rem;">Browse internships →</a>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
