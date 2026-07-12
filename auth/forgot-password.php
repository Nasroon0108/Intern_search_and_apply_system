<?php
$pageTitle = 'Forgot Password';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

if (is_logged_in()) {
    redirect(dashboard_url_for_role(current_user_role()));
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $email = trim($_POST['email'] ?? '');
    
    if (!$email || !is_valid_email($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $result = request_password_reset($mysqli, $email);
        $message = $result['message'];
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="h4 mb-1">Forgot Password</h2>
                    <p class="text-muted small mb-4">Enter your email and we'll send you a link to reset your password</p>

                    <?php if ($message): ?>
                        <div class="alert alert-info"><?= e($message) ?></div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>

                    <?php if (!$message): ?>
                    <form method="post" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?= e($_POST['email'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                    </form>
                    <?php endif; ?>

                    <p class="text-center small mt-3 mb-0">
                        Remember your password? <a href="<?= e(app_url('auth/login.php')) ?>">Login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
