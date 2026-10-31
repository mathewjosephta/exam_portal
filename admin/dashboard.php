<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

$counts = [
    'students' => (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "student"')->fetchColumn(),
    'active_students' => (int) db()->query('SELECT COUNT(*) FROM users WHERE role = "student" AND status = "active"')->fetchColumn(),
    'exams' => (int) db()->query('SELECT COUNT(*) FROM exams')->fetchColumn(),
    'published' => (int) db()->query('SELECT COUNT(*) FROM exams WHERE status = "published"')->fetchColumn(),
    'questions' => (int) db()->query('SELECT COUNT(*) FROM questions')->fetchColumn(),
    'attempts' => (int) db()->query('SELECT COUNT(*) FROM attempts')->fetchColumn(),
];
$activity = db()->query(
    'SELECT a.id, a.status, a.submitted_at, u.name AS student_name, e.title AS exam_title
     FROM attempts a
     JOIN users u ON u.id = a.student_id
     JOIN exams e ON e.id = a.exam_id
     ORDER BY COALESCE(a.submitted_at, a.started_at) DESC LIMIT 8'
)->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Admin dashboard';
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <section class="dashboard-main">
        <div class="dashboard-topbar"><div><h1>Admin dashboard</h1><p>Manage your exam portal from one place.</p></div><a class="btn btn-primary" href="<?= e(app_url('admin/create-exam.php')) ?>">Create an exam</a></div>
        <div class="dashboard-content">
            <div class="card-grid stats-grid">
                <article class="card stat-card"><span>Total students</span><strong><?= e($counts['students']) ?></strong><small><?= e($counts['active_students']) ?> active</small></article>
                <article class="card stat-card"><span>Total exams</span><strong><?= e($counts['exams']) ?></strong><small><?= e($counts['published']) ?> published</small></article>
                <article class="card stat-card"><span>Questions</span><strong><?= e($counts['questions']) ?></strong></article>
                <article class="card stat-card"><span>Attempts</span><strong><?= e($counts['attempts']) ?></strong></article>
            </div>
            <section class="card panel-card">
                <div class="panel-heading"><h2>Recent exam activity</h2><a href="<?= e(app_url('admin/results.php')) ?>">View results</a></div>
                <?php if ($activity): ?>
                    <div class="table-wrapper"><table>
                        <thead><tr><th>Student</th><th>Exam</th><th>Status</th><th>Submitted</th></tr></thead>
                        <tbody>
                        <?php foreach ($activity as $item): ?>
                            <tr>
                                <td><?= e($item['student_name']) ?></td>
                                <td><?= e($item['exam_title']) ?></td>
                                <td><span class="badge <?= $item['status'] === 'submitted' ? 'badge-success' : '' ?>"><?= e(str_replace('_', ' ', $item['status'])) ?></span></td>
                                <td><?= $item['submitted_at'] ? e(date('M j, Y g:i A', strtotime($item['submitted_at']))) : 'In progress' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php else: ?>
                    <div class="empty-state">Student activity will appear here when an exam is started.</div>
                <?php endif; ?>
            </section>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
