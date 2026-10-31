<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('student/exams.php');
}
verify_csrf((string) ($_POST['csrf_token'] ?? ''));

$attemptId = safe_int($_POST['attempt_id'] ?? 0);
$examId = safe_int($_POST['exam_id'] ?? 0);
if ($attemptId <= 0 || $examId <= 0) {
    flash('error', 'Attempt data is invalid.');
    redirect('student/exams.php');
}
$attempt = db()->prepare('SELECT a.*, e.end_time FROM attempts a JOIN exams e ON e.id = a.exam_id WHERE a.id = :id AND a.student_id = :student_id LIMIT 1');
$attempt->execute([':id' => $attemptId, ':student_id' => $_SESSION['user_id']]);
$attempt = $attempt->fetch();
if (!$attempt || $attempt['status'] !== 'in_progress') {
    flash('error', 'This attempt is not active.');
    redirect('student/results.php');
}
$end = new DateTimeImmutable($attempt['end_time'], new DateTimeZone('UTC'));
if ((new DateTimeImmutable('now', new DateTimeZone('UTC'))) > $end) {
    flash('error', 'The exam deadline has passed.');
    redirect('student/results.php');
}

$db = db();
$db->beginTransaction();
try {
    $questions = $db->prepare('SELECT * FROM questions WHERE exam_id = :exam_id');
    $questions->execute([':exam_id' => $examId]);
    $questions = $questions->fetchAll();
    $score = 0;
    foreach ($questions as $question) {
        $selected = $_POST['answers'][$question['id']] ?? null;
        $selected = in_array($selected, ['A','B','C','D'], true) ? $selected : null;
        $isCorrect = $selected === $question['correct_option'];
        $marks = $isCorrect ? (int) $question['marks'] : 0;
        $score += $marks;
        $db->prepare('INSERT INTO answers (attempt_id, question_id, selected_option, is_correct, marks_obtained) VALUES (:attempt_id, :question_id, :selected_option, :is_correct, :marks_obtained) ON DUPLICATE KEY UPDATE selected_option = VALUES(selected_option), is_correct = VALUES(is_correct), marks_obtained = VALUES(marks_obtained), updated_at = NOW()')->execute([
            ':attempt_id' => $attemptId,
            ':question_id' => $question['id'],
            ':selected_option' => $selected,
            ':is_correct' => $isCorrect ? 1 : 0,
            ':marks_obtained' => $marks,
        ]);
    }
    $db->prepare('UPDATE attempts SET score = :score, submitted_at = NOW(), status = "submitted" WHERE id = :id AND status = "in_progress"')->execute([':score' => $score, ':id' => $attemptId]);
    $db->commit();
    flash('success', 'Exam submitted successfully.');
    redirect('student/results.php');
} catch (Throwable $e) {
    $db->rollBack();
    error_log('Exam submission failed: ' . $e->getMessage());
    flash('error', 'Failed to submit exam.');
    redirect('student/exams.php');
}
