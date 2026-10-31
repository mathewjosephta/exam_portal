<?php
declare(strict_types=1);
<<<<<<< HEAD
require __DIR__ . '/../legacy-page.php';
=======

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();
require_once __DIR__ . '/../includes/student-sidebar.php';

$stmt = db()->prepare(
    'SELECT a.id, a.score, a.submitted_at, e.title, e.total_marks
     FROM attempts a
     JOIN exams e ON e.id = a.exam_id
     WHERE a.student_id = :student_id AND a.status = "submitted"
     ORDER BY a.submitted_at DESC'
);
$stmt->execute([':student_id' => (int) $_SESSION['user_id']]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'My results';
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_student_sidebar() ?>
    <section class="dashboard-main">
        <div class="dashboard-topbar"><div><h1>My results</h1><p>Review your completed examinations.</p></div></div>
        <div class="dashboard-content">
            <?php if (!$results): ?>
                <div class="empty-state"><h2>No results yet</h2><p>Once you complete an exam, your result will appear here.</p></div>
            <?php else: ?>
                <div class="table-wrapper"><table>
                    <thead><tr><th>Exam</th><th>Submitted</th><th>Score</th><th>Percentage</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($results as $result): ?>
                        <?php $percentage = (int) $result['total_marks'] > 0
                            ? round(((int) $result['score'] / (int) $result['total_marks']) * 100, 1)
                            : 0; ?>
                        <tr>
                            <td><?= e($result['title']) ?></td>
                            <td><?= e(date('M j, Y g:i A', strtotime($result['submitted_at']))) ?></td>
                            <td><?= e($result['score']) ?> / <?= e($result['total_marks']) ?></td>
                            <td><?= e($percentage) ?>%</td>
                            <td><a class="text-link" href="<?= e(app_url('student/view-result.php?id=' . (int) $result['id'])) ?>">View details</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
>>>>>>> 0ecac9e (Update exam portal and admin features)
