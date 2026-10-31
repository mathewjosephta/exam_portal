# Online Examination Portal

## Requirements

- XAMPP on Windows
- PHP 8.1+
- MySQL 8.0+
- Apache + PHP extension `pdo_mysql`

## Setup

1. Install XAMPP and launch Apache and MySQL.
2. Import `database/exam_portal.sql` in phpMyAdmin.
3. Create `config/database.php` with your local settings.
4. Place the project under `htdocs/exam-portal`.
5. Open `http://localhost/exam-portal`.

## MySQL config

```php
<?php
return new PDO(
    'mysql:host=127.0.0.1;dbname=exam_portal;charset=utf8mb4',
    'root',
    '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
```

## First admin account

Create a secure administrator manually in MySQL using a hashed password. Example:

```sql
INSERT INTO users (name, email, password, role, status)
VALUES ('Admin', 'admin@example.com', '$2y$10$...', 'admin', 'active');
```

Generate the hash with PHP:

```php
<?php
print password_hash('StrongAdminPass!123', PASSWORD_DEFAULT);
```

## Project flow

- Students register from the home page.
- Admins log in and create exams.
- Admin adds questions and publishes the exam.
- Students take available exams and submit answers.
- Scores are calculated server-side and stored in the database.

## Troubleshooting

- Ensure MySQL is running.
- Verify `config/database.php` credentials.
- Check Apache is serving the project folder in `htdocs`.
- If a page shows database errors, ensure the database was imported successfully.
