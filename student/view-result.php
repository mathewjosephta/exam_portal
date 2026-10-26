<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../config/exams.php';
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    flash('error', 'Please sign in to view exam results.');
    redirect('login.php');
}
if (!in_array($_SESSION['role'], ['student', 'admin'], true)) {
    http_response_code(403);
    exit('You do not have permission to view this result.');
}

$attemptId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$attemptId) {
    http_response_code(404);
    exit('Exam result not found.');
}
$attemptQuery = $pdo->prepare(
    "SELECT a.id, a.status, a.score, a.submitted_at, a.student_id, e.id AS exam_id, e.title,
            (SELECT COALESCE(SUM(q.marks), 0) FROM questions q WHERE q.exam_id = e.id) AS total_marks
     FROM attempts a JOIN exams e ON e.id = a.exam_id
     WHERE a.id = :id"
);
$attemptQuery->execute(['id' => $attemptId]);
$attempt = $attemptQuery->fetch();
if (!$attempt || ($_SESSION['role'] === 'student' && (int) $attempt['student_id'] !== (int) $_SESSION['user_id'])) {
    http_response_code(404);
    exit('Exam result not found.');
}
if ($attempt['status'] !== 'submitted') {
    redirect('student/take-exam.php?id=' . $attemptId);
}

$answerQuery = $pdo->prepare(
    'SELECT q.question_text, q.marks, a.selected_option_id,
            selected.option_text AS selected_text,
            correct.option_text AS correct_text,
            (selected.id IS NOT NULL AND selected.is_correct = 1) AS is_correct
     FROM questions q
     LEFT JOIN answers a ON a.question_id = q.id AND a.attempt_id = :attempt_id
     LEFT JOIN question_options selected ON selected.id = a.selected_option_id
     LEFT JOIN question_options correct ON correct.question_id = q.id AND correct.is_correct = 1
     WHERE q.exam_id = :exam_id
     ORDER BY q.position'
);
$answerQuery->execute(['attempt_id' => $attemptId, 'exam_id' => $attempt['exam_id']]);
$answers = $answerQuery->fetchAll();

render_header('Exam result');
?>
<div class="page-heading">
    <div><p class="eyebrow">RESULT</p><h1><?= e($attempt['title']) ?></h1><p class="muted">Submitted <?= e(format_datetime($attempt['submitted_at'])) ?></p></div>
    <div class="score-card"><strong><?= (int) $attempt['score'] ?> / <?= (int) $attempt['total_marks'] ?></strong><span>points</span></div>
</div>
<section class="section-block">
    <h2>Answer review</h2>
    <?php foreach ($answers as $index => $answer): ?>
        <article class="review-card">
            <div class="review-heading"><h3>Question <?= $index + 1 ?></h3><span class="<?= $answer['is_correct'] ? 'text-success' : 'text-error' ?>"><?= $answer['is_correct'] ? 'Correct' : 'Incorrect' ?></span></div>
            <p><?= nl2br(e($answer['question_text'])) ?></p>
            <p class="muted">Your answer: <strong><?= e($answer['selected_text'] ?? 'Not answered') ?></strong></p>
            <?php if (!$answer['is_correct']): ?><p class="muted">Correct answer: <strong><?= e($answer['correct_text'] ?? 'Not available') ?></strong></p><?php endif; ?>
            <p class="meta"><?= (int) $answer['marks'] ?> <?= (int) $answer['marks'] === 1 ? 'mark' : 'marks' ?></p>
        </article>
    <?php endforeach; ?>
</section>
<a class="button button-outline" href="<?= e(app_url($_SESSION['role'] === 'admin' ? 'admin/results.php' : 'student/results.php')) ?>">Back to results</a>
<?php render_footer(); ?>
