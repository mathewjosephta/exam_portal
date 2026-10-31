<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();
require_once __DIR__ . '/../includes/student-sidebar.php';

$attemptId = safe_int($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT a.*, e.title AS exam_title, e.total_marks FROM attempts a JOIN exams e ON e.id = a.exam_id WHERE a.id = :id AND a.student_id = :student_id LIMIT 1');
$stmt->execute([':id' => $attemptId, ':student_id' => $_SESSION['user_id']]);
$attempt = $stmt->fetch();
if (!$attempt) {
    flash('error', 'Result not found.');
    redirect('student/results.php');
}
$answers = db()->prepare('SELECT ans.*, q.question_text, q.correct_option, q.marks FROM answers ans JOIN questions q ON q.id = ans.question_id WHERE ans.attempt_id = :attempt_id ORDER BY q.id');
$answers->execute([':attempt_id' => $attemptId]);
$answers = $answers->fetchAll();

$title = 'Result details';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_student_sidebar() ?>
    <main class="main-panel">
        <div class="topbar"><h1><?= e($attempt['exam_title']) ?></h1></div>
        <section class="panel-card">
            <p><strong>Final score:</strong> <?= e($attempt['score']) ?> / <?= e($attempt['total_marks']) ?></p>
            <p><strong>Submitted:</strong> <?= e($attempt['submitted_at']) ?></p>
        </section>
        <section class="panel-card">
            <table>
                <thead><tr><th>Question</th><th>Your answer</th><th>Correct</th><th>Marks</th></tr></thead>
                <tbody>
                <?php foreach ($answers as $answer): ?>
                    <tr>
                        <td><?= e($answer['question_text']) ?></td>
                        <td><?= e($answer['selected_option'] ?: 'No answer') ?></td>
                        <td><?= e($answer['correct_option']) ?></td>
                        <td><?= e($answer['marks_obtained']) ?> / <?= e($answer['marks']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
