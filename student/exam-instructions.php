<?php
declare(strict_types=1);
<<<<<<< HEAD
require __DIR__ . '/../legacy-page.php';
=======

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();
require_once __DIR__ . '/../includes/student-sidebar.php';

$examId = safe_int($_GET['id'] ?? $_POST['exam_id'] ?? 0);
$exam = $examId > 0 ? get_exam_by_id($examId) : false;
if (!$exam || $exam['status'] !== 'published') {
    set_flash('error', 'That exam is not available.');
    redirect('student/exams.php');
}

$questionCount = count_questions_for_exam($examId);
$attemptStmt = db()->prepare(
    'SELECT id, status FROM attempts WHERE student_id = :student_id AND exam_id = :exam_id LIMIT 1'
);
$attemptStmt->execute([
    ':student_id' => (int) $_SESSION['user_id'],
    ':exam_id' => $examId,
]);
$attempt = $attemptStmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (($_POST['agree'] ?? '') !== '1') {
        set_flash('error', 'Please confirm that you have read the exam instructions.');
        redirect('student/exam-instructions.php?id=' . $examId);
    }

    if (!exam_is_available($exam) || $questionCount === 0) {
        set_flash('error', $questionCount === 0
            ? 'This exam does not have any questions yet.'
            : 'This exam is outside its scheduled availability window.');
        redirect('student/exams.php');
    }

    if (!$attempt) {
        $insert = db()->prepare(
            'INSERT INTO attempts (student_id, exam_id, started_at, status)
             VALUES (:student_id, :exam_id, NOW(), "in_progress")
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $insert->execute([
            ':student_id' => (int) $_SESSION['user_id'],
            ':exam_id' => $examId,
        ]);
        $attemptStmt->execute([
            ':student_id' => (int) $_SESSION['user_id'],
            ':exam_id' => $examId,
        ]);
        $attempt = $attemptStmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($attempt['status'] === 'submitted') {
        redirect('student/view-result.php?id=' . (int) $attempt['id']);
    }

    redirect('student/take-exam.php?id=' . (int) $attempt['id']);
}

$pageTitle = 'Exam instructions';
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_student_sidebar() ?>
    <section class="dashboard-main">
        <div class="dashboard-topbar">
            <div><h1><?= e($exam['title']) ?></h1><p>Review the details before you begin.</p></div>
        </div>
        <div class="dashboard-content">
            <section class="card instruction-card">
                <h2>Exam instructions</h2>
                <p><?= e($exam['description']) ?></p>
                <div class="card-grid instruction-stats">
                    <div><span>Duration</span><strong><?= e($exam['duration_minutes']) ?> minutes</strong></div>
                    <div><span>Questions</span><strong><?= e($questionCount) ?></strong></div>
                    <div><span>Total marks</span><strong><?= e($exam['total_marks']) ?></strong></div>
                    <div><span>Available until</span><strong><?= e(date('M j, Y g:i A', strtotime($exam['end_time']))) ?></strong></div>
                </div>
                <ul class="instruction-list">
                    <li>Your exam timer starts as soon as you begin.</li>
                    <li>Your attempt is submitted automatically when the time expires.</li>
                    <li>You can move between questions and change answers before submitting.</li>
                    <li>You can submit early using the final question's submit button.</li>
                </ul>
                <?php if ($attempt && $attempt['status'] === 'in_progress'): ?>
                    <a class="btn btn-primary" href="<?= e(app_url('student/take-exam.php?id=' . (int) $attempt['id'])) ?>">Resume exam</a>
                <?php elseif (!exam_is_available($exam)): ?>
                    <p class="text-muted">This exam is currently outside its scheduled window.</p>
                <?php elseif ($questionCount === 0): ?>
                    <p class="text-muted">This exam is not ready yet because it has no questions.</p>
                <?php else: ?>
                    <form method="post" class="start-exam-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="exam_id" value="<?= e($examId) ?>">
                        <label><input type="checkbox" name="agree" value="1" required> I have read and agree to the exam instructions.</label>
                        <button class="btn btn-primary" type="submit">Start exam <span aria-hidden="true">↗</span></button>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
>>>>>>> 0ecac9e (Update exam portal and admin features)
