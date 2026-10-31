<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf((string) ($_POST['csrf_token'] ?? ''));
    $action = $_POST['action'] ?? '';
    $studentId = safe_int($_POST['student_id'] ?? 0);
    if ($action === 'toggle_status') {
        $current = db()->prepare('SELECT status FROM users WHERE id = :id AND role = "student" LIMIT 1');
        $current->execute([':id' => $studentId]);
        $row = $current->fetch();
        if ($row) {
            $newStatus = $row['status'] === 'active' ? 'disabled' : 'active';
            db()->prepare('UPDATE users SET status = :status WHERE id = :id')->execute([':status' => $newStatus, ':id' => $studentId]);
            flash('success', 'Student status updated.');
        }
        redirect('admin/students.php');
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$status = $_GET['status'] ?? 'all';
$sql = 'SELECT * FROM users WHERE role = "student"';
$params = [];
if ($search !== '') {
    $sql .= ' AND (name LIKE :search OR email LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
if ($status !== 'all') {
    $sql .= ' AND status = :status';
    $params[':status'] = $status;
}
$sql .= ' ORDER BY created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$title = 'Students';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <main class="main-panel">
        <div class="topbar"><h1>Student management</h1></div>
        <form class="toolbar" method="get">
            <input type="text" name="search" placeholder="Search by name/email" value="<?= e($search) ?>">
            <select name="status">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>Disabled</option>
            </select>
            <button class="button" type="submit">Filter</button>
        </form>
        <section class="panel-card">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Created</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= e($student['name']) ?></td>
                        <td><?= e($student['email']) ?></td>
                        <td><span class="badge <?= e($student['status']) ?>"><?= e($student['status']) ?></span></td>
                        <td><?= e($student['created_at']) ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="student_id" value="<?= e($student['id']) ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <button type="submit" class="link-button"><?= $student['status'] === 'active' ? 'Disable' : 'Enable' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
