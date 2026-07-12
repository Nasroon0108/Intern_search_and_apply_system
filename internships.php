<?php
$pageTitle = 'Search Internships';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

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
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-4">Filters</h5>
                    
                    <form method="get" id="filterForm">
                        <div class="mb-3">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="keyword" placeholder="Job title, company..." value="<?= e($keyword) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">District</label>
                            <select class="form-select form-select-sm" name="district">
                                <option value="">All Districts</option>
                                <?php foreach (DISTRICTS as $d): ?>
                                    <option value="<?= e($d) ?>" <?= ($district === $d) ? 'selected' : '' ?>><?= e($d) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Province</label>
                            <select class="form-select form-select-sm" name="province">
                                <option value="">All Provinces</option>
                                <?php foreach (PROVINCES as $p): ?>
                                    <option value="<?= e($p) ?>" <?= ($province === $p) ? 'selected' : '' ?>><?= e($p) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Work Type</label>
                            <select class="form-select form-select-sm" name="work_type">
                                <option value="">All Types</option>
                                <?php foreach (WORK_TYPES as $wt): ?>
                                    <option value="<?= e($wt) ?>" <?= ($workType === $wt) ? 'selected' : '' ?>><?= e($wt) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Industry</label>
                            <select class="form-select form-select-sm" name="industry">
                                <option value="">All Industries</option>
                                <?php foreach ($industries as $ind): ?>
                                    <option value="<?= e($ind) ?>" <?= ($industry === $ind) ? 'selected' : '' ?>><?= e($ind) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sort By</label>
                            <select class="form-select form-select-sm" name="sort">
                                <option value="recent" <?= ($sort === 'recent') ? 'selected' : '' ?>>Most Recent</option>
                                <option value="deadline" <?= ($sort === 'deadline') ? 'selected' : '' ?>>Application Deadline</option>
                                <option value="salary" <?= ($sort === 'salary') ? 'selected' : '' ?>>Highest Stipend</option>
                                <option value="popular" <?= ($sort === 'popular') ? 'selected' : '' ?>>Most Viewed</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-sm">Apply Filters</button>
                        <a href="<?= e(app_url('internships.php')) ?>" class="btn btn-outline-secondary w-100 btn-sm mt-2">Clear Filters</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="mb-3">
                <h4>
                    <span class="badge bg-primary"><?= e($totalRows) ?> internships found</span>
                </h4>
            </div>

            <?php if (count($internships) > 0): ?>
                <div class="row g-3">
                    <?php foreach ($internships as $internship): ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm h-100 position-relative">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-auto">
                                            <?php if ($internship['logo']): ?>
                                                <img src="<?= e(app_url('uploads/logos/' . $internship['logo'])) ?>" alt="Logo" style="width: 60px; height: 60px; object-fit: contain;">
                                            <?php else: ?>
                                                <div class="bg-secondary-subtle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                    <i class="bi bi-briefcase"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col">
                                            <h5 class="card-title mb-1"><?= e($internship['title']) ?></h5>
                                            <p class="text-muted small mb-2"><?= e($internship['company_name']) ?></p>
                                            
                                            <div class="mb-2">
                                                <?php if ($internship['work_type']): ?>
                                                    <span class="badge bg-light text-dark me-1"><?= e($internship['work_type']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($internship['stipend']): ?>
                                                    <span class="badge bg-success me-1">Rs. <?= e(number_format($internship['stipend'], 0)) ?></span>
                                                <?php endif; ?>
                                                <?php if ($internship['duration_months']): ?>
                                                    <span class="badge bg-info me-1"><?= e($internship['duration_months']) ?> months</span>
                                                <?php endif; ?>
                                            </div>

                                            <p class="card-text small text-muted mb-2"><?= e(substr($internship['responsibilities'] ?? '', 0, 150) . '...') ?></p>

                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    📍 <?= e($internship['district']) ?>
                                                    <?php if ($internship['application_deadline']): ?>
                                                        | Deadline: <?= e(date('M j, Y', strtotime($internship['application_deadline']))) ?>
                                                    <?php endif; ?>
                                                </small>
                                                <a href="<?= e(app_url('internship-detail.php?id=' . $internship['id'])) ?>" class="btn btn-sm btn-primary">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= e($page - 1) ?>&keyword=<?= e($keyword) ?>&district=<?= e($district) ?>&province=<?= e($province) ?>&work_type=<?= e($workType) ?>&industry=<?= e($industry) ?>&sort=<?= e($sort) ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= e($i) ?>&keyword=<?= e($keyword) ?>&district=<?= e($district) ?>&province=<?= e($province) ?>&work_type=<?= e($workType) ?>&industry=<?= e($industry) ?>&sort=<?= e($sort) ?>"><?= e($i) ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= e($page + 1) ?>&keyword=<?= e($keyword) ?>&district=<?= e($district) ?>&province=<?= e($province) ?>&work_type=<?= e($workType) ?>&industry=<?= e($industry) ?>&sort=<?= e($sort) ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info text-center py-5">
                    <i class="bi bi-search fs-1"></i>
                    <p class="mt-3">No internships found matching your filters. Try adjusting your search criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
