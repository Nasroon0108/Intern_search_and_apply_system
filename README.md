# InternConnect Sri Lanka

Internship search and application portal for Sri Lankan university students, companies, and admins.

**Stack:** PHP 8+, MySQL, Bootstrap 5, HTML/CSS/JavaScript  

**Repository:** [Intern_search_and_apply_system](https://github.com/Nasroon0108/Intern_search_and_apply_system)

---

## Quick start

```bash
# 1. Clone into XAMPP htdocs
cd C:\xampp\htdocs
git clone https://github.com/Nasroon0108/Intern_search_and_apply_system.git

# 2. Start Apache + MySQL in XAMPP Control Panel

# 3. Import database
# phpMyAdmin → Import → sql/schema.sql

# 4. Seed admin + demo data (open once each in browser)
# http://localhost/Intern_search_and_apply_system/setup/seed_admin.php
# http://localhost/Intern_search_and_apply_system/setup/seed_data.php

# 5. Open the app
# http://localhost/Intern_search_and_apply_system/
```

Friend-friendly walkthrough: see **[FRIEND_SETUP.md](FRIEND_SETUP.md)**  
ER documentation: see **[ENTITY_RELATIONSHIP_REPORT.md](ENTITY_RELATIONSHIP_REPORT.md)**

---

## Requirements

- PHP 8.0+ (XAMPP PHP 8.2 works)
- MySQL 5.7+ / MariaDB
- Apache (XAMPP / WAMP / Laragon)

---

## Features

### Students
- Register with email verification
- Profile: education, skills, projects, certifications, photo
- Multiple CV upload (PDF) and primary CV selection
- Search / filter / save internships
- Apply with CV + cover letter
- Track application status
- Light / dark theme

### Companies
- Register and await admin approval
- Company profile + logo
- Post and manage internships
- Review applications with **View CV** and **Cover Letter**
- Update application status (pending → shortlisted → interview → accept/reject)

### Admins
- Dashboard statistics
- Manage users (view detail, activate/block, mark email verified)
- Approve / reject companies
- Moderate internships (approve, reject, close, **reopen**)

---

## Demo accounts (after seeding)

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@internconnect.local` | `Admin123` |
| Student | `amaya.perera@seed.internconnect.lk` | `Demo@123` |
| Student | `kavindu.silva@seed.internconnect.lk` | `Demo@123` |
| Company | `techvista@seed.internconnect.lk` | `Demo@123` |
| Company | `greenwave@seed.internconnect.lk` | `Demo@123` |

---

## Installation details

### 1. Place the project

```bash
cd C:\xampp\htdocs
git clone https://github.com/Nasroon0108/Intern_search_and_apply_system.git
```

Or extract a ZIP to `C:\xampp\htdocs\Intern_search_and_apply_system`.

### 2. Start Apache & MySQL

Use XAMPP Control Panel → start **Apache** and **MySQL**.

### 3. Import the schema

1. Open http://localhost/phpmyadmin  
2. **Import** → choose `sql/schema.sql` → Go  

This creates database `internconnect_sl` and all tables.

### 4. Database config

Default XAMPP settings in `config/database.php` usually work:

- Host: `localhost`
- User: `root`
- Password: *(empty)*
- Database: `internconnect_sl`

For custom credentials:

```bash
copy config\database.local.php.example config\database.local.php
```

Edit `config/database.local.php`, then save. It overrides defaults automatically.

If your folder name differs, update `APP_URL` in `config/config.php`:

```php
define('APP_URL', 'http://localhost/Intern_search_and_apply_system');
```

### 5. Uploads folders

Ensure these exist and are writable:

```
uploads/
├── cvs/
├── photos/
└── logos/
```

### 6. Seed accounts

| Script | Purpose |
|--------|---------|
| `/setup/seed_admin.php` | Creates admin user |
| `/setup/seed_data.php` | Demo students, companies, internships, applications |

Open each URL **once** in the browser after importing the schema.

---

## Email verification (local development)

On localhost, outbound email is disabled (`MAIL_ENABLED` is off in `config/config.php`).

After registration, the login page shows a **verification link** you can click.  
Admins can also mark a user’s email as verified from **Admin → Users → View**.

---

## Project structure

```
Intern_search_and_apply_system/
├── admin/           # Admin panel
├── auth/            # Login, register, verify email, password reset
├── company/         # Company portal (+ download-cv.php)
├── student/         # Student portal
├── config/          # App + database settings
├── includes/        # Auth, CSRF, email, layouts, helpers
├── setup/           # seed_admin.php, seed_data.php
├── sql/schema.sql   # Database schema
├── uploads/         # CVs, photos, logos (gitignored contents)
├── assets/          # CSS, JS, images
├── index.php        # Landing page
├── internships.php  # Public internship list
└── internship-detail.php
```

---

## Main database tables

| Table | Purpose |
|-------|---------|
| `users` | Accounts (student / company / admin) |
| `students` / `companies` | Role profiles |
| `internships` | Postings |
| `applications` | Applications (+ `cv_id`, cover letter) |
| `student_cvs` | Uploaded CV files |
| `email_verifications` | Email verify tokens |
| `password_resets` | Reset tokens |
| `favorites` | Saved internships |
| `education` / `skills` / `projects` / `certifications` | Student profile data |

Full ER notes: **[ENTITY_RELATIONSHIP_REPORT.md](ENTITY_RELATIONSHIP_REPORT.md)**

---

## Security

- CSRF tokens on forms
- Prepared statements (SQL injection protection)
- Output escaping (`e()`) for XSS protection
- Password hashing (`password_hash`)
- Role-based access control
- Secure CV download (companies only for their applicants)
- Upload MIME/size validation

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Database connection failed | Start MySQL; check `config/database.php` or `database.local.php` |
| Unknown database / missing tables | Re-import `sql/schema.sql` |
| Admin login fails | Run `setup/seed_admin.php` (password is `Admin123`) |
| Email never arrives | Expected on localhost — use the on-screen verify link |
| Upload errors | Make `uploads/cvs`, `uploads/photos`, `uploads/logos` writable |
| Styles look wrong | Hard refresh: Ctrl+F5 |
| Wrong site URL | Fix `APP_URL` in `config/config.php` to match folder name |

---

## Production notes

1. Set `APP_URL` to your real domain (HTTPS).
2. Use strong DB credentials via `database.local.php`.
3. Configure real SMTP / mail in `includes/email.php` (and enable mail when not on localhost).
4. Remove or protect `/setup/` scripts on a live server.
5. Keep `uploads/` writable; do not commit uploaded files.

---

## License

Academic / educational project — InternConnect Sri Lanka.
