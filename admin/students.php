<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/admin-sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $studentId = safe_int($_POST['student_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($studentId <= 0 || !in_array($status, ['active', 'disabled'], true)) {
        set_flash('error', 'Invalid student update.');
        redirect('admin/students.php');
    }

    $update = db()->prepare(
        'UPDATE users SET status = :status WHERE id = :id AND role = "student"'
    );
    $update->execute([':status' => $status, ':id' => $studentId]);
    set_flash('success', $update->rowCount() > 0
        ? 'Student status updated.'
        : 'No student account was changed.');
    redirect('admin/students.php');
}

$search = trim((string) ($_GET['search'] ?? ''));
$status = $_GET['status'] ?? 'all';
if (!in_array($status, ['all', 'active', 'disabled'], true)) {
    $status = 'all';
}
$sql = 'SELECT id, name, email, status, created_at FROM users WHERE role = "student"';
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
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Students';
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_admin_sidebar() ?>
    <section class="dashboard-main">
        <div class="dashboard-topbar"><div><h1>Students</h1><p>Manage student accounts and access.</p></div></div>
        <div class="dashboard-content">
            <form class="filter-form" method="get">
                <input class="form-control" type="search" name="search" placeholder="Search name or email" value="<?= e($search) ?>">
                <select class="form-control" name="status">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All accounts</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="disabled" <?= $status === 'disabled' ? 'selected' : '' ?>>Disabled</option>
                </select>
                <button class="btn btn-primary" type="submit">Filter</button>
            </form>
            <section class="card panel-card">
                <?php if (!$students): ?>
                    <div class="empty-state">No student accounts match your search.</div>
                <?php else: ?>
                    <div class="table-wrapper"><table>
                        <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Registered</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($students as $student): ?>
                            <?php $nextStatus = $student['status'] === 'active' ? 'disabled' : 'active'; ?>
                            <tr>
                                <td><?= e($student['name']) ?></td>
                                <td><?= e($student['email']) ?></td>
                                <td><span class="badge <?= $student['status'] === 'active' ? 'badge-success' : '' ?>"><?= e($student['status']) ?></span></td>
                                <td><?= e(date('M j, Y', strtotime($student['created_at']))) ?></td>
                                <td>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="student_id" value="<?= e($student['id']) ?>">
                                        <input type="hidden" name="status" value="<?= e($nextStatus) ?>">
                                        <button class="text-button" type="submit"><?= $nextStatus === 'active' ? 'Enable' : 'Disable' ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table></div>
                <?php endif; ?>
            </section>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
