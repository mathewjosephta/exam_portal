<?php
declare(strict_types=1);
<<<<<<< HEAD
require __DIR__ . '/../legacy-page.php';
=======

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();
require_once __DIR__ . '/../includes/student-sidebar.php';

$attemptId = safe_int($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT a.id, a.score, a.status, a.submitted_at, e.title, e.total_marks
     FROM attempts a
     JOIN exams e ON e.id = a.exam_id
     WHERE a.id = :attempt_id AND a.student_id = :student_id LIMIT 1'
);
$stmt->execute([
    ':attempt_id' => $attemptId,
    ':student_id' => (int) $_SESSION['user_id'],
]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result || $result['status'] !== 'submitted') {
    set_flash('error', 'That result could not be found.');
    redirect('student/results.php');
}

$answersStmt = db()->prepare(
    'SELECT q.question_text, q.option_a, q.option_b, q.option_c, q.option_d,
            q.correct_option, q.marks, a.selected_option, a.marks_obtained
     FROM answers a
     JOIN questions q ON q.id = a.question_id
     WHERE a.attempt_id = :attempt_id ORDER BY q.id'
);
$answersStmt->execute([':attempt_id' => $attemptId]);
$answers = $answersStmt->fetchAll(PDO::FETCH_ASSOC);
$percentage = (int) $result['total_marks'] > 0
    ? round(((int) $result['score'] / (int) $result['total_marks']) * 100, 1)
    : 0;

$pageTitle = 'Result details';
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_student_sidebar() ?>
    <section class="dashboard-main">
        <div class="dashboard-topbar">
            <div><h1><?= e($result['title']) ?></h1><p>Your answers and final score.</p></div>
            <a class="btn btn-outline" href="<?= e(app_url('student/results.php')) ?>">All results</a>
        </div>
        <div class="dashboard-content">
            <section class="card result-summary">
                <div><span>Final score</span><strong><?= e($result['score']) ?> / <?= e($result['total_marks']) ?></strong></div>
                <div><span>Percentage</span><strong><?= e($percentage) ?>%</strong></div>
                <div><span>Submitted</span><strong><?= e(date('M j, Y g:i A', strtotime($result['submitted_at']))) ?></strong></div>
            </section>
            <?php foreach ($answers as $index => $answer): ?>
                <?php
                $selectedText = $answer['selected_option']
                    ? $answer['option_' . strtolower($answer['selected_option'])]
                    : 'No answer selected';
                $correctText = $answer['option_' . strtolower($answer['correct_option'])];
                ?>
                <article class="card result-answer">
                    <p class="question-number">Question <?= e($index + 1) ?> · <?= e($answer['marks_obtained']) ?> / <?= e($answer['marks']) ?> marks</p>
                    <h2><?= e($answer['question_text']) ?></h2>
                    <p>Your answer: <strong><?= e($answer['selected_option'] ?: 'Not answered') ?></strong> — <?= e($selectedText) ?></p>
                    <p>Correct answer: <strong><?= e($answer['correct_option']) ?></strong> — <?= e($correctText) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
>>>>>>> 0ecac9e (Update exam portal and admin features)
