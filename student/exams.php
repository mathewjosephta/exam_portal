<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();
require_once __DIR__ . '/../includes/student-sidebar.php';

$stmt = db()->prepare(
    'SELECT e.*, a.id AS attempt_id, a.status AS attempt_status
     FROM exams e
     LEFT JOIN attempts a
       ON a.exam_id = e.id AND a.student_id = :student_id
     WHERE e.status = "published"
     ORDER BY e.start_time DESC'
);
$stmt->execute([':student_id' => (int) $_SESSION['user_id']]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Exams';
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_student_sidebar() ?>
    <section class="dashboard-main">
        <div class="dashboard-topbar">
            <div><h1>Exams</h1><p>Browse your published examinations.</p></div>
        </div>
        <div class="dashboard-content">
            <?php if (!$exams): ?>
                <div class="empty-state"><h2>No exams available yet</h2><p>Published exams will show up here.</p></div>
            <?php else: ?>
                <div class="exam-grid">
                    <?php foreach ($exams as $exam): ?>
                        <?php
                        $isAvailable = exam_is_available($exam);
                        $attemptId = (int) ($exam['attempt_id'] ?? 0);
                        $attemptStatus = $exam['attempt_status'] ?? '';
                        ?>
                        <article class="exam-card">
                            <span class="badge <?= $isAvailable ? 'badge-success' : '' ?>">
                                <?= $attemptStatus === 'submitted' ? 'Completed' : ($isAvailable ? 'Open now' : 'Scheduled') ?>
                            </span>
                            <h2><?= e($exam['title']) ?></h2>
                            <p><?= e($exam['description']) ?></p>
                            <div class="exam-meta">
                                <span><?= e($exam['duration_minutes']) ?> min</span>
                                <span><?= e($exam['total_marks']) ?> marks</span>
                                <span><?= e(date('M j, Y g:i A', strtotime($exam['start_time']))) ?></span>
                            </div>
                            <?php if ($attemptStatus === 'in_progress'): ?>
                                <a class="btn btn-primary btn-block" href="<?= e(app_url('student/take-exam.php?id=' . $attemptId)) ?>">Resume exam</a>
                            <?php elseif ($attemptStatus === 'submitted'): ?>
                                <a class="btn btn-outline btn-block" href="<?= e(app_url('student/view-result.php?id=' . $attemptId)) ?>">View result</a>
                            <?php elseif ($isAvailable): ?>
                                <a class="btn btn-primary btn-block" href="<?= e(app_url('student/exam-instructions.php?id=' . (int) $exam['id'])) ?>">View instructions</a>
                            <?php else: ?>
                                <button class="btn btn-outline btn-block" type="button" disabled>
                                    <?= strtotime($exam['start_time']) > time()
                                        ? 'Opens ' . e(date('M j, g:i A', strtotime($exam['start_time'])))
                                        : 'Exam window closed' ?>
                                </button>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
