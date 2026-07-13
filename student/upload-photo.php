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

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    
    if (!isset($_FILES['photo'])) {
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
            // Delete old photo if exists
            if ($student['profile_photo']) {
                delete_uploaded_file(UPLOAD_PHOTO_PATH, $student['profile_photo']);
            }
            
            // Update profile photo
            $stmt = $mysqli->prepare('UPDATE students SET profile_photo = ? WHERE id = ?');
            $stmt->bind_param('si', $result['path'], $student['id']);
            $stmt->execute();
            $stmt->close();
            
            $message = 'Photo uploaded successfully!';
            $student['profile_photo'] = $result['path'];
        } else {
            $error = $result['error'];
        }
    }
}

// Handle delete
if (isset($_GET['delete']) && $student['profile_photo']) {
    delete_uploaded_file(UPLOAD_PHOTO_PATH, $student['profile_photo']);
    $stmt = $mysqli->prepare('UPDATE students SET profile_photo = NULL WHERE id = ?');
    $stmt->bind_param('i', $student['id']);
    $stmt->execute();
    $stmt->close();
    $message = 'Photo deleted!';
    $student['profile_photo'] = null;
}
?>

require_once dirname(__DIR__) . '/includes/student-layout.php';
?>

<h1 class="page-title">Profile Photo</h1>
<p class="page-sub">Upload or update your profile picture</p>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="ds-card p-4 text-center">
            <?php if ($message): ?><div class="alert alert-success alert-dismissible fade show py-2 px-3 small"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if ($error):   ?><div class="alert alert-danger  alert-dismissible fade show py-2 px-3 small"><?= e($error)   ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

            <div style="margin-bottom:1.5rem;">
                <?php if ($student['profile_photo']): ?>
                    <img src="<?= e(app_url('uploads/photos/'.$student['profile_photo'])) ?>" alt="Profile" class="rounded-circle" style="width:140px;height:140px;object-fit:cover;border:3px solid #e8eaf0;">
                <?php else: ?>
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:140px;height:140px;background:#eff3ff;color:#1349cc;font-size:3rem;border:3px solid #e8eaf0;">
                        <i class="bi bi-person"></i>
                    </div>
                <?php endif; ?>
            </div>

            <form method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="mb-3 text-start">
                    <label class="form-label">Select Photo (JPG, PNG, WebP)</label>
                    <input type="file" class="form-control" name="photo" accept="image/jpeg,image/png,image/webp" required>
                    <div class="form-text" style="font-size:.72rem;color:#9ca3af;">Max 2 MB · Square image recommended</div>
                </div>
                <button type="submit" style="width:100%;background:#1349cc;color:#fff;border:none;border-radius:.6rem;padding:.65rem;font-weight:600;cursor:pointer;margin-bottom:.5rem;">Upload Photo</button>
            </form>

            <?php if ($student['profile_photo']): ?>
                <a href="?delete=1" onclick="return confirm('Delete your profile photo?')"
                   style="display:block;width:100%;border:1.5px solid #fee2e2;color:#ef4444;border-radius:.6rem;padding:.55rem;font-weight:500;text-decoration:none;margin-bottom:.5rem;">Delete Photo</a>
            <?php endif; ?>
            <a href="<?= e(app_url('student/profile.php')) ?>"
               style="display:block;width:100%;border:1.5px solid #e8eaf0;color:#374151;border-radius:.6rem;padding:.55rem;font-size:.875rem;text-decoration:none;">← Back to Profile</a>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
