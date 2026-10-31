<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

function json_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    json_response(405, ['error' => 'Method not allowed.']);
}

if (!is_logged_in() || ($_SESSION['role'] ?? '') !== 'student'
    || ($_SESSION['status'] ?? 'active') !== 'active') {
    json_response(401, ['error' => 'Please sign in again.']);
}

try {
    $account = db()->prepare(
        'SELECT status FROM users WHERE id = :id AND role = "student" LIMIT 1'
    );
    $account->execute([':id' => (int) $_SESSION['user_id']]);
    if ($account->fetchColumn() !== 'active') {
        json_response(401, ['error' => 'Please sign in again.']);
    }
} catch (Throwable $exception) {
    error_log('Answer autosave account check failed: ' . $exception->getMessage());
    json_response(500, ['error' => 'Unable to save your answer right now.']);
}

$submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$storedToken = $_SESSION['csrf_token'] ?? '';
if (!is_string($submittedToken) || !is_string($storedToken)
    || $storedToken === '' || !hash_equals($storedToken, $submittedToken)) {
    json_response(403, ['error' => 'Your session expired. Reload the exam and try again.']);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    json_response(400, ['error' => 'Invalid request data.']);
}

$attemptId = safe_int($input['attempt_id'] ?? null);
$questionId = safe_int($input['question_id'] ?? null);
$selected = $input['selected_option'] ?? null;

if ($attemptId <= 0 || $questionId <= 0
    || !is_string($selected) || !in_array($selected, ['A', 'B', 'C', 'D'], true)) {
    json_response(400, ['error' => 'Choose a valid answer.']);
}

try {
    $attemptStmt = db()->prepare(
        'SELECT a.id, a.status, a.started_at, e.duration_minutes, e.end_time
         FROM attempts a
         JOIN exams e ON e.id = a.exam_id
         WHERE a.id = :attempt_id AND a.student_id = :student_id LIMIT 1'
    );
    $attemptStmt->execute([
        ':attempt_id' => $attemptId,
        ':student_id' => (int) $_SESSION['user_id'],
    ]);
    $attempt = $attemptStmt->fetch(PDO::FETCH_ASSOC);

    if (!$attempt || $attempt['status'] !== 'in_progress') {
        json_response(409, ['error' => 'This exam is no longer in progress.']);
    }

    if (time() > attempt_deadline($attempt)) {
        complete_attempt($attemptId, (int) $_SESSION['user_id']);
        json_response(410, ['error' => 'Your exam time has expired.']);
    }

    $validQuestion = db()->prepare(
        'SELECT id FROM questions
         WHERE id = :question_id
           AND exam_id = (SELECT exam_id FROM attempts WHERE id = :attempt_id)'
    );
    $validQuestion->execute([
        ':question_id' => $questionId,
        ':attempt_id' => $attemptId,
    ]);
    if (!$validQuestion->fetchColumn()) {
        json_response(400, ['error' => 'That question is not part of this exam.']);
    }

    $save = db()->prepare(
        'INSERT INTO answers (attempt_id, question_id, selected_option)
         VALUES (:attempt_id, :question_id, :selected_option)
         ON DUPLICATE KEY UPDATE selected_option = VALUES(selected_option)'
    );
    $save->execute([
        ':attempt_id' => $attemptId,
        ':question_id' => $questionId,
        ':selected_option' => $selected,
    ]);

    json_response(200, ['saved' => true]);
} catch (Throwable $exception) {
    error_log('Answer autosave failed: ' . $exception->getMessage());
    json_response(500, ['error' => 'Your answer could not be saved. Please try again.']);
}
