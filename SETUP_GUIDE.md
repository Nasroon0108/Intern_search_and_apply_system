# ⚡ Quick Setup Guide - InternConnect Sri Lanka

## 🎯 5-Minute Quick Start

```
1. Place project in: C:\xampp\htdocs\Intern_search_and_apply_system
2. Start Apache + MySQL (XAMPP Control Panel)
3. Import database: http://localhost/phpmyadmin → Import → sql/schema.sql
4. Run admin seed:  /setup/seed_admin.php
5. Run demo seed:   /setup/seed_data.php
6. Open: http://localhost/Intern_search_and_apply_system
7. Login (demo): amaya.perera@seed.internconnect.lk / Demo@123
   Admin: admin@internconnect.local / Admin123
```

**✅ Done!** Your platform is ready with demo data.

> Full friend-friendly guide: see **FRIEND_SETUP.md**

---

## 📋 Detailed Setup (Step-by-Step)

### Step 1️⃣: Download Project
```bash
# Clone from GitHub
git clone https://github.com/Nasroon0108/Intern_search_and_apply_system.git

# Or download ZIP and extract to C:\xampp\htdocs\
```

### Step 2️⃣: Start Servers
- **XAMPP**: Click "Start" for Apache & MySQL
- **WAMP**: Click system tray → Start All Services  
- **Laragon**: Click "Start All"

### Step 3️⃣: Import Database

#### 🖱️ Method A: phpMyAdmin (Easiest)
```
1. Open: http://localhost/phpmyadmin
2. Click "Import" tab
3. Select file: sql/schema.sql
4. Click "Import" ✅
```

#### 💻 Method B: Command Line
```bash
mysql -u root < sql/schema.sql
```

#### 🔧 Method C: MySQL Workbench
```
File → Open SQL Script → Select sql/schema.sql → Execute All
```

### Step 4️⃣: Configure Database (Optional)

**If using default XAMPP settings:** No configuration needed!

**If using custom credentials:**
```bash
# Copy template
copy config\database.local.php.example config\database.local.php

# Edit with your credentials
```

Edit `config/database.local.php`:
```php
<?php
return [
    'host' => 'localhost',
    'user' => 'your_username',
    'pass' => 'your_password',
    'name' => 'internconnect_sl',
];
```

### Step 5️⃣: Create Upload Directories
```bash
mkdir uploads
mkdir uploads\cvs
mkdir uploads\photos
mkdir uploads\logos
```

---

## 🌐 Access the Application

### Landing Page
```
http://localhost/Intern_search_and_apply_system
```

### Student Registration
```
http://localhost/Intern_search_and_apply_system/auth/register-student.php
```

### Company Registration
```
http://localhost/Intern_search_and_apply_system/auth/register-company.php
```

### Admin Login
```
Email:    admin@internconnect.local
Password: admin123
```

---

## 🔍 Verify Everything Works

After setup, check these to confirm installation:

| ✓ Check | Location | Expected Result |
|--------|----------|-----------------|
| Apache Running | XAMPP Control Panel | Apache = green |
| MySQL Running | XAMPP Control Panel | MySQL = green |
| Database Created | http://localhost/phpmyadmin | See `internconnect_sl` |
| App Loads | http://localhost/Intern_search_and_apply_system | Homepage displays |
| Registration Works | Try student registration | Form loads without errors |
| Admin Login Works | admin@internconnect.local / admin123 | Admin dashboard loads |
| File Upload Works | Student → Upload Photo | Photo uploads successfully |
| Search Works | http://localhost/Intern_search_and_apply_system/internships.php | Internship list displays |

---

## ⚙️ Configuration Files

### `config/config.php` - Application Settings
```php
define('APP_URL', 'http://localhost/Intern_search_and_apply_system');
define('SESSION_LIFETIME', 3600);  // 1 hour
define('MAX_CV_SIZE', 5 * 1024 * 1024);      // 5 MB
define('MAX_PHOTO_SIZE', 2 * 1024 * 1024);   // 2 MB
```

### `config/database.php` - Database Defaults
```php
$dbConfig = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'internconnect_sl',
    'charset' => 'utf8mb4',
];
```

**Note:** If `config/database.local.php` exists, it overrides these defaults!

---

## 🚨 Common Problems & Solutions

### Problem 1: "Database connection failed"
```
Cause:  MySQL not running
Fix:    Start MySQL in XAMPP Control Panel
```

### Problem 2: "Unknown database 'internconnect_sl'"
```
Cause:  Schema not imported
Fix:    Re-import sql/schema.sql via phpMyAdmin
```

### Problem 3: "Access denied for user 'root'@'localhost'"
```
Cause:  Wrong password in database config
Fix:    Check credentials in config/database.local.php
```

### Problem 4: "Permission denied" on file upload
```
Cause:  uploads/ folder not writable
Fix:    Right-click uploads/ → Properties → Security → Grant Write permission
```

### Problem 5: "Table 'users' doesn't exist"
```
Cause:  Database import incomplete
Fix:    Verify 15+ tables in phpMyAdmin, re-import if needed
```

---

## 👥 Default Test Account

After setup, use this to test:

```
Email:    admin@internconnect.local
Password: admin123
```

