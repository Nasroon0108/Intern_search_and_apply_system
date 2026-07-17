<?php
/**
 * One-time script to create/update the default admin password.
 * Visit: http://localhost/Intern_search_and_apply_system/setup/seed_admin.php
 * DELETE or protect this file in production.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

$email = 'admin@internconnect.local';
$password = 'Admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

header('Content-Type: text/html; charset=UTF-8');

$stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    $stmt = $mysqli->prepare('UPDATE users SET password_hash = ?, role = ?, status = ?, email_verified = 1 WHERE id = ?');
    $role = ROLE_ADMIN;
    $status = STATUS_ACTIVE;
    $stmt->bind_param('sssi', $hash, $role, $status, $user['id']);
    $stmt->execute();
    $stmt->close();
    $title = 'Admin password reset';
    $message = 'Admin password was updated successfully.';
} else {
    $stmt = $mysqli->prepare('INSERT INTO users (email, password_hash, role, status, email_verified) VALUES (?, ?, ?, ?, 1)');
    $role = ROLE_ADMIN;
    $status = STATUS_ACTIVE;
    $stmt->bind_param('ssss', $email, $hash, $role, $status);
    $stmt->execute();
    $userId = (int) $stmt->insert_id;
    $stmt->close();

    $stmt = $mysqli->prepare('INSERT INTO admins (user_id, full_name) VALUES (?, ?)');
    $name = 'System Administrator';
    $stmt->bind_param('is', $userId, $name);
    $stmt->execute();
    $stmt->close();
    $title = 'Admin account created';
    $message = 'Admin account was created successfully.';
}

$stmt = $mysqli->prepare('SELECT id FROM admins WHERE user_id = ? LIMIT 1');
$adminUserId = (int) ($user['id'] ?? $userId);
$stmt->bind_param('i', $adminUserId);
$stmt->execute();
$adminProfile = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$adminProfile) {
    $stmt = $mysqli->prepare('INSERT INTO admins (user_id, full_name) VALUES (?, ?)');
    $name = 'System Administrator';
    $stmt->bind_param('is', $adminUserId, $name);
    $stmt->execute();
    $stmt->close();
}

$stmt = $mysqli->prepare('SELECT email, role, status, email_verified, password_hash FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $adminUserId);
$stmt->execute();
$adminRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$dbName = $mysqli->query('SELECT DATABASE() AS db_name')->fetch_assoc()['db_name'] ?? 'unknown';
$passwordCheck = $adminRow ? password_verify($password, $adminRow['password_hash']) : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 560px; margin: 3rem auto; padding: 0 1rem; }
        .box { border: 1px solid #d1d5db; border-radius: 8px; padding: 1.25rem; background: #f9fafb; }
        code { background: #e5e7eb; padding: 0.1rem 0.35rem; border-radius: 4px; }
        a { color: #1349cc; }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($title) ?></h1>
    <p><?= htmlspecialchars($message) ?></p>
    <div class="box">
        <p><strong>Email:</strong> <code><?= htmlspecialchars($email) ?></code></p>
        <p><strong>Password:</strong> <code><?= htmlspecialchars($password) ?></code></p>
        <p><em>Password is case-sensitive. Copy exactly.</em></p>
    </div>
    <h2>Check</h2>
    <div class="box">
        <p><strong>Database:</strong> <code><?= htmlspecialchars($dbName) ?></code></p>
        <p><strong>Role:</strong> <code><?= htmlspecialchars($adminRow['role'] ?? 'missing') ?></code></p>
        <p><strong>Status:</strong> <code><?= htmlspecialchars($adminRow['status'] ?? 'missing') ?></code></p>
        <p><strong>Email verified:</strong> <code><?= htmlspecialchars((string)($adminRow['email_verified'] ?? 'missing')) ?></code></p>
        <p><strong>Password test:</strong> <code><?= $passwordCheck ? 'PASS' : 'FAIL' ?></code></p>
    </div>
    <p><a href="../auth/login.php">Go to Login</a></p>
</body>
</html>
