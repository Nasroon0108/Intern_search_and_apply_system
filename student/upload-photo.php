<?php
$pageTitle   = 'Upload Profile Photo';
$currentPage = 'profile';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
require_role(ROLE_STUDENT);

$userId = current_user_id();
$student = get_student_by_user_id($mysqli, $userId);

if (!$student) {
    die('Student profile not found.');
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    if (!isset($_FILES['photo']) || ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $error = 'No file uploaded.';
    } else {
        $result = handle_file_upload(
            $_FILES['photo'],
            UPLOAD_PHOTO_PATH,
            ALLOWED_PHOTO_TYPES,
            MAX_PHOTO_SIZE,
            'profile_' . $student['id']
        );

        if ($result['success']) {
            if ($student['profile_photo']) {
                delete_uploaded_file(UPLOAD_PHOTO_PATH, $student['profile_photo']);
            }

            $stmt = $mysqli->prepare('UPDATE students SET profile_photo = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('si', $result['path'], $student['id']);
                if ($stmt->execute()) {
                    $message = 'Photo uploaded successfully!';
                    $student['profile_photo'] = $result['path'];
                } else {
                    $error = 'Failed to save photo to profile.';
                }
                $stmt->close();
            } else {
                $error = 'Failed to update profile photo.';
            }
        } else {
            $error = $result['error'];
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete_photo') {
    require_valid_csrf();
    if (!empty($student['profile_photo'])) {
        delete_uploaded_file(UPLOAD_PHOTO_PATH, $student['profile_photo']);
        $stmt = $mysqli->prepare('UPDATE students SET profile_photo = NULL WHERE id = ?');
        $stmt->bind_param('i', $student['id']);
        $stmt->execute();
        $stmt->close();
        $message = 'Photo deleted!';
        $student['profile_photo'] = null;
    }
} elseif (isset($_GET['delete']) && $student['profile_photo']) {
    // Legacy GET support — prefer POST form below
    delete_uploaded_file(UPLOAD_PHOTO_PATH, $student['profile_photo']);
    $stmt = $mysqli->prepare('UPDATE students SET profile_photo = NULL WHERE id = ?');
    $stmt->bind_param('i', $student['id']);
    $stmt->execute();
    $stmt->close();
    $message = 'Photo deleted!';
    $student['profile_photo'] = null;
}

require_once dirname(__DIR__) . '/includes/student-layout.php';
?>

<h1 class="page-title">Profile Photo</h1>
<p class="page-sub">Upload or update your profile picture</p>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="ds-card p-4 text-center">
            <?php if ($message): ?><div class="alert alert-success alert-dismissible fade show py-2 px-3 small"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger  alert-dismissible fade show py-2 px-3 small"><?= e($error)   ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

            <div class="mb-4">
                <?php if ($student['profile_photo']): ?>
                    <img src="<?= e(app_url('uploads/photos/'.$student['profile_photo'])) ?>" alt="Profile" class="rounded-circle sp-photo-img" style="width:140px;height:140px;object-fit:cover;">
                <?php else: ?>
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center sp-photo-placeholder">
                        <i class="bi bi-person"></i>
                    </div>
                <?php endif; ?>
            </div>

            <form method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3 text-start">
                    <label class="form-label">Select Photo (JPG, PNG, WebP)</label>
                    <input type="file" class="form-control" name="photo" accept="image/jpeg,image/png,image/webp" required>
                    <div class="form-text sp-muted">Max 2 MB · Square image recommended</div>
                </div>
                <button type="submit" class="sp-btn-primary sp-btn-block">Upload Photo</button>
            </form>

            <?php if ($student['profile_photo']): ?>
                <form method="post" onsubmit="return confirm('Delete your profile photo?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="delete_photo">
                    <button type="submit" class="sp-btn-danger-block">Delete Photo</button>
                </form>
            <?php endif; ?>
            <a href="<?= e(app_url('student/profile.php')) ?>" class="sp-btn-outline-block">← Back to Profile</a>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
