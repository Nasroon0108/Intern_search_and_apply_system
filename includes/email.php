<?php
/**
 * Email sending utilities
 */

function send_email(string $to, string $subject, string $htmlBody): bool
{
    if (!MAIL_ENABLED) {
        return true;
    }

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: " . APP_NAME . " <noreply@internconnect.lk>" . "\r\n";
    $headers .= "Reply-To: support@internconnect.lk" . "\r\n";

    $sent = @mail($to, $subject, $htmlBody, $headers);
    if (!$sent) {
        error_log("Failed to send email to {$to}: {$subject}");
    }

    return $sent;
}

function send_verification_email(string $email, string $token): bool
{
    $verificationLink = app_url("auth/verify-email.php?token=" . urlencode($token));
    
    $subject = APP_NAME . " - Email Verification";
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2>Welcome to " . e(APP_NAME) . "!</h2>
        <p>Thank you for registering. Please verify your email address to activate your account.</p>
        <p>
            <a href='{$verificationLink}' style='display: inline-block; background-color: #0056b3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                Verify Email Address
            </a>
        </p>
        <p>Or copy and paste this link in your browser:</p>
        <p style='word-break: break-all;'>{$verificationLink}</p>
        <p>This link will expire in 24 hours.</p>
        <hr>
        <p style='font-size: 12px; color: #666;'>
            If you didn't register for this account, please ignore this email.
        </p>
    </div>";

    return send_email($email, $subject, $htmlBody);
}

function send_password_reset_email(string $email, string $token): bool
{
    $resetLink = app_url("auth/reset-password.php?token=" . urlencode($token));
    
    $subject = APP_NAME . " - Password Reset Request";
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2>Password Reset Request</h2>
        <p>You requested to reset your password. Click the link below to proceed:</p>
        <p>
            <a href='{$resetLink}' style='display: inline-block; background-color: #0056b3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                Reset Password
            </a>
        </p>
        <p>Or copy and paste this link in your browser:</p>
        <p style='word-break: break-all;'>{$resetLink}</p>
        <p>This link will expire in 1 hour.</p>
        <hr>
        <p style='font-size: 12px; color: #666;'>
            If you didn't request a password reset, please ignore this email or contact support.
        </p>
    </div>";

    return send_email($email, $subject, $htmlBody);
}

function send_application_confirmation_email(string $to, array $student, array $internship, array $company): bool
{
    $subject = APP_NAME . " - Application Received";
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2>Application Received</h2>
        <p>Hi " . e($student['full_name']) . ",</p>
        <p>Your application for <strong>" . e($internship['title']) . "</strong> at <strong>" . e($company['company_name']) . "</strong> has been received.</p>
        <p>You can track your application status in your dashboard.</p>
        <p><a href='" . app_url('student/dashboard.php') . "' style='color: #0056b3;'>View Your Applications</a></p>
        <hr>
        <p style='font-size: 12px; color: #666;'>Thank you for using " . e(APP_NAME) . "</p>
    </div>";

    return send_email($to, $subject, $htmlBody);
}

function send_shortlist_email(string $to, array $student, array $internship, array $company): bool
{
    $subject = "Great News! You've been shortlisted for " . e($internship['title']);
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2>You've Been Shortlisted!</h2>
        <p>Hi " . e($student['full_name']) . ",</p>
        <p>Congratulations! " . e($company['company_name']) . " has shortlisted you for the <strong>" . e($internship['title']) . "</strong> position.</p>
        <p>Check your dashboard for updates and next steps.</p>
        <p><a href='" . app_url('student/dashboard.php') . "' style='color: #0056b3;'>View Updates</a></p>
        <hr>
        <p style='font-size: 12px; color: #666;'>Good luck!</p>
    </div>";

    return send_email($to, $subject, $htmlBody);
}

function send_interview_invitation_email(string $to, array $student, array $interview, array $internship, array $company): bool
{
    $date = date('F j, Y', strtotime($interview['scheduled_at']));
    $time = date('h:i A', strtotime($interview['scheduled_at']));
    
    $subject = "Interview Invitation - " . e($internship['title']) . " at " . e($company['company_name']);
    $htmlBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2>Interview Invitation</h2>
        <p>Hi " . e($student['full_name']) . ",</p>
        <p>" . e($company['company_name']) . " invites you to an interview for the <strong>" . e($internship['title']) . "</strong> position.</p>
        <p>
            <strong>Interview Details:</strong><br>
            Date: {$date}<br>
            Time: {$time}<br>
            " . (!empty($interview['meeting_link']) ? "Meeting Link: <a href='" . e($interview['meeting_link']) . "'>Join Here</a><br>" : '') . "
            Location: " . e($interview['location'] ?? 'To be decided') . "
        </p>
        <p>" . e($interview['notes'] ?? '') . "</p>
        <p><a href='" . app_url('student/dashboard.php') . "' style='color: #0056b3;'>View More Details</a></p>
        <hr>
        <p style='font-size: 12px; color: #666;'>Best of luck!</p>
    </div>";

    return send_email($to, $subject, $htmlBody);
}
