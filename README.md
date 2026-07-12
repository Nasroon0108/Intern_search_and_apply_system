# InternConnect Sri Lanka 🎓

**Internship Search & Application Portal for Sri Lankan university students**

Connect students with internship opportunities and help companies find talented interns!

**Stack:** PHP 8.0+, MySQL 5.7+, Bootstrap 5, HTML, CSS, JavaScript

**Repository:** [Intern_search_and_apply_system](https://github.com/Nasroon0108/Intern_search_and_apply_system)

---

## ⚡ Quick Start (5 Minutes)

```bash
# 1. Clone project
git clone https://github.com/Nasroon0108/Intern_search_and_apply_system.git

# 2. Start Apache & MySQL (XAMPP Control Panel)

# 3. Import database
# Open: http://localhost/phpmyadmin → Import → select sql/schema.sql

# 4. Open in browser
# http://localhost/Intern_search_and_apply_system
```

**That's it!** See detailed setup instructions below.

---

## 📋 Requirements

- **PHP 8.0+** (or 7.4+)
- **MySQL 5.7+** or MariaDB 10.2+
- **Apache** web server (XAMPP, WAMP, or Laragon recommended)
- **4MB disk space** minimum

---

## ✨ Features

### 🎓 For Students
✅ User registration with email verification  
✅ Comprehensive profile management  
✅ Advanced internship search & filtering  
✅ Save favorite internships  
✅ Apply with CV & cover letter  
✅ Track application status  
✅ View interview invitations  

### 🏢 For Companies
✅ Company registration & verification  
✅ Post internship opportunities  
✅ Receive & manage applications  
✅ Shortlist & communicate with candidates  
✅ View application statistics  
✅ Manage internship postings  

### 👨‍💼 For Admins
✅ Verify company accounts  
✅ Moderate internship postings  
✅ Manage platform users  
✅ View platform statistics  
✅ Access detailed company & internship info  

---

---

## Installation & Setup

### Prerequisites

- **PHP 8.0+** (or 7.4+)
- **MySQL 5.7+** or **MariaDB**
- **Apache** web server (or any PHP-capable server)
- **Recommended:** XAMPP, WAMP, Laragon, or similar all-in-one package

---

## Step-by-Step Installation Guide

### 1. Download & Place Project

#### Option A: Clone from GitHub
```bash
cd C:\xampp\htdocs
git clone https://github.com/Nasroon0108/Intern_search_and_apply_system.git
cd Intern_search_and_apply_system
```

#### Option B: Manual Download
- Download project files
- Extract to your web root: `C:\xampp\htdocs\Intern_search_and_apply_system`

---

### 2. Start Web & Database Server

#### Using XAMPP:
1. Open **XAMPP Control Panel**
2. Click **Start** for:
   - Apache (port 80)
   - MySQL (port 3306)
3. Wait for both to turn green ✓

#### Using WAMP:
- Click system tray icon → **Start All Services**

#### Using Laragon:
- Click **Start All** button

---

### 3. Create & Initialize Database

#### Method A: phpMyAdmin (GUI - Easiest)

1. Open browser: **http://localhost/phpmyadmin**
2. Click **"Import"** tab (top menu)
3. Click **"Choose File"** → Select `sql/schema.sql` from your project
4. Click **"Import"** button at bottom
5. ✅ Database created with all tables!

#### Method B: MySQL Command Line (Fastest)

```bash
# Windows Command Prompt (in your project directory)
mysql -u root < sql/schema.sql

# Or if MySQL requires password:
mysql -u root -p < sql/schema.sql
# Enter password when prompted
```

#### Method C: MySQL Workbench

1. Connect to MySQL server
2. File → Open SQL Script → Select `sql/schema.sql`
3. Execute all (Ctrl+Shift+Enter)

**After import, verify:** In phpMyAdmin, you should see database `internconnect_sl` with 15+ tables

---

### 4. Configure Database Connection

#### Option A: Default Configuration (For XAMPP)
Default settings in `config/database.php` already work:
- Host: `localhost`
- User: `root`
- Password: (empty)
- Database: `internconnect_sl`

**No further action needed if using default XAMPP setup!**

#### Option B: Custom Configuration (For different credentials)

1. Copy configuration template:
   ```bash
   copy config\database.local.php.example config\database.local.php
   ```

2. Edit `config/database.local.php` with your credentials:
   ```php
   <?php
   return [
       'host' => 'localhost',      // Your MySQL host
       'user' => 'root',           // Your MySQL username
       'pass' => 'password123',    // Your MySQL password
       'name' => 'internconnect_sl',  // Database name
   ];
   ```

3. Save the file

The application will automatically load `database.local.php` if it exists, overriding defaults.

---

### 5. Create Required Directories

These directories store uploaded files (they should already exist):
```
uploads/
├── cvs/          (Student CVs - max 5MB each)
├── photos/       (Profile photos - max 2MB each)
└── logos/        (Company logos - max 2MB each)
```

**Verify they exist** - if missing, create them:
```bash
mkdir uploads
mkdir uploads\cvs
mkdir uploads\photos
mkdir uploads\logos
```

Set write permissions:
```bash
# Windows - Right-click folder → Properties → Security → Edit
# Grant "Modify" permission for IIS_IUSRS or NETWORK SERVICE
```

---

## Running the Application

### 1. Access in Browser

Open your browser and navigate to:

```
http://localhost/Intern_search_and_apply_system
```

You should see the **InternConnect Sri Lanka** homepage.

---

### 2. Test the Connection

#### Verify Database Connection
1. Go to **http://localhost/Intern_search_and_apply_system**
2. Click **"Register as Student"** or **"Register as Company"**
3. If registration form loads, database is connected ✅

#### Troubleshooting Connection Errors

**Error: "Database connection failed"**
- Check MySQL is running (XAMPP Control Panel → MySQL green)
- Verify credentials in `config/database.php` or `config/database.local.php`
- Database name is `internconnect_sl` (not `internconnect_sl_dev`)

**Error: "Table 'internconnect_sl.users' doesn't exist"**
- Schema not imported correctly
- Re-import `sql/schema.sql` via phpMyAdmin
- Verify all 15+ tables appear in phpMyAdmin

**Error: "Permission denied" for uploads**
- Right-click `uploads/` folder → Properties → Security
- Add write permissions for IIS_IUSRS or NETWORK SERVICE

---

## Test User Accounts (After Setup)

The database includes a default admin account:

**Admin Login:**
- Email: `admin@internconnect.local`
- Password: `admin123`

**Create Student Account:**
1. Go to http://localhost/Intern_search_and_apply_system/auth/register-student.php
2. Fill form with any email/password
3. Click verify email link (in development, check email verification URL)
4. Access student dashboard

**Create Company Account:**
1. Go to http://localhost/Intern_search_and_apply_system/auth/register-company.php
2. Fill form with company details
3. Admin must approve company → Then access company dashboard

---

## Configuration Reference

### `config/config.php` - Application Settings

```php
define('APP_URL', 'http://localhost/Intern_search_and_apply_system');
define('SESSION_LIFETIME', 3600);  // 1 hour - adjust if needed

// File size limits
define('MAX_CV_SIZE', 5 * 1024 * 1024);      // 5 MB for CVs
define('MAX_PHOTO_SIZE', 2 * 1024 * 1024);   // 2 MB for photos
```

### `config/database.php` - Database Settings

```php
$dbConfig = [
    'host' => 'localhost',           // MySQL server
    'user' => 'root',                // MySQL username
    'pass' => '',                    // MySQL password
    'name' => 'internconnect_sl',    // Database name
    'charset' => 'utf8mb4',          // Character encoding
];
```

---

## Database Tables Overview

| Table | Purpose |
|-------|---------|
| `users` | User accounts (student/company/admin) |
| `students` | Student profiles & information |
| `companies` | Company profiles & verification |
| `internships` | Job postings |
| `applications` | Student applications |
| `education` | Student education history |
| `skills` | Available skills library |
| `student_skills` | Student's skill proficiencies |
| `projects` | Student portfolio projects |
| `certifications` | Student certifications |
| `student_cvs` | Uploaded CV files |
| `password_resets` | Password reset tokens |
| `email_verifications` | Email verification tokens |
| `favorites` | Saved/favorite internships |
| `internship_skills` | Required skills per internship |

---

### 6. Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| **"Connection refused" error** | Check MySQL is running; verify port 3306 is open |
| **"Access denied for user 'root'@'localhost'"** | Verify password in `database.local.php`; reset MySQL password if needed |
| **File upload fails** | Check `uploads/` folder permissions; ensure folder is writable |
| **Sessions not working** | Verify `session.save_path` is writable in PHP settings |
| **Email not sending** | In development, emails are logged; configure SMTP for production |
| **403 Forbidden error** | Check file permissions; IIS_IUSRS user needs read access to project |

---

## Project Features by Role

### 👨‍🎓 Student
- Register & verify email
- Complete comprehensive profile (education, skills, projects, certifications)
- Search & filter internships (keyword, location, type, industry)
- Save favorite internships
- Apply with CV & cover letter
- Track application status

### 🏢 Company
- Register company account
- Update company profile with logo
- Post internship opportunities
- Manage applications received
- Shortlist candidates
- View application statistics

### 👨‍💼 Admin
- Verify company registrations
- Moderate internship postings
- Manage platform users
- View platform statistics
- Access company & internship details

---

## Deployment Notes

For production deployment:

1. **Update `config/config.php`:**
   ```php
   define('APP_URL', 'https://yourdomain.com');
   ```

2. **Create `config/database.local.php`** with production credentials

3. **Enable HTTPS** (SSL certificate required)

4. **Configure Email** (SMTP in `includes/email.php`)

5. **Set file permissions:**
   - `uploads/` → writable by web server
   - `config/` → readable only by web server

6. **Hide sensitive files:**
   - Add `.htaccess` to prevent direct access to `config/`, `includes/`, `sql/` folders

---

## 🗂️ Project Structure

```
Intern_search_and_apply_system/
├── admin/                    # Admin dashboard & management pages
├── auth/                     # Login, register, password reset
├── company/                  # Company profile & internship management
├── config/                   # Database & application configuration
│   ├── config.php           # App settings
│   ├── database.php         # Default DB credentials
│   └── database.local.php.example
├── includes/                # Core functionality
│   ├── header.php           # Navigation & layout
│   ├── footer.php
│   ├── auth.php             # Authentication functions
│   ├── email.php            # Email templates & sending
│   ├── csrf.php             # CSRF protection
│   └── functions.php        # Helper functions
├── setup/                   # One-time setup scripts
│   └── seed_admin.php       # Create default admin
├── sql/                     # Database schema
│   └── schema.sql           # Import this file
├── student/                 # Student dashboard & profile pages
├── uploads/                 # File storage (not in git)
│   ├── cvs/
│   ├── photos/
│   └── logos/
├── assets/                  # Frontend files
│   ├── css/style.css
│   └── js/main.js
├── index.php                # Landing page
└── internship*.php          # Public internship search/detail
```

---

## 📞 Troubleshooting Guide

### Database Connection Issues

| Error | Cause | Solution |
|-------|-------|----------|
| **"Database connection failed"** | MySQL not running | Start MySQL in XAMPP/WAMP/Laragon |
| **"Access denied for user 'root'@'localhost'"** | Wrong password in config | Check `database.local.php` credentials |
| **"Unknown database 'internconnect_sl'"** | Schema not imported | Import `sql/schema.sql` via phpMyAdmin |
| **"Table 'users' doesn't exist"** | Schema import incomplete | Re-import `sql/schema.sql` |
| **Connection times out** | MySQL port blocked | Check port 3306 in firewall settings |

### File Upload Issues

| Error | Cause | Solution |
|-------|-------|----------|
| **"Permission denied"** | Upload folder not writable | Right-click folder → Properties → Security → Grant Write permission |
| **"File too large"** | Exceeds MAX_CV_SIZE or MAX_PHOTO_SIZE | Check limits in `config/config.php` |
| **"Invalid file type"** | Not PDF/JPG/PNG/WebP | Upload correct file type |
| **Files don't appear** | Wrong folder path | Verify `uploads/` directories exist |

### PHP Errors

| Error | Cause | Solution |
|-------|-------|----------|
| **"Call to undefined function"** | Missing include/require | Check require statements at top of file |
| **"Session not working"** | session.save_path not writable | Check PHP session directory permissions |
| **"Fatal error: Uncaught Error"** | Syntax error in PHP | Check error logs: `php -l filename.php` |

### Access & Permission Issues

| Error | Cause | Solution |
|-------|-------|----------|
| **403 Forbidden** | File permissions too restrictive | Check NTFS permissions or directory ownership |
| **Can't access admin panel** | User not admin role | Login as admin@internconnect.local |
| **Can't verify company** | Logged in as non-admin | Only admins can verify companies |

---

## 🔍 Verification Checklist

After installation, verify everything is working:

- [ ] **Apache Running** - XAMPP Control Panel shows Apache green
- [ ] **MySQL Running** - XAMPP Control Panel shows MySQL green  
- [ ] **Database Created** - `internconnect_sl` appears in phpMyAdmin
- [ ] **Tables Exist** - 15+ tables visible in phpMyAdmin
- [ ] **App Loads** - http://localhost/Intern_search_and_apply_system displays homepage
- [ ] **Can Register** - Student/Company registration forms load without errors
- [ ] **Admin Login Works** - Login with admin@internconnect.local / admin123
- [ ] **File Upload Works** - Can upload CV/photo without permission errors
- [ ] **Search Works** - Can search for internships
- [ ] **Apply Works** - Can submit application as student

---

## 📊 Database Connection Architecture

The application uses a two-tier configuration system:

### Tier 1: Default Configuration (`config/database.php`)
```php
$dbConfig = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',  // Empty for default XAMPP
    'name' => 'internconnect_sl',
];
```

### Tier 2: Local Override (`config/database.local.php`)
```php
<?php
// This file overrides defaults if it exists
// Copy from database.local.php.example and edit
return [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => 'your_password',  // Override password
    'name' => 'internconnect_sl',
];
```

**How it works:**
1. App loads default config from `database.php`
2. If `database.local.php` exists, it merges and overrides defaults
3. **Benefit**: Share project on GitHub without exposing passwords

---

## 🔐 Security Features Implemented

✅ **CSRF Protection** - All forms have CSRF tokens  
✅ **SQL Injection Prevention** - Prepared statements with bound parameters  
✅ **XSS Protection** - HTML escaping on all user input  
✅ **Password Security** - bcrypt hashing with PASSWORD_DEFAULT  
✅ **Email Verification** - Required before account activation  
✅ **Password Reset** - Secure token-based reset with 1-hour expiration  
✅ **Session Security** - Regenerate ID on login, 1-hour timeout  
✅ **File Upload Validation** - MIME type checking, size limits  
✅ **Role-Based Access Control** - Pages check user role before displaying  
✅ **Database Foreign Keys** - Referential integrity with InnoDB  

---

## 🚀 Quick Reference: Common Tasks

### Reset Admin Password
1. Open phpMyAdmin
2. Go to `users` table
3. Find admin record (email: `admin@internconnect.local`)
4. Use "Change Password" or update password_hash manually

### Clear All Test Data
```bash
# In phpMyAdmin, run:
TRUNCATE TABLE applications;
TRUNCATE TABLE student_skills;
TRUNCATE TABLE student_cvs;
TRUNCATE TABLE education;
TRUNCATE TABLE projects;
TRUNCATE TABLE certifications;
DELETE FROM students WHERE user_id != 1;
DELETE FROM companies WHERE user_id != 1;
DELETE FROM users WHERE role != 'admin';
```

### Check PHP Version
```bash
php -v
```

### Check MySQL Version
```bash
mysql -u root -V
```

### View PHP Error Logs
- XAMPP: `C:\xampp\apache\logs\error.log`
- WAMP: `C:\wamp64\logs\apache\error.log`

### Enable SQL Query Logging (Debug)
Add to `config/database.php` after connection:
```php
$mysqli->query("SET SESSION sql_mode='';");
```

---

## 📚 Additional Resources

- **PHP Documentation:** https://www.php.net/manual/
- **MySQL Documentation:** https://dev.mysql.com/doc/
- **Bootstrap 5 Docs:** https://getbootstrap.com/docs/5.0/
- **MySQLi Prepared Statements:** https://www.php.net/manual/en/mysqli.quickstart.prepared-statements.php

---

## 💡 Tips for Development

1. **Always backup database before major changes**
2. **Use phpMyAdmin to inspect data if unsure**
3. **Check browser console (F12) for JavaScript errors**
4. **Use `var_dump()` or `print_r()` to debug PHP variables**
5. **Keep config files out of version control** - Use `.gitignore` for sensitive files
6. **Test each user role separately** - Student, Company, Admin workflows are different
7. **Clear browser cache (Ctrl+Shift+Delete)** if styles/JS not updating

---

## 🎓 Learning Resources

- **PHP Beginner Guide:** https://www.w3schools.com/php/
- **MySQL Tutorial:** https://www.w3schools.com/mysql/
- **Web Security Best Practices:** https://owasp.org/
- **RESTful API Design:** https://restfulapi.net/

---

## 📄 License & Attribution

**Project Name:** InternConnect Sri Lanka  
**Purpose:** Internship management platform for Sri Lankan universities  
**Technology:** PHP, MySQL, Bootstrap 5  
**Repository:** [GitHub](https://github.com/Nasroon0108/Intern_search_and_apply_system)

---

## ✍️ Contributing

To contribute to this project:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📧 Support & Questions

- Create an issue on GitHub for bugs
- Email: Contact project maintainer
- Check the troubleshooting section above first

---

**Happy coding! 🚀**
