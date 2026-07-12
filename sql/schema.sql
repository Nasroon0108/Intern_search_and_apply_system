-- InternConnect Sri Lanka - Database Schema (Phase 1+ foundation)
-- Run in phpMyAdmin or: mysql -u root -p < sql/schema.sql

CREATE DATABASE IF NOT EXISTS internconnect_sl
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE internconnect_sl;

-- ============================================================
-- Core users & authentication
-- ============================================================

CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('student', 'company', 'admin') NOT NULL,
    status          ENUM('active', 'pending', 'blocked') NOT NULL DEFAULT 'pending',
    email_verified  TINYINT(1) NOT NULL DEFAULT 0,
    last_login      DATETIME NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

CREATE TABLE password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token       VARCHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    used        TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE email_verifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token       VARCHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    verified_at DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Student profile
-- ============================================================

CREATE TABLE students (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL UNIQUE,
    full_name       VARCHAR(150) NOT NULL,
    phone           VARCHAR(20) NULL,
    district        VARCHAR(80) NULL,
    province        VARCHAR(80) NULL,
    university      VARCHAR(200) NULL,
    degree_program  VARCHAR(200) NULL,
    gpa             DECIMAL(3,2) NULL,
    profile_photo   VARCHAR(255) NULL,
    bio             TEXT NULL,
    profile_completion TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_students_district (district),
    INDEX idx_students_university (university)
) ENGINE=InnoDB;

