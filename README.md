# ExamPortal

A PHP and MySQL online examination portal with student registration, an administrator dashboard, timed multiple-choice exams, autosaved answers, automatic scoring, and answer review.

## Requirements

- PHP 8.1 or newer with `pdo_mysql` and `mbstring` enabled
- MySQL 8.0 or a compatible MariaDB release
- A web server configured to serve this directory as the application root

## Setup

1. Create the database and tables by importing `database/online_exam.sql` into MySQL.
2. Copy `config/database.example.php` to `config/database.php` and update the host, database name, username, and password, or set the `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` environment variables. `config/database.php` is intentionally excluded from version control. `.env.example` documents the variables; PHP does not load `.env` files automatically.
3. Point your PHP web server at this directory. For a local PHP development server, run `php -S 127.0.0.1:8000` from the project root and open `http://127.0.0.1:8000`.
4. Register a student account from the portal.
5. Promote the first administrator from a trusted MySQL session:

   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'your-admin-email@example.com';
   ```

   Sign out and sign back in to open the administrator dashboard.

The example database settings are for local development only. Configure production credentials outside source control, serve the site over HTTPS, and use a dedicated MySQL account with access only to this database.

## Using the portal

- Administrators create draft exams, add multiple-choice questions, then publish them. Published questions are locked to preserve existing attempts and scores. Each student can make one attempt per exam; availability dates control when an attempt can be started.
- Students register, open an available exam, and choose one answer per question. Answers save automatically; the server enforces the timer and calculates the score at submission.
- Students can review their submitted answers and results. Administrators can view all submitted scores.
- Exam availability inputs and displayed timestamps use UTC.

Only student accounts can be created through public registration. Create or promote administrator accounts through a trusted database session.