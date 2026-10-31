<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();
require_once __DIR__ . '/../includes/student-sidebar.php';

$examId = safe_int($_GET['id'] ?? 0);
$exam = get_exam_by_id($examId);
if (!$exam) {
    flash('error', 'Exam not found.');
    redirect('student/exams.php');
}
if ($exam['status'] !== 'published') {
    flash('error', 'The exam is not available.');
    redirect('student/exams.php');
}
if (!exam_is_available($exam)) {
    flash('error', 'Exam is not available at this time.');
    redirect('student/exams.php');
}

$existing = db()->prepare('SELECT * FROM attempts WHERE student_id = :student_id AND exam_id = :exam_id LIMIT 1');
$existing->execute([':student_id' => $_SESSION['user_id'], ':exam_id' => $examId]);
$existing = $existing->fetch();
if ($existing && $existing['status'] === 'submitted') {
    flash('error', 'You already submitted this exam.');
    redirect('student/results.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf((string) ($_POST['csrf_token'] ?? ''));
    if ($existing) {
        redirect('student/take-exam.php?id=' . $existing['id']);
    }
    db()->prepare('INSERT INTO attempts (student_id, exam_id, started_at, created_at, status) VALUES (:student_id, :exam_id, NOW(), NOW(), "in_progress")')->execute([
        ':student_id' => $_SESSION['user_id'],
        ':exam_id' => $examId,
    ]);
    $id = (int) db()->lastInsertId();
    redirect('student/take-exam.php?id=' . $id);
}

$questionCount = count_questions_for_exam($examId);
$title = 'Exam instructions';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_student_sidebar() ?>
    <main class="main-panel">
        <div class="topbar"><h1><?= e($exam['title']) ?></h1></div>
        <section class="panel-card exam-instructions">
            <h2>Instructions</h2>
            <ul>
                <li>Duration: <?= e($exam['duration_minutes']) ?> minutes</li>
                <li>Total marks: <?= e($exam['total_marks']) ?></li>
                <li>Questions: <?= e($questionCount) ?></li>
                <li>Window: <?= e($exam['start_time']) ?> to <?= e($exam['end_time']) ?></li>
            </ul>
            <p>Please read carefully before starting.</p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label><input type="checkbox" name="agree" required> I agree to begin this exam.</label>
                <button class="button" type="submit">Start exam</button>
            </form>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
