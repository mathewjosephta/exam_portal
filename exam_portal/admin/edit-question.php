<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

$id = safe_int($_GET['id'] ?? 0);
$examId = safe_int($_GET['exam_id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Question id is invalid.');
    redirect('admin/exams.php');
}

$question = db()->prepare('SELECT * FROM questions WHERE id = :id LIMIT 1');
$question->execute([':id' => $id]);
$question = $question->fetch();
if (!$question) {
    flash('error', 'Question not found.');
    redirect('admin/exams.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf((string) ($_POST['csrf_token'] ?? ''));
    $deletedExamId = safe_int($_POST['exam_id'] ?? 0);
    db()->prepare('DELETE FROM questions WHERE id = :id')->execute([':id' => $id]);
    $total = (int) db()->query('SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = ' . $deletedExamId)->fetchColumn();
    db()->prepare('UPDATE exams SET total_marks = :marks WHERE id = :id')->execute([':marks' => $total, ':id' => $deletedExamId]);
    flash('success', 'Question deleted.');
    redirect('admin/questions.php?exam_id=' . $deletedExamId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf((string) ($_POST['csrf_token'] ?? ''));
    $newText = trim((string) ($_POST['question_text'] ?? ''));
    $optA = trim((string) ($_POST['option_a'] ?? ''));
    $optB = trim((string) ($_POST['option_b'] ?? ''));
    $optC = trim((string) ($_POST['option_c'] ?? ''));
    $optD = trim((string) ($_POST['option_d'] ?? ''));
    $correct = strtoupper((string) ($_POST['correct_option'] ?? ''));
    $marks = safe_int($_POST['marks'] ?? 0);
    if ($newText === '' || $optA === '' || $optB === '' || $optC === '' || $optD === '' || !in_array($correct, ['A', 'B', 'C', 'D'], true) || $marks <= 0) {
        flash('error', 'Question data is invalid.');
        redirect('admin/edit-question.php?id=' . $id . '&exam_id=' . $examId);
    }
    db()->prepare('UPDATE questions SET question_text = :text, option_a = :a, option_b = :b, option_c = :c, option_d = :d, correct_option = :correct, marks = :marks WHERE id = :id')->execute([
        ':text' => $newText,
        ':a' => $optA,
        ':b' => $optB,
        ':c' => $optC,
        ':d' => $optD,
        ':correct' => $correct,
        ':marks' => $marks,
        ':id' => $id,
    ]);
    $total = (int) db()->query('SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = ' . (int) $question['exam_id'])->fetchColumn();
    db()->prepare('UPDATE exams SET total_marks = :marks WHERE id = :id')->execute([':marks' => $total, ':id' => (int) $question['exam_id']]);
    flash('success', 'Question updated.');
    redirect('admin/questions.php?exam_id=' . $question['exam_id']);
}

$title = 'Edit question';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <main class="main-panel">
        <div class="topbar"><h1>Edit question</h1></div>
        <section class="panel-card form-card">
            <form method="post" class="stacked-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label>Question text<textarea name="question_text" rows="4" required><?= e($question['question_text']) ?></textarea></label>
                <div class="split-grid">
                    <label>Option A<input type="text" name="option_a" value="<?= e($question['option_a']) ?>" required></label>
                    <label>Option B<input type="text" name="option_b" value="<?= e($question['option_b']) ?>" required></label>
                </div>
                <div class="split-grid">
                    <label>Option C<input type="text" name="option_c" value="<?= e($question['option_c']) ?>" required></label>
                    <label>Option D<input type="text" name="option_d" value="<?= e($question['option_d']) ?>" required></label>
                </div>
                <div class="split-grid">
                    <label>Correct option
                        <select name="correct_option">
                            <?php foreach (['A','B','C','D'] as $option): ?>
                                <option value="<?= e($option) ?>" <?= $question['correct_option'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Marks<input type="number" name="marks" min="1" value="<?= e($question['marks']) ?>" required></label>
                </div>
                <button class="button" type="submit">Update question</button>
            </form>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
