<?php
declare(strict_types=1);

$applicationTimezone = getenv('APP_TIMEZONE') ?: 'Asia/Kolkata';
if (!date_default_timezone_set($applicationTimezone)) {
    throw new RuntimeException('APP_TIMEZONE must be a valid PHP timezone identifier.');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = dirname($script);
    $base = preg_replace('~/(admin|student|api)$~', '', $base) ?? $base;
    $base = $base === '.' || $base === '/' ? '' : rtrim($base, '/');

    return $path === '' ? $base : $base . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    $location = str_starts_with($path, '/') ? $path : app_url($path);
    header('Location: ' . $location);
    exit;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function current_user(): array
{
    return [
        'id' => (int) ($_SESSION['user_id'] ?? 0),
        'name' => (string) ($_SESSION['name'] ?? ''),
        'email' => (string) ($_SESSION['email'] ?? ''),
        'role' => (string) ($_SESSION['role'] ?? ''),
        'status' => (string) ($_SESSION['status'] ?? ''),
    ];
}

function require_role(string $role): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        redirect('login.php');
    }

    $account = db()->prepare('SELECT name, email, role, status FROM users WHERE id = :id LIMIT 1');
    $account->execute([':id' => (int) $_SESSION['user_id']]);
    $account = $account->fetch(PDO::FETCH_ASSOC);

    if (!$account || $account['status'] !== 'active') {
        $_SESSION = [];
        session_destroy();
        redirect('login.php');
    }

    $_SESSION['name'] = $account['name'];
    $_SESSION['email'] = $account['email'];
    $_SESSION['role'] = $account['role'];
    $_SESSION['status'] = $account['status'];

    if ($account['role'] !== $role) {
        redirect(($_SESSION['role'] ?? '') === 'admin'
            ? 'admin/dashboard.php'
            : 'student/dashboard.php');
    }
}

function require_student(): void
{
    require_role('student');
}

function require_admin(): void
{
    require_role('admin');
}

function safe_int(mixed $value): int
{
    $validated = filter_var($value, FILTER_VALIDATE_INT);
    return $validated === false ? 0 : (int) $validated;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(mixed $submitted = null): void
{
    $submitted ??= $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';

    if (!is_string($submitted) || !is_string($stored)
        || $stored === '' || !hash_equals($stored, $submitted)) {
        http_response_code(403);
        exit('Invalid request. Please refresh the page and try again.');
    }
}

function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function flash(string $key, string $message): void
{
    set_flash($key, $message);
}

function get_flash(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);

    return is_string($message) ? $message : null;
}

function db(): PDO
{
    static $connection;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $connection = require __DIR__ . '/../config/database.php';
    if (!$connection instanceof PDO) {
        throw new RuntimeException('config/database.php must return a PDO connection.');
    }

    $connection->exec("SET time_zone = '" . date('P') . "'");

    return $connection;
}

function get_user_by_email(string $email): array|false
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function get_exam_by_id(int $examId): array|false
{
    $stmt = db()->prepare('SELECT * FROM exams WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $examId]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function count_questions_for_exam(int $examId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM questions WHERE exam_id = :exam_id');
    $stmt->execute([':exam_id' => $examId]);

    return (int) $stmt->fetchColumn();
}

function exam_is_available(array $exam): bool
{
    $now = time();
    $start = strtotime((string) ($exam['start_time'] ?? ''));
    $end = strtotime((string) ($exam['end_time'] ?? ''));

    return ($exam['status'] ?? '') === 'published'
        && $start !== false
        && $end !== false
        && $start <= $now
        && $now <= $end;
}

function attempt_deadline(array $attempt, ?array $exam = null): int
{
    $startedAt = strtotime((string) ($attempt['started_at'] ?? ''));
    $examEnd = strtotime((string) ($exam['end_time'] ?? $attempt['end_time'] ?? ''));

    if ($startedAt === false || $examEnd === false) {
        return 0;
    }

    return min(
        $startedAt + ((int) ($exam['duration_minutes'] ?? $attempt['duration_minutes'] ?? 0) * 60),
        $examEnd
    );
}

