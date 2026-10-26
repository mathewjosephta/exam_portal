<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $email = strtolower(trim(request_string($_POST, 'email')));
    $password = request_string($_POST, 'password');

    $query = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = :email');
    $query->execute(['email' => $email]);
    $user = $query->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        redirect($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
    }
    $error = 'The email address or password is incorrect.';
}

render_header('Sign in');
?>
<section class="auth-card">
    <h1>Welcome back</h1>
    <p class="muted">Sign in to continue to your ExamPortal account.</p>
    <?php if ($error !== ''): ?><div class="notice notice-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" required maxlength="190">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button class="button" type="submit">Sign in</button>
    </form>
    <p class="auth-switch">New to ExamPortal? <a href="<?= e(app_url('register.php')) ?>">Create an account</a></p>
</section>
<?php render_footer(); ?>
