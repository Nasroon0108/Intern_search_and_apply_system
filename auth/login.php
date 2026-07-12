<?php
$pageTitle = 'Login';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

if (is_logged_in()) {
    redirect(dashboard_url_for_role(current_user_role()));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = login_user($mysqli, $email, $password);
    if ($result['success']) {
        set_flash('success', 'Welcome back!');
        redirect(dashboard_url_for_role($result['role']));
    }
    $error = $result['error'];
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h4 mb-1">Sign in</h2>
                    <p class="text-muted small mb-4">Access your InternConnect account</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="post" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?= e($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="text-end mt-1">
                                <a href="<?= e(app_url('auth/forgot-password.php')) ?>" class="text-muted small">Forgot password?</a>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>

                    <hr class="my-4">
                    <p class="text-center small mb-0">
                        New here?
                        <a href="<?= e(app_url('auth/register-student.php')) ?>">Register as Student</a>
                        or
                        <a href="<?= e(app_url('auth/register-company.php')) ?>">Company</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
