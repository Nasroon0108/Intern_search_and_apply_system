<?php
$pageTitle = 'My Education';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

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
        $stmt->bind_param('isssiiids', $student['id'], $institution, $degree, $fieldOfStudy, $startYear, $endYear, $gpa, $description);
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
        $stmt->bind_param('isssiiisi', $institution, $degree, $fieldOfStudy, $startYear, $endYear, $gpa, $description, $educationId, $student['id']);
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
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="list-group list-group-flush">
                    <a href="<?= e(app_url('student/profile.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-person"></i> Basic Info
                    </a>
                    <a href="<?= e(app_url('student/education.php')) ?>" class="list-group-item list-group-item-action active">
                        <i class="bi bi-mortarboard"></i> Education
                    </a>
                    <a href="<?= e(app_url('student/skills.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-star"></i> Skills
                    </a>
                    <a href="<?= e(app_url('student/projects.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-briefcase"></i> Projects
                    </a>
                    <a href="<?= e(app_url('student/certifications.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-award"></i> Certifications
                    </a>
                    <a href="<?= e(app_url('student/cvs.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-file-pdf"></i> CVs
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Education</h5>
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-sm btn-primary">Add Education</a>
                    <?php else: ?>
                        <a href="?action=list" class="btn btn-sm btn-secondary">Back</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success alert-dismissible fade show"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>

                    <?php if ($action === 'list'): ?>
                        <?php if (count($educations) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($educations as $edu): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?= e($edu['degree']) ?></h6>
                                                <p class="text-muted small mb-1"><?= e($edu['institution']) ?></p>
                                                <?php if ($edu['field_of_study']): ?>
                                                    <small class="text-muted"><?= e($edu['field_of_study']) ?></small>
                                                <?php endif; ?>
                                                <?php if ($edu['start_year'] || $edu['end_year']): ?>
                                                    <small class="text-muted d-block">
                                                        <?= e($edu['start_year'] ?? '') ?> - <?= e($edu['end_year'] ?? 'Present') ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <a href="?action=edit&id=<?= e($edu['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <a href="?delete=<?= e($edu['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No education added yet. <a href="?action=add">Add one now</a></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="post" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="_action" value="<?= e($action) ?>">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Institution *</label>
                                    <input type="text" class="form-control" name="institution" required value="<?= e($education['institution'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Degree *</label>
                                    <input type="text" class="form-control" name="degree" required value="<?= e($education['degree'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Field of Study</label>
                                    <input type="text" class="form-control" name="field_of_study" value="<?= e($education['field_of_study'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Start Year</label>
                                    <input type="number" class="form-control" name="start_year" min="1900" max="<?= date('Y') ?>" value="<?= e($education['start_year'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Year</label>
                                    <input type="number" class="form-control" name="end_year" min="1900" max="<?= date('Y') + 10 ?>" value="<?= e($education['end_year'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">GPA</label>
                                    <input type="number" class="form-control" name="gpa" min="0" max="4" step="0.01" value="<?= e($education['gpa'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"><?= e($education['description'] ?? '') ?></textarea>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a href="?action=list" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
