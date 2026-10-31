<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

$examId = safe_int($_GET['exam_id'] ?? 0);
if ($examId <= 0) {
    flash('error', 'Please select a valid exam.');
    redirect('admin/exams.php');
}
$exam = get_exam_by_id($examId);
if (!$exam) {
    flash('error', 'Exam not found.');
    redirect('admin/exams.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf((string) ($_POST['csrf_token'] ?? ''));
    $questionText = trim((string) ($_POST['question_text'] ?? ''));
    $options = [
        'A' => trim((string) ($_POST['option_a'] ?? '')),
        'B' => trim((string) ($_POST['option_b'] ?? '')),
        'C' => trim((string) ($_POST['option_c'] ?? '')),
        'D' => trim((string) ($_POST['option_d'] ?? '')),
    ];
    $correct = strtoupper((string) ($_POST['correct_option'] ?? ''));
    $marks = safe_int($_POST['marks'] ?? 0);
    if ($questionText === '' || in_array('', $options, true) || !isset($options[$correct]) || $marks <= 0) {
        flash('error', 'Invalid question data.');
        redirect('admin/add-question.php?exam_id=' . $examId);
    }
    db()->prepare('INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, marks) VALUES (:exam_id, :text, :a, :b, :c, :d, :correct, :marks)')->execute([
        ':exam_id' => $examId,
        ':text' => $questionText,
        ':a' => $options['A'],
        ':b' => $options['B'],
        ':c' => $options['C'],
        ':d' => $options['D'],
        ':correct' => $correct,
        ':marks' => $marks,
    ]);
    $totalMarks = (int) db()->query('SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = ' . $examId)->fetchColumn();
    db()->prepare('UPDATE exams SET total_marks = :marks, updated_at = NOW() WHERE id = :id')->execute([':marks' => $totalMarks, ':id' => $examId]);
    flash('success', 'Question added.');
    redirect('admin/questions.php?exam_id=' . $examId);
}

$title = 'Add question';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <main class="main-panel">
        <div class="topbar"><h1>Add question</h1></div>
        <section class="panel-card form-card">
            <form method="post" class="stacked-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label>Question text<textarea name="question_text" rows="4" required></textarea></label>
                <div class="split-grid">
                    <label>Option A<input type="text" name="option_a" required></label>
                    <label>Option B<input type="text" name="option_b" required></label>
                </div>
                <div class="split-grid">
                    <label>Option C<input type="text" name="option_c" required></label>
                    <label>Option D<input type="text" name="option_d" required></label>
                </div>
                <div class="split-grid">
                    <label>Correct option
                        <select name="correct_option">
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </label>
                    <label>Marks<input type="number" name="marks" min="1" value="1" required></label>
                </div>
                <button class="button" type="submit">Save question</button>
            </form>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
