<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require_role('admin');

$examId = filter_input(INPUT_GET, 'exam_id', FILTER_VALIDATE_INT);
if (!$examId) {
    $examId = filter_input(INPUT_POST, 'exam_id', FILTER_VALIDATE_INT);
}
if (!$examId) {
    redirect('admin/exams.php');
}
$examQuery = $pdo->prepare('SELECT id, title, status FROM exams WHERE id = :id');
$examQuery->execute(['id' => $examId]);
$exam = $examQuery->fetch();
if (!$exam) {
    http_response_code(404);
    exit('Exam not found.');
}
$attemptQuery = $pdo->prepare('SELECT COUNT(*) FROM attempts WHERE exam_id = :exam_id');
$attemptQuery->execute(['exam_id' => $examId]);
if ($exam['status'] !== 'draft' || (int) $attemptQuery->fetchColumn() > 0) {
    http_response_code(409);
    exit('Questions cannot be changed after the exam is published or has attempts.');
}

$errors = [];
$questionText = '';
$marks = '1';
$options = ['', '', '', ''];
$correct = '0';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $questionText = trim(request_string($_POST, 'question_text'));
    $marks = trim(request_string($_POST, 'marks'));
    $submittedOptions = $_POST['options'] ?? [];
    if (!is_array($submittedOptions)) {
        $submittedOptions = [];
    }
    $options = array_map(
        static fn ($option): string => is_string($option) || is_numeric($option) ? trim((string) $option) : '',
        $submittedOptions
    );
    $correct = request_string($_POST, 'correct_option');

    if ($questionText === '' || mb_strlen($questionText) > 10000) {
        $errors[] = 'Enter a question (up to 10,000 characters).';
    }
    if (!ctype_digit($marks) || (int) $marks < 1 || (int) $marks > 1000) {
        $errors[] = 'Marks must be between 1 and 1,000.';
    }
    if (count($options) < 2 || count($options) > 6) {
        $errors[] = 'Provide between 2 and 6 answer choices.';
    } else {
        foreach ($options as $option) {
            if ($option === '' || mb_strlen($option) > 500) {
                $errors[] = 'Each answer choice is required and must be at most 500 characters.';
                break;
            }
        }
    }
    if (!ctype_digit($correct)) {
        $errors[] = 'Select the correct answer.';
    } else {
        $correct = (string) (int) $correct;
        if (!isset($options[(int) $correct])) {
            $errors[] = 'Select the correct answer.';
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $positionQuery = $pdo->prepare('SELECT COALESCE(MAX(position), 0) + 1 FROM questions WHERE exam_id = :exam_id');
            $positionQuery->execute(['exam_id' => $examId]);
            $position = (int) $positionQuery->fetchColumn();
            $insertQuestion = $pdo->prepare(
                'INSERT INTO questions (exam_id, question_text, marks, position)
                 VALUES (:exam_id, :question_text, :marks, :position)'
            );
            $insertQuestion->execute([
                'exam_id' => $examId,
                'question_text' => $questionText,
                'marks' => (int) $marks,
                'position' => $position,
            ]);
            $questionId = (int) $pdo->lastInsertId();
            $insertOption = $pdo->prepare(
                'INSERT INTO question_options (question_id, option_text, is_correct, position)
                 VALUES (:question_id, :option_text, :is_correct, :position)'
            );
            foreach ($options as $index => $option) {
                $insertOption->execute([
                    'question_id' => $questionId,
                    'option_text' => $option,
                    'is_correct' => (int) ((string) $index === $correct),
                    'position' => $index + 1,
                ]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        flash('success', 'Question added.');
        redirect('admin/questions.php?exam_id=' . $examId);
    }
}

render_header('Add question');
?>
<section class="content-card narrow">
    <p class="eyebrow"><?= e($exam['title']) ?></p><h1>Add a multiple-choice question</h1>
    <?php if ($errors): ?><div class="notice notice-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="exam_id" value="<?= $examId ?>">
        <label for="question_text">Question</label>
        <textarea id="question_text" name="question_text" rows="4" required><?= e($questionText) ?></textarea>
        <label for="marks">Marks</label>
        <input id="marks" name="marks" type="number" min="1" max="1000" value="<?= e($marks) ?>" required>
        <fieldset class="choice-fields"><legend>Answer choices (select the correct one)</legend>
            <?php for ($index = 0; $index < 6; $index++): ?>
                <div class="choice-row">
                    <input type="radio" name="correct_option" value="<?= $index ?>" aria-label="Mark choice <?= $index + 1 ?> as correct" <?= $correct === (string) $index ? 'checked' : '' ?>>
                    <input name="options[]" value="<?= e($options[$index] ?? '') ?>" maxlength="500" placeholder="Choice <?= $index + 1 ?>" <?= $index < 2 ? 'required' : '' ?>>
                </div>
            <?php endfor; ?>
        </fieldset>
        <button class="button" type="submit">Save question</button>
    </form>
</section>
<?php render_footer(); ?>
