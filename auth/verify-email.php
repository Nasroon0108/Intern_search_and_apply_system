<?php
$pageTitle = 'Verify Email';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

$message = '';
$error = '';

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    $result = verify_email($mysqli, $token);
    
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
                <div class="card-body p-4 text-center">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= e($message) ?></div>
                        <a href="<?= e(app_url('auth/login.php')) ?>" class="btn btn-primary">Go to Login</a>
                    <?php elseif ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                        <p class="text-muted small mb-3">Didn't receive a verification email?</p>
                        <a href="<?= e(app_url('auth/login.php')) ?>" class="btn btn-secondary btn-sm">Back to Login</a>
                    <?php else: ?>
                        <p class="text-muted">Processing verification...</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
