<?php
/**
 * Secure CV download for company users.
 * Only allows download when the CV belongs to an application for this company's internship.
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/config/database.php';

init_session();
require_role(ROLE_COMPANY);

$userId = current_user_id();
$company = get_company_by_user_id($mysqli, $userId);

if (!$company) {
    http_response_code(403);
    die('Access denied.');
}

$applicationId = (int)($_GET['application_id'] ?? 0);

if (!$applicationId) {
    http_response_code(400);
    die('Invalid request.');
}

$stmt = $mysqli->prepare(
    'SELECT cv.file_path, cv.title, s.full_name
     FROM applications a
     JOIN internships i ON i.id = a.internship_id
     JOIN students s ON s.id = a.student_id
     JOIN student_cvs cv ON cv.id = a.cv_id
     WHERE a.id = ? AND i.company_id = ? AND a.cv_id IS NOT NULL
     LIMIT 1'
);
$stmt->bind_param('ii', $applicationId, $company['id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || empty($row['file_path'])) {
    http_response_code(404);
    die('CV not found for this application.');
}

$filePath = rtrim(UPLOAD_CV_PATH, '/\\') . DIRECTORY_SEPARATOR . basename($row['file_path']);

if (!is_file($filePath)) {
    http_response_code(404);
    die('CV file is missing from the server.');
}

$safeName = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $row['full_name'] ?: 'candidate');
$downloadName = trim($safeName) . '_CV.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $downloadName . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');

readfile($filePath);
exit;
