<?php
$pageTitle = 'My Skills';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_STUDENT);

$userId = current_user_id();
$student = get_student_by_user_id($mysqli, $userId);

$error = '';
$message = '';

// Get all skills grouped by category
$skills_by_category = [];
$stmt = $mysqli->query(
    'SELECT sc.id as cat_id, sc.name as cat_name, sc.type, s.id, s.name 
     FROM skill_categories sc 
     LEFT JOIN skills s ON s.category_id = sc.id 
     ORDER BY sc.type, sc.name, s.name'
);
while ($row = $stmt->fetch_assoc()) {
    $cat_key = $row['cat_id'] . ':' . $row['cat_name'];
    if (!isset($skills_by_category[$cat_key])) {
        $skills_by_category[$cat_key] = ['type' => $row['type'], 'skills' => []];
    }
    if ($row['id']) {
        $skills_by_category[$cat_key]['skills'][] = ['id' => $row['id'], 'name' => $row['name']];
    }
}

// Get student skills
$student_skills = [];
$stmt = $mysqli->prepare(
    'SELECT skill_id, proficiency FROM student_skills WHERE student_id = ?'
);
$stmt->bind_param('i', $student['id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $student_skills[$row['skill_id']] = $row['proficiency'];
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    
    $skillId = (int)$_POST['skill_id'];
    $proficiency = $_POST['proficiency'] ?? 'intermediate';
    
    if (!in_array($proficiency, ['beginner', 'intermediate', 'advanced'])) {
        $proficiency = 'intermediate';
    }
    
    if ($_POST['_action'] === 'add') {
        // Check if already added
        $stmt = $mysqli->prepare('SELECT id FROM student_skills WHERE student_id = ? AND skill_id = ?');
        $stmt->bind_param('ii', $student['id'], $skillId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            $stmt = $mysqli->prepare('INSERT INTO student_skills (student_id, skill_id, proficiency) VALUES (?, ?, ?)');
            $stmt->bind_param('iis', $student['id'], $skillId, $proficiency);
            $stmt->execute();
            $message = 'Skill added successfully!';
        } else {
            $stmt->close();
            $error = 'This skill is already added.';
        }
        $stmt->close();
    } else if ($_POST['_action'] === 'update') {
        $stmt = $mysqli->prepare('UPDATE student_skills SET proficiency = ? WHERE student_id = ? AND skill_id = ?');
        $stmt->bind_param('sii', $proficiency, $student['id'], $skillId);
        $stmt->execute();
        $message = 'Skill updated successfully!';
        $stmt->close();
    }
    
    // Refresh student skills
    $student_skills = [];
    $stmt = $mysqli->prepare('SELECT skill_id, proficiency FROM student_skills WHERE student_id = ?');
    $stmt->bind_param('i', $student['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $student_skills[$row['skill_id']] = $row['proficiency'];
    }
    $stmt->close();
}

// Handle delete
if (isset($_GET['delete'])) {
    $skillId = (int)$_GET['delete'];
    $stmt = $mysqli->prepare('DELETE FROM student_skills WHERE student_id = ? AND skill_id = ?');
    $stmt->bind_param('ii', $student['id'], $skillId);
    $stmt->execute();
    $stmt->close();
    unset($student_skills[$skillId]);
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
                    <a href="<?= e(app_url('student/skills.php')) ?>" class="list-group-item list-group-item-action active">
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
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">My Skills</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>

                    <?php if (count($student_skills) > 0): ?>
                        <h6 class="mb-3">Your Skills</h6>
                        <div class="row g-2 mb-4">
                            <?php foreach ($student_skills as $skillId => $proficiency): ?>
                                <?php
                                $skillName = null;
                                foreach ($skills_by_category as $cat => $data) {
                                    foreach ($data['skills'] as $skill) {
                                        if ($skill['id'] == $skillId) {
                                            $skillName = $skill['name'];
                                            break 2;
                                        }
                                    }
                                }
                                ?>
                                <div class="col-auto">
                                    <span class="badge bg-primary me-2"><?= e($skillName) ?> <span class="text-capitalize">(<?= e($proficiency) ?>)</span></span>
                                    <a href="?delete=<?= e($skillId) ?>" class="badge bg-danger text-decoration-none" onclick="return confirm('Remove this skill?')">×</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <hr>
                    <h6 class="mb-3">Add Skills</h6>
                    <div class="row g-3">
                        <?php foreach ($skills_by_category as $cat_key => $category): ?>
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= e($category['type'] === 'technical' ? '💻 Technical' : '🎯 Soft') ?> Skills - <?php 
                                    $parts = explode(':', $cat_key);
                                    echo e(end($parts));
                                ?></label>
                                <div class="btn-group-vertical w-100" role="group">
                                    <?php foreach ($category['skills'] as $skill): ?>
                                        <?php if (!isset($student_skills[$skill['id']])): ?>
                                            <form method="post" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="_action" value="add">
                                                <input type="hidden" name="skill_id" value="<?= e($skill['id']) ?>">
                                                <div class="d-flex gap-2 align-items-center p-2 border">
                                                    <input type="hidden" name="proficiency" value="intermediate">
                                                    <span class="flex-grow-1"><?= e($skill['name']) ?></span>
                                                    <button type="submit" class="btn btn-sm btn-outline-success">Add</button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
