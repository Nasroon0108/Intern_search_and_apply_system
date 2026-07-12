<?php
$pageTitle = 'Company Registration';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

if (is_logged_in()) {
    redirect(dashboard_url_for_role(current_user_role()));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $result = register_company($mysqli, $_POST);
    if ($result['success']) {
        set_flash('success', 'Registration submitted! Your account will be reviewed by an admin before you can post internships.');
        redirect(app_url('auth/login.php'));
    }
    $error = $result['error'];
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h4 mb-1">Company Registration</h2>
                    <p class="text-muted small mb-4">Register your company to post internship vacancies</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="post" novalidate>
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Company Name *</label>
                                <input type="text" class="form-control" name="company_name" required
                                       value="<?= e($_POST['company_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Industry</label>
                                <select class="form-select" name="industry">
                                    <option value="">Select industry</option>
                                    <?php foreach (JOB_CATEGORIES as $cat): ?>
                                        <option value="<?= e($cat) ?>" <?= (($_POST['industry'] ?? '') === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Person *</label>
                                <input type="text" class="form-control" name="contact_person" required
                                       value="<?= e($_POST['contact_person'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone"
                                       value="<?= e($_POST['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">District</label>
                                <select class="form-select" name="district">
                                    <option value="">Select district</option>
                                    <?php foreach (DISTRICTS as $d): ?>
                                        <option value="<?= e($d) ?>" <?= (($_POST['district'] ?? '') === $d) ? 'selected' : '' ?>><?= e($d) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required
                                       value="<?= e($_POST['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password *</label>
                                <input type="password" class="form-control" name="password" required minlength="8">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password *</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                        </div>
                        <div class="alert alert-info small mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Company accounts require admin verification before posting internships.
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-3">Submit Registration</button>
                    </form>

                    <p class="text-center small mt-3 mb-0">
                        Already registered? <a href="<?= e(app_url('auth/login.php')) ?>">Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
