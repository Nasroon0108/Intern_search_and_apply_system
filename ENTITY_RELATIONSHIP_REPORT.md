# InternConnect Sri Lanka — Entity & Relationship Report

**Project:** Intern Search and Apply System  
**Database:** `internconnect_sl` (MySQL / InnoDB)  
**Schema source:** `sql/schema.sql`  
**Document purpose:** Reference for ER diagrams, class diagrams, use-case diagrams, and DFDs in the project report.

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [High-Level Domain Model](#2-high-level-domain-model)
3. [Complete Entity List](#3-complete-entity-list)
4. [Relationship Summary](#4-relationship-summary)
5. [ER Diagram (Mermaid)](#5-er-diagram-mermaid)
6. [Actor–Entity Access Map](#6-actorentity-access-map)
7. [Business Rules & Constraints](#7-business-rules--constraints)
8. [File Upload Entities](#8-file-upload-entities)
9. [Application Pages ↔ Entities](#9-application-pages--entities)
10. [Suggested Diagrams for Project Report](#10-suggested-diagrams-for-project-report)
11. [Entity Count Summary](#11-entity-count-summary)
12. [Enumeration Reference](#12-enumeration-reference)

---

## 1. System Overview

InternConnect is a web-based internship portal connecting **students**, **companies**, and **administrators**. It supports internship search, application submission, company verification, and platform administration.

### 1.1 Actors

| Actor | Profile Table | Main Activities |
|--------|----------------|-----------------|
| **Student** | `students` | Build profile, search internships, apply, track applications, save favorites |
| **Company** | `companies` | Post/manage internships, review applications, update application status |
| **Admin** | `admins` | Verify companies, moderate internships/users, manage announcements and logs |

### 1.2 Central Authentication

All actors authenticate through the **`users`** table using email and password. Each role has a dedicated profile table linked by `user_id` (1:1 relationship).

### 1.3 Technology Stack

| Layer | Technology |
|-------|------------|
| Frontend | HTML, CSS, Bootstrap 5, JavaScript |
| Backend | PHP |
| Database | MySQL (InnoDB) |
| Server | XAMPP (Apache) |

---

## 2. High-Level Domain Model

The system is organized into six logical modules:

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Authentication │     │  Student Profile │     │ Company Profile │
│  users, tokens  │     │  students + CVs  │     │  companies      │
└────────┬────────┘     │  education, etc. │     └────────┬────────┘
         │              └────────┬─────────┘              │
         │                       │                        │
         └───────────────────────┼────────────────────────┘
                                 │
                    ┌────────────▼────────────┐
                    │   Internship & Skills   │
                    │ internships, skills     │
                    └────────────┬────────────┘
                                 │
              ┌──────────────────┼──────────────────┐
              │                  │                  │
    ┌─────────▼────────┐ ┌──────▼──────┐ ┌────────▼────────┐
    │   Applications   │ │  Favorites  │ │ Communication   │
    │  applications    │ │  favorites  │ │ notifications,  │
    │  interviews      │ │             │ │ messages          │
    └──────────────────┘ └─────────────┘ └─────────────────┘
                                 │
                    ┌────────────▼────────────┐
                    │   Admin & Platform    │
                    │ admins, announcements │
                    │ admin_logs            │
                    └───────────────────────┘
```

---

## 3. Complete Entity List

**Total tables: 22**

---

### 3.1 Core Authentication (3 entities)

#### Entity: `users` (Central — superclass for all roles)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | Unique user identifier |
| email | VARCHAR(255) | UNIQUE | Login email address |
| password_hash | VARCHAR(255) | | Bcrypt/hashed password |
| role | ENUM | | `student`, `company`, `admin` |
| status | ENUM | | `active`, `pending`, `blocked` |
| email_verified | TINYINT(1) | | 0 = not verified, 1 = verified |
| last_login | DATETIME | | Last successful login |
| created_at | DATETIME | | Record creation time |
| updated_at | DATETIME | | Last update time |

**Indexes:** `idx_users_role`, `idx_users_status`

---

#### Entity: `password_resets`

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| user_id | INT UNSIGNED | FK → users | User requesting reset |
| token | VARCHAR(64) | UNIQUE | Secure reset token |
| expires_at | DATETIME | | Token expiration |
| used | TINYINT(1) | | Whether token was consumed |
| created_at | DATETIME | | |

---

#### Entity: `email_verifications`

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| user_id | INT UNSIGNED | FK → users | User being verified |
| token | VARCHAR(64) | UNIQUE | Verification token |
| expires_at | DATETIME | | Token expiration |
| verified_at | DATETIME | NULL | When email was verified |
| created_at | DATETIME | | |

---

### 3.2 Student Profile (6 entities)

#### Entity: `students` (1:1 with `users` where role = student)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| user_id | INT UNSIGNED | FK, UNIQUE → users | Link to login account |
| full_name | VARCHAR(150) | | Student full name |
| phone | VARCHAR(20) | NULL | Contact number |
| district | VARCHAR(80) | NULL | Sri Lanka district |
| province | VARCHAR(80) | NULL | Sri Lanka province |
| university | VARCHAR(200) | NULL | University name |
| degree_program | VARCHAR(200) | NULL | Degree program |
| gpa | DECIMAL(3,2) | NULL | Grade point average |
| profile_photo | VARCHAR(255) | NULL | Photo file path |
| bio | TEXT | NULL | Personal biography |
| profile_completion | TINYINT UNSIGNED | | 0–100% completion score |
| created_at | DATETIME | | |
| updated_at | DATETIME | | |

**Indexes:** `idx_students_district`, `idx_students_university`

---

#### Entity: `student_cvs` (1:N — student has many CVs)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| student_id | INT UNSIGNED | FK → students | Owner student |
| title | VARCHAR(100) | | CV display name |
| file_path | VARCHAR(255) | | PDF file path |
| is_primary | TINYINT(1) | | Primary CV flag |
| uploaded_at | DATETIME | | Upload timestamp |

---

#### Entity: `education` (1:N)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| student_id | INT UNSIGNED | FK → students | |
| institution | VARCHAR(200) | | School/university name |
| degree | VARCHAR(150) | | Degree title |
| field_of_study | VARCHAR(150) | NULL | Major/field |
| start_year | YEAR | NULL | Start year |
| end_year | YEAR | NULL | End year |
| gpa | DECIMAL(3,2) | NULL | GPA for this record |
| description | TEXT | NULL | Additional details |

---

#### Entity: `certifications` (1:N)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| student_id | INT UNSIGNED | FK → students | |
| title | VARCHAR(200) | | Certification name |
| issuer | VARCHAR(200) | NULL | Issuing organization |
| issue_date | DATE | NULL | Date issued |
| credential_url | VARCHAR(500) | NULL | Verification URL |

---

#### Entity: `projects` (1:N)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| student_id | INT UNSIGNED | FK → students | |
| title | VARCHAR(200) | | Project name |
| description | TEXT | NULL | Project description |
| technologies | VARCHAR(300) | NULL | Tech stack used |
| project_url | VARCHAR(500) | NULL | Live/demo URL |
| start_date | DATE | NULL | |
| end_date | DATE | NULL | |

---

#### Entity: `student_skills` (M:N junction — see Section 3.3)

---

### 3.3 Skills (3 entities)

#### Entity: `skill_categories`

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| name | VARCHAR(100) | UNIQUE | Category name |
| type | ENUM | | `technical` or `soft` |

**Seed data:** Programming, Web Development, Data & Analytics, Communication, Leadership

---

#### Entity: `skills`

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| category_id | INT UNSIGNED | FK → skill_categories | Parent category |
| name | VARCHAR(100) | UNIQUE | Skill name |

**Seed data:** Python, Java, PHP, C#, HTML/CSS, JavaScript, React, Bootstrap, SQL, Excel, Power BI, Teamwork, Presentation, Project Management

---

#### Entity: `student_skills` (M:N junction: Student ↔ Skill)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| student_id | INT UNSIGNED | PK, FK → students | |
| skill_id | INT UNSIGNED | PK, FK → skills | |
| proficiency | ENUM | | `beginner`, `intermediate`, `advanced` |

---

### 3.4 Company Profile (1 entity)

#### Entity: `companies` (1:1 with `users` where role = company)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| user_id | INT UNSIGNED | FK, UNIQUE → users | Link to login account |
| company_name | VARCHAR(200) | | Official company name |
| industry | VARCHAR(100) | NULL | Industry sector |
| district | VARCHAR(80) | NULL | |
| province | VARCHAR(80) | NULL | |
| address | TEXT | NULL | Physical address |
| website | VARCHAR(300) | NULL | Company website |
| phone | VARCHAR(20) | NULL | Contact phone |
| description | TEXT | NULL | Company overview |
| logo | VARCHAR(255) | NULL | Logo file path |
| contact_person | VARCHAR(150) | NULL | HR/contact name |
| contact_email | VARCHAR(255) | NULL | Contact email |
| verified | TINYINT(1) | | 0/1 verification flag |
| verification_status | ENUM | | `pending`, `approved`, `rejected` |
| created_at | DATETIME | | |
| updated_at | DATETIME | | |

**Indexes:** `idx_companies_verified`, `idx_companies_district`

---

### 3.5 Internships & Applications (4 entities)

#### Entity: `internships` (1:N — company posts many internships)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| company_id | INT UNSIGNED | FK → companies | Posting company |
| title | VARCHAR(200) | | Internship title |
| category | VARCHAR(100) | NULL | Job category |
| industry | VARCHAR(100) | NULL | |
| location | VARCHAR(150) | NULL | General location |
| district | VARCHAR(80) | NULL | |
| province | VARCHAR(80) | NULL | |
| work_type | ENUM | | `On-site`, `Remote`, `Hybrid` |
| stipend | DECIMAL(10,2) | NULL | Monthly stipend (Rs.) |
| stipend_note | VARCHAR(200) | NULL | Stipend notes |
| duration_months | TINYINT UNSIGNED | NULL | Duration in months |
| vacancies | SMALLINT UNSIGNED | | Number of positions |
| responsibilities | TEXT | NULL | Role responsibilities |
| requirements | TEXT | NULL | Candidate requirements |
| benefits | TEXT | NULL | Internship benefits |
| contact_email | VARCHAR(255) | NULL | |
| contact_phone | VARCHAR(20) | NULL | |
| application_deadline | DATE | NULL | Last date to apply |
| status | ENUM | | `draft`, `pending`, `active`, `closed`, `rejected` |
| views_count | INT UNSIGNED | | Page view counter |
| created_at | DATETIME | | |
| updated_at | DATETIME | | |

**Indexes:** `idx_internships_status`, `idx_internships_district`, `idx_internships_deadline`  
**Full-text index:** `title`, `responsibilities`, `requirements`

---

#### Entity: `internship_skills` (M:N junction: Internship ↔ Skill)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| internship_id | INT UNSIGNED | PK, FK → internships | |
| skill_id | INT UNSIGNED | PK, FK → skills | |

---

#### Entity: `applications` (M:N between Student and Internship, with attributes)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| student_id | INT UNSIGNED | FK → students | Applicant |
| internship_id | INT UNSIGNED | FK → internships | Target internship |
| cv_id | INT UNSIGNED | FK, NULL → student_cvs | CV submitted |
| cover_letter | TEXT | NULL | Cover letter text |
| cover_letter_file | VARCHAR(255) | NULL | Cover letter file |
| status | ENUM | | Application status |
| applied_at | DATETIME | | Submission time |
| updated_at | DATETIME | | Last status change |
| company_notes | TEXT | NULL | Internal company notes |

**Unique constraint:** `(student_id, internship_id)` — one application per student per internship  
**Index:** `idx_applications_status`

**Application status lifecycle:**

```
                    ┌─────────────┐
                    │   pending   │
                    └──────┬──────┘
           ┌───────────────┼───────────────┐
           ▼               ▼               ▼
    ┌────────────┐  ┌────────────┐  ┌────────────┐
    │ withdrawn  │  │shortlisted │  │  rejected  │
    │ (student)  │  └─────┬──────┘  └────────────┘
    └────────────┘        │
                          ▼
                   ┌────────────┐
                   │ interview  │
                   └─────┬──────┘
                         ▼
                   ┌────────────┐
                   │  accepted  │
                   └────────────┘
```

---

#### Entity: `favorites` (M:N — saved/bookmarked internships)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| student_id | INT UNSIGNED | PK, FK → students | |
| internship_id | INT UNSIGNED | PK, FK → internships | |
| saved_at | DATETIME | | When saved |

---

### 3.6 Communication (3 entities)

#### Entity: `notifications`

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| user_id | INT UNSIGNED | FK → users | Recipient |
| title | VARCHAR(200) | | Notification title |
| message | TEXT | | Notification body |
| type | VARCHAR(50) | | e.g. info, success, warning |
| link | VARCHAR(500) | NULL | Optional action URL |
| is_read | TINYINT(1) | | Read status |
| created_at | DATETIME | | |

**Index:** `idx_notifications_user_read (user_id, is_read)`

---

#### Entity: `messages`

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| sender_id | INT UNSIGNED | FK → users | Message sender |
| receiver_id | INT UNSIGNED | FK → users | Message receiver |
| application_id | INT UNSIGNED | FK, NULL → applications | Optional context |
| subject | VARCHAR(200) | NULL | Message subject |
| body | TEXT | | Message content |
| is_read | TINYINT(1) | | Read status |
| sent_at | DATETIME | | Send timestamp |

---

#### Entity: `interviews`

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| application_id | INT UNSIGNED | FK → applications | Related application |
| scheduled_at | DATETIME | | Interview date/time |
| location | VARCHAR(300) | NULL | Physical location |
| meeting_link | VARCHAR(500) | NULL | Online meeting URL |
| notes | TEXT | NULL | Interview notes |
| status | ENUM | | `scheduled`, `completed`, `cancelled`, `no_show` |
| created_at | DATETIME | | |

---

### 3.7 Admin & Platform (3 entities)

#### Entity: `admins` (1:1 with `users` where role = admin)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| user_id | INT UNSIGNED | FK, UNIQUE → users | |
| full_name | VARCHAR(150) | | Admin display name |

---

#### Entity: `announcements`

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| admin_id | INT UNSIGNED | FK → admins | Publishing admin |
| title | VARCHAR(200) | | Announcement title |
| content | TEXT | | Announcement body |
| target_role | ENUM | | `all`, `student`, `company` |
| is_active | TINYINT(1) | | Visibility flag |
| created_at | DATETIME | | |

---

#### Entity: `admin_logs` (Audit trail)

| Attribute | Data Type | Key | Description |
|-----------|-----------|-----|-------------|
| id | INT UNSIGNED | PK | |
| admin_id | INT UNSIGNED | FK → admins | Acting admin |
| action | VARCHAR(100) | | Action performed |
| target_type | VARCHAR(50) | NULL | Entity type affected |
| target_id | INT UNSIGNED | NULL | Entity ID affected |
| details | TEXT | NULL | Additional log details |
| created_at | DATETIME | | |

---

## 4. Relationship Summary

| # | Parent Entity | Child Entity | Cardinality | FK Column | On Delete |
|---|---------------|--------------|-------------|-----------|-----------|
| 1 | users | students | 1 : 1 | students.user_id | CASCADE |
| 2 | users | companies | 1 : 1 | companies.user_id | CASCADE |
| 3 | users | admins | 1 : 1 | admins.user_id | CASCADE |
| 4 | users | password_resets | 1 : N | password_resets.user_id | CASCADE |
| 5 | users | email_verifications | 1 : N | email_verifications.user_id | CASCADE |
| 6 | users | notifications | 1 : N | notifications.user_id | CASCADE |
| 7 | users | messages (sender) | 1 : N | messages.sender_id | CASCADE |
| 8 | users | messages (receiver) | 1 : N | messages.receiver_id | CASCADE |
| 9 | students | student_cvs | 1 : N | student_cvs.student_id | CASCADE |
| 10 | students | education | 1 : N | education.student_id | CASCADE |
| 11 | students | certifications | 1 : N | certifications.student_id | CASCADE |
| 12 | students | projects | 1 : N | projects.student_id | CASCADE |
| 13 | students | applications | 1 : N | applications.student_id | CASCADE |
| 14 | students | favorites | 1 : N | favorites.student_id | CASCADE |
| 15 | students | student_skills | M : N | student_skills (junction) | CASCADE |
| 16 | skill_categories | skills | 1 : N | skills.category_id | SET NULL |
| 17 | skills | student_skills | 1 : N | student_skills.skill_id | CASCADE |
| 18 | companies | internships | 1 : N | internships.company_id | CASCADE |
| 19 | internships | internship_skills | M : N | internship_skills (junction) | CASCADE |
| 20 | internships | applications | 1 : N | applications.internship_id | CASCADE |
| 21 | internships | favorites | 1 : N | favorites.internship_id | CASCADE |
| 22 | student_cvs | applications | 1 : N | applications.cv_id | SET NULL |
| 23 | applications | interviews | 1 : N | interviews.application_id | CASCADE |
| 24 | applications | messages | 1 : N | messages.application_id | SET NULL |
| 25 | admins | announcements | 1 : N | announcements.admin_id | CASCADE |
| 26 | admins | admin_logs | 1 : N | admin_logs.admin_id | CASCADE |

### 4.1 Junction (Associative) Entities

| Junction Table | Connects | Additional Attributes |
|----------------|----------|---------------------|
| `student_skills` | students ↔ skills | proficiency |
| `internship_skills` | internships ↔ skills | — |
| `favorites` | students ↔ internships | saved_at |
| `applications` | students ↔ internships | cv_id, cover_letter, status, etc. |

---

## 5. ER Diagram (Mermaid)

Use this in [Mermaid Live Editor](https://mermaid.live) or any tool that supports Mermaid syntax.

```mermaid
erDiagram
    users ||--o| students : "has profile"
    users ||--o| companies : "has profile"
    users ||--o| admins : "has profile"
    users ||--o{ password_resets : "requests"
    users ||--o{ email_verifications : "verifies"
    users ||--o{ notifications : "receives"
    users ||--o{ messages : "sends"
    users ||--o{ messages : "receives"

    students ||--o{ student_cvs : "uploads"
    students ||--o{ education : "has"
    students ||--o{ certifications : "has"
    students ||--o{ projects : "has"
    students ||--o{ student_skills : "has"
    students ||--o{ applications : "submits"
    students ||--o{ favorites : "saves"

    skill_categories ||--o{ skills : "contains"
    skills ||--o{ student_skills : "on student"
    skills ||--o{ internship_skills : "required for"

    companies ||--o{ internships : "posts"
    internships ||--o{ internship_skills : "requires"
    internships ||--o{ applications : "receives"
    internships ||--o{ favorites : "saved in"

    applications }o--|| student_cvs : "uses CV"
    applications ||--o{ interviews : "schedules"
    applications ||--o{ messages : "context"

    admins ||--o{ announcements : "publishes"
    admins ||--o{ admin_logs : "records"

    users {
        int id PK
        string email UK
        string password_hash
        enum role
        enum status
        tinyint email_verified
        datetime created_at
    }

    students {
        int id PK
        int user_id FK_UK
        string full_name
        string university
        decimal gpa
        tinyint profile_completion
    }

    companies {
        int id PK
        int user_id FK_UK
        string company_name
        tinyint verified
        enum verification_status
    }

    internships {
        int id PK
        int company_id FK
        string title
        enum work_type
        enum status
        date application_deadline
        int views_count
    }

    applications {
        int id PK
        int student_id FK
        int internship_id FK
        int cv_id FK
        enum status
        datetime applied_at
    }

    skills {
        int id PK
        int category_id FK
        string name UK
    }

    admins {
        int id PK
        int user_id FK_UK
        string full_name
    }
```

---

## 6. Actor–Entity Access Map

| Entity / Feature | Student | Company | Admin | Public (Guest) |
|------------------|:-------:|:-------:|:-----:|:--------------:|
| Register / Login | ✓ | ✓ | ✓ | ✓ |
| `students` profile | CRUD own | — | View | — |
| `student_cvs` | CRUD own | — | — | — |
| `education` | CRUD own | — | — | — |
| `certifications` | CRUD own | — | — | — |
| `projects` | CRUD own | — | — | — |
| `student_skills` | CRUD own | — | — | — |
| `companies` profile | — | CRUD own | Verify/Manage | — |
| `internships` browse | Read active | CRUD own | Moderate all | Read active |
| `internship_skills` | — | CRUD own | — | — |
| `applications` | Create, view, withdraw own | View/update status | View all | — |
| `favorites` | CRUD own | — | — | — |
| `notifications` | Read own | Read own | — | — |
| `messages` | Send/receive | Send/receive | — | — |
| `interviews` | View own | Schedule/manage | — | — |
| `announcements` | Read | Read | CRUD | — |
| `admin_logs` | — | — | Read/Write | — |
| `users` management | — | — | CRUD | — |

---

## 7. Business Rules & Constraints

1. **Unique email** — Each `users.email` must be unique across the system.
2. **Role-specific profile** — A user has exactly one profile table based on `role` (student, company, or admin).
3. **One application per internship** — A student can apply to the same internship only once (`UNIQUE(student_id, internship_id)`).
4. **Company verification** — New companies register with `users.status = pending` and `companies.verification_status = pending`. Admin must approve before full platform access.
5. **Internship moderation** — New internships default to `status = pending`. Admin activates or rejects them.
6. **CV on application** — When applying, student optionally attaches a `student_cvs` record via `applications.cv_id`.
7. **Shared skill taxonomy** — Both students and internships reference the same master `skills` list for matching.
8. **Cascade deletion** — Deleting a `users` record removes linked profile, applications, notifications, and related data.
9. **Profile completion** — `students.profile_completion` (0–100%) is calculated from completed profile sections.
10. **Primary CV** — Students can mark one CV as primary (`student_cvs.is_primary`).
11. **Email verification** — Users must verify email (`email_verified = 1`) before full access.
12. **Password reset tokens** — Tokens in `password_resets` expire and can only be used once.

---

## 8. File Upload Entities

| Entity | Attribute | Storage Directory | Allowed Format |
|--------|-----------|-------------------|----------------|
| `students` | profile_photo | `uploads/photos/` | JPG, PNG, WebP |
| `student_cvs` | file_path | `uploads/cvs/` | PDF |
| `companies` | logo | `uploads/logos/` | JPG, PNG, WebP |
| `applications` | cover_letter_file | uploads (optional) | PDF |

---

## 9. Application Pages ↔ Entities

| PHP Page / Module | Primary Entities |
|-------------------|------------------|
| `auth/login.php` | users |
| `auth/register-student.php` | users, students, email_verifications |
| `auth/register-company.php` | users, companies, email_verifications |
| `auth/forgot-password.php` | users, password_resets |
| `auth/verify-email.php` | users, email_verifications |
| `student/dashboard.php` | students, applications, internships |
| `student/profile.php` | students, users |
| `student/skills.php` | student_skills, skills, skill_categories |
| `student/cvs.php` | student_cvs |
| `student/education.php` | education |
| `student/certifications.php` | certifications |
| `student/projects.php` | projects |
| `student/applications.php` | applications, internships, companies |
| `student/saved.php` | favorites, internships, companies |
| `internships.php` (Explore) | internships, companies, favorites |
| `internship-detail.php` | internships, applications, internship_skills |
| `company/dashboard.php` | companies, internships, applications |
| `company/profile.php` | companies |
| `company/post-internship.php` | internships, internship_skills |
| `company/internships.php` | internships |
| `company/applications.php` | applications, students, internships |
| `admin/dashboard.php` | users, students, companies, internships, applications |
| `admin/users.php` | users, students, companies |
| `admin/companies.php` | companies, users |
| `admin/internships.php` | internships, companies |
| `admin/company-detail.php` | companies, users, internships |
| `admin/internship-detail.php` | internships, applications, internship_skills |

---

## 10. Suggested Diagrams for Project Report

| Diagram Type | Recommended Content | Section Reference |
|--------------|---------------------|-------------------|
| **Context Diagram (DFD Level 0)** | Student, Company, Admin → InternConnect System → Database | Section 1 |
| **DFD Level 1** | Sub-processes: Authentication, Profile, Search, Apply, Manage, Admin | Section 2 |
| **ER Diagram** | All 22 tables with relationships | Section 4, 5 |
| **Extended ER Diagram** | ER + all attributes on each entity | Section 3 |
| **Use Case Diagram** | 3 actors with use cases from Section 6 | Section 6 |
| **Activity Diagram** | Application lifecycle (pending → accepted/rejected) | Section 3.5 |
| **Sequence Diagram** | Student applies: login → select CV → submit → notification | Section 3.5 |
| **Class Diagram** | User, Student, Company, Admin, Internship, Application classes | Section 3 |
| **State Diagram** | Application status transitions | Section 3.5 |

---

## 11. Entity Count Summary

| Module | Tables | Count |
|--------|--------|-------|
| Authentication | users, password_resets, email_verifications | 3 |
| Student Profile | students, student_cvs, education, certifications, projects | 5 |
| Skills | skill_categories, skills, student_skills, internship_skills | 4 |
| Company | companies | 1 |
| Internships & Applications | internships, applications, favorites | 3 |
| Communication | notifications, messages, interviews | 3 |
| Admin & Platform | admins, announcements, admin_logs | 3 |
| **Total** | | **22** |

---

## 12. Enumeration Reference

| Table | Column | Allowed Values |
|-------|--------|----------------|
| users | role | `student`, `company`, `admin` |
| users | status | `active`, `pending`, `blocked` |
| skill_categories | type | `technical`, `soft` |
| student_skills | proficiency | `beginner`, `intermediate`, `advanced` |
| companies | verification_status | `pending`, `approved`, `rejected` |
| internships | work_type | `On-site`, `Remote`, `Hybrid` |
| internships | status | `draft`, `pending`, `active`, `closed`, `rejected` |
| applications | status | `pending`, `shortlisted`, `interview`, `accepted`, `rejected`, `withdrawn` |
| interviews | status | `scheduled`, `completed`, `cancelled`, `no_show` |
| announcements | target_role | `all`, `student`, `company` |

---

## Document Information

| Field | Value |
|-------|-------|
| Generated for | InternConnect Sri Lanka — Academic Project Report |
| Schema version | Phase 1+ foundation (`sql/schema.sql`) |
| Total entities | 22 tables |
| Total relationships | 26 foreign-key relationships |
| Junction tables | 4 (student_skills, internship_skills, favorites, applications) |

---

*End of Entity & Relationship Report*
