<?php
/**
 * Authentication & session management
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/email.php';

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

    // Admin accounts bypass pending/verification checks
    if ($user['role'] === ROLE_ADMIN) {
        if ($user['status'] !== STATUS_ACTIVE) {
            return ['success' => false, 'error' => 'Admin account is not active. Contact system administrator.'];
        }
    } elseif ($user['role'] === ROLE_COMPANY && $user['status'] === STATUS_PENDING) {
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

        // Generate verification token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $stmt = $db->prepare('INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $userId, $token, $expiresAt);
        $stmt->execute();
        $stmt->close();

        $db->commit();

        // Send verification email (skipped on localhost — see MAIL_ENABLED)
        send_verification_email($email, $token);

        return [
            'success' => true,
            'user_id' => $userId,
            'verify_token' => $token,
            'message' => 'Registration successful! Check your email to verify your account.',
        ];
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

        // Generate verification token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $stmt = $db->prepare('INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $userId, $token, $expiresAt);
        $stmt->execute();
        $stmt->close();

        $db->commit();

        // Send verification email (skipped on localhost — see MAIL_ENABLED)
        send_verification_email($email, $token);

        return [
            'success' => true,
            'user_id' => $userId,
            'verify_token' => $token,
            'message' => 'Registration successful! Check your email to verify your account.',
        ];
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

/**
 * Verify email token
 */
function verify_email(mysqli $db, string $token): array
{
    $stmt = $db->prepare(
        'SELECT id, user_id, expires_at, verified_at FROM email_verifications 
         WHERE token = ? AND expires_at > NOW() LIMIT 1'
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $verification = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$verification) {
        return ['success' => false, 'error' => 'Invalid or expired verification link.'];
    }

    if ($verification['verified_at']) {
        return ['success' => true, 'message' => 'Email already verified. You can log in.'];
    }

    $userId = (int) $verification['user_id'];

    $stmt = $db->prepare('SELECT role, status FROM users WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return ['success' => false, 'error' => 'User account not found.'];
    }

    $db->begin_transaction();
    try {
        // Companies stay pending until an admin approves the company profile
        if ($user['role'] === ROLE_COMPANY && $user['status'] === STATUS_PENDING) {
            $stmt = $db->prepare('UPDATE users SET email_verified = 1 WHERE id = ?');
            $stmt->bind_param('i', $userId);
        } else {
            $stmt = $db->prepare('UPDATE users SET email_verified = 1, status = ? WHERE id = ?');
            $status = STATUS_ACTIVE;
            $stmt->bind_param('si', $status, $userId);
        }
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare('UPDATE email_verifications SET verified_at = NOW() WHERE id = ?');
        $stmt->bind_param('i', $verification['id']);
        $stmt->execute();
        $stmt->close();

        $db->commit();
        return ['success' => true, 'message' => 'Email verified successfully! You can now log in.'];
    } catch (Throwable $e) {
        $db->rollback();
        return ['success' => false, 'error' => 'Verification failed. Please try again.'];
    }
}

/**
 * Admin helper: mark a user's email as verified without a token
 */
function mark_email_verified(mysqli $db, int $userId): bool
{
    $stmt = $db->prepare('UPDATE users SET email_verified = 1 WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        $stmt = $db->prepare(
            'UPDATE email_verifications SET verified_at = NOW()
             WHERE user_id = ? AND verified_at IS NULL'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }

    return $ok;
}

/**
 * Initiate password reset
 */
function request_password_reset(mysqli $db, string $email): array
{
    $stmt = $db->prepare('SELECT id, email FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // For security, don't reveal if email exists
    if (!$user) {
        return ['success' => true, 'message' => 'If an account exists with that email, a password reset link has been sent.'];
    }

    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $user['id'], $token, $expiresAt);
    $stmt->execute();
    $stmt->close();

    // Send email
    send_password_reset_email($user['email'], $token);

    return ['success' => true, 'message' => 'If an account exists with that email, a password reset link has been sent.'];
}

/**
 * Reset password with token
 */
function reset_password(mysqli $db, string $token, string $newPassword, string $confirmPassword): array
{
    if ($newPassword !== $confirmPassword) {
        return ['success' => false, 'error' => 'Passwords do not match.'];
    }

    if ($pwdError = validate_password($newPassword)) {
        return ['success' => false, 'error' => $pwdError];
    }

    $stmt = $db->prepare(
        'SELECT id, user_id, expires_at, used FROM password_resets 
         WHERE token = ? AND expires_at > NOW() LIMIT 1'
    );
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reset) {
        return ['success' => false, 'error' => 'Invalid or expired password reset link.'];
    }

    if ($reset['used']) {
        return ['success' => false, 'error' => 'This password reset link has already been used.'];
    }

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $userId = $reset['user_id'];

    $db->begin_transaction();
    try {
        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->bind_param('si', $hash, $userId);
        $stmt->execute();
        $stmt->close();

        $stmt = $db->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
        $stmt->bind_param('i', $reset['id']);
        $stmt->execute();
        $stmt->close();

        $db->commit();
        return ['success' => true, 'message' => 'Password reset successfully! You can now log in.'];
    } catch (Throwable $e) {
        $db->rollback();
        return ['success' => false, 'error' => 'Password reset failed. Please try again.'];
    }
}
