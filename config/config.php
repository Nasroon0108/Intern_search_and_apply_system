<?php
/**
 * InternConnect Sri Lanka - Application Configuration
 */

define('APP_NAME', 'InternConnect Sri Lanka');
define('APP_URL', 'http://localhost/Intern_search_and_apply_system');
define('APP_ROOT', dirname(__DIR__));

// Session
define('SESSION_LIFETIME', 3600); // 1 hour

// File uploads
define('MAX_CV_SIZE', 5 * 1024 * 1024);       // 5 MB
define('MAX_PHOTO_SIZE', 2 * 1024 * 1024);    // 2 MB
define('ALLOWED_CV_TYPES', ['application/pdf']);
define('ALLOWED_PHOTO_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

define('UPLOAD_CV_PATH', APP_ROOT . '/uploads/cvs/');
define('UPLOAD_PHOTO_PATH', APP_ROOT . '/uploads/photos/');
define('UPLOAD_LOGO_PATH', APP_ROOT . '/uploads/logos/');

// User roles
define('ROLE_STUDENT', 'student');
define('ROLE_COMPANY', 'company');
define('ROLE_ADMIN', 'admin');

// Account status
define('STATUS_ACTIVE', 'active');
define('STATUS_PENDING', 'pending');
define('STATUS_BLOCKED', 'blocked');

// Sri Lankan provinces
define('PROVINCES', [
    'Western', 'Central', 'Southern', 'Northern', 'Eastern',
    'North Western', 'North Central', 'Uva', 'Sabaragamuwa'
]);

// Sri Lankan districts (sample set for filters)
define('DISTRICTS', [
    'Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Matale', 'Nuwara Eliya',
    'Galle', 'Matara', 'Hambantota', 'Jaffna', 'Kilinochchi', 'Mannar',
    'Vavuniya', 'Mullaitivu', 'Batticaloa', 'Ampara', 'Trincomalee',
    'Kurunegala', 'Puttalam', 'Anuradhapura', 'Polonnaruwa', 'Badulla',
    'Monaragala', 'Ratnapura', 'Kegalle'
]);

// Work types
define('WORK_TYPES', ['On-site', 'Remote', 'Hybrid']);

// Internship categories
define('JOB_CATEGORIES', [
    'IT & Software', 'Engineering', 'Business & Finance', 'Marketing',
    'Design & Creative', 'Healthcare', 'Education', 'Hospitality',
    'Law', 'Agriculture', 'Other'
]);

date_default_timezone_set('Asia/Colombo');
