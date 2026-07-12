<?php
/**
 * Authentication & session management
 */

require_once __DIR__ . '/functions.php';

function init_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to continue.');
        redirect(app_url('auth/login.php'));
    }
}

function require_role(string ...$roles): void
{
    require_login();
    if (!in_array(current_user_role(), $roles, true)) {
        http_response_code(403);
        die('Access denied.');
    }
}

function login_user(mysqli $db, string $email, string $password): array
{
    $stmt = $db->prepare('SELECT id, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    if ($user['status'] === STATUS_BLOCKED) {
        return ['success' => false, 'error' => 'Your account has been blocked. Contact support.'];
    }

    if ($user['role'] === ROLE_COMPANY && $user['status'] === STATUS_PENDING) {
        // Allow login but company may have limited access until verified
    } elseif ($user['status'] !== STATUS_ACTIVE) {
        return ['success' => false, 'error' => 'Your account is not active yet. Please wait for approval.'];
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    $stmt = $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $stmt->close();

    return ['success' => true, 'role' => $user['role']];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function register_student(mysqli $db, array $data): array
{
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $confirm = $data['confirm_password'] ?? '';
    $fullName = trim($data['full_name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $university = trim($data['university'] ?? '');
    $district = trim($data['district'] ?? '');

    if (!$fullName || !$email || !$password) {
        return ['success' => false, 'error' => 'Please fill in all required fields.'];
    }
    if (!is_valid_email($email)) {
        return ['success' => false, 'error' => 'Invalid email address.'];
    }
    if ($password !== $confirm) {
        return ['success' => false, 'error' => 'Passwords do not match.'];
    }
    if ($pwdError = validate_password($password)) {
        return ['success' => false, 'error' => $pwdError];
    }

    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'error' => 'An account with this email already exists.'];
    }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = ROLE_STUDENT;
    $status = STATUS_ACTIVE;

    $db->begin_transaction();
    try {
        $stmt = $db->prepare('INSERT INTO users (email, password_hash, role, status, email_verified) VALUES (?, ?, ?, ?, 0)');
        $stmt->bind_param('ssss', $email, $hash, $role, $status);
        $stmt->execute();
        $userId = $stmt->insert_id;
        $stmt->close();

        $stmt = $db->prepare('INSERT INTO students (user_id, full_name, phone, university, district, profile_completion) VALUES (?, ?, ?, ?, ?, 20)');
        $stmt->bind_param('issss', $userId, $fullName, $phone, $university, $district);
        $stmt->execute();
        $stmt->close();

        $db->commit();
        return ['success' => true, 'user_id' => $userId];
    } catch (Throwable $e) {
        $db->rollback();
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
}

function register_company(mysqli $db, array $data): array
{
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $confirm = $data['confirm_password'] ?? '';
    $companyName = trim($data['company_name'] ?? '');
    $industry = trim($data['industry'] ?? '');
    $district = trim($data['district'] ?? '');
    $contactPerson = trim($data['contact_person'] ?? '');
    $phone = trim($data['phone'] ?? '');

    if (!$companyName || !$email || !$password || !$contactPerson) {
        return ['success' => false, 'error' => 'Please fill in all required fields.'];
    }
    if (!is_valid_email($email)) {
        return ['success' => false, 'error' => 'Invalid email address.'];
    }
    if ($password !== $confirm) {
        return ['success' => false, 'error' => 'Passwords do not match.'];
    }
    if ($pwdError = validate_password($password)) {
        return ['success' => false, 'error' => $pwdError];
    }

    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'error' => 'An account with this email already exists.'];
    }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = ROLE_COMPANY;
    $status = STATUS_PENDING;

    $db->begin_transaction();
    try {
        $stmt = $db->prepare('INSERT INTO users (email, password_hash, role, status, email_verified) VALUES (?, ?, ?, ?, 0)');
        $stmt->bind_param('ssss', $email, $hash, $role, $status);
        $stmt->execute();
        $userId = $stmt->insert_id;
        $stmt->close();

        $stmt = $db->prepare(
            'INSERT INTO companies (user_id, company_name, industry, district, contact_person, contact_email, phone, verification_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $verificationStatus = 'pending';
        $stmt->bind_param('isssssss', $userId, $companyName, $industry, $district, $contactPerson, $email, $phone, $verificationStatus);
        $stmt->execute();
        $stmt->close();

        $db->commit();
        return ['success' => true, 'user_id' => $userId];
    } catch (Throwable $e) {
        $db->rollback();
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
}

function get_student_by_user_id(mysqli $db, int $userId): ?array
{
    $stmt = $db->prepare('SELECT * FROM students WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function get_company_by_user_id(mysqli $db, int $userId): ?array
{
    $stmt = $db->prepare('SELECT * FROM companies WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function get_admin_by_user_id(mysqli $db, int $userId): ?array
{
    $stmt = $db->prepare('SELECT a.*, u.email FROM admins a JOIN users u ON u.id = a.user_id WHERE a.user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}
