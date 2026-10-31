<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $user = get_user_by_email($email);
    if ($user && password_verify($password, $user['password']) && $user['status'] === 'active') {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['status'] = $user['status'];
        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
    }
    flash('error', 'Invalid email or password.');
    redirect('login.php');
}

$title = 'Login';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-shell">
    <div class="auth-card">
        <h1>Login</h1>
        <form method="post" class="auth-form">
            <label>Email<input type="email" name="email" required></label>
            <label>Password<input type="password" name="password" required></label>
            <button class="button" type="submit">Login</button>
        </form>
        <div class="auth-links">
            <a href="<?= e(app_url('register.php')) ?>">Register</a>
            <a href="<?= e(app_url('forgot-password.php')) ?>">Forgot password</a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
