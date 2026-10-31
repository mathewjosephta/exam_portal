<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();
require_once __DIR__ . '/../includes/student-sidebar.php';

$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf((string) ($_POST['csrf_token'] ?? ''));
    $name = trim((string) ($_POST['name'] ?? ''));
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    if (mb_strlen($name) < 2) {
        flash('error', 'Name must be at least 2 characters.');
        redirect('student/profile.php');
    }
    $stored = db()->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
    $stored->execute([':id' => $user['id']]);
    $stored = $stored->fetch();
    if (!$stored || !password_verify($currentPassword, $stored['password'])) {
        flash('error', 'Current password is incorrect.');
        redirect('student/profile.php');
    }
    if ($newPassword !== '') {
        if (strlen($newPassword) < 8 || $newPassword !== $confirmPassword) {
            flash('error', 'New password must be at least 8 chars and match confirmation.');
            redirect('student/profile.php');
        }
        db()->prepare('UPDATE users SET name = :name, password = :password WHERE id = :id')->execute([
            ':name' => $name,
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':id' => $user['id'],
        ]);
    } else {
        db()->prepare('UPDATE users SET name = :name WHERE id = :id')->execute([':name' => $name, ':id' => $user['id']]);
    }
    $_SESSION['user_name'] = $name;
    flash('success', 'Profile updated.');
    redirect('student/profile.php');
}

$title = 'Profile';
include __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_student_sidebar() ?>
    <main class="main-panel">
        <div class="topbar"><h1>My profile</h1></div>
        <section class="panel-card form-card">
            <form method="post" class="stacked-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <label>Name<input type="text" name="name" value="<?= e($user['name']) ?>" required></label>
                <label>Email<input type="email" value="<?= e($user['email']) ?>" readonly></label>
                <label>Current password<input type="password" name="current_password" required></label>
                <label>New password (optional)<input type="password" name="new_password"></label>
                <label>Confirm password<input type="password" name="confirm_password"></label>
                <button class="button" type="submit">Save profile</button>
            </form>
        </section>
    </main>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
