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
    
    $stmt = $mysqli->prepare('SELECT * FROM applications WHERE student_id = ? AND internship_id = ?');
    $stmt->bind_param('ii', $student['id'], $internshipId);
    $stmt->execute();
    $application = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $alreadyApplied = ($application !== null);

    // Check if saved
    $stmt = $mysqli->prepare('SELECT student_id FROM favorites WHERE student_id = ? AND internship_id = ? LIMIT 1');
    $stmt->bind_param('ii', $student['id'], $internshipId);
    $stmt->execute();
    $isSaved = ($stmt->get_result()->fetch_assoc() !== null);
    $stmt->close();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($internship['title']) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
    <style>
        body { background:#f3f4f8; }
        .top-nav { background:#fff;border-bottom:1px solid #e8eaf0;padding:.7rem 2rem;display:flex;align-items:center;gap:1.5rem;position:sticky;top:0;z-index:100; }
        .top-nav .brand { display:flex;align-items:center;gap:.55rem;text-decoration:none; }
        .top-nav .brand .brand-icon { width:32px;height:32px;border-radius:8px;background:#1349cc;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.95rem; }
        .top-nav .brand .brand-name  { font-weight:700;font-size:.95rem;color:#111827; }
        .top-nav .nav-links { display:flex;gap:1.25rem;margin-left:1.5rem; }
        .top-nav .nav-links a { font-size:.85rem;color:#6b7280;text-decoration:none;font-weight:500; }
        .top-nav .nav-links a:hover { color:#1349cc; }
        .top-nav .nav-right { margin-left:auto;display:flex;align-items:center;gap:.75rem; }
        .btn-nav-outline { border:1.5px solid #d1d5db;background:#fff;color:#374151;border-radius:.5rem;padding:.35rem .85rem;font-size:.82rem;font-weight:500;text-decoration:none; }
        .btn-nav-primary { background:#1349cc;color:#fff;border:none;border-radius:.5rem;padding:.35rem .85rem;font-size:.82rem;font-weight:600;text-decoration:none; }
        .btn-nav-primary:hover { background:#1038a8;color:#fff; }
        .page-wrap { max-width:1100px;margin:0 auto;padding:2rem 1.5rem; }
        .detail-card { background:#fff;border:1px solid #e8eaf0;border-radius:.75rem;padding:1.75rem;margin-bottom:1rem; }
        .chip { font-size:.72rem;padding:.25rem .65rem;border-radius:2rem;font-weight:500; }
        .chip-gray  { background:#f3f4f8;color:#374151; }
        .chip-green { background:#f0fdf4;color:#166534; }
        .chip-blue  { background:#eff6ff;color:#1d4ed8; }
        .section-title { font-size:.75rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.07em;margin-bottom:.75rem;margin-top:1.25rem; }
        .apply-card { background:#fff;border:1px solid #e8eaf0;border-radius:.75rem;padding:1.5rem;position:sticky;top:5rem; }
        .btn-apply { width:100%;background:#1349cc;color:#fff;border:none;border-radius:.6rem;padding:.7rem;font-weight:700;font-size:.95rem;cursor:pointer; }
        .btn-apply:hover { background:#1038a8; }
        .btn-apply:disabled { background:#9ca3af;cursor:not-allowed; }
        .form-label { font-size:.82rem;font-weight:500;color:#374151;margin-bottom:.3rem; }
        .form-control, .form-select { border-radius:.55rem;border-color:#e8eaf0;font-size:.875rem; }
        .form-control:focus, .form-select:focus { border-color:#1349cc;box-shadow:0 0 0 3px rgba(19,73,204,.1); }
    </style>
</head>
<body>

<nav class="top-nav">
    <a href="<?= e(app_url('index.php')) ?>" class="brand">
        <div class="brand-icon"><i class="bi bi-briefcase-fill"></i></div>
        <div class="brand-name">InternConnect</div>
    </a>
    <div class="nav-links">
        <a href="<?= e(app_url('index.php')) ?>">Home</a>
        <a href="<?= e(app_url('internships.php')) ?>">Explore</a>
        <?php if ($isLoggedIn): ?>
            <a href="<?= e(app_url(match($userRole){ 'student'=>'student/dashboard.php','company'=>'company/dashboard.php','admin'=>'admin/dashboard.php',default=>'index.php'})) ?>">Dashboard</a>
        <?php endif; ?>
    </div>
    <div class="nav-right">
        <?php if ($isLoggedIn): ?>
            <a href="<?= e(app_url('auth/logout.php')) ?>" class="btn-nav-outline">Logout</a>
        <?php else: ?>
            <a href="<?= e(app_url('auth/login.php')) ?>" class="btn-nav-outline">Login</a>
            <a href="<?= e(app_url('auth/register-student.php')) ?>" class="btn-nav-primary">Register</a>
        <?php endif; ?>
    </div>
</nav>

<?php if ($flash): ?>
<div style="background:#fff;padding:.5rem 2rem;border-bottom:1px solid #e8eaf0;">
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show py-2 px-3 small mb-0">
        <?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<div class="page-wrap">
    <div style="margin-bottom:1rem;">
        <a href="<?= e(app_url('internships.php')) ?>" style="font-size:.82rem;color:#6b7280;text-decoration:none;"><i class="bi bi-arrow-left"></i> Back to listings</a>
    </div>

    <div class="row g-4">
        <!-- Left: Details -->
        <div class="col-lg-8">
            <div class="detail-card">
                <div style="display:flex;align-items:flex-start;gap:1rem;margin-bottom:1.25rem;">
                    <div style="width:56px;height:56px;border-radius:.75rem;background:#eff3ff;color:#1349cc;font-weight:700;font-size:1.3rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <?php if ($internship['logo']): ?>
                            <img src="<?= e(app_url('uploads/logos/'.$internship['logo'])) ?>" style="width:56px;height:56px;object-fit:contain;border-radius:.75rem;" alt="">
                        <?php else: ?>
                            <?= e(strtoupper(substr($internship['company_name'],0,1))) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1 style="font-size:1.35rem;font-weight:800;color:#111827;margin-bottom:.2rem;"><?= e($internship['title']) ?></h1>
                        <div style="font-size:.875rem;color:#6b7280;"><?= e($internship['company_name']) ?></div>
                    </div>
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1.25rem;">
                    <?php if ($internship['work_type']): ?><span class="chip chip-gray"><?= e($internship['work_type']) ?></span><?php endif; ?>
                    <?php if ($internship['stipend']): ?><span class="chip chip-green">Rs. <?= e(number_format($internship['stipend'],0)) ?>/month</span><?php endif; ?>
                    <?php if ($internship['duration_months']): ?><span class="chip chip-blue"><?= e($internship['duration_months']) ?> months</span><?php endif; ?>
                    <?php if ($internship['industry']): ?><span class="chip chip-gray"><?= e($internship['industry']) ?></span><?php endif; ?>
                    <?php if ($internship['vacancies']): ?><span class="chip chip-gray"><?= e($internship['vacancies']) ?> vacancies</span><?php endif; ?>
                </div>

                <div class="row g-3" style="background:#f8f9fc;border-radius:.6rem;padding:.75rem .5rem;margin-bottom:1.25rem;">
                    <div class="col-6 col-md-3 text-center">
                        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:.2rem;">Location</div>
                        <div style="font-size:.85rem;font-weight:600;color:#111827;"><?= e($internship['district'] ?? '—') ?></div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:.2rem;">Industry</div>
                        <div style="font-size:.85rem;font-weight:600;color:#111827;"><?= e($internship['industry'] ?? '—') ?></div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:.2rem;">Posted</div>
                        <div style="font-size:.85rem;font-weight:600;color:#111827;"><?= e(date('M j, Y', strtotime($internship['created_at']))) ?></div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div style="font-size:.7rem;color:#9ca3af;margin-bottom:.2rem;">Deadline</div>
                        <div style="font-size:.85rem;font-weight:600;color:#111827;"><?= e($internship['application_deadline'] ? date('M j, Y', strtotime($internship['application_deadline'])) : 'Ongoing') ?></div>
                    </div>
                </div>
            </div>

            <?php if ($applyMessage): ?><div class="alert alert-success alert-dismissible fade show py-2 px-3 small"><?= e($applyMessage) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if ($applyError):   ?><div class="alert alert-danger  alert-dismissible fade show py-2 px-3 small"><?= e($applyError)   ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

            <div class="detail-card">
                <?php if ($internship['responsibilities']): ?>
                    <div class="section-title">Responsibilities</div>
                    <p style="font-size:.875rem;color:#374151;line-height:1.7;"><?= nl2br(e($internship['responsibilities'])) ?></p>
                <?php endif; ?>

                <?php if ($internship['requirements']): ?>
                    <div class="section-title">Requirements</div>
                    <p style="font-size:.875rem;color:#374151;line-height:1.7;"><?= nl2br(e($internship['requirements'])) ?></p>
                <?php endif; ?>

                <?php if (count($skills) > 0): ?>
                    <div class="section-title">Required Skills</div>
                    <div style="display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.5rem;">
                        <?php foreach ($skills as $skill): ?><span class="chip chip-blue"><?= e($skill) ?></span><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($internship['benefits']): ?>
                    <div class="section-title">Benefits</div>
                    <p style="font-size:.875rem;color:#374151;line-height:1.7;"><?= nl2br(e($internship['benefits'])) ?></p>
                <?php endif; ?>

                <div class="section-title">Contact</div>
                <p style="font-size:.875rem;color:#374151;margin:0;">
                    Email: <a href="mailto:<?= e($internship['contact_email']) ?>" style="color:#1349cc;"><?= e($internship['contact_email']) ?></a>
                    <?php if ($internship['contact_phone']): ?> · Phone: <?= e($internship['contact_phone']) ?><?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Right: Apply panel -->
        <div class="col-lg-4">
            <div class="apply-card">
                <?php if ($isLoggedIn && $userRole === ROLE_STUDENT): ?>
                    <form method="post" action="<?= e(app_url('internship-detail.php?id='.$internshipId.'&toggle_save=1')) ?>">
                        <?= csrf_field() ?>
                        <button type="submit" style="width:100%;background:<?= $isSaved?'#fee2e2':'#eff3ff' ?>;color:<?= $isSaved?'#ef4444':'#1349cc' ?>;border:1.5px solid <?= $isSaved?'#fca5a5':'#c7d2fe' ?>;border-radius:.6rem;padding:.55rem;font-weight:600;font-size:.85rem;cursor:pointer;margin-bottom:1rem;">
                            <i class="bi bi-<?= $isSaved?'heart-fill':'heart' ?>"></i> <?= $isSaved ? 'Remove from Saved' : 'Save Internship' ?>
                        </button>
                    </form>
                <?php endif; ?>

                <?php if (!$isLoggedIn): ?>
                    <a href="<?= e(app_url('auth/login.php')) ?>" class="btn-apply d-block text-center text-decoration-none mb-2" style="padding:.7rem;border-radius:.6rem;background:#1349cc;color:#fff;font-weight:700;">Login to Apply</a>
                    <p style="text-align:center;font-size:.78rem;color:#9ca3af;margin:0;">No account? <a href="<?= e(app_url('auth/register-student.php')) ?>" style="color:#1349cc;">Register free</a></p>

                <?php elseif ($userRole !== ROLE_STUDENT): ?>
                    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:.6rem;padding:1rem;font-size:.82rem;color:#1d4ed8;text-align:center;">
                        Only students can apply for internships.
                    </div>

                <?php elseif ($alreadyApplied): ?>
                    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:.6rem;padding:1rem;font-size:.875rem;color:#166534;text-align:center;">
                        <i class="bi bi-check-circle-fill"></i> You've already applied!
                    </div>

                <?php else: ?>
                    <div style="font-size:.9rem;font-weight:700;color:#111827;margin-bottom:1rem;">Apply Now</div>
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
                                <div style="font-size:.75rem;color:#b45309;background:#fef9c3;border-radius:.4rem;padding:.4rem .7rem;margin-top:.4rem;">
                                    <a href="<?= e(app_url('student/cvs.php')) ?>" style="color:#92400e;font-weight:600;">Upload a CV</a> to apply
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cover Letter <span style="color:#9ca3af;">(optional)</span></label>
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
</div>

<footer style="background:#fff;border-top:1px solid #e8eaf0;padding:.9rem 2rem;margin-top:2rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
    <span style="font-size:.75rem;color:#9ca3af;">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</span>
    <div style="display:flex;gap:1.25rem;">
        <a href="#" style="font-size:.75rem;color:#9ca3af;text-decoration:none;">Privacy Policy</a>
        <a href="#" style="font-size:.75rem;color:#9ca3af;text-decoration:none;">Support</a>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(app_url('assets/js/main.js')) ?>"></script>
</body>
</html>