Or create your own:
1. Click "Register as Student"
2. Fill form and submit
3. Verify email link (check in browser address bar)
4. Login as student
5. Complete profile

---

## 📁 Project Structure Overview

```
Intern_search_and_apply_system/
├── 📂 auth/                 → Login, Register, Password Reset
├── 📂 student/              → Student Dashboard, Profile, Applications
├── 📂 company/              → Company Dashboard, Post Jobs
├── 📂 admin/                → Admin Panel, Verification, Moderation
├── 📂 config/               → Database & App Configuration
├── 📂 includes/             → Core Functions & Layout
├── 📂 sql/                  → Database Schema (import this!)
├── 📂 uploads/              → Student CVs, Photos, Company Logos
├── 📂 assets/               → CSS & JavaScript
└── 📄 index.php             → Homepage

Key Files:
├── config/database.php              (default DB settings)
├── config/database.local.php.example (copy & customize this)
├── config/config.php                (app settings)
└── sql/schema.sql                   (database structure)
```

---

## 🔐 Database Tables (15 Total)

| Table | Purpose |
|-------|---------|
| `users` | User accounts (email, password, role, status) |
| `students` | Student profile information |
| `companies` | Company profile information |
| `internships` | Job postings |
| `applications` | Student applications to jobs |
| `education` | Student education history |
| `skills` | Available skills |
| `student_skills` | Student's skills & proficiency |
| `projects` | Student portfolio projects |
| `certifications` | Student certifications |
| `student_cvs` | Uploaded CV files |
| `internship_skills` | Skills required for internships |
| `password_resets` | Password reset tokens |
| `email_verifications` | Email verification tokens |
| `favorites` | Saved internships |

---

## 🎯 First Steps After Setup

1. **Login as Admin**
   - Email: `admin@internconnect.local`
   - Password: `admin123`
   - Verify admin dashboard loads

2. **Create Student Account**
   - Go to: `/auth/register-student.php`
   - Fill in form
   - Click verification link
   - Verify student dashboard works

3. **Create Company Account**
   - Go to: `/auth/register-company.php`
   - Fill in company info
   - Go back to Admin → Verify Company
   - Verify company dashboard works

4. **Test Job Posting**
   - Login as company
   - Post an internship
   - Go back to Admin → Approve internship
   - View as student → Search & apply

5. **Test Application**
   - Upload CV as student
   - Apply to internship
   - Check applications list as company
   - Change status (shortlist, interview, accept, reject)

---

## 🔧 Changing Configuration

### Change Database Name
Edit `config/database.local.php`:
```php
return [
    'name' => 'my_custom_db_name',  // Change this
];
```

### Change Application URL
Edit `config/config.php`:
```php
define('APP_URL', 'http://my-custom-url.com');
```

### Change Session Duration
Edit `config/config.php`:
```php
define('SESSION_LIFETIME', 7200);  // 2 hours instead of 1
```

### Change File Upload Limits
Edit `config/config.php`:
```php
define('MAX_CV_SIZE', 10 * 1024 * 1024);  // 10 MB instead of 5
```

---

## 📱 Features by User Role

### 👨‍🎓 Student Features
- ✅ Register & verify email
- ✅ Complete profile (education, skills, projects, certifications)
- ✅ Upload CV & photos
- ✅ Search internships with filters
- ✅ Save favorite internships
- ✅ Apply to internships
- ✅ Track application status

### 🏢 Company Features
- ✅ Register & verify account
- ✅ Update company profile
- ✅ Post internship opportunities
- ✅ Manage applications
- ✅ Change application status
- ✅ View application statistics

### 👨‍💼 Admin Features
- ✅ Verify company registrations
- ✅ Moderate internship postings
- ✅ Manage platform users
- ✅ View platform statistics
- ✅ Access company & internship details

---

## 🚀 Production Deployment

When deploying to production:

1. **Update APP_URL in config/config.php**
   ```php
   define('APP_URL', 'https://yourdomain.com');
   ```

2. **Create production database config**
   ```php
   // config/database.local.php
   return [
       'host' => 'your_server',
       'user' => 'db_user',
       'pass' => 'strong_password',
       'name' => 'internconnect_production',
   ];
   ```

3. **Enable HTTPS** (SSL certificate)

4. **Set proper file permissions**
   ```
   uploads/ → read/write by web server
   config/ → read by web server only
   ```

5. **Configure SMTP for email** (in `includes/email.php`)

6. **Backup database regularly**

---

## 📞 Need Help?

### Check These First:
1. ✅ Is MySQL running? (XAMPP Control Panel)
2. ✅ Is Apache running? (XAMPP Control Panel)
3. ✅ Did you import `sql/schema.sql`?
4. ✅ Do 15+ tables appear in phpMyAdmin?
5. ✅ Can you access http://localhost/phpmyadmin?

### Still Stuck?
- Check README.md for detailed troubleshooting
- Review error logs in browser console (F12)
- Check MySQL error log in phpMyAdmin

---

## 🎓 Learning Resources

- PHP: https://www.php.net/manual/
- MySQL: https://dev.mysql.com/doc/
- Bootstrap: https://getbootstrap.com/docs/5.0/
- Security: https://owasp.org/

---

**Happy coding! 🚀**  
Questions? Check the full README.md in the project root.
