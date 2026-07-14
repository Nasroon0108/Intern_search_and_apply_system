<?php
$pageTitle = 'Login';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();

if (is_logged_in()) {
    redirect(dashboard_url_for_role(current_user_role()));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = login_user($mysqli, $email, $password);
    if ($result['success']) {
        set_flash('success', 'Welcome back!');
        redirect(dashboard_url_for_role($result['role']));
    }
    $error = $result['error'];
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require dirname(__DIR__) . '/includes/theme-head.php'; ?>
    <title>Sign In | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; }

        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Left panel ── */
        .auth-panel-left {
            background: linear-gradient(160deg, #1a3faa 0%, #1349cc 60%, #1a5fd4 100%);
            padding: 2.5rem 2.5rem 2rem;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .auth-panel-left .brand-logo {
            display: flex;
            align-items: center;
            gap: .6rem;
            color: #fff;
            font-weight: 700;
            font-size: 1.25rem;
            text-decoration: none;
            margin-bottom: .25rem;
        }

        .auth-panel-left .brand-logo i {
            font-size: 1.6rem;
        }

        .auth-panel-left .brand-tagline {
            color: rgba(255,255,255,.65);
            font-size: .7rem;
            letter-spacing: .12em;
            text-transform: uppercase;
            margin-bottom: 2.5rem;
        }

        .auth-panel-left .preview-card {
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 1rem;
            overflow: hidden;
            flex: 1;
            max-height: 320px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-panel-left .preview-card img {
            width: 100%;
            height: 100%;
            min-height: 220px;
            object-fit: cover;
            opacity: .95;
            display: block;
        }

        .auth-panel-left .quote {
            margin-top: 2rem;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            line-height: 1.5;
        }

        .auth-panel-left .quote-sub {
            color: rgba(255,255,255,.65);
            font-size: .825rem;
            margin-top: .5rem;
        }

        /* ── Right panel ── */
        .auth-panel-right {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 3.5rem;
            min-height: 100vh;
            background: #fff;
        }

        .auth-panel-right .auth-title {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: .4rem;
        }

        .auth-panel-right .auth-subtitle {
            color: #6b7280;
            font-size: .95rem;
            margin-bottom: 2rem;
        }

        /* OR divider */
        .or-divider {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 1.5rem 0;
            color: #9ca3af;
            font-size: .8rem;
            letter-spacing: .06em;
            text-transform: uppercase;
        }
        .or-divider::before,
        .or-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        /* Password toggle */
        .pw-wrapper { position: relative; }
        .pw-wrapper .pw-toggle {
            position: absolute;
            right: .9rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 0;
            color: #9ca3af;
            cursor: pointer;
            font-size: 1.1rem;
            line-height: 1;
        }
        .pw-wrapper .pw-toggle:hover { color: #374151; }

        /* Inputs */
        .auth-panel-right .form-control,
        .auth-panel-right .form-select {
            border-radius: .6rem;
            border-color: #d1d5db;
            padding: .65rem 1rem;
            font-size: .9rem;
        }
        .auth-panel-right .form-control:focus {
            border-color: #1349cc;
            box-shadow: 0 0 0 3px rgba(19,73,204,.12);
        }

        .auth-panel-right .form-label {
            font-size: .85rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: .35rem;
        }

        /* Buttons */
        .btn-auth-primary {
            background: #1349cc;
            border: none;
            color: #fff;
            border-radius: .6rem;
            padding: .7rem 1rem;
            font-weight: 600;
            font-size: .95rem;
            width: 100%;
            transition: background .2s;
        }
        .btn-auth-primary:hover { background: #1038a8; color: #fff; }

        .btn-auth-outline {
            background: #fff;
            border: 1.5px solid #d1d5db;
            color: #374151;
            border-radius: .6rem;
            padding: .65rem 1rem;
            font-weight: 500;
            font-size: .9rem;
            width: 100%;
            transition: border-color .2s, background .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            text-decoration: none;
        }
        .btn-auth-outline:hover { border-color: #1349cc; background: #f0f4ff; color: #1349cc; }

        /* Footer links */
        .auth-footer-links {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
            font-size: .8rem;
        }
        .auth-footer-links a { color: #9ca3af; text-decoration: none; }
        .auth-footer-links a:hover { color: #374151; }

        /* Responsive */
        @media (max-width: 767px) {
            .auth-panel-left  { min-height: auto; padding: 2rem 1.5rem; }
            .auth-panel-right { min-height: auto; padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="theme-toggle-fixed"><?php require dirname(__DIR__) . '/includes/theme-toggle.php'; ?></div>
    <div class="container-fluid p-0 flex-grow-1">
        <div class="row g-0 h-100">

            <!-- ── Left blue panel ── -->
            <div class="col-lg-5 auth-panel-left">
                <a href="<?= e(app_url('index.php')) ?>" class="brand-logo">
                    <i class="bi bi-briefcase-fill"></i> InternConnect
                </a>
                <p class="brand-tagline">The Official University Internship Portal</p>

                <div class="preview-card">
                    <img src="<?= e(app_url('assets/images/auth-hero.jpg')) ?>"
                         alt="Students connecting with companies through InternConnect"
                         loading="lazy">
                </div>

                <p class="quote mt-4">"Bridging the gap between academic excellence and professional achievement."</p>
                <p class="quote-sub">Trusted by thousands of students and top companies across Sri Lanka.</p>
            </div>

            <!-- ── Right form panel ── -->
            <div class="col-lg-7 auth-panel-right">
                <div style="max-width: 420px; width: 100%; margin: 0 auto;">

                    <h1 class="auth-title">Welcome Back</h1>
                    <p class="auth-subtitle">Sign in to manage your internships and applications.</p>

                    <a href="<?= e(app_url('auth/register-student.php')) ?>" class="btn-auth-outline mb-2">
                        <i class="bi bi-mortarboard"></i> Sign up as Student
                    </a>
                    <a href="<?= e(app_url('auth/register-company.php')) ?>" class="btn-auth-outline mb-2">
                        <i class="bi bi-building"></i> Sign up as Company
                    </a>

                    <div class="or-divider">or sign in with email</div>

                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <strong>Demo accounts</strong> (password: <code>Demo@123</code>)<br>
                        <span class="text-muted">Students:</span>
                        <a href="#" class="demo-login" data-email="amaya.perera@seed.internconnect.lk">Amaya</a> ·
                        <a href="#" class="demo-login" data-email="kavindu.silva@seed.internconnect.lk">Kavindu</a> ·
                        <a href="#" class="demo-login" data-email="nethmi.fernando@seed.internconnect.lk">Nethmi</a><br>
                        <span class="text-muted">Companies:</span>
                        <a href="#" class="demo-login" data-email="techvista@seed.internconnect.lk">TechVista</a> ·
                        <a href="#" class="demo-login" data-email="greenwave@seed.internconnect.lk">GreenWave</a>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 px-3 small mb-3"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="post" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="name@example.com" required
                                   value="<?= e($_POST['email'] ?? '') ?>">
                        </div>

                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label mb-0">Password</label>
                                <a href="<?= e(app_url('auth/forgot-password.php')) ?>"
                                   class="text-decoration-none small" style="color:#1349cc; font-size:.8rem;">Forgot?</a>
                            </div>
                            <div class="pw-wrapper">
                                <input type="password" class="form-control pe-5" id="password" name="password"
                                       placeholder="••••••••" required>
                                <button type="button" class="pw-toggle" onclick="togglePw('password', this)" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                                <label class="form-check-label small" for="rememberMe" style="color:#374151;">
                                    Remember me for 30 days
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn-auth-primary mb-3">Sign In</button>
                    </form>

                    <hr style="border-color:#e5e7eb;">

                    <p class="text-center small mb-0" style="color:#6b7280;">
                        Don't have an account?
                        <a href="<?= e(app_url('auth/register-student.php')) ?>"
                           class="fw-semibold text-decoration-none" style="color:#1349cc;">Student sign-up</a>
                        &nbsp;·&nbsp;
                        <a href="<?= e(app_url('auth/register-company.php')) ?>"
                           class="fw-semibold text-decoration-none" style="color:#1349cc;">Company sign-up</a>
                    </p>

                    <div class="auth-footer-links">
                        <a href="<?= e(app_url('auth/forgot-password.php')) ?>">Forgot password</a>
                        <a href="#">Privacy Policy</a>
                        <a href="#">Support</a>
                    </div>

                </div>
            </div>

        </div><!-- row -->
    </div><!-- container-fluid -->
</div><!-- auth-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset_url('assets/js/main.js')) ?>"></script>
<script>
function togglePw(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

document.querySelectorAll('.demo-login').forEach(function (link) {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        document.getElementById('email').value = this.dataset.email;
        document.getElementById('password').value = 'Demo@123';
    });
});
</script>
</body>
</html>
