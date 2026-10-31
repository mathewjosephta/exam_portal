<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

$exams = db()->query('SELECT id, title FROM exams ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$examId = safe_int($_GET['exam_id'] ?? 0);
if ($examId <= 0 && $exams) {
    $examId = (int) $exams[0]['id'];
}
$exam = $examId > 0 ? get_exam_by_id($examId) : false;
$questions = [];
$hasAttempts = false;

if ($exam) {
    $stmt = db()->prepare('SELECT * FROM questions WHERE exam_id = :exam_id ORDER BY id');
    $stmt->execute([':exam_id' => $examId]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $attempts = db()->prepare('SELECT EXISTS(SELECT 1 FROM attempts WHERE exam_id = :exam_id)');
    $attempts->execute([':exam_id' => $examId]);
    $hasAttempts = (bool) $attempts->fetchColumn();
}

$pageTitle = 'Questions';
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <section class="dashboard-main">
        <div class="dashboard-topbar"><div><h1>Questions</h1><p>Choose an exam and manage its questions.</p></div></div>
        <div class="dashboard-content">
            <?php if (!$exams): ?>
                <div class="empty-state"><h2>Create an exam first</h2><a class="btn btn-primary" href="<?= e(app_url('admin/create-exam.php')) ?>">Create exam</a></div>
            <?php else: ?>
                <form method="get" class="filter-form">
                    <select class="form-control" name="exam_id" aria-label="Select exam">
                        <?php foreach ($exams as $item): ?>
                            <option value="<?= e($item['id']) ?>" <?= $examId === (int) $item['id'] ? 'selected' : '' ?>><?= e($item['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline" type="submit">Show questions</button>
                    <?php if ($exam && !$hasAttempts): ?>
                        <a class="btn btn-primary" href="<?= e(app_url('admin/add-question.php?exam_id=' . $examId)) ?>">Add question</a>
                    <?php endif; ?>
                </form>
                <?php if ($exam): ?>
                    <section class="card panel-card">
                        <div class="panel-heading"><h2><?= e($exam['title']) ?></h2><span><?= e($exam['total_marks']) ?> total marks</span></div>
                        <?php if ($hasAttempts): ?><p class="text-muted">Question editing is locked after an attempt has started.</p><?php endif; ?>
                        <?php if (!$questions): ?>
                            <div class="empty-state">This exam does not have any questions yet.</div>
                        <?php else: ?>
                            <div class="table-wrapper"><table>
                                <thead><tr><th>#</th><th>Question</th><th>Correct answer</th><th>Marks</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($questions as $index => $question): ?>
                                    <tr>
                                        <td><?= e($index + 1) ?></td>
                                        <td><?= e($question['question_text']) ?></td>
                                        <td><?= e($question['correct_option']) ?></td>
                                        <td><?= e($question['marks']) ?></td>
                                        <td><?php if (!$hasAttempts): ?><a class="text-link" href="<?= e(app_url('admin/edit-question.php?id=' . (int) $question['id'])) ?>">Edit</a><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table></div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
