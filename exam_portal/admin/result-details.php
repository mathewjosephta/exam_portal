<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

$attemptId = safe_int($_GET['attempt_id'] ?? 0);
$stmt = db()->prepare('SELECT a.*, u.name AS student_name, u.email, e.title AS exam_title, e.total_marks FROM attempts a JOIN users u ON u.id = a.student_id JOIN exams e ON e.id = a.exam_id WHERE a.id = :id LIMIT 1');
$stmt->execute([':id' => $attemptId]);
$attempt = $stmt->fetch();
if (!$attempt) {
    flash('error', 'Attempt not found.');
    redirect('admin/results.php');
}
$answers = db()->prepare('SELECT ans.*, q.question_text, q.correct_option, q.marks FROM answers ans JOIN questions q ON q.id = ans.question_id WHERE ans.attempt_id = :attempt_id ORDER BY q.id');
$answers->execute([':attempt_id' => $attemptId]);
$answers = $answers->fetchAll();

$title = 'Attempt details';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <main class="main-panel">
        <div class="topbar"><h1>Attempt details</h1></div>
        <section class="panel-card">
            <p><strong>Student:</strong> <?= e($attempt['student_name']) ?> (<?= e($attempt['email']) ?>)</p>
            <p><strong>Exam:</strong> <?= e($attempt['exam_title']) ?></p>
            <p><strong>Score:</strong> <?= e($attempt['score']) ?> / <?= e($attempt['total_marks']) ?></p>
            <p><strong>Submitted:</strong> <?= e($attempt['submitted_at']) ?></p>
        </section>
        <section class="panel-card">
            <table>
                <thead><tr><th>Question</th><th>Selected</th><th>Correct</th><th>Marks</th></tr></thead>
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
