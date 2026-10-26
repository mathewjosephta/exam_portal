<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require_role('admin');

$query = $pdo->query(
    "SELECT a.id, a.score, a.submitted_at, e.title, u.name AS student_name, u.email,
            (SELECT COALESCE(SUM(q.marks), 0) FROM questions q WHERE q.exam_id = e.id) AS total_marks
     FROM attempts a
     JOIN exams e ON e.id = a.exam_id
     JOIN users u ON u.id = a.student_id
     WHERE a.status = 'submitted'
     ORDER BY a.submitted_at DESC"
);
$results = $query->fetchAll();

render_header('Exam results');
?>
<div class="page-heading"><div><p class="eyebrow">ADMINISTRATION</p><h1>Exam results</h1><p class="muted">Scores for all submitted examination attempts.</p></div></div>
<?php if (!$results): ?>
    <div class="empty-state">There are no submitted exams yet.</div>
<?php else: ?>
    <div class="table-wrap"><table>
        <thead><tr><th>Student</th><th>Exam</th><th>Score</th><th>Submitted</th><th></th></tr></thead>
        <tbody><?php foreach ($results as $result): ?>
            <tr>
                <td><strong><?= e($result['student_name']) ?></strong><br><span class="muted"><?= e($result['email']) ?></span></td>
                <td><?= e($result['title']) ?></td>
                <td><?= (int) $result['score'] ?> / <?= (int) $result['total_marks'] ?></td>
                <td><?= e(format_datetime($result['submitted_at'])) ?></td>
                <td><a href="<?= e(app_url('student/view-result.php?id=' . (int) $result['id'])) ?>">Review</a></td>
            </tr>
        <?php endforeach; ?></tbody>
    </table></div>
<?php endif; ?>
<?php render_footer(); ?>
