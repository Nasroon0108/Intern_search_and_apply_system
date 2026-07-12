<?php
$pageTitle = 'My Certifications';
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/config/database.php';

require_role(ROLE_STUDENT);

$userId = current_user_id();
$student = get_student_by_user_id($mysqli, $userId);

$action = $_GET['action'] ?? 'list';
$certId = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    
    $title = trim($_POST['title'] ?? '');
    $issuer = trim($_POST['issuer'] ?? '');
    $issueDate = !empty($_POST['issue_date']) ? $_POST['issue_date'] : null;
    $credentialUrl = trim($_POST['credential_url'] ?? '');
    
    if (!$title) {
        $error = 'Certification title is required.';
    } else if ($_POST['_action'] === 'add') {
        $stmt = $mysqli->prepare(
            'INSERT INTO certifications (student_id, title, issuer, issue_date, credential_url) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('issss', $student['id'], $title, $issuer, $issueDate, $credentialUrl);
        if ($stmt->execute()) {
            $message = 'Certification added successfully!';
            $action = 'list';
        }
        $stmt->close();
    } else if ($_POST['_action'] === 'edit' && $certId) {
        $stmt = $mysqli->prepare(
            'UPDATE certifications SET title = ?, issuer = ?, issue_date = ?, credential_url = ? WHERE id = ? AND student_id = ?'
        );
        $stmt->bind_param('isssii', $title, $issuer, $issueDate, $credentialUrl, $certId, $student['id']);
        if ($stmt->execute()) {
            $message = 'Certification updated successfully!';
            $action = 'list';
        }
        $stmt->close();
    }
}

if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $mysqli->prepare('DELETE FROM certifications WHERE id = ? AND student_id = ?');
    $stmt->bind_param('ii', $delId, $student['id']);
    $stmt->execute();
    $stmt->close();
    $action = 'list';
}

$stmt = $mysqli->prepare('SELECT * FROM certifications WHERE student_id = ? ORDER BY issue_date DESC');
$stmt->bind_param('i', $student['id']);
$stmt->execute();
$certs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$cert = null;
if ($action !== 'list' && $certId) {
    foreach ($certs as $c) {
        if ($c['id'] === $certId) {
            $cert = $c;
            break;
        }
    }
}
?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="list-group list-group-flush">
                    <a href="<?= e(app_url('student/profile.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-person"></i> Basic Info
                    </a>
                    <a href="<?= e(app_url('student/education.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-mortarboard"></i> Education
                    </a>
                    <a href="<?= e(app_url('student/skills.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-star"></i> Skills
                    </a>
                    <a href="<?= e(app_url('student/projects.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-briefcase"></i> Projects
                    </a>
                    <a href="<?= e(app_url('student/certifications.php')) ?>" class="list-group-item list-group-item-action active">
                        <i class="bi bi-award"></i> Certifications
                    </a>
                    <a href="<?= e(app_url('student/cvs.php')) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-file-pdf"></i> CVs
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">My Certifications</h5>
                    <?php if ($action === 'list'): ?>
                        <a href="?action=add" class="btn btn-sm btn-primary">Add Certification</a>
                    <?php else: ?>
                        <a href="?action=list" class="btn btn-sm btn-secondary">Back</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (isset($message)): ?>
                        <div class="alert alert-success alert-dismissible fade show"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>

                    <?php if ($action === 'list'): ?>
                        <?php if (count($certs) > 0): ?>
                            <div class="list-group">
                                <?php foreach ($certs as $c): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?= e($c['title']) ?></h6>
                                                <?php if ($c['issuer']): ?>
                                                    <p class="text-muted small mb-1">Issued by: <?= e($c['issuer']) ?></p>
                                                <?php endif; ?>
                                                <?php if ($c['issue_date']): ?>
                                                    <small class="text-muted"><?= e(date('M Y', strtotime($c['issue_date']))) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <a href="?action=edit&id=<?= e($c['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                <a href="?delete=<?= e($c['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No certifications added yet.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="post" novalidate>
                            <?= csrf_field() ?>
                            <input type="hidden" name="_action" value="<?= e($action) ?>">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Certification Title *</label>
                                    <input type="text" class="form-control" name="title" required value="<?= e($cert['title'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Issuing Organization</label>
                                    <input type="text" class="form-control" name="issuer" value="<?= e($cert['issuer'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Issue Date</label>
                                    <input type="date" class="form-control" name="issue_date" value="<?= e($cert['issue_date'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Credential URL</label>
                                    <input type="url" class="form-control" name="credential_url" value="<?= e($cert['credential_url'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a href="?action=list" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
