<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $examId = filter_input(INPUT_POST, 'exam_id', FILTER_VALIDATE_INT);
    if (!$examId) {
        http_response_code(400);
        exit('Invalid exam.');
    }
    $examQuery = $pdo->prepare('SELECT id, status FROM exams WHERE id = :id');
    $examQuery->execute(['id' => $examId]);
    $exam = $examQuery->fetch();
    if (!$exam) {
        http_response_code(404);
        exit('Exam not found.');
    }

    if ($exam['status'] === 'draft') {
        $check = $pdo->prepare(
            'SELECT COUNT(*) FROM questions q
             JOIN question_options o ON o.question_id = q.id AND o.is_correct = 1
             WHERE q.exam_id = :exam_id'
        );
        $check->execute(['exam_id' => $examId]);
        if ((int) $check->fetchColumn() < 1) {
            flash('error', 'Add at least one question with a correct answer before publishing.');
            redirect('admin/questions.php?exam_id=' . $examId);
        }
        $update = $pdo->prepare("UPDATE exams SET status = 'published' WHERE id = :id");
        $update->execute(['id' => $examId]);
        flash('success', 'The exam is now published.');
    } else {
        $attemptQuery = $pdo->prepare("SELECT COUNT(*) FROM attempts WHERE exam_id = :exam_id AND status = 'in_progress'");
        $attemptQuery->execute(['exam_id' => $examId]);
        if ((int) $attemptQuery->fetchColumn() > 0) {
            flash('error', 'This exam cannot be unpublished while students have attempts in progress.');
            redirect('admin/exams.php');
        }
        $update = $pdo->prepare("UPDATE exams SET status = 'draft' WHERE id = :id");
        $update->execute(['id' => $examId]);
        flash('success', 'The exam is now a draft and is no longer available to students.');
    }
    redirect('admin/exams.php');
}

$exams = $pdo->query(
    'SELECT e.id, e.title, e.duration_minutes, e.status, e.starts_at, e.ends_at,
            COUNT(DISTINCT q.id) AS question_count,
            COUNT(DISTINCT a.id) AS attempt_count
     FROM exams e
     LEFT JOIN questions q ON q.exam_id = e.id
     LEFT JOIN attempts a ON a.exam_id = e.id
     GROUP BY e.id ORDER BY e.created_at DESC'
)->fetchAll();

render_header('Manage exams');
?>
<div class="page-heading"><div><p class="eyebrow">ADMINISTRATION</p><h1>Exams</h1><p class="muted">Draft exams are private. Publish an exam to make it available to students.</p></div><a class="button" href="<?= e(app_url('admin/create-exam.php')) ?>">Create exam</a></div>
<?php if (!$exams): ?>
    <div class="empty-state">No exams yet. Create your first exam to get started.</div>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Exam</th><th>Status</th><th>Questions</th><th>Attempts</th><th>Availability</th><th>Actions</th></tr></thead>
        <tbody><?php foreach ($exams as $exam): ?>
            <tr>
                <td><strong><?= e($exam['title']) ?></strong><br><span class="muted"><?= (int) $exam['duration_minutes'] ?> min</span></td>
                <td><span class="status status-<?= e($exam['status']) ?>"><?= e(ucfirst($exam['status'])) ?></span></td>
                <td><?= (int) $exam['question_count'] ?></td>
                <td><?= (int) $exam['attempt_count'] ?></td>
                <td><?= e(format_datetime($exam['starts_at'])) ?><br>to <?= e(format_datetime($exam['ends_at'])) ?></td>
                <td class="actions">
                    <a href="<?= e(app_url('admin/questions.php?exam_id=' . (int) $exam['id'])) ?>">Questions</a>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="exam_id" value="<?= (int) $exam['id'] ?>">
                        <button class="link-button" type="submit"><?= $exam['status'] === 'draft' ? 'Publish' : 'Unpublish' ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?></tbody>
    </table></div>
<?php endif; ?>
<?php render_footer(); ?>