function complete_attempt(int $attemptId, int $studentId, ?array $submittedAnswers = null): void
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare(
            'SELECT a.id, a.status, a.started_at, e.id AS exam_id,
                    e.duration_minutes, e.end_time
             FROM attempts a
             JOIN exams e ON e.id = a.exam_id
             WHERE a.id = :attempt_id AND a.student_id = :student_id
             FOR UPDATE'
        );
        $stmt->execute([
            ':attempt_id' => $attemptId,
            ':student_id' => $studentId,
        ]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt) {
            throw new RuntimeException('Exam attempt was not found.');
        }

        if ($attempt['status'] !== 'submitted') {
            $canAcceptSubmitted = $submittedAnswers !== null
                && time() <= attempt_deadline($attempt);
            $questions = $pdo->prepare(
                'SELECT id, correct_option, marks
                 FROM questions WHERE exam_id = :exam_id ORDER BY id'
            );
            $questions->execute([':exam_id' => (int) $attempt['exam_id']]);
            $savedAnswers = [];
            if (!$canAcceptSubmitted) {
                $saved = $pdo->prepare(
                    'SELECT question_id, selected_option
                     FROM answers WHERE attempt_id = :attempt_id'
                );
                $saved->execute([':attempt_id' => $attemptId]);
                foreach ($saved->fetchAll(PDO::FETCH_ASSOC) as $answer) {
                    $savedAnswers[(int) $answer['question_id']] = $answer['selected_option'];
                }
            }

            $saveAnswer = $pdo->prepare(
                'INSERT INTO answers
                    (attempt_id, question_id, selected_option, is_correct, marks_obtained)
                 VALUES
                    (:attempt_id, :question_id, :selected_option, :is_correct, :marks_obtained)
                 ON DUPLICATE KEY UPDATE
                    selected_option = VALUES(selected_option),
                    is_correct = VALUES(is_correct),
                    marks_obtained = VALUES(marks_obtained)'
            );

            foreach ($questions->fetchAll(PDO::FETCH_ASSOC) as $question) {
                $selected = null;
                if ($canAcceptSubmitted) {
                    $candidate = $submittedAnswers[(string) $question['id']] ?? null;
                    if (is_string($candidate) && in_array($candidate, ['A', 'B', 'C', 'D'], true)) {
                        $selected = $candidate;
                    }
                } else {
                    $saved = $savedAnswers[(int) $question['id']] ?? null;
                    $selected = is_string($saved) ? $saved : null;
                }

                $correct = $selected !== null
                    && $selected === $question['correct_option'];
                $saveAnswer->execute([
                    ':attempt_id' => $attemptId,
                    ':question_id' => (int) $question['id'],
                    ':selected_option' => $selected,
                    ':is_correct' => $correct ? 1 : 0,
                    ':marks_obtained' => $correct ? (int) $question['marks'] : 0,
                ]);
            }

            $score = $pdo->prepare(
                'SELECT COALESCE(SUM(marks_obtained), 0)
                 FROM answers WHERE attempt_id = :attempt_id'
            );
            $score->execute([':attempt_id' => $attemptId]);

            $update = $pdo->prepare(
                'UPDATE attempts
                 SET score = :score, submitted_at = NOW(), status = "submitted"
                 WHERE id = :attempt_id AND status = "in_progress"'
            );
            $update->execute([
                ':score' => (int) $score->fetchColumn(),
                ':attempt_id' => $attemptId,
            ]);
        }

        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function render_flash_messages(): void
{
    foreach (['success', 'error'] as $type) {
        $message = get_flash($type);
        if ($message !== null) {
            echo '<div class="alert alert-' . e($type) . '" role="'
                . ($type === 'error' ? 'alert' : 'status') . '">'
                . e($message) . '</div>';
        }
    }
}
