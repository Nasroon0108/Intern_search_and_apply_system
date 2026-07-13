<?php
$pageTitle   = 'My Certifications';
$currentPage = 'certifications';
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
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

require_once dirname(__DIR__) . '/includes/student-layout.php';
?>

<h1 class="page-title">My Certifications</h1>
<p class="page-sub">Add your professional certifications and courses</p>

<div class="ds-card p-4">
    <div class="sp-card-header">
        <div class="sp-heading" style="margin-bottom:0;">Certifications</div>
        <?php if ($action === 'list'): ?>
            <a href="?action=add" class="sp-btn-primary"><i class="bi bi-plus-lg"></i> Add Certification</a>
        <?php else: ?>
            <a href="?action=list" class="sp-btn-outline">← Back</a>
        <?php endif; ?>
    </div>
    <?php if (isset($message)): ?><div class="alert alert-success alert-dismissible fade show py-2 px-3 small"><?= e($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

    <?php if ($action === 'list'): ?>
        <?php if (count($certs) > 0): ?>
            <div class="sp-list-stack">
            <?php foreach ($certs as $c): ?>
                <div class="sp-record-row">
                    <div>
                        <div class="sp-item-title"><?= e($c['title']) ?></div>
                        <?php if ($c['issuer']): ?><div class="sp-item-sub">Issued by: <?= e($c['issuer']) ?></div><?php endif; ?>
                        <?php if ($c['issue_date']): ?><div class="sp-muted"><?= e(date('M Y', strtotime($c['issue_date']))) ?></div><?php endif; ?>
                    </div>
                    <div class="sp-record-actions">
                        <a href="?action=edit&id=<?= e($c['id']) ?>" class="sp-btn-outline">Edit</a>
                        <a href="?delete=<?= e($c['id']) ?>" onclick="return confirm('Delete?')" class="sp-btn-danger">Delete</a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="sp-empty"><i class="bi bi-award"></i>No certifications added yet.</div>
        <?php endif; ?>
    <?php else: ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="<?= e($action) ?>">
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Certification Title *</label><input type="text" class="form-control" name="title" required value="<?= e($cert['title'] ?? '') ?>"></div>
                <div class="col-12"><label class="form-label">Issuing Organization</label><input type="text" class="form-control" name="issuer" value="<?= e($cert['issuer'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Issue Date</label><input type="date" class="form-control" name="issue_date" value="<?= e($cert['issue_date'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Credential URL</label><input type="url" class="form-control" name="credential_url" value="<?= e($cert['credential_url'] ?? '') ?>"></div>
            </div>
            <div class="mt-3 sp-form-actions">
                <button type="submit" class="sp-btn-primary">Save</button>
                <a href="?action=list" class="sp-btn-outline">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/student-layout-end.php'; ?>
