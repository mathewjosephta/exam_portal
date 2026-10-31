<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $examId = safe_int($_POST['exam_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $exam = $examId > 0 ? get_exam_by_id($examId) : false;

    if (!$exam) {
        set_flash('error', 'Exam not found.');
        redirect('admin/exams.php');
    }

    if ($action === 'publish') {
        if (count_questions_for_exam($examId) === 0) {
            set_flash('error', 'Add at least one question before publishing this exam.');
        } else {
            $marks = db()->prepare('SELECT COALESCE(SUM(marks), 0) FROM questions WHERE exam_id = :id');
            $marks->execute([':id' => $examId]);
            $update = db()->prepare(
                'UPDATE exams SET status = "published", total_marks = :marks WHERE id = :id'
            );
            $update->execute([':marks' => (int) $marks->fetchColumn(), ':id' => $examId]);
            set_flash('success', 'Exam published.');
        }
    } elseif ($action === 'close') {
        $update = db()->prepare('UPDATE exams SET status = "closed" WHERE id = :id');
        $update->execute([':id' => $examId]);
        set_flash('success', 'Exam closed.');
    } elseif ($action === 'delete') {
        $attempts = db()->prepare('SELECT COUNT(*) FROM attempts WHERE exam_id = :id');
        $attempts->execute([':id' => $examId]);
        if ((int) $attempts->fetchColumn() > 0) {
            set_flash('error', 'An exam cannot be deleted after a student has started it.');
        } else {
            $delete = db()->prepare('DELETE FROM exams WHERE id = :id');
            $delete->execute([':id' => $examId]);
            set_flash('success', 'Exam deleted.');
        }
    } else {
        set_flash('error', 'Unknown exam action.');
    }

    redirect('admin/exams.php');
}

$exams = db()->query(
    'SELECT e.*,
        (SELECT COUNT(*) FROM questions q WHERE q.exam_id = e.id) AS question_count,
        (SELECT COUNT(*) FROM attempts a WHERE a.exam_id = e.id) AS attempt_count
     FROM exams e ORDER BY e.created_at DESC'
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Exam management';
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <section class="dashboard-main">
        <div class="dashboard-topbar"><div><h1>Exams</h1><p>Create, publish, and manage examinations.</p></div><a class="btn btn-primary" href="<?= e(app_url('admin/create-exam.php')) ?>">Create exam <span aria-hidden="true">+</span></a></div>
        <div class="dashboard-content">
            <section class="card panel-card">
                <?php if (!$exams): ?>
                    <div class="empty-state"><h2>No exams yet</h2><p>Create your first exam to get started.</p></div>
                <?php else: ?>
                    <div class="table-wrapper"><table>
                        <thead><tr><th>Exam</th><th>Availability</th><th>Duration</th><th>Marks</th><th>Status</th><th>Questions</th><th>Attempts</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($exams as $exam): ?>
                            <tr>
                                <td><strong><?= e($exam['title']) ?></strong></td>
                                <td><?= e(date('M j', strtotime($exam['start_time']))) ?> – <?= e(date('M j, Y', strtotime($exam['end_time']))) ?></td>
                                <td><?= e($exam['duration_minutes']) ?> min</td>
                                <td><?= e($exam['total_marks']) ?></td>
                                <td><span class="badge <?= $exam['status'] === 'published' ? 'badge-success' : '' ?>"><?= e($exam['status']) ?></span></td>
                                <td><?= e($exam['question_count']) ?></td>
                                <td><?= e($exam['attempt_count']) ?></td>
                                <td>
                                    <div class="action-links">
                                        <a href="<?= e(app_url('admin/create-exam.php?id=' . (int) $exam['id'])) ?>">Edit</a>
                                        <a href="<?= e(app_url('admin/questions.php?exam_id=' . (int) $exam['id'])) ?>">Questions</a>
                                        <?php if ($exam['status'] !== 'published'): ?>
                                            <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="exam_id" value="<?= e($exam['id']) ?>"><input type="hidden" name="action" value="publish"><button class="text-button" type="submit">Publish</button></form>
                                        <?php endif; ?>
                                        <?php if ($exam['status'] !== 'closed'): ?>
                                            <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="exam_id" value="<?= e($exam['id']) ?>"><input type="hidden" name="action" value="close"><button class="text-button" type="submit">Close</button></form>
                                        <?php endif; ?>
                                        <?php if ((int) $exam['attempt_count'] === 0): ?>
                                            <form method="post" onsubmit="return confirm('Delete this exam and its questions?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="exam_id" value="<?= e($exam['id']) ?>"><input type="hidden" name="action" value="delete"><button class="text-button danger" type="submit">Delete</button></form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </section>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