CREATE TABLE student_cvs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id  INT UNSIGNED NOT NULL,
    title       VARCHAR(100) NOT NULL DEFAULT 'My CV',
    file_path   VARCHAR(255) NOT NULL,
    is_primary  TINYINT(1) NOT NULL DEFAULT 0,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE education (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED NOT NULL,
    institution     VARCHAR(200) NOT NULL,
    degree          VARCHAR(150) NOT NULL,
    field_of_study  VARCHAR(150) NULL,
    start_year      YEAR NULL,
    end_year        YEAR NULL,
    gpa             DECIMAL(3,2) NULL,
    description     TEXT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE certifications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED NOT NULL,
    title           VARCHAR(200) NOT NULL,
    issuer          VARCHAR(200) NULL,
    issue_date      DATE NULL,
    credential_url  VARCHAR(500) NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE projects (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id  INT UNSIGNED NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT NULL,
    technologies VARCHAR(300) NULL,
    project_url VARCHAR(500) NULL,
    start_date  DATE NULL,
    end_date    DATE NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Skills
-- ============================================================

CREATE TABLE skill_categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    type        ENUM('technical', 'soft') NOT NULL DEFAULT 'technical'
) ENGINE=InnoDB;

CREATE TABLE skills (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NULL,
    name        VARCHAR(100) NOT NULL,
    FOREIGN KEY (category_id) REFERENCES skill_categories(id) ON DELETE SET NULL,
    UNIQUE KEY uq_skill_name (name)
) ENGINE=InnoDB;

CREATE TABLE student_skills (
    student_id  INT UNSIGNED NOT NULL,
    skill_id    INT UNSIGNED NOT NULL,
    proficiency ENUM('beginner', 'intermediate', 'advanced') NOT NULL DEFAULT 'intermediate',
    PRIMARY KEY (student_id, skill_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Company profile
-- ============================================================

CREATE TABLE companies (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL UNIQUE,
    company_name        VARCHAR(200) NOT NULL,
    industry            VARCHAR(100) NULL,
    district            VARCHAR(80) NULL,
    province            VARCHAR(80) NULL,
    address             TEXT NULL,
    website             VARCHAR(300) NULL,
    phone               VARCHAR(20) NULL,
    description         TEXT NULL,
    logo                VARCHAR(255) NULL,
    contact_person      VARCHAR(150) NULL,
    contact_email       VARCHAR(255) NULL,
    verified            TINYINT(1) NOT NULL DEFAULT 0,
    verification_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_companies_verified (verified),
    INDEX idx_companies_district (district)
) ENGINE=InnoDB;

-- ============================================================
-- Internships & applications
-- ============================================================

CREATE TABLE internships (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id          INT UNSIGNED NOT NULL,
    title               VARCHAR(200) NOT NULL,
    category            VARCHAR(100) NULL,
    industry            VARCHAR(100) NULL,
    location            VARCHAR(150) NULL,
    district            VARCHAR(80) NULL,
    province            VARCHAR(80) NULL,
    work_type           ENUM('On-site', 'Remote', 'Hybrid') NOT NULL DEFAULT 'On-site',
    stipend             DECIMAL(10,2) NULL,
    stipend_note        VARCHAR(200) NULL,
    duration_months     TINYINT UNSIGNED NULL,
    vacancies           SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    responsibilities    TEXT NULL,
    requirements        TEXT NULL,
    benefits            TEXT NULL,
    contact_email       VARCHAR(255) NULL,
    contact_phone       VARCHAR(20) NULL,
    application_deadline DATE NULL,
    status              ENUM('draft', 'pending', 'active', 'closed', 'rejected') NOT NULL DEFAULT 'pending',
    views_count         INT UNSIGNED NOT NULL DEFAULT 0,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    INDEX idx_internships_status (status),
    INDEX idx_internships_district (district),
    INDEX idx_internships_deadline (application_deadline),
    FULLTEXT idx_internships_search (title, responsibilities, requirements)
) ENGINE=InnoDB;

CREATE TABLE internship_skills (
    internship_id   INT UNSIGNED NOT NULL,
    skill_id        INT UNSIGNED NOT NULL,
    PRIMARY KEY (internship_id, skill_id),
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE applications (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      INT UNSIGNED NOT NULL,
    internship_id   INT UNSIGNED NOT NULL,
    cv_id           INT UNSIGNED NULL,
    cover_letter    TEXT NULL,
    cover_letter_file VARCHAR(255) NULL,
    status          ENUM('pending', 'shortlisted', 'interview', 'accepted', 'rejected', 'withdrawn') NOT NULL DEFAULT 'pending',
    applied_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    company_notes   TEXT NULL,
    UNIQUE KEY uq_application (student_id, internship_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE,
    FOREIGN KEY (cv_id) REFERENCES student_cvs(id) ON DELETE SET NULL,
    INDEX idx_applications_status (status)
) ENGINE=InnoDB;

CREATE TABLE favorites (
    student_id      INT UNSIGNED NOT NULL,
    internship_id   INT UNSIGNED NOT NULL,
    saved_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id, internship_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES internships(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Notifications, messaging, interviews
-- ============================================================

CREATE TABLE notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(200) NOT NULL,
    message     TEXT NOT NULL,
    type        VARCHAR(50) NOT NULL DEFAULT 'info',
    link        VARCHAR(500) NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_notifications_user_read (user_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE messages (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id       INT UNSIGNED NOT NULL,
    receiver_id     INT UNSIGNED NOT NULL,
    application_id  INT UNSIGNED NULL,
    subject         VARCHAR(200) NULL,
    body            TEXT NOT NULL,
    is_read         TINYINT(1) NOT NULL DEFAULT 0,
    sent_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE interviews (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED NOT NULL,
    scheduled_at    DATETIME NOT NULL,
    location        VARCHAR(300) NULL,
    meeting_link    VARCHAR(500) NULL,
    notes           TEXT NULL,
    status          ENUM('scheduled', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'scheduled',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Admin & platform
-- ============================================================

CREATE TABLE admins (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL UNIQUE,
    full_name   VARCHAR(150) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE announcements (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT UNSIGNED NOT NULL,
    title       VARCHAR(200) NOT NULL,
    content     TEXT NOT NULL,
    target_role ENUM('all', 'student', 'company') NOT NULL DEFAULT 'all',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE admin_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT UNSIGNED NOT NULL,
    action      VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NULL,
    target_id   INT UNSIGNED NULL,
    details     TEXT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- Seed data
-- ============================================================

INSERT INTO skill_categories (name, type) VALUES
    ('Programming', 'technical'),
    ('Web Development', 'technical'),
    ('Data & Analytics', 'technical'),
    ('Communication', 'soft'),
    ('Leadership', 'soft');

INSERT INTO skills (category_id, name) VALUES
    (1, 'Python'), (1, 'Java'), (1, 'PHP'), (1, 'C#'),
    (2, 'HTML/CSS'), (2, 'JavaScript'), (2, 'React'), (2, 'Bootstrap'),
    (3, 'SQL'), (3, 'Excel'), (3, 'Power BI'),
    (4, 'Teamwork'), (4, 'Presentation'),
    (5, 'Project Management');

-- Default admin: run setup/seed_admin.php after importing schema
-- Email: admin@internconnect.lk | Password: Admin@123
