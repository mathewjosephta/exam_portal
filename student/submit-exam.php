<?php
declare(strict_types=1);
<<<<<<< HEAD
require __DIR__ . '/../legacy-page.php';
=======

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('student/exams.php');
}
verify_csrf();

$attemptId = safe_int($_POST['attempt_id'] ?? 0);
$check = db()->prepare(
    'SELECT status FROM attempts WHERE id = :id AND student_id = :student_id LIMIT 1'
);
$check->execute([
    ':id' => $attemptId,
    ':student_id' => (int) $_SESSION['user_id'],
]);
$attempt = $check->fetch(PDO::FETCH_ASSOC);

if (!$attempt) {
    set_flash('error', 'That exam attempt could not be found.');
    redirect('student/exams.php');
}

if ($attempt['status'] === 'submitted') {
    redirect('student/view-result.php?id=' . $attemptId);
}

$submittedAnswers = $_POST['answers'] ?? [];
if (!is_array($submittedAnswers)) {
    $submittedAnswers = [];
}

try {
    complete_attempt($attemptId, (int) $_SESSION['user_id'], $submittedAnswers);
} catch (Throwable $exception) {
    error_log('Exam submission failed: ' . $exception->getMessage());
    set_flash('error', 'Unable to submit your exam right now. Please try again.');
    redirect('student/take-exam.php?id=' . $attemptId);
}

set_flash('success', 'Your exam was submitted successfully.');
redirect('student/view-result.php?id=' . $attemptId);
>>>>>>> 0ecac9e (Update exam portal and admin features)
