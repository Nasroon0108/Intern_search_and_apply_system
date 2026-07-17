# How to Run InternConnect on Your Local Computer

Share this guide with your friend so she can set up the project and demo data in about 10–15 minutes.

---

## What She Needs

1. **XAMPP** (Apache + MySQL + PHP)  
   Download: https://www.apachefriends.org/
2. The project files (ZIP from you, or clone from GitHub)

---

## Step 1 — Install & start XAMPP

1. Install XAMPP.
2. Open **XAMPP Control Panel**.
3. Click **Start** for:
   - **Apache**
   - **MySQL**

Both should show green “Running”.

---

## Step 2 — Put the project in `htdocs`

### Option A — From ZIP (easiest)

1. Extract the ZIP.
2. Move/rename the folder to:

```
C:\xampp\htdocs\Intern_search_and_apply_system
```

Folder name must match exactly (or you will need to change `APP_URL` later).

### Option B — From GitHub

```bash
cd C:\xampp\htdocs
git clone https://github.com/Nasroon0108/Intern_search_and_apply_system.git
```

---

## Step 3 — Create upload folders

In the project folder, make sure these folders exist:

```
Intern_search_and_apply_system/
  uploads/
    cvs/
    photos/
    logos/
```

If missing, create them manually in File Explorer.

---

## Step 4 — Import the database

1. Open: http://localhost/phpmyadmin
2. Click **Import**
3. Choose file:  
   `C:\xampp\htdocs\Intern_search_and_apply_system\sql\schema.sql`
4. Click **Go / Import**

This creates the database `internconnect_sl` and all tables (plus basic skills seed data).

### Confirm

In phpMyAdmin, open database `internconnect_sl` — you should see many tables (`users`, `students`, `companies`, `internships`, etc.).

---

## Step 5 — Check the app URL

Open this in the browser:

```
http://localhost/Intern_search_and_apply_system/
```

If the page loads, go to Step 6.

### If your folder name is different

Edit `config/config.php` and change:

```php
define('APP_URL', 'http://localhost/YOUR_FOLDER_NAME');
```

Example:

```php
define('APP_URL', 'http://localhost/Intern_search_and_apply_system');
```

---

## Step 6 — Create the Admin account (required)

Importing `schema.sql` does **not** create a login. She must open this URL once:

```
http://localhost/Intern_search_and_apply_system/setup/seed_admin.php
```

She should see a success page with:

| Field | Value |
|-------|-------|
| **Email** | `admin@internconnect.local` |
| **Password** | `Admin123` |

Then go to Login and use those exact credentials (case-sensitive).

**If login keeps failing:** open `seed_admin.php` again (it resets the password), then retry. Do not use `Admin@123` or `admin123` — those are wrong.

---

## Step 7 — Load demo / seeded data (students, companies, internships)

Open this URL once:

```
http://localhost/Intern_search_and_apply_system/setup/seed_data.php
```

This creates demo students, companies, internships, applications, favorites, and notifications.

**Password for ALL seeded accounts:**

```
Demo@123
```

### Demo Student accounts

| Name | Email |
|------|-------|
| Amaya Perera | `amaya.perera@seed.internconnect.lk` |
| Kavindu Silva | `kavindu.silva@seed.internconnect.lk` |
| Nethmi Fernando | `nethmi.fernando@seed.internconnect.lk` |

### Demo Company accounts

| Company | Email |
|---------|-------|
| TechVista Lanka | `techvista@seed.internconnect.lk` |
| GreenWave Solutions | `greenwave@seed.internconnect.lk` |

---

## Step 8 — Login and test

Login page:

```
http://localhost/Intern_search_and_apply_system/auth/login.php
```

Try these:

1. **Student:** `amaya.perera@seed.internconnect.lk` / `Demo@123`
2. **Company:** `techvista@seed.internconnect.lk` / `Demo@123`
3. **Admin:** `admin@internconnect.local` / `Admin123`

---

## Quick checklist

| Step | Done? |
|------|-------|
| XAMPP Apache + MySQL running | ☐ |
| Project in `C:\xampp\htdocs\Intern_search_and_apply_system` | ☐ |
| `uploads/cvs`, `uploads/photos`, `uploads/logos` exist | ☐ |
| Imported `sql/schema.sql` | ☐ |
| Homepage opens | ☐ |
| Ran `setup/seed_admin.php` | ☐ |
| Ran `setup/seed_data.php` | ☐ |
| Can login with a demo account | ☐ |

---

## Common problems

### “Database connection failed”
- Start **MySQL** in XAMPP.

### “Unknown database internconnect_sl”
- Import `sql/schema.sql` again in phpMyAdmin.

### Homepage 404 / blank page
- Check folder name matches `APP_URL` in `config/config.php`.
- Confirm Apache is running.

### Login works but style looks broken
- Hard refresh: `Ctrl + F5`.

### Seed pages show errors about missing tables
- Schema import may have failed — re-import `sql/schema.sql`.

### Want to reset and seed again?
1. In phpMyAdmin, drop database `internconnect_sl`
2. Re-import `sql/schema.sql`
3. Run `seed_admin.php` again
4. Run `seed_data.php` again

---

## Default database settings (XAMPP)

Usually no change needed:

| Setting | Value |
|---------|--------|
| Host | `localhost` |
| User | `root` |
| Password | *(empty)* |
| Database | `internconnect_sl` |

If MySQL has a password, create `config/database.local.php`:

```php
<?php
return [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => 'your_mysql_password',
    'name' => 'internconnect_sl',
];
```

---

## What to send your friend

1. This file (`FRIEND_SETUP.md`), **or**
2. The project ZIP + this guide, **or**
3. The GitHub link:
   - https://github.com/Nasroon0108/Intern_search_and_apply_system

Tell her the 3 important URLs after setup:

1. App: `http://localhost/Intern_search_and_apply_system/`
2. Admin seed: `.../setup/seed_admin.php`
3. Demo seed: `.../setup/seed_data.php`

---

**Note:** Keep `setup/seed_admin.php` and `setup/seed_data.php` only for local demo use. Do not leave them open on a live/public server.
