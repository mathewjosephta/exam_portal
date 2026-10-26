<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../config/exams.php';
require_role('student');

$examId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$examId) {
    http_response_code(404);
    exit('Exam not found.');
}
$query = $pdo->prepare(
    'SELECT e.*, COUNT(q.id) AS question_count
     FROM exams e LEFT JOIN questions q ON q.exam_id = e.id
     WHERE e.id = :id GROUP BY e.id'
);
$query->execute(['id' => $examId]);
$exam = $query->fetch();
if (!$exam) {
    http_response_code(404);
    exit('Exam not found.');
}

$attemptQuery = $pdo->prepare(
    'SELECT id, status FROM attempts WHERE exam_id = :exam_id AND student_id = :student_id'
);
$attemptQuery->execute(['exam_id' => $examId, 'student_id' => $_SESSION['user_id']]);
$attempt = $attemptQuery->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    if ((!$attempt && !exam_is_open($exam)) || (int) $exam['question_count'] < 1) {
        flash('error', 'This exam is not currently available.');
        redirect('student/dashboard.php');
    }
    if (!$attempt) {
        $create = $pdo->prepare(
            'INSERT INTO attempts (exam_id, student_id)
             VALUES (:exam_id, :student_id)
             ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
        );
        $create->execute(['exam_id' => $examId, 'student_id' => $_SESSION['user_id']]);
        $attemptId = (int) $pdo->lastInsertId();
    } else {
        $attemptId = (int) $attempt['id'];
    }
    redirect('student/' . ($attempt && $attempt['status'] === 'submitted' ? 'view-result.php?id=' : 'take-exam.php?id=') . $attemptId);
}

render_header('Exam instructions');
?>
<section class="content-card narrow">
    <p class="eyebrow">EXAM INSTRUCTIONS</p>
    <h1><?= e($exam['title']) ?></h1>
    <p><?= nl2br(e($exam['description'] ?: 'Read each question carefully and submit your answers before the timer expires.')) ?></p>
    <p class="muted">Availability dates control when an exam can be started. Once started, you have the full time limit shown below.</p>
    <div class="info-grid">
        <div><span>Questions</span><strong><?= (int) $exam['question_count'] ?></strong></div>
        <div><span>Time allowed</span><strong><?= (int) $exam['duration_minutes'] ?> minutes</strong></div>
        <div><span>Available until</span><strong><?= e(format_datetime($exam['ends_at'])) ?></strong></div>
    </div>
    <?php if ($attempt && $attempt['status'] === 'submitted'): ?>
        <a class="button" href="<?= e(app_url('student/view-result.php?id=' . (int) $attempt['id'])) ?>">View submitted result</a>
    <?php elseif (!exam_is_open($exam)): ?>
        <div class="notice notice-error">This exam is not currently open.</div>
    <?php else: ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="button" type="submit"><?= $attempt ? 'Continue exam' : 'Start exam' ?></button>
        </form>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
