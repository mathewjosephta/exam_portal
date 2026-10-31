<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

$examId = safe_int($_GET['id'] ?? $_POST['id'] ?? 0);
$exam = $examId > 0 ? get_exam_by_id($examId) : false;
$error = null;
$hasAttempts = false;

if ($examId > 0 && !$exam) {
    set_flash('error', 'Exam not found.');
    redirect('admin/exams.php');
}
if ($exam) {
    $attemptCount = db()->prepare('SELECT COUNT(*) FROM attempts WHERE exam_id = :id');
    $attemptCount->execute([':id' => $examId]);
    $hasAttempts = (int) $attemptCount->fetchColumn() > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $duration = safe_int($_POST['duration_minutes'] ?? 0);
    $status = $_POST['status'] ?? 'draft';
    $startInput = trim((string) ($_POST['start_time'] ?? ''));
    $endInput = trim((string) ($_POST['end_time'] ?? ''));

    $start = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $startInput);
    $startErrors = DateTimeImmutable::getLastErrors();
    $end = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $endInput);
    $endErrors = DateTimeImmutable::getLastErrors();
    $validStart = $start !== false && ($startErrors === false
        || ($startErrors['warning_count'] === 0 && $startErrors['error_count'] === 0));
    $validEnd = $end !== false && ($endErrors === false
        || ($endErrors['warning_count'] === 0 && $endErrors['error_count'] === 0));

    if ($hasAttempts) {
        $error = 'This exam cannot be edited after a student has started it.';
    } elseif ($title === '' || strlen($title) > 255 || $description === ''
        || $duration < 1 || $duration > 1440 || !$validStart || !$validEnd
        || !in_array($status, ['draft', 'published', 'closed'], true)) {
        $error = 'Enter a valid title, description, duration, status, and schedule.';
    } elseif ($end <= $start) {
        $error = 'The end time must be later than the start time.';
    } elseif ($examId > 0 && $status === 'published' && count_questions_for_exam($examId) === 0) {
        $error = 'Add at least one question before publishing this exam.';
    } else {
        try {
            $values = [
                ':title' => $title,
                ':description' => $description,
                ':duration' => $duration,
                ':start' => $start->format('Y-m-d H:i:s'),
                ':end' => $end->format('Y-m-d H:i:s'),
                ':status' => $status,
            ];
            if ($examId > 0) {
                $save = db()->prepare(
                    'UPDATE exams SET title = :title, description = :description,
                        duration_minutes = :duration, start_time = :start,
                        end_time = :end, status = :status
                     WHERE id = :id'
                );
                $values[':id'] = $examId;
                $save->execute($values);
                set_flash('success', 'Exam updated.');
            } else {
                $save = db()->prepare(
                    'INSERT INTO exams
                        (title, description, duration_minutes, start_time, end_time, status, created_by)
                     VALUES
                        (:title, :description, :duration, :start, :end, "draft", :created_by)'
                );
                unset($values[':status']);
                $values[':created_by'] = (int) $_SESSION['user_id'];
                $save->execute($values);
                $examId = (int) db()->lastInsertId();
                set_flash('success', 'Exam created. Add questions before publishing it.');
            }
            redirect('admin/exams.php');
        } catch (PDOException $exception) {
            error_log('Exam save failed: ' . $exception->getMessage());
            $error = 'Unable to save this exam right now. Please try again.';
        }
    }

    $exam = [
        'id' => $examId,
        'title' => $title,
        'description' => $description,
        'duration_minutes' => $duration,
        'status' => $status,
        'start_time' => $startInput,
        'end_time' => $endInput,
    ];
}

$pageTitle = $examId > 0 ? 'Edit exam' : 'Create exam';
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <section class="dashboard-main">
        <div class="dashboard-topbar"><div><h1><?= e($pageTitle) ?></h1><p>Set the exam details and schedule.</p></div><a class="btn btn-outline" href="<?= e(app_url('admin/exams.php')) ?>">Back to exams</a></div>
        <div class="dashboard-content">
            <section class="card form-panel">
                <?php if ($error !== null): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
                <?php if ($hasAttempts): ?><p class="alert alert-error">Editing is locked because students have already started this exam.</p><?php endif; ?>
                <form method="post" class="stacked-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= e($exam['id'] ?? 0) ?>">
                    <label for="title">Exam title</label>
                    <input class="form-control" id="title" name="title" maxlength="255" value="<?= e($exam['title'] ?? '') ?>" required <?= $hasAttempts ? 'disabled' : '' ?>>
                    <label for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required <?= $hasAttempts ? 'disabled' : '' ?>><?= e($exam['description'] ?? '') ?></textarea>
                    <div class="split-grid">
                        <div>
                            <label for="duration_minutes">Duration (minutes)</label>
                            <input class="form-control" id="duration_minutes" name="duration_minutes" type="number" min="1" max="1440" value="<?= e($exam['duration_minutes'] ?? 30) ?>" required <?= $hasAttempts ? 'disabled' : '' ?>>
                        </div>
                        <div>
                            <label for="status">Status</label>
                            <select class="form-control" id="status" name="status" <?= $hasAttempts ? 'disabled' : '' ?>>
                                <?php foreach (($examId > 0
                                    ? ['draft' => 'Draft', 'published' => 'Published', 'closed' => 'Closed']
                                    : ['draft' => 'Draft']) as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= ($exam['status'] ?? 'draft') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="split-grid">
                        <div>
                            <label for="start_time">Opens at</label>
                            <input class="form-control" id="start_time" name="start_time" type="datetime-local" value="<?= e($exam && isset($exam['start_time']) ? date('Y-m-d\TH:i', strtotime($exam['start_time'])) : '') ?>" required <?= $hasAttempts ? 'disabled' : '' ?>>
                        </div>
                        <div>
                            <label for="end_time">Closes at</label>
                            <input class="form-control" id="end_time" name="end_time" type="datetime-local" value="<?= e($exam && isset($exam['end_time']) ? date('Y-m-d\TH:i', strtotime($exam['end_time'])) : '') ?>" required <?= $hasAttempts ? 'disabled' : '' ?>>
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit" <?= $hasAttempts ? 'disabled' : '' ?>>Save exam</button>
                </form>
            </section>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
