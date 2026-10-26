<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../config/exams.php';
require_role('student');

$attemptId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$attemptId) {
    http_response_code(404);
    exit('Exam attempt not found.');
}
$query = $pdo->prepare(
    'SELECT a.id, a.status, a.started_at, e.id AS exam_id, e.title, e.duration_minutes
     FROM attempts a JOIN exams e ON e.id = a.exam_id
     WHERE a.id = :id AND a.student_id = :student_id'
);
$query->execute(['id' => $attemptId, 'student_id' => $_SESSION['user_id']]);
$attempt = $query->fetch();
if (!$attempt) {
    http_response_code(404);
    exit('Exam attempt not found.');
}
if ($attempt['status'] === 'submitted') {
    redirect('student/view-result.php?id=' . $attemptId);
}

$timeLimit = (int) $attempt['duration_minutes'] * 60;
$elapsed = time() - strtotime($attempt['started_at']);
$secondsLeft = max(0, $timeLimit - $elapsed);
if ($secondsLeft === 0) {
    submit_attempt($pdo, $attemptId, (int) $_SESSION['user_id']);
    flash('success', 'Your time expired. The exam has been submitted.');
    redirect('student/view-result.php?id=' . $attemptId);
}

$questionQuery = $pdo->prepare(
    'SELECT q.id, q.question_text, q.marks, q.position,
            o.id AS option_id, o.option_text, o.position AS option_position,
            a.selected_option_id
     FROM questions q
     JOIN question_options o ON o.question_id = q.id
     LEFT JOIN answers a ON a.question_id = q.id AND a.attempt_id = :attempt_id
     WHERE q.exam_id = :exam_id
     ORDER BY q.position, o.position'
);
$questionQuery->execute(['attempt_id' => $attemptId, 'exam_id' => $attempt['exam_id']]);
$rows = $questionQuery->fetchAll();
$questions = [];
foreach ($rows as $row) {
    $questionId = (int) $row['id'];
    if (!isset($questions[$questionId])) {
        $questions[$questionId] = [
            'id' => $questionId,
            'text' => $row['question_text'],
            'marks' => (int) $row['marks'],
            'options' => [],
            'selected_option_id' => $row['selected_option_id'] === null ? null : (int) $row['selected_option_id'],
        ];
    }
    $questions[$questionId]['options'][] = [
        'id' => (int) $row['option_id'],
        'text' => $row['option_text'],
    ];
}
if (!$questions) {
    http_response_code(409);
    exit('This exam currently has no questions. Contact the exam administrator.');
}

render_header($attempt['title']);
?>
<div class="exam-topbar">
    <div><p class="eyebrow">IN PROGRESS</p><h1><?= e($attempt['title']) ?></h1></div>
    <div class="timer" id="exam-timer" data-seconds="<?= $secondsLeft ?>" aria-live="polite">--:--</div>
</div>
<p class="muted">Your answers save automatically when you select an option. Submit when you are finished.</p>
<div id="save-status" class="save-status" role="status" aria-live="polite"></div>
<form id="exam-form" method="post" action="<?= e(app_url('student/submit-exam.php')) ?>"
      data-save-url="<?= e(app_url('api/save-answer.php')) ?>"
      data-csrf="<?= e(csrf_token()) ?>">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="attempt_id" value="<?= $attemptId ?>">
    <?php $number = 0; foreach ($questions as $question): $number++; ?>
        <fieldset class="question-card">
            <legend><span class="question-number">Question <?= $number ?></span><?= nl2br(e($question['text'])) ?></legend>
            <p class="muted"><?= (int) $question['marks'] ?> <?= (int) $question['marks'] === 1 ? 'mark' : 'marks' ?></p>
            <?php foreach ($question['options'] as $option): ?>
                <label class="option">
                    <input type="radio" name="question_<?= $question['id'] ?>" value="<?= $option['id'] ?>"
                        data-question-id="<?= $question['id'] ?>"
                        <?= $question['selected_option_id'] === $option['id'] ? 'checked' : '' ?>>
                    <span><?= e($option['text']) ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>
    <?php endforeach; ?>
    <div class="submit-row">
        <span class="muted"><?= count($questions) ?> questions</span>
        <button class="button" id="submit-exam" type="submit">Submit exam</button>
    </div>
</form>
<script src="<?= e(app_url('assets/js/exam.js')) ?>" defer></script>
<?php render_footer(); ?>
