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
    <div class="sp-card-header">
        <div class="sp-heading" style="margin-bottom:0;">Education Records</div>
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="sp-btn-primary"><i class="bi bi-plus-lg"></i> Add Education</a>
        <?php else: ?>
            <a href="?action=list" class="sp-btn-outline">← Back</a>
        <?php endif; ?>
    </div>

    <?php if (isset($message)): ?><div class="alert alert-success alert-dismissible fade show py-2 px-3 small"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if (isset($error)):   ?><div class="alert alert-danger  alert-dismissible fade show py-2 px-3 small"><?= e($error)   ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <?php if ($action === 'list'): ?>
        <?php if (count($educations) > 0): ?>
            <div class="sp-list-stack">
            <?php foreach ($educations as $edu): ?>
                <div class="sp-record-row">
                    <div>
                        <div class="sp-item-title"><?= e($edu['degree']) ?></div>
                        <div class="sp-item-sub"><?= e($edu['institution']) ?></div>
                        <?php if ($edu['field_of_study']): ?><div class="sp-muted"><?= e($edu['field_of_study']) ?></div><?php endif; ?>
                        <?php if ($edu['start_year'] || $edu['end_year']): ?><div class="sp-muted"><?= e($edu['start_year'] ?? '') ?> – <?= e($edu['end_year'] ?? 'Present') ?></div><?php endif; ?>
                    </div>
                    <div class="sp-record-actions">
                        <a href="?action=edit&id=<?= e($edu['id']) ?>" class="sp-btn-outline">Edit</a>
                        <a href="?delete=<?= e($edu['id']) ?>" onclick="return confirm('Delete this record?')" class="sp-btn-danger">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="sp-empty"><i class="bi bi-mortarboard"></i>No education added yet.</div>
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
            <div class="mt-3 sp-form-actions">
                <button type="submit" class="sp-btn-primary">Save</button>
                <a href="?action=list" class="sp-btn-outline">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
