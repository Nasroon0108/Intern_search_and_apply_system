<?php
$pageTitle   = 'My Profile';
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
$user = ['email' => $_SESSION['user_email']];

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $degreeProgram = trim($_POST['degree_program'] ?? '');
    $gpa = !empty($_POST['gpa']) ? (float)$_POST['gpa'] : null;
    $district = trim($_POST['district'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    if (!$fullName) {
        $error = 'Full name is required.';
    } else {
        $stmt = $mysqli->prepare(
            'UPDATE students SET full_name = ?, phone = ?, university = ?, degree_program = ?, gpa = ?, district = ?, province = ?, bio = ?, updated_at = NOW() WHERE user_id = ?'
        );
        $stmt->bind_param('ssssdsssi', $fullName, $phone, $university, $degreeProgram, $gpa, $district, $province, $bio, $userId);
        if ($stmt->execute()) {
            $message = 'Profile updated successfully!';
            $student = get_student_by_user_id($mysqli, $userId);
        } else {
            $error = 'Failed to update profile.';
        }
        $stmt->close();
    }
}

// Calculate profile completion
$profileCompletion = 20; // Base 20% for registered
if ($student['full_name']) $profileCompletion += 10;
if ($student['phone']) $profileCompletion += 5;
if ($student['university']) $profileCompletion += 10;
if ($student['gpa']) $profileCompletion += 10;
if ($student['profile_photo']) $profileCompletion += 10;
if ($student['bio']) $profileCompletion += 5;

// Check for education, skills, projects
$stmt = $mysqli->prepare('SELECT COUNT(*) as count FROM education WHERE student_id = ?');
$stmt->bind_param('i', $student['id']);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()['count'] > 0) $profileCompletion += 10;
$stmt->close();

$stmt = $mysqli->prepare('SELECT COUNT(*) as count FROM student_skills WHERE student_id = ?');
$stmt->bind_param('i', $student['id']);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()['count'] > 0) $profileCompletion += 10;
$stmt->close();

$profileCompletion = min($profileCompletion, 100);

// Update profile completion in database
$stmt = $mysqli->prepare('UPDATE students SET profile_completion = ? WHERE id = ?');
$stmt->bind_param('ii', $profileCompletion, $student['id']);
$stmt->execute();
$stmt->close();
?>

require_once dirname(__DIR__) . '/includes/student-layout.php';
?>

<h1 class="page-title">My Profile</h1>
<p class="page-sub">Manage your personal information and profile details</p>

<div class="row g-4">
    <!-- Left: photo + completion -->
    <div class="col-md-3">
        <div class="ds-card p-4 text-center mb-3">
            <?php if ($student['profile_photo']): ?>
                <img src="<?= e(app_url('uploads/photos/' . $student['profile_photo'])) ?>" alt="Profile" class="rounded-circle mb-3" style="width:100px;height:100px;object-fit:cover;">
            <?php else: ?>
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:100px;height:100px;background:#eff3ff;color:#1349cc;font-size:2.5rem;">
                    <i class="bi bi-person"></i>
                </div>
            <?php endif; ?>
            <div style="font-weight:700;font-size:.95rem;color:#111827;"><?= e($student['full_name']) ?></div>
            <div style="font-size:.78rem;color:#9ca3af;margin-bottom:1rem;"><?= e($user['email']) ?></div>
            <a href="<?= e(app_url('student/upload-photo.php')) ?>" style="font-size:.8rem;font-weight:600;color:#1349cc;background:#eff3ff;padding:.45rem 1rem;border-radius:.5rem;text-decoration:none;display:inline-block;">Upload Photo</a>
            <hr style="border-color:#e8eaf0;margin:1rem 0;">
            <div style="text-align:left;">
                <div style="font-size:.75rem;color:#9ca3af;margin-bottom:.4rem;">Profile Completion</div>
                <div style="background:#e8eaf0;border-radius:2px;height:8px;overflow:hidden;margin-bottom:.3rem;">
                    <div style="height:100%;width:<?= e($profileCompletion) ?>%;background:#1349cc;border-radius:2px;"></div>
                </div>
                <div style="font-size:.78rem;font-weight:700;color:#1349cc;"><?= e($profileCompletion) ?>%</div>
            </div>
        </div>
    </div>

    <!-- Right: form -->
    <div class="col-md-9">
        <div class="ds-card p-4">
            <div style="font-size:1rem;font-weight:700;color:#111827;margin-bottom:1.25rem;padding-bottom:.75rem;border-bottom:1px solid #e8eaf0;">Basic Information</div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show py-2 px-3 small"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" name="full_name" required value="<?= e($student['full_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" value="<?= e($student['phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">University</label>
                        <input type="text" class="form-control" name="university" value="<?= e($student['university'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Degree Program</label>
                        <input type="text" class="form-control" name="degree_program" value="<?= e($student['degree_program'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">GPA</label>
                        <input type="number" class="form-control" name="gpa" min="0" max="4" step="0.01" value="<?= e($student['gpa'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">District</label>
                        <select class="form-select" name="district">
                            <option value="">Select district</option>
                            <?php foreach (DISTRICTS as $d): ?>
                                <option value="<?= e($d) ?>" <?= ($student['district'] === $d) ? 'selected' : '' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Province</label>
                        <select class="form-select" name="province">
                            <option value="">Select province</option>
                            <?php foreach (PROVINCES as $p): ?>
                                <option value="<?= e($p) ?>" <?= ($student['province'] === $p) ? 'selected' : '' ?>><?= e($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" name="bio" rows="4" placeholder="Tell us about yourself..."><?= e($student['bio'] ?? '') ?></textarea>
                    </div>
                </div>
                <button type="submit" style="margin-top:1.25rem;background:#1349cc;color:#fff;border:none;border-radius:.6rem;padding:.65rem 1.5rem;font-weight:600;cursor:pointer;">Save Changes</button>
            </form>
        </div>
    </div>
</div>
