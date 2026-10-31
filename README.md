# examspace Online Examination Portal

## Requirements

- XAMPP with Apache and MySQL
- PHP 8.1 or later with `pdo_mysql`
- Composer (needed for password-reset email support)

## Local setup

1. Put the project in `C:\xampp\htdocs\exam_portal` and start Apache and MySQL.
2. For a fresh install, import [`database/online_exam.sql`](database/online_exam.sql) using phpMyAdmin. It creates the `online_exam` database and the required users, exams, questions, attempts, answers, and password reset tables.
   For an existing installation that uses `question_options`, run [`database/migrate-existing-installation.sql`](database/migrate-existing-installation.sql) once instead. It retains the old columns and copies existing options and answers into the new format.
3. Copy `config/database.example.php` to `config/database.php` and set your MySQL credentials.
4. Run `composer install` from the project directory to install PHPMailer.
5. To enable password reset emails, copy `config/mail.example.php` to `config/mail.php` and enter your SMTP settings.
6. Visit `http://localhost/exam_portal/`.

The local database and mail configuration files are intentionally ignored by Git. Never commit real passwords or SMTP credentials.
Exam schedules use the `APP_TIMEZONE` PHP/Apache environment variable; the default is `Asia/Kolkata`.

## First administrator

Public registration creates student accounts only. Create the first administrator directly in MySQL with a password hash:

```sql
INSERT INTO users (name, email, password_hash, role, status)
VALUES ('Admin', 'admin@example.com', '<password-hash>', 'admin', 'active');
```

Generate a hash with PHP:

```php
<?php
echo password_hash('replace-with-a-strong-password', PASSWORD_DEFAULT);
```

## Using the portal

- Students can register, sign in, browse published exams, start or resume an attempt, and review submitted results.
- Administrators can manage students, create exams, add questions, publish or close exams, and review submissions.
- Exam answers are checked and scored on the server. An attempt can be submitted early or automatically when its time limit or exam window ends.
- Students can request a password reset; reset tokens are stored as hashes and expire after 30 minutes.

## Troubleshooting

- Confirm Apache and MySQL are running.
- Check the credentials in `config/database.php` and verify the `online_exam` database exists.
- Run `composer install` if password-reset email support reports a missing PHPMailer dependency.
- Check the Apache/PHP error log for database or SMTP errors; application errors are not displayed with internal details.
