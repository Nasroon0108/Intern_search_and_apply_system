<?php
$pageTitle = 'Upload Profile Photo';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h4 mb-1">Profile Photo</h2>
                    <p class="text-muted small mb-4">Upload or update your profile photo</p>

                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>

                    <div class="text-center mb-4">
                        <?php if ($student['profile_photo']): ?>
                            <img src="<?= e(app_url('uploads/photos/' . $student['profile_photo'])) ?>" alt="Profile" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary-subtle d-inline-flex align-items-center justify-content-center mb-3" style="width: 150px; height: 150px;">
                                <i class="bi bi-person fs-1"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Select Photo (JPG, PNG, WebP) *</label>
                            <input type="file" class="form-control" name="photo" accept="image/jpeg,image/png,image/webp" required>
                            <div class="form-text">Max 2 MB. Recommended: Square image (e.g., 500x500px)</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Upload Photo</button>
                    </form>

                    <?php if ($student['profile_photo']): ?>
                        <a href="?delete=1" class="btn btn-outline-danger w-100 mt-2" onclick="return confirm('Delete your profile photo?')">Delete Photo</a>
                    <?php endif; ?>

                    <a href="<?= e(app_url('student/profile.php')) ?>" class="btn btn-secondary w-100 mt-2">Back to Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
