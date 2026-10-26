<?php
declare(strict_types=1);
require __DIR__ . '/../config/bootstrap.php';
require_role('admin');

$stats = [
    'exams' => (int) $pdo->query('SELECT COUNT(*) FROM exams')->fetchColumn(),
    'published' => (int) $pdo->query("SELECT COUNT(*) FROM exams WHERE status = 'published'")->fetchColumn(),
    'students' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn(),
    'submissions' => (int) $pdo->query("SELECT COUNT(*) FROM attempts WHERE status = 'submitted'")->fetchColumn(),
];

render_header('Admin dashboard');
?>
<div class="page-heading"><div><p class="eyebrow">ADMINISTRATION</p><h1>Dashboard</h1><p class="muted">Create examinations, manage questions, and review submissions.</p></div><a class="button" href="<?= e(app_url('admin/create-exam.php')) ?>">Create exam</a></div>
<div class="stat-grid">
    <div class="stat-card"><span>All exams</span><strong><?= $stats['exams'] ?></strong></div>
    <div class="stat-card"><span>Published exams</span><strong><?= $stats['published'] ?></strong></div>
    <div class="stat-card"><span>Students</span><strong><?= $stats['students'] ?></strong></div>
    <div class="stat-card"><span>Submissions</span><strong><?= $stats['submissions'] ?></strong></div>
</div>
<div class="quick-links">
    <a class="card" href="<?= e(app_url('admin/exams.php')) ?>"><h2>Manage exams</h2><p class="muted">Publish exams and add questions.</p></a>
    <a class="card" href="<?= e(app_url('admin/results.php')) ?>"><h2>Review results</h2><p class="muted">See submitted scores and student attempts.</p></a>
</div>
<?php render_footer(); ?>
