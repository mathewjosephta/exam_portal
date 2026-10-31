<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();
require_once __DIR__ . '/../includes/student-sidebar.php';

$user = current_user();
$exams = db()->query('SELECT * FROM exams WHERE status = "published" ORDER BY start_time ASC')->fetchAll();

$title = 'Exams';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_student_sidebar() ?>
    <main class="main-panel">
        <div class="topbar"><h1>Available exams</h1></div>
        <section class="panel-card">
            <table>
                <thead><tr><th>Exam</th><th>Duration</th><th>Marks</th><th>Availability</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($exams as $exam): ?>
                    <?php $attempt = db()->prepare('SELECT * FROM attempts WHERE student_id = :student_id AND exam_id = :exam_id LIMIT 1'); $attempt->execute([':student_id' => $user['id'], ':exam_id' => $exam['id']]); $currentAttempt = $attempt->fetch(); ?>
                    <tr>
                        <td>
                            <strong><?= e($exam['title']) ?></strong><br>
                            <small><?= e($exam['description']) ?></small>
                        </td>
                        <td><?= e($exam['duration_minutes']) ?> min</td>
                        <td><?= e($exam['total_marks']) ?></td>
                        <td><?= e($exam['start_time']) ?> to <?= e($exam['end_time']) ?></td>
                        <td>
                            <?php if ($currentAttempt && $currentAttempt['status'] === 'submitted'): ?>
                                <span class="badge published">Completed</span>
                            <?php elseif (exam_is_available($exam)): ?>
                                <a class="button button-small" href="<?= e(app_url('student/exam-instructions.php?id=' . $exam['id'])) ?>">Start</a>
                            <?php else: ?>
                                <span class="badge draft">Unavailable</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
