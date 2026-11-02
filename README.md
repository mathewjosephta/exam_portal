# examspace | Online Examination Portal

A web-based examination management system built with PHP and MySQL. examspace provides separate interfaces for administrators and students, covering exam creation, question management, timed exam attempts, answer submission, and result review.

> **Project status:** Development project. Test the application locally before using it for real examinations or deploying it publicly.

> **Repository integrity warning:** The current `main` branch contains unresolved Git merge-conflict markers in at least `database/online_exam.sql`, `student/take-exam.php`, and `student/submit-exam.php`. Resolve these conflicts and validate the resulting schema and PHP files before importing the database or running the application. The repository also contains a nested `exam_portal/` directory with a second copy of application files. Confirm which copy is authoritative before removing or reorganising either one.


## Features

### Student
- Register for a student account and sign in.
- Browse examinations published by administrators.
- Read exam instructions and start or resume an attempt.
- Take timed multiple-choice examinations.
- Save selected answers during an attempt.
- Submit examinations and view scores and result details.
- Request password reset instructions by email.

### Administrator
- Access an administrator dashboard and view portal statistics.
- Create and manage examinations.
- Add questions, answer choices, marks, and correct answers.
- Configure exam duration and availability windows.
- Publish or unpublish examinations subject to attempt-state restrictions.
- Manage student accounts.
- Review exam submissions and individual results.

### Application
- Role-based access for administrators and students.
- Password hashing and verification using PHP's password API.
- Database access through PDO prepared statements.
- Session-based authentication and CSRF checks.
- Server-side answer validation and score calculation.
- Password-reset tokens stored as hashes with an expiry.
- Security-related HTTP response headers.

## Technology Stack

| Layer | Technologies |
|---|---|
| Backend | PHP 8.1+ |
| Database | MySQL |
| Frontend | HTML5, CSS3, JavaScript |
| Database access | PDO with PDO MySQL |
| Email | PHPMailer |
| Local development | XAMPP |
| Dependencies | Composer |

## Project Structure

```text
exam_portal/
├── admin/                  # Administrator pages
├── api/                    # Application API and answer handling
├── assets/
│   ├── css/                # Stylesheets
│   └── js/                 # Frontend JavaScript
├── config/
│   ├── bootstrap.php       # Application bootstrap and shared helpers
│   ├── database.example.php
│   ├── mail.example.php
│   └── exams.php
├── database/
│   ├── online_exam.sql     # Database setup
│   └── migrate-existing-installation.sql
├── includes/               # Shared authentication and layout helpers
├── student/                # Student pages and exam workflow
├── composer.json
├── index.php               # Application entry point
├── login.php
├── register.php
├── logout.php
├── forgot-password.php
└── reset-password.php
```

## Requirements

- PHP 8.1 or later.
- MySQL 8.0 or a compatible MySQL release.
- Apache or another PHP-compatible web server.
- PHP extensions: PDO and PDO MySQL.
- Composer for installing PHPMailer, which is used by password-reset email functionality.

XAMPP is a convenient local development option because it bundles Apache, PHP, and MySQL/MariaDB.

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/mathewjosephta/exam_portal.git
cd exam_portal
```

For XAMPP on Windows, the project can be placed in:

```text
C:\xampp\htdocs\exam_portal
```

### 2. Start Apache and MySQL

Open the XAMPP Control Panel and start Apache and MySQL. Confirm that the configured ports are available.

### 3. Create the database

Open `http://localhost/phpmyadmin` and import `database/online_exam.sql`. The application expects a database named `online_exam`.

If upgrading an existing installation, review `database/migrate-existing-installation.sql` and back up the database before running a migration.

### 4. Configure database access

Copy the example configuration file:

**Windows PowerShell**
```powershell
Copy-Item config/database.example.php config/database.php
```

**macOS / Linux**
```bash
cp config/database.example.php config/database.php
```

Edit `config/database.php` and enter the MySQL host, database name, username, and password.

The example configuration uses host `127.0.0.1`, database `online_exam`, username `root`, and an empty password. These are common local XAMPP defaults, not suitable production credentials.

### 5. Install dependencies

Run this from the project root:

```bash
composer install
```

### 6. Configure email (optional)

To enable password-reset email, copy `config/mail.example.php` to `config/mail.php` and enter valid SMTP settings. Keep real credentials out of version control.

### 7. Open the application

Visit `http://localhost/exam_portal/` in your browser. Register a student account or sign in with an administrator account.

## Creating the First Administrator

Public registration is intended for student accounts. Create the first administrator directly in MySQL.

Generate a password hash using PHP:

```bash
php -r "echo password_hash('ChangeThisPassword', PASSWORD_DEFAULT), PHP_EOL;"
```

Then insert the administrator:

```sql
INSERT INTO users (name, email, password_hash, role, status)
VALUES (
    'Administrator',
    'admin@example.com',
    'PASTE_GENERATED_PASSWORD_HASH_HERE',
    'admin',
    'active'
);
```

Replace the sample email and hash with your own values.

## Examination Workflow

1. An administrator creates an examination and configures its duration and availability.
2. The administrator adds questions, answer choices, marks, and correct answers.
3. The examination is published.
4. Students sign in and view available examinations.
5. A student starts an attempt and answers within the time limit.
6. Answers are saved during the attempt.
7. The student submits the exam, or the application handles expiry according to the exam timer.
8. The server evaluates submitted answers and records the result.
9. Students and administrators can review result information permitted by their roles.

## Configuration Notes

- The application uses the `online_exam` MySQL database.
- Keep local database credentials in `config/database.php`.
- Keep SMTP credentials in `config/mail.php`.
- The shared bootstrap configures sessions, security headers, database access, and common helpers.
- Use HTTPS and strong database credentials when deploying outside localhost.
- Configure PHP and MySQL time zones consistently. Test exam start, end, and timer behaviour in the target environment.

## Troubleshooting

| Problem | What to check |
|---|---|
| Database connection fails | Confirm MySQL is running and `config/database.php` contains valid credentials. |
| Database or table not found | Confirm the `online_exam` database and required tables were imported. |
| PHPMailer class not found | Run `composer install` from the project root. |
| Password reset email is not delivered | Check SMTP configuration, credentials, port, and server logs. |
| Apache does not start | Check whether another service is using the configured HTTP port. |
| Login or registration fails | Check the PHP/Apache error log and database schema. |
| Exam timer behaves unexpectedly | Verify PHP and MySQL time-zone configuration and exam availability dates. |

## Security Notes

- Never commit passwords, SMTP credentials, private configuration files, or production database exports.
- Use HTTPS in production.
- Use a dedicated database account with only the permissions the application needs.
- Back up the database before migrations or schema changes.
- Test role restrictions, exam timing, answer submission, and result access before real use.
- Keep PHP, MySQL, Composer dependencies, and the web server updated.

## Contributing

Contributions and suggestions are welcome.

1. Fork the repository.
2. Create a feature branch.
3. Make and test your changes locally.
4. Commit your changes with a descriptive message.
5. Open a pull request describing the changes.

## License

No license is currently specified in this repository. Unless a license is added, treat the project as all rights reserved by its copyright holder.

---

**Repository:** https://github.com/mathewjosephta/exam_portal