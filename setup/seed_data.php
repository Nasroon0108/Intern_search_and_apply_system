<?php
/**
 * Seed demo data for students and companies.
 * Run once: http://localhost/Intern_search_and_apply_system/setup/seed_data.php
 * Or CLI: php setup/seed_data.php
 *
 * Default password for all seeded accounts: Demo@123
 * DELETE or protect this file in production.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

const SEED_PASSWORD = 'Demo@123';

$isCli = PHP_SAPI === 'cli';
$log = static function (string $message) use ($isCli): void {
    echo $isCli ? $message . PHP_EOL : '<li>' . htmlspecialchars($message) . '</li>';
};

if (!$isCli) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Seed Data</title>';
    echo '<style>body{font-family:system-ui,sans-serif;max-width:720px;margin:2rem auto;padding:0 1rem}';
    echo 'ul{background:#f8f9fc;border:1px solid #e8eaf0;border-radius:8px;padding:1rem 1rem 1rem 2rem}</style></head><body>';
    echo '<h1>InternConnect — Seed Data</h1><ul>';
}

$hash = password_hash(SEED_PASSWORD, PASSWORD_DEFAULT);

function seed_user(mysqli $db, string $email, string $role, string $status, int $verified = 1): int
{
  global $hash;
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $stmt = $db->prepare('UPDATE users SET password_hash = ?, role = ?, status = ?, email_verified = ? WHERE id = ?');
        $stmt->bind_param('sssii', $hash, $role, $status, $verified, $row['id']);
        $stmt->execute();
        $stmt->close();
        return (int) $row['id'];
    }

    $stmt = $db->prepare('INSERT INTO users (email, password_hash, role, status, email_verified) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssi', $email, $hash, $role, $status, $verified);
    $stmt->execute();
    $id = (int) $stmt->insert_id;
    $stmt->close();
    return $id;
}

function seed_student_profile(mysqli $db, int $userId, array $s): int
{
    $stmt = $db->prepare('SELECT id FROM students WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $stmt = $db->prepare(
            'UPDATE students SET full_name=?, phone=?, district=?, province=?, university=?, degree_program=?, gpa=?, bio=?, profile_completion=? WHERE id=?'
        );
        $stmt->bind_param(
            'ssssssdsii',
            $s['full_name'],
            $s['phone'],
            $s['district'],
            $s['province'],
            $s['university'],
            $s['degree_program'],
            $s['gpa'],
            $s['bio'],
            $s['profile_completion'],
            $row['id']
        );
        $stmt->execute();
        $stmt->close();
        return (int) $row['id'];
    }

    $stmt = $db->prepare(
        'INSERT INTO students (user_id, full_name, phone, district, province, university, degree_program, gpa, bio, profile_completion)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'issssssdsi',
        $userId,
        $s['full_name'],
        $s['phone'],
        $s['district'],
        $s['province'],
        $s['university'],
        $s['degree_program'],
        $s['gpa'],
        $s['bio'],
        $s['profile_completion']
    );
    $stmt->execute();
    $id = (int) $stmt->insert_id;
    $stmt->close();
    return $id;
}

function seed_company_profile(mysqli $db, int $userId, array $c): int
{
    $stmt = $db->prepare('SELECT id FROM companies WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $verified = 1;
    $verificationStatus = 'approved';

    if ($row) {
        $stmt = $db->prepare(
            'UPDATE companies SET company_name=?, industry=?, district=?, province=?, address=?, website=?, phone=?, description=?, contact_person=?, contact_email=?, verified=?, verification_status=? WHERE id=?'
        );
        $stmt->bind_param(
            'ssssssssssisi',
            $c['company_name'],
            $c['industry'],
            $c['district'],
            $c['province'],
            $c['address'],
            $c['website'],
            $c['phone'],
            $c['description'],
            $c['contact_person'],
            $c['contact_email'],
            $verified,
            $verificationStatus,
            $row['id']
        );
        $stmt->execute();
        $stmt->close();
        return (int) $row['id'];
    }

    $stmt = $db->prepare(
        'INSERT INTO companies (user_id, company_name, industry, district, province, address, website, phone, description, contact_person, contact_email, verified, verification_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param(
        'issssssssssis',
        $userId,
        $c['company_name'],
        $c['industry'],
        $c['district'],
        $c['province'],
        $c['address'],
        $c['website'],
        $c['phone'],
        $c['description'],
        $c['contact_person'],
        $c['contact_email'],
        $verified,
        $verificationStatus
    );
    $stmt->execute();
    $id = (int) $stmt->insert_id;
    $stmt->close();
    return $id;
}

function get_skill_id(mysqli $db, string $name): ?int
{
    $stmt = $db->prepare('SELECT id FROM skills WHERE name = ? LIMIT 1');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int) $row['id'] : null;
}

function seed_student_cv(mysqli $db, int $studentId, string $slug, string $title): int
{
    $filePath = 'seed_' . $slug . '.pdf';
    $fullPath = UPLOAD_CV_PATH . $filePath;
    $sample = dirname(__DIR__) . '/uploads/cvs/seed_sample.pdf';
    if (!is_dir(UPLOAD_CV_PATH)) {
        mkdir(UPLOAD_CV_PATH, 0755, true);
    }
    if (!file_exists($fullPath) && file_exists($sample)) {
        copy($sample, $fullPath);
    }

    $stmt = $db->prepare('SELECT id FROM student_cvs WHERE student_id = ? AND title = ? LIMIT 1');
    $stmt->bind_param('is', $studentId, $title);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        return (int) $row['id'];
    }

    $isPrimary = 1;
    $stmt = $db->prepare('INSERT INTO student_cvs (student_id, title, file_path, is_primary) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('issi', $studentId, $title, $filePath, $isPrimary);
    $stmt->execute();
    $id = (int) $stmt->insert_id;
    $stmt->close();
    return $id;
}

function clear_student_extras(mysqli $db, int $studentId): void
{
    foreach (['education', 'certifications', 'projects', 'student_skills'] as $table) {
        $stmt = $db->prepare("DELETE FROM {$table} WHERE student_id = ?");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $stmt->close();
    }
}

function seed_internship(mysqli $db, int $companyId, array $job, array $skillNames): int
{
    $stmt = $db->prepare('SELECT id FROM internships WHERE company_id = ? AND title = ? LIMIT 1');
    $stmt->bind_param('is', $companyId, $job['title']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $internshipId = (int) $row['id'];
        $stmt = $db->prepare(
            'UPDATE internships SET category=?, industry=?, location=?, district=?, province=?, work_type=?, stipend=?, duration_months=?, vacancies=?, responsibilities=?, requirements=?, benefits=?, contact_email=?, status=?, application_deadline=? WHERE id=?'
        );
        $stmt->bind_param(
            'ssssssdiissssssi',
            $job['category'],
            $job['industry'],
            $job['location'],
            $job['district'],
            $job['province'],
            $job['work_type'],
            $job['stipend'],
            $job['duration_months'],
            $job['vacancies'],
            $job['responsibilities'],
            $job['requirements'],
            $job['benefits'],
            $job['contact_email'],
            $job['status'],
            $job['application_deadline'],
            $internshipId
        );
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $db->prepare(
            'INSERT INTO internships (company_id, title, category, industry, location, district, province, work_type, stipend, duration_months, vacancies, responsibilities, requirements, benefits, contact_email, status, application_deadline)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'isssssssdiissssss',
            $companyId,
            $job['title'],
            $job['category'],
            $job['industry'],
            $job['location'],
            $job['district'],
            $job['province'],
            $job['work_type'],
            $job['stipend'],
            $job['duration_months'],
            $job['vacancies'],
            $job['responsibilities'],
            $job['requirements'],
            $job['benefits'],
            $job['contact_email'],
            $job['status'],
            $job['application_deadline']
        );
        $stmt->execute();
        $internshipId = (int) $stmt->insert_id;
        $stmt->close();
    }

    $stmt = $db->prepare('DELETE FROM internship_skills WHERE internship_id = ?');
    $stmt->bind_param('i', $internshipId);
    $stmt->execute();
    $stmt->close();

    foreach ($skillNames as $skillName) {
        $skillId = get_skill_id($db, $skillName);
        if (!$skillId) {
            continue;
        }
        $stmt = $db->prepare('INSERT IGNORE INTO internship_skills (internship_id, skill_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $internshipId, $skillId);
        $stmt->execute();
        $stmt->close();
    }

    return $internshipId;
}

function seed_application(mysqli $db, int $studentId, int $internshipId, int $cvId, string $status, string $coverLetter): void
{
    $stmt = $db->prepare('SELECT id FROM applications WHERE student_id = ? AND internship_id = ? LIMIT 1');
    $stmt->bind_param('ii', $studentId, $internshipId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $stmt = $db->prepare('UPDATE applications SET cv_id=?, cover_letter=?, status=? WHERE id=?');
        $stmt->bind_param('issi', $cvId, $coverLetter, $status, $row['id']);
        $stmt->execute();
        $stmt->close();
        return;
    }

    $stmt = $db->prepare('INSERT INTO applications (student_id, internship_id, cv_id, cover_letter, status) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('iiiss', $studentId, $internshipId, $cvId, $coverLetter, $status);
    $stmt->execute();
    $stmt->close();
}

function seed_favorite(mysqli $db, int $studentId, int $internshipId): void
{
    $stmt = $db->prepare('INSERT IGNORE INTO favorites (student_id, internship_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $studentId, $internshipId);
    $stmt->execute();
    $stmt->close();
}

function seed_notification(mysqli $db, int $userId, string $title, string $message, string $type = 'info'): void
{
    $stmt = $db->prepare('SELECT id FROM notifications WHERE user_id = ? AND title = ? LIMIT 1');
    $stmt->bind_param('is', $userId, $title);
    $stmt->execute();
    if ($stmt->get_result()->fetch_assoc()) {
        $stmt->close();
        return;
    }
    $stmt->close();

    $stmt = $db->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $userId, $title, $message, $type);
    $stmt->execute();
    $stmt->close();
}

// ── Students ────────────────────────────────────────────────────────────────

$students = [
    [
        'email' => 'amaya.perera@seed.internconnect.lk',
        'full_name' => 'Amaya Perera',
        'phone' => '0771234567',
        'district' => 'Colombo',
        'province' => 'Western',
        'university' => 'University of Colombo',
        'degree_program' => 'BSc (Hons) Information Technology',
        'gpa' => 3.72,
        'bio' => 'Final-year IT undergraduate passionate about web development and UI/UX. Seeking a software internship in Colombo.',
        'profile_completion' => 88,
        'slug' => 'amaya',
        'education' => [
            ['University of Colombo', 'BSc (Hons) IT', 'Information Technology', 2022, 2026, 3.72, 'Dean\'s List 2024'],
            ['Royal College Colombo', 'G.C.E. Advanced Level', 'Physical Science', 2019, 2021, null, '3A passes'],
        ],
        'certifications' => [
            ['Google IT Support Certificate', 'Google', '2024-06-01'],
            ['Meta Front-End Developer', 'Meta', '2025-01-15'],
        ],
        'projects' => [
            ['Campus Event Manager', 'Web app for university event registration built with PHP and MySQL.', 'PHP, MySQL, Bootstrap', 'https://github.com/example/campus-events', '2024-03-01', '2024-08-01'],
            ['Sri Lanka Weather Dashboard', 'Real-time weather dashboard using public APIs.', 'JavaScript, Chart.js', 'https://github.com/example/weather-sl', '2025-01-01', '2025-02-01'],
        ],
        'skills' => [
            'PHP' => 'advanced', 'JavaScript' => 'intermediate', 'React' => 'intermediate',
            'SQL' => 'advanced', 'HTML/CSS' => 'advanced', 'Teamwork' => 'advanced',
        ],
    ],
    [
        'email' => 'kavindu.silva@seed.internconnect.lk',
        'full_name' => 'Kavindu Silva',
        'phone' => '0719876543',
        'district' => 'Gampaha',
        'province' => 'Western',
        'university' => 'University of Moratuwa',
        'degree_program' => 'BSc Engineering (Computer Science)',
        'gpa' => 3.55,
        'bio' => 'Computer science undergraduate with experience in backend systems and databases. Interested in fintech internships.',
        'profile_completion' => 82,
        'slug' => 'kavindu',
        'education' => [
            ['University of Moratuwa', 'BSc Engineering', 'Computer Science & Engineering', 2021, 2026, 3.55, 'Active member of IEEE student branch'],
        ],
        'certifications' => [
            ['AWS Cloud Practitioner', 'Amazon Web Services', '2024-11-20'],
        ],
        'projects' => [
            ['Inventory API Service', 'REST API for small business inventory management.', 'Java, Spring Boot, PostgreSQL', null, '2024-06-01', '2024-12-01'],
        ],
        'skills' => [
            'Java' => 'advanced', 'Python' => 'intermediate', 'SQL' => 'advanced',
            'C#' => 'beginner', 'Project Management' => 'intermediate',
        ],
    ],
    [
        'email' => 'nethmi.fernando@seed.internconnect.lk',
        'full_name' => 'Nethmi Fernando',
        'phone' => '0765554433',
        'district' => 'Kandy',
        'province' => 'Central',
        'university' => 'NSBM Green University',
        'degree_program' => 'BSc (Hons) Business Management',
        'gpa' => 3.40,
        'bio' => 'Business management student with strong analytical skills. Looking for marketing and business analyst internships.',
        'profile_completion' => 75,
        'slug' => 'nethmi',
        'education' => [
            ['NSBM Green University', 'BSc (Hons) Business Management', 'Business Analytics', 2022, 2026, 3.40, 'Marketing society secretary'],
        ],
        'certifications' => [
            ['HubSpot Content Marketing', 'HubSpot Academy', '2024-09-10'],
            ['Excel for Business Analytics', 'Coursera', '2023-12-05'],
        ],
        'projects' => [
            ['SME Social Media Strategy', 'Marketing plan for a local bakery brand with KPI tracking.', 'Excel, Power BI, Canva', null, '2024-01-01', '2024-04-01'],
        ],
        'skills' => [
            'Excel' => 'advanced', 'Power BI' => 'intermediate', 'Presentation' => 'advanced',
            'Teamwork' => 'advanced', 'SQL' => 'beginner',
        ],
    ],
];

$studentIds = [];
foreach ($students as $s) {
    $userId = seed_user($mysqli, $s['email'], ROLE_STUDENT, STATUS_ACTIVE, 1);
    $studentId = seed_student_profile($mysqli, $userId, $s);
    $studentIds[$s['slug']] = ['user_id' => $userId, 'student_id' => $studentId];
    clear_student_extras($mysqli, $studentId);

    foreach ($s['education'] as $edu) {
        [$inst, $degree, $field, $start, $end, $gpa, $desc] = $edu;
        $stmt = $mysqli->prepare('INSERT INTO education (student_id, institution, degree, field_of_study, start_year, end_year, gpa, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issiisds', $studentId, $inst, $degree, $field, $start, $end, $gpa, $desc);
        $stmt->execute();
        $stmt->close();
    }

    foreach ($s['certifications'] as $cert) {
        [$title, $issuer, $date] = $cert;
        $stmt = $mysqli->prepare('INSERT INTO certifications (student_id, title, issuer, issue_date) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isss', $studentId, $title, $issuer, $date);
        $stmt->execute();
        $stmt->close();
    }

    foreach ($s['projects'] as $proj) {
        [$title, $desc, $tech, $url, $start, $end] = $proj;
        $stmt = $mysqli->prepare('INSERT INTO projects (student_id, title, description, technologies, project_url, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('issssss', $studentId, $title, $desc, $tech, $url, $start, $end);
        $stmt->execute();
        $stmt->close();
    }

    foreach ($s['skills'] as $skillName => $proficiency) {
        $skillId = get_skill_id($mysqli, $skillName);
        if (!$skillId) {
            continue;
        }
        $stmt = $mysqli->prepare('INSERT INTO student_skills (student_id, skill_id, proficiency) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $studentId, $skillId, $proficiency);
        $stmt->execute();
        $stmt->close();
    }

    $cvId = seed_student_cv($mysqli, $studentId, $s['slug'], 'Primary CV');
    $studentIds[$s['slug']]['cv_id'] = $cvId;
    $log("Student seeded: {$s['full_name']} ({$s['email']})");
}

// ── Companies ───────────────────────────────────────────────────────────────

$companies = [
    [
        'email' => 'techvista@seed.internconnect.lk',
        'company_name' => 'TechVista Lanka',
        'industry' => 'IT & Software',
        'district' => 'Colombo',
        'province' => 'Western',
        'address' => '42 Galle Road, Colombo 03',
        'website' => 'https://techvista.example.lk',
        'phone' => '0112345678',
        'description' => 'TechVista Lanka builds custom software and cloud solutions for Sri Lankan enterprises.',
        'contact_person' => 'Dilshan Wickramasinghe',
        'contact_email' => 'careers@techvista.example.lk',
        'slug' => 'techvista',
        'internships' => [
            [
                'title' => 'Frontend Developer Intern',
                'category' => 'IT & Software',
                'industry' => 'IT & Software',
                'location' => 'Colombo 03',
                'district' => 'Colombo',
                'province' => 'Western',
                'work_type' => 'Hybrid',
                'stipend' => 35000,
                'duration_months' => 6,
                'vacancies' => 2,
                'responsibilities' => "Build responsive UI components\nCollaborate with backend team\nWrite clean, tested JavaScript",
                'requirements' => "Undergraduate in IT or related field\nKnowledge of React and HTML/CSS\nGood communication skills",
                'benefits' => "Monthly stipend\nMentorship program\nCertificate on completion",
                'contact_email' => 'careers@techvista.example.lk',
                'status' => 'active',
                'application_deadline' => '2026-08-31',
                'skills' => ['JavaScript', 'React', 'HTML/CSS', 'Bootstrap'],
            ],
            [
                'title' => 'Backend Developer Intern',
                'category' => 'IT & Software',
                'industry' => 'IT & Software',
                'location' => 'Colombo 03',
                'district' => 'Colombo',
                'province' => 'Western',
                'work_type' => 'On-site',
                'stipend' => 40000,
                'duration_months' => 6,
                'vacancies' => 1,
                'responsibilities' => "Develop REST APIs\nOptimize database queries\nParticipate in code reviews",
                'requirements' => "Knowledge of PHP or Java\nSQL experience\nUnderstanding of MVC patterns",
                'benefits' => "Transport allowance\nLunch provided\nFull-time offer potential",
                'contact_email' => 'careers@techvista.example.lk',
                'status' => 'active',
                'application_deadline' => '2026-09-15',
                'skills' => ['PHP', 'SQL', 'Java'],
            ],
        ],
    ],
    [
        'email' => 'greenwave@seed.internconnect.lk',
        'company_name' => 'GreenWave Solutions',
        'industry' => 'Marketing',
        'district' => 'Kandy',
        'province' => 'Central',
        'address' => '15 Peradeniya Road, Kandy',
        'website' => 'https://greenwave.example.lk',
        'phone' => '0812223344',
        'description' => 'GreenWave Solutions is a digital marketing agency helping Sri Lankan brands grow online.',
        'contact_person' => 'Ishara Bandara',
        'contact_email' => 'hr@greenwave.example.lk',
        'slug' => 'greenwave',
        'internships' => [
            [
                'title' => 'Digital Marketing Intern',
                'category' => 'Marketing',
                'industry' => 'Marketing',
                'location' => 'Kandy',
                'district' => 'Kandy',
                'province' => 'Central',
                'work_type' => 'Hybrid',
                'stipend' => 25000,
                'duration_months' => 4,
                'vacancies' => 3,
                'responsibilities' => "Manage social media calendars\nCreate campaign reports\nSupport client presentations",
                'requirements' => "Marketing or business undergraduate\nStrong writing skills\nFamiliarity with analytics tools",
                'benefits' => "Flexible hours\nPortfolio-building projects\nReference letter",
                'contact_email' => 'hr@greenwave.example.lk',
                'status' => 'active',
                'application_deadline' => '2026-07-30',
                'skills' => ['Excel', 'Presentation', 'Teamwork'],
            ],
            [
                'title' => 'Business Analyst Intern',
                'category' => 'Business & Finance',
                'industry' => 'Marketing',
                'location' => 'Kandy',
                'district' => 'Kandy',
                'province' => 'Central',
                'work_type' => 'On-site',
                'stipend' => 28000,
                'duration_months' => 5,
                'vacancies' => 1,
                'responsibilities' => "Analyze campaign performance data\nPrepare dashboards for clients\nResearch market trends",
                'requirements' => "Business or statistics background\nExcel and Power BI skills\nDetail-oriented mindset",
                'benefits' => "Training workshops\nNetworking events\nStipend + travel",
                'contact_email' => 'hr@greenwave.example.lk',
                'status' => 'active',
                'application_deadline' => '2026-08-20',
                'skills' => ['Excel', 'Power BI', 'SQL'],
            ],
        ],
    ],
];

$companyIds = [];
$internshipMap = [];

foreach ($companies as $c) {
    $userId = seed_user($mysqli, $c['email'], ROLE_COMPANY, STATUS_ACTIVE, 1);
    $companyId = seed_company_profile($mysqli, $userId, $c);
    $companyIds[$c['slug']] = ['user_id' => $userId, 'company_id' => $companyId];
    $internshipMap[$c['slug']] = [];

    foreach ($c['internships'] as $job) {
        $skills = $job['skills'];
        unset($job['skills']);
        $internshipId = seed_internship($mysqli, $companyId, $job, $skills);
        $internshipMap[$c['slug']][$job['title']] = $internshipId;
    }

    $log("Company seeded: {$c['company_name']} ({$c['email']})");
}

// Activate existing ABC company account if present
$stmt = $mysqli->prepare('SELECT u.id, c.id AS company_id FROM users u JOIN companies c ON c.user_id = u.id WHERE u.email = ? LIMIT 1');
$abcEmail = 'nasroonmahii@gmail.com';
$stmt->bind_param('s', $abcEmail);
$stmt->execute();
$abc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($abc) {
    $active = STATUS_ACTIVE;
    $stmt = $mysqli->prepare('UPDATE users SET status = ?, email_verified = 1 WHERE id = ?');
    $stmt->bind_param('si', $active, $abc['id']);
    $stmt->execute();
    $stmt->close();

    $verified = 1;
    $approved = 'approved';
    $stmt = $mysqli->prepare('UPDATE companies SET verified = ?, verification_status = ? WHERE id = ?');
    $stmt->bind_param('isi', $verified, $approved, $abc['company_id']);
    $stmt->execute();
    $stmt->close();

    $companyIds['abc'] = ['user_id' => (int) $abc['id'], 'company_id' => (int) $abc['company_id']];
    $log('Updated existing ABC company: verified and active');

    $stmt = $mysqli->prepare('SELECT id, title FROM internships WHERE company_id = ? AND status = ? ORDER BY id');
    $activeStatus = 'active';
    $stmt->bind_param('is', $abc['company_id'], $activeStatus);
    $stmt->execute();
    $abcInternships = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $internshipMap['abc'] = [];
    foreach ($abcInternships as $job) {
        $internshipMap['abc'][$job['title'] . '_' . $job['id']] = (int) $job['id'];
    }
}

// Enrich existing student account (if present) with a CV and new applications
$stmt = $mysqli->prepare(
    'SELECT s.id AS student_id, u.id AS user_id FROM students s JOIN users u ON u.id = s.user_id WHERE u.email = ? LIMIT 1'
);
$existingStudentEmail = 'nasroonmahi@gmail.com';
$stmt->bind_param('s', $existingStudentEmail);
$stmt->execute();
$existingStudent = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existingStudent) {
    $existingStudentId = (int) $existingStudent['student_id'];
    $existingUserId = (int) $existingStudent['user_id'];
    $existingCvId = seed_student_cv($mysqli, $existingStudentId, 'nasroon', 'My CV');

    if (isset($internshipMap['techvista']['Frontend Developer Intern'])) {
        seed_application(
            $mysqli,
            $existingStudentId,
            $internshipMap['techvista']['Frontend Developer Intern'],
            $existingCvId,
            'shortlisted',
            'I would love to contribute to TechVista as a frontend developer intern.'
        );
    }
    if (isset($internshipMap['greenwave']['Digital Marketing Intern'])) {
        seed_application(
            $mysqli,
            $existingStudentId,
            $internshipMap['greenwave']['Digital Marketing Intern'],
            $existingCvId,
            'pending',
            'Interested in gaining digital marketing experience with GreenWave Solutions.'
        );
        seed_favorite($mysqli, $existingStudentId, $internshipMap['greenwave']['Digital Marketing Intern']);
    }

    $verified = 1;
    $stmt = $mysqli->prepare('UPDATE users SET email_verified = ? WHERE id = ?');
    $stmt->bind_param('ii', $verified, $existingUserId);
    $stmt->execute();
    $stmt->close();

    $log('Updated existing student account with demo applications');
}

// ── Applications & favorites ────────────────────────────────────────────────

$apps = [
    ['amaya', 'techvista', 'Frontend Developer Intern', 'shortlisted', 'I am excited to apply my React skills to real client projects at TechVista.'],
    ['amaya', 'techvista', 'Backend Developer Intern', 'pending', 'I have strong PHP and SQL experience from university projects.'],
    ['kavindu', 'techvista', 'Backend Developer Intern', 'interview', 'My Java API project demonstrates my backend development ability.'],
    ['kavindu', 'greenwave', 'Business Analyst Intern', 'pending', 'I enjoy working with data and would love to support your analytics team.'],
    ['nethmi', 'greenwave', 'Digital Marketing Intern', 'accepted', 'I have hands-on experience creating social media strategies for local brands.'],
    ['nethmi', 'greenwave', 'Business Analyst Intern', 'rejected', 'Thank you for considering my application for this role.'],
];

// Demo applications to your ABC company (visible when logged in as nasroonmahii@gmail.com)
if (isset($companyIds['abc'], $internshipMap['abc']) && count($internshipMap['abc']) >= 2) {
    $abcJobs = array_values($internshipMap['abc']);
    $apps[] = ['amaya', $abcJobs[0], 'shortlisted', 'I am keen to join ABC as a software engineering intern.'];
    if (isset($abcJobs[2])) {
        $apps[] = ['kavindu', $abcJobs[2], 'interview', 'My backend and QA skills align well with this QA Engineer role.'];
    } elseif (isset($abcJobs[1])) {
        $apps[] = ['kavindu', $abcJobs[1], 'interview', 'My backend and QA skills align well with this role.'];
    }
    if (isset($abcJobs[1])) {
        $apps[] = ['nethmi', $abcJobs[1], 'pending', 'I am interested in exploring software opportunities at ABC.'];
    }
}

foreach ($apps as $app) {
    if (count($app) === 5) {
        [$studentSlug, $companySlug, $jobTitle, $status, $cover] = $app;
        if (!isset($studentIds[$studentSlug], $internshipMap[$companySlug][$jobTitle])) {
            continue;
        }
        $internshipId = $internshipMap[$companySlug][$jobTitle];
    } else {
        [$studentSlug, $internshipId, $status, $cover] = $app;
        if (!isset($studentIds[$studentSlug])) {
            continue;
        }
    }
    seed_application(
        $mysqli,
        $studentIds[$studentSlug]['student_id'],
        $internshipId,
        $studentIds[$studentSlug]['cv_id'],
        $status,
        $cover
    );
}

seed_favorite($mysqli, $studentIds['amaya']['student_id'], $internshipMap['techvista']['Frontend Developer Intern']);
seed_favorite($mysqli, $studentIds['kavindu']['student_id'], $internshipMap['techvista']['Backend Developer Intern']);
seed_favorite($mysqli, $studentIds['nethmi']['student_id'], $internshipMap['greenwave']['Digital Marketing Intern']);

$log('Applications and favorites seeded');

// ── Notifications ─────────────────────────────────────────────────────────────

seed_notification($mysqli, $studentIds['amaya']['user_id'], 'Application shortlisted', 'TechVista Lanka shortlisted you for Frontend Developer Intern.', 'success');
seed_notification($mysqli, $studentIds['kavindu']['user_id'], 'Interview scheduled', 'You have been invited to interview for Backend Developer Intern at TechVista Lanka.', 'info');
seed_notification($mysqli, $studentIds['nethmi']['user_id'], 'Offer received', 'Congratulations! GreenWave Solutions accepted your application for Digital Marketing Intern.', 'success');
seed_notification($mysqli, $companyIds['techvista']['user_id'], 'New application', 'Kavindu Silva applied for Backend Developer Intern.', 'info');
seed_notification($mysqli, $companyIds['greenwave']['user_id'], 'New application', 'Nethmi Fernando applied for Digital Marketing Intern.', 'info');
if (isset($companyIds['abc'])) {
    seed_notification($mysqli, $companyIds['abc']['user_id'], 'New applications', 'Demo students Amaya, Kavindu and Nethmi applied to your internships.', 'info');
}
if (isset($existingUserId)) {
    seed_notification($mysqli, $existingUserId, 'Application update', 'TechVista Lanka shortlisted you for Frontend Developer Intern.', 'success');
}

$log('Notifications seeded');

// ── Summary ─────────────────────────────────────────────────────────────────

$counts = [
    'students' => $mysqli->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'],
    'companies' => $mysqli->query("SELECT COUNT(*) c FROM companies")->fetch_assoc()['c'],
    'internships' => $mysqli->query("SELECT COUNT(*) c FROM internships WHERE status='active'")->fetch_assoc()['c'],
    'applications' => $mysqli->query("SELECT COUNT(*) c FROM applications")->fetch_assoc()['c'],
];

$log("Done — {$counts['students']} students, {$counts['companies']} companies, {$counts['internships']} active internships, {$counts['applications']} applications");

if (!$isCli) {
    echo '</ul>';
    echo '<p><strong>Password for all seeded accounts:</strong> ' . htmlspecialchars(SEED_PASSWORD) . '</p>';
    echo '<h2>Student logins</h2><ul>';
    foreach ($students as $s) {
        echo '<li>' . htmlspecialchars($s['email']) . ' — ' . htmlspecialchars($s['full_name']) . '</li>';
    }
    echo '</ul><h2>Company logins</h2><ul>';
    foreach ($companies as $c) {
        echo '<li>' . htmlspecialchars($c['email']) . ' — ' . htmlspecialchars($c['company_name']) . '</li>';
    }
    echo '</ul><p><a href="../auth/login.php">Go to Login</a></p></body></html>';
}
