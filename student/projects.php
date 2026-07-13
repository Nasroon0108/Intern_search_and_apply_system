<?php
$pageTitle   = 'My Projects';
$currentPage = 'projects';
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

require_once dirname(__DIR__) . '/includes/student-layout.php';
?>

<h1 class="page-title">My Projects</h1>
<p class="page-sub">Showcase your personal and academic projects</p>

<div class="ds-card p-4">
    <div class="sp-card-header">
        <div class="sp-heading" style="margin-bottom:0;">Projects</div>
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="sp-btn-primary"><i class="bi bi-plus-lg"></i> Add Project</a>
        <?php else: ?>
            <a href="?action=list" class="sp-btn-outline">← Back</a>
        <?php endif; ?>
    </div>
    <?php if (isset($message)): ?><div class="alert alert-success alert-dismissible fade show py-2 px-3 small"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <?php if ($action === 'list'): ?>
        <?php if (count($projects) > 0): ?>
            <div class="sp-list-stack">
            <?php foreach ($projects as $p): ?>
                <div class="sp-record-row">
                    <div>
                        <div class="sp-item-title"><?= e($p['title']) ?></div>
                        <?php if ($p['description']): ?><div class="sp-item-sub"><?= e(substr($p['description'], 0, 120)) ?></div><?php endif; ?>
                        <?php if ($p['technologies']): ?><div class="sp-muted">Tech: <?= e($p['technologies']) ?></div><?php endif; ?>
                    </div>
                    <div class="sp-record-actions">
                        <a href="?action=edit&id=<?= e($p['id']) ?>" class="sp-btn-outline">Edit</a>
                        <a href="?delete=<?= e($p['id']) ?>" onclick="return confirm('Delete?')" class="sp-btn-danger">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="sp-empty"><i class="bi bi-briefcase"></i>No projects added yet.</div>
        <?php endif; ?>
    <?php else: ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="<?= e($action) ?>">
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Project Title *</label><input type="text" class="form-control" name="title" required value="<?= e($project['title'] ?? '') ?>"></div>
                <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4"><?= e($project['description'] ?? '') ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Start Date</label><input type="date" class="form-control" name="start_date" value="<?= e($project['start_date'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">End Date</label><input type="date" class="form-control" name="end_date" value="<?= e($project['end_date'] ?? '') ?>"></div>
                <div class="col-12"><label class="form-label">Technologies</label><input type="text" class="form-control" name="technologies" placeholder="e.g. React, Node.js, PHP" value="<?= e($project['technologies'] ?? '') ?>"></div>
                <div class="col-12"><label class="form-label">Project URL</label><input type="url" class="form-control" name="project_url" value="<?= e($project['project_url'] ?? '') ?>"></div>
            </div>
            <div class="mt-3 sp-form-actions">
                <button type="submit" class="sp-btn-primary">Save</button>
                <a href="?action=list" class="sp-btn-outline">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
