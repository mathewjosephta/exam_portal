<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

$examId = safe_int($_GET['id'] ?? 0);
$exam = $examId > 0 ? get_exam_by_id($examId) : null;
if ($examId > 0 && !$exam) {
    flash('error', 'Exam not found.');
    redirect('admin/exams.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf((string) ($_POST['csrf_token'] ?? ''));
    $id = safe_int($_POST['id'] ?? 0);
    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $minutes = safe_int($_POST['duration_minutes'] ?? 0);
    $start = trim((string) ($_POST['start_time'] ?? ''));
    $end = trim((string) ($_POST['end_time'] ?? ''));
    $status = in_array($_POST['status'] ?? 'draft', ['draft', 'published', 'closed'], true) ? $_POST['status'] : 'draft';
    if ($title === '' || $description === '' || $minutes <= 0 || $start === '' || $end === '') {
        flash('error', 'All fields are required.');
        redirect('admin/create-exam.php' . ($id > 0 ? '?id=' . $id : ''));
    }
    $startDt = new DateTimeImmutable($start, new DateTimeZone('UTC'));
    $endDt = new DateTimeImmutable($end, new DateTimeZone('UTC'));
    if ($endDt <= $startDt) {
        flash('error', 'End time must be later than start time.');
        redirect('admin/create-exam.php' . ($id > 0 ? '?id=' . $id : ''));
    }
    if ($status === 'published' && $id > 0 && count_questions_for_exam($id) === 0) {
        flash('error', 'This exam needs at least one question before publishing.');
        redirect('admin/create-exam.php?id=' . $id);
    }
    if ($id > 0) {
        db()->prepare('UPDATE exams SET title = :title, description = :description, duration_minutes = :minutes, start_time = :start, end_time = :end, status = :status, updated_at = NOW() WHERE id = :id')->execute([
            ':title' => $title,
            ':description' => $description,
            ':minutes' => $minutes,
            ':start' => $startDt->format('Y-m-d H:i:s'),
            ':end' => $endDt->format('Y-m-d H:i:s'),
            ':status' => $status,
            ':id' => $id,
        ]);
        flash('success', 'Exam updated.');
    } else {
        db()->prepare('INSERT INTO exams (title, description, duration_minutes, total_marks, start_time, end_time, status, created_by) VALUES (:title, :description, :minutes, 0, :start, :end, :status, :created_by)')->execute([
            ':title' => $title,
            ':description' => $description,
            ':minutes' => $minutes,
            ':start' => $startDt->format('Y-m-d H:i:s'),
            ':end' => $endDt->format('Y-m-d H:i:s'),
            ':status' => $status,
            ':created_by' => (int) $_SESSION['user_id'],
        ]);
        flash('success', 'Exam created.');
    }
    redirect('admin/exams.php');
}

$title = $exam ? 'Edit exam' : 'Create exam';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <main class="main-panel">
        <div class="topbar"><h1><?= e($title) ?></h1></div>
        <section class="panel-card form-card">
            <form method="post" class="stacked-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= e($exam['id'] ?? 0) ?>">
                <label>Title<input type="text" name="title" value="<?= e($exam['title'] ?? '') ?>" required></label>
                <label>Description<textarea name="description" rows="4" required><?= e($exam['description'] ?? '') ?></textarea></label>
                <div class="split-grid">
                    <label>Duration minutes<input type="number" name="duration_minutes" min="1" value="<?= e($exam['duration_minutes'] ?? 30) ?>" required></label>
                    <label>Status
                        <select name="status">
                            <option value="draft" <?= (($exam['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= (($exam['status'] ?? 'draft') === 'published') ? 'selected' : '' ?>>Published</option>
                            <option value="closed" <?= (($exam['status'] ?? 'draft') === 'closed') ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </label>
                </div>
                <div class="split-grid">
                    <label>Start time<input type="datetime-local" name="start_time" value="<?= e($exam ? date('Y-m-d\TH:i', strtotime($exam['start_time'])) : '') ?>" required></label>
                    <label>End time<input type="datetime-local" name="end_time" value="<?= e($exam ? date('Y-m-d\TH:i', strtotime($exam['end_time'])) : '') ?>" required></label>
                </div>
                <button class="button" type="submit">Save exam</button>
            </form>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
