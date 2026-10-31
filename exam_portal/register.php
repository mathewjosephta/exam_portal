<?php

declare(strict_types=1);

session_start();
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect($_SESSION['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    if (mb_strlen($name) < 2) {
        flash('error', 'Name must be at least 2 characters.');
        redirect('register.php');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Enter a valid email.');
        redirect('register.php');
    }
    if (strlen($password) < 8 || $password !== $confirm) {
        flash('error', 'Password must be at least 8 characters and match confirm password.');
        redirect('register.php');
    }
    if (get_user_by_email($email)) {
        flash('error', 'This email is already registered.');
        redirect('register.php');
    }
    $stmt = db()->prepare('INSERT INTO users (name, email, password, role, status) VALUES (:name, :email, :password, :role, :status)');
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => 'student',
        ':status' => 'active',
    ]);
    flash('success', 'Registration successful. Please log in.');
    redirect('login.php');
}

$title = 'Register';
include __DIR__ . '/includes/header.php';
?>
<div class="auth-shell">
    <div class="auth-card">
        <h1>Register</h1>
        <form method="post" class="auth-form">
            <label>Name<input type="text" name="name" required></label>
            <label>Email<input type="email" name="email" required></label>
            <label>Password<input type="password" name="password" required></label>
            <label>Confirm password<input type="password" name="confirm_password" required></label>
            <button class="button" type="submit">Register</button>
        </form>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
