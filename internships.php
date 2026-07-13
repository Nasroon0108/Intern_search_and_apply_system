<?php
$pageTitle = 'Search Internships';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

init_session();

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$keyword = trim($_GET['keyword'] ?? '');
$district = trim($_GET['district'] ?? '');
$province = trim($_GET['province'] ?? '');
$workType = trim($_GET['work_type'] ?? '');
$industry = trim($_GET['industry'] ?? '');
$sort = $_GET['sort'] ?? 'recent';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

// Build query
$where = ['internships.status = ?'];
$params = ['active'];
$types = 's';

if ($search || $keyword) {
    $searchTerm = '%' . ($search ?: $keyword) . '%';
    $where[] = '(internships.title LIKE ? OR internships.responsibilities LIKE ? OR companies.company_name LIKE ?)';
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

if ($district) {
    $where[] = 'internships.district = ?';
    $params[] = $district;
    $types .= 's';
}

if ($province) {
    $where[] = 'internships.province = ?';
    $params[] = $province;
    $types .= 's';
}

if ($workType) {
    $where[] = 'internships.work_type = ?';
    $params[] = $workType;
    $types .= 's';
}

if ($industry) {
    $where[] = 'internships.industry = ?';
    $params[] = $industry;
    $types .= 's';
}

$orderBy = match($sort) {
    'salary' => 'internships.stipend DESC',
    'deadline' => 'internships.application_deadline ASC',
    'popular' => 'internships.views_count DESC',
    default => 'internships.created_at DESC'
};

$whereClause = implode(' AND ', $where);

// Count total
$countQuery = "SELECT COUNT(*) as total FROM internships JOIN companies ON companies.id = internships.company_id WHERE $whereClause";
$stmt = $mysqli->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$totalPages = ceil($totalRows / $perPage);
$offset = ($page - 1) * $perPage;

// Get internships
$query = "
    SELECT internships.*, companies.company_name, companies.logo, companies.district as company_district
    FROM internships
    JOIN companies ON companies.id = internships.company_id
    WHERE $whereClause
    ORDER BY $orderBy
    LIMIT ? OFFSET ?
";

$stmt = $mysqli->prepare($query);
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$internships = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get industries for filter
$industries = [];
$stmt = $mysqli->query("SELECT DISTINCT industry FROM internships WHERE status = 'active' ORDER BY industry");
while ($row = $stmt->fetch_assoc()) {
    if ($row['industry']) $industries[] = $row['industry'];
}

$flash      = get_flash();
$isLoggedIn = is_logged_in();
$userRole   = current_user_role();
$isStudent  = $isLoggedIn && $userRole === ROLE_STUDENT;
$currentPage = 'explore';

$exploreStyles = <<<'CSS'
        .filter-card { background:#fff;border:1px solid #e8eaf0;border-radius:.75rem;padding:1.25rem; }
        .filter-card h6 { font-size:.78rem;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.07em;margin-bottom:1rem; }
        .filter-card .form-label { font-size:.78rem;font-weight:500;color:#374151;margin-bottom:.25rem; }
        .filter-card .form-control, .filter-card .form-select { font-size:.82rem;border-radius:.5rem;border-color:#e8eaf0;padding:.45rem .75rem; }
        .filter-card .form-control:focus, .filter-card .form-select:focus { border-color:#1349cc;box-shadow:0 0 0 3px rgba(19,73,204,.1); }
        .intern-card { background:#fff;border:1px solid #e8eaf0;border-radius:.75rem;padding:1.25rem;margin-bottom:.85rem;transition:box-shadow .15s,border-color .15s; }
        .intern-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.07);border-color:#c7d2fe; }
        .intern-card .co-logo { width:48px;height:48px;border-radius:.6rem;background:#eff3ff;color:#1349cc;font-weight:700;font-size:1.1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
        .intern-card .co-logo img { width:100%;height:100%;object-fit:contain;border-radius:.6rem; }
        .intern-card .title { font-size:.95rem;font-weight:700;color:#111827;margin-bottom:.15rem; }
        .intern-card .company { font-size:.8rem;color:#6b7280;margin-bottom:.5rem; }
        .chip { font-size:.7rem;padding:.2rem .6rem;border-radius:2rem;font-weight:500; }
        .chip-gray  { background:#f3f4f8;color:#374151; }
        .chip-green { background:#f0fdf4;color:#166534; }
        .chip-blue  { background:#eff6ff;color:#1d4ed8; }
        .btn-view { font-size:.8rem;font-weight:600;background:#1349cc;color:#fff;border:none;border-radius:.5rem;padding:.4rem .9rem;text-decoration:none; }
        .btn-view:hover { background:#1038a8;color:#fff; }
        .results-header { font-size:.85rem;color:#6b7280;margin-bottom:1rem; }
        .results-header strong { color:#111827; }
        .pager { display:flex;justify-content:center;gap:.4rem;margin-top:1.25rem;flex-wrap:wrap; }
        .pager a { padding:.35rem .8rem;border:1.5px solid #e8eaf0;border-radius:.45rem;font-size:.8rem;text-decoration:none;color:#374151; }
        .pager a.active { background:#1349cc;border-color:#1349cc;color:#fff; }
        .pager a:hover:not(.active) { border-color:#1349cc;color:#1349cc; }
CSS;

if ($isStudent) {
    $student = get_student_by_user_id($mysqli, current_user_id());
    $extraHead = '<style>' . $exploreStyles . '</style>';
    require_once __DIR__ . '/includes/student-layout.php';
} else {
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
    <style>
        body { background: #f3f4f8; }
        .top-nav {
            background: #fff; border-bottom: 1px solid #e8eaf0;
            padding: .7rem 2rem; display: flex; align-items: center; gap: 1.5rem;
            position: sticky; top: 0; z-index: 100;
        }
        .top-nav .brand { display:flex;align-items:center;gap:.55rem;text-decoration:none; }
        .top-nav .brand .brand-icon { width:32px;height:32px;border-radius:8px;background:#1349cc;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.95rem; }
        .top-nav .brand .brand-name  { font-weight:700;font-size:.95rem;color:#111827; }
        .top-nav .nav-links { display:flex;gap:1.25rem;margin-left:1.5rem; }
        .top-nav .nav-links a { font-size:.85rem;color:#6b7280;text-decoration:none;font-weight:500; }
        .top-nav .nav-links a:hover, .top-nav .nav-links a.active { color:#1349cc; }
        .top-nav .nav-right { margin-left:auto;display:flex;align-items:center;gap:.75rem; }
        .btn-nav-outline { border:1.5px solid #d1d5db;background:#fff;color:#374151;border-radius:.5rem;padding:.35rem .85rem;font-size:.82rem;font-weight:500;text-decoration:none; }
        .btn-nav-outline:hover { border-color:#1349cc;color:#1349cc; }
        .btn-nav-primary { background:#1349cc;color:#fff;border:none;border-radius:.5rem;padding:.35rem .85rem;font-size:.82rem;font-weight:600;text-decoration:none; }
        .btn-nav-primary:hover { background:#1038a8;color:#fff; }
        .page-wrap { max-width:1200px;margin:0 auto;padding:2rem 1.5rem; }
        <?= $exploreStyles ?>
    </style>
</head>
<body>

<!-- Top nav -->
<nav class="top-nav">
    <a href="<?= e(app_url('index.php')) ?>" class="brand">
        <div class="brand-icon"><i class="bi bi-briefcase-fill"></i></div>
        <div class="brand-name">InternConnect</div>
    </a>
    <div class="nav-links">
        <a href="<?= e(app_url('index.php')) ?>">Home</a>
        <a href="<?= e(app_url('internships.php')) ?>" class="active">Explore</a>
        <?php if ($isLoggedIn): ?>
            <a href="<?= e(app_url(match($userRole){ 'student'=>'student/dashboard.php','company'=>'company/dashboard.php','admin'=>'admin/dashboard.php',default=>'index.php'})) ?>">Dashboard</a>
        <?php endif; ?>
    </div>
    <div class="nav-right">
        <?php if ($isLoggedIn): ?>
            <a href="<?= e(app_url('auth/logout.php')) ?>" class="btn-nav-outline">Logout</a>
        <?php else: ?>
            <a href="<?= e(app_url('auth/login.php')) ?>" class="btn-nav-outline">Login</a>
            <a href="<?= e(app_url('auth/register-student.php')) ?>" class="btn-nav-primary">Register</a>
        <?php endif; ?>
    </div>
</nav>

<?php if ($flash): ?>
<div style="background:#fff;padding:.5rem 2rem;border-bottom:1px solid #e8eaf0;">
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show py-2 px-3 small mb-0" role="alert">
        <?= e($flash['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>
<?php } ?>

<div class="<?= $isStudent ? '' : 'page-wrap' ?>">
    <div class="row g-4">

        <!-- Filters -->
        <div class="col-lg-3">
            <div class="filter-card">
                <h6>Filters</h6>
                <form method="get" id="filterForm">
                    <div class="mb-3">
                        <label class="form-label">Keyword</label>
                        <input type="text" class="form-control" name="keyword" placeholder="Title, company…" value="<?= e($keyword) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">District</label>
                        <select class="form-select" name="district">
                            <option value="">All Districts</option>
                            <?php foreach (DISTRICTS as $d): ?>
                                <option value="<?= e($d) ?>" <?= ($district===$d)?'selected':'' ?>><?= e($d) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Province</label>
                        <select class="form-select" name="province">
                            <option value="">All Provinces</option>
                            <?php foreach (PROVINCES as $p): ?>
                                <option value="<?= e($p) ?>" <?= ($province===$p)?'selected':'' ?>><?= e($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Work Type</label>
                        <select class="form-select" name="work_type">
                            <option value="">All Types</option>
                            <?php foreach (WORK_TYPES as $wt): ?>
                                <option value="<?= e($wt) ?>" <?= ($workType===$wt)?'selected':'' ?>><?= e($wt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Industry</label>
                        <select class="form-select" name="industry">
                            <option value="">All Industries</option>
                            <?php foreach ($industries as $ind): ?>
                                <option value="<?= e($ind) ?>" <?= ($industry===$ind)?'selected':'' ?>><?= e($ind) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Sort By</label>
                        <select class="form-select" name="sort">
                            <option value="recent"   <?= ($sort==='recent')  ?'selected':'' ?>>Most Recent</option>
                            <option value="deadline" <?= ($sort==='deadline')?'selected':'' ?>>Deadline</option>
                            <option value="salary"   <?= ($sort==='salary')  ?'selected':'' ?>>Highest Stipend</option>
                            <option value="popular"  <?= ($sort==='popular') ?'selected':'' ?>>Most Viewed</option>
                        </select>
                    </div>
                    <button type="submit" style="width:100%;background:#1349cc;color:#fff;border:none;border-radius:.5rem;padding:.55rem;font-weight:600;cursor:pointer;font-size:.85rem;margin-bottom:.4rem;">Apply Filters</button>
                    <a href="<?= e(app_url('internships.php')) ?>" style="display:block;text-align:center;font-size:.8rem;color:#9ca3af;text-decoration:none;padding:.3rem;">Clear Filters</a>
                </form>
            </div>
        </div>

        <!-- Results -->
        <div class="col-lg-9">
            <div class="results-header">
                Showing <strong><?= e($totalRows) ?> internship<?= $totalRows != 1 ? 's' : '' ?></strong>
                <?= $keyword ? ' for "<strong>'.e($keyword).'</strong>"' : '' ?>
            </div>

            <?php if (count($internships) > 0): ?>
                <?php foreach ($internships as $intern):
                    $co1 = strtoupper(substr($intern['company_name'], 0, 1));
                ?>
                <div class="intern-card">
                    <div style="display:flex;align-items:flex-start;gap:1rem;">
                        <div class="co-logo">
                            <?php if ($intern['logo']): ?>
                                <img src="<?= e(app_url('uploads/logos/'.$intern['logo'])) ?>" alt="">
                            <?php else: ?><?= e($co1) ?><?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div class="title"><?= e($intern['title']) ?></div>
                            <div class="company"><?= e($intern['company_name']) ?> · <?= e($intern['district'] ?? '') ?></div>
                            <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.6rem;">
                                <?php if ($intern['work_type']): ?><span class="chip chip-gray"><?= e($intern['work_type']) ?></span><?php endif; ?>
                                <?php if ($intern['stipend']): ?><span class="chip chip-green">Rs. <?= e(number_format($intern['stipend'],0)) ?></span><?php endif; ?>
                                <?php if ($intern['duration_months']): ?><span class="chip chip-blue"><?= e($intern['duration_months']) ?> months</span><?php endif; ?>
                                <?php if ($intern['industry']): ?><span class="chip chip-gray"><?= e($intern['industry']) ?></span><?php endif; ?>
                            </div>
                            <?php if ($intern['responsibilities']): ?>
                                <div style="font-size:.78rem;color:#6b7280;margin-bottom:.6rem;"><?= e(substr($intern['responsibilities'], 0, 130)) ?>…</div>
                            <?php endif; ?>
                            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
                                <div style="font-size:.72rem;color:#9ca3af;">
                                    <?php if ($intern['application_deadline']): ?>
                                        Deadline: <?= e(date('M j, Y', strtotime($intern['application_deadline']))) ?>
                                    <?php else: ?>
                                        Posted: <?= e(date('M j, Y', strtotime($intern['created_at']))) ?>
                                    <?php endif; ?>
                                </div>
                                <a href="<?= e(app_url('internship-detail.php?id='.$intern['id'])) ?>" class="btn-view">View Details</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($totalPages > 1):
                    $qBase = http_build_query(['keyword'=>$keyword,'district'=>$district,'province'=>$province,'work_type'=>$workType,'industry'=>$industry,'sort'=>$sort]);
                ?>
                <div class="pager">
                    <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>&<?= $qBase ?>">← Prev</a><?php endif; ?>
                    <?php for ($i=1; $i<=$totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&<?= $qBase ?>" class="<?= $i===$page?'active':'' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?><a href="?page=<?= $page+1 ?>&<?= $qBase ?>">Next →</a><?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div style="text-align:center;padding:4rem 2rem;background:#fff;border:1px solid #e8eaf0;border-radius:.75rem;">
                    <i class="bi bi-search" style="font-size:2.5rem;color:#d1d5db;display:block;margin-bottom:.75rem;"></i>
                    <p style="color:#9ca3af;margin-bottom:.5rem;">No internships found. Try adjusting your filters.</p>
                    <a href="<?= e(app_url('internships.php')) ?>" style="color:#1349cc;font-size:.875rem;font-weight:600;">Clear filters →</a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php if ($isStudent): ?>
<?php require_once __DIR__ . '/includes/student-layout-end.php'; ?>
<?php else: ?>
<footer style="background:#fff;border-top:1px solid #e8eaf0;padding:.9rem 2rem;margin-top:2rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
    <span style="font-size:.75rem;color:#9ca3af;">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</span>
    <div style="display:flex;gap:1.25rem;">
        <a href="#" style="font-size:.75rem;color:#9ca3af;text-decoration:none;">Privacy Policy</a>
        <a href="#" style="font-size:.75rem;color:#9ca3af;text-decoration:none;">Support</a>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(app_url('assets/js/main.js')) ?>"></script>
</body>
</html>
<?php endif; ?>
