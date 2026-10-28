<?php
declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../config/exams.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function api_response(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

function api_input(): array
{
    $data = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($data)) {
        api_response(['error' => 'Request body must contain valid JSON.'], 400);
    }
    return $data;
}

function api_require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        header('Allow: ' . $method);
        api_response(['error' => 'Method not allowed.'], 405);
    }
}

function api_require_csrf(): void
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        api_response(['error' => 'Your session expired. Reload the page and try again.'], 403);
    }
}

function api_require_role(string $role): int
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        api_response(['error' => 'Please sign in to continue.'], 401);
    }
    if ($_SESSION['role'] !== $role) {
        api_response(['error' => 'You do not have permission to do that.'], 403);
    }
    return (int) $_SESSION['user_id'];
}

function api_int(mixed $value): int
{
    $number = filter_var($value, FILTER_VALIDATE_INT);
    return $number === false ? 0 : (int) $number;
}

function api_user(): ?array
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        return null;
    }
    return [
        'id' => (int) $_SESSION['user_id'],
        'name' => (string) $_SESSION['user_name'],
        'role' => (string) $_SESSION['role'],
    ];
}

function api_exam(int $examId): array
{
    global $pdo;
    $query = $pdo->prepare(
        'SELECT e.*, COUNT(q.id) AS question_count
         FROM exams e LEFT JOIN questions q ON q.exam_id = e.id
         WHERE e.id = :id GROUP BY e.id'
    );
    $query->execute(['id' => $examId]);
    $exam = $query->fetch();
    if (!$exam) {
        api_response(['error' => 'Exam not found.'], 404);
    }
    return $exam;
}

function api_attempt(int $attemptId, int $studentId): array
{
    global $pdo;
    $query = $pdo->prepare(
        'SELECT a.id, a.status, a.score, a.started_at, a.submitted_at, a.student_id,
                e.id AS exam_id, e.title, e.duration_minutes
         FROM attempts a JOIN exams e ON e.id = a.exam_id
         WHERE a.id = :id AND a.student_id = :student_id'
    );
    $query->execute(['id' => $attemptId, 'student_id' => $studentId]);
    $attempt = $query->fetch();
    if (!$attempt) {
        api_response(['error' => 'Exam attempt not found.'], 404);
    }
    return $attempt;
}

