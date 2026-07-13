<?php
$pageTitle = 'Post Internship';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_COMPANY);

$userId = current_user_id();
$company = get_company_by_user_id($mysqli, $userId);

if (!$company) {
    die('Company profile not found.');
}

$internshipId = (int)($_GET['id'] ?? 0);
$internship = null;
$isEdit = false;

if ($internshipId > 0) {
    $stmt = $mysqli->prepare('SELECT * FROM internships WHERE id = ? AND company_id = ?');
    $stmt->bind_param('ii', $internshipId, $company['id']);
    $stmt->execute();
    $internship = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$internship) {
        redirect(app_url('company/internships.php'));
    }
    $isEdit = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();

    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $workType = trim($_POST['work_type'] ?? '');
    $stipend = (int)($_POST['stipend'] ?? 0);
    $durationMonths = (int)($_POST['duration_months'] ?? 0);
    $vacancies = (int)($_POST['vacancies'] ?? 1);
    $responsibilities = trim($_POST['responsibilities'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $benefits = trim($_POST['benefits'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? $company['contact_email'] ?? $_SESSION['user_email'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? $company['phone'] ?? '');
    $applicationDeadline = trim($_POST['application_deadline'] ?? '');
    $applicationDeadline = $applicationDeadline !== '' ? $applicationDeadline : null;
    $skills = $_POST['skills'] ?? [];

    // Validate
    if (!$title || !$district || !$province || !$workType || !$vacancies) {
        set_flash('error', 'Please fill in all required fields.');
    } else {
        $status = $isEdit ? $internship['status'] : 'active';

        if ($isEdit) {
            $stmt = $mysqli->prepare(
                'UPDATE internships SET
                 title = ?, category = ?, industry = ?, district = ?, province = ?,
                 work_type = ?, stipend = ?, duration_months = ?, vacancies = ?,
                 responsibilities = ?, requirements = ?, benefits = ?,
                 contact_email = ?, contact_phone = ?, application_deadline = ?,
                 updated_at = NOW()
                 WHERE id = ?'
            );

            $stmt->bind_param(
                'ssssssiiissssssi',
                $title, $category, $industry, $district, $province,
                $workType, $stipend, $durationMonths, $vacancies,
                $responsibilities, $requirements, $benefits,
                $contactEmail, $contactPhone, $applicationDeadline, $internshipId
            );
        } else {
            $stmt = $mysqli->prepare(
                'INSERT INTO internships
                 (company_id, title, category, industry, district, province,
                  work_type, stipend, duration_months, vacancies,
                  responsibilities, requirements, benefits,
                  contact_email, contact_phone, application_deadline, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );

            $stmt->bind_param(
                'issssssiiisssssss',
                $company['id'], $title, $category, $industry, $district, $province,
                $workType, $stipend, $durationMonths, $vacancies,
                $responsibilities, $requirements, $benefits,
                $contactEmail, $contactPhone, $applicationDeadline, $status
            );
        }

        if ($stmt->execute()) {
            if (!$isEdit) {
                $internshipId = $stmt->insert_id;
            }
            $stmt->close();

            // Handle skills
            $delSkills = $mysqli->prepare('DELETE FROM internship_skills WHERE internship_id = ?');
            if ($delSkills) {
                $delSkills->bind_param('i', $internshipId);
                $delSkills->execute();
                $delSkills->close();
            }

            if (count($skills) > 0) {
                $stmt = $mysqli->prepare('INSERT INTO internship_skills (internship_id, skill_id) VALUES (?, ?)');
                foreach ($skills as $skillId) {
                    $skillId = (int)$skillId;
                    $stmt->bind_param('ii', $internshipId, $skillId);
                    $stmt->execute();
                }
                $stmt->close();
            }

            set_flash('success', $isEdit ? 'Internship updated successfully.' : 'Internship posted successfully and is now visible to students.');
            redirect(app_url('company/internships.php'));
        } else {
            set_flash('error', 'Failed to save internship.');
        }
    }
}

// Get skills for form
$stmt = $mysqli->prepare(
    'SELECT s.id, sc.name AS category, s.name
     FROM skills s
     JOIN skill_categories sc ON sc.id = s.category_id
     ORDER BY sc.name, s.name'
);
$stmt->execute();
$skillsList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get current skills if editing
$currentSkills = [];
if ($isEdit) {
    $stmt = $mysqli->prepare('SELECT skill_id FROM internship_skills WHERE internship_id = ?');
    $stmt->bind_param('i', $internshipId);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $currentSkills = array_map(fn($s) => $s['skill_id'], $current);
}

// Group skills by category
$skillsByCategory = [];
foreach ($skillsList as $skill) {
    $skillsByCategory[$skill['category']][] = $skill;
}
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><?= $isEdit ? 'Edit Internship' : 'Post New Internship' ?></h2>
            <p class="text-muted">Fill in the details of your internship opportunity</p>
        </div>
    </div>

    <form method="POST" class="card border-0 shadow-sm">
        <?= csrf_field() ?>
        <div class="card-body">
            <h5 class="mb-3">Basic Information</h5>

            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="form-label">Title *</label>
                    <input type="text" class="form-control" name="title" value="<?= e($internship['title'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <input type="text" class="form-control" name="category" value="<?= e($internship['category'] ?? '') ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Industry</label>
                    <input type="text" class="form-control" name="industry" value="<?= e($internship['industry'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Work Type *</label>
                    <select class="form-select" name="work_type" required>
                        <option value="">Select Work Type</option>
                        <?php foreach (WORK_TYPES as $wt): ?>
                            <option value="<?= e($wt) ?>" <?= ($internship['work_type'] ?? '') === $wt ? 'selected' : '' ?>>
                                <?= e($wt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">District *</label>
                    <select class="form-select" name="district" required>
                        <option value="">Select District</option>
                        <?php foreach (DISTRICTS as $dist): ?>
                            <option value="<?= e($dist) ?>" <?= ($internship['district'] ?? '') === $dist ? 'selected' : '' ?>>
                                <?= e($dist) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Province *</label>
                    <select class="form-select" name="province" required>
                        <option value="">Select Province</option>
                        <?php foreach (PROVINCES as $prov): ?>
                            <option value="<?= e($prov) ?>" <?= ($internship['province'] ?? '') === $prov ? 'selected' : '' ?>>
                                <?= e($prov) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <h5 class="mb-3 mt-4">Opportunity Details</h5>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Stipend (Rs.)</label>
                    <input type="number" class="form-control" name="stipend" value="<?= e($internship['stipend'] ?? 0) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Duration (Months)</label>
                    <input type="number" class="form-control" name="duration_months" value="<?= e($internship['duration_months'] ?? 0) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vacancies *</label>
                    <input type="number" class="form-control" name="vacancies" value="<?= e($internship['vacancies'] ?? 1) ?>" min="1" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Application Deadline</label>
                <input type="date" class="form-control" name="application_deadline" value="<?= e($internship['application_deadline'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Responsibilities</label>
                <textarea class="form-control" name="responsibilities" rows="4"><?= e($internship['responsibilities'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Requirements</label>
                <textarea class="form-control" name="requirements" rows="4"><?= e($internship['requirements'] ?? '') ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Benefits</label>
                <textarea class="form-control" name="benefits" rows="3"><?= e($internship['benefits'] ?? '') ?></textarea>
            </div>

            <h5 class="mb-3 mt-4">Required Skills</h5>

            <div class="row g-3 mb-3">
                <?php foreach ($skillsByCategory as $category => $skills): ?>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title mb-3"><?= e($category) ?></h6>
                                <div class="form-check-group">
                                    <?php foreach ($skills as $skill): ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="skills[]" value="<?= e($skill['id']) ?>" id="skill_<?= e($skill['id']) ?>" <?= in_array($skill['id'], $currentSkills) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="skill_<?= e($skill['id']) ?>">
                                                <?= e($skill['name']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h5 class="mb-3 mt-4">Contact Information</h5>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Contact Email</label>
                    <input type="email" class="form-control" name="contact_email" value="<?= e($internship['contact_email'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Contact Phone</label>
                    <input type="tel" class="form-control" name="contact_phone" value="<?= e($internship['contact_phone'] ?? '') ?>">
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Update Internship' : 'Post Internship' ?></button>
                <a href="<?= e(app_url('company/internships.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
