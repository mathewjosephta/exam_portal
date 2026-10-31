<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_student();
require_once __DIR__ . '/../includes/student-sidebar.php';

$user = current_user();
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));

    if (preg_match('/\A[^\r\n]{2,120}\z/u', $name) !== 1) {
        $error = 'Your name must be between 2 and 120 characters.';
    } elseif (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        try {
            $update = db()->prepare(
                'UPDATE users SET name = :name, email = :email WHERE id = :id AND role = "student"'
            );
            $update->execute([
                ':name' => $name,
                ':email' => $email,
                ':id' => $user['id'],
            ]);
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            $user['name'] = $name;
            $user['email'] = $email;
            $success = 'Your profile has been updated.';
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'That email address is already associated with another account.';
            } else {
                error_log('Student profile update failed: ' . $exception->getMessage());
                $error = 'Unable to update your profile right now. Please try again.';
            }
        }
    }
}

$pageTitle = 'My profile';
$basePath = app_url('');
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-layout">
    <?= render_student_sidebar() ?>
    <section class="dashboard-main">
        <div class="dashboard-topbar"><div><h1>My profile</h1><p>Keep your account details up to date.</p></div></div>
        <div class="dashboard-content">
            <section class="card form-panel">
                <?php if ($error !== null): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
                <?php if ($success !== null): ?><div class="alert alert-success" role="status"><?= e($success) ?></div><?php endif; ?>
                <form method="post" class="stacked-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <label for="name">Full name</label>
                    <input class="form-control" id="name" name="name" maxlength="120" autocomplete="name" value="<?= e($user['name']) ?>" required>
                    <label for="email">Email address</label>
                    <input class="form-control" id="email" name="email" type="email" maxlength="190" autocomplete="email" value="<?= e($user['email']) ?>" required>
                    <button class="btn btn-primary" type="submit">Save changes</button>
                </form>
            </section>
        </div>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
