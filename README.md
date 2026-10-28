# ExamPortal

A PHP and MySQL online examination portal with static HTML pages, a JavaScript client, and a PHP JSON API. It supports student registration, an administrator dashboard, timed multiple-choice exams, autosaved answers, automatic scoring, and answer review.

## Requirements

- PHP 8.1 or newer with `pdo_mysql` and `mbstring` enabled
- MySQL 8.0 or a compatible MariaDB release
- A web server configured to serve this directory as the application root

## Setup

1. Create the database and tables by importing `database/online_exam.sql` into MySQL.
2. Copy `config/database.example.php` to `config/database.php` and update the host, database name, username, and password, or set the `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` environment variables. `config/database.php` is intentionally excluded from version control. `.env.example` documents the variables; PHP does not load `.env` files automatically.
3. Point your PHP web server at this directory. For a local PHP development server, run `php -S 127.0.0.1:8000` from the project root and open `http://127.0.0.1:8000`. The landing page and every student, admin, login, and exam screen are separate HTML files. PHP handles the application data and actions through `api/index.php`.
4. Register a student account from the portal.
5. Promote the first administrator from a trusted MySQL session:

   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'your-admin-email@example.com';
   ```

   Sign out and sign back in to open the administrator dashboard.

The example database settings are for local development only. Configure production credentials outside source control, serve the site over HTTPS, and use a dedicated MySQL account with access only to this database. For production, set `APP_ENV=production`; this makes the session cookie HTTPS-only, so TLS must be enabled at the web server or trusted reverse proxy.

### Updating an existing installation

The full SQL file is intended for a new database. For an existing installation, take a verified backup first, then apply the migration files in `database/migrations/` in order. Apply `database/migrations/20261029_auth_rate_limits.sql` and then `database/migrations/20261029_exam_answer_integrity.sql` before deploying the updated API. The second migration adds composite foreign keys; inspect its diagnostic query first and resolve any legacy mismatched answers before applying it. Test migrations against a staging copy before production.

## HTML and PHP separation

- `index.html`, `login.html`, `register.html`, `student/*.html`, and `admin/*.html` are the browser pages.
- `assets/js/app.js` loads data and handles interactions using the JSON API.
- `api/index.php?action=...` handles authentication, role checks, exam operations, and JSON responses.
- `config/` contains the shared PHP bootstrap and database connection.

The API uses the existing PHP session and a CSRF token. Keep the HTML, JavaScript, and PHP API on the same origin so the browser can send the session cookie. The old `.php` page URLs redirect to their `.html` equivalents for compatibility.

## Using the portal

- Administrators create draft exams, add multiple-choice questions, then publish them. Published questions are locked to preserve existing attempts and scores. Each student can make one attempt per exam; availability dates control when an attempt can be started.
- Students register, open an available exam, and choose one answer per question. Answers save automatically; the server enforces the timer and calculates the score at submission.
- Students can review their submitted answers and results. Administrators can view all submitted scores.
- Exam availability inputs and displayed timestamps use UTC.

Only student accounts can be created through public registration. Create or promote administrator accounts through a trusted database session.