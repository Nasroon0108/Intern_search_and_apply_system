<?php
$pageTitle   = 'My Skills';
$currentPage = 'skills';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
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

require_once dirname(__DIR__) . '/includes/student-layout.php';
?>

<h1 class="page-title">My Skills</h1>
<p class="page-sub">Add and manage your technical and soft skills</p>

<div class="ds-card p-4">

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 px-3 small"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <?php if (count($student_skills) > 0): ?>
        <div class="mb-4">
            <div class="sp-heading-sm">Your Skills</div>
            <div class="sp-skill-tags">
                <?php foreach ($student_skills as $skillId => $proficiency):
                    $skillName = null;
                    foreach ($skills_by_category as $cat => $data)
                        foreach ($data['skills'] as $skill)
                            if ($skill['id'] == $skillId) { $skillName = $skill['name']; break 2; }
                ?>
                <span class="sp-skill-tag">
                    <?= e($skillName) ?> <span class="sp-tag-level">(<?= e($proficiency) ?>)</span>
                    <a href="?delete=<?= e($skillId) ?>" onclick="return confirm('Remove this skill?')" class="sp-tag-remove">×</a>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <hr class="sp-divider">
    <?php endif; ?>

    <div class="sp-heading-sm mb-3">Add Skills</div>
    <div class="row g-3">
        <?php foreach ($skills_by_category as $cat_key => $category):
            $parts   = explode(':', $cat_key);
            $catName = end($parts);
            $icon    = $category['type'] === 'technical' ? '💻' : '🎯';
        ?>
        <div class="col-md-6">
            <div class="sp-cat-title"><?= $icon ?> <?= e($catName) ?></div>
            <?php foreach ($category['skills'] as $skill):
                if (isset($student_skills[$skill['id']])) continue;
            ?>
            <form method="post" class="mb-1">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="add">
                <input type="hidden" name="skill_id" value="<?= e($skill['id']) ?>">
                <input type="hidden" name="proficiency" value="intermediate">
                <div class="sp-skill-row">
                    <span><?= e($skill['name']) ?></span>
                    <button type="submit" class="sp-btn-add">+ Add</button>
                </div>
            </form>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
