<?php
$pageTitle = 'Company Registration';
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
    $result = register_company($mysqli, $_POST);
    if ($result['success']) {
        if (!MAIL_ENABLED && !empty($result['verify_token'])) {
            $_SESSION['dev_verify_url'] = app_url('auth/verify-email.php?token=' . urlencode($result['verify_token']));
            set_flash('success', 'Registration submitted! Email sending is disabled on localhost — use the verification link shown below, then wait for admin approval.');
        } else {
            set_flash('success', 'Registration submitted! Check your email to verify your account, then an admin will review your company profile.');
        }
        redirect(app_url('auth/login.php'));
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
    <title>Company Registration | <?= e(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(app_url('assets/css/style.css')) ?>" rel="stylesheet">
    <style>
        html, body { height: 100%; margin: 0; }
        .auth-wrapper { min-height: 100vh; display: flex; flex-direction: column; }

        /* ── Left panel ── */
        .auth-panel-left {
            background: linear-gradient(160deg, #1a3faa 0%, #1349cc 60%, #1a5fd4 100%);
            padding: 2.5rem 2.5rem 2rem;
            display: flex; flex-direction: column; min-height: 100vh;
        }
        .auth-panel-left .brand-logo {
            display: flex; align-items: center; gap: .6rem;
            color: #fff; font-weight: 700; font-size: 1.25rem;
            text-decoration: none; margin-bottom: .25rem;
        }
        .auth-panel-left .brand-logo i { font-size: 1.6rem; }
        .auth-panel-left .brand-tagline {
            color: rgba(255,255,255,.65); font-size: .7rem;
            letter-spacing: .12em; text-transform: uppercase; margin-bottom: 2.5rem;
        }
        .auth-panel-left .steps-list { list-style: none; padding: 0; margin: 0; flex: 1; }
        .auth-panel-left .steps-list li {
            display: flex; align-items: flex-start; gap: .9rem;
            color: rgba(255,255,255,.85); font-size: .9rem; margin-bottom: 1.5rem;
        }
        .auth-panel-left .steps-list li .step-icon {
            width: 2.25rem; height: 2.25rem; border-radius: 50%;
            background: rgba(255,255,255,.18); display: flex; align-items: center;
            justify-content: center; flex-shrink: 0; font-size: 1rem; color: #fff;
        }
        .auth-panel-left .steps-list li .step-title { font-weight: 600; color: #fff; }
        .auth-panel-left .steps-list li .step-desc { font-size: .8rem; color: rgba(255,255,255,.6); }
        .auth-panel-left .quote { margin-top: 2rem; color: #fff; font-size: 1rem; font-weight: 600; line-height: 1.5; }
        .auth-panel-left .quote-sub { color: rgba(255,255,255,.65); font-size: .825rem; margin-top: .5rem; }

        /* ── Right panel ── */
        .auth-panel-right {
            display: flex; flex-direction: column; justify-content: center;
            padding: 3rem 3.5rem; min-height: 100vh; background: #fff;
        }
        .auth-title { font-size: 1.75rem; font-weight: 700; color: #111827; margin-bottom: .3rem; }
        .auth-subtitle { color: #6b7280; font-size: .9rem; margin-bottom: 1.75rem; }

        .form-control, .form-select {
            border-radius: .6rem; border-color: #d1d5db;
            padding: .6rem 1rem; font-size: .875rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #1349cc; box-shadow: 0 0 0 3px rgba(19,73,204,.12);
        }
        .form-label { font-size: .82rem; font-weight: 500; color: #374151; margin-bottom: .3rem; }

        /* Password toggle */
        .pw-wrapper { position: relative; }
        .pw-wrapper .pw-toggle {
            position: absolute; right: .9rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; padding: 0; color: #9ca3af;
            cursor: pointer; font-size: 1.1rem; line-height: 1;
        }
        .pw-wrapper .pw-toggle:hover { color: #374151; }

        .btn-auth-primary {
            background: #1349cc; border: none; color: #fff;
            border-radius: .6rem; padding: .7rem 1rem;
            font-weight: 600; font-size: .95rem; width: 100%; transition: background .2s;
        }
        .btn-auth-primary:hover { background: #1038a8; color: #fff; }

        .auth-footer-links {
            display: flex; gap: 1.5rem; justify-content: center; flex-wrap: wrap;
            margin-top: 1.5rem; font-size: .8rem;
        }
        .auth-footer-links a { color: #9ca3af; text-decoration: none; }
        .auth-footer-links a:hover { color: #374151; }

        .section-divider {
            font-size: .75rem; font-weight: 600; color: #9ca3af;
            text-transform: uppercase; letter-spacing: .08em;
            border-bottom: 1px solid #e5e7eb; padding-bottom: .4rem;
            margin-bottom: 1rem; margin-top: 1.25rem;
        }

        .info-badge {
            background: #eff6ff; border: 1px solid #bfdbfe;
            border-radius: .6rem; padding: .65rem 1rem;
            font-size: .82rem; color: #1d4ed8;
            display: flex; align-items: flex-start; gap: .5rem;
        }

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
            <div class="col-lg-4 auth-panel-left">
                <a href="<?= e(app_url('index.php')) ?>" class="brand-logo">
                    <i class="bi bi-briefcase-fill"></i> InternConnect
                </a>
                <p class="brand-tagline">The Official University Internship Portal</p>

                <ul class="steps-list">
                    <li>
                        <div class="step-icon"><i class="bi bi-building"></i></div>
                        <div>
                            <div class="step-title">Register your company</div>
                            <div class="step-desc">Set up your employer profile</div>
                        </div>
                    </li>
                    <li>
                        <div class="step-icon"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <div class="step-title">Admin verification</div>
                            <div class="step-desc">Quick review to ensure quality listings</div>
                        </div>
                    </li>
                    <li>
                        <div class="step-icon"><i class="bi bi-megaphone"></i></div>
                        <div>
                            <div class="step-title">Post internships</div>
                            <div class="step-desc">Reach thousands of qualified students</div>
                        </div>
                    </li>
                    <li>
                        <div class="step-icon"><i class="bi bi-people"></i></div>
                        <div>
                            <div class="step-title">Hire top talent</div>
                            <div class="step-desc">Review applications and select the best fit</div>
                        </div>
                    </li>
                </ul>

                <p class="quote">"Find the next generation of professionals."</p>
                <p class="quote-sub">Connect with motivated students from universities across Sri Lanka.</p>
            </div>

            <!-- ── Right form panel ── -->
            <div class="col-lg-8 auth-panel-right">
                <div style="max-width: 580px; width: 100%; margin: 0 auto;">

                    <h1 class="auth-title">Register Your Company</h1>
                    <p class="auth-subtitle">Create an employer account to post internship opportunities</p>

                    <?php if ($error): ?>
                        <div class="alert alert-danger py-2 px-3 small mb-3"><?= e($error) ?></div>
                    <?php endif; ?>

                    <div class="info-badge mb-3">
                        <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
                        <span>Company accounts require admin verification before you can post internships. You'll be notified once approved.</span>
                    </div>

                    <form method="post" novalidate>
                        <?= csrf_field() ?>

                        <!-- Company info -->
                        <p class="section-divider">Company Information</p>
                        <div class="row g-3 mb-1">
                            <div class="col-md-6">
                                <label class="form-label">Company Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="company_name" required
                                       placeholder="e.g. Tech Solutions PVT Ltd"
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
                                <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="contact_person" required
                                       placeholder="e.g. Nimal Silva"
                                       value="<?= e($_POST['contact_person'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" name="phone"
                                       placeholder="07X XXX XXXX"
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
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" required
                                       placeholder="hr@company.com"
                                       value="<?= e($_POST['email'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Account credentials -->
                        <p class="section-divider">Account Credentials</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="pw-wrapper">
                                    <input type="password" class="form-control pe-5" name="password" id="pw1"
                                           placeholder="••••••••" required minlength="8">
                                    <button type="button" class="pw-toggle" onclick="togglePw('pw1',this)" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text" style="font-size:.75rem; color:#9ca3af;">
                                    Min 8 chars · uppercase · lowercase · number
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <div class="pw-wrapper">
                                    <input type="password" class="form-control pe-5" name="confirm_password" id="pw2"
                                           placeholder="••••••••" required>
                                    <button type="button" class="pw-toggle" onclick="togglePw('pw2',this)" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-auth-primary mt-4">Submit Registration</button>
                    </form>

                    <hr class="mt-4" style="border-color:#e5e7eb;">
                    <p class="text-center small mb-0" style="color:#6b7280;">
                        Already registered?
                        <a href="<?= e(app_url('auth/login.php')) ?>"
                           class="fw-semibold text-decoration-none" style="color:#1349cc;">Sign in</a>
                        &nbsp;·&nbsp;
                        Are you a student?
                        <a href="<?= e(app_url('auth/register-student.php')) ?>"
                           class="fw-semibold text-decoration-none" style="color:#1349cc;">Student sign-up</a>
                    </p>

                    <div class="auth-footer-links">
                        <a href="#">Privacy Policy</a>
                        <a href="#">Terms of Use</a>
                        <a href="#">Support</a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
</script>
<script src="<?= e(asset_url('assets/js/main.js')) ?>"></script>
</body>
</html>
