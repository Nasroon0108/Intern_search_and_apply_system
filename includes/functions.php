<?php
/**
 * Common helper functions
 */

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    if (!headers_sent()) {
        header('Location: ' . $path);
    }
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function is_valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_password(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Password must contain at least one uppercase letter.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        return 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'Password must contain at least one number.';
    }
    return null;
}

function app_url(string $path = ''): string
{
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function current_user_role(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

function dashboard_url_for_role(string $role): string
{
    return match ($role) {
        ROLE_STUDENT => app_url('student/dashboard.php'),
        ROLE_COMPANY => app_url('company/dashboard.php'),
        ROLE_ADMIN   => app_url('admin/dashboard.php'),
        default      => app_url('index.php'),
    };
}

/**
 * Validate and store an uploaded file.
 *
 * @return array{success: bool, path?: string, error?: string}
 */
function handle_file_upload(array $file, string $destinationDir, array $allowedMimeTypes, int $maxSize, string $prefix = 'file'): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid upload request.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'No file uploaded.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed. Please try again.'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File exceeds maximum allowed size.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        return ['success' => false, 'error' => 'File type is not allowed.'];
    }

    $extension = match ($mimeType) {
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'bin',
    };

    if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0755, true);
    }

    $filename = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $fullPath = rtrim($destinationDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        return ['success' => false, 'error' => 'Could not save uploaded file.'];
    }

    return ['success' => true, 'path' => $filename];
}

function delete_uploaded_file(string $dir, ?string $filename): void
{
    if (!$filename) {
        return;
    }
    $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . basename($filename);
    if (is_file($path)) {
        unlink($path);
    }
}
