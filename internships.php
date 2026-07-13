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

if ($isStudent) {
    $student = get_student_by_user_id($mysqli, current_user_id());
    require_once __DIR__ . '/includes/student-layout.php';
} else {
    require_once __DIR__ . '/includes/header.php';
}
?>

<?php if (!$isStudent): ?><div class="container py-4"><?php endif; ?>

<div>
    <div class="row g-4 align-items-start">

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
                                <img src="<?= e(app_url('uploads/logos/' . $intern['logo'])) ?>" alt="<?= e($intern['company_name']) ?>"
                                     onerror="this.style.display='none';this.parentElement.textContent='<?= e($co1) ?>';">
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
                <div class="ic-empty">
                    <i class="bi bi-search"></i>
                    <p>No internships found. Try adjusting your filters.</p>
                    <a href="<?= e(app_url('internships.php')) ?>" class="btn-view">Clear filters</a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php if ($isStudent): ?>
<?php require_once __DIR__ . '/includes/student-layout-end.php'; ?>
<?php else: ?>
</div><!-- container -->
<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php endif; ?>
