<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require_role('admin');

$examId = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);
if (!$examId) {
    redirect('admin/exams.php');
}
$examQuery = $pdo->prepare('SELECT id, title, status FROM exams WHERE id = :id');
$examQuery->execute(['id' => $examId]);
$exam = $examQuery->fetch();
if (!$exam) {
    http_response_code(404);
    exit('Exam not found.');
}
$attemptQuery = $pdo->prepare('SELECT COUNT(*) FROM attempts WHERE exam_id = :exam_id');
$attemptQuery->execute(['exam_id' => $examId]);
$hasAttempts = (int) $attemptQuery->fetchColumn() > 0;

$questionQuery = $pdo->prepare(
    'SELECT q.id, q.question_text, q.marks, q.position,
            o.option_text, o.is_correct, o.position AS option_position
     FROM questions q JOIN question_options o ON o.question_id = q.id
     WHERE q.exam_id = :exam_id ORDER BY q.position, o.position'
);
$questionQuery->execute(['exam_id' => $examId]);
$questions = [];
foreach ($questionQuery->fetchAll() as $row) {
    $questionId = (int) $row['id'];
    if (!isset($questions[$questionId])) {
        $questions[$questionId] = [
            'text' => $row['question_text'],
            'marks' => (int) $row['marks'],
            'options' => [],
        ];
    }
    $questions[$questionId]['options'][] = [
        'text' => $row['option_text'],
        'correct' => (bool) $row['is_correct'],
    ];
}

render_header('Exam questions');
?>
<div class="page-heading">
    <div><p class="eyebrow">EXAM QUESTIONS</p><h1><?= e($exam['title']) ?></h1><p class="muted"><?= count($questions) ?> questions · <?= e(ucfirst($exam['status'])) ?></p></div>
    <a class="button button-outline" href="<?= e(app_url('admin/exams.php')) ?>">Back to exams</a>
</div>
<?php if ($exam['status'] === 'draft' && !$hasAttempts): ?>
    <p><a class="button" href="<?= e(app_url('admin/add-question.php?exam_id=' . $examId)) ?>">Add a question</a></p>
<?php else: ?>
    <div class="notice notice-info">Questions are read-only after publishing or once an exam has attempts.</div>
<?php endif; ?>
<?php if (!$questions): ?>
    <div class="empty-state">No questions have been added to this exam.</div>
<?php else: foreach ($questions as $index => $question): ?>
    <article class="review-card">
        <h2>Question <?= $index + 1 ?> <span class="muted">· <?= $question['marks'] ?> <?= $question['marks'] === 1 ? 'mark' : 'marks' ?></span></h2>
        <p><?= nl2br(e($question['text'])) ?></p>
        <ul class="option-list"><?php foreach ($question['options'] as $option): ?>
            <li class="<?= $option['correct'] ? 'text-success' : '' ?>"><?= e($option['text']) ?><?= $option['correct'] ? ' (correct)' : '' ?></li>
        <?php endforeach; ?></ul>
    </article>
<?php endforeach; endif; ?>
<?php render_footer(); ?>
