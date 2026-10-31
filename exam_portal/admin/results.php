<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

$search = trim((string) ($_GET['search'] ?? ''));
$examId = safe_int($_GET['exam_id'] ?? 0);
$sql = 'SELECT a.id, a.score, a.submitted_at, u.name, u.email, e.title AS exam_title, e.total_marks FROM attempts a JOIN users u ON u.id = a.student_id JOIN exams e ON e.id = a.exam_id WHERE a.status = "submitted"';
$params = [];
if ($search !== '') {
    $sql .= ' AND (u.name LIKE :search OR u.email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($examId > 0) {
    $sql .= ' AND e.id = :exam_id';
    $params[':exam_id'] = $examId;
}
$sql .= ' ORDER BY a.submitted_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();
$exams = db()->query('SELECT * FROM exams ORDER BY title')->fetchAll();

$title = 'Results';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <main class="main-panel">
        <div class="topbar"><h1>Submitted results</h1></div>
        <form class="toolbar" method="get">
            <input type="text" name="search" placeholder="Search name or email" value="<?= e($search) ?>">
            <select name="exam_id">
                <option value="0">All exams</option>
                <?php foreach ($exams as $exam): ?>
                    <option value="<?= e($exam['id']) ?>" <?= $examId === (int) $exam['id'] ? 'selected' : '' ?>><?= e($exam['title']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button" type="submit">Filter</button>
        </form>
        <section class="panel-card">
            <table>
                <thead><tr><th>Student</th><th>Exam</th><th>Score</th><th>Percentage</th><th>Submitted</th><th>Details</th></tr></thead>
                <tbody>
                <?php foreach ($results as $row): ?>
                    <?php $pct = $row['total_marks'] > 0 ? round(($row['score'] / $row['total_marks']) * 100, 2) : 0; ?>
                    <tr>
                        <td><?= e($row['name']) ?> (<?= e($row['email']) ?>)</td>
                        <td><?= e($row['exam_title']) ?></td>
                        <td><?= e($row['score']) ?>/<?= e($row['total_marks']) ?></td>
                        <td><?= e($pct) ?>%</td>
                        <td><?= e($row['submitted_at']) ?></td>
                        <td><a href="<?= e(app_url('admin/result-details.php?attempt_id=' . $row['id'])) ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
