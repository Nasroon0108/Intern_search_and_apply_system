<?php
$pageTitle = 'Reset Password';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

if (is_logged_in()) {
    redirect(dashboard_url_for_role(current_user_role()));
}

$error = '';
$message = '';
$token = trim($_GET['token'] ?? '');

if (!$token) {
    $error = 'Invalid password reset link.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    require_valid_csrf();
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    $result = reset_password($mysqli, $token, $newPassword, $confirmPassword);
    
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['error'];
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card ic-auth-card">
                <div class="card-body p-4">
                    <h2 class="h4 mb-1">Reset Password</h2>
                    <p class="text-muted small mb-4">Enter your new password</p>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= e($message) ?></div>
                        <a href="<?= e(app_url('auth/login.php')) ?>" class="btn btn-primary w-100">Go to Login</a>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                        <a href="<?= e(app_url('auth/forgot-password.php')) ?>" class="btn btn-secondary w-100">Request New Link</a>
                    <?php else: ?>
                    <form method="post" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <div class="form-text">Min 8 chars, upper, lower, and a number.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                    </form>
                    <?php endif; ?>

                    <p class="text-center small mt-3 mb-0">
                        <a href="<?= e(app_url('auth/login.php')) ?>">Back to Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
