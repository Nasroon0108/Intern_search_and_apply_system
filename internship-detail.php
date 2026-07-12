<?php
$pageTitle = 'Internship Details';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();

$internshipId = (int)($_GET['id'] ?? 0);

if (!$internshipId) {
    redirect(app_url('internships.php'));
}

// Get internship details
$stmt = $mysqli->prepare(
    'SELECT i.*, c.* 
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
while ($row = $stmt->get_result()->fetch_assoc()) {
    $skills[] = $row['name'];
}
$stmt->close();

// Check if student has already applied
$alreadyApplied = false;
$application = null;
if (is_logged_in() && current_user_role() === ROLE_STUDENT) {
    $userId = current_user_id();
    $student = get_student_by_user_id($mysqli, $userId);
    
    $stmt = $mysqli->prepare('SELECT * FROM applications WHERE student_id = ? AND internship_id = ?');
    $stmt->bind_param('ii', $student['id'], $internshipId);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $alreadyApplied = ($application !== null);
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
                    'INSERT INTO applications (student_id, internship_id, cv_id, cover_letter, status) VALUES (?, ?, ?, ?, ?)'
                );
                $appStatus = 'pending';
                $stmt->bind_param('iiiis', $student['id'], $internshipId, $cvId, $coverLetter, $appStatus);
                
                if ($stmt->execute()) {
                    $applyMessage = 'Application submitted successfully!';
                    $alreadyApplied = true;
                    
                    // Send confirmation email
                    send_application_confirmation_email($student['email'], $student, $internship, $internship);
                } else {
                    $applyError = 'Failed to submit application.';
                }
                $stmt->close();
            }
        }
    }
}

// Get student's CVs if logged in
$studentCvs = [];
if (is_logged_in() && current_user_role() === ROLE_STUDENT && $student) {
    $stmt = $mysqli->prepare('SELECT * FROM student_cvs WHERE student_id = ? ORDER BY is_primary DESC');
    $stmt->bind_param('i', $student['id']);
    $stmt->execute();
    $studentCvs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <h1 class="h2"><?= e($internship['title']) ?></h1>
                        <p class="text-muted mb-3"><?= e($internship['company_name']) ?></p>
                        
                        <div class="mb-3">
                            <?php if ($internship['work_type']): ?>
                                <span class="badge bg-light text-dark me-2"><?= e($internship['work_type']) ?></span>
                            <?php endif; ?>
                            <?php if ($internship['stipend']): ?>
                                <span class="badge bg-success me-2">Rs. <?= e(number_format($internship['stipend'], 0)) ?>/month</span>
                            <?php endif; ?>
                            <?php if ($internship['duration_months']): ?>
                                <span class="badge bg-info me-2"><?= e($internship['duration_months']) ?> months</span>
                            <?php endif; ?>
                            <span class="badge bg-secondary"><?= e($internship['vacancies']) ?> vacancies</span>
                        </div>

                        <hr>

                        <div class="row text-center text-md-start">
                            <div class="col-6 col-md-3 mb-3">
                                <small class="text-muted d-block">Location</small>
                                <strong><?= e($internship['district']) ?></strong>
                            </div>
                            <div class="col-6 col-md-3 mb-3">
                                <small class="text-muted d-block">Industry</small>
                                <strong><?= e($internship['industry'] ?? 'Not specified') ?></strong>
                            </div>
                            <div class="col-6 col-md-3 mb-3">
                                <small class="text-muted d-block">Posted</small>
                                <strong><?= e(date('M j, Y', strtotime($internship['created_at']))) ?></strong>
                            </div>
                            <div class="col-6 col-md-3 mb-3">
                                <small class="text-muted d-block">Deadline</small>
                                <strong><?= e($internship['application_deadline'] ? date('M j, Y', strtotime($internship['application_deadline'])) : 'Ongoing') ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($applyMessage): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= e($applyMessage) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <?php if ($applyError): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= e($applyError) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Job Description</h5>
                </div>
                <div class="card-body">
                    <h6>Responsibilities</h6>
                    <p><?= nl2br(e($internship['responsibilities'])) ?></p>

                    <h6 class="mt-4">Requirements</h6>
                    <p><?= nl2br(e($internship['requirements'])) ?></p>

                    <?php if (count($skills) > 0): ?>
                        <h6 class="mt-4">Required Skills</h6>
                        <div class="mb-3">
                            <?php foreach ($skills as $skill): ?>
                                <span class="badge bg-primary me-2 mb-2"><?= e($skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($internship['benefits']): ?>
                        <h6 class="mt-4">Benefits</h6>
                        <p><?= nl2br(e($internship['benefits'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">Contact Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Email:</strong> <a href="mailto:<?= e($internship['contact_email']) ?>"><?= e($internship['contact_email']) ?></a></p>
                    <?php if ($internship['contact_phone']): ?>
                        <p class="mb-0"><strong>Phone:</strong> <?= e($internship['contact_phone']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-body">
                    <?php if (!is_logged_in()): ?>
                        <a href="<?= e(app_url('auth/login.php')) ?>" class="btn btn-primary w-100 mb-2">Login to Apply</a>
                        <p class="text-center text-muted small mb-0">Don't have an account? <a href="<?= e(app_url('auth/register-student.php')) ?>">Register</a></p>
                    <?php elseif (current_user_role() !== ROLE_STUDENT): ?>
                        <p class="alert alert-info mb-0">Only students can apply for internships.</p>
                    <?php elseif ($alreadyApplied): ?>
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle"></i>
                            You have already applied for this internship.
                        </div>
                    <?php else: ?>
                        <form method="post">
                            <?= csrf_field() ?>
                            <h6 class="mb-3">Apply Now</h6>
                            
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
                                    <small class="text-danger">You need to upload a CV first. <a href="<?= e(app_url('student/cvs.php')) ?>">Upload CV</a></small>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Cover Letter</label>
                                <textarea class="form-control" name="cover_letter" rows="4" placeholder="Tell the company why you're interested in this opportunity..."></textarea>
                            </div>

                            <button type="submit" name="apply" class="btn btn-primary w-100" <?= count($studentCvs) === 0 ? 'disabled' : '' ?>>
                                Submit Application
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
