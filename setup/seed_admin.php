<?php
/**
 * One-time script to create/update the default admin password.
 * Visit: http://localhost/Intern_search_and_apply_system/setup/seed_admin.php
 * DELETE or protect this file in production.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

$email = 'admin@internconnect.lk';
$password = 'Admin@123';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $mysqli->prepare('SELECT id FROM users WHERE email = ? AND role = ?');
$role = ROLE_ADMIN;
$stmt->bind_param('ss', $email, $role);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    $stmt = $mysqli->prepare('UPDATE users SET password_hash = ?, status = ?, email_verified = 1 WHERE id = ?');
    $status = STATUS_ACTIVE;
    $stmt->bind_param('ssi', $hash, $status, $user['id']);
    $stmt->execute();
    $stmt->close();
    echo '<h2>Admin password updated.</h2>';
} else {
    $stmt = $mysqli->prepare('INSERT INTO users (email, password_hash, role, status, email_verified) VALUES (?, ?, ?, ?, 1)');
    $role = ROLE_ADMIN;
    $status = STATUS_ACTIVE;
    $stmt->bind_param('ssss', $email, $hash, $role, $status);
    $stmt->execute();
    $userId = $stmt->insert_id;
    $stmt->close();

    $stmt = $mysqli->prepare('INSERT INTO admins (user_id, full_name) VALUES (?, ?)');
    $name = 'System Administrator';
    $stmt->bind_param('is', $userId, $name);
    $stmt->execute();
    $stmt->close();
    echo '<h2>Admin account created.</h2>';
}

echo '<p>Email: <strong>' . htmlspecialchars($email) . '</strong></p>';
echo '<p>Password: <strong>' . htmlspecialchars($password) . '</strong></p>';
echo '<p><a href="../auth/login.php">Go to Login</a></p>';
