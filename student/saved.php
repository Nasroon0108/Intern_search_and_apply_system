<?php
$pageTitle = 'Saved Internships';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_STUDENT);

$userId = current_user_id();
$student = get_student_by_user_id($mysqli, $userId);

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

// Count total
$stmt = $mysqli->prepare('SELECT COUNT(*) as total FROM favorites WHERE student_id = ?');
$stmt->bind_param('i', $student['id']);
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalRows / $perPage);
$offset = ($page - 1) * $perPage;

// Get saved internships
$stmt = $mysqli->prepare(
    'SELECT i.*, c.company_name, c.logo, f.saved_at
     FROM favorites f
     JOIN internships i ON i.id = f.internship_id
     JOIN companies c ON c.id = i.company_id
     WHERE f.student_id = ?
     ORDER BY f.saved_at DESC
     LIMIT ? OFFSET ?'
);
$stmt->bind_param('iii', $student['id'], $perPage, $offset);
$stmt->execute();
$saved = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle unsave
if (isset($_GET['unsave'])) {
    $internshipId = (int)$_GET['unsave'];
    $stmt = $mysqli->prepare('DELETE FROM favorites WHERE student_id = ? AND internship_id = ?');
    $stmt->bind_param('ii', $student['id'], $internshipId);
    $stmt->execute();
    $stmt->close();
    redirect(app_url('student/saved.php'));
}
?>

<div class="container py-5">
    <h2 class="mb-4">Saved Internships</h2>

    <?php if (count($saved) > 0): ?>
        <div class="row g-3">
            <?php foreach ($saved as $internship): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-auto">
                                    <?php if ($internship['logo']): ?>
                                        <img src="<?= e(app_url('uploads/logos/' . $internship['logo'])) ?>" alt="Logo" style="width: 60px; height: 60px; object-fit: contain;">
                                    <?php else: ?>
                                        <div class="bg-secondary-subtle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                            <i class="bi bi-briefcase"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col">
                                    <h5 class="card-title mb-1"><?= e($internship['title']) ?></h5>
                                    <p class="text-muted small mb-2"><?= e($internship['company_name']) ?></p>
                                    
                                    <div class="mb-2">
                                        <?php if ($internship['work_type']): ?>
                                            <span class="badge bg-light text-dark me-1"><?= e($internship['work_type']) ?></span>
                                        <?php endif; ?>
                                        <?php if ($internship['stipend']): ?>
                                            <span class="badge bg-success me-1">Rs. <?= e(number_format($internship['stipend'], 0)) ?></span>
                                        <?php endif; ?>
                                        <?php if ($internship['duration_months']): ?>
                                            <span class="badge bg-info me-1"><?= e($internship['duration_months']) ?> months</span>
                                        <?php endif; ?>
                                    </div>

                                    <small class="text-muted">
                                        📍 <?= e($internship['district']) ?> | 
                                        Saved: <?= e(date('M j, Y', strtotime($internship['saved_at']))) ?>
                                        <?php if ($internship['application_deadline']): ?>
                                            | Deadline: <?= e(date('M j, Y', strtotime($internship['application_deadline']))) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="col-auto">
                                    <a href="<?= e(app_url('internship-detail.php?id=' . $internship['id'])) ?>" class="btn btn-primary">View Details</a>
                                    <a href="?unsave=<?= e($internship['id']) ?>" class="btn btn-outline-danger" onclick="return confirm('Remove from saved?')">Remove</a>
                                </div>
                            </div>
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
                            <a class="page-link" href="?page=<?= e($page - 1) ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= e($i) ?>"><?= e($i) ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= e($page + 1) ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-heart fs-1"></i>
            <p class="mt-3">You haven't saved any internships yet. <a href="<?= e(app_url('internships.php')) ?>">Browse internships</a> and save your favorites!</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
