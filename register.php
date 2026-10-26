<?php
declare(strict_types=1);
require __DIR__ . '/config/bootstrap.php';

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
}

$errors = [];
$name = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['csrf_token'] ?? null);
    $name = trim(request_string($_POST, 'name'));
    $email = strtolower(trim(request_string($_POST, 'email')));
    $password = request_string($_POST, 'password');
    $confirmation = request_string($_POST, 'password_confirmation');

    if ($name === '' || mb_strlen($name) > 120) {
        $errors[] = 'Enter a name no longer than 120 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Your password must be between 8 and 72 bytes long.';
    }
    if ($password !== $confirmation) {
        $errors[] = 'The password confirmation does not match.';
    }

    if (!$errors) {
        $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $existing->execute(['email' => $email]);
        if ($existing->fetch()) {
            $errors[] = 'An account with that email address already exists.';
        } else {
            $insert = $pdo->prepare(
                "INSERT INTO users (name, email, password_hash, role)
                 VALUES (:name, :email, :password_hash, 'student')"
            );
            $insert->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            $_SESSION['role'] = 'student';
            flash('success', 'Your student account is ready.');
            redirect('student/dashboard.php');
        }
    }
}

render_header('Create account');
?>
<section class="auth-card">
    <h1>Create a student account</h1>
    <p class="muted">Register to access and take available examinations.</p>
    <?php if ($errors): ?>
        <div class="notice notice-error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label for="name">Full name</label>
        <input id="name" name="name" type="text" value="<?= e($name) ?>" autocomplete="name" required maxlength="120">
        <label for="email">Email address</label>
        <input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" required maxlength="190">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        <label for="password_confirmation">Confirm password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
        <button class="button" type="submit">Create account</button>
    </form>
    <p class="auth-switch">Already registered? <a href="<?= e(app_url('login.php')) ?>">Sign in</a></p>
</section>
<?php render_footer(); ?>
