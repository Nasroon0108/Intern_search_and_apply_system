# InternConnect Sri Lanka

Internship Search & Application Portal for Sri Lankan university students.

**Stack:** PHP, MySQL, HTML, CSS, Bootstrap 5, JavaScript

**Repository:** [Intern_search_and_apply_system](https://github.com/Nasroon0108/Intern_search_and_apply_system)

---

## Phase 1 (Current) — Foundation

- MySQL database schema (full project structure)
- User authentication (Student, Company, Admin)
- Password hashing (`password_hash`)
- Session management & role-based access control
- CSRF protection on forms
- Prepared statements (SQL injection prevention)
- Bootstrap UI with role-specific dashboards
- Secure file upload helpers (ready for Phase 2)

---

## Requirements

- PHP 8.0+
- MySQL 5.7+ / MariaDB
- Apache (XAMPP, WAMP, or Laragon recommended)

---

## Installation (XAMPP)

### 1. Clone or copy project

Place the project in your web root, e.g.:

```
C:\xampp\htdocs\Intern_search_and_apply_system
```

### 2. Create database

1. Start **Apache** and **MySQL** in XAMPP
2. Open **phpMyAdmin**: http://localhost/phpmyadmin
3. Import `sql/schema.sql`

### 3. Configure database (optional)

Default settings work with XAMPP (`root`, no password).

To override, copy and edit:

```
config/database.local.php.example  →  config/database.local.php
```

### 4. Create admin account

Visit once in your browser:

```
http://localhost/Intern_search_and_apply_system/setup/seed_admin.php
```

**Default admin credentials:**
- Email: `admin@internconnect.lk`
- Password: `Admin@123`

### 5. Open the app

```
http://localhost/Intern_search_and_apply_system/
```

---

## Project Structure

```
Intern_search_and_apply_system/
├── admin/              # Admin pages
├── assets/             # CSS, JS
├── auth/               # Login, register, logout
├── company/            # Company pages
├── config/             # App & DB config
├── includes/           # Auth, CSRF, helpers, layout
├── setup/              # One-time setup scripts
├── sql/                # Database schema
├── student/            # Student pages
├── uploads/            # CVs, photos (not in git)
└── index.php           # Landing page
```

---

## User Roles

| Role    | Register at                    | Notes                          |
|---------|--------------------------------|--------------------------------|
| Student | `/auth/register-student.php`   | Active immediately             |
| Company | `/auth/register-company.php`   | Pending until admin verifies   |
| Admin   | `setup/seed_admin.php`         | Pre-created via setup script   |

---

## Security (Phase 1)

- Bcrypt password hashing
- Prepared MySQLi statements
- CSRF tokens on POST forms
- Session timeout (1 hour)
- HttpOnly session cookies
- Upload directory blocks PHP execution (`.htaccess`)
- Input validation & HTML escaping

---

## Roadmap

- **Phase 2:** Student & Company features (profiles, internships, apply)
- **Phase 3:** Admin panel, analytics, reports
- **Phase 4:** Notifications, interviews, certificates

---

## License

University project — InternConnect Sri Lanka
