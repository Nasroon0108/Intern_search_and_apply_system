<?php
$pageTitle = 'My Projects';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_STUDENT);

$userId = current_user_id();
$student = get_student_by_user_id($mysqli, $userId);

$action = $_GET['action'] ?? 'list';
$projectId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $technologies = trim($_POST['technologies'] ?? '');
    $projectUrl = trim($_POST['project_url'] ?? '');
    $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    
    if (!$title) {
        $error = 'Project title is required.';
    } else if ($_POST['_action'] === 'add') {
        $stmt = $mysqli->prepare(
            'INSERT INTO projects (student_id, title, description, technologies, project_url, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issssss', $student['id'], $title, $description, $technologies, $projectUrl, $startDate, $endDate);
        if ($stmt->execute()) {
            $message = 'Project added successfully!';
            $action = 'list';
        }
        $stmt->close();
    } else if ($_POST['_action'] === 'edit' && $projectId) {
        $stmt = $mysqli->prepare(
            'UPDATE projects SET title = ?, description = ?, technologies = ?, project_url = ?, start_date = ?, end_date = ? WHERE id = ? AND student_id = ?'
        );
        $stmt->bind_param('isssssii', $title, $description, $technologies, $projectUrl, $startDate, $endDate, $projectId, $student['id']);
        if ($stmt->execute()) {
            $message = 'Project updated successfully!';
            $action = 'list';
        }
        $stmt->close();
    }
}

if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $mysqli->prepare('DELETE FROM projects WHERE id = ? AND student_id = ?');
    $stmt->bind_param('ii', $delId, $student['id']);
    $stmt->execute();
    $stmt->close();
    $action = 'list';
}

$stmt = $mysqli->prepare('SELECT * FROM projects WHERE student_id = ? ORDER BY start_date DESC');
$stmt->bind_param('i', $student['id']);
$stmt->execute();
$projects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$project = null;
if ($action !== 'list' && $projectId) {
    foreach ($projects as $p) {
        if ($p['id'] === $projectId) {
            $project = $p;
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
                    <a href="<?= e(app_url('student/education.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-mortarboard"></i> Education
                    </a>
                    <a href="<?= e(app_url('student/skills.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-star"></i> Skills
                    </a>
                    <a href="<?= e(app_url('student/projects.php')) ?>" class="list-group-item list-group-item-action active">
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
                    <h5 class="mb-0">My Projects</h5>
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-sm btn-primary">Add Project</a>
                    <?php else: ?>
                        <a href="?action=list" class="btn btn-sm btn-secondary">Back</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success alert-dismissible fade show"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>

                    <?php if ($action === 'list'): ?>
                        <?php if (count($projects) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($projects as $p): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?= e($p['title']) ?></h6>
                                                <p class="text-muted small mb-1"><?= e(substr($p['description'] ?? '', 0, 100)) ?></p>
                                                <?php if ($p['technologies']): ?>
                                                    <small class="text-muted">Tech: <?= e($p['technologies']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <a href="?action=edit&id=<?= e($p['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <a href="?delete=<?= e($p['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No projects added yet.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="post" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="_action" value="<?= e($action) ?>">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Project Title *</label>
                                    <input type="text" class="form-control" name="title" required value="<?= e($project['title'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="4"><?= e($project['description'] ?? '') ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" value="<?= e($project['start_date'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" value="<?= e($project['end_date'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Technologies</label>
                                    <input type="text" class="form-control" name="technologies" placeholder="e.g. React, Node.js, MongoDB" value="<?= e($project['technologies'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Project URL</label>
                                    <input type="url" class="form-control" name="project_url" value="<?= e($project['project_url'] ?? '') ?>">
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
