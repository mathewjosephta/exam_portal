<?php
declare(strict_types=1);
<<<<<<< HEAD
require __DIR__ . '/../legacy-page.php';
=======

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();

$attemptId = safe_int($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT a.id, a.exam_id, a.student_id, a.started_at, a.status,
            e.title, e.description, e.duration_minutes, e.total_marks, e.end_time
     FROM attempts a
     JOIN exams e ON e.id = a.exam_id
     WHERE a.id = :attempt_id AND a.student_id = :student_id LIMIT 1'
);
$stmt->execute([
    ':attempt_id' => $attemptId,
    ':student_id' => (int) $_SESSION['user_id'],
]);
$attempt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attempt) {
    set_flash('error', 'That exam attempt could not be found.');
    redirect('student/exams.php');
}
if ($attempt['status'] !== 'in_progress') {
    redirect('student/view-result.php?id=' . $attemptId);
}

$deadline = attempt_deadline($attempt);
if ($deadline <= time()) {
    try {
        complete_attempt($attemptId, (int) $_SESSION['user_id']);
    } catch (Throwable $exception) {
        error_log('Expired exam completion failed: ' . $exception->getMessage());
        http_response_code(500);
        exit('Unable to complete this exam right now. Please try again.');
    }
    set_flash('success', 'Your exam time has ended. Your saved answers were submitted.');
    redirect('student/view-result.php?id=' . $attemptId);
}

$questionsStmt = db()->prepare(
    'SELECT q.id, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.marks,
            a.selected_option
     FROM questions q
     LEFT JOIN answers a
       ON a.question_id = q.id AND a.attempt_id = :attempt_id
     WHERE q.exam_id = :exam_id
     ORDER BY q.id'
);
$questionsStmt->execute([
    ':attempt_id' => $attemptId,
    ':exam_id' => (int) $attempt['exam_id'],
]);
$questions = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);

if (!$questions) {
    set_flash('error', 'This exam currently has no questions.');
    redirect('student/exams.php');
}

$pageTitle = 'Taking ' . $attempt['title'];
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<section class="exam-take-page">
    <div class="exam-header">
        <div>
            <a class="text-link" href="<?= e(app_url('student/exams.php')) ?>">← Exams</a>
            <h1><?= e($attempt['title']) ?></h1>
            <p><?= e($attempt['description']) ?></p>
        </div>
        <div class="exam-clock">
            <span>Time remaining</span>
            <strong id="exam-timer" data-deadline="<?= e($deadline) ?>">--:--</strong>
        </div>
    </div>
    <form
        id="exam-form"
        class="exam-body"
        method="post"
        action="<?= e(app_url('student/submit-exam.php')) ?>"
        data-attempt-id="<?= e($attemptId) ?>"
        data-csrf="<?= e(csrf_token()) ?>"
        data-save-url="<?= e(app_url('api/save-answer.php')) ?>"
    >
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="attempt_id" value="<?= e($attemptId) ?>">
        <p id="save-status" class="save-status" role="status">Your answers are saved when you move to another question.</p>
        <?php foreach ($questions as $index => $question): ?>
            <?php $questionId = (int) $question['id']; ?>
            <section class="question-card" data-question-index="<?= e($index) ?>">
                <div class="question-number">Question <?= e($index + 1) ?> of <?= e(count($questions)) ?> <span><?= e($question['marks']) ?> marks</span></div>
                <h2 class="question-text"><?= e($question['question_text']) ?></h2>
                <div class="answer-options">
                    <?php foreach (['A', 'B', 'C', 'D'] as $option): ?>
                        <?php $optionText = $question['option_' . strtolower($option)]; ?>
                        <label class="option-label">
                            <input
                                type="radio"
                                name="answers[<?= e($questionId) ?>]"
                                value="<?= e($option) ?>"
                                data-question-id="<?= e($questionId) ?>"
                                <?= $question['selected_option'] === $option ? 'checked' : '' ?>
                            >
                            <span><strong><?= e($option) ?>.</strong> <?= e($optionText) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="question-nav">
                    <button class="btn btn-outline prev-question" type="button" <?= $index === 0 ? 'disabled' : '' ?>>Previous</button>
                    <?php if ($index < count($questions) - 1): ?>
                        <button class="btn btn-primary next-question" type="button">Next question</button>
                    <?php else: ?>
                        <button class="btn btn-primary" id="submit-exam" type="submit">Submit exam</button>
                    <?php endif; ?>
                </div>
            </section>
        <?php endforeach; ?>
        <nav class="question-palette" aria-label="Question navigation">
            <?php foreach ($questions as $index => $_question): ?>
                <button type="button" class="question-jump" data-jump-index="<?= e($index) ?>" aria-label="Go to question <?= e($index + 1) ?>"><?= e($index + 1) ?></button>
            <?php endforeach; ?>
        </nav>
    </form>
</section>
<script src="<?= e(app_url('assets/js/exam.js')) ?>" defer></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
>>>>>>> 0ecac9e (Update exam portal and admin features)
