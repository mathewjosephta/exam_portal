<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../config/exams.php';
require_role('student');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Submit the exam using the exam form.');
}
verify_csrf($_POST['csrf_token'] ?? null);
$attemptId = filter_input(INPUT_POST, 'attempt_id', FILTER_VALIDATE_INT);
if (!$attemptId) {
    http_response_code(400);
    exit('Invalid exam attempt.');
}
$attemptQuery = $pdo->prepare('SELECT status FROM attempts WHERE id = :id AND student_id = :student_id');
$attemptQuery->execute(['id' => $attemptId, 'student_id' => $_SESSION['user_id']]);
$attempt = $attemptQuery->fetch();
if (!$attempt) {
    http_response_code(404);
    exit('Exam attempt not found.');
}
if ($attempt['status'] === 'in_progress') {
    submit_attempt($pdo, $attemptId, (int) $_SESSION['user_id']);
    flash('success', 'Your exam has been submitted.');
}
redirect('student/view-result.php?id=' . $attemptId);
