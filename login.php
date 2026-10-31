<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

// Redirect users who are already logged in.
if (is_logged_in()) {
    $role = $_SESSION['role'] ?? 'student';

    redirect(
        $role === 'admin'
            ? 'admin/dashboard.php'
            : 'student/dashboard.php'
    );
}

$error = get_flash('error');
$success = get_flash('success');

// Process login form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        set_flash('error', 'Please enter a valid email address and password.');
        redirect('login.php');
    }

    try {
        $stmt = db()->prepare(
            'SELECT id, name, email, password_hash, role, status
             FROM users
             WHERE email = :email
             LIMIT 1'
        );

        $stmt->execute([
            ':email' => $email,
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            !$user ||
            $user['status'] !== 'active' ||
            !password_verify($password, $user['password_hash'])
        ) {
            set_flash('error', 'Invalid email or password.');
            redirect('login.php');
        }

        // Regenerate session ID after successful authentication.
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['status'] = $user['status'];

        redirect(
            $user['role'] === 'admin'
                ? 'admin/dashboard.php'
                : 'student/dashboard.php'
        );

    } catch (PDOException $exception) {
        error_log('Login database error: ' . $exception->getMessage());

        set_flash(
            'error',
            'Unable to log in right now. Please try again later.'
        );

        redirect('login.php');
    }
}

$pageTitle = 'Login';
$basePath = app_url('');
$minimalNavigation = true;

require_once __DIR__ . '/includes/header.php';
?>

<section class="login-page">

    <section class="login-card">

        <div class="login-heading">
            <h1>Welcome back</h1>

            <p>
                Log in to your examspace account to continue.
            </p>
        </div>

        <?php if ($error !== null): ?>
            <div class="login-alert login-alert-error" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== null): ?>
            <div class="login-alert login-alert-success" role="status">
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <form
            class="login-form"
            method="POST"
            action="login.php"
        >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e(csrf_token()) ?>"
            >

            <div class="login-field">

                <label for="email">Email address</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email address"
                    autocomplete="email"
                    value="<?= e($_POST['email'] ?? '') ?>"
                    required
                >

            </div>

            <div class="login-field">

                <label for="password">Password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

            </div>

            <!-- Forgot Password Link -->
            <div class="forgot-password">
                <a href="forgot-password.php">
                    Forgot password?
                </a>
            </div>

            <button
                type="submit"
                class="login-button"
            >
                Log In
            </button>

        </form>

        <div class="login-footer">
            <p>
                Don't have an account?
                <a href="register.php">Create an account</a>
            </p>
        </div>

    </section>

</section>

<style>
.login-page {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 48px 20px;
    background: #f8faf9;
}

.login-card {
    width: 100%;
    max-width: 420px;
    padding: 36px;
    background: #ffffff;
    border: 1px solid #e5ebe7;
    border-radius: 16px;
    box-shadow: 0 8px 30px rgba(20, 40, 30, 0.05);
}

.login-heading {
    margin-bottom: 28px;
}

.login-heading h1 {
    margin: 0 0 10px;
    color: #17251d;
    font-size: 28px;
    font-weight: 700;
}

.login-heading p {
    margin: 0;
    color: #69766e;
    font-size: 14px;
    line-height: 1.6;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.login-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.login-field label {
    color: #26352c;
    font-size: 14px;
    font-weight: 600;
}

.login-field input {
    width: 100%;
    box-sizing: border-box;
    padding: 13px 14px;
    border: 1px solid #dce5df;
    border-radius: 9px;
    outline: none;
    background: #ffffff;
    color: #17251d;
    font-size: 14px;
}

.login-field input:focus {
    border-color: #25d878;
    box-shadow: 0 0 0 3px rgba(37, 216, 120, 0.12);
}

.forgot-password {
    display: block !important;
    width: 100%;
    margin-top: -8px;
    margin-bottom: 0;
    text-align: right;
}

.forgot-password a {
    display: inline-block;
    color: #159957;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    visibility: visible;
}

.forgot-password a:hover {
    text-decoration: underline;
}

.login-button {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 9px;
    background: #25d878;
    color: #10251a;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s ease;
}

.login-button:hover {
    background: #1ec56a;
}

.login-footer {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #edf1ee;
    text-align: center;
}

.login-footer p {
    margin: 0;
    color: #69766e;
    font-size: 13px;
}

.login-footer a {
    color: #159957;
    font-weight: 600;
    text-decoration: none;
}

.login-footer a:hover {
    text-decoration: underline;
}

.login-alert {
    margin-bottom: 20px;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 13px;
    line-height: 1.6;
}

.login-alert-error {
    background: #fff0f0;
    color: #b42318;
}

.login-alert-success {
    background: #effaf3;
    color: #17663a;
}

@media (max-width: 480px) {
    .login-card {
        padding: 26px 20px;
    }

    .login-heading h1 {
        font-size: 25px;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>