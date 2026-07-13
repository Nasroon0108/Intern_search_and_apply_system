<?php
$pageTitle = 'Internship Details';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

init_session();

$internshipId = (int)($_GET['id'] ?? 0);

if (!$internshipId) {
    redirect(app_url('internships.php'));
}

// Get internship details
$stmt = $mysqli->prepare(
    'SELECT i.*, c.company_name, c.logo, c.description AS company_description, c.website, c.industry AS company_industry
     FROM internships i
     JOIN companies c ON c.id = i.company_id
     WHERE i.id = ? AND i.status = ?'
);
$status = 'active';
$stmt->bind_param('is', $internshipId, $status);
$stmt->execute();
$internship = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$internship) {
    redirect(app_url('internships.php'));
}

// Increment view count
$stmt = $mysqli->prepare('UPDATE internships SET views_count = views_count + 1 WHERE id = ?');
$stmt->bind_param('i', $internshipId);
$stmt->execute();
$stmt->close();

// Get skills
$skills = [];
$stmt = $mysqli->prepare(
    'SELECT s.name FROM skills s
     JOIN internship_skills isk ON isk.skill_id = s.id
     WHERE isk.internship_id = ?'
);
$stmt->bind_param('i', $internshipId);
$stmt->execute();
$skillsResult = $stmt->get_result();
while ($row = $skillsResult->fetch_assoc()) {
    $skills[] = $row['name'];
}
$stmt->close();

// Check if student has already applied
$alreadyApplied = false;
$application = null;
$isSaved = false;
$student = null;
if (is_logged_in() && current_user_role() === ROLE_STUDENT) {
    $userId = current_user_id();
    $student = get_student_by_user_id($mysqli, $userId);

    if ($student) {
        $stmt = $mysqli->prepare('SELECT * FROM applications WHERE student_id = ? AND internship_id = ?');
        $stmt->bind_param('ii', $student['id'], $internshipId);
        $stmt->execute();
        $application = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $alreadyApplied = ($application !== null);

        $stmt = $mysqli->prepare('SELECT student_id FROM favorites WHERE student_id = ? AND internship_id = ? LIMIT 1');
        $stmt->bind_param('ii', $student['id'], $internshipId);
        $stmt->execute();
        $isSaved = ($stmt->get_result()->fetch_assoc() !== null);
        $stmt->close();
    }
}

// Handle save/unsave
if (isset($_GET['toggle_save'])) {
    if (!is_logged_in() || current_user_role() !== ROLE_STUDENT) {
        redirect(app_url('auth/login.php'));
    }

    require_valid_csrf();
    
    $userId = current_user_id();
    $student = get_student_by_user_id($mysqli, $userId);

    if ($isSaved) {
        $stmt = $mysqli->prepare('DELETE FROM favorites WHERE student_id = ? AND internship_id = ?');
        $stmt->bind_param('ii', $student['id'], $internshipId);
        $stmt->execute();
        $stmt->close();
        $isSaved = false;
        set_flash('success', 'Removed from saved internships');
    } else {
        $stmt = $mysqli->prepare('INSERT INTO favorites (student_id, internship_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $student['id'], $internshipId);
        if ($stmt->execute()) {
            $isSaved = true;
            set_flash('success', 'Added to saved internships');
        }
        $stmt->close();
    }

    redirect(app_url("internship-detail.php?id=$internshipId"));
}

// Handle apply
$applyError = '';
$applyMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    require_valid_csrf();
    
    if (!is_logged_in() || current_user_role() !== ROLE_STUDENT) {
        $applyError = 'You must be logged in as a student to apply.';
    } else if ($alreadyApplied) {
        $applyError = 'You have already applied for this internship.';
    } else {
        if (!$student) {
            $student = get_student_by_user_id($mysqli, current_user_id());
        }

        if (!$student) {
            $applyError = 'Student profile not found.';
        } else {
        $coverLetter = trim($_POST['cover_letter'] ?? '');
        $cvId = (int)($_POST['cv_id'] ?? 0);
        
        if (!$cvId) {
            $applyError = 'Please select a CV.';
        } else {
            // Verify CV belongs to student
            $stmt = $mysqli->prepare('SELECT id FROM student_cvs WHERE id = ? AND student_id = ?');
            $stmt->bind_param('ii', $cvId, $student['id']);
            $stmt->execute();
            $cv = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$cv) {
                $applyError = 'Invalid CV selected.';
            } else {
                // Create application
                $stmt = $mysqli->prepare(
                    'INSERT INTO applications (student_id, internship_id, cv_id, cover_letter, status, applied_at) VALUES (?, ?, ?, ?, ?, NOW())'
                );
                $appStatus = 'pending';
                $stmt->bind_param('iiiss', $student['id'], $internshipId, $cvId, $coverLetter, $appStatus);
                
                if ($stmt->execute()) {
                    $applyMessage = 'Application submitted successfully!';
                    $alreadyApplied = true;
                    
                    $studentEmail = $_SESSION['user_email'] ?? '';
                    if ($studentEmail !== '') {
                        $company = ['company_name' => $internship['company_name'] ?? ''];
                        send_application_confirmation_email($studentEmail, $student, $internship, $company);
                    }
                } else {
                    $applyError = 'Failed to submit application.';
                }
                $stmt->close();
            }
        }
        }
    }
}

