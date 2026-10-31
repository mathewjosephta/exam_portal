<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

$studentCount = (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn();
$activeCount = (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "student" AND status = "active"')->fetchColumn();
$examCount = (int) db()->query('SELECT COUNT(*) FROM exams')->fetchColumn();
$publishedCount = (int) db()->query('SELECT COUNT(*) FROM exams WHERE status = "published"')->fetchColumn();
$questionCount = (int) db()->query('SELECT COUNT(*) FROM questions')->fetchColumn();
$attemptCount = (int) db()->query('SELECT COUNT(*) FROM attempts')->fetchColumn();
$recentActivity = db()->query('SELECT a.id, u.name, e.title, a.status, a.submitted_at FROM attempts a JOIN users u ON u.id = a.student_id JOIN exams e ON e.id = a.exam_id ORDER BY a.created_at DESC LIMIT 5')->fetchAll();
$recentResults = db()->query('SELECT a.id, u.name, e.title, a.score, e.total_marks, a.submitted_at FROM attempts a JOIN users u ON u.id = a.student_id JOIN exams e ON e.id = a.exam_id WHERE a.status = "submitted" ORDER BY a.submitted_at DESC LIMIT 5')->fetchAll();

$title = 'Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <main class="main-panel">
        <div class="topbar">
            <h1>Admin Dashboard</h1>
            <span class="user-chip"><?= e(current_user()['name']) ?></span>
        </div>
        <div class="card-grid">
            <div class="stat-card"><span>Total students</span><strong><?= e($studentCount) ?></strong></div>
            <div class="stat-card"><span>Active students</span><strong><?= e($activeCount) ?></strong></div>
            <div class="stat-card"><span>Total exams</span><strong><?= e($examCount) ?></strong></div>
            <div class="stat-card"><span>Published exams</span><strong><?= e($publishedCount) ?></strong></div>
            <div class="stat-card"><span>Total questions</span><strong><?= e($questionCount) ?></strong></div>
            <div class="stat-card"><span>Total attempts</span><strong><?= e($attemptCount) ?></strong></div>
        </div>
        <div class="panel-grid">
            <section class="panel-card">
                <h2>Recent activity</h2>
                <table>
                    <thead><tr><th>Student</th><th>Exam</th><th>Status</th><th>Submitted</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentActivity as $row): ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <td><?= e($row['title']) ?></td>
                            <td><?= e($row['status']) ?></td>
                            <td><?= e($row['submitted_at'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            <section class="panel-card">
                <h2>Recent results</h2>
                <table>
                    <thead><tr><th>Student</th><th>Exam</th><th>Score</th><th>Submitted</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentResults as $row): ?>
                        <tr>
                            <td><?= e($row['name']) ?></td>
                            <td><?= e($row['title']) ?></td>
                            <td><?= e($row['score']) ?>/<?= e($row['total_marks']) ?></td>
                            <td><?= e($row['submitted_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
