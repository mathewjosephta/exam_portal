<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();

$attemptId = safe_int($_GET['id'] ?? 0);
if ($attemptId <= 0) {
    flash('error', 'Attempt not found.');
    redirect('student/exams.php');
}

$stmt = db()->prepare('SELECT a.*, e.title, e.description, e.duration_minutes, e.total_marks, e.end_time, e.status FROM attempts a JOIN exams e ON e.id = a.exam_id WHERE a.id = :id AND a.student_id = :student_id LIMIT 1');
$stmt->execute([':id' => $attemptId, ':student_id' => $_SESSION['user_id']]);
$attempt = $stmt->fetch();
if (!$attempt) {
    flash('error', 'Invalid attempt.');
    redirect('student/exams.php');
}
if ($attempt['status'] !== 'in_progress') {
    flash('error', 'This attempt is already submitted.');
    redirect('student/results.php');
}
$exam = get_exam_by_id((int) $attempt['exam_id']);
if (!$exam || $exam['status'] !== 'published') {
    flash('error', 'This exam is not available.');
    redirect('student/exams.php');
}
$endTime = new DateTimeImmutable($exam['end_time'], new DateTimeZone('UTC'));
if (new DateTimeImmutable('now', new DateTimeZone('UTC')) > $endTime) {
    db()->prepare('UPDATE attempts SET status = "submitted", submitted_at = NOW(), score = 0 WHERE id = :id')->execute([':id' => $attemptId]);
    flash('error', 'The exam deadline has passed.');
    redirect('student/results.php');
}

$questions = db()->prepare('SELECT * FROM questions WHERE exam_id = :exam_id ORDER BY id ASC');
$questions->execute([':exam_id' => $exam['id']]);
$questions = $questions->fetchAll();
$remainingSeconds = max(0, (int) $endTime->format('U') - (int) (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('U'));

$title = 'Take exam';
include __DIR__ . '/../includes/header.php';
?>
<div class="exam-page">
    <div class="exam-header">
        <div>
            <h1><?= e($exam['title']) ?></h1>
            <p><?= e($exam['description']) ?></p>
        </div>
        <div id="countdown" class="timer-box" data-deadline="<?= e($remainingSeconds) ?>">00:00</div>
    </div>
    <form id="exam-form" method="post" action="<?= e(app_url('student/submit-exam.php')) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="attempt_id" value="<?= e($attemptId) ?>">
        <input type="hidden" name="exam_id" value="<?= e($exam['id']) ?>">
        <?php foreach ($questions as $index => $question): ?>
            <?php
            $selected = db()->prepare('SELECT selected_option FROM answers WHERE attempt_id = :attempt_id AND question_id = :question_id LIMIT 1');
            $selected->execute([':attempt_id' => $attemptId, ':question_id' => $question['id']]);
            $selectedOption = $selected->fetch()['selected_option'] ?? '';
            ?>
            <div class="question-card" data-index="<?= e($index) ?>" style="display: <?= $index === 0 ? 'block' : 'none' ?>;">
                <div class="question-top">Question <?= e($index + 1) ?> / <?= e(count($questions)) ?></div>
                <h3><?= e($question['question_text']) ?></h3>
                <?php foreach (['A','B','C','D'] as $opt): ?>
                    <?php $optionText = $question['option_a']; if ($opt === 'B') $optionText = $question['option_b']; if ($opt === 'C') $optionText = $question['option_c']; if ($opt === 'D') $optionText = $question['option_d']; ?>
                    <label class="answer-option">
                        <input type="radio" name="answers[<?= e($question['id']) ?>]" value="<?= e($opt) ?>" <?= $selectedOption === $opt ? 'checked' : '' ?>>
                        <span><?= e($opt) ?>. <?= e($optionText) ?></span>
                    </label>
                <?php endforeach; ?>
                <div class="question-nav">
                    <button type="button" class="button button-secondary prev-question" <?= $index === 0 ? 'disabled' : '' ?>>Previous</button>
                    <?php if ($index < count($questions) - 1): ?>
                        <button type="button" class="button next-question">Next</button>
                    <?php else: ?>
                        <button type="submit" class="button">Submit exam</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </form>
</div>
<script>
const cards = [...document.querySelectorAll('.question-card')];
let current = 0;
function showQuestion(index) {
    cards.forEach((card, idx) => card.style.display = idx === index ? 'block' : 'none');
    document.querySelectorAll('.prev-question').forEach((btn, idx) => { btn.disabled = idx === 0 && current === 0; });
}

document.querySelectorAll('.next-question').forEach((btn) => {
    btn.addEventListener('click', () => {
        if (current < cards.length - 1) {
            current += 1;
            showQuestion(current);
        }
    });
});
document.querySelectorAll('.prev-question').forEach((btn) => {
    btn.addEventListener('click', () => {
        if (current > 0) {
            current -= 1;
            showQuestion(current);
        }
    });
});
</script>
<script defer src="<?= e(app_url('assets/js/exam-timer.js')) ?>"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
