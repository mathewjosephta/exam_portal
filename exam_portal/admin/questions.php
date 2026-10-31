<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

$examId = safe_int($_GET['exam_id'] ?? 0);
if ($examId <= 0) {
    $row = db()->query('SELECT id FROM exams ORDER BY id DESC LIMIT 1')->fetch();
    if (!$row) {
        flash('error', 'No exams found.');
        redirect('admin/exams.php');
    }
    $examId = (int) $row['id'];
}

$exam = get_exam_by_id($examId);
if (!$exam) {
    flash('error', 'Exam not found.');
    redirect('admin/exams.php');
}

$questions = db()->prepare('SELECT * FROM questions WHERE exam_id = :exam_id ORDER BY id ASC');
$questions->execute([':exam_id' => $examId]);
$questions = $questions->fetchAll();
$totalMarks = array_reduce($questions, fn($sum, $q) => $sum + (int) $q['marks'], 0);
db()->prepare('UPDATE exams SET total_marks = :marks, updated_at = NOW() WHERE id = :id')->execute([':marks' => $totalMarks, ':id' => $examId]);

$title = 'Questions';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <main class="main-panel">
        <div class="topbar">
            <h1><?= e($exam['title']) ?></h1>
            <a class="button" href="<?= e(app_url('admin/add-question.php?exam_id=' . $examId)) ?>">Add question</a>
        </div>
        <p class="muted">Total marks: <?= e($totalMarks) ?></p>
        <section class="panel-card">
            <table>
                <thead><tr><th>#</th><th>Question</th><th>Correct</th><th>Marks</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($questions as $index => $question): ?>
                    <tr>
                        <td><?= e($index + 1) ?></td>
                        <td><?= e($question['question_text']) ?></td>
                        <td><?= e($question['correct_option']) ?></td>
                        <td><?= e($question['marks']) ?></td>
                        <td>
                            <a href="<?= e(app_url('admin/edit-question.php?id=' . $question['id'] . '&exam_id=' . $examId)) ?>">Edit</a>
                            <form method="post" action="<?= e(app_url('admin/edit-question.php')) ?>" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= e($question['id']) ?>">
                                <input type="hidden" name="exam_id" value="<?= e($examId) ?>">
                                <button type="submit" class="link-button danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
