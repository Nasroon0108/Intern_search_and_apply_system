<?php
$pageTitle = 'My CVs';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_STUDENT);

$userId = current_user_id();
$student = get_student_by_user_id($mysqli, $userId);

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['_action'] === 'upload') {
    require_valid_csrf();
    
    if (!isset($_FILES['cv_file'])) {
        $error = 'No file uploaded.';
    } else {
        $result = handle_file_upload(
            $_FILES['cv_file'],
            UPLOAD_CV_PATH,
            ALLOWED_CV_TYPES,
            MAX_CV_SIZE,
            'cv_' . $student['id']
        );
        
        if ($result['success']) {
            $title = trim($_POST['title'] ?? 'My CV');
            $isPrimary = !empty($_POST['is_primary']);
            
            // If setting as primary, unset others
            if ($isPrimary) {
                $stmt = $mysqli->prepare('UPDATE student_cvs SET is_primary = 0 WHERE student_id = ?');
                $stmt->bind_param('i', $student['id']);
                $stmt->execute();
                $stmt->close();
            }
            
            $stmt = $mysqli->prepare('INSERT INTO student_cvs (student_id, title, file_path, is_primary) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('issi', $student['id'], $title, $result['path'], $isPrimary);
            $stmt->execute();
            $stmt->close();
            
            $message = 'CV uploaded successfully!';
        } else {
            $error = $result['error'];
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $cvId = (int)$_GET['delete'];
    $stmt = $mysqli->prepare('SELECT file_path FROM student_cvs WHERE id = ? AND student_id = ?');
    $stmt->bind_param('ii', $cvId, $student['id']);
    $stmt->execute();
    $cv = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($cv) {
        delete_uploaded_file(UPLOAD_CV_PATH, $cv['file_path']);
        $stmt = $mysqli->prepare('DELETE FROM student_cvs WHERE id = ?');
        $stmt->bind_param('i', $cvId);
        $stmt->execute();
        $stmt->close();
        $message = 'CV deleted successfully!';
    }
}

// Handle set primary
if (isset($_GET['primary'])) {
    $cvId = (int)$_GET['primary'];
    $stmt = $mysqli->prepare('UPDATE student_cvs SET is_primary = 0 WHERE student_id = ?');
    $stmt->bind_param('i', $student['id']);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $mysqli->prepare('UPDATE student_cvs SET is_primary = 1 WHERE id = ? AND student_id = ?');
    $stmt->bind_param('ii', $cvId, $student['id']);
    $stmt->execute();
    $stmt->close();
    $message = 'CV set as primary!';
}

$stmt = $mysqli->prepare('SELECT * FROM student_cvs WHERE student_id = ? ORDER BY is_primary DESC, uploaded_at DESC');
$stmt->bind_param('i', $student['id']);
$stmt->execute();
$cvs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
                    <a href="<?= e(app_url('student/projects.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-briefcase"></i> Projects
                    </a>
                    <a href="<?= e(app_url('student/certifications.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-award"></i> Certifications
                    </a>
                    <a href="<?= e(app_url('student/cvs.php')) ?>" class="list-group-item list-group-item-action active">
                        <i class="bi bi-file-pdf"></i> CVs
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">My CVs</h5>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>

                    <h6 class="mb-3">Upload New CV</h6>
                    <form method="post" enctype="multipart/form-data" class="mb-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_action" value="upload">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">CV Title</label>
                                <input type="text" class="form-control" name="title" value="My CV">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CV File (PDF) *</label>
                                <input type="file" class="form-control" name="cv_file" accept=".pdf" required>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="isPrimary" name="is_primary">
                                    <label class="form-check-label" for="isPrimary">Set as primary CV</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Upload CV</button>
                            </div>
                        </div>
                    </form>

                    <hr>
                    <h6 class="mb-3">Your CVs</h6>
                    <?php if (count($cvs) > 0): ?>
                        <div class="list-group">
                            <?php foreach ($cvs as $cv): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1">
                                                <?= e($cv['title']) ?>
                                                <?php if ($cv['is_primary']): ?>
                                                    <span class="badge bg-success">Primary</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted">Uploaded: <?= e(date('M j, Y', strtotime($cv['uploaded_at']))) ?></small>
                                        </div>
                                        <div>
                                            <?php if (!$cv['is_primary']): ?>
                                                <a href="?primary=<?= e($cv['id']) ?>" class="btn btn-sm btn-outline-info">Set Primary</a>
                                            <?php endif; ?>
                                            <a href="<?= e(app_url('uploads/cvs/' . $cv['file_path'])) ?>" class="btn btn-sm btn-outline-success" download>Download</a>
                                            <a href="?delete=<?= e($cv['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-4">No CVs uploaded yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
