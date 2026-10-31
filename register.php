<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect(
        ($_SESSION['role'] ?? '') === 'admin'
            ? 'admin/dashboard.php'
            : 'student/dashboard.php'
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (preg_match('/\A[^\r\n]{2,120}\z/u', $name) !== 1) {
        flash('error', 'Name must be between 2 and 120 characters.');
        redirect('register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Enter a valid email.');
        redirect('register.php');
    }

    if (strlen($password) < 8 || $password !== $confirm) {
        flash(
            'error',
            'Password must be at least 8 characters and match confirm password.'
        );
        redirect('register.php');
    }

    try {
        if (get_user_by_email($email)) {
            flash('error', 'This email is already registered.');
            redirect('register.php');
        }

        $stmt = db()->prepare(
            'INSERT INTO users (name, email, password_hash, role)
             VALUES (:name, :email, :password_hash, :role)'
        );
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ':role' => 'student',
        ]);
    } catch (PDOException $exception) {
        error_log('Registration database error: ' . $exception->getMessage());
        flash('error', 'Unable to create your account right now. Please try again later.');
        redirect('register.php');
    }

    flash('success', 'Registration successful. Please log in.');
    redirect('login.php');
}

$pageTitle = 'Create account';
$basePath = app_url('');
$minimalNavigation = true;
$error = get_flash('error');
$success = get_flash('success');

include __DIR__ . '/includes/header.php';
?>

<section class="register-page">

    <div class="register-container">

        <div class="register-aside">

            <div class="brand">
                <span class="brand-mark">e.</span>
                <span>exam<span class="brand-light">space</span></span>
            </div>

            <div class="register-message">

                <span class="register-tag">
                    <span class="badge-dot"></span>
                    YOUR LEARNING JOURNEY STARTS HERE
                </span>

                <h1>
                    Make room<br>
                    for your <span>next level.</span>
                </h1>

                <p>
                    Create your account to access examinations,
                    track your results, and keep your academic
                    progress organized.
                </p>

                <div class="register-benefits">
                    <div>
                        <span class="benefit-check">✓</span>
                        One place for your examinations
                    </div>

                    <div>
                        <span class="benefit-check">✓</span>
                        Easy access to your results
                    </div>

                    <div>
                        <span class="benefit-check">✓</span>
                        A simpler exam experience
                    </div>
                </div>

            </div>

            <p class="register-copyright">
                © <?= date('Y') ?> examspace
            </p>

        </div>

        <div class="register-main">

            <div class="register-card">

                <div class="register-heading">
                    <span class="register-step">GET STARTED</span>

                    <h2>Create your account</h2>

                    <p>
                        Enter your details below to join examspace.
                    </p>
                </div>

                <?php if ($error !== null): ?>
                    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($success !== null): ?>
                    <div class="alert alert-success" role="status"><?= e($success) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e($basePath) ?>/register.php"
                      class="register-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="form-group">
                        <label for="name">Full name</label>

                        <input
                            class="form-control"
                            type="text"
                            id="name"
                            name="name"
                            placeholder="Enter your full name"
                            minlength="2"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Email address</label>

                        <input
                            class="form-control"
                            type="email"
                            id="email"
                            name="email"
                            placeholder="you@example.com"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>

                        <input
                            class="form-control"
                            type="password"
                            id="password"
                            name="password"
                            placeholder="At least 8 characters"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            Confirm password
                        </label>

                        <input
                            class="form-control"
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            placeholder="Enter your password again"
                            minlength="8"
                            autocomplete="new-password"
                            required
                        >

                        <small id="passwordMessage"
                               class="password-message"></small>
                    </div>

                    <button
                        class="btn btn-primary register-submit"
                        type="submit"
                    >
                        Create account
                        <span aria-hidden="true">↗</span>
                    </button>

                </form>

                <div class="register-divider">
                    <span>ALREADY A MEMBER?</span>
                </div>

                <a
                    class="register-login"
                    href="<?= e($basePath) ?>/login.php"
                >
                    Log in to your account
                    <span aria-hidden="true">→</span>
                </a>

            </div>

        </div>

    </div>

</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const message = document.getElementById('passwordMessage');

    function validatePasswords() {
        if (!confirmPassword.value) {
            message.textContent = '';
            confirmPassword.setCustomValidity('');
            return;
        }

        if (password.value !== confirmPassword.value) {
            message.textContent = 'Passwords do not match.';
            confirmPassword.setCustomValidity('Passwords do not match.');
        } else {
            message.textContent = 'Passwords match.';
            confirmPassword.setCustomValidity('');
        }
    }

    password.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
