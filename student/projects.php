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
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
        <div style="font-size:.9rem;font-weight:700;color:#111827;">Projects</div>
        <?php if ($action === 'list'): ?>
            <a href="?action=add" style="font-size:.8rem;font-weight:600;background:#1349cc;color:#fff;padding:.4rem .9rem;border-radius:.5rem;text-decoration:none;"><i class="bi bi-plus-lg"></i> Add Project</a>
        <?php else: ?>
            <a href="?action=list" style="font-size:.8rem;font-weight:500;border:1.5px solid #e8eaf0;color:#374151;padding:.4rem .9rem;border-radius:.5rem;text-decoration:none;">← Back</a>
        <?php endif; ?>
    </div>
    <?php if (isset($message)): ?><div class="alert alert-success alert-dismissible fade show py-2 px-3 small"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <?php if ($action === 'list'): ?>
        <?php if (count($projects) > 0): ?>
            <div style="display:flex;flex-direction:column;gap:.75rem;">
            <?php foreach ($projects as $p): ?>
                <div style="padding:1rem 1.25rem;border:1px solid #e8eaf0;border-radius:.6rem;display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
                    <div>
                        <div style="font-weight:600;color:#111827;font-size:.9rem;"><?= e($p['title']) ?></div>
                        <?php if ($p['description']): ?><div style="font-size:.8rem;color:#6b7280;margin:.15rem 0;"><?= e(substr($p['description'], 0, 120)) ?></div><?php endif; ?>
                        <?php if ($p['technologies']): ?><div style="font-size:.75rem;color:#9ca3af;">Tech: <?= e($p['technologies']) ?></div><?php endif; ?>
                    </div>
                    <div style="display:flex;gap:.4rem;flex-shrink:0;">
                        <a href="?action=edit&id=<?= e($p['id']) ?>" style="font-size:.75rem;border:1.5px solid #e8eaf0;color:#374151;padding:.3rem .65rem;border-radius:.45rem;text-decoration:none;">Edit</a>
                        <a href="?delete=<?= e($p['id']) ?>" onclick="return confirm('Delete?')" style="font-size:.75rem;background:#fee2e2;color:#ef4444;padding:.3rem .65rem;border-radius:.45rem;text-decoration:none;">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center;padding:3rem 1rem;color:#9ca3af;"><i class="bi bi-briefcase" style="font-size:2.5rem;display:block;margin-bottom:.75rem;color:#d1d5db;"></i>No projects added yet.</div>
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
            <div class="mt-3" style="display:flex;gap:.5rem;">
                <button type="submit" style="background:#1349cc;color:#fff;border:none;border-radius:.6rem;padding:.6rem 1.25rem;font-weight:600;cursor:pointer;">Save</button>
                <a href="?action=list" style="border:1.5px solid #e8eaf0;color:#374151;padding:.55rem 1rem;border-radius:.6rem;text-decoration:none;font-size:.875rem;">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
