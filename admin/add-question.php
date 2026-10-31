<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

$examId = safe_int($_GET['exam_id'] ?? $_POST['exam_id'] ?? 0);
$exam = $examId > 0 ? get_exam_by_id($examId) : false;
if (!$exam) {
    set_flash('error', 'Select a valid exam before adding questions.');
    redirect('admin/exams.php');
}

$attempts = db()->prepare('SELECT EXISTS(SELECT 1 FROM attempts WHERE exam_id = :exam_id)');
$attempts->execute([':exam_id' => $examId]);
$hasAttempts = (bool) $attempts->fetchColumn();
if ($hasAttempts) {
    set_flash('error', 'Questions cannot be changed after students have started this exam.');
    redirect('admin/questions.php?exam_id=' . $examId);
}

$error = null;
$form = [
    'question_text' => '',
    'option_a' => '',
    'option_b' => '',
    'option_c' => '',
    'option_d' => '',
    'correct_option' => 'A',
    'marks' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    foreach (array_keys($form) as $key) {
        $form[$key] = trim((string) ($_POST[$key] ?? $form[$key]));
    }
    $form['correct_option'] = strtoupper($form['correct_option']);
    $marks = safe_int($form['marks']);

    if ($form['question_text'] === ''
        || $form['option_a'] === '' || $form['option_b'] === ''
        || $form['option_c'] === '' || $form['option_d'] === ''
        || preg_match('/\A.{1,500}\z/us', $form['option_a']) !== 1
        || preg_match('/\A.{1,500}\z/us', $form['option_b']) !== 1
        || preg_match('/\A.{1,500}\z/us', $form['option_c']) !== 1
        || preg_match('/\A.{1,500}\z/us', $form['option_d']) !== 1
        || !in_array($form['correct_option'], ['A', 'B', 'C', 'D'], true)
        || $marks < 1 || $marks > 10000) {
        $error = 'Complete all fields, keep each option under 500 characters, and use valid marks.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $insert = $pdo->prepare(
                'INSERT INTO questions
                    (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option, marks)
                 VALUES
                    (:exam_id, :question_text, :option_a, :option_b, :option_c, :option_d, :correct_option, :marks)'
            );
            $insert->execute([
                ':exam_id' => $examId,
                ':question_text' => $form['question_text'],
                ':option_a' => $form['option_a'],
                ':option_b' => $form['option_b'],
                ':option_c' => $form['option_c'],
                ':option_d' => $form['option_d'],
                ':correct_option' => $form['correct_option'],
                ':marks' => $marks,
            ]);
            $total = $pdo->prepare('SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = :exam_id');
            $total->execute([':exam_id' => $examId]);
            $pdo->prepare('UPDATE exams SET total_marks = :total WHERE id = :id')
                ->execute([':total' => (int) $total->fetchColumn(), ':id' => $examId]);
            $pdo->commit();
            set_flash('success', 'Question added.');
            redirect('admin/questions.php?exam_id=' . $examId);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Question creation failed: ' . $exception->getMessage());
            $error = 'Unable to save this question right now. Please try again.';
        }
    }
}

$pageTitle = 'Add question';
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <section class="dashboard-main">
        <div class="dashboard-topbar"><div><h1>Add a question</h1><p><?= e($exam['title']) ?></p></div><a class="btn btn-outline" href="<?= e(app_url('admin/questions.php?exam_id=' . $examId)) ?>">Back to questions</a></div>
        <div class="dashboard-content">
            <section class="card form-panel">
                <?php if ($error !== null): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
                <form method="post" class="stacked-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="exam_id" value="<?= e($examId) ?>">
                    <label for="question_text">Question</label>
                    <textarea class="form-control" id="question_text" name="question_text" rows="4" required><?= e($form['question_text']) ?></textarea>
                    <div class="split-grid">
                        <?php foreach (['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $key => $label): ?>
                            <div>
                                <label for="option_<?= e($key) ?>">Option <?= e($label) ?></label>
                                <input class="form-control" id="option_<?= e($key) ?>" name="option_<?= e($key) ?>" maxlength="500" value="<?= e($form['option_' . $key]) ?>" required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="split-grid">
                        <div>
                            <label for="correct_option">Correct option</label>
                            <select class="form-control" id="correct_option" name="correct_option">
                                <?php foreach (['A', 'B', 'C', 'D'] as $option): ?>
                                    <option value="<?= e($option) ?>" <?= $form['correct_option'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="marks">Marks</label>
                            <input class="form-control" id="marks" name="marks" type="number" min="1" max="10000" value="<?= e($form['marks']) ?>" required>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Save question</button>
                </form>
            </section>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
