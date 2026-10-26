<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../config/exams.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'student') {
    http_response_code(401);
    echo json_encode(['error' => 'Please sign in again.']);
    exit;
}
$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($csrf) || !hash_equals(csrf_token(), $csrf)) {
    http_response_code(403);
    echo json_encode(['error' => 'Your session expired. Reload the exam and try again.']);
    exit;
}
$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data.']);
    exit;
}
$attemptId = filter_var($input['attempt_id'] ?? null, FILTER_VALIDATE_INT);
$questionId = filter_var($input['question_id'] ?? null, FILTER_VALIDATE_INT);
$optionId = filter_var($input['option_id'] ?? null, FILTER_VALIDATE_INT);
if (!$attemptId || !$questionId || !$optionId) {
    http_response_code(400);
    echo json_encode(['error' => 'Select a valid answer.']);
    exit;
}

$pdo->beginTransaction();
try {
    $attemptQuery = $pdo->prepare(
        "SELECT a.status, a.started_at, e.duration_minutes
         FROM attempts a JOIN exams e ON e.id = a.exam_id
         WHERE a.id = :id AND a.student_id = :student_id FOR UPDATE"
    );
    $attemptQuery->execute(['id' => $attemptId, 'student_id' => $_SESSION['user_id']]);
    $attempt = $attemptQuery->fetch();
    if (!$attempt || $attempt['status'] !== 'in_progress') {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['error' => 'This exam is no longer in progress.']);
        exit;
    }
    $secondsAllowed = (int) $attempt['duration_minutes'] * 60;
    if (time() - strtotime($attempt['started_at']) >= $secondsAllowed) {
        $pdo->rollBack();
        submit_attempt($pdo, (int) $attemptId, (int) $_SESSION['user_id']);
        http_response_code(410);
        echo json_encode(['error' => 'Your exam time has expired.']);
        exit;
    }

    $validOption = $pdo->prepare(
        'SELECT q.id
         FROM questions q
         JOIN question_options o ON o.question_id = q.id
         JOIN attempts a ON a.exam_id = q.exam_id
         WHERE a.id = :attempt_id AND q.id = :question_id AND o.id = :option_id'
    );
    $validOption->execute([
        'attempt_id' => $attemptId,
        'question_id' => $questionId,
        'option_id' => $optionId,
    ]);
    if (!$validOption->fetchColumn()) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'That answer is not part of this exam question.']);
        exit;
    }
    $save = $pdo->prepare(
        'INSERT INTO answers (attempt_id, question_id, selected_option_id)
         VALUES (:attempt_id, :question_id, :option_id)
         ON DUPLICATE KEY UPDATE selected_option_id = VALUES(selected_option_id)'
    );
    $save->execute([
        'attempt_id' => $attemptId,
        'question_id' => $questionId,
        'option_id' => $optionId,
    ]);
    $pdo->commit();
    echo json_encode(['saved' => true]);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}
