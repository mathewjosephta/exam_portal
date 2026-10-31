<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();
require_once __DIR__ . '/../includes/student-sidebar.php';

$user = current_user();
$availableCount = (int) db()->query('SELECT COUNT(*) FROM exams WHERE status = "published" AND start_time <= NOW() AND end_time >= NOW()')->fetchColumn();
$completedStmt = db()->prepare('SELECT COUNT(*) AS total FROM attempts WHERE student_id = :student_id AND status = "submitted"');
$completedStmt->execute([':student_id' => $user['id']]);
$completedCount = (int) ($completedStmt->fetch()['total'] ?? 0);
$avgResult = db()->prepare('SELECT AVG(score) AS avg FROM attempts WHERE student_id = :student_id AND status = "submitted"');
$avgResult->execute([':student_id' => $user['id']]);
$avgScore = (float) ($avgResult->fetch()['avg'] ?? 0);
$upcoming = db()->query('SELECT * FROM exams WHERE status = "published" AND start_time > NOW() ORDER BY start_time ASC LIMIT 5')->fetchAll();
$recentStmt = db()->prepare('SELECT a.*, e.title, e.total_marks FROM attempts a JOIN exams e ON e.id = a.exam_id WHERE a.student_id = :student_id AND a.status = "submitted" ORDER BY a.submitted_at DESC LIMIT 5');
$recentStmt->execute([':student_id' => $user['id']]);
$recent = $recentStmt->fetchAll();

$title = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_student_sidebar() ?>
    <main class="main-panel">
        <div class="topbar">
            <h1>Welcome, <?= e($user['name']) ?></h1>
            <a class="button" href="<?= e(app_url('student/exams.php')) ?>">View exams</a>
        </div>
        <div class="card-grid">
            <div class="stat-card"><span>Available exams</span><strong><?= e($availableCount) ?></strong></div>
            <div class="stat-card"><span>Completed exams</span><strong><?= e($completedCount) ?></strong></div>
            <div class="stat-card"><span>Average score</span><strong><?= e(round($avgScore, 1)) ?></strong></div>
        </div>
        <div class="panel-grid">
            <section class="panel-card">
                <h2>Upcoming exams</h2>
                <table>
                    <thead><tr><th>Exam</th><th>Start</th><th>End</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcoming as $exam): ?>
                        <tr><td><?= e($exam['title']) ?></td><td><?= e($exam['start_time']) ?></td><td><?= e($exam['end_time']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            <section class="panel-card">
                <h2>Recent results</h2>
                <table>
                    <thead><tr><th>Exam</th><th>Score</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $result): ?>
                        <tr><td><?= e($result['title']) ?></td><td><?= e($result['score']) ?>/<?= e($result['total_marks']) ?></td><td><?= e($result['submitted_at']) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
