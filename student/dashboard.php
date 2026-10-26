<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../config/exams.php';
require_role('student');

$available = $pdo->query(
    "SELECT e.*, COUNT(q.id) AS question_count
     FROM exams e
     LEFT JOIN questions q ON q.exam_id = e.id
     WHERE e.status = 'published'
       AND (e.starts_at IS NULL OR e.starts_at <= CURRENT_TIMESTAMP)
       AND (e.ends_at IS NULL OR e.ends_at >= CURRENT_TIMESTAMP)
     GROUP BY e.id
     HAVING COUNT(q.id) > 0
     ORDER BY e.starts_at IS NOT NULL, e.starts_at, e.created_at DESC"
)->fetchAll();

$attemptQuery = $pdo->prepare(
    'SELECT a.id, a.status, a.score, a.started_at, a.submitted_at, e.title, e.duration_minutes
     FROM attempts a JOIN exams e ON e.id = a.exam_id
     WHERE a.student_id = :student_id ORDER BY a.started_at DESC'
);
$attemptQuery->execute(['student_id' => $_SESSION['user_id']]);
$attempts = $attemptQuery->fetchAll();

render_header('Student dashboard');
?>
<div class="page-heading">
    <div><p class="eyebrow">STUDENT PORTAL</p><h1>Your dashboard</h1><p class="muted">Choose an available exam or continue an attempt in progress.</p></div>
    <a class="button button-outline" href="<?= e(app_url('student/results.php')) ?>">View results</a>
</div>

<section class="section-block">
    <h2>Available exams</h2>
    <?php if (!$available): ?>
        <div class="empty-state">There are no exams open right now. Check back later.</div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($available as $exam): ?>
                <article class="card">
                    <h3><?= e($exam['title']) ?></h3>
                    <p class="muted"><?= e($exam['description'] ?: 'No additional instructions provided.') ?></p>
                    <p class="meta"><?= (int) $exam['question_count'] ?> questions · <?= (int) $exam['duration_minutes'] ?> minutes</p>
                    <a class="button" href="<?= e(app_url('student/exam-instructions.php?id=' . (int) $exam['id'])) ?>">View exam</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="section-block">
    <h2>Your exam activity</h2>
    <?php if (!$attempts): ?>
        <div class="empty-state">You have not started an exam yet.</div>
    <?php else: ?>
        <div class="table-wrap"><table>
            <thead><tr><th>Exam</th><th>Status</th><th>Started</th><th>Score</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($attempts as $attempt): ?>
                <tr>
                    <td><?= e($attempt['title']) ?></td>
                    <td><span class="status status-<?= e($attempt['status']) ?>"><?= e(str_replace('_', ' ', ucfirst($attempt['status']))) ?></span></td>
                    <td><?= e(format_datetime($attempt['started_at'])) ?></td>
                    <td><?= $attempt['status'] === 'submitted' ? e($attempt['score']) : '—' ?></td>
                    <td>
                        <?php if ($attempt['status'] === 'submitted'): ?>
                            <a href="<?= e(app_url('student/view-result.php?id=' . (int) $attempt['id'])) ?>">View result</a>
                        <?php else: ?>
                            <a href="<?= e(app_url('student/take-exam.php?id=' . (int) $attempt['id'])) ?>">Continue exam</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</section>
<?php render_footer(); ?>
