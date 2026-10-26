<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require __DIR__ . '/../config/exams.php';
require_role('student');

$query = $pdo->prepare(
    "SELECT a.id, a.score, a.submitted_at, e.title,
            (SELECT COALESCE(SUM(q.marks), 0) FROM questions q WHERE q.exam_id = e.id) AS total_marks
     FROM attempts a JOIN exams e ON e.id = a.exam_id
     WHERE a.student_id = :student_id AND a.status = 'submitted'
     ORDER BY a.submitted_at DESC"
);
$query->execute(['student_id' => $_SESSION['user_id']]);
$results = $query->fetchAll();

render_header('My results');
?>
<div class="page-heading"><div><p class="eyebrow">STUDENT PORTAL</p><h1>My results</h1><p class="muted">Your completed exam attempts and scores.</p></div></div>
<?php if (!$results): ?>
    <div class="empty-state">No submitted exam results yet.</div>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Exam</th><th>Score</th><th>Submitted</th><th></th></tr></thead>
        <tbody><?php foreach ($results as $result): ?>
            <tr>
                <td><?= e($result['title']) ?></td>
                <td><?= (int) $result['score'] ?> / <?= (int) $result['total_marks'] ?></td>
                <td><?= e(format_datetime($result['submitted_at'])) ?></td>
                <td><a href="<?= e(app_url('student/view-result.php?id=' . (int) $result['id'])) ?>">Review answers</a></td>
            </tr>
        <?php endforeach; ?></tbody>
    </table></div>
<?php endif; ?>
<?php render_footer(); ?>
