<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require_role('admin');

$errors = [];
$title = '';
$description = '';
$duration = '30';
$startsAt = '';
$endsAt = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $title = trim(request_string($_POST, 'title'));
    $description = trim(request_string($_POST, 'description'));
    $duration = trim(request_string($_POST, 'duration_minutes'));
    $startsAt = trim(request_string($_POST, 'starts_at'));
    $endsAt = trim(request_string($_POST, 'ends_at'));

    if ($title === '' || mb_strlen($title) > 180) {
        $errors[] = 'Enter an exam title no longer than 180 characters.';
    }
    if (!ctype_digit($duration) || (int) $duration < 1 || (int) $duration > 600) {
        $errors[] = 'Duration must be between 1 and 600 minutes.';
    }
    $parseDate = static function (string $value): string|false|null {
        if ($value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
        $dateErrors = DateTimeImmutable::getLastErrors();
        if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
            return false;
        }
        return $date->format('Y-m-d H:i:s');
    };
    $startDate = $parseDate($startsAt);
    $endDate = $parseDate($endsAt);
    if ($startDate === false || $endDate === false) {
        $errors[] = 'Enter valid availability dates.';
    } elseif ($startDate !== null && $endDate !== null && $startDate >= $endDate) {
        $errors[] = 'The end time must be after the start time.';
    }

    if (!$errors) {
        $insert = $pdo->prepare(
            "INSERT INTO exams (title, description, duration_minutes, starts_at, ends_at, created_by)
             VALUES (:title, :description, :duration, :starts_at, :ends_at, :created_by)"
        );
        $insert->execute([
            'title' => $title,
            'description' => $description === '' ? null : $description,
            'duration' => (int) $duration,
            'starts_at' => $startDate,
            'ends_at' => $endDate,
            'created_by' => $_SESSION['user_id'],
        ]);
        $examId = (int) $pdo->lastInsertId();
        flash('success', 'Exam created as a draft. Add questions before publishing it.');
        redirect('admin/questions.php?exam_id=' . $examId);
    }
}

render_header('Create exam');
?>
<section class="content-card narrow">
    <p class="eyebrow">EXAM SETUP</p><h1>Create an exam</h1>
    <?php if ($errors): ?><div class="notice notice-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="title">Exam title</label>
        <input id="title" name="title" value="<?= e($title) ?>" maxlength="180" required>
        <label for="description">Instructions / description</label>
        <textarea id="description" name="description" rows="5"><?= e($description) ?></textarea>
        <label for="duration_minutes">Time limit (minutes)</label>
        <input id="duration_minutes" name="duration_minutes" type="number" min="1" max="600" value="<?= e($duration) ?>" required>
        <label for="starts_at">Available from, UTC (optional)</label>
        <input id="starts_at" name="starts_at" type="datetime-local" value="<?= e($startsAt) ?>">
        <label for="ends_at">Available until, UTC (optional)</label>
        <input id="ends_at" name="ends_at" type="datetime-local" value="<?= e($endsAt) ?>">
        <button class="button" type="submit">Create draft exam</button>
    </form>
</section>
<?php render_footer(); ?>