$action = (string) ($_GET['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'session':
        api_require_method('GET');
        api_response(['user' => api_user(), 'csrf' => csrf_token()]);

    case 'login':
        api_require_method('POST');
        api_require_csrf();
        $input = api_input();
        $email = strtolower(trim(is_string($input['email'] ?? null) ? $input['email'] : ''));
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';
        $query = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = :email');
        $query->execute(['email' => $email]);
        $user = $query->fetch();
        if (!$user || !password_verify($password, $user['password_hash'])) {
            api_response(['error' => 'The email address or password is incorrect.'], 422);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        api_response(['user' => api_user(), 'csrf' => csrf_token()]);

    case 'register':
        api_require_method('POST');
        api_require_csrf();
        $input = api_input();
        $name = trim(is_string($input['name'] ?? null) ? $input['name'] : '');
        $email = strtolower(trim(is_string($input['email'] ?? null) ? $input['email'] : ''));
        $password = is_string($input['password'] ?? null) ? $input['password'] : '';
        $confirmation = is_string($input['password_confirmation'] ?? null) ? $input['password_confirmation'] : '';
        $errors = [];
        if ($name === '' || mb_strlen($name) > 120) {
            $errors[] = 'Enter a name no longer than 120 characters.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors[] = 'Your password must be between 8 and 72 bytes long.';
        }
        if ($password !== $confirmation) {
            $errors[] = 'The password confirmation does not match.';
        }
        if ($errors) {
            api_response(['errors' => $errors], 422);
        }
        $insert = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role)
             VALUES (:name, :email, :password_hash, 'student')"
        );
        try {
            $insert->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                api_response(['errors' => ['An account with that email address already exists.']], 422);
            }
            throw $exception;
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $pdo->lastInsertId();
        $_SESSION['user_name'] = $name;
        $_SESSION['role'] = 'student';
        api_response(['user' => api_user(), 'csrf' => csrf_token()], 201);

    case 'logout':
        api_require_method('POST');
        api_require_csrf();
        $_SESSION = [];
        session_regenerate_id(true);
        session_destroy();
        api_response(['signed_out' => true]);

    case 'student/dashboard':
        api_require_method('GET');
        $studentId = api_require_role('student');
        $available = $pdo->query(
            "SELECT e.id, e.title, e.description, e.duration_minutes, COUNT(q.id) AS question_count
             FROM exams e LEFT JOIN questions q ON q.exam_id = e.id
             WHERE e.status = 'published'
               AND (e.starts_at IS NULL OR e.starts_at <= CURRENT_TIMESTAMP)
               AND (e.ends_at IS NULL OR e.ends_at >= CURRENT_TIMESTAMP)
             GROUP BY e.id HAVING COUNT(q.id) > 0
             ORDER BY e.starts_at IS NOT NULL, e.starts_at, e.created_at DESC"
        )->fetchAll();
        $query = $pdo->prepare(
            'SELECT a.id, a.status, a.score, a.started_at, a.submitted_at, e.title
             FROM attempts a JOIN exams e ON e.id = a.exam_id
             WHERE a.student_id = :student_id ORDER BY a.started_at DESC'
        );
        $query->execute(['student_id' => $studentId]);
        api_response(['available' => $available, 'attempts' => $query->fetchAll()]);

    case 'student/exam':
        api_require_method('GET');
        $studentId = api_require_role('student');
        $examId = api_int($_GET['id'] ?? null);
        if (!$examId) {
            api_response(['error' => 'Exam not found.'], 404);
        }
        $exam = api_exam($examId);
        $query = $pdo->prepare('SELECT id, status FROM attempts WHERE exam_id = :exam_id AND student_id = :student_id');
        $query->execute(['exam_id' => $examId, 'student_id' => $studentId]);
        api_response(['exam' => $exam, 'attempt' => $query->fetch() ?: null, 'open' => exam_is_open($exam)]);

    case 'student/start':
        api_require_method('POST');
        $studentId = api_require_role('student');
        api_require_csrf();
        $examId = api_int(api_input()['exam_id'] ?? null);
        if (!$examId) {
            api_response(['error' => 'Invalid exam.'], 400);
        }
        $exam = api_exam($examId);
        $existing = $pdo->prepare('SELECT id, status FROM attempts WHERE exam_id = :exam_id AND student_id = :student_id');
        $existing->execute(['exam_id' => $examId, 'student_id' => $studentId]);
        $attempt = $existing->fetch();
        if (!$attempt && (!exam_is_open($exam) || (int) $exam['question_count'] < 1)) {
            api_response(['error' => 'This exam is not currently available.'], 409);
        }
        if (!$attempt) {
            $create = $pdo->prepare(
                'INSERT INTO attempts (exam_id, student_id)
                 VALUES (:exam_id, :student_id)
                 ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
            );
            $create->execute(['exam_id' => $examId, 'student_id' => $studentId]);
            $attemptId = (int) $pdo->lastInsertId();
            $statusQuery = $pdo->prepare('SELECT status FROM attempts WHERE id = :id');
            $statusQuery->execute(['id' => $attemptId]);
            $attempt = ['id' => $attemptId, 'status' => $statusQuery->fetchColumn()];
        }
        api_response(['attempt_id' => (int) $attempt['id'], 'status' => $attempt['status']]);

    case 'student/attempt':
        api_require_method('GET');
        $studentId = api_require_role('student');
        $attemptId = api_int($_GET['id'] ?? null);
        $attempt = api_attempt($attemptId, $studentId);
        if ($attempt['status'] === 'submitted') {
            api_response(['submitted' => true, 'attempt_id' => $attemptId]);
        }
        $timeLimit = (int) $attempt['duration_minutes'] * 60;
        $secondsLeft = max(0, $timeLimit - (time() - strtotime($attempt['started_at'])));
        if ($secondsLeft === 0) {
            submit_attempt($pdo, $attemptId, $studentId);
            api_response(['submitted' => true, 'attempt_id' => $attemptId]);
        }
        $query = $pdo->prepare(
            'SELECT q.id, q.question_text, q.marks, q.position,
                    o.id AS option_id, o.option_text, o.position AS option_position, a.selected_option_id
             FROM questions q
             JOIN question_options o ON o.question_id = q.id
             LEFT JOIN answers a ON a.question_id = q.id AND a.attempt_id = :attempt_id
             WHERE q.exam_id = :exam_id ORDER BY q.position, o.position'
        );
        $query->execute(['attempt_id' => $attemptId, 'exam_id' => $attempt['exam_id']]);
        $questions = [];
        foreach ($query->fetchAll() as $row) {
            $questionId = (int) $row['id'];
            if (!isset($questions[$questionId])) {
                $questions[$questionId] = [
                    'id' => $questionId,
                    'text' => $row['question_text'],
                    'marks' => (int) $row['marks'],
                    'options' => [],
                    'selected_option_id' => $row['selected_option_id'] === null ? null : (int) $row['selected_option_id'],
                ];
            }
            $questions[$questionId]['options'][] = [
                'id' => (int) $row['option_id'],
                'text' => $row['option_text'],
            ];
        }
        api_response([
            'attempt' => ['id' => $attemptId, 'title' => $attempt['title']],
            'seconds_left' => $secondsLeft,
            'questions' => array_values($questions),
            'csrf' => csrf_token(),
        ]);

    case 'student/answer':
        api_require_method('POST');
        $studentId = api_require_role('student');
        api_require_csrf();
        $input = api_input();
        $attemptId = api_int($input['attempt_id'] ?? null);
        $questionId = api_int($input['question_id'] ?? null);
        $optionId = api_int($input['option_id'] ?? null);
        if (!$attemptId || !$questionId || !$optionId) {
            api_response(['error' => 'Select a valid answer.'], 400);
        }
        $pdo->beginTransaction();
        try {
            $attemptQuery = $pdo->prepare(
                "SELECT a.status, a.started_at, e.duration_minutes
                 FROM attempts a JOIN exams e ON e.id = a.exam_id
                 WHERE a.id = :id AND a.student_id = :student_id FOR UPDATE"
            );
            $attemptQuery->execute(['id' => $attemptId, 'student_id' => $studentId]);
            $attempt = $attemptQuery->fetch();
            if (!$attempt || $attempt['status'] !== 'in_progress') {
                $pdo->rollBack();
                api_response(['error' => 'This exam is no longer in progress.'], 409);
            }
            if (time() - strtotime($attempt['started_at']) >= (int) $attempt['duration_minutes'] * 60) {
                $pdo->rollBack();
                submit_attempt($pdo, $attemptId, $studentId);
                api_response(['error' => 'Your exam time has expired.'], 410);
            }
            $valid = $pdo->prepare(
                'SELECT q.id FROM questions q
                 JOIN question_options o ON o.question_id = q.id
                 JOIN attempts a ON a.exam_id = q.exam_id
                 WHERE a.id = :attempt_id AND q.id = :question_id AND o.id = :option_id'
            );
            $valid->execute(['attempt_id' => $attemptId, 'question_id' => $questionId, 'option_id' => $optionId]);
            if (!$valid->fetchColumn()) {
                $pdo->rollBack();
                api_response(['error' => 'That answer is not part of this exam question.'], 400);
            }
            $save = $pdo->prepare(
                'INSERT INTO answers (attempt_id, question_id, selected_option_id)
                 VALUES (:attempt_id, :question_id, :option_id)
                 ON DUPLICATE KEY UPDATE selected_option_id = VALUES(selected_option_id)'
            );
            $save->execute(['attempt_id' => $attemptId, 'question_id' => $questionId, 'option_id' => $optionId]);
            $pdo->commit();
            api_response(['saved' => true]);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

    case 'student/submit':
        api_require_method('POST');
        $studentId = api_require_role('student');
        api_require_csrf();
        $attemptId = api_int(api_input()['attempt_id'] ?? null);
        if (!$attemptId) {
            api_response(['error' => 'Invalid exam attempt.'], 400);
        }
        api_attempt($attemptId, $studentId);
        submit_attempt($pdo, $attemptId, $studentId);
        api_response(['submitted' => true, 'attempt_id' => $attemptId]);

    case 'student/results':
        api_require_method('GET');
        $studentId = api_require_role('student');
        $query = $pdo->prepare(
            "SELECT a.id, a.score, a.submitted_at, e.title,
                    (SELECT COALESCE(SUM(q.marks), 0) FROM questions q WHERE q.exam_id = e.id) AS total_marks
             FROM attempts a JOIN exams e ON e.id = a.exam_id
             WHERE a.student_id = :student_id AND a.status = 'submitted' ORDER BY a.submitted_at DESC"
        );
        $query->execute(['student_id' => $studentId]);
        api_response(['results' => $query->fetchAll()]);

    case 'student/result':
        api_require_method('GET');
        if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
            api_response(['error' => 'Please sign in to continue.'], 401);
        }
        if (!in_array($_SESSION['role'], ['student', 'admin'], true)) {
            api_response(['error' => 'You do not have permission to view this result.'], 403);
        }
        $studentId = (int) $_SESSION['user_id'];
        $attemptId = api_int($_GET['id'] ?? null);
        if ($_SESSION['role'] === 'admin') {
            $attemptQuery = $pdo->prepare(
                'SELECT a.id, a.status, a.score, a.started_at, a.submitted_at, a.student_id,
                        e.id AS exam_id, e.title, e.duration_minutes
                 FROM attempts a JOIN exams e ON e.id = a.exam_id WHERE a.id = :id'
            );
            $attemptQuery->execute(['id' => $attemptId]);
            $attempt = $attemptQuery->fetch();
            if (!$attempt) {
                api_response(['error' => 'Exam attempt not found.'], 404);
            }
        } else {
            $attempt = api_attempt($attemptId, $studentId);
        }
        if ($attempt['status'] !== 'submitted') {
            api_response(['error' => 'This exam has not been submitted yet.'], 409);
        }
        $summary = $pdo->prepare(
            'SELECT a.score, a.submitted_at,
                    (SELECT COALESCE(SUM(q.marks), 0) FROM questions q WHERE q.exam_id = e.id) AS total_marks
             FROM attempts a JOIN exams e ON e.id = a.exam_id WHERE a.id = :id'
        );
        $summary->execute(['id' => $attemptId]);
        $answerQuery = $pdo->prepare(
            'SELECT q.question_text, q.marks, selected.option_text AS selected_text,
                    correct.option_text AS correct_text,
                    (selected.id IS NOT NULL AND selected.is_correct = 1) AS is_correct
             FROM questions q
             LEFT JOIN answers a ON a.question_id = q.id AND a.attempt_id = :attempt_id
             LEFT JOIN question_options selected ON selected.id = a.selected_option_id
             LEFT JOIN question_options correct ON correct.question_id = q.id AND correct.is_correct = 1
             WHERE q.exam_id = :exam_id ORDER BY q.position'
        );
        $answerQuery->execute(['attempt_id' => $attemptId, 'exam_id' => $attempt['exam_id']]);
        api_response([
            'attempt' => ['id' => $attemptId, 'title' => $attempt['title']] + $summary->fetch(),
            'answers' => $answerQuery->fetchAll(),
        ]);

    case 'admin/dashboard':
        api_require_method('GET');
        api_require_role('admin');
        api_response(['stats' => [
            'exams' => (int) $pdo->query('SELECT COUNT(*) FROM exams')->fetchColumn(),
            'published' => (int) $pdo->query("SELECT COUNT(*) FROM exams WHERE status = 'published'")->fetchColumn(),
            'students' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
            'submissions' => (int) $pdo->query("SELECT COUNT(*) FROM attempts WHERE status = 'submitted'")->fetchColumn(),
        ]]);

    case 'admin/exams':
        api_require_method('GET');
        api_require_role('admin');
        $exams = $pdo->query(
            'SELECT e.id, e.title, e.duration_minutes, e.status, e.starts_at, e.ends_at,
                    COUNT(DISTINCT q.id) AS question_count, COUNT(DISTINCT a.id) AS attempt_count
             FROM exams e LEFT JOIN questions q ON q.exam_id = e.id
             LEFT JOIN attempts a ON a.exam_id = e.id
             GROUP BY e.id ORDER BY e.created_at DESC'
        )->fetchAll();
        api_response(['exams' => $exams]);

    case 'admin/exam/create':
        api_require_method('POST');
        $adminId = api_require_role('admin');
        api_require_csrf();
        $input = api_input();
        $title = trim(is_string($input['title'] ?? null) ? $input['title'] : '');
        $description = trim(is_string($input['description'] ?? null) ? $input['description'] : '');
        $duration = is_string($input['duration_minutes'] ?? null) || is_int($input['duration_minutes'] ?? null)
            ? trim((string) $input['duration_minutes']) : '';
        $startsAt = is_string($input['starts_at'] ?? null) ? trim($input['starts_at']) : '';
        $endsAt = is_string($input['ends_at'] ?? null) ? trim($input['ends_at']) : '';
        $errors = [];
        if ($title === '' || mb_strlen($title) > 180) {
            $errors[] = 'Enter an exam title no longer than 180 characters.';
        }
        if (!ctype_digit($duration) || (int) $duration < 1 || (int) $duration > 600) {
            $errors[] = 'Duration must be between 1 and 600 minutes.';
        }
        $parseDate = static function (string $value): string|false|null {
            if ($value === '') {
                return null;
            }
            $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] || $dateErrors['error_count']))) {
                return false;
            }
            return $date->format('Y-m-d H:i:s');
        };
        $startDate = $parseDate($startsAt);
        $endDate = $parseDate($endsAt);
        if ($startDate === false || $endDate === false) {
            $errors[] = 'Enter valid availability dates.';
        } elseif ($startDate !== null && $endDate !== null && $startDate >= $endDate) {
            $errors[] = 'The end time must be after the start time.';
        }
        if ($errors) {
            api_response(['errors' => $errors], 422);
        }
        $insert = $pdo->prepare(
            'INSERT INTO exams (title, description, duration_minutes, starts_at, ends_at, created_by)
             VALUES (:title, :description, :duration, :starts_at, :ends_at, :created_by)'
        );
        $insert->execute([
            'title' => $title, 'description' => $description === '' ? null : $description,
            'duration' => (int) $duration, 'starts_at' => $startDate, 'ends_at' => $endDate,
            'created_by' => $adminId,
        ]);
        api_response(['exam_id' => (int) $pdo->lastInsertId()], 201);

    case 'admin/questions':
        api_require_method('GET');
        api_require_role('admin');
        $examId = api_int($_GET['id'] ?? null);
        $exam = api_exam($examId);
        $attemptQuery = $pdo->prepare('SELECT COUNT(*) FROM attempts WHERE exam_id = :exam_id');
        $attemptQuery->execute(['exam_id' => $examId]);
        $questionQuery = $pdo->prepare(
            'SELECT q.id, q.question_text, q.marks, q.position, o.option_text, o.is_correct, o.position AS option_position
             FROM questions q JOIN question_options o ON o.question_id = q.id
             WHERE q.exam_id = :exam_id ORDER BY q.position, o.position'
        );
        $questionQuery->execute(['exam_id' => $examId]);
        $questions = [];
        foreach ($questionQuery->fetchAll() as $row) {
            $questionId = (int) $row['id'];
            if (!isset($questions[$questionId])) {
                $questions[$questionId] = [
                    'text' => $row['question_text'], 'marks' => (int) $row['marks'], 'options' => [],
                ];
            }
            $questions[$questionId]['options'][] = [
                'text' => $row['option_text'], 'correct' => (bool) $row['is_correct'],
            ];
        }
        api_response([
            'exam' => $exam, 'questions' => array_values($questions),
            'editable' => $exam['status'] === 'draft' && (int) $attemptQuery->fetchColumn() === 0,
        ]);

    case 'admin/question/create':
        api_require_method('POST');
        api_require_role('admin');
        api_require_csrf();
        $input = api_input();
        $examId = api_int($input['exam_id'] ?? null);
        if ($examId < 1) {
            api_response(['error' => 'Invalid exam.'], 400);
        }
        $text = trim(is_string($input['question_text'] ?? null) ? $input['question_text'] : '');
        $marks = api_int($input['marks'] ?? null);
        $options = $input['options'] ?? null;
        $correct = api_int($input['correct_option'] ?? null);
        $errors = [];
        if ($text === '' || mb_strlen($text) > 10000) {
            $errors[] = 'Enter a question (up to 10,000 characters).';
        }
        if ($marks < 1 || $marks > 1000) {
            $errors[] = 'Marks must be between 1 and 1,000.';
        }
        if (!is_array($options) || count($options) < 2 || count($options) > 6) {
            $errors[] = 'Provide between 2 and 6 answer choices.';
        } else {
            foreach ($options as $option) {
                if (!is_string($option) || trim($option) === '' || mb_strlen(trim($option)) > 500) {
                    $errors[] = 'Each answer choice is required and must be at most 500 characters.';
                    break;
                }
            }
            if (!array_key_exists($correct, $options)) {
                $errors[] = 'Select the correct answer.';
            }
        }
        if ($errors) {
            api_response(['errors' => $errors], 422);
        }
        $pdo->beginTransaction();
        try {
            // Serialize question creation with publishing by locking the same exam row.
            $examLock = $pdo->prepare('SELECT status FROM exams WHERE id = :exam_id FOR UPDATE');
            $examLock->execute(['exam_id' => $examId]);
            $lockedExam = $examLock->fetch();
            if (!$lockedExam) {
                $pdo->rollBack();
                api_response(['error' => 'Exam not found.'], 404);
            }
            $attemptQuery = $pdo->prepare('SELECT COUNT(*) FROM attempts WHERE exam_id = :exam_id');
            $attemptQuery->execute(['exam_id' => $examId]);
            if ($lockedExam['status'] !== 'draft' || (int) $attemptQuery->fetchColumn() > 0) {
                $pdo->rollBack();
                api_response(['error' => 'Questions cannot be changed after the exam is published or has attempts.'], 409);
            }
            $positionQuery = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM questions WHERE exam_id = :exam_id');
            $positionQuery->execute(['exam_id' => $examId]);
            $insertQuestion = $pdo->prepare(
                'INSERT INTO questions (exam_id, question_text, marks, position)
                 VALUES (:exam_id, :question_text, :marks, :position)'
            );
            $insertQuestion->execute([
                'exam_id' => $examId, 'question_text' => $text, 'marks' => $marks,
                'position' => (int) $positionQuery->fetchColumn(),
            ]);
            $questionId = (int) $pdo->lastInsertId();
            $insertOption = $pdo->prepare(
                'INSERT INTO question_options (question_id, option_text, is_correct, position)
                 VALUES (:question_id, :option_text, :is_correct, :position)'
            );
            foreach ($options as $index => $option) {
                $insertOption->execute([
                    'question_id' => $questionId, 'option_text' => trim($option),
                    'is_correct' => (int) ($index === $correct), 'position' => $index + 1,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        api_response(['created' => true], 201);

    case 'admin/exam/publish':
        api_require_method('POST');
        api_require_role('admin');
        api_require_csrf();
        $examId = api_int(api_input()['exam_id'] ?? null);
        if ($examId < 1) {
            api_response(['error' => 'Invalid exam.'], 400);
        }
        $pdo->beginTransaction();
        try {
            // Question creation and publishing both lock this row, preventing a publish/create race.
            $examLock = $pdo->prepare('SELECT status FROM exams WHERE id = :id FOR UPDATE');
            $examLock->execute(['id' => $examId]);
            $exam = $examLock->fetch();
            if (!$exam) {
                $pdo->rollBack();
                api_response(['error' => 'Exam not found.'], 404);
            }
            if ($exam['status'] === 'draft') {
                $invalid = $pdo->prepare(
                    'SELECT q.id
                     FROM questions q
                     LEFT JOIN question_options o ON o.question_id = q.id
                     WHERE q.exam_id = :exam_id
                     GROUP BY q.id
                     HAVING SUM(CASE WHEN o.is_correct = 1 THEN 1 ELSE 0 END) <> 1'
                );
                $invalid->execute(['exam_id' => $examId]);
                if ($invalid->fetchColumn() !== false) {
                    $pdo->rollBack();
                    api_response(['error' => 'Every question must have exactly one correct answer before publishing.'], 409);
                }
                $questionCount = $pdo->prepare('SELECT COUNT(*) FROM questions WHERE exam_id = :exam_id');
                $questionCount->execute(['exam_id' => $examId]);
                if ((int) $questionCount->fetchColumn() < 1) {
                    $pdo->rollBack();
                    api_response(['error' => 'Add at least one question before publishing.'], 409);
                }
                $update = $pdo->prepare("UPDATE exams SET status = 'published' WHERE id = :id");
                $update->execute(['id' => $examId]);
                $pdo->commit();
                api_response(['status' => 'published']);
            }
            $attempts = $pdo->prepare("SELECT COUNT(*) FROM attempts WHERE exam_id = :exam_id AND status = 'in_progress'");
            $attempts->execute(['exam_id' => $examId]);
            if ((int) $attempts->fetchColumn() > 0) {
                $pdo->rollBack();
                api_response(['error' => 'This exam cannot be unpublished while students have attempts in progress.'], 409);
            }
            $update = $pdo->prepare("UPDATE exams SET status = 'draft' WHERE id = :id");
            $update->execute(['id' => $examId]);
            $pdo->commit();
            api_response(['status' => 'draft']);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

    case 'admin/results':
        api_require_method('GET');
        api_require_role('admin');
        $query = $pdo->query(
            "SELECT a.id, a.score, a.submitted_at, e.title, u.name AS student_name, u.email,
                    (SELECT COALESCE(SUM(q.marks), 0) FROM questions q WHERE q.exam_id = e.id) AS total_marks
             FROM attempts a JOIN exams e ON e.id = a.exam_id JOIN users u ON u.id = a.student_id
             WHERE a.status = 'submitted' ORDER BY a.submitted_at DESC"
        );
        api_response(['results' => $query->fetchAll()]);

    default:
        api_response(['error' => 'Unknown API action.'], 404);
}
