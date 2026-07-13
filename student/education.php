<?php
$pageTitle   = 'My Education';
$currentPage = 'education';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
require_role(ROLE_STUDENT);

$userId = current_user_id();
$student = get_student_by_user_id($mysqli, $userId);

$action = $_GET['action'] ?? 'list';
$educationId = (int)($_GET['id'] ?? 0);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    
    $institution = trim($_POST['institution'] ?? '');
    $degree = trim($_POST['degree'] ?? '');
    $fieldOfStudy = trim($_POST['field_of_study'] ?? '');
    $startYear = !empty($_POST['start_year']) ? (int)$_POST['start_year'] : null;
    $endYear = !empty($_POST['end_year']) ? (int)$_POST['end_year'] : null;
    $gpa = !empty($_POST['gpa']) ? (float)$_POST['gpa'] : null;
    $description = trim($_POST['description'] ?? '');
    
    if (!$institution || !$degree) {
        $error = 'Institution and degree are required.';
    } else if ($_POST['_action'] === 'add') {
        $stmt = $mysqli->prepare(
            'INSERT INTO education (student_id, institution, degree, field_of_study, start_year, end_year, gpa, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('isssiids', $student['id'], $institution, $degree, $fieldOfStudy, $startYear, $endYear, $gpa, $description);
        if ($stmt->execute()) {
            $message = 'Education added successfully!';
            $action = 'list';
        } else {
            $error = 'Failed to add education.';
        }
        $stmt->close();
    } else if ($_POST['_action'] === 'edit' && $educationId) {
        $stmt = $mysqli->prepare(
            'UPDATE education SET institution = ?, degree = ?, field_of_study = ?, start_year = ?, end_year = ?, gpa = ?, description = ? WHERE id = ? AND student_id = ?'
        );
        $stmt->bind_param('sssiiidsi', $institution, $degree, $fieldOfStudy, $startYear, $endYear, $gpa, $description, $educationId, $student['id']);
        if ($stmt->execute()) {
            $message = 'Education updated successfully!';
            $action = 'list';
        } else {
            $error = 'Failed to update education.';
        }
        $stmt->close();
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $mysqli->prepare('DELETE FROM education WHERE id = ? AND student_id = ?');
    $stmt->bind_param('ii', $delId, $student['id']);
    $stmt->execute();
    $stmt->close();
    $action = 'list';
}

// Get education records
$stmt = $mysqli->prepare('SELECT * FROM education WHERE student_id = ? ORDER BY start_year DESC');
$stmt->bind_param('i', $student['id']);
$stmt->execute();
$educations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$education = null;
if ($action !== 'list' && $educationId) {
    foreach ($educations as $edu) {
        if ($edu['id'] === $educationId) {
            $education = $edu;
            break;
        }
    }
}

require_once dirname(__DIR__) . '/includes/student-layout.php';
?>

<h1 class="page-title">Education</h1>
<p class="page-sub">Add your academic qualifications and degrees</p>

<div class="ds-card p-4">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
        <div style="font-size:.9rem;font-weight:700;color:#111827;">Education Records</div>
        <?php if ($action === 'list'): ?>
            <a href="?action=add" style="font-size:.8rem;font-weight:600;background:#1349cc;color:#fff;padding:.4rem .9rem;border-radius:.5rem;text-decoration:none;"><i class="bi bi-plus-lg"></i> Add Education</a>
        <?php else: ?>
            <a href="?action=list" style="font-size:.8rem;font-weight:500;border:1.5px solid #e8eaf0;color:#374151;padding:.4rem .9rem;border-radius:.5rem;text-decoration:none;">← Back</a>
        <?php endif; ?>
    </div>

    <?php if (isset($message)): ?><div class="alert alert-success alert-dismissible fade show py-2 px-3 small"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if (isset($error)):   ?><div class="alert alert-danger  alert-dismissible fade show py-2 px-3 small"><?= e($error)   ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <?php if ($action === 'list'): ?>
        <?php if (count($educations) > 0): ?>
            <div style="display:flex;flex-direction:column;gap:.75rem;">
            <?php foreach ($educations as $edu): ?>
                <div style="padding:1rem 1.25rem;border:1px solid #e8eaf0;border-radius:.6rem;display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
                    <div>
                        <div style="font-weight:600;color:#111827;font-size:.9rem;"><?= e($edu['degree']) ?></div>
                        <div style="font-size:.8rem;color:#6b7280;margin:.15rem 0;"><?= e($edu['institution']) ?></div>
                        <?php if ($edu['field_of_study']): ?><div style="font-size:.75rem;color:#9ca3af;"><?= e($edu['field_of_study']) ?></div><?php endif; ?>
                        <?php if ($edu['start_year'] || $edu['end_year']): ?><div style="font-size:.75rem;color:#9ca3af;"><?= e($edu['start_year'] ?? '') ?> – <?= e($edu['end_year'] ?? 'Present') ?></div><?php endif; ?>
                    </div>
                    <div style="display:flex;gap:.4rem;flex-shrink:0;">
                        <a href="?action=edit&id=<?= e($edu['id']) ?>" style="font-size:.75rem;border:1.5px solid #e8eaf0;color:#374151;padding:.3rem .65rem;border-radius:.45rem;text-decoration:none;">Edit</a>
                        <a href="?delete=<?= e($edu['id']) ?>" onclick="return confirm('Delete this record?')" style="font-size:.75rem;background:#fee2e2;color:#ef4444;padding:.3rem .65rem;border-radius:.45rem;text-decoration:none;">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center;padding:3rem 1rem;color:#9ca3af;"><i class="bi bi-mortarboard" style="font-size:2.5rem;display:block;margin-bottom:.75rem;color:#d1d5db;"></i>No education added yet.</div>
        <?php endif; ?>
    <?php else: ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="<?= e($action) ?>">
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Institution *</label><input type="text" class="form-control" name="institution" required value="<?= e($education['institution'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Degree *</label><input type="text" class="form-control" name="degree" required value="<?= e($education['degree'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Field of Study</label><input type="text" class="form-control" name="field_of_study" value="<?= e($education['field_of_study'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Start Year</label><input type="number" class="form-control" name="start_year" min="1990" max="<?= date('Y') ?>" value="<?= e($education['start_year'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">End Year</label><input type="number" class="form-control" name="end_year" min="1990" max="<?= date('Y') + 10 ?>" value="<?= e($education['end_year'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">GPA</label><input type="number" class="form-control" name="gpa" min="0" max="4" step="0.01" value="<?= e($education['gpa'] ?? '') ?>"></div>
                <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?= e($education['description'] ?? '') ?></textarea></div>
            </div>
            <div class="mt-3" style="display:flex;gap:.5rem;">
                <button type="submit" style="background:#1349cc;color:#fff;border:none;border-radius:.6rem;padding:.6rem 1.25rem;font-weight:600;cursor:pointer;">Save</button>
                <a href="?action=list" style="border:1.5px solid #e8eaf0;color:#374151;padding:.55rem 1rem;border-radius:.6rem;text-decoration:none;font-size:.875rem;">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
