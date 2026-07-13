<?php
$pageTitle   = 'My CVs';
$currentPage = 'cvs';
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

require_once dirname(__DIR__) . '/includes/student-layout.php';
?>

<h1 class="page-title">My CVs</h1>
<p class="page-sub">Upload and manage your CV documents</p>

<div class="row g-4">
    <!-- Upload form -->
    <div class="col-md-5">
        <div class="ds-card p-4">
            <div class="sp-heading">Upload New CV</div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show py-2 px-3 small"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="upload">
                <div class="mb-3">
                    <label class="form-label">CV Title</label>
                    <input type="text" class="form-control" name="title" value="My CV">
                </div>
                <div class="mb-3">
                    <label class="form-label">PDF File *</label>
                    <input type="file" class="form-control" name="cv_file" accept=".pdf" required>
                    <div class="form-text sp-muted">Max 5MB · PDF only</div>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="isPrimary" name="is_primary">
                    <label class="form-check-label small" for="isPrimary">Set as primary CV</label>
                </div>
                <button type="submit" class="sp-btn-primary">
                    <i class="bi bi-upload me-1"></i> Upload CV
                </button>
            </form>
        </div>
    </div>

    <!-- CV list -->
    <div class="col-md-7">
        <div class="ds-card p-4">
            <div class="sp-heading">Your CVs</div>
            <?php if (count($cvs) > 0): ?>
                <div class="sp-list-stack">
                <?php foreach ($cvs as $cv): ?>
                    <div class="sp-list-row">
                        <div class="sp-file-icon">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </div>
                        <div class="sp-flex-grow">
                            <div class="sp-item-title">
                                <?= e($cv['title']) ?>
                                <?php if ($cv['is_primary']): ?>
                                    <span class="sp-badge-primary">PRIMARY</span>
                                <?php endif; ?>
                            </div>
                            <div class="sp-muted">Uploaded: <?= e(date('M j, Y', strtotime($cv['uploaded_at']))) ?></div>
                        </div>
                        <div class="sp-record-actions">
                            <?php if (!$cv['is_primary']): ?>
                                <a href="?primary=<?= e($cv['id']) ?>" class="sp-btn-outline">Set Primary</a>
                            <?php endif; ?>
                            <a href="<?= e(app_url('uploads/cvs/'.$cv['file_path'])) ?>" download class="sp-btn-download">Download</a>
                            <a href="?delete=<?= e($cv['id']) ?>" onclick="return confirm('Delete this CV?')" class="sp-btn-danger">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="sp-empty" style="padding:2.5rem 1rem;">
                    <i class="bi bi-file-earmark-text"></i>
                    No CVs uploaded yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
