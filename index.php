<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero-section text-white py-5">
    <div class="container py-4">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-5 fw-bold mb-3">Find Your Dream Internship in Sri Lanka</h1>
                <p class="lead mb-4">
                    InternConnect brings students and companies together on one platform.
                    Search internships by district, field, and work type — apply online and track your applications.
                </p>
                <?php if (!is_logged_in()): ?>
                    <a href="<?= e(app_url('auth/register-student.php')) ?>" class="btn btn-light btn-lg me-2">I'm a Student</a>
                    <a href="<?= e(app_url('auth/register-company.php')) ?>" class="btn btn-outline-light btn-lg me-2">I'm a Company</a>
                    <a href="<?= e(app_url('internships.php')) ?>" class="btn btn-outline-light btn-lg">Explore Internships</a>
                <?php else: ?>
                    <a href="<?= e(app_url('internships.php')) ?>" class="btn btn-light btn-lg me-2">Explore Internships</a>
                    <a href="<?= e(dashboard_url_for_role(current_user_role())) ?>" class="btn btn-outline-light btn-lg">Go to Dashboard</a>
                <?php endif; ?>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <i class="bi bi-mortarboard display-1 opacity-75"></i>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="h3">How It Works</h2>
            <p class="text-muted">Simple steps for students and companies</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="feature-icon bg-primary-subtle text-primary rounded-circle mx-auto mb-3">
                        <i class="bi bi-person-plus fs-3"></i>
                    </div>
                    <h3 class="h5">1. Register</h3>
                    <p class="text-muted small mb-0">Students and companies create accounts with secure login.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="feature-icon bg-success-subtle text-success rounded-circle mx-auto mb-3">
                        <i class="bi bi-search fs-3"></i>
                    </div>
                    <h3 class="h5">2. Search & Apply</h3>
                    <p class="text-muted small mb-0">Browse internships with filters and apply with your CV online.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm text-center p-4">
                    <div class="feature-icon bg-warning-subtle text-warning rounded-circle mx-auto mb-3">
                        <i class="bi bi-graph-up-arrow fs-3"></i>
                    </div>
                    <h3 class="h5">3. Track Progress</h3>
                    <p class="text-muted small mb-0">Monitor application status from pending to accepted.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-light py-5">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="display-6 fw-bold text-primary">25</div>
                    <div class="text-muted small">Districts</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="display-6 fw-bold text-primary">3</div>
                    <div class="text-muted small">User Roles</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="display-6 fw-bold text-primary">100%</div>
                    <div class="text-muted small">Online Apply</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="display-6 fw-bold text-primary">SL</div>
                    <div class="text-muted small">Sri Lanka Focus</div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
