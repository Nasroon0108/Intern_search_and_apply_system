<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

init_session();

if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}

$flash = get_flash();
$isLoggedIn = is_logged_in();
$userRole = current_user_role();
$userEmail = $_SESSION['user_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= e(app_url('index.php')) ?>">
            <i class="bi bi-briefcase-fill me-1"></i> InternConnect
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(app_url('index.php')) ?>">Home</a>
                </li>
                <?php if ($isLoggedIn && $userRole === ROLE_STUDENT): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(app_url('student/dashboard.php')) ?>">Dashboard</a></li>
                <?php elseif ($isLoggedIn && $userRole === ROLE_COMPANY): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(app_url('company/dashboard.php')) ?>">Dashboard</a></li>
                <?php elseif ($isLoggedIn && $userRole === ROLE_ADMIN): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(app_url('admin/dashboard.php')) ?>">Admin</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($isLoggedIn): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?= e($userEmail) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text text-muted small text-capitalize"><?= e($userRole) ?> account</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= e(app_url('auth/logout.php')) ?>"><i class="bi bi-box-arrow-right me-1"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(app_url('auth/login.php')) ?>">Login</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Register</a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= e(app_url('auth/register-student.php')) ?>">As Student</a></li>
                            <li><a class="dropdown-item" href="<?= e(app_url('auth/register-company.php')) ?>">As Company</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
    <?php if ($flash): ?>
        <div class="container mt-3">
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
