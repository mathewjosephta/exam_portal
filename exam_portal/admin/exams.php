<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf((string) ($_POST['csrf_token'] ?? ''));
    $examId = safe_int($_POST['exam_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $exam = get_exam_by_id($examId);
    if (!$exam) {
        flash('error', 'Exam not found.');
        redirect('admin/exams.php');
    }
    if ($action === 'publish') {
        $count = count_questions_for_exam($examId);
        if ($count === 0) {
            flash('error', 'Add at least one question before publishing.');
            redirect('admin/exams.php');
        }
        db()->prepare('UPDATE exams SET status = "published", updated_at = NOW() WHERE id = :id')->execute([':id' => $examId]);
        flash('success', 'Exam published.');
    } elseif ($action === 'close') {
        db()->prepare('UPDATE exams SET status = "closed", updated_at = NOW() WHERE id = :id')->execute([':id' => $examId]);
        flash('success', 'Exam closed.');
    } elseif ($action === 'delete') {
        $attempts = db()->prepare('SELECT COUNT(*) AS total FROM attempts WHERE exam_id = :id');
        $attempts->execute([':id' => $examId]);
        if ((int) ($attempts->fetch()['total'] ?? 0) > 0) {
            flash('error', 'Delete is not allowed when students have attempted this exam.');
            redirect('admin/exams.php');
        }
        db()->prepare('DELETE FROM exams WHERE id = :id')->execute([':id' => $examId]);
        flash('success', 'Exam deleted.');
    }
    redirect('admin/exams.php');
}

$exams = db()->query('SELECT e.*, COUNT(q.id) AS question_count, COUNT(a.id) AS attempt_count FROM exams e LEFT JOIN questions q ON q.exam_id = e.id LEFT JOIN attempts a ON a.exam_id = e.id GROUP BY e.id ORDER BY e.created_at DESC')->fetchAll();

$title = 'Exams';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <main class="main-panel">
        <div class="topbar">
            <h1>Exam management</h1>
            <a class="button" href="<?= e(app_url('admin/create-exam.php')) ?>">Create exam</a>
        </div>
        <section class="panel-card">
            <table>
                <thead><tr><th>Title</th><th>Duration</th><th>Marks</th><th>Window</th><th>Status</th><th>Questions</th><th>Attempts</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($exams as $exam): ?>
                    <tr>
                        <td><?= e($exam['title']) ?></td>
                        <td><?= e($exam['duration_minutes']) ?> min</td>
                        <td><?= e($exam['total_marks']) ?></td>
                        <td><?= e($exam['start_time']) ?> / <?= e($exam['end_time']) ?></td>
                        <td><span class="badge <?= e($exam['status']) ?>"><?= e($exam['status']) ?></span></td>
                        <td><?= e($exam['question_count']) ?></td>
                        <td><?= e($exam['attempt_count']) ?></td>
                        <td>
                            <a href="<?= e(app_url('admin/create-exam.php?id=' . $exam['id'])) ?>">Edit</a>
                            <a href="<?= e(app_url('admin/questions.php?exam_id=' . $exam['id'])) ?>">Questions</a>
                            <?php if ($exam['status'] !== 'published'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="exam_id" value="<?= e($exam['id']) ?>">
                                    <input type="hidden" name="action" value="publish">
                                    <button type="submit" class="link-button">Publish</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($exam['status'] !== 'closed'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="exam_id" value="<?= e($exam['id']) ?>">
                                    <input type="hidden" name="action" value="close">
                                    <button type="submit" class="link-button">Close</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="exam_id" value="<?= e($exam['id']) ?>">
                                <input type="hidden" name="action" value="delete">
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
