<?php
declare(strict_types=1);
<<<<<<< HEAD
require __DIR__ . '/../legacy-page.php';
=======

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();
require_once __DIR__ . '/../includes/student-sidebar.php';

$user = current_user();
$available = db()->prepare(
    'SELECT COUNT(*)
     FROM exams e
     WHERE e.status = "published"
       AND e.start_time <= NOW() AND e.end_time >= NOW()
       AND NOT EXISTS (
           SELECT 1 FROM attempts a
           WHERE a.exam_id = e.id AND a.student_id = :student_id
       )'
);
$available->execute([':student_id' => $user['id']]);
$availableCount = (int) $available->fetchColumn();

$stats = db()->prepare(
    'SELECT COUNT(*) AS completed, COALESCE(AVG(
        CASE WHEN e.total_marks > 0 THEN (a.score / e.total_marks) * 100 ELSE 0 END
     ), 0) AS average_score
     FROM attempts a
     JOIN exams e ON e.id = a.exam_id
     WHERE a.student_id = :student_id AND a.status = "submitted"'
);
$stats->execute([':student_id' => $user['id']]);
$summary = $stats->fetch(PDO::FETCH_ASSOC);

$upcoming = db()->query(
    'SELECT id, title, start_time, end_time, duration_minutes
     FROM exams
     WHERE status = "published" AND start_time > NOW()
     ORDER BY start_time ASC LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);

$recent = db()->prepare(
    'SELECT a.id, a.score, a.submitted_at, e.title, e.total_marks
     FROM attempts a
     JOIN exams e ON e.id = a.exam_id
     WHERE a.student_id = :student_id AND a.status = "submitted"
     ORDER BY a.submitted_at DESC LIMIT 5'
);
$recent->execute([':student_id' => $user['id']]);
$recentResults = $recent->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Student dashboard';
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_student_sidebar() ?>
    <section class="dashboard-main">
        <div class="dashboard-topbar">
            <div>
                <h1>Welcome, <?= e($user['name']) ?></h1>
                <p>Here is your exam activity at a glance.</p>
            </div>
            <a class="btn btn-primary" href="<?= e(app_url('student/exams.php')) ?>">Browse exams <span aria-hidden="true">↗</span></a>
        </div>
        <div class="dashboard-content">
            <div class="card-grid stats-grid">
                <article class="card stat-card"><span>Available exams</span><strong><?= e($availableCount) ?></strong></article>
                <article class="card stat-card"><span>Completed exams</span><strong><?= e($summary['completed'] ?? 0) ?></strong></article>
                <article class="card stat-card"><span>Average score</span><strong><?= e(number_format((float) ($summary['average_score'] ?? 0), 1)) ?>%</strong></article>
            </div>

            <div class="panel-grid">
                <section class="card panel-card">
                    <div class="panel-heading"><h2>Upcoming exams</h2><a href="<?= e(app_url('student/exams.php')) ?>">See all</a></div>
                    <?php if ($upcoming): ?>
                        <div class="table-wrapper"><table>
                            <thead><tr><th>Exam</th><th>Starts</th><th>Duration</th></tr></thead>
                            <tbody>
                            <?php foreach ($upcoming as $exam): ?>
                                <tr>
                                    <td><?= e($exam['title']) ?></td>
                                    <td><?= e(date('M j, Y g:i A', strtotime($exam['start_time']))) ?></td>
                                    <td><?= e($exam['duration_minutes']) ?> min</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table></div>
                    <?php else: ?>
                        <div class="empty-state">There are no upcoming exams right now.</div>
                    <?php endif; ?>
                </section>

                <section class="card panel-card">
                    <div class="panel-heading"><h2>Recent results</h2><a href="<?= e(app_url('student/results.php')) ?>">See all</a></div>
                    <?php if ($recentResults): ?>
                        <div class="table-wrapper"><table>
                            <thead><tr><th>Exam</th><th>Score</th><th>Submitted</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentResults as $result): ?>
                                <tr>
                                    <td><a href="<?= e(app_url('student/view-result.php?id=' . (int) $result['id'])) ?>"><?= e($result['title']) ?></a></td>
                                    <td><?= e($result['score']) ?> / <?= e($result['total_marks']) ?></td>
                                    <td><?= e(date('M j, Y', strtotime($result['submitted_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table></div>
                    <?php else: ?>
                        <div class="empty-state">Your completed exam results will appear here.</div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
>>>>>>> 0ecac9e (Update exam portal and admin features)