// Get student's CVs if logged in
$studentCvs = [];
if (is_logged_in() && current_user_role() === ROLE_STUDENT && $student) {
    $stmt = $mysqli->prepare('SELECT * FROM student_cvs WHERE student_id = ? ORDER BY is_primary DESC, uploaded_at DESC');
    $stmt->bind_param('i', $student['id']);
    $stmt->execute();
    $studentCvs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$flash      = get_flash();
$isLoggedIn = is_logged_in();
$userRole   = current_user_role();
$isStudent  = $isLoggedIn && $userRole === ROLE_STUDENT;
$currentPage = 'explore';
$pageTitle  = $internship['title'];

if ($isStudent && $student) {
    require_once __DIR__ . '/includes/student-layout.php';
} else {
    require_once __DIR__ . '/includes/header.php';
}
?>

<?php if (!$isStudent || !$student): ?><div class="container py-4"><?php endif; ?>

<a href="<?= e(app_url('internships.php')) ?>" class="back-link"><i class="bi bi-arrow-left"></i> Back to listings</a>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="detail-card">
            <div class="detail-header">
                <div class="detail-logo">
                    <?php if ($internship['logo']): ?>
                        <img src="<?= e(app_url('uploads/logos/' . $internship['logo'])) ?>" alt="">
                    <?php else: ?>
                        <?= e(strtoupper(substr($internship['company_name'], 0, 1))) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <h1 class="detail-title"><?= e($internship['title']) ?></h1>
                    <div class="detail-company"><?= e($internship['company_name']) ?></div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-1 mb-3">
                <?php if ($internship['work_type']): ?><span class="chip chip-gray"><?= e($internship['work_type']) ?></span><?php endif; ?>
                <?php if ($internship['stipend']): ?><span class="chip chip-green">Rs. <?= e(number_format($internship['stipend'], 0)) ?>/month</span><?php endif; ?>
                <?php if ($internship['duration_months']): ?><span class="chip chip-blue"><?= e($internship['duration_months']) ?> months</span><?php endif; ?>
                <?php if ($internship['industry']): ?><span class="chip chip-gray"><?= e($internship['industry']) ?></span><?php endif; ?>
                <?php if ($internship['vacancies']): ?><span class="chip chip-gray"><?= e($internship['vacancies']) ?> vacancies</span><?php endif; ?>
            </div>

            <div class="row g-3 detail-meta">
                <div class="col-6 col-md-3 text-center">
                    <div class="detail-meta-label">Location</div>
                    <div class="detail-meta-value"><?= e($internship['district'] ?? '—') ?></div>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <div class="detail-meta-label">Industry</div>
                    <div class="detail-meta-value"><?= e($internship['industry'] ?? '—') ?></div>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <div class="detail-meta-label">Posted</div>
                    <div class="detail-meta-value"><?= e(date('M j, Y', strtotime($internship['created_at']))) ?></div>
                </div>
                <div class="col-6 col-md-3 text-center">
                    <div class="detail-meta-label">Deadline</div>
                    <div class="detail-meta-value"><?= e($internship['application_deadline'] ? date('M j, Y', strtotime($internship['application_deadline'])) : 'Ongoing') ?></div>
                </div>
            </div>
        </div>

        <?php if ($applyMessage): ?><div class="alert alert-success alert-dismissible fade show py-2 px-3 small"><?= e($applyMessage) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if ($applyError): ?><div class="alert alert-danger alert-dismissible fade show py-2 px-3 small"><?= e($applyError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <div class="detail-card">
            <?php if ($internship['responsibilities']): ?>
                <div class="section-title">Responsibilities</div>
                <p class="detail-body-text"><?= nl2br(e($internship['responsibilities'])) ?></p>
            <?php endif; ?>

            <?php if ($internship['requirements']): ?>
                <div class="section-title">Requirements</div>
                <p class="detail-body-text"><?= nl2br(e($internship['requirements'])) ?></p>
            <?php endif; ?>

            <?php if (count($skills) > 0): ?>
                <div class="section-title">Required Skills</div>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <?php foreach ($skills as $skill): ?><span class="chip chip-blue"><?= e($skill) ?></span><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($internship['benefits']): ?>
                <div class="section-title">Benefits</div>
                <p class="detail-body-text"><?= nl2br(e($internship['benefits'])) ?></p>
            <?php endif; ?>

            <div class="section-title">Contact</div>
            <p class="detail-body-text mb-0">
                Email: <a href="mailto:<?= e($internship['contact_email']) ?>" class="text-primary"><?= e($internship['contact_email']) ?></a>
                <?php if ($internship['contact_phone']): ?> · Phone: <?= e($internship['contact_phone']) ?><?php endif; ?>
            </p>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="apply-card">
            <?php if ($isLoggedIn && $userRole === ROLE_STUDENT): ?>
                <form method="post" action="<?= e(app_url('internship-detail.php?id=' . $internshipId . '&toggle_save=1')) ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-save <?= $isSaved ? 'saved' : '' ?>">
                        <i class="bi bi-<?= $isSaved ? 'heart-fill' : 'heart' ?>"></i>
                        <?= $isSaved ? 'Remove from Saved' : 'Save Internship' ?>
                    </button>
                </form>
            <?php endif; ?>

            <?php if (!$isLoggedIn): ?>
                <a href="<?= e(app_url('auth/login.php')) ?>" class="btn-apply d-block text-center text-decoration-none mb-2">Login to Apply</a>
                <p class="text-center small text-muted mb-0">No account? <a href="<?= e(app_url('auth/register-student.php')) ?>">Register free</a></p>

            <?php elseif ($userRole !== ROLE_STUDENT): ?>
                <div class="status-box info">Only students can apply for internships.</div>

            <?php elseif ($alreadyApplied): ?>
                <div class="status-box success"><i class="bi bi-check-circle-fill"></i> You've already applied!</div>

            <?php else: ?>
                <div class="fw-bold mb-3">Apply Now</div>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Select CV *</label>
                        <select class="form-select" name="cv_id" required>
                            <option value="">Choose a CV</option>
                            <?php foreach ($studentCvs as $cv): ?>
                                <option value="<?= e($cv['id']) ?>" <?= $cv['is_primary'] ? 'selected' : '' ?>>
                                    <?= e($cv['title']) ?> <?= $cv['is_primary'] ? '(Primary)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (count($studentCvs) === 0): ?>
                            <div class="chip chip-yellow mt-2">
                                <a href="<?= e(app_url('student/cvs.php')) ?>" class="text-decoration-none fw-semibold">Upload a CV</a> to apply
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cover Letter <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" name="cover_letter" rows="4" placeholder="Why are you a great fit?"></textarea>
                    </div>
                    <button type="submit" name="apply" class="btn-apply" <?= count($studentCvs) === 0 ? 'disabled' : '' ?>>
                        Submit Application
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isStudent && $student): ?>
<?php require_once __DIR__ . '/includes/student-layout-end.php'; ?>
<?php else: ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php endif; ?>
