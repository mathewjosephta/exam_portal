<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

$pdo = db();

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));

$validToken = preg_match('/\A[a-f0-9]{64}\z/', $token) === 1;
$resetRecord = false;
$error = '';
$success = false;

if ($validToken) {
    $stmt = $pdo->prepare(
        'SELECT id, user_id
         FROM password_resets
         WHERE token_hash = :token_hash
           AND expires_at > NOW()
         LIMIT 1'
    );

    $stmt->execute([
        ':token_hash' => hash('sha256', $token),
    ]);

    $resetRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!$validToken || !$resetRecord) {
        $error = 'This reset link is invalid or has expired. Request a new one.';
    } elseif (strlen($password) < 8) {
        $error = 'Your password must contain at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } else {
        try {
            $pdo->beginTransaction();

            // Lock the token row to prevent concurrent reuse.
            $stmt = $pdo->prepare(
                'SELECT id, user_id
                 FROM password_resets
                 WHERE id = :id
                   AND token_hash = :token_hash
                   AND expires_at > NOW()
                 FOR UPDATE'
            );

            $stmt->execute([
                ':id' => $resetRecord['id'],
                ':token_hash' => hash('sha256', $token),
            ]);

            $currentReset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$currentReset) {
                $pdo->rollBack();
                $error = 'This reset link is invalid or has expired. Request a new one.';
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET password_hash = :password_hash
                     WHERE id = :user_id'
                );

                $stmt->execute([
                    ':password_hash' => password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    ),
                    ':user_id' => $currentReset['user_id'],
                ]);

                // Invalidate all reset tokens for this account.
                $stmt = $pdo->prepare(
                    'DELETE FROM password_resets WHERE user_id = :user_id'
                );

                $stmt->execute([
                    ':user_id' => $currentReset['user_id'],
                ]);

                $pdo->commit();
                $success = true;
            }
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Password reset failed: ' . $exception->getMessage());
            $error = 'Unable to reset your password right now. Please try again.';
        }
    }
}

$pageTitle = 'Reset Password';
$basePath = app_url('');

require_once __DIR__ . '/includes/header.php';
?>

<section class="forgot-page">
    <section class="forgot-card">
        <?php if ($success): ?>

            <h1>Password updated</h1>

            <p>Your password has been changed successfully.</p>

            <a class="forgot-button button-link" href="login.php">
                Continue to login
            </a>

        <?php elseif (!$resetRecord): ?>

            <h1>Reset link expired</h1>

            <p>
                This link is invalid or has expired.
                Please request a new password reset link.
            </p>

            <a class="forgot-button button-link" href="forgot-password.php">
                Request a new link
            </a>

        <?php else: ?>

            <h1>Create a new password</h1>

            <p>Choose a new password for your examspace account.</p>

            <?php if ($error !== ''): ?>
                <div class="forgot-message" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="reset-password.php">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrf_token()) ?>"
                >

                <input
                    type="hidden"
                    name="token"
                    value="<?= e($token) ?>"
                >

                <label for="password">New password</label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <label for="confirm_password">Confirm new password</label>

                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >

                <button type="submit" class="forgot-button">
                    Update password
                </button>
            </form>

        <?php endif; ?>
    </section>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>